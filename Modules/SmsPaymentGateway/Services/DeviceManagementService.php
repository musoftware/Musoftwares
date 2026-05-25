<?php

namespace Modules\SmsPaymentGateway\Services;

use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class DeviceManagementService
{
    /**
     * Prepare data for a new device connection QR code.
     * Enforces SaaS limits based on config.
     *
     * @return array
     * @throws Exception
     */
    public function generateConnectionData(User $user): array
    {
        $maxDevices = config('sms-payment-gateway.max_devices_per_tenant', 1);
        $currentDeviceCount = SmsPaymentGatewayDevice::where('user_id', $user->id)->count();

        if ($currentDeviceCount >= $maxDevices) {
            throw new Exception(__('messages.device_limit_reached', ['limit' => $maxDevices]) ?? 'Device limit reached. Please upgrade your subscription.');
        }

        $connectionCode = Str::random(32);
        $expiresAt = Carbon::now()->addMinutes(10);

        SmsPaymentGatewayDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'pending'
            ],
            [
                'connection_code' => $connectionCode,
                'connection_code_expires_at' => $expiresAt,
                'status' => 'pending',
            ]
        );

        return [
            'connection_code' => $connectionCode,
            'expires_at' => $expiresAt,
            'qr_data' => [
                'type' => 'sms_payment_gateway_connection',
                'connection_code' => $connectionCode,
                'user_id' => $user->id,
                'api_url' => url('/api/sms-payment-gateway/connect'),
                'expires_at' => $expiresAt->toIso8601String(),
            ]
        ];
    }

    /**
     * Delete a device.
     */
    public function deleteDevice(SmsPaymentGatewayDevice $device): void
    {
        $device->delete();
    }

    /**
     * Clear all transactions for a device.
     */
    public function clearDeviceTransactions(SmsPaymentGatewayDevice $device): int
    {
        return SmsPaymentGatewayTransaction::where('device_id', $device->id)->delete();
    }

    /**
     * Toggle the spoof detection feature for a device.
     */
    public function toggleSpoofDetection(SmsPaymentGatewayDevice $device): bool
    {
        $newStatus = !($device->enable_spoof_detection ?? true);

        $device->update([
            'enable_spoof_detection' => $newStatus
        ]);

        return $newStatus;
    }
}
