<?php

namespace App\Http\Controllers\Client\Concerns;

use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

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

            $dateCarbon = $date ? $this->parseSharedBoardDate($date) : null;

            // abort() must stay outside any try/catch, or the 403 would be swallowed.
            if ($dateCarbon && $dateCarbon->startOfDay()->isAfter(Carbon::today('Africa/Cairo'))) {
                abort(403, 'Access to future dates is restricted on shared boards.');
            }
        }
    }

    /**
     * Parses a board date in Cairo time. Returns null when it cannot be parsed,
     * so the controller's own validation can report the bad input.
     */
    private function parseSharedBoardDate(mixed $date): ?Carbon
    {
        try {
            return is_string($date)
                ? Carbon::createFromFormat('!Y-m-d', $date, 'Africa/Cairo')
                : Carbon::instance($date)->setTimezone('Africa/Cairo');
        } catch (\Throwable $e) {
            Log::debug('Shared board: unparseable date, leaving validation to the controller', [
                'date' => is_string($date) ? $date : get_debug_type($date),
                'error' => $e->getMessage(),
            ]);

            return null;
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
