<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Services\SerialDeviceService;
use App\Http\Requests\Admin\SerialDevice\UpdateSerialDeviceStatusRequest;
use App\Http\Resources\SerialDeviceResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin: manage serial devices (all registered machines).
 * Copied from old project: Admin/SerialDeviceController.
 *
 * Devices are auto-created by the API check-in endpoint.
 * Admin can: search, filter by status/software, change status, delete.
 */
class SerialDeviceController extends Controller
{
    public function __construct(
        protected SerialDeviceService $serialDeviceService
    ) {}

    public function index(Request $request): Response
    {
        $validPerPageOptions = [10, 20, 50, 100];

        $filters = [
            'search'      => trim((string) $request->query('search')),
            'user'        => trim((string) $request->query('user')),
            'status'      => $request->query('status'),
            'software_id' => $request->query('software_id'),
            'sort'        => $request->query('sort', 'recent'),
            'per_page'    => (int) $request->query('per_page', 20),
        ];

        // Sanitize status
        $filters['status'] = in_array($filters['status'], SerialDevice::statuses(), true)
            ? $filters['status']
            : null;

        // Sanitize per_page
        if (!in_array($filters['per_page'], $validPerPageOptions, true)) {
            $filters['per_page'] = 20;
        }

        // Build query
        $query = SerialDevice::query()->with(['software', 'userDeviceAssignment.user']);

        // Filters
        $query->when($filters['search'], function ($q, string $search) {
            $like = "%{$search}%";
            $q->where(function ($sub) use ($like) {
                $sub->where('device_id', 'like', $like)
                    ->orWhere('machine_name', 'like', $like)
                    ->orWhere('user_domain', 'like', $like);
            });
        });

        $query->when($filters['user'], function ($q, string $user) {
            $like = "%{$user}%";
            $q->where(function ($sub) use ($like) {
                $sub->where('user_name', 'like', $like)
                    ->orWhere('user_domain', 'like', $like);
            });
        });

        $query->when($filters['status'], fn($q, $s) => $q->where('status', $s));
        $query->when($filters['software_id'], fn($q, $id) => $q->where('serial_software_id', (int) $id));

        // Sorting
        $sortBy    = $request->query('sort_by');
        $direction = in_array($request->query('direction', 'desc'), ['asc', 'desc']) ? $request->query('direction', 'desc') : 'desc';

        $validSortColumns = ['device_id', 'serial_software_id', 'user_name', 'machine_name', 'status', 'last_check_date', 'created_at', 'updated_at'];
        if ($sortBy && in_array($sortBy, $validSortColumns, true)) {
            $filters['sort_by'] = $sortBy;
            $filters['direction'] = $direction;
        } else {
            $sortPreset = $request->query('sort', 'recent');
            [$filters['sort_by'], $filters['direction']] = match ($sortPreset) {
                'oldest' => ['created_at', 'asc'],
                'alpha'  => ['device_id', 'asc'],
                default  => ['created_at', 'desc'],
            };
            $filters['sort'] = $sortPreset === 'oldest' ? 'oldest' : ($sortPreset === 'alpha' ? 'alpha' : 'recent');
        }

        $query->orderBy($filters['sort_by'], $filters['direction']);

        $devices = $query->paginate($filters['per_page'])->withQueryString()->through(fn($d) => (new SerialDeviceResource($d))->resolve());

        $softwares = SerialSoftware::orderBy('name')->get(['id', 'name']);

        $stats = [
            'total'    => SerialDevice::count(),
            'active'   => SerialDevice::where('status', SerialDevice::STATUS_ACTIVE)->count(),
            'inactive' => SerialDevice::where('status', SerialDevice::STATUS_INACTIVE)->count(),
        ];

        return Inertia::render('Admin/SerialDevices/Index', [
            'devices'        => $devices,
            'filters'        => $filters,
            'statuses'       => SerialDevice::statuses(),
            'softwares'      => $softwares,
            'stats'          => $stats,
            'perPageOptions' => $validPerPageOptions,
        ]);
    }

    public function updateStatus(UpdateSerialDeviceStatusRequest $request, SerialDevice $serialDevice): RedirectResponse
    {
        $this->serialDeviceService->updateStatus($serialDevice, $request->validated('status'));

        return back()->with('success', 'Device status updated.');
    }

    public function destroy(SerialDevice $serialDevice): RedirectResponse
    {
        $this->serialDeviceService->deleteDevice($serialDevice);

        return back()->with('success', 'Device deleted.');
    }
}
