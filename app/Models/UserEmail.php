<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEmail extends Model
{
    public const SOURCE_ADMIN = 'admin';

    public const SOURCE_MERGE = 'merge';

    public const SOURCE_SELF = 'self';

    protected $fillable = [
        'user_id',
        'email',
        'verified_at',
        'source',
        'added_by_user_id',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
