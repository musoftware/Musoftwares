<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * ERP Task Board — a container for todo items, scoped per tenant.
 * Recovered from old project: Task model.
 * Links to TenantClient and/or Project. Created by admin, visible to client.
 */
class ERPTask extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'erp_tasks';

    protected $fillable = [
        'tenant_id', 'task_name', 'task_description',
        'client_id', 'project_id', 'status', 'archived',
        'priority', 'created_by', 'assigned_to', 'assigned_team_member_id',
        'due_date', 'completed_at',
    ];

    protected $casts = [
        'archived'     => 'boolean',
        'due_date'     => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUSES   = ['open', 'in_progress', 'review', 'completed', 'archived'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    // ── Relationships ────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(ERPTodoItem::class, 'task_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigneeTeamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'assigned_team_member_id');
    }

    public function comments()
    {
        return $this->morphMany(\App\Models\Comment::class, 'commentable');
    }

    // ── Business Logic ───────────────────────────────────────────────

    /**
     * Completion percentage based on todo items.
     * Recovered from old project: Task::completed_percentage()
     */
    public function completionPercentage(): float
    {
        $total     = $this->items()->count();
        $completed = $this->items()->where('completed', true)->count();
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;
    }

    /**
     * Whether all items are completed.
     * Recovered from old project: Task::completed()
     */
    public function isFullyCompleted(): bool
    {
        $total = $this->items()->count();
        return $total > 0 && $this->items()->where('completed', false)->count() === 0;
    }

    /**
     * Count of incomplete active items.
     * Recovered from old project: shapeTask()
     */
    public function pendingCount(): int
    {
        return $this->items()->where('completed', false)->where('paused', false)->count();
    }
}
