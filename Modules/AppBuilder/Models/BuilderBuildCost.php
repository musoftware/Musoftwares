<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One delivered build: its estimate, what it really cost, and the work factor that came out of the two. */
class BuilderBuildCost extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'estimated_tokens' => 'integer',
        'cost_usd' => 'decimal:4',
        'work_factor' => 'decimal:3',
    ];
}
