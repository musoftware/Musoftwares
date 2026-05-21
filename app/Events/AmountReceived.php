<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmountReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client;
    public $amount;
    public $reason;
    public $currency;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($client, $amount, $reason, $currency = 'EGP')
    {
        $this->client = $client;
        $this->amount = $amount;
        $this->reason = $reason;
        $this->currency = $currency;
    }
}
