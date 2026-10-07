<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SerialDevice;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\StoreTool;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ToolStoreController extends Controller
{
    /**
     * Display the Tools & Software Store catalog.
     * Only displays tools explicitly published by the Admin.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tools = StoreTool::published()
            ->with('serialSoftware')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (StoreTool $tool) use ($user) {
                $userLicense = null;
                if ($user && $tool->serial_software_id) {
                    $userLicense = SerialSoftwareLicense::where('user_id', $user->id)
                        ->where('serial_software_id', $tool->serial_software_id)
                        ->active()
                        ->first();
                }

                return [
                    'id'                   => $tool->id,
                    'name'                 => $tool->name,
                    'tagline'              => $tool->tagline,
                    'description'          => $tool->description,
                    'version'              => $tool->version,
                    'download_url'         => $tool->download_url,
                    'category'             => $tool->category,
                    'requires_payment'     => (bool) $tool->requires_payment,
                    'price'                => $tool->price !== null ? (float) $tool->price : 0.0,
                    'currency'             => $tool->currency ?: 'USD',
                    'payment_instructions' => $tool->payment_instructions,
                    'whatsapp_number'      => $tool->whatsapp_number,
                    'features'             => $tool->features ?: [],
                    'serial_software_id'   => $tool->serial_software_id,
                    'serial_software_name' => $tool->serialSoftware?->name,
                    'has_license'          => (bool) $userLicense,
                    'license_expires_at'   => $userLicense?->expires_at?->toIso8601String(),
                ];
            });

        $userLicensesCount = $user
            ? SerialSoftwareLicense::where('user_id', $user->id)->active()->count()
            : 0;

        return Inertia::render('Store/Tools/Index', [
            'tools'             => $tools,
            'softwares'         => $tools, // backward compatibility
            'userLicensesCount' => $userLicensesCount,
        ]);
    }

    /**
     * Activate a free store tool license for the signed-in user.
     * Paid tools are activated by an admin after a confirmed manual payment.
     */
    public function purchase(Request $request, StoreTool $storeTool): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $storeTool->is_published) {
            abort(404);
        }

        if ($this->requiresPayment($storeTool)) {
            return back()->with('error', __('This tool requires payment. Please follow the payment instructions and our team will activate your license.'));
        }

        $user = $request->user();
        $deviceId = trim((string) ($validated['device_id'] ?? ''));

        if ($deviceId !== '' && $this->deviceOwnedByAnotherUser($deviceId, $user->id)) {
            throw ValidationException::withMessages([
                'device_id' => __('This device is already linked to another account.'),
            ]);
        }

        if ($storeTool->serial_software_id) {
            DB::transaction(fn () => $this->activateLicense($user, (int) $storeTool->serial_software_id, $deviceId));
        }

        return back()->with('success', __('License for :tool activated successfully on email :email.', [
            'tool'  => $storeTool->name,
            'email' => $user->email,
        ]));
    }

    private function requiresPayment(StoreTool $storeTool): bool
    {
        return (bool) $storeTool->requires_payment || (float) $storeTool->price > 0;
    }

    private function deviceOwnedByAnotherUser(string $deviceId, int $userId): bool
    {
        return SerialUserDevice::withTrashed()
            ->where('device_id', $deviceId)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $userId)
            ->exists();
    }

    private function activateLicense(User $user, int $serialSoftwareId, string $deviceId): void
    {
        $cairoNow = now()->setTimezone('Africa/Cairo');
        $expiry = (clone $cairoNow)->addYear();

        SerialSoftwareLicense::withTrashed()->updateOrCreate(
            ['user_id' => $user->id, 'serial_software_id' => $serialSoftwareId],
            ['status' => SerialSoftwareLicense::STATUS_ACTIVE, 'expires_at' => $expiry, 'deleted_at' => null]
        );

        if ($deviceId === '') {
            return;
        }

        SerialUserDevice::withTrashed()->updateOrCreate(
            ['device_id' => $deviceId],
            ['user_id' => $user->id, 'status' => SerialUserDevice::STATUS_ACTIVE, 'expires_at' => $expiry, 'deleted_at' => null]
        );

        SerialDevice::updateOrCreate(
            ['serial_software_id' => $serialSoftwareId, 'device_id' => $deviceId],
            ['status' => SerialDevice::STATUS_ACTIVE, 'last_check_date' => $cairoNow]
        );
    }

    /**
     * User's owned software licenses and linked devices.
     */
    public function myLicenses(Request $request): Response
    {
        $user = $request->user();

        $licenses = SerialSoftwareLicense::with('software')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (SerialSoftwareLicense $license) {
                return [
                    'id'            => $license->id,
                    'software_name' => $license->software?->name ?? 'Unknown',
                    'status'        => $license->isActive() ? 'active' : ($license->status ?: 'inactive'),
                    'expires_at'    => $license->expires_at?->toIso8601String(),
                    'max_devices'   => $license->max_devices,
                    'created_at'    => $license->created_at?->toIso8601String(),
                ];
            });

        $linkedDevices = SerialUserDevice::with('devices.software')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (SerialUserDevice $ud) {
                $primaryDevice = $ud->devices->first();

                return [
                    'id'              => $ud->id,
                    'device_id'       => $ud->device_id,
                    'status'          => $ud->status,
                    'expires_at'      => $ud->expires_at?->toIso8601String(),
                    'machine_name'    => $primaryDevice?->machine_name,
                    'os_version'      => $primaryDevice?->os_version,
                    'last_check_date' => $primaryDevice?->last_check_date?->toIso8601String(),
                    'software_name'   => $primaryDevice?->software?->name ?? 'Assigned Device',
                ];
            });

        return Inertia::render('Store/Tools/MyLicenses', [
            'licenses'      => $licenses,
            'linkedDevices' => $linkedDevices,
        ]);
    }
}
