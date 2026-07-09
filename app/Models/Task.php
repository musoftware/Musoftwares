<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $fillable = ['task_name', 'task_description', 'project_id', 'user_id', 'swimlane_id', 'assigned_to_admin', 'due_date', 'priority', 'archived'];

    protected static function booted(): void
    {
        static::deleting(function (Task $task) {
            // Remove any board placements that reference this task (polymorphic cleanup).
            ProjectBoardItem::where('itemable_type', static::class)
                ->where('itemable_id', $task->id)
                ->delete();
        });
    }

    public function completed_percentage()
    {
        return round(
            $this->task_todo_items()->count() > 0
                ? ($this->task_todo_items()
                    ->where('completed', true)
                    ->count() *
                    100) /
                $this->task_todo_items()->count()
                : 0,
            2,
        );
    }

    /**
     * Progress by time: task due-date window, or else the current / next booked todo slot (start_at → end_at).
     * Returns null only when there is no due date and no scheduled slots on incomplete todos.
     */
    public function time_based_progress_percentage(): ?float
    {
        if ($this->due_date !== null && $this->due_date !== '') {
            return $this->dueDateTimelineProgress();
        }

        return $this->scheduledSessionProgress();
    }

    /**
     * Calendar line under the roadmap card: task due date, or focus session end (Cairo), or "no deadline".
     */
    public function roadmapCalendarFootnote(): string
    {
        if ($this->due_date !== null && $this->due_date !== '') {
            return Carbon::parse($this->due_date)->format('M j');
        }

        $tz = 'Africa/Cairo';
        $todos = $this->focusScheduleTodos();
        if ($todos->isEmpty()) {
            return __('common.no_deadline');
        }

        $now = Carbon::now($tz);

        foreach ($todos as $todo) {
            $s = Carbon::parse($todo->start_at, $tz);
            $e = Carbon::parse($todo->end_at, $tz);
            if ($e->lte($s)) {
                continue;
            }
            if ($now->between($s, $e, true)) {
                return __('client.dashboard.session_ends_at', ['time' => $e->format('M j, g:i A')]);
            }
        }

        foreach ($todos as $todo) {
            $s = Carbon::parse($todo->start_at, $tz);
            $e = Carbon::parse($todo->end_at, $tz);
            if ($e->lte($s)) {
                continue;
            }
            if ($s->gt($now)) {
                return __('client.dashboard.session_ends_at', ['time' => $e->format('M j, g:i A')]);
            }
        }

        $latestEnd = null;
        foreach ($todos as $todo) {
            $s = Carbon::parse($todo->start_at, $tz);
            $e = Carbon::parse($todo->end_at, $tz);
            if ($e->lte($s)) {
                continue;
            }
            if ($latestEnd === null || $e->gt($latestEnd)) {
                $latestEnd = $e;
            }
        }

        if ($latestEnd !== null) {
            return __('client.dashboard.session_ended_at', ['time' => $latestEnd->format('M j, g:i A')]);
        }

        return __('common.no_deadline');
    }

    /**
     * Incomplete focus todos with a booked window (same set as session progress).
     *
     * @return Collection<int, Todo>
     */
    protected function focusScheduleTodos(): Collection
    {
        return $this->task_todo_items()
            ->where(function ($q) {
                $q->where('completed', false)->orWhereNull('completed');
            })
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->orderBy('start_at')
            ->get(['start_at', 'end_at']);
    }

    /**
     * Elapsed fraction from created_at through end of due_date day.
     */
    protected function dueDateTimelineProgress(): float
    {
        $start = $this->created_at ? Carbon::parse($this->created_at) : Carbon::now();
        $end = Carbon::parse($this->due_date)->endOfDay();

        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();
        $nowTs = Carbon::now()->getTimestamp();

        $total = $endTs - $startTs;
        if ($total <= 0) {
            return 100.0;
        }

        $elapsed = $nowTs - $startTs;
        $ratio = $elapsed / $total;

        return round(min(100, max(0, $ratio * 100)), 2);
    }

    /**
     * Progress through an active booking window, or 0% before the next slot, or 100% after all slots ended.
     *
     * Focus bookings use Africa/Cairo wall times in the UI and DB (see ClientTodoFocus); app timezone is UTC,
     * so naive datetimes must be parsed in Cairo or "now" will miss the active slot.
     */
    protected function scheduledSessionProgress(): ?float
    {
        $tz = 'Africa/Cairo';
        $now = Carbon::now($tz);

        $todos = $this->focusScheduleTodos();

        if ($todos->isEmpty()) {
            return null;
        }

        foreach ($todos as $todo) {
            $s = Carbon::parse($todo->start_at, $tz);
            $e = Carbon::parse($todo->end_at, $tz);
            if ($e->lte($s)) {
                continue;
            }
            if ($now->between($s, $e, true)) {
                $total = $e->getTimestamp() - $s->getTimestamp();
                $elapsed = $now->getTimestamp() - $s->getTimestamp();

                return round(min(100, max(0, ($elapsed / $total) * 100)), 2);
            }
        }

        foreach ($todos as $todo) {
            if (Carbon::parse($todo->start_at, $tz)->gt($now)) {
                return 0.0;
            }
        }

        return 100.0;
    }

    public function task_todo_items()
    {
        return $this->hasMany(Todo::class);
    }

    /* public function todo_swimlane()
    {
        return $this->belongsTo(TodoSwimlane::class, 'swimlane_id');
    } */

    /* public function todo_swimlane_team()
    {
        return optional($this->todo_swimlane)->title ?? 'None';
    } */

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'tasks_share');
    }

    public function completed()
    {
        foreach ($this->task_todo_items as $todo) {
            if (! $todo->completed) {
                return false;
            }
        }

        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function comments()
    {
        return $this->morphMany(ProjectComment::class, 'commentable');
    }
}
