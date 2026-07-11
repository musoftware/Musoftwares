<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItemTimer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['invoice_item_id', 'date_start', 'date_end', 'amount', 'project_id', 'user_id', 'currency_id', 'business_amount', 'business_calculated'];

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function diff()
    {
        if (! $this->date_end) {
            return 0;
        }

        return Carbon::parse($this->date_end)->diffInSeconds(Carbon::parse($this->date_start));
    }
}
