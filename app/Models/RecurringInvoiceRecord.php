<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringInvoiceRecord extends Model
{
    use HasFactory, ScopesToLegalWindow;

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
