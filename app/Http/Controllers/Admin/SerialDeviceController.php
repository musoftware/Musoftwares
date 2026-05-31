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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin: manage serial devices (all registered machines).
 * Copied from old project: Admin/SerialDeviceController.
 *
 * Devices are auto-created by the API check-in endpoint.
 * Admin can: search, filter by status/software, change status, delete, bulk ops, export.
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
            'search'          => trim((string) $request->query('search')),
            'user'            => trim((string) $request->query('user')),
            'status'          => $request->query('status'),
            'software_id'     => $request->query('software_id'),
            'date_from'       => $request->query('date_from'),
            'date_to'         => $request->query('date_to'),
            'last_check_from' => $request->query('last_check_from'),
            'last_check_to'   => $request->query('last_check_to'),
            'os_version'      => $request->query('os_version'),
            'is_64bit'        => $request->query('is_64bit'),
            'has_user'        => $request->query('has_user'),
            'sort'            => $request->query('sort', 'recent'),
            'per_page'        => (int) $request->query('per_page', 20),
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
        $query = $this->buildFilteredQuery($filters);

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
            'total'              => SerialDevice::count(),
            'active'             => SerialDevice::where('status', SerialDevice::STATUS_ACTIVE)->count(),
            'inactive'           => SerialDevice::where('status', SerialDevice::STATUS_INACTIVE)->count(),
            'blocked'            => SerialDevice::where('status', SerialDevice::STATUS_BLOCKED)->count(),
            'checked_in_today'   => SerialDevice::whereDate('last_check_date', today())->count(),
            'new_this_week'      => SerialDevice::where('created_at', '>=', now()->startOfWeek())->count(),
            'new_this_month'     => SerialDevice::where('created_at', '>=', now()->startOfMonth())->count(),
            'never_checked_in'   => SerialDevice::whereNull('last_check_date')->count(),
            'devices_per_software' => SerialSoftware::withCount('devices')->orderByDesc('devices_count')->get(['id', 'name', 'devices_count']),
        ];

        $osVersions = SerialDevice::whereNotNull('os_version')->distinct()->pluck('os_version');

        return Inertia::render('Admin/SerialDevices/Index', [
            'devices'        => $devices,
            'filters'        => $filters,
            'statuses'       => SerialDevice::statuses(),
            'softwares'      => $softwares,
            'stats'          => $stats,
            'perPageOptions' => $validPerPageOptions,
            'osVersions'     => $osVersions,
        ]);
    }

    public function updateStatus(UpdateSerialDeviceStatusRequest $request, SerialDevice $serialDevice): RedirectResponse
    {
        $this->serialDeviceService->updateStatus($serialDevice, $request->validated('status'));

        return back()->with('success', __('Device status updated.'));
    }

    public function destroy(SerialDevice $serialDevice): RedirectResponse
    {
        $this->serialDeviceService->deleteDevice($serialDevice);

        return back()->with('success', __('Device deleted.'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'search'          => trim((string) $request->query('search')),
            'user'            => trim((string) $request->query('user')),
            'status'          => in_array($request->query('status'), SerialDevice::statuses(), true) ? $request->query('status') : null,
            'software_id'     => $request->query('software_id'),
            'date_from'       => $request->query('date_from'),
            'date_to'         => $request->query('date_to'),
            'last_check_from' => $request->query('last_check_from'),
            'last_check_to'   => $request->query('last_check_to'),
            'os_version'      => $request->query('os_version'),
            'is_64bit'        => $request->query('is_64bit'),
            'has_user'        => $request->query('has_user'),
        ];

        $query = $this->buildFilteredQuery($filters);
        $query->orderBy('created_at', 'desc');

        $filename = 'serial-devices-' . now()->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Device ID',
                'Machine Name',
                'User Name',
                'User Domain',
                'Software',
                'Status',
                'OS Version',
                '64-bit',
                'Last Check',
                'Registered',
                'Linked User',
            ]);

            $query->chunk(500, function ($devices) use ($handle) {
                foreach ($devices as $device) {
                    fputcsv($handle, [
                        $device->device_id,
                        $device->machine_name,
                        $device->user_name,
                        $device->user_domain,
                        $device->software?->name ?? '',
                        $device->status,
                        $device->os_version ?? '',
                        $device->is_64bit_os === null ? '' : ($device->is_64bit_os ? 'Yes' : 'No'),
                        $device->last_check_date?->toDateTimeString() ?? '',
                        $device->created_at?->toDateString() ?? '',
                        $device->userDeviceAssignment?->user?->email ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['required', 'integer', 'exists:serial_devices,id'],
            'status' => ['required', 'string', Rule::in(SerialDevice::statuses())],
        ]);

        SerialDevice::whereIn('id', $validated['ids'])->update(['status' => $validated['status']]);

        $count = count($validated['ids']);

        return back()->with('success', __(':count device(s) status updated.', ['count' => $count]));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:serial_devices,id'],
        ]);

        $count = SerialDevice::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', __(':count device(s) deleted.', ['count' => $count]));
    }

    /**
     * Build a filtered query from filter params (shared between index & export).
     */
    private function buildFilteredQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = SerialDevice::query()->with(['software', 'userDeviceAssignment.user']);

        // Text search
        $query->when($filters['search'] ?? null, function ($q, string $search) {
            $like = "%{$search}%";
            $q->where(function ($sub) use ($like) {
                $sub->where('device_id', 'like', $like)
                    ->orWhere('machine_name', 'like', $like)
                    ->orWhere('user_domain', 'like', $like);
            });
        });

        // User search
        $query->when($filters['user'] ?? null, function ($q, string $user) {
            $like = "%{$user}%";
            $q->where(function ($sub) use ($like) {
                $sub->where('user_name', 'like', $like)
                    ->orWhere('user_domain', 'like', $like);
            });
        });

        // Status
        $query->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s));

        // Software
        $query->when($filters['software_id'] ?? null, fn($q, $id) => $q->where('serial_software_id', (int) $id));

        // Date range (created_at)
        $query->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d));
        $query->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d));

        // Last check date range
        $query->when($filters['last_check_from'] ?? null, fn($q, $d) => $q->whereDate('last_check_date', '>=', $d));
        $query->when($filters['last_check_to'] ?? null, fn($q, $d) => $q->whereDate('last_check_date', '<=', $d));

        // OS Version
        $query->when($filters['os_version'] ?? null, fn($q, $os) => $q->where('os_version', 'like', "%{$os}%"));

        // 64-bit filter
        if (isset($filters['is_64bit']) && $filters['is_64bit'] !== '' && $filters['is_64bit'] !== null) {
            $query->where('is_64bit_os', (int) $filters['is_64bit']);
        }

        // Has linked user
        if (isset($filters['has_user']) && $filters['has_user'] !== '' && $filters['has_user'] !== null) {
            if ($filters['has_user'] === 'yes') {
                $query->whereHas('userDeviceAssignment');
            } else {
                $query->whereDoesntHave('userDeviceAssignment');
            }
        }

        return $query;
    }
}
