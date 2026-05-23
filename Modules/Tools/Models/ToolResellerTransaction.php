<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ToolResellerTransaction extends Model
{
    protected $fillable = [
        'reseller_id', 'user_id', 'type', 'amount', 'balance_after',
        'currency_id', 'description', 'reference',
    ];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
    ];

    // top_up | charge | manual_credit | manual_debit | suspension
    public static array $types = ['top_up', 'charge', 'manual_credit', 'manual_debit', 'suspension'];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(ToolReseller::class, 'reseller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }
}
