<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

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
