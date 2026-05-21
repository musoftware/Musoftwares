<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceCostLine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creditUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credit_user_id');
    }

    public function isProcessed(): bool
    {
        if ($this->line_type === 'direct') {
            return $this->cost_transaction_id !== null;
        }

        return $this->earned_transaction_id !== null;
    }
}
