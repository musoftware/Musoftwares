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
            ->with('customKeys')
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

    public function storeKey(Request $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'default_value' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        \App\Models\SerialSoftwareKey::updateOrCreate(
            [
                'serial_software_id' => $serialSoftware->id,
                'key' => $validated['key'],
            ],
            [
                'default_value' => $validated['default_value'],
                'description' => $validated['description'] ?? null,
            ]
        );

        return back()->with('success', 'Custom key saved successfully.');
    }

    public function destroyKey(SerialSoftware $serialSoftware, \App\Models\SerialSoftwareKey $serialSoftwareKey): RedirectResponse
    {
        if ($serialSoftwareKey->serial_software_id === $serialSoftware->id) {
            $serialSoftwareKey->delete();
        }

        return back()->with('success', 'Custom key deleted successfully.');
    }

    /**
     * Update payment and activation settings for a software.
     */
    public function updatePaymentSettings(Request $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $validated = $request->validate([
            'requires_payment' => ['required', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->serialSoftwareService->updateFullSettings($serialSoftware, $validated);

        return back()->with('success', 'Payment settings updated successfully.');
    }

    /**
     * Dedicated full-page settings management.
     */
    public function settings(SerialSoftware $serialSoftware): Response
    {
        $serialSoftware->load([
            'customKeys',
            'packages' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);

        $serialSoftware->loadCount([
            'devices as total_devices',
            'devices as active_count' => fn ($q) => $q->where('status', 'active'),
            'devices as inactive_count' => fn ($q) => $q->where('status', 'inactive'),
            'devices as blocked_count' => fn ($q) => $q->where('status', 'blocked'),
        ]);

        $commonCurrencies = ['USD', 'EUR', 'GBP', 'EGP', 'SAR', 'AED', 'KWD', 'QAR', 'OMR', 'BHD'];

        return Inertia::render('Admin/SerialSoftwares/Settings', [
            'software' => [
                'id' => $serialSoftware->id,
                'name' => $serialSoftware->name,
                'is_active' => (bool) ($serialSoftware->is_active ?? true),
                'default_status' => $serialSoftware->default_status,
                'pricing_type' => $serialSoftware->pricing_type ?? ($serialSoftware->requires_payment ? 'single' : 'free'),
                'requires_payment' => (bool) $serialSoftware->requires_payment,
                'price' => $serialSoftware->price !== null ? (float) $serialSoftware->price : null,
                'currency' => $serialSoftware->currency ?? 'USD',
                'billing_cycle' => $serialSoftware->billing_cycle ?? 'lifetime',
                'billing_days' => $serialSoftware->billing_days,
                'whatsapp_number' => $serialSoftware->whatsapp_number ?? '',
                'payment_instructions' => $serialSoftware->payment_instructions ?? '',
                'total_devices' => $serialSoftware->total_devices ?? 0,
                'active_count' => $serialSoftware->active_count ?? 0,
                'inactive_count' => $serialSoftware->inactive_count ?? 0,
                'blocked_count' => $serialSoftware->blocked_count ?? 0,
                'created_at' => $serialSoftware->created_at?->diffForHumans(),
                'custom_keys' => $serialSoftware->customKeys,
                'packages' => $serialSoftware->packages,
            ],
            'commonCurrencies' => $commonCurrencies,
        ]);
    }

    /**
     * Save full software settings.
     */
    public function updateSettings(Request $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'default_status' => ['required', 'in:active,inactive'],
            'pricing_type' => ['required', 'in:free,single,packages'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_cycle' => ['nullable', 'in:lifetime,monthly,annual,custom'],
            'billing_days' => ['nullable', 'integer', 'min:1'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'payment_instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->serialSoftwareService->updateFullSettings($serialSoftware, $validated);

        return back()->with('success', 'Software settings saved successfully.');
    }

    /**
     * Create a new package for this software.
     */
    public function storePackage(Request $request, SerialSoftware $serialSoftware): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_cycle' => ['required', 'in:lifetime,monthly,annual,custom'],
            'billing_days' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['integer'],
            'custom_values' => ['nullable', 'array'],
        ]);

        $this->serialSoftwareService->createPackage($serialSoftware, $validated);

        return back()->with('success', 'Package created successfully.');
    }

    /**
     * Update an existing package.
     */
    public function updatePackage(Request $request, SerialSoftware $serialSoftware, \App\Models\SerialSoftwarePackage $package): RedirectResponse
    {
        if ($package->serial_software_id !== $serialSoftware->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_cycle' => ['required', 'in:lifetime,monthly,annual,custom'],
            'billing_days' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['integer'],
            'custom_values' => ['nullable', 'array'],
        ]);

        $this->serialSoftwareService->updatePackage($package, $validated);

        return back()->with('success', 'Package updated successfully.');
    }

    /**
     * Delete a package.
     */
    public function destroyPackage(SerialSoftware $serialSoftware, \App\Models\SerialSoftwarePackage $package): RedirectResponse
    {
        if ($package->serial_software_id !== $serialSoftware->id) {
            abort(404);
        }

        $this->serialSoftwareService->deletePackage($package);

        return back()->with('success', 'Package deleted successfully.');
    }
}
