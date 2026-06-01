<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialDevice;
use App\Models\SerialUserDevice;
use App\Models\User;
use App\Services\SerialUserDeviceService;
use App\Http\Requests\Admin\SerialUserDevice\StoreSerialUserDeviceRequest;
use App\Http\Requests\Admin\SerialUserDevice\UpdateSerialUserDeviceStatusRequest;
use App\Http\Requests\Admin\SerialUserDevice\UpdateUserSerialStatusRequest;
use App\Http\Requests\Admin\SerialUserDevice\UpdateUserTempValidRequest;
use App\Http\Resources\SerialUserDeviceResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin: manage user-device assignments.
 * Copied from old project: Admin/SerialUserDeviceController.
 *
 * This is the main operational panel for license assignment.
 * Admin maps device_id → user. Status change triggers Observer → SerialDevice sync.
 * Includes bulk update by user (all devices of a user in one action).
 */
class SerialUserDeviceController extends Controller
{
    public function __construct(
        protected SerialUserDeviceService $serialUserDeviceService
    ) {}

    /**
     * All assignments with search/filter/sort.
     */
    public function index(Request $request): Response
    {
        $validPerPageOptions = [10, 20, 50, 100];

        $filters = [
            'search'   => trim((string) $request->query('search')),
            'user_id'  => $request->query('user_id'),
            'status'   => $request->query('status'),
            'sort'     => $request->query('sort', 'recent'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        $filters['status'] = in_array($filters['status'], SerialUserDevice::statuses(), true)
            ? $filters['status']
            : null;

        if (!in_array($filters['per_page'], $validPerPageOptions, true)) {
            $filters['per_page'] = 20;
        }

        $query = SerialUserDevice::query()->with('user');

        $query->when($filters['search'], function ($q, string $search) {
            $like = "%{$search}%";
            $q->where(function ($sub) use ($like) {
                $sub->where('device_id', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        });

        $query->when($filters['user_id'], fn($q, $id) => $q->where('user_id', (int) $id));
        $query->when($filters['status'], fn($q, $s) => $q->where('status', $s));

        $query->when(true, function ($q) use ($filters) {
            match ($filters['sort']) {
                'oldest' => $q->orderBy('created_at'),
                'alpha'  => $q->orderBy('device_id'),
                default  => $q->orderByDesc('created_at'),
            };
        });

        $userDevices = $query->paginate($filters['per_page'])->withQueryString()->through(fn($d) => (new SerialUserDeviceResource($d))->resolve());

        $stats = [
            'total'    => SerialUserDevice::count(),
            'active'   => SerialUserDevice::where('status', SerialUserDevice::STATUS_ACTIVE)->count(),
            'inactive' => SerialUserDevice::where('status', SerialUserDevice::STATUS_INACTIVE)->count(),
        ];

        return Inertia::render('Admin/SerialUserDevices/Index', [
            'userDevices'    => $userDevices,
            'filters'        => $filters,
            'statuses'       => SerialUserDevice::statuses(),
            'stats'          => $stats,
            'perPageOptions' => $validPerPageOptions,
        ]);
    }

    /**
     * Assignment form — shows available (unassigned) devices and all users.
     */
    public function assign(): Response
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Only show devices not already assigned to someone
        $assignedDeviceIds  = SerialUserDevice::pluck('device_id')->toArray();
        $availableDevices   = SerialDevice::whereNotIn('device_id', $assignedDeviceIds)
            ->select('device_id', 'machine_name', 'user_name', 'serial_software_id')
            ->with('software:id,name')
            ->distinct()
            ->orderBy('device_id')
            ->get();

        return Inertia::render('Admin/SerialUserDevices/Assign', [
            'users'            => $users,
            'availableDevices' => $availableDevices,
        ]);
    }

    /**
     * Assign a device to a user.
     */
    public function store(StoreSerialUserDeviceRequest $request): RedirectResponse
    {
        $this->serialUserDeviceService->assignDevice($request->validated());

        return redirect()
            ->route('admin.serial-user-devices.index')
            ->with('success', __('general.device_assigned_successfully'));
    }

    /**
     * Update status of a single assignment.
     * Observer triggers sync to SerialDevice.
     */
    public function updateStatus(UpdateSerialUserDeviceStatusRequest $request, SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $this->serialUserDeviceService->updateStatus($serialUserDevice, $request->validated('status'));

        return back()->with('success', __('general.assignment_status_updated'));
    }

    /**
     * Unassign a device from a user.
     */
    public function destroy(SerialUserDevice $serialUserDevice): RedirectResponse
    {
        $this->serialUserDeviceService->unassignDevice($serialUserDevice);

        return back()->with('success', __('general.assignment_removed'));
    }

    /**
     * View assignments grouped by user — for bulk management.
     * Copied from old project: SerialUserDeviceController::byUser()
     */
    public function byUser(Request $request): Response
    {
        $filters = [
            'search'   => trim((string) $request->query('search')),
            'status'   => $request->query('status'),
            'sort'     => $request->query('sort', 'recent'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        $query = User::query()
            ->whereHas('serialUserDevices')
            ->withCount(['serialUserDevices as total_devices'])
            ->withCount(['serialUserDevices as active_devices' => fn($q) => $q->where('status', SerialUserDevice::STATUS_ACTIVE)])
            ->withCount(['serialUserDevices as inactive_devices' => fn($q) => $q->where('status', SerialUserDevice::STATUS_INACTIVE)]);

        $query->when($filters['search'], function ($q, string $search) {
            $like = "%{$search}%";
            $q->where(fn($sub) => $sub->where('name', 'like', $like)->orWhere('email', 'like', $like));
        });

        $filters['sort'] === 'recent'
            ? $query->orderByDesc('active_devices')
            : $query->orderBy('name');

        $users = $query->paginate($filters['per_page'])->withQueryString();

        return Inertia::render('Admin/SerialUserDevices/ByUser', compact('users', 'filters'));
    }

    /**
     * Update ALL device assignments for a user to a given status.
     * Uses get()->each() to trigger Observer per record (not bulk ->update()).
     * Copied from old project: SerialUserDeviceController::updateUserStatus()
     */
    public function updateUserStatus(UpdateUserSerialStatusRequest $request, User $user): RedirectResponse
    {
        $this->serialUserDeviceService->updateUserStatus($user, $request->validated('status'));

        return back()->with('success', "All devices for {$user->name} set to {$validated['status']}.");
    }

    /**
     * Update temp_valid_until on a user.
     * Allows temporary license override without touching device status.
     * Copied from old project: SerialUserDeviceController::updateUserTempValid()
     */
    public function updateUserTempValid(UpdateUserTempValidRequest $request, User $user): RedirectResponse
    {
        $this->serialUserDeviceService->updateUserTempValid($user, $request->validated('temp_valid_until'));

        return back()->with('success', "Temporary validity updated for {$user->name}.");
    }
}
