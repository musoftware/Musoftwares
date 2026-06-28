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

    }
}
