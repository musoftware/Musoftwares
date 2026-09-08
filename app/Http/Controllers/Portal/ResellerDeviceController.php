<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\Reseller\AssignDeviceRequest;
use App\Http\Requests\Portal\Reseller\RenewDeviceRequest;
use App\Models\SerialDevice;
use App\Models\SerialUserDevice;
use App\Services\ResellerDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResellerDeviceController extends Controller
{
    public function __construct(
        protected ResellerDeviceService $resellerDeviceService
    ) {}

    /**
     * Reseller Portal Device Management Dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isReseller()) {
            abort(403, __('You do not have access to the software reseller portal.'));
        }

        $filters = [
            'search' => trim((string) $request->query('search')),
            'software_id' => $request->query('software_id'),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        $allocatedSoftwares = $this->resellerDeviceService->getAllocatedSoftwares($user);
        $stats = $this->resellerDeviceService->getResellerStats($user);
        $devices = $this->resellerDeviceService->getResellerDevices($user, $filters);

        // Get unassigned device check-ins for the reseller's allocated softwares
        $softwareIds = $allocatedSoftwares->pluck('serial_software_id')->toArray();
        $assignedDeviceIds = SerialUserDevice::whereNotNull('user_id')->pluck('device_id')->toArray();

        $availableDevices = SerialDevice::whereIn('serial_software_id', $softwareIds)
            ->whereNotIn('device_id', $assignedDeviceIds)
            ->with('software:id,name')
            ->orderByDesc('last_check_date')
            ->limit(50)
            ->get(['id', 'serial_software_id', 'device_id', 'machine_name', 'user_name', 'os_version', 'last_check_date']);

        return Inertia::render('Portal/Devices/Index', [
            'allocatedSoftwares' => $allocatedSoftwares,
            'stats' => $stats,
            'devices' => $devices,
            'availableDevices' => $availableDevices,
            'filters' => $filters,
            'canViewAllDevices' => $user->canViewAllDevices(),
        ]);
    }

    /**
     * Helper to verify if user has permission to modify this device assignment.
     */
    protected function authorizeDeviceModification(Request $request, SerialUserDevice $serialUserDevice): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $serialUserDevice->reseller_id === $user->id) {
            return;
        }

        $canViewSoftwareIds = \App\Models\SerialSoftwareReseller::where('user_id', $user->id)
            ->where('status', \App\Models\SerialSoftwareReseller::STATUS_ACTIVE)
            ->where(function ($q) use ($user) {
                $q->where('can_view_all_devices', true);
                if ($user->can_view_all_devices) {
                    $q->orWhereRaw('1 = 1');
                }
            })
            ->pluck('serial_software_id')
            ->toArray();

        $deviceSoftwareIds = $serialUserDevice->devices()->pluck('serial_software_id')->toArray();
        if (! empty(array_intersect($canViewSoftwareIds, $deviceSoftwareIds))) {
            return;
        }

        abort(403, __('Unauthorized to modify this device.'));
    }

    /**
     * Assign a device to a customer.
     */
    public function store(AssignDeviceRequest $request): RedirectResponse
    {
        $this->resellerDeviceService->assignDeviceToCustomer($request->user(), $request->validated());

        return back()->with('success', __('Device assigned to customer successfully.'));
    }

    /**
     * Extend / renew a device license (e.g. +1 Month).
     */
    public function renew(RenewDeviceRequest $request, SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $this->authorizeDeviceModification($request, $serialUserDevice);

        $this->resellerDeviceService->renewDevice(
            $serialUserDevice,
            $request->validated('duration_preset'),
            $request->validated('custom_expires_at')
        );

        return back()->with('success', __('Device license renewed successfully.'));
    }

    /**
     * Update device active / inactive status.
     */
    public function updateStatus(Request $request, SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $this->authorizeDeviceModification($request, $serialUserDevice);

        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $this->resellerDeviceService->updateStatus($serialUserDevice, $request->input('status'));

        return back()->with('success', __('Device status updated successfully.'));
    }

    /**
     * Reseller Device Details full page.
     */
    public function show(Request $request, SerialUserDevice $serialUserDevice): Response
    {
        $this->authorizeDeviceModification($request, $serialUserDevice);

        $serialUserDevice->load([
            'user',
            'reseller',
            'devices.software',
        ]);

        $firstDevice = $serialUserDevice->devices->first();

        $expiryInfo = [
            'expires_at' => $serialUserDevice->expires_at?->toDateString(),
            'expires_at_formatted' => $serialUserDevice->expires_at?->format('Y-m-d H:i'),
            'is_expired' => $serialUserDevice->isExpired(),
            'is_expiring_soon' => $serialUserDevice->expires_at && ! $serialUserDevice->isExpired() && now()->diffInDays($serialUserDevice->expires_at, false) <= 7,
            'remaining_days' => $serialUserDevice->expires_at ? max(0, (int) ceil(now()->diffInDays($serialUserDevice->expires_at, false))) : null,
            'is_lifetime' => $serialUserDevice->expires_at === null,
        ];

        return Inertia::render('Portal/Devices/Show', [
            'device' => [
                'id' => $serialUserDevice->id,
                'device_id' => $serialUserDevice->device_id,
                'status' => $serialUserDevice->status,
                'expires_at' => $serialUserDevice->expires_at?->toIso8601String(),
                'notes' => $serialUserDevice->notes,
                'created_at' => $serialUserDevice->created_at?->toDateString(),
                'updated_at' => $serialUserDevice->updated_at?->toDateTimeString(),
                'user' => $serialUserDevice->user ? [
                    'id' => $serialUserDevice->user->id,
                    'name' => $serialUserDevice->user->name,
                    'email' => $serialUserDevice->user->email,
                    'phone' => $serialUserDevice->user->phone ?? null,
                    'created_at' => $serialUserDevice->user->created_at?->toDateString(),
                ] : null,
                'reseller' => $serialUserDevice->reseller ? [
                    'id' => $serialUserDevice->reseller->id,
                    'name' => $serialUserDevice->reseller->name,
                    'email' => $serialUserDevice->reseller->email,
                ] : null,
                'telemetry' => $firstDevice ? [
                    'id' => $firstDevice->id,
                    'machine_name' => $firstDevice->machine_name,
                    'user_name' => $firstDevice->user_name,
                    'user_domain' => $firstDevice->user_domain,
                    'os_version' => $firstDevice->os_version,
                    'framework_version' => $firstDevice->framework_version,
                    'is_64bit_os' => $firstDevice->is_64bit_os,
                    'is_64bit_process' => $firstDevice->is_64bit_process,
                    'current_directory' => $firstDevice->current_directory,
                    'current_culture' => $firstDevice->current_culture,
                    'last_check_date' => $firstDevice->last_check_date?->diffForHumans(),
                    'last_check_date_full' => $firstDevice->last_check_date?->toDateTimeString(),
                    'software' => $firstDevice->software ? [
                        'id' => $firstDevice->software->id,
                        'name' => $firstDevice->software->name,
                    ] : null,
                ] : null,
            ],
            'expiryInfo' => $expiryInfo,
        ]);
    }

    /**
     * Update reseller notes on a device.
     */
    public function updateNotes(Request $request, SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $this->authorizeDeviceModification($request, $serialUserDevice);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $serialUserDevice->update([
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', __('Notes updated successfully.'));
    }

    /**
     * Unassign a device.
     */
    public function destroy(Request $request, SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $this->authorizeDeviceModification($request, $serialUserDevice);

        $this->resellerDeviceService->unassignDevice($serialUserDevice);

        return back()->with('success', __('Device unassigned successfully.'));
    }
}
