<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectBoardItem;
use Carbon\Carbon;

/**
 * Builds per-day board cards (notes + tasks + reports merged with their saved placements).
 * Shared by the client calendar (applies future-task gating) and the admin project board
 * (sees everything, no gating).
 */
class ProjectBoardService
{
    /** @return string[] */
    public function lanes(): array
    {
        return ['backlog', 'in_progress', 'review', 'done'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cardsForDate(Project $project, Carbon $date, bool $applyFutureGating): array
    {
        // Clients with the flag enabled never see a board for a future date.
        if ($applyFutureGating && $project->hide_future_tasks && $date->isAfter(Carbon::today())) {
            return [];
        }

        $notes = $project->boardNotes()
            ->whereDate('for_date', $date->toDateString())
            ->get();

        $tasks = $project->tasks()
            ->whereDate('due_date', $date->toDateString())
            ->get();

        // Clients only see published reports; admins see all reports for the date.
        $reportsQuery = $applyFutureGating ? $project->publishedReports() : $project->reports();
        $reports = $reportsQuery->whereDate('published_at', $date->toDateString())->get();

        return $this->buildCards($project, $date, $notes, $tasks, $reports);
    }

    /**
     * @param  iterable  $notes
     * @param  iterable  $tasks
     * @param  iterable  $reports
     * @return array<int, array<string, mixed>>
     */
    private function buildCards(Project $project, Carbon $date, $notes, $tasks, $reports): array
    {
        $placements = $project->boardItems()
            ->whereDate('for_date', $date->toDateString())
            ->get()
            ->keyBy(fn (ProjectBoardItem $item) => $item->itemable_type.':'.$item->itemable_id);

        $cards = [];
        $autoIndex = 0;

        $addCard = function (string $type, int $id, string $title, array $extra = []) use (&$cards, $placements, &$autoIndex) {
            $morph = ProjectBoardItem::morphClassFor($type);
            $placement = $placements->get("{$morph}:{$id}");

            $cards[] = array_merge([
                'type' => $type,
                'id' => $id,
                'title' => $title,
                'lane' => $placement->lane ?? 'backlog',
                'pos_x' => $placement->pos_x ?? 24,
                'pos_y' => $placement->pos_y ?? (24 + ($autoIndex * 12)),
            ], $extra);

            $autoIndex++;
        };

        foreach ($notes as $note) {
            $addCard('note', $note->id, $note->content ?: __('general.sticky_note'), [
                'color' => $note->color,
                'content' => $note->content,
            ]);
        }

        foreach ($tasks as $task) {
            $addCard('task', $task->id, $task->task_name, [
                'priority' => $task->priority,
                'done' => method_exists($task, 'completed') ? $task->completed() : false,
            ]);
        }

        foreach ($reports as $report) {
            $addCard('report', $report->id, $report->title, [
                'published_at' => optional($report->published_at)->toIso8601String(),
            ]);
        }

        return $cards;
    }
}
