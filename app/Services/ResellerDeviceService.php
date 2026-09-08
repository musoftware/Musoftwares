<?php

namespace App\Services;

use App\Helpers\FinanceHelper;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\SerialDevice;
use App\Models\SerialDeviceTrialLog;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwarePackage;
use App\Models\SerialSoftwareReseller;
use App\Models\SerialUserDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResellerDeviceService extends BaseService
{
    /**
     * Get softwares allocated to the reseller with pricing details and active packages.
     */
    public function getAllocatedSoftwares(User $reseller)
    {
        return SerialSoftwareReseller::with(['software.packages' => fn ($q) => $q->active()])
            ->where('user_id', $reseller->id)
            ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
            ->get()
            ->map(function ($allocation) {
                $software = $allocation->software;
                $activeCount = $allocation->activeDevicesCount();
                $quota = $allocation->max_devices;
                $remaining = $allocation->remainingQuota();

                $packages = $software ? $software->packages->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'price' => (float) $pkg->price,
                    'reseller_price' => $pkg->reseller_price !== null ? (float) $pkg->reseller_price : (float) $pkg->price,
                    'currency' => $pkg->currency ?: 'USD',
                    'billing_cycle' => $pkg->billing_cycle,
                    'billing_days' => $pkg->billing_days,
                    'is_default' => (bool) $pkg->is_default,
                    'description' => $pkg->description,
                ])->values()->all() : [];

                return [
                    'id' => $allocation->id,
                    'serial_software_id' => $allocation->serial_software_id,
                    'software_name' => $software?->name ?? 'Unknown',
                    'pricing_type' => $software?->pricing_type ?? 'free',
                    'requires_payment' => (bool) ($software?->requires_payment ?? false),
                    'price' => $software?->price !== null ? (float) $software->price : null,
                    'reseller_price' => $software?->reseller_price !== null ? (float) $software->reseller_price : null,
                    'currency' => $software?->currency ?? 'USD',
                    'billing_cycle' => $software?->billing_cycle ?? 'lifetime',
                    'billing_days' => $software?->billing_days,
                    'packages' => $packages,
                    'max_devices' => $quota,
                    'can_view_all_devices' => (bool) $allocation->can_view_all_devices,
                    'active_devices_count' => $activeCount,
                    'remaining_quota' => $remaining,
                    'is_unlimited' => $allocation->isUnlimitedDevices(),
                    'status' => $allocation->status,
                ];
            });
    }

    /**
     * Check if a device has already consumed its 1-day free trial for a specific software.
     */
    public function hasUsedFreeTrial(int $softwareId, string $deviceId): bool
    {
        return SerialDeviceTrialLog::hasUsedTrial($softwareId, $deviceId);
    }

    /**
     * Apply reseller device visibility scope (per software).
     */
    public function applyResellerDeviceScope(Builder $query, User $reseller): void
    {
        if ($reseller->isAdmin()) {
            return;
        }

        $allDevicesSoftwareIds = SerialSoftwareReseller::where('user_id', $reseller->id)
            ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
            ->where('can_view_all_devices', true)
            ->pluck('serial_software_id')
            ->toArray();

        $ownDevicesSoftwareIds = SerialSoftwareReseller::where('user_id', $reseller->id)
            ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
            ->where('can_view_all_devices', false)
            ->pluck('serial_software_id')
            ->toArray();

        if ($reseller->can_view_all_devices) {
            $allAllocated = array_unique(array_merge($allDevicesSoftwareIds, $ownDevicesSoftwareIds));
            $query->whereHas('devices', function ($q) use ($allAllocated) {
                $q->whereIn('serial_software_id', $allAllocated);
            });
            return;
        }

        $query->where(function (Builder $q) use ($reseller, $allDevicesSoftwareIds, $ownDevicesSoftwareIds) {
            $hasCondition = false;

            if (! empty($allDevicesSoftwareIds)) {
                $q->whereHas('devices', function ($sub) use ($allDevicesSoftwareIds) {
                    $sub->whereIn('serial_software_id', $allDevicesSoftwareIds);
                });
                $hasCondition = true;
            }

            if (! empty($ownDevicesSoftwareIds)) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $q->$method(function ($sub) use ($reseller, $ownDevicesSoftwareIds) {
                    $sub->where('reseller_id', $reseller->id)
                        ->whereHas('devices', function ($d) use ($ownDevicesSoftwareIds) {
                            $d->whereIn('serial_software_id', $ownDevicesSoftwareIds);
                        });
                });
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Get high-level stats for the reseller dashboard.
     */
    public function getResellerStats(User $reseller): array
    {
        $cairoNow = now()->setTimezone('Africa/Cairo');

        $baseQuery = SerialUserDevice::query();
        $this->applyResellerDeviceScope($baseQuery, $reseller);

        $totalDevices = (clone $baseQuery)->count();
        $activeDevices = (clone $baseQuery)->where('status', SerialUserDevice::STATUS_ACTIVE)
            ->where(function ($q) use ($cairoNow) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $cairoNow);
            })->count();

        $expiredDevices = (clone $baseQuery)->whereNotNull('expires_at')
            ->where('expires_at', '<=', $cairoNow)->count();

        $expiringSoon = (clone $baseQuery)->where('status', SerialUserDevice::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$cairoNow, (clone $cairoNow)->addDays(7)])
            ->count();

        $softwaresCount = SerialSoftwareReseller::where('user_id', $reseller->id)
            ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
            ->count();

        return [
            'total_devices' => $totalDevices,
            'active_devices' => $activeDevices,
            'expired_devices' => $expiredDevices,
            'expiring_soon' => $expiringSoon,
            'total_softwares' => $softwaresCount,
        ];
    }

    /**
     * Paginated list of devices belonging to or visible to this reseller.
     */
    public function getResellerDevices(User $reseller, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $cairoNow = now()->setTimezone('Africa/Cairo');

        $userCols = 'id,name,email';
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
            $userCols .= ',phone';
        }

        $query = SerialUserDevice::query()
            ->with([
                'user:' . $userCols,
                'reseller:id,name,email',
                'package:id,name,price,reseller_price,currency,billing_cycle',
                'devices.software:id,name,price,reseller_price,currency,pricing_type',
            ]);

        $this->applyResellerDeviceScope($query, $reseller);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('device_id', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
                            $u->orWhere('phone', 'like', "%{$search}%");
                        }
                    });
            });
        }

        if (! empty($filters['software_id'])) {
            $softwareId = (int) $filters['software_id'];
            $query->whereHas('devices', fn ($d) => $d->where('serial_software_id', $softwareId));
        }

        if (! empty($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->where('status', SerialUserDevice::STATUS_ACTIVE)
                    ->where(function ($q) use ($cairoNow) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', $cairoNow);
                    }),
                'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', $cairoNow),
                'expiring_soon' => $query->where('status', SerialUserDevice::STATUS_ACTIVE)
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [$cairoNow, (clone $cairoNow)->addDays(7)]),
                'inactive' => $query->where('status', SerialUserDevice::STATUS_INACTIVE),
                default => null,
            };
        }

        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        // Check trial status for current devices in the page
        $deviceIds = $paginator->pluck('device_id')->unique()->toArray();
        $trialClaimedMap = SerialDeviceTrialLog::whereIn('device_id', $deviceIds)
            ->get()
            ->groupBy(fn ($item) => $item->serial_software_id . '_' . $item->device_id);

        $paginator->getCollection()->transform(function ($item) use ($trialClaimedMap) {
            $softwareId = $item->devices->first()?->serial_software_id;
            $key = $softwareId . '_' . $item->device_id;
            $item->has_used_trial = $softwareId ? isset($trialClaimedMap[$key]) : false;
            return $item;
        });

        return $paginator;
    }

    /**
     * Resolve plan expiration and reseller cost.
     */
    public function resolvePlanCostAndExpiry(
        SerialSoftware $software,
        string $preset,
        ?int $packageId = null,
        ?string $customDate = null,
        ?Carbon $baseDate = null
    ): array {
        $cairoNow = $baseDate ? (clone $baseDate)->setTimezone('Africa/Cairo') : now()->setTimezone('Africa/Cairo');

        // 1-Day Free Trial
        if ($preset === '1_day_trial') {
            return [
                'cost' => 0.00,
                'customer_price' => 0.00,
                'currency' => $software->currency ?: 'USD',
                'expires_at' => (clone $cairoNow)->addDay(),
                'package_id' => null,
                'is_free_trial' => true,
                'description' => __('1-Day Free Trial'),
            ];
        }

        // Package selected
        if ($packageId) {
            $pkg = SerialSoftwarePackage::where('serial_software_id', $software->id)->findOrFail($packageId);
            $resellerCost = $pkg->reseller_price !== null ? (float) $pkg->reseller_price : (float) $pkg->price;
            $customerPrice = (float) $pkg->price;

            $expiresAt = match ($pkg->billing_cycle) {
                SerialSoftwarePackage::CYCLE_LIFETIME => null,
                SerialSoftwarePackage::CYCLE_MONTHLY => (clone $cairoNow)->addMonth(),
                SerialSoftwarePackage::CYCLE_ANNUAL => (clone $cairoNow)->addYear(),
                SerialSoftwarePackage::CYCLE_CUSTOM => $pkg->billing_days
                    ? (clone $cairoNow)->addDays($pkg->billing_days)
                    : (clone $cairoNow)->addMonth(),
                default => (clone $cairoNow)->addMonth(),
            };

            return [
                'cost' => $resellerCost,
                'customer_price' => $customerPrice,
                'currency' => $pkg->currency ?: ($software->currency ?: 'USD'),
                'expires_at' => $expiresAt,
                'package_id' => $pkg->id,
                'is_free_trial' => false,
                'description' => __('Package: :name', ['name' => $pkg->name]),
            ];
        }

        // Free software
        if ($software->isFree()) {
            return [
                'cost' => 0.00,
                'customer_price' => 0.00,
                'currency' => $software->currency ?: 'USD',
                'expires_at' => $this->calculatePresetExpiry($cairoNow, $preset, $customDate),
                'package_id' => null,
                'is_free_trial' => false,
                'description' => __('Free Software Activation'),
            ];
        }

        // Single Paid Software
        $baseReseller = $software->reseller_price !== null ? (float) $software->reseller_price : (float) $software->price;
        $baseCustomer = (float) ($software->price ?? 0.00);

        $multiplier = match ($preset) {
            '1_month' => 1,
            '3_months' => 3,
            '6_months' => 6,
            '1_year' => 12,
            'lifetime' => 1,
            default => 1,
        };

        return [
            'cost' => $baseReseller * $multiplier,
            'customer_price' => $baseCustomer * $multiplier,
            'currency' => $software->currency ?: 'USD',
            'expires_at' => $this->calculatePresetExpiry($cairoNow, $preset, $customDate),
            'package_id' => null,
            'is_free_trial' => false,
            'description' => __('License Term: :term', ['term' => $preset]),
        ];
    }

    /**
     * Helper to calculate expiry date from preset string.
     */
    protected function calculatePresetExpiry(Carbon $baseDate, string $preset, ?string $customDate = null): ?Carbon
    {
        return match ($preset) {
            '1_month' => (clone $baseDate)->addMonth(),
            '3_months' => (clone $baseDate)->addMonths(3),
            '6_months' => (clone $baseDate)->addMonths(6),
            '1_year' => (clone $baseDate)->addYear(),
            'lifetime' => null,
            'custom' => $customDate ? Carbon::parse($customDate, 'Africa/Cairo') : (clone $baseDate)->addMonth(),
            default => (clone $baseDate)->addMonth(),
        };
    }

    /**
     * Charge the reseller's wallet balance using multi-currency exchange and pessimistic lock.
     */
    public function chargeResellerWallet(User $reseller, float $cost, string $currencyCode, string $description): void
    {
        if ($cost <= 0) {
            return;
        }

        // Admins bypass wallet deduction
        if ($reseller->isAdmin()) {
            return;
        }

        $softwareCurrencyModel = Currency::where('currency', $currencyCode)->first();
        $softwareCurrencyId = $softwareCurrencyModel?->id ?? (int) $reseller->currency_id;

        // Lock reseller row for update to prevent race conditions
        $lockedReseller = User::where('id', $reseller->id)->lockForUpdate()->first();
        if (! $lockedReseller) {
            throw new \Exception('Reseller user account not found.');
        }

        // Calculate cost in reseller's wallet currency
        $costInResellerCurrency = CurrenciesExchange::RateToday($cost, $softwareCurrencyId, $lockedReseller->currency_id);
        $availableBalance = (float) $lockedReseller->available_balance();

        if ($availableBalance < $costInResellerCurrency) {
            $neededFormatted = FinanceHelper::instance()->format_money($costInResellerCurrency, $lockedReseller->currency_id);
            $availableFormatted = FinanceHelper::instance()->format_money($availableBalance, $lockedReseller->currency_id);

            throw ValidationException::withMessages([
                'balance' => [
                    __('Insufficient wallet balance. This activation requires :cost, but your available balance is only :available. Please top up your wallet.', [
                        'cost' => $neededFormatted,
                        'available' => $availableFormatted,
                    ]),
                ],
            ]);
        }

        // Deduct from wallet with full transaction logging
        $lockedReseller->add_balance(-1 * $cost, $description, 'used', $softwareCurrencyId);
    }

    /**
     * Assign a device to a customer under the reseller, charging wallet or granting 1-day trial.
     */
    public function assignDeviceToCustomer(User $reseller, array $data): SerialUserDevice
    {
        return DB::transaction(function () use ($reseller, $data) {
            $software = SerialSoftware::findOrFail($data['serial_software_id']);
            $deviceId = trim($data['device_id']);
            $preset = $data['duration_preset'] ?? '1_month';
            $packageId = ! empty($data['package_id']) ? (int) $data['package_id'] : null;

            // 1. Verify reseller has access to this software
            $allocation = SerialSoftwareReseller::where('user_id', $reseller->id)
                ->where('serial_software_id', $software->id)
                ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
                ->first();

            if (! $allocation && ! $reseller->isAdmin()) {
                throw ValidationException::withMessages([
                    'serial_software_id' => [__('You do not have permission to distribute this software.')],
                ]);
            }

            // 2. Verify device quota if not unlimited
            if ($allocation && ! $allocation->isUnlimitedDevices()) {
                $activeCount = $allocation->activeDevicesCount();
                if ($activeCount >= $allocation->max_devices) {
                    throw ValidationException::withMessages([
                        'serial_software_id' => [__('Device limit reached for this software. Current: :current / :max', [
                            'current' => $activeCount,
                            'max' => $allocation->max_devices,
                        ])],
                    ]);
                }
            }

            // 3. Handle 1-Day Free Trial Anti-Bypass Check
            if ($preset === '1_day_trial') {
                if ($this->hasUsedFreeTrial($software->id, $deviceId)) {
                    throw ValidationException::withMessages([
                        'duration_preset' => [__('This device has already used its 1-day free trial for this software and cannot claim it again.')],
                    ]);
                }
            }

            // 4. Resolve plan, expiration, and cost
            $plan = $this->resolvePlanCostAndExpiry(
                $software,
                $preset,
                $packageId,
                $data['custom_expires_at'] ?? null
            );

            // 5. Charge reseller's wallet if paid
            if (! $plan['is_free_trial'] && $plan['cost'] > 0) {
                $this->chargeResellerWallet(
                    $reseller,
                    $plan['cost'],
                    $plan['currency'],
                    "Software Activation: {$software->name} ({$plan['description']}) for device {$deviceId}"
                );
            }

            // 6. Find or create customer
            $customer = $this->resolveCustomer($data);

            // 7. Ensure device_id is not actively assigned to another user
            $existingAssignment = SerialUserDevice::where('device_id', $deviceId)
                ->where('user_id', '!=', $customer->id)
                ->first();

            if ($existingAssignment) {
                throw ValidationException::withMessages([
                    'device_id' => [__('This device is already assigned to another customer.')],
                ]);
            }

            // 8. Create or update SerialUserDevice
            $userDevice = SerialUserDevice::withTrashed()->updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'user_id' => $customer->id,
                    'package_id' => $plan['package_id'],
                    'reseller_id' => $reseller->id,
                    'status' => SerialUserDevice::STATUS_ACTIVE,
                    'expires_at' => $plan['expires_at'],
                    'notes' => $data['notes'] ?? null,
                    'deleted_at' => null,
                ]
            );

            // 9. If this was a free trial, record in permanent immutable trial table
            if ($plan['is_free_trial']) {
                SerialDeviceTrialLog::recordTrial($software->id, $deviceId, $reseller->id, $customer->id);
            }

            // 10. Ensure SerialDevice record exists and is active
            SerialDevice::firstOrCreate(
                [
                    'serial_software_id' => $software->id,
                    'device_id' => $deviceId,
                ],
                [
                    'status' => SerialDevice::STATUS_ACTIVE,
                    'package_id' => $plan['package_id'],
                ]
            )->update([
                'status' => SerialDevice::STATUS_ACTIVE,
                'package_id' => $plan['package_id'],
            ]);

            return $userDevice;
        });
    }

    /**
     * Extend / renew a device's license expiration with wallet charging.
     */
    public function renewDevice(
        SerialUserDevice $serialUserDevice,
        string $preset,
        ?int $packageId = null,
        ?string $customDate = null
    ): SerialUserDevice {
        return DB::transaction(function () use ($serialUserDevice, $preset, $packageId, $customDate) {
            // Strictly forbid claiming free trial as a renewal
            if ($preset === '1_day_trial') {
                throw ValidationException::withMessages([
                    'duration_preset' => [__('The 1-day free trial can only be granted on initial activation, not for renewals.')],
                ]);
            }

            // Determine software
            $software = $serialUserDevice->package?->software
                ?? $serialUserDevice->devices()->first()?->software;

            if (! $software) {
                throw ValidationException::withMessages([
                    'device_id' => [__('Software associated with this device could not be found.')],
                ]);
            }

            $cairoNow = now()->setTimezone('Africa/Cairo');
            $baseDate = ($serialUserDevice->expires_at && $serialUserDevice->expires_at->greaterThan($cairoNow))
                ? (clone $serialUserDevice->expires_at)->setTimezone('Africa/Cairo')
                : (clone $cairoNow);

            // If a package is specified or was previously assigned, use it
            $targetPackageId = $packageId ?? $serialUserDevice->package_id;

            $plan = $this->resolvePlanCostAndExpiry(
                $software,
                $preset,
                $targetPackageId,
                $customDate,
                $baseDate
            );

            // Charge reseller wallet
            $reseller = $serialUserDevice->reseller ?? auth()->user();
            if ($reseller && $plan['cost'] > 0) {
                $this->chargeResellerWallet(
                    $reseller,
                    $plan['cost'],
                    $plan['currency'],
                    "Software License Renewal: {$software->name} ({$plan['description']}) for device {$serialUserDevice->device_id}"
                );
            }

            $serialUserDevice->update([
                'status' => SerialUserDevice::STATUS_ACTIVE,
                'expires_at' => $plan['expires_at'],
                'package_id' => $plan['package_id'] ?? $serialUserDevice->package_id,
            ]);

            // Keep SerialDevice in sync
            SerialDevice::where('device_id', $serialUserDevice->device_id)
                ->update([
                    'status' => SerialDevice::STATUS_ACTIVE,
                    'package_id' => $plan['package_id'] ?? $serialUserDevice->package_id,
                ]);

            return $serialUserDevice;
        });
    }

    /**
     * Update device status (activate or deactivate).
     */
    public function updateStatus(SerialUserDevice $serialUserDevice, string $status): void
    {
        $serialUserDevice->update(['status' => $status]);
        SerialDevice::where('device_id', $serialUserDevice->device_id)
            ->update(['status' => $status]);
    }

    /**
     * Unassign device.
     */
    public function unassignDevice(SerialUserDevice $serialUserDevice): void
    {
        $deviceId = $serialUserDevice->device_id;
        $serialUserDevice->delete();

        SerialDevice::where('device_id', $deviceId)
            ->update(['status' => SerialDevice::STATUS_INACTIVE]);
    }

    /**
     * Helper to resolve customer User.
     */
    protected function resolveCustomer(array $data): User
    {
        if (! empty($data['customer_user_id'])) {
            return User::findOrFail($data['customer_user_id']);
        }

        $email = strtolower(trim($data['customer_email']));
        $customer = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $customer) {
            $customer = new User();
            $customer->name = trim($data['customer_name']);
            $customer->email = $email;
            $customer->password = Hash::make(Str::random(16));
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone') && ! empty($data['customer_phone'])) {
                $customer->phone = $data['customer_phone'];
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'mobile_1') && ! empty($data['customer_phone'])) {
                $customer->mobile_1 = $data['customer_phone'];
            }
            $customer->save();

            try {
                if (method_exists($customer, 'assignRole')) {
                    $customer->assignRole('client');
                }
            } catch (\Throwable) {
                // Ignore if role already assigned or table missing in test
            }
        } else {
            if (! empty($data['customer_phone'])) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone') && empty($customer->phone)) {
                    $customer->update(['phone' => $data['customer_phone']]);
                }
            }
        }

        return $customer;
    }
}
