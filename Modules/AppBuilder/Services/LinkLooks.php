<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderAttachment;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use RuntimeException;

/**
 * What the customer shares is looked at before the chat answers, like a real programmer opens an example
 * site or a spreadsheet first. The worker does the looking, in Docker on the owner's PC:
 * - a link: opened in a real browser; a full-page screenshot is shown in the chat, and Claude reads sharp
 *   pieces of it with the page text;
 * - an Excel, Word or CSV file: read with its sheets, cells and formulas, and the text goes to Claude.
 * When no worker is there, or it is too slow, this server reads a link's HTML itself; a file then waits.
 */
class LinkLooks
{
    public const PENDING = 'pending';

    public const TAKEN = 'taken';

    public const SEEN = 'seen';

    public const READ = 'read';

    public const FAILED = 'failed';

    public const LINK = 'link';

    public const FILE = 'file';

    private const MAX_LINKS = 3;

    private const HEARTBEAT = 'looks';

    /** The worker asks every few seconds; silent longer than this, it is not there. */
    private const WORKER_FRESH_SECONDS = 40;

    /** The longest the customer waits before the chat answers anyway. */
    private const WAIT_SECONDS = 50;

    /** A Figma design is drawn without a graphics card on the worker, so it gets longer. */
    private const FIGMA_WAIT_SECONDS = 150;

    private const TEXT_CHARS = 80000;

    private const FINISHED = [self::SEEN, self::READ, self::FAILED];

    public function __construct(
        private readonly PageReader $reader,
        private readonly AttachmentStore $attachments,
        private readonly WorkerHeartbeat $heartbeat,
    ) {}

    /** @return list<array<string, mixed>> the links and readable files in a customer's message, still to look at */
    public function sharedIn(BuilderMessage $question): array
    {
        $links = array_map(fn (string $url) => ['kind' => self::LINK, 'url' => $url, 'status' => self::PENDING], $this->linksIn($question->content));
        $files = $question->attachments()->get()
            ->filter(fn (BuilderAttachment $file) => ChatFileTypes::isReadByWorker($file->mime))
            ->map(fn (BuilderAttachment $file) => ['kind' => self::FILE, 'attachment_id' => $file->id, 'name' => $file->original_name, 'status' => self::PENDING])
            ->values()->all();

        return [...$links, ...$files];
    }

    public function workerIsWatching(): bool
    {
        return $this->heartbeat->seenWithin(self::HEARTBEAT, self::WORKER_FRESH_SECONDS);
    }

    /** The worker will look; the chat answers once it has. */
    public function askWorker(BuilderMessage $question, array $shared): void
    {
        $this->save($question, $shared);
        $question->project->update(['awaiting_look' => true, 'look_started_at' => now()]);
    }

    /** No worker: links are read here (the HTML only); files cannot be read without it. */
    public function readHere(BuilderMessage $question, array $shared): void
    {
        $this->save($question, array_map(fn (array $item) => $this->withoutWorker($item), $shared));
    }

    /** @return list<array<string, mixed>> what the worker should look at now, marked as taken */
    public function forWorker(): array
    {
        $this->heartbeat->beat(self::HEARTBEAT);
        $waiting = BuilderProject::where('awaiting_look', true)->where('look_started_at', '>=', now()->subSeconds(self::FIGMA_WAIT_SECONDS))->pluck('uuid', 'id');

        return BuilderMessage::whereIn('project_id', $waiting->keys())->where('role', BuilderMessage::USER)->latest('id')->limit(20)->get()
            ->flatMap(fn (BuilderMessage $message) => $this->take($message, (string) $waiting[$message->project_id]))->values()->all();
    }

    /**
     * The worker's result for one item. True when everything in the message is done, so the chat can answer.
     *
     * @param  list<UploadedFile>  $pieces
     */
    public function complete(BuilderMessage $message, int $index, array $result, ?UploadedFile $fullPage, array $pieces): bool
    {
        $shared = $message->payload['looks'] ?? [];
        if (! isset($shared[$index]) || in_array($shared[$index]['status'], self::FINISHED, true)) {
            return false;
        }

        $shared[$index] = match (true) {
            ($shared[$index]['kind'] ?? self::LINK) === self::FILE => $this->fileRead($shared[$index], $result),
            $fullPage !== null => $this->pageSeen($message, $shared[$index], $result, $fullPage, $pieces),
            default => $this->failed($shared[$index], (string) ($result['reason'] ?? 'The page could not be opened.')),
        };
        $this->save($message, $shared);

        return $this->allFinished($shared);
    }

    /** True when the wait is over: what the worker did not finish is read here or marked as not read. */
    public function finishLate(BuilderProject $project): bool
    {
        if (! $project->awaiting_look) {
            return false;
        }

        $question = $project->lastCustomerMessage();
        if ($project->look_started_at?->gt(now()->subSeconds($this->waitFor($question)))) {
            return false;
        }

        if ($question) {
            $shared = collect($question->payload['looks'] ?? [])
                ->map(fn (array $item) => in_array($item['status'], self::FINISHED, true) ? $item : $this->withoutWorker($item))->all();
            $this->save($question, $shared);
        }

        return true;
    }

    private function waitFor(?BuilderMessage $question): int
    {
        $figma = collect($question?->payload['looks'] ?? [])
            ->contains(fn (array $item) => preg_match('/(^|\.)figma\.com$/i', (string) parse_url((string) ($item['url'] ?? ''), PHP_URL_HOST)) === 1);

        return $figma ? self::FIGMA_WAIT_SECONDS : self::WAIT_SECONDS;
    }

    /** True while the worker is reading a file (rather than opening a link), for the line the customer sees. */
    public function isReadingAFile(BuilderProject $project): bool
    {
        $question = $project->lastCustomerMessage();

        return collect($question?->payload['looks'] ?? [])->contains(fn (array $item) => ($item['kind'] ?? self::LINK) === self::FILE);
    }

    /** @return list<string> */
    private function linksIn(string $text): array
    {
        preg_match_all('~https?://[^\s<>"\'\x{0600}-\x{06FF}]+~u', $text, $matches);

        return collect($matches[0])
            ->map(fn (string $url) => rtrim($url, '.,;:!?)]}،؟'))
            ->filter(fn (string $url) => filter_var($url, FILTER_VALIDATE_URL) !== false)
            ->unique()->take(self::MAX_LINKS)->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function take(BuilderMessage $message, string $projectUuid): array
    {
        $shared = $message->payload['looks'] ?? [];
        $taken = [];
        foreach ($shared as $index => $item) {
            if ($item['status'] === self::PENDING) {
                $shared[$index]['status'] = self::TAKEN;
                $taken[] = ['message_id' => $message->id, 'index' => $index, 'project' => $projectUuid] + array_diff_key($item, ['status' => true]);
            }
        }
        if ($taken !== []) {
            $this->save($message, $shared);
        }

        return $taken;
    }

    private function fileRead(array $item, array $result): array
    {
        $text = trim((string) ($result['text'] ?? ''));
        if ($text === '') {
            return $this->failed($item, (string) ($result['reason'] ?? 'The file could not be read.'));
        }
        BuilderAttachment::whereKey($item['attachment_id'])->update(['extracted_text' => mb_substr($text, 0, self::TEXT_CHARS)]);

        return ['status' => self::SEEN] + $item;
    }

    /** @param  list<UploadedFile>  $pieces */
    private function pageSeen(BuilderMessage $message, array $item, array $result, UploadedFile $fullPage, array $pieces): array
    {
        $files = $this->attachments->attach($message, [$fullPage, ...$pieces]);

        return [
            'kind' => self::LINK,
            'url' => $item['url'],
            'status' => self::SEEN,
            'title' => mb_substr((string) ($result['title'] ?? ''), 0, 300),
            'description' => mb_substr((string) ($result['description'] ?? ''), 0, 1000),
            'text' => mb_substr((string) ($result['text'] ?? ''), 0, 12000),
            'full_id' => $files[0]->id,
            'piece_ids' => array_map(fn ($file) => $file->id, array_slice($files, 1)),
        ];
    }

    private function withoutWorker(array $item): array
    {
        if (($item['kind'] ?? self::LINK) === self::FILE) {
            return $this->failed($item, 'the file reader on the engineering computer did not answer in time');
        }

        try {
            return ['kind' => self::LINK, 'url' => $item['url'], 'status' => self::READ] + $this->reader->read($item['url']);
        } catch (RuntimeException $e) {
            return $this->failed($item, $e->getMessage());
        }
    }

    private function failed(array $item, string $reason): array
    {
        return ['status' => self::FAILED, 'reason' => mb_substr($reason, 0, 300)] + $item;
    }

    private function allFinished(array $shared): bool
    {
        return collect($shared)->every(fn (array $item) => in_array($item['status'], self::FINISHED, true));
    }

    private function save(BuilderMessage $message, array $shared): void
    {
        $message->update(['payload' => ['looks' => array_values($shared)] + (array) $message->payload]);
    }
}
