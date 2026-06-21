<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIntegration extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'scopes' => 'array',
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
