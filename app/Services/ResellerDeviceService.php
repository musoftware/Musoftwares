<?php

namespace App\Services;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
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
     * Get softwares allocated to the reseller.
     */
    public function getAllocatedSoftwares(User $reseller)
    {
        return SerialSoftwareReseller::with('software')
            ->where('user_id', $reseller->id)
            ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
            ->get()
            ->map(function ($allocation) {
                $activeCount = $allocation->activeDevicesCount();
                $quota = $allocation->max_devices;
                $remaining = $allocation->remainingQuota();

                return [
                    'id' => $allocation->id,
                    'serial_software_id' => $allocation->serial_software_id,
                    'software_name' => $allocation->software?->name ?? 'Unknown',
                    'max_devices' => $quota,
                    'active_devices_count' => $activeCount,
                    'remaining_quota' => $remaining,
                    'is_unlimited' => $allocation->isUnlimitedDevices(),
                    'status' => $allocation->status,
                ];
            });
    }

    /**
     * Get high-level stats for the reseller dashboard.
     */
    public function getResellerStats(User $reseller): array
    {
        $cairoNow = now()->setTimezone('Africa/Cairo');

        $baseQuery = SerialUserDevice::query();

        if ($reseller->canViewAllDevices()) {
            $allocatedSoftwareIds = SerialSoftwareReseller::where('user_id', $reseller->id)
                ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
                ->pluck('serial_software_id')
                ->toArray();

            $baseQuery->whereHas('devices', function ($q) use ($allocatedSoftwareIds) {
                $q->whereIn('serial_software_id', $allocatedSoftwareIds);
            });
        } else {
            $baseQuery->where('reseller_id', $reseller->id);
        }

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
            'total_softwares' => $softwaresCount,
            'total_devices' => $totalDevices,
            'active_devices' => $activeDevices,
            'expired_devices' => $expiredDevices,
            'expiring_soon' => $expiringSoon,
        ];
    }

    /**
     * Query devices managed by this reseller with filters.
     */
    public function getResellerDevices(User $reseller, array $filters): LengthAwarePaginator
    {
        $perPage = in_array((int) ($filters['per_page'] ?? 20), [10, 20, 50, 100], true)
            ? (int) ($filters['per_page'] ?? 20)
            : 20;

        $cairoNow = now()->setTimezone('Africa/Cairo');

        $userCols = 'id,name,email';
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
            $userCols .= ',phone';
        }

        $query = SerialUserDevice::query()
            ->with(['user:' . $userCols, 'reseller:id,name,email', 'devices.software:id,name']);

        if ($reseller->canViewAllDevices()) {
            $allocatedSoftwareIds = SerialSoftwareReseller::where('user_id', $reseller->id)
                ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
                ->pluck('serial_software_id')
                ->toArray();

            $query->whereHas('devices', function ($q) use ($allocatedSoftwareIds) {
                $q->whereIn('serial_software_id', $allocatedSoftwareIds);
            });
        } else {
            $query->where('reseller_id', $reseller->id);
        }

        // Filter by search
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

        // Filter by software
        if (! empty($filters['software_id'])) {
            $softwareId = (int) $filters['software_id'];
            $query->whereHas('devices', fn ($d) => $d->where('serial_software_id', $softwareId));
        }

        // Filter by status / expiry state
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

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Assign a device to a customer under the reseller.
     */
    public function assignDeviceToCustomer(User $reseller, array $data): SerialUserDevice
    {
        return DB::transaction(function () use ($reseller, $data) {
            // 1. Verify reseller has access to this software
            $allocation = SerialSoftwareReseller::where('user_id', $reseller->id)
                ->where('serial_software_id', $data['serial_software_id'])
                ->where('status', SerialSoftwareReseller::STATUS_ACTIVE)
                ->first();

            if (! $allocation && ! $reseller->isAdmin()) {
                throw ValidationException::withMessages([
                    'serial_software_id' => [__('You do not have access to distribute this software.')],
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

            // 3. Find or create customer
            $customer = $this->resolveCustomer($data);

            // 4. Calculate expiration
            $expiresAt = $this->calculateExpiration(
                $data['duration_preset'] ?? '1_month',
                $data['custom_expires_at'] ?? null
            );

            // 5. Ensure device_id is not actively assigned to another user
            $existingAssignment = SerialUserDevice::where('device_id', $data['device_id'])
                ->where('user_id', '!=', $customer->id)
                ->first();

            if ($existingAssignment) {
                throw ValidationException::withMessages([
                    'device_id' => [__('This device is already assigned to another customer.')],
                ]);
            }

            // 6. Create or update SerialUserDevice
            $userDevice = SerialUserDevice::withTrashed()->updateOrCreate(
                ['device_id' => $data['device_id']],
                [
                    'user_id' => $customer->id,
                    'reseller_id' => $reseller->id,
                    'status' => SerialUserDevice::STATUS_ACTIVE,
                    'expires_at' => $expiresAt,
                    'notes' => $data['notes'] ?? null,
                    'deleted_at' => null,
                ]
            );

            // 7. Ensure SerialDevice record exists for this software
            SerialDevice::firstOrCreate(
                [
                    'serial_software_id' => $data['serial_software_id'],
                    'device_id' => $data['device_id'],
                ],
                [
                    'status' => SerialDevice::STATUS_ACTIVE,
                ]
            )->update([
                'status' => SerialDevice::STATUS_ACTIVE,
            ]);

            return $userDevice;
        });
    }

    /**
     * Extend / renew a device's license expiration.
     */
    public function renewDevice(SerialUserDevice $serialUserDevice, string $preset, ?string $customDate = null): SerialUserDevice
    {
        $cairoNow = now()->setTimezone('Africa/Cairo');

        // If existing expiration is in the future, extend from that point; otherwise extend from now
        $baseDate = ($serialUserDevice->expires_at && $serialUserDevice->expires_at->greaterThan($cairoNow))
            ? (clone $serialUserDevice->expires_at)->setTimezone('Africa/Cairo')
            : (clone $cairoNow);

        $newExpiry = match ($preset) {
            '1_month' => (clone $baseDate)->addMonth(),
            '3_months' => (clone $baseDate)->addMonths(3),
            '6_months' => (clone $baseDate)->addMonths(6),
            '1_year' => (clone $baseDate)->addYear(),
            'lifetime' => null,
            'custom' => $customDate ? Carbon::parse($customDate, 'Africa/Cairo') : (clone $baseDate)->addMonth(),
            default => (clone $baseDate)->addMonth(),
        };

        $serialUserDevice->update([
            'status' => SerialUserDevice::STATUS_ACTIVE,
            'expires_at' => $newExpiry,
        ]);

        // Keep SerialDevice in sync
        SerialDevice::where('device_id', $serialUserDevice->device_id)
            ->update(['status' => SerialDevice::STATUS_ACTIVE]);

        return $serialUserDevice;
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
            // Update phone if provided
            if (! empty($data['customer_phone'])) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone') && empty($customer->phone)) {
                    $customer->update(['phone' => $data['customer_phone']]);
                }
            }
        }

        return $customer;
    }

    /**
     * Calculate initial expiration timestamp based on preset.
     */
    protected function calculateExpiration(string $preset, ?string $customDate): ?Carbon
    {
        $cairoNow = now()->setTimezone('Africa/Cairo');

        return match ($preset) {
            '1_month' => (clone $cairoNow)->addMonth(),
            '3_months' => (clone $cairoNow)->addMonths(3),
            '6_months' => (clone $cairoNow)->addMonths(6),
            '1_year' => (clone $cairoNow)->addYear(),
            'lifetime' => null,
            'custom' => $customDate ? Carbon::parse($customDate, 'Africa/Cairo') : (clone $cairoNow)->addMonth(),
            default => (clone $cairoNow)->addMonth(),
        };
    }
}
