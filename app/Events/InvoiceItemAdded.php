<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceItemAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;

    public $item;

    public function __construct($invoice, $item)
    {
        $this->invoice = $invoice;
        $this->item = $item;
    }
}
