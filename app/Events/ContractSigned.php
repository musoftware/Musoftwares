<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractSigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contract;

    public function __construct($contract)
    {
        $this->contract = $contract;
    }
}
