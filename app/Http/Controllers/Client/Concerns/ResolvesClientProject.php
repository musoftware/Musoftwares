<?php

namespace App\Http\Controllers\Client\Concerns;

use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Shared helpers for client-facing project controllers: ownership enforcement,
 * per-day board payloads, and the future-task gating behaviour.
 */
trait ResolvesClientProject
{
    protected function authorizeProject(Project $project): void
    {
        $this->authorize('view', $project);
    }

    /**
     * Day boards and task lists honour the per-project "hide future items" flag.
     * Returns true when the given date should be hidden from the client.
     */
    protected function shouldHideFuture(Project $project, ?CarbonInterface $date): bool
    {
        if (! $project->hide_future_tasks || $date === null) {
            return false;
        }

        return $date->isAfter(Carbon::today());
    }

    /**
     * Default workflow lanes for the per-day board.
     */
    protected function boardLanes(): array
    {
        return ['backlog', 'in_progress', 'review', 'done'];
    }
}
