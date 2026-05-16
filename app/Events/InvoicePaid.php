<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class InvoicePaid {
    use Dispatchable, SerializesModels;
    public $invoice;
    public function __construct($invoice) { $this->invoice = $invoice; }
}
