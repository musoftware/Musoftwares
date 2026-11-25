<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AppBuilder\Services\LinkLooks;

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

    /** Pictures and notes from walking through a site the customer wants copied (SiteTours). */
    public const TOUR = 'tour';

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array'];

    /** The chat's answers and the team's lines also reach the customer's Telegram while they talk to this project there. */
    protected static function booted(): void
    {
        static::created(function (self $message): void {
            if (in_array($message->role, [self::ASSISTANT, self::TEAM], true)) {
                \Illuminate\Support\Facades\DB::afterCommit(fn () => app(\Modules\AppBuilder\Services\TelegramChat::class)->forward($message));
            }
        });
    }

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
            // the team's line (a notice or the owner's own words) is already on the customer's page
            self::TEAM => '[The Musoftware team wrote this to the customer; the customer did not write it. Do not reply to it or thank anyone for it, answer only what the customer writes] '.$this->content,
            self::PROGRESS => '[The build showed the customer a screenshot of finished work] '.$this->content,
            self::TOUR => $this->tourNotes(),
            default => $this->content.$this->linkNotes(),
        };
    }

    /** What the walk through a site found, for the chat model and for the build; the customer saw only the line and the pictures. */
    public function tourNotes(): string
    {
        $tour = (array) ($this->payload['tour'] ?? []);
        $how = ! empty($tour['logged_in']) ? 'logged in with the customer\'s test account' : 'as a visitor, not logged in';

        return '[The engineering computer walked through '.($tour['url'] ?? 'the site')." in a real browser, {$how}, and took one picture of each page (attached, in the order visited). "
            .'The customer did not write this; they see the pictures. What it found on each page:]'
            ."\n\n".($tour['report'] ?? '');
    }

    /** What each link or file the customer shared showed when it was looked at for Claude. */
    private function linkNotes(): string
    {
        return collect($this->payload['looks'] ?? [])
            ->map(fn (array $item) => ($item['kind'] ?? 'link') === 'file' ? $this->fileNote($item) : $this->linkNote($item))
            ->implode('');
    }

    private function linkNote(array $look): string
    {
        if (! empty($look['over_limit'])) {
            return "\n\n[The link {$look['url']} was not opened: the customer already sent ".LinkLooks::LINKS_PER_DAY.' links today, the most in one day. Tell them kindly, in one line, to send it again tomorrow. Do not ask for screenshots of it and do not guess what is on it.]';
        }
        if (! in_array($look['status'] ?? '', ['seen', 'read'], true)) {
            return "\n\n[The link {$look['url']} could not be opened: ".($look['reason'] ?? 'no answer in time').'. Ask for screenshots if you need to see it.]';
        }
        $how = $look['status'] === 'seen' ? 'in a real browser; screenshots of the whole page are attached, top to bottom' : 'as plain HTML only, so parts drawn by scripts are missing';

        return "\n\n[The link {$look['url']} was opened {$how}.\nTitle: {$look['title']}\nDescription: {$look['description']}\nVisible text: {$look['text']}]";
    }

    private function fileNote(array $item): string
    {
        $text = $this->attachments->firstWhere('id', $item['attachment_id'])?->extracted_text;
        if (($item['status'] ?? '') !== 'seen' || ! $text) {
            return "\n\n[The file {$item['name']} could not be read: ".($item['reason'] ?? 'no answer in time').'. Tell the customer plainly and ask them to send it again in a little while.]';
        }

        return "\n\n[The file {$item['name']} was read. Every sheet with its cells, and every formula with the value it gives:\n{$text}]";
    }

    /** Claude only knows customer and assistant turns; a team line is read as a note on the customer's side. */
    public function modelRole(): string
    {
        return $this->role === self::ASSISTANT ? self::ASSISTANT : self::USER;
    }
}
