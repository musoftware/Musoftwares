<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shortlink\Models\Shortlink;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'deposit_percentage' => 'decimal:2',
        'development_total' => 'decimal:2',
        'indicative_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'valid_until' => 'date',
        'last_viewed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Quotation $quotation) {
            if (empty($quotation->uuid)) {
                $quotation->uuid = (string) Str::uuid();
            }

            if (empty($quotation->quotation_number)) {
                $quotation->quotation_number = static::generateQuotationNumber();
            }
        });
    }

    public static function generateQuotationNumber(): string
    {
        $year = date('Y');
        $lastQuote = static::withTrashed()
            ->where('quotation_number', 'LIKE', "QT-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastQuote && preg_match('/QT-' . $year . '-(\d+)/', $lastQuote->quotation_number, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('QT-%s-%04d', $year, $nextSeq);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function ourWorkItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->where('type', 'our_work')->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function indicativeItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->where('type', 'indicative_cost')->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(QuotationOrder::class)->orderBy('id', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function currencyModel(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class, 'shortlink_id');
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');

        $devTotal = 0;
        $indTotal = 0;

        foreach ($this->items as $item) {
            $total = (float) $item->price * (int) max(1, $item->quantity);
            if ($item->type === 'indicative_cost') {
                $indTotal += $total;
            } else {
                $devTotal += $total;
            }
        }

        $depositPct = (float) ($this->deposit_percentage ?? 50.00);
        $depositAmount = $devTotal * ($depositPct / 100.0);
        $remainingAmount = $devTotal - $depositAmount;
        $grandTotal = $devTotal + $indTotal;

        $this->updateQuietly([
            'development_total' => $devTotal,
            'indicative_total' => $indTotal,
            'grand_total' => $grandTotal,
            'deposit_amount' => $depositAmount,
            'remaining_amount' => $remainingAmount,
        ]);
    }
}
