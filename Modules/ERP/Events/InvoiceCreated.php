<?php

namespace Modules\ERP\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ERP\Models\Invoice;

class InvoiceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
}
