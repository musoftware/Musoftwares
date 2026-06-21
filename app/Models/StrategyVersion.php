<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyVersion extends Model
{
    use SoftDeletes, HasFactory;

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
