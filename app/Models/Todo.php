<?php

namespace App\Models;

use App\Trait\ChatModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Todo extends Model
{
    use ChatModelTrait;
    use HasFactory;
    use SoftDeletes;

    public function ChatName()
    {
        return Todo::class;
    }

    public $fillable = [
        'title',
        'description',
        'user_id',
        'project_id',
        'task_id',
        'parent_id',
        'completed',
        'inDate',
        'priority',
        'priorityColor',
        'tags',
        'paused',
        'start_at',
        'end_at',
        'completed_at',
        'sort_index',
        'invoice_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'paused' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'completed_at' => 'datetime',
        'tags' => 'array',
    ];

    public static function parseItems($items)
    {
        foreach ($items as &$item) {
            $item = static::parseTags($item);
        }

        return $items;
    }

    public static function parseImages($images): array
    {
        $images_urls = [];
        foreach ($images as $image) {
            $images_urls[] = [
                'path' => Storage::disk('todo')->url($image['filename']),
                'id' => $image['id'],
            ];
        }

        return $images_urls;
    }

    public static function parseTags($item)
    {
        $item['images_urls'] = Todo::parseImages($item->task_todo_images()->get());
        $item['audio'] = $item->task_todo_audios()->get();
        $item['client_name'] = $item->user->name;
        $item['task_name'] = $item->task->task_name;

        if (isset($item['completed'])) {
            $item['completed'] = ($item['completed'] === '1') || ($item['completed'] === 1);
        }

        return $item;
    }

    public function task_todo_images()
    {
        return $this->hasMany(TodoImage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(TodoChecklistItem::class, 'todo_id');
    }

    public function pause()
    {
        $this->paused = true;
        $this->save();
    }

    public function resume()
    {
        $this->paused = false;
        $this->save();
    }

    public function task_todo_audios()
    {
        return $this->hasMany(TodoAudio::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope: incomplete and not paused. Pure status filter — does NOT consider
     * whether the related task exists or is archived. Use this whenever the
     * question is "is this todo still actionable?" regardless of its board.
     *
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(function ($q) {
                $q->where('completed', false)->orWhereNull('completed');
            })
            ->where(function ($q) {
                $q->where('paused', false)->orWhereNull('paused');
            });
    }

    /**
     * Scope: active todos whose parent task (if any) is not archived. Use this
     * to drive the admin "Active Tasks" list. Counted both for the list AND
     * the stat cards so they can never drift.
     *
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function scopeForActiveList(Builder $query): Builder
    {
        return $query->active()->whereHas('task', function ($q) {
            $q->where('archived', false);
        });
    }

    public function checklist_items()
    {
        return $this->hasMany(TodoChecklistItem::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ProjectComment::class, 'commentable');
    }

    /**
     * True if any incomplete scheduled todo (any client) overlaps [start, end).
     */
    public static function focusCalendarSlotTaken(Carbon $start, Carbon $end, ?int $exceptTodoId = null): bool
    {
        return static::query()
            ->where('completed', false)
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->when($exceptTodoId !== null, fn ($q) => $q->where('id', '!=', $exceptTodoId))
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)
                    ->where('end_at', '>', $start);
            })
            ->exists();
    }
}
