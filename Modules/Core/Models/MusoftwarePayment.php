<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusoftwarePayment extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'customer_data' => 'array',
        'metadata' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(MusoftwareClient::class, 'client_id');
    }
}
