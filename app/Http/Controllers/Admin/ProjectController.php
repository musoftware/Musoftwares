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
        
        $query = Project::with(['client', 'contracts', 'contracts.versions', 'invoices' => function($q) {
            $q->where('status', 'unpaid');
        }]);
        
        if ($status === 'archived') {
            $query->where('archived', 1);
        } else {
            $query->where('archived', 0);
        }

        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'currentTab' => $status,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $this->projectService->createProject($request->validated());

        return redirect()->back()->with('success', __('general.project_created_successfully'));
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        $this->projectService->updateProject($id, $request->validated());

        return redirect()->back()->with('success', __('general.project_updated_successfully'));
    }

    public function archive($id)
    {
        $this->projectService->archiveProject($id);

        return redirect()->back()->with('success', __('general.project_archived'));
    }

    public function restore($id)
    {
        $this->projectService->restoreProject($id);

        return redirect()->back()->with('success', __('general.project_restored'));
    }

    public function destroy($id)
    {
        $this->projectService->deleteProject($id);

        return redirect()->back()->with('success', __('general.project_deleted'));
    }

    public function searchClients(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json($clients);
    }
}
