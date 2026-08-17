<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QuotationOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (QuotationOrder $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }

            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = static::withTrashed()
            ->where('order_number', 'LIKE', "QTO-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder && preg_match('/QTO-' . $year . '-(\d+)/', $lastOrder->order_number, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('QTO-%s-%04d', $year, $nextSeq);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
