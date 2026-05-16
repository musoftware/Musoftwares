<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends TenantModel
{
    protected $table = 'withdrawal_requests';
    protected $fillable = [
        'tenant_id', 'client_id', 'payment_method_id', 'amount', 'currency_code',
        'status', 'admin_notes', 'reference', 'proof_path'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
