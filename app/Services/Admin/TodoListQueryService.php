<?php

namespace App\Services\Admin;

use App\Models\Currency;
use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for the admin "Active Tasks" list query.
 *
 * Both the page and the stats use the same `scopeActive` base so the KPI
 * numbers can never drift from the visible rows. Orphan todos (no live task)
 * are counted separately and surfaced in their own bucket.
 */
class TodoListQueryService
{
    /** Allowed sort keys → list of [column, direction] tuples applied in order. */
    public const SORTS = [
        'created_desc' => [['created_at', 'desc'], ['id', 'desc']],
        'created_asc' => [['created_at', 'asc'],  ['id', 'asc']],
        'priority' => [['priority', 'asc'],   ['end_at', 'asc']],
        'due_asc' => [['end_at', 'asc'],     ['id', 'asc']],
        'due_desc' => [['end_at', 'desc'],    ['id', 'desc']],
        'client' => [['user_id', 'asc'],    ['id', 'asc']],
    ];

    /**
     * SQL fragment to push NULLs last in an ORDER BY clause.
     */
    public const NULLS_LAST = 'CASE WHEN %s IS NULL THEN 1 ELSE 0 END';

    /**
     * Parse a request's filter payload into a normalized array.
     * All keys are optional; missing / blank / invalid values fall back to safe defaults.
     */
    public function normalizeFilters(array $input): array
    {
        $allowedSorts = implode(',', array_keys(self::SORTS));

        // Validate the shape, but never fail on `sort` — it's lenient by design.
        $v = validator($input, [
            'search' => ['nullable', 'string', 'max:120'],
            'client_id' => ['nullable', 'integer'],
            'tenant_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'is_paid' => ['nullable', 'in:0,1'],
            'paused' => ['nullable', 'in:0,1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:'.$allowedSorts],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
        ]);
        // Drop the `sort` key from the bag if it's the only error.
        $errors = $v->errors();
        if ($errors->has('sort') && $errors->count() === 1) {
            $errors = new MessageBag;
        }
        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }

        $clientId = $input['client_id'] ?? $input['tenant_id'] ?? null;

        $blank = fn ($v) => ($v === null || $v === '' || (is_string($v) && trim($v) === '')) ? null : $v;

        return [
            'search' => $blank($input['search'] ?? null),
            'client_id' => $blank($clientId),
            'priority' => $blank($input['priority'] ?? null),
            'is_paid' => $blank($input['is_paid'] ?? null),
            'paused' => $blank($input['paused'] ?? null),
            'date_from' => $blank($input['date_from'] ?? null),
            'date_to' => $blank($input['date_to'] ?? null),
            'sort' => array_key_exists($input['sort'] ?? '', self::SORTS) ? $input['sort'] : 'created_desc',
            'per_page' => (int) ($input['per_page'] ?? 50),
        ];
    }

    /**
     * Apply the common filter set (search, client, priority, paid, paused, dates)
     * to any Todo query. Returns the builder for chaining.
     */
    public function applyFilters(Builder $query, array $f): Builder
    {
        if (! empty($f['client_id'])) {
            $clientId = (int) $f['client_id'];
            $query->where(function ($q) use ($clientId) {
                $q->where(function ($sq) use ($clientId) {
                    $sq->whereNull('project_id')
                        ->where('user_id', $clientId);
                })->orWhereHas('project', function ($pq) use ($clientId) {
                    $pq->where('user_id', $clientId);
                })->orWhereHas('task.project', function ($tpq) use ($clientId) {
                    $tpq->where('user_id', $clientId);
                });
            });
        }
        if (! empty($f['priority'])) {
            $query->where('priority', $f['priority']);
        }
        if ($f['is_paid'] !== null) {
            $query->where('is_paid', (bool) $f['is_paid']);
        }
        if ($f['paused'] !== null) {
            $query->where('paused', (bool) $f['paused']);
        }
        if (! empty($f['date_from'])) {
            $query->whereDate('end_at', '>=', $f['date_from']);
        }
        if (! empty($f['date_to'])) {
            $query->whereDate('end_at', '<=', $f['date_to']);
        }
        if (! empty($f['search'])) {
            $term = '%'.$f['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('project.client', fn ($cq) => $cq->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('task.project.client', fn ($cq) => $cq->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('task', fn ($tq) => $tq->where('task_name', 'like', $term));
            });
        }

        return $query;
    }

    public function applySort(Builder $query, string $sortKey): Builder
    {
        if ($sortKey === 'client') {
            $query->select('todos.*')
                ->leftJoin('projects', function ($join) {
                    $join->on('projects.id', '=', 'todos.project_id')
                        ->whereNull('projects.deleted_at');
                })
                ->leftJoin('tasks', function ($join) {
                    $join->on('tasks.id', '=', 'todos.task_id')
                        ->whereNull('tasks.deleted_at');
                })
                ->leftJoin('projects as task_projects', function ($join) {
                    $join->on('task_projects.id', '=', 'tasks.project_id')
                        ->whereNull('task_projects.deleted_at');
                })
                ->leftJoin('users as client_users', 'client_users.id', '=', DB::raw('COALESCE(projects.user_id, task_projects.user_id, todos.user_id)'))
                ->orderByRaw('CASE WHEN client_users.name IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('client_users.name', 'asc')
                ->orderByRaw('CASE WHEN COALESCE(projects.project_name, task_projects.project_name) IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy(DB::raw('COALESCE(projects.project_name, task_projects.project_name)'), 'asc')
                ->orderBy('todos.id', 'asc');

            return $query;
        }

        $sorts = self::SORTS[$sortKey] ?? self::SORTS['created_desc'];
        foreach ($sorts as [$col, $dir]) {
            $query->orderByRaw(sprintf(self::NULLS_LAST, $col).' ASC');
            $query->orderBy($col, $dir);
        }

        return $query;
    }

    /**
     * Base active query (incomplete + not paused). Includes orphans.
     * Orphan = no task OR task.archived = true.
     *
     * @param  bool  $liveOnly  if true, also requires task.archived = false
     */
    public function baseQuery(bool $liveOnly = false): Builder
    {
        $q = Todo::query()->active();
        if ($liveOnly) {
            $q->whereHas('task', fn ($t) => $t->where('archived', false));
        }

        return $q;
    }

    /**
     * Compute KPI numbers. The list total (`total_active_todos`) uses the
     * SAME scope as the paginated list so the two can never drift. The
     * board-only stat counts todos living on non-archived task boards.
     */
    public function computeStats(): array
    {
        $all = $this->baseQuery(liveOnly: false);
        $live = $this->baseQuery(liveOnly: true);

        $allProjectClients = Project::whereHas('todos', function ($q) {
            $q->active();
        })->distinct()->pluck('user_id');

        $allTaskProjectClients = Project::whereHas('tasks.task_todo_items', function ($q) {
            $q->active();
        })->distinct()->pluck('user_id');

        $allOrphanClients = Todo::query()->active()
            ->whereNull('project_id')
            ->distinct()
            ->pluck('user_id');

        $totalActiveClients = $allProjectClients
            ->merge($allTaskProjectClients)
            ->merge($allOrphanClients)
            ->unique()
            ->filter()
            ->count();

        return [
            'total_active_todos' => (clone $all)->count(),
            'total_in_boards' => (clone $live)->count(),
            'total_active_clients' => $totalActiveClients,
            'total_task_boards' => (clone $live)->distinct('task_id')->count('task_id'),
            'overdue_count' => (clone $all)
                ->whereNotNull('end_at')
                ->where('end_at', '<', now())
                ->count(),
            'orphan_count' => (clone $all)
                ->where(function ($q) {
                    $q->whereNull('task_id')
                        ->orWhereDoesntHave('task', fn ($t) => $t->where('archived', false));
                })
                ->count(),
        ];
    }

    /**
     * Paginated, filtered, eager-loaded list query for the page.
     */
    public function paginate(array $f, int $perPage): LengthAwarePaginator
    {
        $with = [
            'task.project.client',
            'project.client',
            'user',
            'children' => fn ($q) => $q->orderBy('sort_index')->orderBy('id'),
        ];
        if (Schema::hasColumn('todos', 'currency_id')) {
            $with['currency'] = fn ($q) => $q;
        }

        $query = $this->baseQuery(liveOnly: false)->with($with);
        $this->applyFilters($query, $f);
        $this->applySort($query, $f['sort']);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Build the client → task → todo nested structure used by the Inertia view.
     * Orphan todos (no live task) are grouped under a synthetic "Orphaned Todos"
     * bucket per client so the stat `total_active_todos` matches the list.
     *
     * @return array<int, array{client: array, tasks: array}>
     */
    public function arrange(LengthAwarePaginator $paginator): array
    {
        $now = now();
        $data = [];
        $currencies = $this->preloadCurrencies($paginator->getCollection());

        foreach ($paginator->getCollection() as $todo) {
            $client = null;
            if ($todo->project && $todo->project->client) {
                $client = $todo->project->client;
            } elseif ($todo->task && $todo->task->project && $todo->task->project->client) {
                $client = $todo->task->project->client;
            } else {
                $client = $todo->user;
            }

            if (! $client) {
                continue;
            }

            $project = $todo->project;
            if (! $project && $todo->task && $todo->task->project) {
                $project = $todo->task->project;
            }

            $userId = $client->id;

            if (! isset($data[$userId])) {
                $data[$userId] = [
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'avatar_url' => $client->avatar_url ?: null,
                    ],
                    'tasks' => [],
                ];
            }

            $bucketKey = $project ? ('project-'.$project->id) : 'no-project';

            if (! isset($data[$userId]['tasks'][$bucketKey])) {
                $data[$userId]['tasks'][$bucketKey] = ! $project
                    ? [
                        'id' => null,
                        'task_name' => __('general.orphaned_todos'),
                        'status' => 'orphaned',
                        'is_orphan' => true,
                        'todos' => [],
                    ]
                    : [
                        'id' => $project->id,
                        'task_name' => $project->project_name,
                        'status' => $project->status ?? 'open',
                        'is_orphan' => false,
                        'todos' => [],
                    ];
            }

            $data[$userId]['tasks'][$bucketKey]['todos'][] = $this->serializeTodo($todo, $client, $currencies, $now);
        }

        $arranged = array_values($data);
        foreach ($arranged as &$group) {
            $group['tasks'] = array_values($group['tasks']);
        }
        unset($group);

        return $arranged;
    }

    protected function preloadCurrencies(Collection $todos): array
    {
        if (! Schema::hasColumn('todos', 'currency_id')) {
            return [];
        }
        $ids = $todos->pluck('currency_id')->filter()->unique()->all();
        if (empty($ids)) {
            return [];
        }

        return Currency::whereIn('id', $ids)->get()->keyBy('id')->all();
    }

    /**
     * The tasks table has no status column, so we derive one from completion
     * of the in-page todo: open (this todo incomplete) or completed
     * (this todo complete). This stays cheap and accurate.
     */
    protected function deriveStatus(Task $task, Todo $todo): string
    {
        return $todo->completed ? 'completed' : 'open';
    }

    protected function serializeTodo(Todo $todo, User $client, array $currencies, Carbon $now): array
    {
        $tags = [];
        if ($todo->tags) {
            $tags = is_array($todo->tags)
                ? $todo->tags
                : (json_decode((string) $todo->tags, true) ?? []);
        }

        $currencyId = Schema::hasColumn('todos', 'currency_id')
            ? $todo->currency_id
            : null;
        $cost = Schema::hasColumn('todos', 'cost')
            ? $todo->cost
            : null;
        $isPaid = Schema::hasColumn('todos', 'is_paid')
            ? (bool) $todo->is_paid
            : false;

        $currency = $currencyId && isset($currencies[$currencyId])
            ? $currencies[$currencyId]
            : null;

        $isOrphan = ! $todo->task || (bool) $todo->task->archived;

        return [
            'id' => $todo->id,
            'task_id' => $todo->task_id,
            'project_id' => $todo->project_id,
            'in_date' => $todo->inDate ? Carbon::parse($todo->inDate)->toDateString() : null,
            'title' => $todo->title,
            'description' => $todo->description,
            'priority' => $todo->priority ?? 'normal',
            'priority_color' => $todo->priorityColor,
            'paused' => (bool) $todo->paused,
            'is_paid' => $isPaid,
            'cost' => $cost !== null ? (float) $cost : null,
            'cost_currency' => $currency?->currency ?? $client->currency_name() ?? null,
            'cost_currency_id' => $currencyId,
            'start_at' => $todo->start_at ? Carbon::parse($todo->start_at)->toISOString() : null,
            'end_at' => $todo->end_at ? Carbon::parse($todo->end_at)->toISOString() : null,
            'tags' => $tags,
            'created_at' => $todo->created_at?->toISOString(),
            'is_orphan' => $isOrphan,
            'is_overdue' => $todo->end_at ? Carbon::parse($todo->end_at)->lt($now) : false,
            'stale' => $todo->created_at ? $todo->created_at->lt($now->copy()->subDays(7)) : false,
            'task_name' => $todo->task?->task_name,
        ];
    }
}
