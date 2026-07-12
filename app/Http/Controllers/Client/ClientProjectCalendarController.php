<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Services\ProjectBoardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProjectCalendarController extends Controller
{
    use ResolvesClientProject;

    public function __construct(protected ProjectBoardService $boardService) {}

    public function calendarIndex(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        return redirect()->route('client.projects.calendar.date', [
            'project' => $project->id,
            'date' => Carbon::today('Africa/Cairo')->toDateString(),
        ]);
    }

    public function calendarDate(Request $request, Project $project, string $date)
    {
        $this->authorizeProject($project);

        $dateCarbon = $this->parseDate($date) ?? Carbon::today('Africa/Cairo');
        $isAdmin = $request->user()?->isAdmin() === true;

        $cards = $this->boardService->cardsForDate($project, $dateCarbon, applyFutureGating: true);
        $categories = $this->boardService->categoriesFor($project);

        $activeDates = ProjectBoardItem::where('project_id', $project->id)
            ->distinct()
            ->pluck('for_date')
            ->map(fn ($d) => is_string($d) ? $d : $d->toDateString())
            ->toArray();

        return Inertia::render('Client/Projects/Calendar/Date', [
            'project' => ['id' => $project->id, 'name' => $project->project_name, 'hide_future_tasks' => (bool) $project->hide_future_tasks],
            'date' => $dateCarbon->toDateString(),
            'lanes' => $this->boardService->lanes(),
            'cards' => fn () => $cards,
            'categories' => fn () => $categories->map(fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->localizedName(),
                'name_ar' => $c->name_ar,
                'color' => $c->color,
                'text_color' => $c->text_color,
                'is_system' => (bool) $c->is_system,
                'sort' => (int) $c->sort,
            ])->values(),
            'hideFuture' => ! $isAdmin && $this->shouldHideFuture($project, $dateCarbon),
            'isAdmin' => $isAdmin,
            'activeDates' => $activeDates,
        ]);
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m-d', $date, 'Africa/Cairo');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
