<?php

namespace App\Models\Tools;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_id',
        'version',
        'config',
        'change_description',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    /**
     * Get the strategy that owns this version.
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }
}
