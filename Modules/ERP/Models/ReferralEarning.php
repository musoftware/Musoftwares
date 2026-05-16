<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEarning extends TenantModel
{
    protected $table = 'client_referral_earnings';

    protected $fillable = [
        'tenant_id', 'client_id', 'invoice_id', 'referrer_id', 'referee_id', 'level',
        'amount', 'amount_currency', 'business_amount', 'business_currency',
        'exchange_rate', 'exchange_rate_date', 'commission_rate', 'description', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'commission_rate' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

