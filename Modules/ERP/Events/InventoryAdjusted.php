<?php

namespace Modules\ERP\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $adjustmentAmount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($product, float $adjustmentAmount)
    {
        $this->product = $product;
        $this->adjustmentAmount = $adjustmentAmount;
    }
}
