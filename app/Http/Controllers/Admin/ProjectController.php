<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\BulkProjectActionRequest;
use App\Http\Requests\Admin\Project\StoreProjectRequest;
use App\Http\Requests\Admin\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectCollection;
use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectBoardItem;
use App\Models\User;
use App\Services\ProjectBoardService;
use App\Services\ProjectService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Modules\Shortlink\Services\ShortlinkService;
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
        'percentage' => 'percentage',
    ];

    private const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    private const VALID_STATUSES = ['open', 'hold_on', 'closed'];

    /**
     * Apply the shared faceted filters to a Project query.
     *
     * Used by both index() and export() so the exported CSV always matches the
     * visible filtered set.
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        // Client
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->integer('client_id'));
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        // Owner
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->integer('owner_id'));
        }

        // Status: prefer multi-statuses[], fall back to single status_filter for backward-compat.
        // NOTE: we deliberately use "statuses" (not "status") because the tab param
        // already occupies "status" (active|archived|all) and reusing it would collide
        // in PHP's $_GET when both a scalar and array are present.
        $statusList = $request->input('statuses', []);
        if (is_string($statusList)) {
            $statusList = array_filter([$statusList]);
        }
        $statusList = is_array($statusList)
            ? array_values(array_intersect($statusList, self::VALID_STATUSES))
            : [];
        if (! empty($statusList)) {
            $query->whereIn('status', $statusList);
        } else {
            $statusFilter = $request->get('status_filter');
            if ($statusFilter && in_array($statusFilter, self::VALID_STATUSES, true)) {
                $query->where('status', $statusFilter);
            }
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

        $this->applyNumericRange($query, $request, 'budget_min', 'budget_max', 'budget');
        $this->applyNumericRange($query, $request, 'percent_min', 'percent_max', 'percentage');
        $this->applyDateRange($query, $request, 'start_from', 'start_to', 'date_start');
        $this->applyDateRange($query, $request, 'created_from', 'created_to', 'created_at');

        if (filter_var($request->get('has_unpaid'), FILTER_VALIDATE_BOOLEAN)) {
            $query->whereHas('invoices', fn ($q) => $q->where('status', 'unpaid'));
        }
    }

    private function applyNumericRange(Builder $query, Request $request, string $minKey, string $maxKey, string $column): void
    {
        $bounds = [];
        if ($request->filled($minKey) && is_numeric($request->get($minKey))) {
            $bounds[] = $request->float($minKey);
        } else {
            $bounds[] = null;
        }
        if ($request->filled($maxKey) && is_numeric($request->get($maxKey))) {
            $bounds[] = $request->float($maxKey);
        } else {
            $bounds[] = null;
        }

        if ($bounds[0] === null && $bounds[1] === null) {
            return;
        }

        if ($bounds[0] !== null && $bounds[1] !== null) {
            $query->whereBetween($column, $bounds);
        } elseif ($bounds[0] !== null) {
            $query->where($column, '>=', $bounds[0]);
        } else {
            $query->where($column, '<=', $bounds[1]);
        }
    }

    private function applyDateRange(Builder $query, Request $request, string $fromKey, string $toKey, string $column): void
    {
        $from = $request->filled($fromKey) ? $this->normalizeDate($request->get($fromKey)) : null;
        $to = $request->filled($toKey) ? $this->normalizeDate($request->get($toKey)) : null;

        if ($from === null && $to === null) {
            return;
        }

        if ($from !== null && $to !== null) {
            $query->whereBetween($column, [$from, $to]);
        } elseif ($from !== null) {
            $query->where($column, '>=', $from);
        } else {
            $query->where($column, '<=', $to);
        }
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }
        try {
            return Carbon::createFromFormat('!Y-m-d', $date)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

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

        $this->applyFilters($query, $request);

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

        // Owners list: only staff who actually own at least one (non-archived-aware) project.
        $owners = User::whereIn('id', Project::whereNotNull('owner_id')->distinct()->pluck('owner_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values();

        $statusInput = $request->input('statuses', []);
        $statusArray = is_array($statusInput)
            ? array_values(array_filter(array_map('strval', $statusInput)))
            : [];

        return Inertia::render('Admin/Projects/Index', [
            'projects' => new ProjectCollection($projects),
            'currentTab' => $tab,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'owners' => $owners,
            'filters' => [
                'search' => $request->search,
                'client_id' => $request->filled('client_id') ? (string) $request->integer('client_id') : ($request->filled('user_id') ? (string) $request->integer('user_id') : null),
                'owner_id' => $request->filled('owner_id') ? (string) $request->integer('owner_id') : null,
                'statuses' => $statusArray,
                'status_filter' => $statusFilter,
                'budget_min' => self::presentInput($request, 'budget_min'),
                'budget_max' => self::presentInput($request, 'budget_max'),
                'percent_min' => self::presentInput($request, 'percent_min'),
                'percent_max' => self::presentInput($request, 'percent_max'),
                'start_from' => self::presentInput($request, 'start_from'),
                'start_to' => self::presentInput($request, 'start_to'),
                'created_from' => self::presentInput($request, 'created_from'),
                'created_to' => self::presentInput($request, 'created_to'),
                'has_unpaid' => filter_var($request->get('has_unpaid'), FILTER_VALIDATE_BOOLEAN) ? '1' : null,
                'view' => in_array($request->get('view'), ['grid', 'table'], true) ? $request->get('view') : 'grid',
            ],
        ]);
    }

    private static function presentInput(Request $request, string $key): ?string
    {
        if (! $request->filled($key)) {
            return null;
        }

        return (string) $request->get($key);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Project::class);

        $initialClient = null;
        $clientParam = $request->filled('client_id') ? 'client_id' : ($request->filled('user_id') ? 'user_id' : null);
        if ($clientParam) {
            $client = User::find($request->integer($clientParam));
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

        $this->applyFilters($query, $request);

        $sort = $request->get('sort', 'created_at');
        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (array_key_exists($sort, self::SORTABLE)) {
            $query->orderBy(self::SORTABLE[$sort], $dir);
        }

        $projects = $query->lazy(200);

        $filename = "projects_{$tab}_".now()->format('Ymd_His').'.csv';
        $columns = [
            'id', 'project_name', 'description', 'client', 'owner', 'status', 'archived',
            'date_start', 'date_end', 'budget', 'hour_rate', 'percentage',
            'cost', 'paid_invoices', 'pending_invoices',
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
            'date' => Carbon::today('Africa/Cairo')->toDateString(),
        ]);
    }

    public function board(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $date = $this->parseBoardDate($request->route('date'));

        $preferences = $this->boardService->getPreference($request->user(), $project);

        $cards = $this->boardService->cardsForDate($project, $date, applyFutureGating: false, preferences: $preferences);
        $categories = $this->boardService->categoriesFor($project);

        $project->loadCount(['tasks', 'reports', 'files']);

        $currency = $project->currencyRow();

        $shareUrl = URL::signedRoute('shared-board.show', [
            'token' => $project->share_token,
            'date' => $date->toDateString(),
        ]);

        $shortUrl = $this->shortUrlForBoardShare($project, $shareUrl, $request->user()?->id);

        $shareUrlEdit = URL::signedRoute('shared-board.show', [
            'token' => $project->share_token,
            'date' => $date->toDateString(),
            'mode' => 'edit',
        ]);

        $shortUrlEdit = $this->shortUrlForBoardShare($project, $shareUrlEdit, $request->user()?->id);

        $activeDates = ProjectBoardItem::where('project_id', $project->id)
            ->distinct()
            ->pluck('for_date')
            ->map(fn ($d) => is_string($d) ? $d : $d->toDateString())
            ->toArray();

        return Inertia::render('Admin/Projects/Board', [
            'project' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'description' => $project->description,
                'status' => $project->status,
                'share_token' => $project->share_token,
                'share_url' => $shareUrl,
                'short_url' => $shortUrl,
                'share_url_edit' => $shareUrlEdit,
                'short_url_edit' => $shortUrlEdit,
                'archived' => (bool) $project->archived,
                'budget' => (string) ($project->budget ?? 0),
                'cost' => (string) $project->costAmount(),
                'paid_invoices' => (string) $project->paidInvoicesAmount(),
                'pending_invoices' => (string) $project->pendingInvoicesAmount(),
                'total_paid' => (string) ($project->total_paid ?? 0),
                'hour_rate' => (string) ($project->hour_rate ?? 0),
                'percentage' => (float) ($project->percentage ?? 0),
                'date_start' => optional($project->date_start)->toDateString(),
                'date_end' => optional($project->date_end)->toDateString(),
                'client_name' => $project->client?->name,
                'owner_name' => $project->owner?->name,
                'currency' => $currency ? [
                    'id' => $currency->id,
                    'currency' => $currency->currency,
                    'symbol' => $currency->symbol,
                    'string_format' => $currency->string_format,
                ] : null,
                'counts' => [
                    'tasks' => $project->tasks_count,
                    'reports' => $project->reports_count,
                    'files' => $project->files_count,
                ],
            ],
            'date' => $date->toDateString(),
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
            'activeDates' => $activeDates,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Persist the viewer's preferred board view-mode + sort selection.
     * Used by the toolbar so the choice follows the user across devices/browsers
     * instead of living only in localStorage.
     */
    public function updateBoardPreferences(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $preferences = $this->boardService->setPreference(
            $request->user(),
            $project,
            $request->only(['view_mode', 'sort_by', 'sort_dir']),
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'preferences' => $preferences,
                'message' => __('general.board_preferences_saved'),
            ]);
        }

        return back()->with('success', __('general.board_preferences_saved'));
    }

    /**
     * Project Finance / Cost Analysis page.
     *
     * Shows the REAL, derivable numbers for a project (no cached "balance"):
     *   - Cost transactions (real spend)
     *   - Paid invoices (amount already collected)
     *   - Pending/unpaid invoices (outstanding)
     *   - A currency-aware summary in the project's own currency.
     */
    public function finance(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'costTransactions' => fn ($q) => $q->latest('created_at'),
            'invoices' => fn ($q) => $q->latest('created_at'),
            'client',
            'owner',
        ]);

        $currency = $project->currencyRow();

        $costRows = $project->costTransactions->map(fn ($c) => [
            'id' => $c->id,
            'reason' => $c->reason,
            'amount' => (string) $c->amount,
            'currency_id' => $c->currency_id,
            'currency_code' => optional(Currency::find($c->currency_id))->currency,
            'business_amount' => (string) ($c->business_amount ?? 0),
            'created_at' => optional($c->created_at)->toIso8601String(),
        ])->values();

        $invoiceRows = $project->invoices->map(fn ($inv) => [
            'id' => $inv->id,
            'uuid' => $inv->uuid,
            'status' => $inv->status,
            'total' => (string) $inv->total(),
            'paid' => (string) $inv->paid,
            'unpaid' => (string) $inv->unpaid_total(),
            'currency_id' => $inv->currency_id,
            'currency_code' => optional(Currency::find($inv->currency_id))->currency,
            'created_at' => optional($inv->created_at)->toIso8601String(),
        ])->values();

        $businessCurrency = AdminSettings::business_currency();
        $businessCurrencyCode = optional(Currency::find($businessCurrency))->currency;

        return Inertia::render('Admin/Projects/Finance', [
            'project' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'description' => $project->description,
                'status' => $project->status,
                'archived' => (bool) $project->archived,
                'budget' => (string) ($project->budget ?? 0),
                'client_name' => $project->client?->name,
                'owner_name' => $project->owner?->name,
                'currency' => $currency ? [
                    'id' => $currency->id,
                    'currency' => $currency->currency,
                    'symbol' => $currency->symbol,
                    'string_format' => $currency->string_format,
                ] : null,
            ],
            'summary' => [
                'cost' => (string) $project->costAmount(),
                'paid_invoices' => (string) $project->paidInvoicesAmount(),
                'pending_invoices' => (string) $project->pendingInvoicesAmount(),
                'budget' => (string) ($project->budget ?? 0),
                'business_cost' => (string) $project->costTransactions()->sum('business_amount'),
                'business_currency_code' => $businessCurrencyCode,
            ],
            'costTransactions' => $costRows,
            'invoices' => $invoiceRows,
        ]);
    }

    private function parseBoardDate(?string $date): Carbon
    {
        if ($date) {
            try {
                return Carbon::createFromFormat('!Y-m-d', $date, 'Africa/Cairo');
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return Carbon::today('Africa/Cairo');
    }

    /**
     * Build (and cache) a short link for the shared-board signed URL.
     *
     * Dedupes on the destination URL so re-rendering the same date reuses the
     * existing short link rather than creating duplicates on every board view.
     * Returns null (gracefully) if the Shortlink module is disabled or fails,
     * so the board keeps working without the short URL.
     */
    protected function shortUrlForBoardShare(Project $project, string $shareUrl, ?int $userId): ?string
    {
        $serviceClass = ShortlinkService::class;

        if (! class_exists($serviceClass)) {
            return null;
        }

        try {
            $service = app($serviceClass);
            $link = $service->findOrCreateForDestination($shareUrl, [
                'label' => 'Project board: '.($project->project_name ?? 'Project'),
                'created_by_user_id' => $userId,
            ], $project);

            return $service->shortUrl($link);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
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

    public function listShares(Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $shares = $project->shares()->with('user:id,name,email')->get()->map(fn ($share) => [
            'id' => $share->id,
            'user_id' => $share->user_id,
            'name' => $share->user?->name,
            'email' => $share->user?->email,
            'can_edit' => $share->can_edit,
        ]);

        return response()->json($shares);
    }

    public function addShare(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $share = $project->shares()->updateOrCreate([
            'user_id' => $data['user_id']
        ], [
            'can_edit' => true
        ]);

        $share->load('user:id,name,email');

        return response()->json([
            'ok' => true,
            'share' => [
                'id' => $share->id,
                'user_id' => $share->user_id,
                'name' => $share->user?->name,
                'email' => $share->user?->email,
                'can_edit' => $share->can_edit,
            ]
        ]);
    }

    public function removeShare(Project $project, ProjectShare $share): JsonResponse
    {
        $this->authorize('update', $project);

        abort_unless($share->project_id === $project->id, 404);

        $share->delete();

        return response()->json(['ok' => true]);
    }
}
