<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AdminNote extends Model
{
    protected $fillable = [
        'noteable_id',
        'noteable_type',
        'author_id',
        'visibility',
        'type',
        'content',
        'is_pinned',
        'risk_level',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
