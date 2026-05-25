<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Http\Requests\Admin\Project\StoreProjectRequest;
use App\Http\Requests\Admin\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');
        
        $query = Project::with(['client']);
        
        if ($status === 'archived') {
            $query->where('archived', 1);
        } else {
            $query->where('archived', 0);
        }

        $projects = $query->latest()->get();
        $clients = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => ProjectResource::collection($projects)->resolve(),
            'clients' => $clients,
            'currentTab' => $status,
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $this->projectService->createProject($request->validated());

        return redirect()->back()->with('success', 'Project created successfully.');
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        $this->projectService->updateProject($id, $request->validated());

        return redirect()->back()->with('success', 'Project updated successfully.');
    }

    public function archive($id)
    {
        $this->projectService->archiveProject($id);

        return redirect()->back()->with('success', 'Project archived.');
    }

    public function restore($id)
    {
        $this->projectService->restoreProject($id);

        return redirect()->back()->with('success', 'Project restored.');
    }

    public function destroy($id)
    {
        $this->projectService->deleteProject($id);

        return redirect()->back()->with('success', 'Project deleted.');
    }
}
