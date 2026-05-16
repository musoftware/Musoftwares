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
        'currency_code',
        'commission_amount',
        'status',
        'delivered_at',
        'completed_at'
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
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
