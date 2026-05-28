<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointPackage extends Model
{
    protected $guarded = [];

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
