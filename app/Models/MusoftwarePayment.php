<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MusoftwarePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'external_order_id',
        'internal_order_id',
        'amount',
        'currency',
        'description',
        'success_url',
        'failure_url',
        'webhook_url',
        'customer_data',
        'metadata',
        'status',
        'kashier_payment_url',
        'kashier_transaction_id',
        'commission_rate',
        'commission_amount',
        'net_amount',
    ];

    protected $casts = [
        'customer_data' => 'array',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(MusoftwareClient::class, 'client_id');
    }

    /**
     * Check if this payment is completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
