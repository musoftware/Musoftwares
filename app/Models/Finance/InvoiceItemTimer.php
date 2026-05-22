<?php

namespace App\Models\Finance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItemTimer extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_item_id', 'date_start', 'date_end', 'amount', 'project_id'];

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function diff()
    {
        if (!$this->date_end) return 0;
        return Carbon::parse($this->date_end)->diffInSeconds(Carbon::parse($this->date_start));
    }
}
