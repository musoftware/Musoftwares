<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A paid build that failed because something broke (the agent process died, the PC or Docker restarted) goes back
 * to the worker by itself, keeping the work already done. A real failure (the budget is used up, the program still
 * fails its final check) waits for the owner, and so does a build that already failed its automatic tries.
 */
class CrashRetries
{
    /** Failures that a new try would only repeat. */
    private const REAL_FAILURES = ['budget', 'final check'];

    public function __construct(private readonly JobQueue $jobs, private readonly ProjectPayment $payment) {}

    public function retryAll(): int
    {
        $due = BuilderProject::where('status', BuilderProject::FAILED)
            ->whereNotNull('paid_at')
            ->where('auto_retries', '<', (int) config('builder.limits.auto_retries'))
            // the worker, Docker or the PC may still be coming back
            ->where('updated_at', '<', now()->subMinutes((int) config('builder.limits.auto_retry_after_minutes')))
            ->whereColumn('job_cost_usd', '<', 'budget_usd')
            ->get()
            ->filter(fn (BuilderProject $project) => ! Str::contains(Str::lower((string) $project->error), self::REAL_FAILURES));

        $due->each(fn (BuilderProject $project) => $this->retry($project));

        return $due->count();
    }

    private function retry(BuilderProject $project): void
    {
        $error = (string) $project->error;
        $try = $project->auto_retries + 1;
        $project->update(['auto_retries' => $try]);
        $this->jobs->requeue($project);
        $this->payment->tellOwner($project, 'Build restarted by itself (try '.$try.'): '.$project->title.' - '.mb_substr($error, 0, 200));
    }
}
