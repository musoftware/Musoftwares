<?php

namespace Modules\Booking\app\Features\WaConfirm\Services;

use Exception;

class BookingWhatsAppConfirmLimitsService
{
    public function canUse(string $limitKey): bool
    {
        return app(\App\Services\MeteredBillingService::class)->canUse($limitKey);
    }

    public function increaseUsage(string $limitKey, int $amount = 1): void
    {
        app(\App\Services\MeteredBillingService::class)->incrementUsage($limitKey, $amount);
    }

    public function enforce(string $limitKey): void
    {
        app(\App\Services\MeteredBillingService::class)->enforce($limitKey);
    }
}
