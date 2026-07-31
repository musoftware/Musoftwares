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

    protected static function booted()
    {
        // Model event hook removed in favor of single queued batch notification in controller
        static::saved(function ($timer) {
            if ($timer->invoiceItem && $timer->invoiceItem->invoice) {
                $timer->invoiceItem->invoice->updateCachedTotal();
            }
        });

        static::deleted(function ($timer) {
            if ($timer->invoiceItem && $timer->invoiceItem->invoice) {
                $timer->invoiceItem->invoice->updateCachedTotal();
            }
        });
    }

    public function invoiceItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function diff()
    {
        if (! $this->date_end) {
            return 0;
        }

        return Carbon::parse($this->date_end)->diffInSeconds(Carbon::parse($this->date_start));
    }
}
