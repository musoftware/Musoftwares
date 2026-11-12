<?php

namespace Modules\AppBuilder\Services\Chat;

use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;
use Throwable;

/**
 * Two people work for the customer: the engineer who builds, and the contact who talks with them. The engineer's
 * delivery note is written for engineers (tests, scripts, files), so the contact retells it in plain words in the
 * customer's language before the customer reads it. The engineer's own note goes to the owner. When the retelling
 * fails, the engineer's note is shown as it is: a delivery never waits for it.
 */
class CustomerVoice
{
    private const MAX_NOTE_CHARS = 12000;

    private const RULES = <<<'TEXT'
You are Musoftware's contact person. The engineer just finished work on this customer's program and wrote the note below
for engineers. Retell it for the customer, who is not technical, as one chat message from you.

- Say what changed that they can see and use, and where (which screen, what happens when they tap what).
- Leave out tests, scripts, files, commands, database names, versions and anything only an engineer cares about.
- Never ask the customer to run, deploy, migrate, upload or configure anything: Musoftware does that.
- If something still needs the customer (a decision, a login, a picture), say it in one plain line.
- Short: a few lines or a short list. No headings, no Markdown symbols.
- Do not invent anything the note does not say.

Put the message in `reply`. `quote.ready` is false, `needs_team` false, `release_now` false, `replaces_request` 0,
and every list is empty.
TEXT;

    public function __construct(private readonly ChatModel $model) {}

    public function retell(BuilderProject $project, string $engineerNote): string
    {
        if (! config('builder.retell_delivery') || trim($engineerNote) === '') {
            return $engineerNote;
        }

        try {
            $turn = $this->model->reply($this->system($project), [['role' => 'user', 'content' => mb_substr($engineerNote, 0, self::MAX_NOTE_CHARS)]]);
        } catch (Throwable $error) {
            Log::warning('App builder: the delivery note was shown as the engineer wrote it', ['project_id' => $project->id, 'error' => $error->getMessage()]);

            return $engineerNote;
        }
        $project->increment('chat_input_tokens', $turn->inputTokens);
        $project->increment('chat_output_tokens', $turn->outputTokens);

        return $turn->reply !== '' ? $turn->reply : $engineerNote;
    }

    private function system(BuilderProject $project): string
    {
        $language = $project->customerLanguage() === 'ar'
            ? 'Write in Arabic the way people speak it in the customer\'s country: '.Dialect::arabicFor($project->country).'.'
            : 'Write in English.';

        return self::RULES."\n\nThe program: ".($project->title ?: 'their program').".\n".$language;
    }
}
