<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ServiceOrder extends Model
{
    protected $table = 'marketplace_orders';

    protected $appends = ['formatted_amount', 'formatted_commission_amount', 'formatted_seller_earnings'];

    public function getFormattedAmountAttribute()
    {
        return \App\Helpers\FinanceHelper::instance()->format_money($this->amount, $this->currency_id);
    }

    public function getFormattedCommissionAmountAttribute()
    {
        return \App\Helpers\FinanceHelper::instance()->format_money($this->commission_amount, $this->currency_id);
    }

    public function getFormattedSellerEarningsAttribute()
    {
        $earnings = $this->amount - $this->commission_amount;
        return \App\Helpers\FinanceHelper::instance()->format_money($earnings, $this->currency_id);
    }

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
        'status' => \Modules\Marketplace\Enums\ServiceOrderStatus::class,
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
