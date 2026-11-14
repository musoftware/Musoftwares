<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\Dialect;
use Throwable;

/**
 * The phases of a big build, as the customer reads them under the progress bar. The engineer keeps the plan in
 * /work/.cb/progress.md as "- [x] title" lines, in engineer English; the titles are retold once in plain words in the
 * customer's language, and only the ticks change after that.
 */
class BuildPhases
{
    private const MAX_PHASES = 15;

    private const LINE = '/^\s*[-*]\s*\[( |x|X)\]\s*(?:\d+[.)]?\s*)?(.+?)\s*$/m';

    private const RULES = <<<'TEXT'
You are Musoftware's contact person. Below are the phases of the program the engineer is building for this customer,
one per line, written for engineers. Rewrite each one as a short line the customer understands (what they will be
able to see or do), no technical words. Keep the same number of lines in the same order, one phase per line, no
numbers, no bullets, no Markdown.

Put the lines in `reply`. `quote.ready` is false, `needs_team` false, `release_now` false, `replaces_request` 0, and
every list is empty.
TEXT;

    public function __construct(private readonly ChatModel $model) {}

    /** Reads the engineer's plan; the customer's titles are kept while the phases stay the same. */
    public function update(BuilderProject $project, string $plan): void
    {
        $phases = $this->parse($plan);
        if ($phases === []) {
            return;
        }
        $hash = hash('sha256', implode("\n", array_column($phases, 'title')));
        $known = $project->progress_phases ?? [];
        $titles = ($known['hash'] ?? null) === $hash ? array_column($known['items'] ?? [], 'title') : $this->retell($project, array_column($phases, 'title'));
        if ($titles === null) {
            return;
        }

        $items = array_map(fn (array $phase, string $title) => ['title' => $title, 'done' => $phase['done']], $phases, $titles);
        $project->update(['progress_phases' => ['hash' => $hash, 'items' => $items]]);
        $this->tellFinished($project, $known['items'] ?? [], $items);
    }

    /** A phase that was just ticked reaches the customer (Telegram, browser): the first plan only sets the list. */
    private function tellFinished(BuilderProject $project, array $before, array $after): void
    {
        $doneBefore = count(array_filter(array_column($before, 'done')));
        $done = array_values(array_filter($after, fn (array $item) => $item['done']));
        if ($before === [] || count($done) <= $doneBefore) {
            return;
        }

        app(BuilderPush::class)->notify($project, 'phase', ['phase' => end($done)['title'], 'done' => (string) count($done), 'all' => (string) count($after)]);
    }

    /** @return list<array{title: string, done: bool}> */
    public function parse(string $plan): array
    {
        preg_match_all(self::LINE, $plan, $matches, PREG_SET_ORDER);

        return array_slice(array_map(fn (array $m) => ['title' => mb_substr($m[2], 0, 300), 'done' => strtolower($m[1]) === 'x'], $matches), 0, self::MAX_PHASES);
    }

    /** @param list<string> $titles @return list<string>|null null when the retelling did not come back line for line */
    private function retell(BuilderProject $project, array $titles): ?array
    {
        if (! config('builder.retell_delivery')) {
            return $titles;
        }
        try {
            $turn = $this->model->reply($this->system($project), [['role' => 'user', 'content' => implode("\n", $titles)]]);
        } catch (Throwable $error) {
            Log::warning('App builder: the build phases were not retold', ['project_id' => $project->id, 'error' => $error->getMessage()]);

            return null;
        }
        $project->increment('chat_input_tokens', $turn->inputTokens);
        $project->increment('chat_output_tokens', $turn->outputTokens);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $turn->reply)), fn (string $line) => $line !== ''));

        return count($lines) === count($titles) ? array_map(fn (string $line) => mb_substr($line, 0, 300), $lines) : null;
    }

    private function system(BuilderProject $project): string
    {
        $language = $project->customerLanguage() === 'ar'
            ? 'Write in Arabic the way the customer speaks it, and stay in it: '.$project->spokenArabic().'.'
            : 'Write in English.';

        return self::RULES."\n\nThe program: ".($project->title ?: 'their program').".\n".$language;
    }
}
