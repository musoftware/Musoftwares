<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringInvoiceRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function recurringInvoice()
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
