<?php

namespace Modules\PasswordSync\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordVault extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'salt',
        'encrypted_data',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PasswordItem::class, 'password_vault_id');
    }
}
