<?php

namespace App\Listeners;

use App\Events\AmountReceived;

class AmountReceivedListener
{
    public function __construct() {}

    public function handle(AmountReceived $event): void {}
}
