<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'todo' => Todo::class,
        'file' => ProjectFile::class,
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

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REVISION = 'revision_requested';

    public static function validApprovalStatuses(): array
    {
        return [
            self::APPROVAL_PENDING,
            self::APPROVAL_APPROVED,
            self::APPROVAL_REVISION,
        ];
    }

    protected $casts = [
        'pos_x' => 'integer',
        'pos_y' => 'integer',
        'sort' => 'integer',
        'category_id' => 'integer',
        'invoice_id' => 'integer',
        'published_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectBoardCategory::class, 'category_id');
    }
}
