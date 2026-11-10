<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use RuntimeException;

/**
 * Links the customer shares are opened before the chat answers, like a real programmer would look at an
 * example site. The worker opens each one in a real browser in Docker and sends a full-page screenshot (shown
 * in the chat) with sharp pieces of it and the page text (read by Claude). When no worker is there, or it is
 * too slow, this server reads what it can of the page itself.
 */
class LinkLooks
{
    public const PENDING = 'pending';

    public const TAKEN = 'taken';

    public const SEEN = 'seen';

    public const READ = 'read';

    public const FAILED = 'failed';

    private const MAX_LINKS = 3;

    private const HEARTBEAT = 'looks';

    /** The worker asks for links every few seconds; silent longer than this, it is not there. */
    private const WORKER_FRESH_SECONDS = 40;

    /** The longest the customer waits for the browser before the chat answers anyway. */
    private const WAIT_SECONDS = 50;

    private const FINISHED = [self::SEEN, self::READ, self::FAILED];

    public function __construct(
        private readonly PageReader $reader,
        private readonly AttachmentStore $attachments,
        private readonly WorkerHeartbeat $heartbeat,
    ) {}

    /** @return list<string> */
    public function linksIn(string $text): array
    {
        preg_match_all('~https?://[^\s<>"\'\x{0600}-\x{06FF}]+~u', $text, $matches);

        return collect($matches[0])
            ->map(fn (string $url) => rtrim($url, '.,;:!?)]}،؟'))
            ->filter(fn (string $url) => filter_var($url, FILTER_VALIDATE_URL) !== false)
            ->unique()->take(self::MAX_LINKS)->values()->all();
    }

    public function workerIsWatching(): bool
    {
        return $this->heartbeat->seenWithin(self::HEARTBEAT, self::WORKER_FRESH_SECONDS);
    }

    /** The worker will open the links; the chat answers once it has seen them. */
    public function askWorker(BuilderMessage $question, array $links): void
    {
        $this->saveLooks($question, array_map(fn (string $url) => ['url' => $url, 'status' => self::PENDING], $links));
        $question->project->update(['awaiting_look' => true, 'look_started_at' => now()]);
    }

    /** No worker: what this server can read of each page (the HTML only, not what scripts draw). */
    public function readHere(BuilderMessage $question, array $links): void
    {
        $this->saveLooks($question, array_map(fn (string $url) => $this->readOne($url), $links));
    }

    /** @return list<array{message_id: int, index: int, url: string}> links for the worker, marked as taken */
    public function forWorker(): array
    {
        $this->heartbeat->beat(self::HEARTBEAT);
        $waiting = BuilderProject::where('awaiting_look', true)->where('look_started_at', '>=', now()->subSeconds(self::WAIT_SECONDS))->pluck('id');

        return BuilderMessage::whereIn('project_id', $waiting)->where('role', BuilderMessage::USER)->latest('id')->limit(20)->get()
            ->flatMap(fn (BuilderMessage $message) => $this->take($message))->values()->all();
    }

    /**
     * The worker's result for one link: the full page for the customer, the pieces and text for Claude.
     * True when every link of the message is done, so the chat can answer.
     *
     * @param  list<UploadedFile>  $pieces
     */
    public function complete(BuilderMessage $message, int $index, array $result, ?UploadedFile $fullPage, array $pieces): bool
    {
        $looks = $message->payload['looks'] ?? [];
        if (! isset($looks[$index]) || in_array($looks[$index]['status'], self::FINISHED, true)) {
            return false;
        }

        $looks[$index] = $fullPage === null
            ? ['url' => $looks[$index]['url'], 'status' => self::FAILED, 'reason' => mb_substr((string) ($result['reason'] ?? 'The page could not be opened.'), 0, 300)]
            : $this->seen($message, $looks[$index]['url'], $result, $fullPage, $pieces);
        $this->saveLooks($message, $looks);

        return $this->allFinished($looks);
    }

    /** True when the wait is over: the links the worker did not finish are read here, so the chat can answer. */
    public function finishLateLooks(BuilderProject $project): bool
    {
        if (! $project->awaiting_look || $project->look_started_at?->gt(now()->subSeconds(self::WAIT_SECONDS))) {
            return false;
        }

        $question = $project->messages()->where('role', BuilderMessage::USER)->latest('id')->first();
        if ($question) {
            $looks = collect($question->payload['looks'] ?? [])
                ->map(fn (array $look) => in_array($look['status'], self::FINISHED, true) ? $look : $this->readOne($look['url']))->all();
            $this->saveLooks($question, $looks);
        }

        return true;
    }

    /** @return list<array{message_id: int, index: int, url: string}> */
    private function take(BuilderMessage $message): array
    {
        $looks = $message->payload['looks'] ?? [];
        $taken = [];
        foreach ($looks as $index => $look) {
            if ($look['status'] === self::PENDING) {
                $looks[$index]['status'] = self::TAKEN;
                $taken[] = ['message_id' => $message->id, 'index' => $index, 'url' => $look['url']];
            }
        }
        if ($taken !== []) {
            $this->saveLooks($message, $looks);
        }

        return $taken;
    }

    /** @param  list<UploadedFile>  $pieces */
    private function seen(BuilderMessage $message, string $url, array $result, UploadedFile $fullPage, array $pieces): array
    {
        $files = $this->attachments->attach($message, [$fullPage, ...$pieces]);

        return [
            'url' => $url,
            'status' => self::SEEN,
            'title' => mb_substr((string) ($result['title'] ?? ''), 0, 300),
            'description' => mb_substr((string) ($result['description'] ?? ''), 0, 1000),
            'text' => mb_substr((string) ($result['text'] ?? ''), 0, 12000),
            'full_id' => $files[0]->id,
            'piece_ids' => array_map(fn ($file) => $file->id, array_slice($files, 1)),
        ];
    }

    private function readOne(string $url): array
    {
        try {
            return ['url' => $url, 'status' => self::READ] + $this->reader->read($url);
        } catch (RuntimeException $e) {
            return ['url' => $url, 'status' => self::FAILED, 'reason' => $e->getMessage()];
        }
    }

    private function allFinished(array $looks): bool
    {
        return collect($looks)->every(fn (array $look) => in_array($look['status'], self::FINISHED, true));
    }

    private function saveLooks(BuilderMessage $message, array $looks): void
    {
        $message->update(['payload' => ['looks' => array_values($looks)] + (array) $message->payload]);
    }
}
