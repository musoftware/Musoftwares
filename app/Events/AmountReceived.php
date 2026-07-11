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

    public $currencyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($client, $amount, $reason, $currencyId = null)
    {
        $this->client = $client;
        $this->amount = $amount;
        $this->reason = $reason;
        $this->currencyId = $currencyId;

        if (! $this->currencyId) {
            throw new \Exception('AmountReceived event is missing currency_id.');
        }
    }
}
