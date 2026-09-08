<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ToolStoreController extends Controller
{
    /**
     * Display the Tools & Software Store catalog.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $softwares = SerialSoftware::whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(function (SerialSoftware $software) use ($user) {
                $userLicense = null;
                if ($user) {
                    $userLicense = SerialSoftwareLicense::where('user_id', $user->id)
                        ->where('serial_software_id', $software->id)
                        ->active()
                        ->first();
                }

                return [
                    'id'                   => $software->id,
                    'name'                 => $software->name,
                    'default_status'       => $software->default_status,
                    'requires_payment'     => (bool) $software->requires_payment,
                    'price'                => $software->price !== null ? (float) $software->price : 0.0,
                    'currency'             => $software->currency ?: 'USD',
                    'payment_instructions' => $software->payment_instructions,
                    'whatsapp_number'      => $software->whatsapp_number,
                    'has_license'          => (bool) $userLicense,
                    'license_expires_at'   => $userLicense?->expires_at?->toIso8601String(),
                ];
            });

        $userLicensesCount = $user
            ? SerialSoftwareLicense::where('user_id', $user->id)->active()->count()
            : 0;

        return Inertia::render('Store/Tools/Index', [
            'softwares'         => $softwares,
            'userLicensesCount' => $userLicensesCount,
        ]);
    }

    /**
     * Purchase or activate software license by email.
     */
    public function purchase(Request $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $validated = $request->validate([
            'email'     => ['required', 'email', 'max:255'],
            'name'      => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $cairoNow = now()->setTimezone('Africa/Cairo');
        $expiry = (clone $cairoNow)->addYear(); // 1 year standard license

        DB::transaction(function () use ($email, $validated, $serialSoftware, $expiry, $cairoNow) {
            // Find or create user
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                $user = User::create([
                    'name'     => ! empty($validated['name']) ? trim($validated['name']) : explode('@', $email)[0],
                    'email'    => $email,
                    'password' => Hash::make(Str::random(16)),
                ]);

                if (! empty($validated['phone']) && \Illuminate\Support\Facades\Schema::hasColumn('users', 'mobile_1')) {
                    $user->update(['mobile_1' => $validated['phone']]);
                }
            }

            // Create or update active software license
            $license = SerialSoftwareLicense::withTrashed()->updateOrCreate(
                [
                    'user_id'            => $user->id,
                    'serial_software_id' => $serialSoftware->id,
                ],
                [
                    'status'     => SerialSoftwareLicense::STATUS_ACTIVE,
                    'expires_at' => $expiry,
                    'deleted_at' => null,
                ]
            );

            // If device_id is provided, immediately bind and activate device
            if (! empty($validated['device_id'])) {
                $deviceId = trim($validated['device_id']);

                SerialUserDevice::withTrashed()->updateOrCreate(
                    ['device_id' => $deviceId],
                    [
                        'user_id'    => $user->id,
                        'status'     => SerialUserDevice::STATUS_ACTIVE,
                        'expires_at' => $expiry,
                        'deleted_at' => null,
                    ]
                );

                SerialDevice::updateOrCreate(
                    [
                        'serial_software_id' => $serialSoftware->id,
                        'device_id'          => $deviceId,
                    ],
                    [
                        'status'          => SerialDevice::STATUS_ACTIVE,
                        'last_check_date' => $cairoNow,
                    ]
                );
            }
        });

        return back()->with('success', __('Software license activated successfully on email :email.', ['email' => $email]));
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
