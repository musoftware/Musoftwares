<?php

namespace Modules\PasswordSync\app\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordItem extends Model
{
    protected $fillable = [
        'password_vault_id',
        'remote_id',
        'encrypted_data',
    ];

    public function vault()
    {
        return $this->belongsTo(PasswordVault::class, 'password_vault_id');
    }
}
