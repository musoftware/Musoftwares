<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One walk through a site the customer wants copied: the worker opens its pages, logs in when it can, takes pictures. */
class BuilderSiteTour extends Model
{
    use SoftDeletes;

    public const PENDING = 'pending';

    public const TAKEN = 'taken';

    public const DONE = 'done';

    public const FAILED = 'failed';

    protected $fillable = ['project_id', 'url', 'status', 'logged_in', 'pages_seen', 'reason', 'taken_at', 'finished_at'];

    protected $casts = ['logged_in' => 'boolean', 'taken_at' => 'datetime', 'finished_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::PENDING, self::TAKEN], true);
    }
}
