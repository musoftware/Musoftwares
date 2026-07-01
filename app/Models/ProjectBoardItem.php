<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoardItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Canonical short-alias → morph class map for board cards.
     * Single source of truth shared by the form request and the calendar/board controllers.
     */
    public const MORPH_MAP = [
        'note' => ProjectBoardNote::class,
        'task' => Task::class,
        'report' => ProjectReport::class,
    ];

    public static function morphClassFor(string $type): string
    {
        return self::MORPH_MAP[$type] ?? ProjectBoardNote::class;
    }

    /**
     * @return string[]
     */
    public static function validTypeKeys(): array
    {
        return array_keys(self::MORPH_MAP);
    }

    protected $casts = [
        'pos_x' => 'integer',
        'pos_y' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function itemable()
    {
        return $this->morphTo();
    }
}
