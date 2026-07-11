<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoardPreference extends Model
{
    protected $table = 'project_board_preferences';

    protected $guarded = ['id'];

    public const VIEW_CARDS = 'cards';

    public const VIEW_GRID = 'grid';

    public const VIEW_LINES = 'lines';

    public const VIEW_TABLE = 'table';

    public const SORT_MANUAL = 'manual';

    public const SORT_TITLE = 'title';

    public const SORT_TYPE = 'type';

    public const SORT_LANE = 'lane';

    public const SORT_PRIORITY = 'priority';

    public const SORT_CATEGORY = 'category';

    public const DIR_ASC = 'asc';

    public const DIR_DESC = 'desc';

    public const VIEWS = [
        self::VIEW_CARDS,
        self::VIEW_GRID,
        self::VIEW_LINES,
        self::VIEW_TABLE,
    ];

    public const SORTS = [
        self::SORT_MANUAL,
        self::SORT_TITLE,
        self::SORT_TYPE,
        self::SORT_LANE,
        self::SORT_PRIORITY,
        self::SORT_CATEGORY,
    ];

    public const DIRS = [self::DIR_ASC, self::DIR_DESC];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
