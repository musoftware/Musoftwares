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
        if (session()->get("shared_project_write_access.{$project->id}")) {
            return;
        }

        $this->authorize('view', $project);

        $user = auth()->user();
        if ($user && $user->id !== $project->user_id && !$user->isAdmin()) {
            $date = request()->route('date')
                ?? request()->input('for_date')
                ?? request()->input('date')
                ?? request()->input('inDate');

            if ($date) {
                try {
                    $dateCarbon = is_string($date) ? Carbon::createFromFormat('!Y-m-d', $date, 'Africa/Cairo') : $date->copy()->setTimezone('Africa/Cairo');
                    if ($dateCarbon->startOfDay()->isAfter(Carbon::today('Africa/Cairo'))) {
                        abort(403, 'Access to future dates is restricted on shared boards.');
                    }
                } catch (\Throwable $e) {
                    // Fail-safe: if date parsing fails, let the controller handle validation.
                }
            }
        }
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

        $cairoDate = Carbon::parse($date)->setTimezone('Africa/Cairo')->startOfDay();
        return $cairoDate->isAfter(Carbon::today('Africa/Cairo'));
    }

    /**
     * Default workflow lanes for the per-day board.
     */
    protected function boardLanes(): array
    {
        return ['backlog', 'in_progress', 'review', 'done'];
    }
}
