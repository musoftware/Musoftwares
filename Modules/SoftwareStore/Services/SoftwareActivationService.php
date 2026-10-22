<?php

namespace Modules\SoftwareStore\Services;

use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\SoftwareStore\Models\SoftwareOrder;

/**
 * Turns a paid store order into a license in the serial system:
 * client account -> license (new or extended) -> device (when the order carries a device id).
 */
class SoftwareActivationService
{
    public function activate(SoftwareOrder $order): void
    {
        $software = $order->product->serialSoftware;
        if (! $software || $order->activated_at) {
            return;
        }

        DB::transaction(function () use ($order, $software) {
            $user = $this->findOrCreateClient($order);
            $expiresAt = $this->extendLicense($user, $software, $order);

            if ($order->device_id) {
                $this->activateDevice($software, $order->device_id, $user, $expiresAt);
            }

            $order->update(['activated_at' => now(), 'license_expires_at' => $expiresAt]);
        });

        Log::info('Software order activated', ['order_id' => $order->id, 'device_id' => $order->device_id]);
    }

    private function findOrCreateClient(SoftwareOrder $order): User
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($order->client_email)])->first();
        if ($user) {
            return $user;
        }

        $user = User::create([
            'name' => $order->client_name,
            'email' => $order->client_email,
            'password' => Hash::make(Str::random(24)),
            'whatsapp_number' => $order->client_whatsapp,
        ]);
        $this->assignClientRole($user);

        return $user;
    }

    private function assignClientRole(User $user): void
    {
        try {
            $user->assignRole('client');
        } catch (\Throwable $e) {
            Log::warning('Software order: client role not assigned', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /** Adds the paid period on top of the current expiry, or on top of today when the license has ended. */
    private function extendLicense(User $user, SerialSoftware $software, SoftwareOrder $order): ?Carbon
    {
        $license = SerialSoftwareLicense::firstOrNew(['user_id' => $user->id, 'serial_software_id' => $software->id]);
        $days = $order->product->licenseDays();

        $expiresAt = $days === null ? null : $this->baseDate($license)->addDays($days);

        $license->fill(['status' => SerialSoftwareLicense::STATUS_ACTIVE, 'expires_at' => $expiresAt])->save();

        return $expiresAt;
    }

    private function baseDate(SerialSoftwareLicense $license): Carbon
    {
        $current = $license->expires_at;

        return $current && $current->isFuture() ? $current->copy() : now();
    }

    private function activateDevice(SerialSoftware $software, string $deviceId, User $user, ?Carbon $expiresAt): void
    {
        SerialDevice::withTrashed()->updateOrCreate(
            ['serial_software_id' => $software->id, 'device_id' => $deviceId],
            ['status' => SerialDevice::STATUS_ACTIVE, 'deleted_at' => null]
        );

        $assignment = SerialUserDevice::withTrashed()->firstOrNew(['device_id' => $deviceId]);
        if ($assignment->trashed()) {
            $assignment->restore();
        }
        $assignment->fill([
            'user_id' => $user->id,
            'status' => SerialUserDevice::STATUS_ACTIVE,
            'expires_at' => $expiresAt,
            'notes' => $assignment->notes ?: 'Activated by store payment',
        ])->save();
    }
}
