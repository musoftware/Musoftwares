<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ServiceOrder extends Model
{
    protected $table = 'marketplace_orders';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'package_id',
        'amount',
        'currency_id',
        'business_amount',
        'business_currency_id',
        'commission_amount',
        'status',
        'delivered_at',
        'completed_at',
        'delivery_payload',
        'auto_complete_at'
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'auto_complete_at' => 'datetime',
        'delivery_payload' => 'array',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }
}
