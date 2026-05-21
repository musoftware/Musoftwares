<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commenter_id',
        'commenter_type',
        'guest_name',
        'guest_email',
        'commentable_type',
        'commentable_id',
        'comment',
        'approved',
        'child_id',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];

    /**
     * Get the commenter model.
     */
    public function commenter()
    {
        return $this->morphTo('commenter');
    }

    /**
     * Get the user who made the comment (when commenter is a User).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'commenter_id');
    }

    /**
     * Get the commentable model.
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Get the replies to this comment.
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'child_id');
    }

    /**
     * Get the parent comment.
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'child_id');
    }

    /**
     * Scope to get approved comments only.
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    /**
     * Scope to get top-level comments (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('child_id');
    }
}
