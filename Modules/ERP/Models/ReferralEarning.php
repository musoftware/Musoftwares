<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralEarning extends TenantModel
{
    use SoftDeletes;

    protected $table = 'erp_client_referral_earnings';

    protected $fillable = [
        'tenant_id', 'invoice_id', 'referrer_id', 'referee_id', 'level',
        'amount', 'currency_id', 'business_amount', 'business_currency_id',
        'exchange_rate', 'exchange_rate_date', 'commission_rate', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'commission_rate' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referee_id');
    }

    public function currencyModel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function businessCurrencyModel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'business_currency_id');
    }
}
