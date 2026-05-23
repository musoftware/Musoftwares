<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantClient;

class Withdrawal extends TenantModel
{
    protected $table = 'erp_withdrawals';

    protected $fillable = [
        'tenant_id', 'client_id', 'payment_method_id', 'amount', 'currency_code',
        'status', 'admin_notes', 'reference', 'proof_path'
    ];

    public function client(): BelongsTo
    {
        // M8 fix: use TenantClient — not Client (alias with different methods)
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
