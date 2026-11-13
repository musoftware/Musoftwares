<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderWorkerEvent;

/**
 * The worker's calls on each project, kept as a short log the owner reads on the admin page. The same call again
 * right after (polling for an answer, the same step) counts up on the last line instead of adding a new one.
 */
class WorkerActivity
{
    /** Lines kept per project; older ones go. */
    private const KEEP = 300;

    private const SHOW = 80;

    public function __construct(private readonly ProjectSecrets $secrets) {}

    public function record(BuilderProject $project, string $kind, ?string $detail, string $worker): void
    {
        // the worker's own words can carry an access detail it was given; the log never shows it
        $detail = $detail === null || $detail === '' ? null : mb_substr($this->secrets->mask($project, $detail), 0, 300);
        $last = BuilderWorkerEvent::where('project_id', $project->id)->latest('id')->first();
        if ($last !== null && $last->kind === $kind && $last->detail === $detail && $last->worker === $worker) {
            $last->increment('repeats');

            return;
        }

        $event = BuilderWorkerEvent::create(['project_id' => $project->id, 'worker' => $worker, 'kind' => $kind, 'detail' => $detail]);
        // most calls add nothing new to trim; check once in a while
        if ($event->id % 20 === 0) {
            $this->trim($project);
        }
    }

    /** @return list<array{id: int, kind: string, detail: string|null, worker: string|null, repeats: int, at: string|null, last_at: string|null}> newest first */
    public function forAdmin(BuilderProject $project): array
    {
        return BuilderWorkerEvent::where('project_id', $project->id)->latest('id')->limit(self::SHOW)->get()
            ->map(fn (BuilderWorkerEvent $event) => [
                'id' => $event->id,
                'kind' => $event->kind,
                'detail' => $event->detail,
                'worker' => $event->worker,
                'repeats' => $event->repeats,
                'at' => $event->created_at?->timezone('Africa/Cairo')->toDateTimeString(),
                'last_at' => $event->updated_at?->timezone('Africa/Cairo')->toDateTimeString(),
            ])->values()->all();
    }

    private function trim(BuilderProject $project): void
    {
        $oldestKept = BuilderWorkerEvent::where('project_id', $project->id)->latest('id')->skip(self::KEEP - 1)->value('id');
        if ($oldestKept !== null) {
            BuilderWorkerEvent::where('project_id', $project->id)->where('id', '<', $oldestKept)->delete();
        }
    }
}
