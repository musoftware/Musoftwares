<?php

namespace App\Listeners;

use App\Events\AmountReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class AmountReceivedListener
{
    public function __construct()
    {
    }

    public function handle(AmountReceived $event): void
    {
        // 1. Notify the user or relevant tenant that an amount was received.
        if (isset($event->client) && isset($event->client->user)) {
            $event->client->user->notify(new \App\Notifications\AmountReceivedNotification($event->amount, $event->currencyId));
        } elseif (isset($event->client) && isset($event->client->email)) {
             \Illuminate\Support\Facades\Notification::route('mail', $event->client->email)
                ->notify(new \App\Notifications\AmountReceivedNotification($event->amount, $event->currencyId));
        }


    }
}
