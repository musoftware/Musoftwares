<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminNote extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'rotated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_revealed_at' => 'datetime',
    ];

    public function noteable()
    {
        return $this->morphTo();
    }
}
