<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserEmbedKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'key',
        'name',
        'allowed_domains',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_domains' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
