<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function noteable()
    {
        return $this->morphTo();
    }
}
