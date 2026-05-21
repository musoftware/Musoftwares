<?php

namespace App\Models;

use App\Trait\ChatModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Todo extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ChatModelTrait;

    public function ChatName()
    {
        return Todo::class;
    }
    public $fillable = [
        'title',
        'description',
        'user_id',
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
        'cost',
        'currency_id',
        'is_paid',
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
        $images_urls = array();
        foreach ($images as $image) {
            $images_urls[] = array(
                'path' => Storage::disk('todo')->url($image['filename']),
                'id' => $image['id']
            );
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

    public function task()
    {
        return $this->belongsTo(Task::class);
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
