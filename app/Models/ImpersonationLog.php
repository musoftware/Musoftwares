<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonated()
    {
        return $this->belongsTo(User::class, 'impersonated_id');
    }
}
