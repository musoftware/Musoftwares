<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    protected $fillable = ['user_id', 'type', 'points', 'reference_type', 'reference_id'];

    // immutable
    public $timestamps = true;

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
