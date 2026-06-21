<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

/**
 * ERP Todo Item — a single work item inside a task board.
 * Recovered from old project: Todo model.
 *
 * Features recovered:
 * - priority + priority_color (visual grouping)
 * - sort_index (drag-and-drop ordering)
 * - paused/resume workflow
 * - cost + is_paid (billable hours tracking)
 * - start_at + end_at (scheduled time slots)
 * - tags (JSON array)
 * - parent_id (nested sub-items)
 * - is_paid guard: paid items cannot be deleted
 */
class ERPTodoItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_todo_items';

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $tenantId = app()->bound('currentTenant') ? app('currentTenant')->id : null;
            if (!$tenantId && auth()->check()) {
                $tenantId = auth()->user()->tenant_id;
            }
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    protected $fillable = [
        'tenant_id', 'task_id', 'title', 'description',
        'completed', 'completed_at',
        'priority', 'priority_color',
        'sort_index', 'paused',
        'cost', 'cost_currency', 'is_paid',
        'start_at', 'end_at',
        'tags', 'parent_id', 'assigned_to', 'assigned_team_member_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'paused'    => 'boolean',
        'is_paid'   => 'boolean',
        'tags'      => 'array',
        'cost'      => 'decimal:2',
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(ERPTask::class, 'task_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_index');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigneeTeamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'assigned_team_member_id');
    }

    // ── Business Logic ───────────────────────────────────────────────

    /**
     * Pause a todo item.
     * Recovered from old project: Todo::pause()
     */
    public function pause(): void
    {
        $this->update(['paused' => true]);
    }

    /**
     * Resume a paused todo item.
     * Recovered from old project: Todo::resume()
     */
    public function resume(): void
    {
        $this->update(['paused' => false]);
    }

    /**
     * Mark as complete — sets sort_index to push to bottom.
     * Recovered from old project: TodoController::complete()
     */
    public function markComplete(): void
    {
        $this->update([
            'completed'    => true,
            'completed_at' => now(),
            'sort_index'   => 999999,
        ]);
    }

    /**
     * Mark as incomplete — resets sort index.
     * Recovered from old project: TodoController::complete() (toggle)
     */
    public function markIncomplete(): void
    {
        $this->update([
            'completed'    => false,
            'completed_at' => null,
            'sort_index'   => 0,
        ]);
    }

    /**
     * Guard: paid items cannot be deleted.
     * Recovered from old project: TodoController::delete()
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_paid;
    }
}
