<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectReport extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'notify_client' => 'boolean',
    ];

    public const TYPES = [
        'progress' => 'Progress Update',
        'milestone' => 'Milestone',
        'issue' => 'Issue / Risk',
        'summary' => 'Weekly Summary',
        'financial' => 'Financial',
        'final' => 'Final Report',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $report) {
            $report->boardItems()->delete();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments()
    {
        return $this->morphMany(ProjectComment::class, 'commentable');
    }

    public function boardItems()
    {
        return $this->morphMany(ProjectBoardItem::class, 'itemable');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lessThanOrEqualTo(now());
    }
}
