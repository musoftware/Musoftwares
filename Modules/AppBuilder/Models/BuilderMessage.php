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
            default => $this->content.$this->linkNotes(),
        };
    }

    /** What each link the customer shared showed when it was opened for Claude. */
    private function linkNotes(): string
    {
        return collect($this->payload['looks'] ?? [])->map(function (array $look): string {
            if (! in_array($look['status'] ?? '', ['seen', 'read'], true)) {
                return "\n\n[The link {$look['url']} could not be opened: ".($look['reason'] ?? 'no answer in time').'. Ask for screenshots if you need to see it.]';
            }
            $how = $look['status'] === 'seen' ? 'in a real browser; screenshots of the whole page are attached, top to bottom' : 'as plain HTML only, so parts drawn by scripts are missing';

            return "\n\n[The link {$look['url']} was opened {$how}.\nTitle: {$look['title']}\nDescription: {$look['description']}\nVisible text: {$look['text']}]";
        })->implode('');
    }

    /** Claude only knows customer and assistant turns; a team line is read as a note on the customer's side. */
    public function modelRole(): string
    {
        return $this->role === self::ASSISTANT ? self::ASSISTANT : self::USER;
    }
}
