<?php

namespace App\Listeners;

use App\Notifications\SaaSLimitReachedNotification;
use Illuminate\Support\Facades\Log;

class SaaSLimitReachedListener
{
    public function __construct()
    {
        //
    }

    public function handle(object $event): void
    {
        Log::info('SaaSLimitReached event handled.', [
            'usage_key' => $event->tenantUsage->usage_key ?? null,
        ]);

        if (isset($event->tenantUsage) && isset($event->tenantUsage->user)) {
            $event->tenantUsage->user->notify(new SaaSLimitReachedNotification($event->tenantUsage));
        }
    }
}
