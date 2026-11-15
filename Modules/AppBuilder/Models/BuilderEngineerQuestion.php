<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A question the chat could not answer for sure, sent to the programmer (the worker), and the answer it got. */
class BuilderEngineerQuestion extends Model
{
    use SoftDeletes;

    public const PENDING = 'pending';

    public const TAKEN = 'taken';

    public const ANSWERED = 'answered';

    protected $fillable = ['project_id', 'question', 'answer', 'status', 'taken_at', 'answered_at'];

    protected $casts = ['taken_at' => 'datetime', 'answered_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }
}
