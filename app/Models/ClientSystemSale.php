<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A sale the client's system reported to us. Only status "paid" counts toward the share of sales. */
class ClientSystemSale extends Model
{
    use SoftDeletes;

    public const STATUSES = ['paid', 'refunded'];

    protected $fillable = ['client_system_id', 'client_system_bill_id', 'external_id', 'amount', 'status', 'sold_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(ClientSystem::class, 'client_system_id');
    }
}
