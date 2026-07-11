<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SerialSoftware\StoreSerialSoftwareRequest;
use App\Http\Requests\Admin\SerialSoftware\UpdateSerialSoftwareStatusRequest;
use App\Http\Resources\SerialSoftwareResource;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Services\SerialSoftwareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin: manage serial softwares (program registry).
 *
 * Softwares are auto-created by the API check-in endpoint.
 * Admin can: view license counts, change default_status, delete, export CSV.
 */
class SerialSoftwareController extends Controller
{
    public function __construct(
        protected SerialSoftwareService $serialSoftwareService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'default_status' => $request->query('default_status'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        // Clamp per_page to allowed values
        if (! in_array($filters['per_page'], [10, 20, 50, 100])) {
            $filters['per_page'] = 20;
        }

        $query = SerialSoftware::query()
            ->withCount(['devices as total_devices'])
            ->withCount(['devices as active_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['devices as inactive_count' => fn ($q) => $q->where('status', 'inactive')])
            ->withCount(['devices as blocked_count' => fn ($q) => $q->where('status', 'blocked')]);

        // Search filter
        if ($filters['search'] !== '') {
            $query->where('name', 'LIKE', '%'.$filters['search'].'%');
        }

        // Status filter
        if ($filters['default_status'] && in_array($filters['default_status'], ['active', 'inactive'])) {
            $query->where('default_status', $filters['default_status']);
        }

        // Sorting
        $validSortColumns = ['name', 'default_status', 'total_devices', 'active_count', 'created_at'];
        $sortBy = in_array($filters['sort_by'], $validSortColumns) ? $filters['sort_by'] : 'created_at';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $direction);

        $softwares = $query
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn ($sw) => (new SerialSoftwareResource($sw))->resolve());

        // Aggregate stats across all softwares (unfiltered)
        $stats = [
            'total_softwares' => SerialSoftware::count(),
            'total_devices_all' => SerialDevice::count(),
            'active_devices_all' => SerialDevice::where('status', 'active')->count(),
            'inactive_devices_all' => SerialDevice::where('status', 'inactive')->count(),
            'blocked_devices_all' => SerialDevice::where('status', 'blocked')->count(),
        ];

        return Inertia::render('Admin/SerialSoftwares/Index', [
            'softwares' => $softwares,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Export softwares as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $softwares = SerialSoftware::query()
            ->withCount(['devices as total_devices'])
            ->withCount(['devices as active_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['devices as inactive_count' => fn ($q) => $q->where('status', 'inactive')])
            ->withCount(['devices as blocked_count' => fn ($q) => $q->where('status', 'blocked')])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'serial-softwares-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($softwares) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Software Name',
                'Default Status',
                'Total Devices',
                'Active',
                'Inactive',
                'Blocked',
                'Registered',
            ]);

            foreach ($softwares as $sw) {
                fputcsv($handle, [
                    $sw->name,
                    $sw->default_status,
                    $sw->total_devices ?? 0,
                    $sw->active_count ?? 0,
                    $sw->inactive_count ?? 0,
                    $sw->blocked_count ?? 0,
                    $sw->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Update the default_status of a software.
     * This affects NEW devices on their first check-in only.
     * Existing device statuses are NOT changed.
     */
    public function updateStatus(UpdateSerialSoftwareStatusRequest $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $this->serialSoftwareService->updateStatus($serialSoftware, $request->validated('status'));

        return back()->with('success', __('general.software_default_status_updated'));
    }

    /**
     * Create a software manually (usually auto-created by API).
     */
    public function store(StoreSerialSoftwareRequest $request): RedirectResponse
    {
        $this->serialSoftwareService->createSoftware($request->validated());

        return back()->with('success', __('general.software_added_successfully'));
    }

    public function destroy(SerialSoftware $serialSoftware): RedirectResponse
    {
        $this->serialSoftwareService->deleteSoftware($serialSoftware);

        return back()->with('success', __('general.software_deleted_successfully'));
    }
}
