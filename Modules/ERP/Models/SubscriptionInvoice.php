<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_reference',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ModulePlan::class, 'plan_id');
    }
}
