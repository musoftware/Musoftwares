<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SerialDevice\UpdateSerialDeviceStatusRequest;
use App\Http\Resources\SerialDeviceResource;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\User;
use App\Services\SerialDeviceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use App\Models\SerialSoftwarePackage;
use App\Models\SerialUserDevice;
use App\Models\SerialSoftwareLicense;
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
            'search' => trim((string) $request->query('search')),
            'user' => trim((string) $request->query('user')),
            'status' => $request->query('status'),
            'software_id' => $request->query('software_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'last_check_from' => $request->query('last_check_from'),
            'last_check_to' => $request->query('last_check_to'),
            'os_version' => $request->query('os_version'),
            'is_64bit' => $request->query('is_64bit'),
            'has_user' => $request->query('has_user'),
            'sort' => $request->query('sort', 'recent'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        // Sanitize status
        $filters['status'] = in_array($filters['status'], SerialDevice::statuses(), true)
            ? $filters['status']
            : null;

        // Sanitize per_page
        if (! in_array($filters['per_page'], $validPerPageOptions, true)) {
            $filters['per_page'] = 20;
        }

        // Build query
        $query = $this->buildFilteredQuery($filters);

        // Sorting
        $sortBy = $request->query('sort_by');
        $direction = in_array($request->query('direction', 'desc'), ['asc', 'desc']) ? $request->query('direction', 'desc') : 'desc';

        $validSortColumns = ['device_id', 'serial_software_id', 'user_name', 'machine_name', 'status', 'last_check_date', 'created_at', 'updated_at'];
        if ($sortBy && in_array($sortBy, $validSortColumns, true)) {
            $filters['sort_by'] = $sortBy;
            $filters['direction'] = $direction;
        } else {
            $sortPreset = $request->query('sort', 'recent');
            [$filters['sort_by'], $filters['direction']] = match ($sortPreset) {
                'oldest' => ['created_at', 'asc'],
                'alpha' => ['device_id', 'asc'],
                default => ['created_at', 'desc'],
            };
            $filters['sort'] = $sortPreset === 'oldest' ? 'oldest' : ($sortPreset === 'alpha' ? 'alpha' : 'recent');
        }

        $query->orderBy($filters['sort_by'], $filters['direction']);

        $devices = $query->paginate($filters['per_page'])->withQueryString()->through(fn ($d) => (new SerialDeviceResource($d))->resolve());

        $softwares = SerialSoftware::with('customKeys')->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => SerialDevice::count(),
            'active' => SerialDevice::where('status', SerialDevice::STATUS_ACTIVE)->count(),
            'inactive' => SerialDevice::where('status', SerialDevice::STATUS_INACTIVE)->count(),
            'blocked' => SerialDevice::where('status', SerialDevice::STATUS_BLOCKED)->count(),
            'checked_in_today' => SerialDevice::whereDate('last_check_date', today())->count(),
            'new_this_week' => SerialDevice::where('created_at', '>=', now()->startOfWeek())->count(),
            'new_this_month' => SerialDevice::where('created_at', '>=', now()->startOfMonth())->count(),
            'never_checked_in' => SerialDevice::whereNull('last_check_date')->count(),
            'devices_per_software' => SerialSoftware::withCount('devices')->orderByDesc('devices_count')->get(['id', 'name', 'devices_count']),
        ];

        $osVersions = SerialDevice::whereNotNull('os_version')->distinct()->pluck('os_version');

        return Inertia::render('Admin/SerialDevices/Index', [
            'devices' => $devices,
            'filters' => $filters,
            'statuses' => SerialDevice::statuses(),
            'softwares' => $softwares,
            'stats' => $stats,
            'perPageOptions' => $validPerPageOptions,
            'osVersions' => $osVersions,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function show(SerialDevice $serialDevice): Response
    {
        $serialDevice->load([
            'software.customKeys',
            'deviceKeys.softwareKey',
            'userDeviceAssignment.user',
            'userDeviceAssignment.reseller',
        ]);

        $resolvedKeys = $serialDevice->getResolvedCustomKeys();

        // Build list of software keys with default and device override info
        $customKeysData = [];
        if ($serialDevice->software && $serialDevice->software->customKeys) {
            $deviceKeyMap = $serialDevice->deviceKeys->keyBy('serial_software_key_id');
            foreach ($serialDevice->software->customKeys as $key) {
                $override = $deviceKeyMap->get($key->id);
                $customKeysData[] = [
                    'id' => $key->id,
                    'key' => $key->key,
                    'label' => $key->label ?? $key->key,
                    'description' => $key->description,
                    'default_value' => $key->default_value,
                    'override_id' => $override?->id,
                    'override_value' => $override?->value,
                    'effective_value' => $resolvedKeys[$key->key] ?? ($override?->value ?? $key->default_value),
                    'is_overridden' => $override !== null,
                ];
            }
        }

        $assignment = $serialDevice->userDeviceAssignment;
        $assignmentData = null;
        if ($assignment) {
            $assignmentData = [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'expires_at' => $assignment->expires_at?->toDateString(),
                'expires_at_formatted' => $assignment->expires_at?->format('Y-m-d H:i'),
                'is_expired' => $assignment->isExpired(),
                'remaining_days' => $assignment->expires_at ? max(0, (int) ceil(now()->diffInDays($assignment->expires_at, false))) : null,
                'notes' => $assignment->notes,
                'user' => $assignment->user ? [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'email' => $assignment->user->email,
                    'phone' => $assignment->user->phone ?? null,
                    'created_at' => $assignment->user->created_at?->toDateString(),
                ] : null,
                'reseller' => $assignment->reseller ? [
                    'id' => $assignment->reseller->id,
                    'name' => $assignment->reseller->name,
                    'email' => $assignment->reseller->email,
                ] : null,
            ];
        }

        return Inertia::render('Admin/SerialDevices/Show', [
            'device' => (new SerialDeviceResource($serialDevice))->resolve(),
            'customKeys' => $customKeysData,
            'assignment' => $assignmentData,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'statuses' => SerialDevice::statuses(),
        ]);
    }

    public function updateExpiresAt(Request $request, SerialDevice $serialDevice): RedirectResponse
    {
        $validated = $request->validate([
            'expires_at' => ['nullable', 'date'],
            'is_lifetime' => ['nullable', 'boolean'],
        ]);

        $assignment = $serialDevice->userDeviceAssignment ?? \App\Models\SerialUserDevice::firstOrCreate(
            ['device_id' => $serialDevice->device_id],
            ['status' => \App\Models\SerialUserDevice::STATUS_ACTIVE, 'notes' => 'Auto-created on expiration update']
        );

        if (! empty($validated['is_lifetime'])) {
            $assignment->update(['expires_at' => null]);
        } else {
            $assignment->update([
                'expires_at' => $validated['expires_at'] ? \Illuminate\Support\Carbon::parse($validated['expires_at'])->endOfDay() : null,
            ]);
        }

        return back()->with('success', 'License expiration updated successfully.');
    }

    public function updateStatus(UpdateSerialDeviceStatusRequest $request, SerialDevice $serialDevice): RedirectResponse
    {
        $status = $request->validated('status');
        $this->serialDeviceService->updateStatus($serialDevice, $status);

        if ($request->has('expires_at') || $request->has('is_lifetime')) {
            $assignment = $serialDevice->userDeviceAssignment ?? \App\Models\SerialUserDevice::firstOrCreate(
                ['device_id' => $serialDevice->device_id],
                ['status' => \App\Models\SerialUserDevice::STATUS_ACTIVE, 'notes' => 'Auto-created on activation']
            );

            if ($request->boolean('is_lifetime')) {
                $assignment->update([
                    'expires_at' => null,
                    'status' => $status === SerialDevice::STATUS_ACTIVE ? \App\Models\SerialUserDevice::STATUS_ACTIVE : $assignment->status,
                ]);
            } elseif ($request->filled('expires_at')) {
                $assignment->update([
                    'expires_at' => \Illuminate\Support\Carbon::parse($request->input('expires_at'))->endOfDay(),
                    'status' => $status === SerialDevice::STATUS_ACTIVE ? \App\Models\SerialUserDevice::STATUS_ACTIVE : $assignment->status,
                ]);
            }
        }

        return back()->with('success', __('general.device_status_updated'));
    }

    public function destroy(SerialDevice $serialDevice): RedirectResponse
    {
        $this->serialDeviceService->deleteDevice($serialDevice);

        return back()->with('success', __('general.device_deleted'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'user' => trim((string) $request->query('user')),
            'status' => in_array($request->query('status'), SerialDevice::statuses(), true) ? $request->query('status') : null,
            'software_id' => $request->query('software_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'last_check_from' => $request->query('last_check_from'),
            'last_check_to' => $request->query('last_check_to'),
            'os_version' => $request->query('os_version'),
            'is_64bit' => $request->query('is_64bit'),
            'has_user' => $request->query('has_user'),
        ];

        $query = $this->buildFilteredQuery($filters);
        $query->orderBy('created_at', 'desc');

        $filename = 'serial-devices-'.now()->format('Y-m-d').'.csv';

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
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:serial_devices,id'],
            'status' => ['required', 'string', Rule::in(SerialDevice::statuses())],
        ]);

        SerialDevice::whereIn('id', $validated['ids'])->update(['status' => $validated['status']]);

        $count = count($validated['ids']);

        return back()->with('success', __(':count device(s) status updated.', ['count' => $count]));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:serial_devices,id'],
        ]);

        $count = SerialDevice::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', __(':count device(s) deleted.', ['count' => $count]));
    }

    /**
     * Build a filtered query from filter params (shared between index & export).
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = SerialDevice::query()->with(['software.customKeys', 'deviceKeys', 'userDeviceAssignment.user']);

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
        $query->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s));

        // Software
        $query->when($filters['software_id'] ?? null, fn ($q, $id) => $q->where('serial_software_id', (int) $id));

        // Date range (created_at)
        $query->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d));
        $query->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));

        // Last check date range
        $query->when($filters['last_check_from'] ?? null, fn ($q, $d) => $q->whereDate('last_check_date', '>=', $d));
        $query->when($filters['last_check_to'] ?? null, fn ($q, $d) => $q->whereDate('last_check_date', '<=', $d));

        // OS Version
        $query->when($filters['os_version'] ?? null, fn ($q, $os) => $q->where('os_version', 'like', "%{$os}%"));

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

    /**
     * Assign, change, or clear client on a device.
     */
    public function assignUser(Request $request, SerialDevice $serialDevice): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $userId = $validated['user_id'];

        if ($userId === null) {
            \App\Models\SerialUserDevice::where('device_id', $serialDevice->device_id)->delete();
            return back()->with('success', __('general.device_unassigned_successfully') ?? 'Device unassigned successfully.');
        }

        \App\Models\SerialUserDevice::updateOrCreate(
            ['device_id' => $serialDevice->device_id],
            [
                'user_id' => $userId,
                'status' => \App\Models\SerialUserDevice::STATUS_ACTIVE,
            ]
        );

        return back()->with('success', __('general.device_assigned_successfully'));
    }

    /**
     * Set a custom key override for this device.
     */
    public function setKeyOverride(Request $request, SerialDevice $serialDevice): RedirectResponse
    {
        $validated = $request->validate([
            'serial_software_key_id' => ['required', 'exists:serial_software_keys,id'],
            'value' => ['required', 'string'],
        ]);

        $softwareKey = \App\Models\SerialSoftwareKey::where('id', $validated['serial_software_key_id'])
            ->where('serial_software_id', $serialDevice->serial_software_id)
            ->firstOrFail();

        \App\Models\SerialDeviceKey::updateOrCreate(
            [
                'serial_device_id' => $serialDevice->id,
                'serial_software_key_id' => $softwareKey->id,
            ],
            [
                'value' => $validated['value'],
            ]
        );

        return back()->with('success', 'Device key override saved successfully.');
    }

    /**
     * Remove key override for this device, reverting back to software default.
     */
    public function removeKeyOverride(SerialDevice $serialDevice, \App\Models\SerialDeviceKey $serialDeviceKey): RedirectResponse
    {
        if ($serialDeviceKey->serial_device_id === $serialDevice->id) {
            $serialDeviceKey->delete();
        }

        return back()->with('success', 'Device key override removed (reverted to default).');
    }

    /**
     * Display the Quick Device Activation page.
     */
    public function quickActivate(Request $request): Response
    {
        $softwares = SerialSoftware::with(['packages' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('name')
            ->get()
            ->map(fn ($sw) => [
                'id' => $sw->id,
                'name' => $sw->name,
                'logo_url' => $sw->logo_url,
                'pricing_type' => $sw->pricing_type ?? ($sw->requires_payment ? 'single' : 'free'),
                'requires_payment' => (bool) $sw->requires_payment,
                'price' => $sw->price !== null ? (float) $sw->price : null,
                'currency' => $sw->currency ?? 'USD',
                'billing_cycle' => $sw->billing_cycle ?? 'lifetime',
                'billing_days' => $sw->billing_days,
                'packages' => $sw->packages->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'price' => (float) $pkg->price,
                    'currency' => $pkg->currency ?? 'USD',
                    'billing_cycle' => $pkg->billing_cycle,
                    'billing_days' => $pkg->billing_days,
                    'description' => $pkg->description,
                    'custom_values' => $pkg->custom_values ?? [],
                ]),
            ]);

        $recentActivations = SerialDevice::with(['software', 'userDeviceAssignment.user', 'package'])
            ->where('status', SerialDevice::STATUS_ACTIVE)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'device_id' => $d->device_id,
                'software_name' => $d->software?->name ?? 'Unknown',
                'package_name' => $d->package?->name ?? null,
                'machine_name' => $d->machine_name,
                'user_name' => $d->userDeviceAssignment?->user?->name ?? $d->user_name,
                'status' => $d->status,
                'expires_at' => $d->userDeviceAssignment?->expires_at?->toDateString(),
                'expires_at_formatted' => $d->userDeviceAssignment?->expires_at?->format('Y-m-d H:i'),
                'is_lifetime' => ! $d->userDeviceAssignment?->expires_at,
                'updated_at' => $d->updated_at?->diffForHumans(),
            ]);

        return Inertia::render('Admin/SerialDevices/QuickActivate', [
            'softwares' => $softwares,
            'recentActivations' => $recentActivations,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'initialDeviceId' => trim((string) $request->query('device_id', '')),
            'initialSoftwareId' => $request->query('software_id') ? (int) $request->query('software_id') : null,
        ]);
    }

    /**
     * Live search for device by code & optional software.
     */
    public function lookupDevice(Request $request): JsonResponse
    {
        $rawDeviceId = trim((string) $request->input('device_id', ''));
        $cleanDeviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawDeviceId);
        $softwareId = $request->input('software_id');

        if (empty($cleanDeviceId)) {
            return response()->json(['found' => false, 'message' => 'Empty device ID']);
        }

        $query = SerialDevice::with(['software.packages', 'userDeviceAssignment.user', 'package'])
            ->where('device_id', $cleanDeviceId);

        if ($softwareId) {
            $query->where('serial_software_id', $softwareId);
        }

        $device = $query->first();

        if (! $device) {
            return response()->json([
                'found' => false,
                'clean_device_id' => $cleanDeviceId,
                'message' => 'Device not found in database (will be pre-registered and activated)',
            ]);
        }

        $assignment = $device->userDeviceAssignment;

        return response()->json([
            'found' => true,
            'clean_device_id' => $cleanDeviceId,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'serial_software_id' => $device->serial_software_id,
                'package_id' => $device->package_id,
                'status' => $device->status,
                'machine_name' => $device->machine_name,
                'user_name' => $device->user_name,
                'os_version' => $device->os_version,
                'last_check_date' => $device->last_check_date?->diffForHumans(),
                'last_check_date_full' => $device->last_check_date?->toDateTimeString(),
                'created_at' => $device->created_at?->diffForHumans(),
            ],
            'assignment' => $assignment ? [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'expires_at' => $assignment->expires_at?->toDateString(),
                'is_lifetime' => ! $assignment->expires_at,
                'user' => $assignment->user ? [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'email' => $assignment->user->email,
                ] : null,
                'package_id' => $assignment->package_id,
            ] : null,
        ]);
    }

    /**
     * Execute quick device activation.
     */
    public function executeQuickActivation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'software_id' => ['required', 'integer', 'exists:serial_softwares,id'],
            'package_id' => ['nullable', 'integer', 'exists:serial_software_packages,id'],
            'duration_type' => ['required', 'in:lifetime,software_default,package_default,days,date'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'expires_date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cleanDeviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $validated['device_id']);
        $software = SerialSoftware::findOrFail($validated['software_id']);
        $package = ! empty($validated['package_id']) ? SerialSoftwarePackage::find($validated['package_id']) : null;

        // Calculate expiration
        $expiresAt = null;
        if ($validated['duration_type'] === 'package_default' && $package) {
            $expiresAt = match ($package->billing_cycle) {
                'monthly' => now()->addMonth()->endOfDay(),
                'annual'  => now()->addYear()->endOfDay(),
                'custom'  => $package->billing_days ? now()->addDays($package->billing_days)->endOfDay() : null,
                default   => null,
            };
        } elseif ($validated['duration_type'] === 'software_default') {
            $expiresAt = match ($software->billing_cycle) {
                'monthly' => now()->addMonth()->endOfDay(),
                'annual'  => now()->addYear()->endOfDay(),
                'custom'  => $software->billing_days ? now()->addDays($software->billing_days)->endOfDay() : null,
                default   => null,
            };
        } elseif ($validated['duration_type'] === 'days' && ! empty($validated['duration_days'])) {
            $expiresAt = now()->addDays($validated['duration_days'])->endOfDay();
        } elseif ($validated['duration_type'] === 'date' && ! empty($validated['expires_date'])) {
            $expiresAt = Carbon::parse($validated['expires_date'])->endOfDay();
        }

        // Find or create device for this software
        $device = SerialDevice::firstOrCreate(
            ['serial_software_id' => $software->id, 'device_id' => $cleanDeviceId],
            [
                'status' => SerialDevice::STATUS_ACTIVE,
                'package_id' => $package?->id,
            ]
        );

        $device->update([
            'status' => SerialDevice::STATUS_ACTIVE,
            'package_id' => $package?->id ?? $device->package_id,
        ]);

        // Find or create SerialUserDevice
        $userDevice = SerialUserDevice::withTrashed()->firstOrNew(['device_id' => $cleanDeviceId]);
        if ($userDevice->trashed()) {
            $userDevice->restore();
        }
        $userDevice->status = SerialUserDevice::STATUS_ACTIVE;
        $userDevice->expires_at = $expiresAt;
        if ($package) {
            $userDevice->package_id = $package->id;
        }
        if (! empty($validated['user_id'])) {
            $userDevice->user_id = $validated['user_id'];
        }
        if (! empty($validated['notes'])) {
            $userDevice->notes = $validated['notes'];
        } elseif (empty($userDevice->notes)) {
            $userDevice->notes = 'Activated via Quick Activation';
        }
        $userDevice->save();

        // If user_id is set and software requires payment, also ensure active license
        if (! empty($validated['user_id']) && $software->requires_payment) {
            SerialSoftwareLicense::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'serial_software_id' => $software->id,
                ],
                [
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                    'package_id' => $package?->id,
                ]
            );
        }

        $durationLabel = $expiresAt ? ('حتى ' . $expiresAt->toDateString()) : 'ترخيص دائم (Lifetime)';
        $packageLabel = $package ? (' - باقة ' . $package->name) : '';
        $message = "تم تفعيل الجهاز [{$cleanDeviceId}] بنجاح لبرنامج [{$software->name}]{$packageLabel} ({$durationLabel}).";

        return back()->with('success', $message)->with('activated_info', [
            'device_id' => $cleanDeviceId,
            'software_name' => $software->name,
            'package_name' => $package?->name,
            'duration_label' => $durationLabel,
            'expires_at' => $expiresAt?->toDateString(),
            'whatsapp_confirmation' => "مرحباً بك! تم تفعيل نسختك بنجاح لبرنامج {$software->name}.
كود جهازك: {$cleanDeviceId}
الصلاحية: {$durationLabel}
يمكنك الآن الضغط على زر 'التحقق من حالة التفعيل' داخل البرنامج للبدء مباشرة.",
        ]);
    }

}
