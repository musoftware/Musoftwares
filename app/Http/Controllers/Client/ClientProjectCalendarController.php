<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProjectCalendarController extends Controller
{
    use ResolvesClientProject;

    public function calendarIndex(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        return redirect()->route('client.projects.calendar.date', [
            'project' => $project->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function calendarDate(Request $request, Project $project, string $date)
    {
        $this->authorizeProject($project);

        $dateCarbon = $this->parseDate($date) ?? Carbon::today();

        $notes = collect();
        $tasks = collect();
        $reports = collect();
        $cards = [];

        // Future-task gating: clients with the flag enabled never see a board for a future date.
        if (! $this->shouldHideFuture($project, $dateCarbon)) {
            $notes = $project->boardNotes()
                ->whereDate('for_date', $dateCarbon->toDateString())
                ->get();

            $tasks = $project->tasks()
                ->whereDate('due_date', $dateCarbon->toDateString())
                ->get();

            $reports = $project->publishedReports()
                ->whereDate('published_at', $dateCarbon->toDateString())
                ->get();

            $cards = $this->buildCards($project, $dateCarbon, $notes, $tasks, $reports);
        }

        return Inertia::render('Client/Projects/Calendar/Date', [
            'project' => ['id' => $project->id, 'name' => $project->project_name, 'hide_future_tasks' => (bool) $project->hide_future_tasks],
            'date' => $dateCarbon->toDateString(),
            'lanes' => $this->boardLanes(),
            'cards' => fn () => $cards,
            'hideFuture' => (bool) $project->hide_future_tasks,
        ]);
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Merge notes/tasks/reports into positioned board cards, applying any saved placements.
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
            $morph = $this->morphClassFor($type);
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
                'done' => $task->completed(),
            ]);
        }

        foreach ($reports as $report) {
            $addCard('report', $report->id, $report->title, [
                'published_at' => optional($report->published_at)->toIso8601String(),
            ]);
        }

        return $cards;
    }

    private function morphClassFor(string $type): string
    {
        return ProjectBoardItem::morphClassFor($type);
    }
}
