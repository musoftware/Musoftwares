<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceCostLine extends Model
{
    use SoftDeletes;

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
