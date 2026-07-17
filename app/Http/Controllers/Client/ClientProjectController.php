<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('user_id', $request->user()->id)
            ->where('archived', 0)
            ->withCount(['tasks', 'publishedReports', 'files'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Client/Projects/Index', [
            'projects' => fn () => ClientProjectResource::collection($projects),
        ]);
    }

    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->loadCount(['tasks', 'publishedReports', 'files']);

        $recentReports = $project->publishedReports()
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'published_at']);

        $allowedTabs = ['tasks', 'discussions', 'files', 'financials'];
        $tab = in_array($request->query('tab'), $allowedTabs, true)
            ? $request->query('tab')
            : 'tasks';

        $team = $this->resolveTeam($project);

        return Inertia::render('Client/Projects/Show', [
            'project' => fn () => (new ClientProjectResource($project))->resolve(),
            'recentReports' => fn () => $recentReports,
            'team' => fn () => $team,
            'activeTab' => fn () => $tab,
            'tabContent' => fn () => $this->loadTabContent($project, $tab),
        ]);
    }

    /**
     * Best-effort project team loader. Falls back to the project owner when no
     * dedicated team relation exists on the Eloquent model.
     */
    protected function resolveTeam(Project $project): array
    {
        $members = [];

        if (method_exists($project, 'team')) {
            try {
                $members = $project->team()->get()->map(fn ($u) => [
                    'id' => (int) $u->id,
                    'name' => $u->name ?? '',
                    'avatar_url' => $u->avatar_url ?? null,
                    'role' => $u->pivot->role ?? null,
                ])->all();
            } catch (\Throwable $e) {
                $members = [];
            }
        }

        if (empty($members) && $project->relationLoaded('user') === false && method_exists($project, 'user')) {
            $owner = $project->user ?? $project->client ?? null;
            if ($owner) {
                $members = [[
                    'id' => (int) $owner->id,
                    'name' => $owner->name ?? '',
                    'avatar_url' => $owner->avatar_url ?? null,
                    'role' => 'Owner',
                ]];
            }
        }

        return $members;
    }

    protected function loadTabContent(Project $project, string $tab): array
    {
        switch ($tab) {
            case 'tasks':
                $tasks = $project->tasks()
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get(['id', 'task_name', 'task_description', 'due_date', 'priority']);
                return ['tasks' => $tasks];

            case 'discussions':
                $comments = $project->comments()
                    ->with('user:id,name,avatar_url')
                    ->latest()
                    ->limit(50)
                    ->get(['id', 'body', 'user_id', 'created_at']);
                return ['discussions' => $comments];

            case 'files':
                $files = $project->files()
                    ->latest()
                    ->limit(50)
                    ->get(['id', 'original_name', 'mime', 'size', 'created_at']);
                return ['files' => $files];

            case 'financials':
                return [
                    'financials' => [
                        'budget' => (string) $project->budget,
                        'paid' => (string) $project->paid_invoices,
                        'pending' => (string) $project->pending_invoices,
                        'percentage' => (float) $project->percentage,
                    ],
                ];
        }

        return [];
    }
}
