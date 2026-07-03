<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = [
        'project_id',
        'author_id',
        'guest_name',
        'guest_email',
        'commentable_type',
        'commentable_id',
        'body',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Display name shown next to a comment. Falls back to the guest name when no user is attached.
     */
    public function displayName(): string
    {
        if ($this->author?->name) {
            return $this->author->name;
        }

        return (string) ($this->guest_name ?: __('general.guest') ?: 'Guest');
    }

    /**
     * True when this comment was posted by an unauthenticated visitor.
     */
    public function isGuest(): bool
    {
        return $this->author_id === null;
    }
}
