<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImpersonationLog extends Model
{
    use ScopesToLegalWindow, SoftDeletes;

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
