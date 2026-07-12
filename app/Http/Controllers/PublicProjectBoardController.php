<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Services\ProjectBoardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicProjectBoardController extends Controller
{
    public function __construct(
        protected ProjectBoardService $boardService
    ) {}

    public function show(Request $request, string $token, string $date)
    {
        $project = Project::where('share_token', $token)->firstOrFail();

        if ($request->input('mode') === 'edit') {
            session()->put("shared_project_write_access.{$project->id}", true);
        }

        if ($request->user()) {
            $isShared = $project->shares()->where('user_id', $request->user()->id)->exists();
            if ($isShared || $request->user()->isAdmin() || $project->user_id === $request->user()->id) {
                session()->put("shared_project_access.{$project->id}", true);
                session()->put("shared_project_write_access.{$project->id}", true);
            }
        }

        $hasEditAccess = (bool) session()->get("shared_project_write_access.{$project->id}");

        try {
            $dateCarbon = Carbon::createFromFormat('!Y-m-d', $date, 'Africa/Cairo');
        } catch (\Throwable $e) {
            $dateCarbon = Carbon::today('Africa/Cairo');
        }

        $cards = $this->boardService->cardsForDate($project, $dateCarbon, applyFutureGating: !$hasEditAccess);
        $categories = $this->boardService->categoriesFor($project);

        $project->loadCount(['tasks', 'reports', 'files']);
        $currency = $project->currencyRow();

        $activeDates = ProjectBoardItem::where('project_id', $project->id)
            ->distinct()
            ->pluck('for_date')
            ->map(fn ($d) => is_string($d) ? $d : $d->toDateString())
            ->toArray();

        return Inertia::render('Public/SharedBoard', [
            'project' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'description' => $project->description,
                'status' => $project->status,
                'share_token' => $project->share_token,
                'client_name' => $project->client?->name,
                'owner_name' => $project->owner?->name,
                'currency' => $currency ? [
                    'id' => $currency->id,
                    'currency' => $currency->currency,
                    'symbol' => $currency->symbol,
                    'string_format' => $currency->string_format,
                ] : null,
            ],
            'date' => $dateCarbon->toDateString(),
            'lanes' => $this->boardService->lanes(),
            'cards' => $cards,
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->localizedName(),
                'color' => $c->color,
                'text_color' => $c->text_color,
                'is_system' => (bool) $c->is_system,
            ])->values(),
            'activeDates' => $activeDates,
            'hasEditAccess' => $hasEditAccess,
        ]);
    }
}
