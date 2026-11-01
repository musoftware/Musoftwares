<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyPointTransaction extends Model
{
    use HasFactory, ScopesToLegalWindow, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'points' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
