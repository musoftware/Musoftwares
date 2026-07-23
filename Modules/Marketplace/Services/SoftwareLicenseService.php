<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\ServiceSerial;
use Modules\Marketplace\Models\SerialUserDevice;
use Illuminate\Support\Facades\DB;

use Exception;
use Modules\Marketplace\Helpers\MarketplaceHelper;

class SoftwareLicenseService
{
    /**
     * Assign a serial key to an order from inventory or generate a new one.
     */
    public function assignSerialToOrder(int $serviceId, int $orderId): ServiceSerial
    {
        return DB::transaction(function () use ($serviceId) {
            $serial = ServiceSerial::where('service_id', $serviceId)
                ->where('is_used', false)
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                // Auto generate serial key
                $serial = ServiceSerial::create([
                    'service_id' => $serviceId,
                    'serial_code' => MarketplaceHelper::generateSerialKey('SOFT'),
                    'is_used' => false,
                ]);
            }

            $serial->update([
                'is_used' => true,
                'used_by' => auth()->id() ?? 1,
                'used_at' => now(),
            ]);

            return $serial;
        });
    }

    /**
     * Bind device HWID / MAC address to serial key and verify quota.
     */
    public function activateDevice(string $serialCode, string $hwid, string $macAddress, ?string $deviceName = null): array
    {
        return DB::transaction(function () use ($serialCode, $hwid, $macAddress, $deviceName) {
            $serial = ServiceSerial::where('serial_code', $serialCode)->first();

            if (!$serial) {
                throw new Exception("Invalid license key.");
            }

            // Check device quota (default max 3 devices)
            $existingDevice = SerialUserDevice::where('serial_key', $serialCode)
                ->where(function ($q) use ($hwid, $macAddress) {
                    $q->where('hwid', $hwid)->orWhere('mac_address', $macAddress);
                })->first();

            if ($existingDevice) {
                $existingDevice->update([
                    'last_seen_at' => now('Africa/Cairo'),
                ]);
                return [
                    'activated' => true,
                    'message' => 'Device already authorized.',
                    'device' => $existingDevice,
                ];
            }

            $deviceCount = SerialUserDevice::where('serial_key', $serialCode)->count();
            $maxDevices = 3;

            if ($deviceCount >= $maxDevices) {
                throw new Exception("Maximum device activation limit reached ({$maxDevices}).");
            }

            $device = SerialUserDevice::create([
                'serial_key' => $serialCode,
                'hwid' => $hwid,
                'mac_address' => $macAddress,
                'device_name' => $deviceName ?? 'Unknown PC',
                'activated_at' => now('Africa/Cairo'),
                'last_seen_at' => now('Africa/Cairo'),
            ]);

            return [
                'activated' => true,
                'message' => 'Device activated successfully.',
                'device' => $device,
            ];
        });
    }
}
