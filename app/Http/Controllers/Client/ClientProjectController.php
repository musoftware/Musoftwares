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
            'projects' => fn () => ClientProjectResource::collection($projects)->additional([
                'meta' => ['current_page' => $projects->currentPage(), 'last_page' => $projects->lastPage()],
            ]),
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

        return Inertia::render('Client/Projects/Show', [
            'project' => fn () => (new ClientProjectResource($project))->resolve(),
            'recentReports' => fn () => $recentReports,
        ]);
    }
}
