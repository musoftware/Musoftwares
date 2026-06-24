<?php

namespace Modules\ERP\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\Contract;

class ContractSigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contract;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }
}
