<?php

namespace Modules\PasswordSync\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordItem extends Model
{
    use SoftDeletes;

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
