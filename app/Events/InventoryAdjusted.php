<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $quantityAdjusted;
    public $reason;

    public function __construct($product, $quantityAdjusted, $reason)
    {
        $this->product = $product;
        $this->quantityAdjusted = $quantityAdjusted;
        $this->reason = $reason;
    }
}
