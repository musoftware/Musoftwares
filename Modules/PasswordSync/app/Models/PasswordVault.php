<?php

namespace Modules\PasswordSync\app\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordVault extends Model
{
    protected $fillable = [
        'user_id',
        'encrypted_data',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
