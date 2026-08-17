<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (QuotationItem $item) {
            $item->total = (float) $item->price * (int) max(1, $item->quantity);
        });

        static::saved(function (QuotationItem $item) {
            if ($item->quotation) {
                $item->quotation->recalculateTotals();
            }
        });

        static::deleted(function (QuotationItem $item) {
            if ($item->quotation) {
                $item->quotation->recalculateTotals();
            }
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
