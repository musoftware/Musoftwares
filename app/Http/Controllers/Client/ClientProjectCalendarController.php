<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectBoardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProjectCalendarController extends Controller
{
    public function __construct(protected ProjectBoardService $boardService)
    {
    }

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

        $cards = $this->boardService->cardsForDate($project, $dateCarbon, applyFutureGating: true);

        return Inertia::render('Client/Projects/Calendar/Date', [
            'project' => ['id' => $project->id, 'name' => $project->project_name, 'hide_future_tasks' => (bool) $project->hide_future_tasks],
            'date' => $dateCarbon->toDateString(),
            'lanes' => $this->boardService->lanes(),
            'cards' => fn () => $cards,
            'hideFuture' => $this->shouldHideFuture($project, $dateCarbon),
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
}
