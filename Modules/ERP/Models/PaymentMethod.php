<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'erp_payment_methods';

    protected $fillable = [
        'tenant_id', 'client_id', 'type', 'is_default', 'status', 'rejection_note',
        'reviewed_by', 'reviewed_at', 'bank_name', 'account_holder_name',
        'account_number', 'iban', 'swift_code', 'bank_country', 'bank_currency_id',
        'branch_name', 'notes'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function bankCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'bank_currency_id');
    }
}
