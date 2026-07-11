<?php

namespace App\Listeners;

use App\Notifications\SaaSLimitApproachingNotification;
use Illuminate\Support\Facades\Log;

class SaaSLimitListener
{
    public function __construct()
    {
        //
    }

    public function handle(object $event): void
    {
        Log::info('SaaSLimitApproaching event handled.', [
            'usage_key' => $event->tenantUsage->usage_key ?? null,
            'percentage' => $event->percentageUsed ?? null,
        ]);

        if (isset($event->tenantUsage) && isset($event->tenantUsage->user)) {
            $event->tenantUsage->user->notify(new SaaSLimitApproachingNotification($event->tenantUsage, $event->percentageUsed));
        }
    }
}
