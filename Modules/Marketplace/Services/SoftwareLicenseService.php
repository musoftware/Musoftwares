<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\ServiceSerial;
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Marketplace\Helpers\MarketplaceHelper;

class SoftwareLicenseService
{
    /**
     * Assign a serial key to an order from inventory or auto-generate if permitted.
     */
    public function assignSerialToOrder(int $serviceId, int $orderId, ?int $buyerId = null): ServiceSerial
    {
        return DB::transaction(function () use ($serviceId, $buyerId) {
            $serial = ServiceSerial::where('service_id', $serviceId)
                ->where('is_used', false)
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                // Auto generate serial key if no pre-stocked serial exists
                $serial = ServiceSerial::create([
                    'service_id' => $serviceId,
                    'serial_code' => MarketplaceHelper::generateSerialKey('SOFT'),
                    'is_used' => false,
                ]);
            }

            $serial->update([
                'is_used' => true,
                'used_by' => $buyerId ?? auth()->id() ?? 1,
                'used_at' => now('Africa/Cairo'),
            ]);

            return $serial;
        });
    }

    /**
     * Add bulk serial keys to a service inventory.
     */
    public function addSerialsToService(int $serviceId, array $serialCodes): int
    {
        $count = 0;
        DB::transaction(function () use ($serviceId, $serialCodes, &$count) {
            foreach ($serialCodes as $code) {
                $code = trim($code);
                if (empty($code)) {
                    continue;
                }

                $exists = ServiceSerial::where('service_id', $serviceId)
                    ->where('serial_code', $code)
                    ->exists();

                if (!$exists) {
                    ServiceSerial::create([
                        'service_id' => $serviceId,
                        'serial_code' => $code,
                        'is_used' => false,
                    ]);
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Get count of available (unused) serial keys for a service.
     */
    public function getAvailableSerialsCount(int $serviceId): int
    {
        return ServiceSerial::where('service_id', $serviceId)
            ->where('is_used', false)
            ->count();
    }
}

