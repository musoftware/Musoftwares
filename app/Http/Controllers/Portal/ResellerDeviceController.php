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
        $assignedDeviceIds = SerialUserDevice::pluck('device_id')->toArray();

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
        ]);
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
        $user = $request->user();

        if ($serialUserDevice->reseller_id !== $user->id && ! $user->isAdmin()) {
            abort(403, __('Unauthorized to modify this device.'));
        }

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
        $user = $request->user();

        if ($serialUserDevice->reseller_id !== $user->id && ! $user->isAdmin()) {
            abort(403, __('Unauthorized to modify this device.'));
        }

        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $this->resellerDeviceService->updateStatus($serialUserDevice, $request->input('status'));

        return back()->with('success', __('Device status updated successfully.'));
    }

    /**
     * Unassign a device.
     */
    public function destroy(Request $request, SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $user = $request->user();

        if ($serialUserDevice->reseller_id !== $user->id && ! $user->isAdmin()) {
            abort(403, __('Unauthorized to unassign this device.'));
        }

        $this->resellerDeviceService->unassignDevice($serialUserDevice);

        return back()->with('success', __('Device unassigned successfully.'));
    }
}
