<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNote extends Model
{
    protected $guarded = [];

    public function noteable()
    {
        return $this->morphTo();
    }
}
