<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One line of the chat. `content` is what the customer sees.
 * `payload` keeps Claude's whole JSON answer, so the next turn shows Claude its own earlier quotes.
 */
class BuilderMessage extends Model
{
    use SoftDeletes;

    public const USER = 'user';

    public const ASSISTANT = 'assistant';

    /** A line from the Musoftware team, written on the admin page. */
    public const TEAM = 'team';

    /** A screenshot the build sent when a big part of the program worked. */
    public const PROGRESS = 'progress';

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BuilderAttachment::class, 'message_id')->orderBy('id');
    }

    /** The text Claude reads for this line on the next turn: its own earlier JSON answers, everything else as written. */
    public function forModel(): string
    {
        if (isset($this->payload['reply'])) {
            return (string) json_encode($this->payload, JSON_UNESCAPED_UNICODE);
        }

        return match ($this->role) {
            self::TEAM => '[Message from the Musoftware team] '.$this->content,
            self::PROGRESS => '[The build showed the customer a screenshot of finished work] '.$this->content,
            default => $this->content,
        };
    }

    /** Claude only knows customer and assistant turns; a team line is read as a note on the customer's side. */
    public function modelRole(): string
    {
        return $this->role === self::ASSISTANT ? self::ASSISTANT : self::USER;
    }
}
