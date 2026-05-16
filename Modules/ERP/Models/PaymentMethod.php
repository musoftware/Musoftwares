<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends TenantAwareModel
{
    protected $fillable = [
        'tenant_id', 'client_id', 'bank_name', 'account_number', 'account_name',
        'swift_code', 'status', 'is_default'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
