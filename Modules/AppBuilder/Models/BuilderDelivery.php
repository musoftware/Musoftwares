<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One delivered build: whether a person had to step in, its real cost and what the customer paid for it. */
class BuilderDelivery extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'hands_off' => 'boolean',
        'cost_usd' => 'decimal:4',
        'price_usd' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }
}
