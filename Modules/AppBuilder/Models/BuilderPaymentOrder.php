<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One payment link and the exact amount it asks for; paid_at is set once, by the first matching webhook. */
class BuilderPaymentOrder extends Model
{
    use SoftDeletes;

    protected $fillable = ['project_id', 'order_id', 'invoice', 'amount', 'currency', 'gateway_amount', 'gateway_currency', 'transaction_id', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }
}
