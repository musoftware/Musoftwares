<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
            $event->tenantUsage->user->notify(new \App\Notifications\SaaSLimitReachedNotification($event->tenantUsage));
        }
    }
}
