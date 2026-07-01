<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\BulkProjectActionRequest;
use App\Http\Requests\Admin\Project\StoreProjectRequest;
use App\Http\Requests\Admin\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectCollection;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectBoardService;
use App\Services\ProjectService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    private const SORTABLE = [
        'id' => 'id',
        'project_name' => 'project_name',
        'status' => 'status',
        'created_at' => 'created_at',
        'date_start' => 'date_start',
        'date_end' => 'date_end',
        'budget' => 'budget',
        'project_balance' => 'project_balance',
    ];

    private const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public function __construct(
        protected ProjectService $projectService,
        protected ProjectBoardService $boardService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $tab = $request->get('status', 'active');   // active | archived | all
        $statusFilter = $request->get('status_filter');   // open | hold_on | closed

        $query = Project::query()
            ->with(['client', 'owner'])
            ->withCount([
                'contracts',
                'invoices as invoices_count' => fn ($q) => $q->where('status', 'unpaid'),
                'tasks',
                'reports',
                'files',
            ]);

        if ($tab === 'archived') {
            $query->where('archived', 1);
        } elseif ($tab === 'all') {
            // no archived filter
        } else {
            $query->where('archived', 0);
        }

        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($statusFilter && in_array($statusFilter, ['open', 'hold_on', 'closed'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sort = $request->get('sort', 'created_at');
        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (! array_key_exists($sort, self::SORTABLE)) {
            $sort = 'created_at';
        }
        $query->orderBy(self::SORTABLE[$sort], $dir);

        // Pagination
        $perPage = (int) $request->get('per_page', 15);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 15;
        }
        $projects = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => new ProjectCollection($projects),
            'currentTab' => $tab,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Project::class);

        $initialClient = null;
        if ($request->filled('client_id')) {
            $client = User::find($request->integer('client_id'));
            if ($client) {
                $initialClient = [
                    'id' => $client->id,
                    'name' => $client->name,
                ];
            }
        }

        return Inertia::render('Admin/Projects/Create', [
            'initialClient' => $initialClient,
            'prefillClientId' => $initialClient['id'] ?? null,
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->createProject($request->validated());

        return redirect()
            ->route('admin.projects.index')
            ->with('success', __('general.project_created_successfully'));
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        $this->projectService->updateProject($id, $request->validated());

        return redirect()->back()->with('success', __('general.project_updated_successfully'));
    }

    public function archive($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('archive', $project);

        $this->projectService->archiveProject($id);

        return redirect()->back()->with('success', __('general.project_archived'));
    }

    public function restore($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('restore', $project);

        $this->projectService->restoreProject($id);

        return redirect()->back()->with('success', __('general.project_restored'));
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);

        $this->projectService->deleteProject($id);

        return redirect()->back()->with('success', __('general.project_deleted'));
    }

    public function bulkAction(BulkProjectActionRequest $request)
    {
        $this->authorize('deleteAny', Project::class);

        $ids = collect($request->validated('ids'))->map(fn ($id) => (int) $id)->filter()->values();
        $action = $request->validated('action');

        $count = match ($action) {
            'archive' => $this->projectService->bulkArchive($ids),
            'restore' => $this->projectService->bulkRestore($ids),
            'delete' => $this->projectService->bulkDelete($ids),
            default => 0,
        };

        return redirect()->back()->with('success', __('general.projects_bulk_action_done', ['count' => $count, 'action' => $action]));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Project::class);

        $tab = $request->get('status', 'active');
        $query = Project::query()->with(['client', 'owner']);
        if ($tab === 'archived') {
            $query->where('archived', 1);
        } elseif ($tab !== 'all') {
            $query->where('archived', 0);
        }
        if ($request->filled('status_filter') && in_array($request->status_filter, ['open', 'hold_on', 'closed'], true)) {
            $query->where('status', $request->status_filter);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $sort = $request->get('sort', 'created_at');
        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (array_key_exists($sort, self::SORTABLE)) {
            $query->orderBy(self::SORTABLE[$sort], $dir);
        }

        $projects = $query->lazy(200);

        $filename = "projects_{$tab}_".now()->format('Ymd_His').'.csv';
        $columns = [
            'id', 'project_name', 'description', 'client', 'owner', 'status', 'archived',
            'date_start', 'date_end', 'project_balance', 'budget', 'hour_rate', 'percentage',
            'created_at', 'archived_at',
        ];

        return ResponseFacade::streamDownload(function () use ($projects, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($this->projectService->exportRows($projects, $columns) as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function boardIndex(Request $request, $project)
    {
        $projectModel = Project::findOrFail($project);
        $this->authorize('view', $projectModel);

        return redirect()->route('admin.projects.board', [
            'project' => $project,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function board(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $date = $this->parseBoardDate($request->route('date'));

        $cards = $this->boardService->cardsForDate($project, $date, applyFutureGating: false);

        $project->loadCount(['tasks', 'reports', 'files']);

        return Inertia::render('Admin/Projects/Board', [
            'project' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'description' => $project->description,
                'status' => $project->status,
                'archived' => (bool) $project->archived,
                'budget' => (string) ($project->budget ?? 0),
                'project_balance' => (string) ($project->project_balance ?? 0),
                'total_paid' => (string) ($project->total_paid ?? 0),
                'hour_rate' => (string) ($project->hour_rate ?? 0),
                'percentage' => (float) ($project->percentage ?? 0),
                'date_start' => optional($project->date_start)->toDateString(),
                'date_end' => optional($project->date_end)->toDateString(),
                'client_name' => $project->client?->name,
                'owner_name' => $project->owner?->name,
                'counts' => [
                    'tasks' => $project->tasks_count,
                    'reports' => $project->reports_count,
                    'files' => $project->files_count,
                ],
            ],
            'date' => $date->toDateString(),
            'lanes' => $this->boardService->lanes(),
            'cards' => fn () => $cards,
        ]);
    }

    private function parseBoardDate(?string $date): Carbon
    {
        if ($date) {
            try {
                return Carbon::createFromFormat('!Y-m-d', $date);
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return Carbon::today();
    }

    public function searchClients(Request $request): JsonResponse
    {
        $this->authorize('searchClients', Project::class);

        $query = trim((string) $request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = User::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($clients);
    }
}
