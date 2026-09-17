<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FinanceHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SyncTodoToGoogleCalendar;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTimer;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\ProjectReport;
use App\Models\ProjectFile;
use App\Models\RecurringBusyTime;
use App\Models\Task;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use App\Services\Admin\TodoListQueryService;
use App\Services\WhatsAppNotificationService;
use App\Models\TaskAuditLog;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminTaskController extends Controller
{
    /**
     * Recursively sanitize all string values in a data structure to valid UTF-8.
     * This prevents "Malformed UTF-8 characters" errors during JSON serialization (Inertia).
     */
    private function sanitizeUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            // Convert to UTF-8, replacing any invalid sequences
            $cleaned = mb_convert_encoding($data, 'UTF-8', 'UTF-8');

            // Remove any remaining invalid UTF-8 bytes
            return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $cleaned) ?? $data;
        }

        if (is_array($data)) {
            return array_map(fn ($item) => $this->sanitizeUtf8($item), $data);
        }

        if ($data instanceof Collection) {
            return $data->map(fn ($item) => $this->sanitizeUtf8($item));
        }

        return $data;
    }

    /**
     * Calculate client hourly rate in EGP, accounting for booking rate overrides,
     * subscriptions/plans discounts, and referral commissions.
     */
    private function getClientHourlyRateInEgp(User $client): float
    {
        $rateEgp = FinanceHelper::calculateOverheadHourlyRate();
        if ((float) $client->booking_rate > 0 && ($client->booking_rate_expires_at === null || now('Africa/Cairo')->startOfDay()->lte(Carbon::parse($client->booking_rate_expires_at)->startOfDay()))) {
            $rateEgp = (float) CurrenciesExchange::RateToday($client->booking_rate, $client->booking_rate_currency_id ?? $client->currency ?? 1, 2);
        } elseif ($client->hasSubscription() && $client->plan) {
            $rateEgp = $client->plan->calcDiscount((float) $rateEgp);
        }
        $fh = FinanceHelper::instance();
        $baseCost = $fh->price_fixer($rateEgp, 2);
        $commission = 0;
        if ($client->ref_user && $client->ref_user->shouldAddCommissionToTotal()) {
            $commission = $client->ref_user->calculateCommissionAmount($baseCost, 2, $client);
        }
        return (float) $fh->price_fixer($baseCost + $commission, 2);
    }

    /**
     * Display a listing of active checklist items for all clients.
     */
    public function asList(Request $request, TodoListQueryService $svc): InertiaResponse
    {
        $filters = $svc->normalizeFilters($request->all());
        $paginator = $svc->paginate($filters, $filters['per_page']);
        $stats = $svc->computeStats();

        $clients = User::role('client')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]);

        $stats['total_clients'] = $clients->count();

        return Inertia::render('Admin/Tasks/AsList', $this->sanitizeUtf8([
            'arrangedClients' => $svc->arrange($paginator),
            'clients' => $clients,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => $stats,
        ]));
    }

    /**
     * Bulk-complete (or un-complete) a set of platform todos. Used by the
     * admin "Active Tasks" list. Validates ownership via the active scope.
     */
    public function bulkCompleteTodos(Request $request)
    {
        $data = $request->validate([
            'todo_ids' => ['required', 'array', 'min:1', 'max:500'],
            'todo_ids.*' => ['integer', 'exists:todos,id'],
            'completed' => ['required', 'boolean'],
        ]);

        $now = now();
        $affected = Todo::query()
            ->active()
            ->whereIn('id', $data['todo_ids'])
            ->update([
                'completed' => (bool) $data['completed'],
                'completed_at' => $data['completed'] ? $now : null,
                'updated_at' => $now,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => __('general.task_status_updated_successfully'),
            'affected' => $affected,
        ]);
    }

    /**
     * Export the current filtered view of the Active Tasks list to CSV.
     * Reuses the same filters as asList() — search, client, priority, paid,
     * paused, date_from, date_to, sort. Uses the shared service so behavior
     * stays in lock-step with the on-screen list.
     */
    public function exportAsList(Request $request, TodoListQueryService $svc)
    {
        $filters = $svc->normalizeFilters($request->all());

        $query = $svc->baseQuery(liveOnly: false)->with([
            'task.project.client',
            'project.client',
            'user',
        ]);
        if (Schema::hasColumn('todos', 'currency_id')) {
            $query->with('currency');
        }
        $svc->applyFilters($query, $filters);
        $svc->applySort($query, $filters['sort']);

        $filename = 'active-tasks-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Todo ID', 'Title', 'Description', 'Priority', 'Paused', 'Paid',
                'Cost', 'Currency', 'Start At', 'End At', 'Created At',
                'Client ID', 'Client Name', 'Client Email',
                'Task ID', 'Task Name', 'Is Orphan',
            ]);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $t) {
                    $client = $t->project?->client ?? $t->task?->project?->client ?? $t->user;
                    fputcsv($out, [
                        $t->id,
                        $t->title,
                        $t->description,
                        $t->priority ?? 'normal',
                        $t->paused ? '1' : '0',
                        $t->is_paid ? '1' : '0',
                        $t->cost,
                        $t->currency?->currency ?? $client?->currency_name(),
                        $t->start_at,
                        $t->end_at,
                        $t->created_at?->toDateTimeString(),
                        $client?->id,
                        $client?->name,
                        $client?->email,
                        $t->task_id,
                        $t->task?->task_name,
                        $t->task ? '0' : '1',
                    ]);
                }
            });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mark a platform todo item as complete.
     */
    public function completeTodo(Request $request, Todo $todo)
    {
        $todo->completed = $request->boolean('completed');
        $todo->completed_at = $todo->completed ? now() : null;
        $todo->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('message', __('general.task_status_updated_successfully'));
    }

    /**
     * Store an unpaid todo in the queue.
     */
    public function storeUnpaidTodo(Request $request, User $client)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo = new Todo;
        $todo->title = $request->title;
        $todo->user_id = $client->id;
        if (Schema::hasColumn('todos', 'tenant_id')) {
            $todo->tenant_id = session('tenant_id');
        }
        $todo->is_paid = false;
        $todo->completed = false;
        $todo->inDate = now('Africa/Cairo')->format('M d, Y H:i:s');
        $todo->priority = 'normal';
        $todo->priorityColor = '#11cdef';
        $todo->tags = []; // casts automatically to json because of $casts array in Todo model

        // Use client's calculated hourly rate in EGP
        $finalCostEgp = $this->getClientHourlyRateInEgp($client);
        $todo->cost = $finalCostEgp;
        $todo->currency_id = 2; // Stored in base EGP

        $todo->save();

        return redirect()->back()->with('message', __('general.task_added_to_the_queue'));
    }

    /**
     * Delete an unpaid todo.
     */
    public function destroyTodo(Todo $todo)
    {
        if ($todo->is_paid) {
            return redirect()->back()->withErrors(['error' => 'Paid tasks cannot be deleted, they must be refunded.']);
        }

        try {
            $adminUser = auth()->user() ?? User::role('super-admin')->first();
            if ($adminUser && class_exists(SyncTodoToGoogleCalendar::class)) {
                SyncTodoToGoogleCalendar::dispatch($todo, $adminUser, 'delete');
            }
        } catch (\Throwable $e) {
        }

        $todo->delete();

        return redirect()->back()->with('message', __('general.task_removed_from_the_queue'));
    }

    /**
     * Pay and schedule an unpaid todo.
     */
    public function payAndScheduleTodo(Todo $todo)
    {
        $client = $todo->user;
        if (! $client || $todo->is_paid) {
            return back()->withErrors(['error' => 'Invalid task.']);
        }

        $userCurrencyId = (int) $client->currency;
        $amountInUserCurrency = CurrenciesExchange::RateToday((float) $todo->cost, $todo->currency_id ?? 2, $userCurrencyId);

        if ($client->available_balance() < $amountInUserCurrency) {
            return back()->withErrors(['error' => 'Client has insufficient balance ('.FinanceHelper::instance()->format_money($client->available_balance(), $client->currency).' available).']);
        }

        try {
            return Cache::lock('client_focus_calendar_slot', 30)->block(10, function () use ($todo, $client, $userCurrencyId, $amountInUserCurrency) {

                $todo->refresh();
                if ($todo->is_paid) {
                    return back()->withErrors(['error' => 'Task is already paid.']);
                }

                if ($todo->start_at && $todo->end_at) {
                    $start = Carbon::parse($todo->start_at, 'Africa/Cairo');
                    $end = Carbon::parse($todo->end_at, 'Africa/Cairo');
                    if (Todo::focusCalendarSlotTaken($start, $end, $todo->id)) {
                        return back()->withErrors(['error' => 'This time slot overlaps with an existing booking.']);
                    }
                }

                DB::beginTransaction();
                try {
                    $todo->is_paid = true;
                    $todo->save();

                    $invoice = new Invoice;
                    $invoice->user_id = $client->id;
                    $invoice->currency = $userCurrencyId;
                    $invoice->project_id = $todo->task ? $todo->task->project_id : null;
                    $invoice->status = 'unpaid';
                    $invoice->schedule = [
                        'start_date' => $todo->start_at,
                        'end_date' => $todo->end_at,
                    ];
                    $client->invoices()->save($invoice);

                    $item = new InvoiceItem;
                    $item->item_title = Str::limit($todo->title, 255);
                    $item->amount = 0;
                    $item->qty = 1;
                    $item->item_type = 'timer';
                    $invoice->items()->save($item);

                    if ($todo->start_at && $todo->end_at) {
                        $timer = new InvoiceItemTimer;
                        $timer->invoice_item_id = $item->id;
                        $timer->user_id = $client->id;
                        $timer->project_id = $invoice->project_id;
                        $timer->date_start = $todo->start_at;
                        $timer->date_end = $todo->end_at;
                        $timer->amount = round($amountInUserCurrency, 2);
                        $timer->currency_id = $invoice->currency;
                        $item->timers()->save($timer);
                    } else {
                        // Just an item without a timer if no start/end
                        $item->amount = round($amountInUserCurrency, 2);
                        $item->save();
                    }

                    $invoice->unpaid = $invoice->total();
                    $invoice->save();
                    $invoice->bill_invoice();

                    DB::commit();

                    try {
                        if ($todo->start_at && $todo->end_at) {
                            $adminUser = auth()->user() ?? User::role('super-admin')->first();
                            if ($adminUser && class_exists(SyncTodoToGoogleCalendar::class)) {
                                SyncTodoToGoogleCalendar::dispatch($todo, $adminUser, 'create');
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Google Calendar sync failed: '.$e->getMessage());
                    }

                    return redirect()->back()->with('message', __('general.task_scheduled_and_billed_successfully'));

                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }
            });
        } catch (LockTimeoutException $e) {
            return back()->withErrors(['error' => 'Booking system is currently busy. Please try again.']);
        } catch (\Throwable $e) {
            Log::error('Admin payAndScheduleTodo failed: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to process payment for task.']);
        }
    }

    /**
     * Display a calendar showing all tasks, scheduled todos, and busy times.
     */
    public function calendar(Request $request): InertiaResponse
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('n'));
        $clientId = $request->get('client_id') ?? $request->get('tenant_id');

        $tz = 'Africa/Cairo';

        // Build the visible date range pinned to the user-facing timezone so
        // server- and client-side date strings agree regardless of php.ini.
        $startOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $startDate = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endDate = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $eventType = $request->get('event_type'); // all|tasks|todos|busy

        // 1. Scheduled todos in range. Eager-load currency & task to avoid
        //    N+1s when serializing the Inertia payload.
        $todosQuery = Todo::whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_at', [$startDate, $endDate])
                    ->orWhereBetween('end_at', [$startDate, $endDate]);
            })
            ->with(['task', 'user']);

        if ($clientId) {
            $todosQuery->where('user_id', $clientId);
        }
        $todos = $todosQuery->get();

        // 2. Tasks due in this range. Use a subquery for completion so we
        //    don't lazy-load `task_todo_items` for every row (N+1 fix).
        $tasksQuery = Task::whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with(['user'])
            ->withCount([
                'task_todo_items as all_todos_count',
                'task_todo_items as open_todos_count' => fn ($q) => $q->where('completed', false),
            ]);

        if ($clientId) {
            $tasksQuery->where('user_id', $clientId);
        }
        $tasks = $tasksQuery->get();

        // 3. Recurring & specific busy times (cached briefly).
        $busyTimes = Cache::remember(
            'admin:calendar:active-busy-times',
            now()->addMinutes(5),
            fn () => RecurringBusyTime::where('is_active', true)->get()
        );

        // Pre-fill the events map and expand busy times in a SINGLE pass.
        $events = [];
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()
        );
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $events[$dateStr] = [
                'tasks' => [],
                'todos' => [],
                'busy_times' => [],
            ];

            $dayOfWeek = $date->format('l');
            foreach ($busyTimes as $bt) {
                $matches = false;
                if ($bt->is_recurring && strcasecmp($bt->day_of_week, $dayOfWeek) === 0) {
                    $matches = true;
                } elseif (! $bt->is_recurring && $bt->specific_date && $bt->specific_date->format('Y-m-d') === $dateStr) {
                    $matches = true;
                }
                if ($matches && (! $clientId || (int) $bt->user_id === (int) $clientId || $bt->user_id === null)) {
                    $events[$dateStr]['busy_times'][] = [
                        'id' => 'busy-'.$bt->id,
                        'title' => $bt->reason ?: __('general.busy'),
                        'is_full_day' => (bool) $bt->is_full_day,
                        'start_time' => $bt->start_time ? Carbon::parse($bt->start_time)->format('H:i') : null,
                        'end_time' => $bt->end_time ? Carbon::parse($bt->end_time)->format('H:i') : null,
                    ];
                }
            }
        }

        // Tasks
        if ($eventType === null || $eventType === 'all' || $eventType === 'tasks') {
            foreach ($tasks as $task) {
                $dateStr = Carbon::parse($task->due_date, $tz)->format('Y-m-d');
                if (! isset($events[$dateStr])) {
                    continue;
                }
                $isDone = $task->all_todos_count > 0
                    ? ((int) $task->open_todos_count === 0)
                    : false;
                $events[$dateStr]['tasks'][] = [
                    'id' => $task->id,
                    'title' => $task->task_name,
                    'priority' => $task->priority ?? 'normal',
                    'completed' => $isDone,
                    'client_id' => $task->user_id,
                    'client' => $task->user?->name ?? __('general.unknown'),
                ];
            }
        }

        // Todos
        if ($eventType === null || $eventType === 'all' || $eventType === 'todos') {
            foreach ($todos as $todo) {
                $dateStr = Carbon::parse($todo->start_at, $tz)->format('Y-m-d');
                if (! isset($events[$dateStr])) {
                    continue;
                }
                $events[$dateStr]['todos'][] = [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'priority' => $todo->priority ?? 'normal',
                    'priority_color' => $todo->priorityColor,
                    'completed' => (bool) $todo->completed,
                    'task_id' => $todo->task_id,
                    'client_id' => $todo->user_id,
                    'client' => $todo->user?->name ?? __('general.unknown'),
                    'start_time' => Carbon::parse($todo->start_at, $tz)->format('H:i'),
                    'end_time' => $todo->end_at ? Carbon::parse($todo->end_at, $tz)->format('H:i') : null,
                ];
            }
        }

        $clients = User::role('client')->orderBy('name')->get(['id', 'name'])->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ]);

        // Quick stats for the calendar header
        $stats = [
            'todos_this_month' => $todos->count(),
            'tasks_this_month' => $tasks->count(),
            'busy_days' => collect($events)->filter(
                fn ($d) => count($d['busy_times']) > 0
            )->count(),
        ];

        return Inertia::render('Admin/Tasks/TaskCalendar', $this->sanitizeUtf8([
            'events' => $events,
            'year' => $year,
            'month' => $month,
            'tz' => $tz,
            'clients' => $clients,
            'stats' => $stats,
            'filters' => [
                'client_id' => $clientId,
                'event_type' => $eventType ?? 'all',
            ],
        ]));
    }

    /**
     * Display client focus board and checklist items queue for a specific client.
     */
    public function clientTasks(Request $request): InertiaResponse
    {
        $clientId = $request->get('client_id') ?? $request->get('tenant_id');
        $search = $request->get('search');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $clients = [];
        $pagination = null;

        if (! $clientId) {
            // Fetch all platform clients with task/todo metrics, sorted by id ASC default
            // Unless direction is specified (following /admin-sorting-rules)
            $clientsQuery = User::role('client')
                ->selectRaw('users.id, users.name, users.email')
                ->selectRaw('(SELECT COUNT(*) FROM todos WHERE todos.user_id = users.id) as total_tasks')
                ->selectRaw('(SELECT COUNT(*) FROM todos WHERE todos.user_id = users.id AND todos.completed = 1) as completed_tasks');

            if ($search) {
                $clientsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('id', $search);
                });
            }

            $paginator = $clientsQuery->orderBy('users.id', $direction)->paginate(12)->withQueryString();
            
            $clients = collect($paginator->items())->map(function ($u) {
                // Get initials (multibyte-safe for Arabic/non-ASCII names)
                $words = explode(' ', $u->name);
                $initials = '';
                foreach ($words as $w) {
                    if (mb_strlen($w, 'UTF-8') > 0) {
                        $initials .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
                    }
                }
                $initials = mb_substr($initials, 0, 2, 'UTF-8');

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'initials' => $initials ?: 'C',
                    'total_tasks' => (int) $u->total_tasks,
                    'completed_tasks' => (int) $u->completed_tasks,
                ];
            });

            $pagination = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ];
        }

        $selectedClient = null;
        $todos = [];

        if ($clientId) {
            $clientUser = User::findOrFail($clientId);
            $totalTasks = Todo::where('user_id', $clientUser->id)->count();
            $completedTasks = Todo::where('user_id', $clientUser->id)->where('completed', true)->count();
            $latestTask = Task::where('user_id', $clientUser->id)->latest()->first();

            $finalCostEgp = $this->getClientHourlyRateInEgp($clientUser);
            $hourlyRateInUserCurrency = CurrenciesExchange::RateToday((float) $finalCostEgp, 2, (int) $clientUser->currency);

            $selectedClient = [
                'id' => $clientUser->id,
                'name' => $clientUser->name,
                'email' => $clientUser->email,
                'balance' => (float) $clientUser->available_balance(),
                'currency' => $clientUser->currency_name(),
                'hourly_rate' => round($hourlyRateInUserCurrency, 2),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'latest_task_id' => $latestTask ? $latestTask->id : null,
            ];

            $todos = Todo::where('user_id', $clientUser->id)
                ->where('completed', false)
                ->with('task')
                ->orderBy('start_at', 'asc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($todo) use ($clientUser) {
                    $clientCurrency = $clientUser->currency_name();
                    $userCurrencyId = (int) $clientUser->currency;

                    // Convert EGP cost to client's currency for displaying on refund dialogs
                    $costInClientCurrency = (float) CurrenciesExchange::RateToday((float) $todo->cost, $todo->currency_id ?? 2, $userCurrencyId);

                    // show_refund logic
                    $invalidSlot = $todo->start_at === null || $todo->end_at === null;
                    if (! $invalidSlot) {
                        $start = Carbon::parse($todo->start_at);
                        $end = Carbon::parse($todo->end_at);
                        $invalidSlot = ! $end->gt($start);
                    }

                    $showRefund = false;
                    if ($todo->is_paid && ! $todo->refunded) {
                        if ($invalidSlot) {
                            $showRefund = true;
                        } elseif ($todo->end_at && Carbon::parse($todo->end_at, 'Africa/Cairo')->isFuture()) {
                            $showRefund = true;
                        }
                    }

                    return [
                        'id' => $todo->id,
                        'task_id' => $todo->task_id,
                        'task_name' => $todo->task?->task_name ?? 'My Focus',
                        'title' => $todo->title,
                        'description' => $todo->description,
                        'completed' => (bool) $todo->completed,
                        'paused' => (bool) $todo->paused,
                        'is_paid' => (bool) $todo->is_paid,
                        'cost' => (float) $todo->cost,
                        'cost_in_client_currency' => $costInClientCurrency,
                        'client_currency' => $clientCurrency,
                        'start_at' => $todo->start_at ? Carbon::parse($todo->start_at)->toISOString() : null,
                        'end_at' => $todo->end_at ? Carbon::parse($todo->end_at)->toISOString() : null,
                        'refunded' => (bool) $todo->refunded,
                        'refund_amount' => (float) $todo->refund_amount,
                        'show_refund' => $showRefund,
                    ];
                });
        }

        return Inertia::render('Admin/Tasks/ClientTasks', $this->sanitizeUtf8([
            'clients' => $clients,
            'selectedClient' => $selectedClient,
            'todos' => $todos,
            'pagination' => $pagination,
            'filters' => [
                'client_id' => $clientId,
                'search' => $search,
                'direction' => $direction,
            ],
        ]));
    }

    /**
     * Refund remaining time for a paid todo that has not finished yet.
     */
    public function refundTodo(Request $request, Todo $todo)
    {
        $user = $todo->user;
        if (! $todo->is_paid || $todo->refunded || ! $user) {
            return redirect()->back()->withErrors(['error' => 'This item cannot be refunded.']);
        }

        $now = now('Africa/Cairo');
        $invalidSlot = $todo->start_at === null || $todo->end_at === null;
        if (! $invalidSlot) {
            $start = Carbon::parse($todo->start_at, 'Africa/Cairo');
            $end = Carbon::parse($todo->end_at, 'Africa/Cairo');
            $invalidSlot = ! $end->gt($start);
        } else {
            $start = null;
            $end = null;
        }

        if ($invalidSlot) {
            $this->refundInvalidSlotTodo($todo, $user);

            return redirect()->back()->with('message', __('general.refund_processed_successfully'));
        }

        if ($now->gte($end)) {
            return redirect()->back()->withErrors(['error' => 'This booking slot has already fully elapsed.']);
        }

        // Use seconds so sub-minute slots are not misclassified (diffInMinutes can be 0)
        $totalSeconds = $start->diffInSeconds($end);
        if ($totalSeconds <= 0) {
            $this->refundInvalidSlotTodo($todo, $user);

            return redirect()->back()->with('message', __('general.refund_processed_successfully'));
        }

        // Only count time from when the slot actually starts (not "now" if the slot is in the future)
        $effectiveStart = $now->gt($start) ? $now : $start;
        $remainingSeconds = max(0, $effectiveStart->diffInSeconds($end));
        $refundFraction = min(1.0, $remainingSeconds / $totalSeconds);
        $refundAmount = (float) min(
            (float) $todo->cost,
            round((float) $todo->cost * $refundFraction, 2)
        );

        if ($refundAmount <= 0) {
            return redirect()->back()->withErrors(['error' => 'No remaining refundable duration in this slot.']);
        }

        try {
            DB::beginTransaction();
            $project = $todo->task ? $todo->task->project : null;

            // Refund user balance
            $reason = 'Refund for todo: '.Str::limit($todo->title, 80).' [todo_time_refund]';
            $user->add_balance(
                $refundAmount,
                $reason,
                'earned',
                (int) $todo->currency_id,
                $project
            );

            // SYNC InvoiceItemTimer if it exists
            $oldEnd = Carbon::parse($todo->end_at)->toDateTimeString();
            $timerRecord = InvoiceItemTimer::where('user_id', $user->id)
                ->where('date_start', Carbon::parse($todo->start_at)->toDateTimeString())
                ->where('date_end', $oldEnd)
                ->whereHas('invoiceItem', function ($q) use ($todo) {
                    $q->where('item_title', Str::limit($todo->title, 255));
                })
                ->first();

            // Collapse the booking to "used" portion
            $todo->refunded = true;
            $todo->refunded_at = now();
            $todo->refund_amount = $refundAmount;
            $todo->end_at = ($now->lt($start) ? $start : $now)->toDateTimeString();
            $usedFraction = 1 - $refundFraction;
            $todo->cost = round((float) $todo->cost * $usedFraction, 2);
            $todo->save();

            if ($timerRecord) {
                $timerRecord->date_end = $todo->end_at;
                $timerRecord->amount = round((float) $timerRecord->amount * $usedFraction, 2);
                $timerRecord->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Todo refund remaining time failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to process refund.']);
        }

        // WhatsApp notification if service exists
        try {
            if (class_exists('App\Services\WhatsAppNotificationService')) {
                $whatsApp = app(WhatsAppNotificationService::class);
                $message = $whatsApp->generateRefundMessage($user, $todo, $refundAmount);
                $whatsApp->sendMessage($user, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp refund notification failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);
        }

        return redirect()->back()->with('message', __('general.refund_processed_successfully'));
    }

    /**
     * Full refund for paid todos with an unusable window, then remove the todo.
     */
    protected function refundInvalidSlotTodo(Todo $todo, $user): void
    {
        $refundAmount = round((float) $todo->cost, 2);
        $refundReason = 'Refund for todo: '.Str::limit($todo->title, 80).' [todo_time_refund]';

        try {
            DB::beginTransaction();
            $project = $todo->task ? $todo->task->project : null;

            if ($refundAmount > 0) {
                $user->add_balance(
                    $refundAmount,
                    $refundReason,
                    'earned',
                    (int) $todo->currency_id,
                    $project
                );
            }

            // Sync timer delete
            $oldEnd = $todo->end_at ? Carbon::parse($todo->end_at)->toDateTimeString() : null;
            $dateStartStr = $todo->start_at ? Carbon::parse($todo->start_at)->toDateTimeString() : null;
            if ($dateStartStr !== null && $oldEnd !== null) {
                $timerRecord = InvoiceItemTimer::where('user_id', $user->id)
                    ->where('date_start', $dateStartStr)
                    ->where('date_end', $oldEnd)
                    ->whereHas('invoiceItem', function ($q) use ($todo) {
                        $q->where('item_title', Str::limit($todo->title, 255));
                    })
                    ->first();
                if ($timerRecord) {
                    $timerRecord->delete();
                }
            }

            // Delete todo and children
            foreach ($todo->children as $child) {
                $child->delete();
            }

            try {
                $adminUser = auth()->user() ?? User::role('super-admin')->first();
                if ($adminUser && class_exists(SyncTodoToGoogleCalendar::class)) {
                    SyncTodoToGoogleCalendar::dispatch($todo, $adminUser, 'delete');
                }
            } catch (\Throwable $e) {
            }

            $todo->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Todo refund invalid slot failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);
            throw $e;
        }

        // WhatsApp notification if service exists
        if ($refundAmount > 0) {
            try {
                if (class_exists('App\Services\WhatsAppNotificationService')) {
                    $whatsApp = app(WhatsAppNotificationService::class);
                    $message = $whatsApp->generateRefundMessage($user, $todo, $refundAmount);
                    $whatsApp->sendMessage($user, $message);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp refund notification failed: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'todo_id' => $todo->id,
                ]);
            }
        }
    }

    /**
     * Schedule a todo item for a client.
     * Checks for slot conflicts before saving.
     */
    public function scheduleTodo(Request $request, Todo $todo)
    {
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $start = Carbon::parse($request->start_at, 'Africa/Cairo');
        $end = Carbon::parse($request->end_at, 'Africa/Cairo');

        // Check for conflicting bookings (exclude this todo from the check)
        if (Todo::focusCalendarSlotTaken($start, $end, $todo->id)) {
            return back()->withErrors([
                'start_at' => 'This time slot overlaps with an existing booking. Please choose a different time.',
            ]);
        }

        $todo->start_at = $start->toDateTimeString();
        $todo->end_at = $end->toDateTimeString();
        $todo->save();

        return redirect()->back()->with('message', __('general.time_scheduled_successfully'));
    }

    /**
     * Create and schedule a paid todo item for a client, deducting from their balance.
     */
    public function storeClientTodo(Request $request, User $client)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $start = Carbon::parse($request->start_at, 'Africa/Cairo');
        $end = Carbon::parse($request->end_at, 'Africa/Cairo');

        if (Todo::focusCalendarSlotTaken($start, $end)) {
            return back()->withErrors(['start_at' => 'This time slot overlaps with an existing booking.']);
        }

        try {
            return Cache::lock('client_focus_calendar_slot', 30)->block(10, function () use ($request, $client, $start, $end) {

                if (Todo::focusCalendarSlotTaken($start, $end)) {
                    return back()->withErrors(['start_at' => 'This time slot overlaps with an existing booking.']);
                }

                $durationHours = $end->diffInMinutes($start) / 60;

                $currencyId = 2;
                $finalCostEgp = $this->getClientHourlyRateInEgp($client);
                $finalCost = round($finalCostEgp * $durationHours, 2);

                $amountInUserCurrency = CurrenciesExchange::RateToday((float) $finalCost, $currencyId, (int) $client->currency);

                if ($client->available_balance() < $amountInUserCurrency) {
                    return back()->withErrors(['error' => 'Client has insufficient balance ('.FinanceHelper::instance()->format_money($client->available_balance(), $client->currency).' available, '.FinanceHelper::instance()->format_money($amountInUserCurrency, $client->currency).' required).']);
                }

                DB::beginTransaction();
                try {
                    $task = Task::firstOrCreate(
                        ['user_id' => $client->id, 'task_name' => 'My Focus'],
                        ['task_description' => 'Default task list for your focus items', 'shared_with_admin' => '0']
                    );

                    $todo = $task->task_todo_items()->create([
                        'user_id' => $client->id,
                        'title' => $request->title,
                        'description' => $request->description,
                        'completed' => false,
                        'inDate' => now('Africa/Cairo')->format('M d, Y H:i:s'),
                        'start_at' => $start->toDateTimeString(),
                        'end_at' => $end->toDateTimeString(),
                        'cost' => $finalCost,
                        'currency_id' => $currencyId,
                        'is_paid' => true,
                        'priority' => 'normal',
                        'priorityColor' => '#11cdef',
                        'tags' => '[]',
                    ]);

                    $invoice = new Invoice;
                    $invoice->user_id = $client->id;
                    $invoice->currency = (int) $client->currency;
                    $invoice->project_id = $task->project_id;
                    $invoice->status = 'unpaid';
                    $invoice->schedule = [
                        'start_date' => $todo->start_at,
                        'end_date' => $todo->end_at,
                    ];
                    $client->invoices()->save($invoice);

                    $item = new InvoiceItem;
                    $item->item_title = Str::limit($todo->title, 255);
                    $item->amount = 0;
                    $item->qty = 1;
                    $item->item_type = 'timer';
                    $invoice->items()->save($item);

                    $timer = new InvoiceItemTimer;
                    $timer->invoice_item_id = $item->id;
                    $timer->user_id = $client->id;
                    $timer->project_id = $task->project_id;
                    $timer->date_start = $start->toDateTimeString();
                    $timer->date_end = $end->toDateTimeString();
                    $timer->amount = round($amountInUserCurrency, 2);
                    $timer->currency_id = $invoice->currency;
                    $item->timers()->save($timer);

                    $invoice->unpaid = $invoice->total();
                    $invoice->save();

                    $invoice->bill_invoice();

                    DB::commit();

                    try {
                        $adminUser = auth()->user() ?? User::role('super-admin')->first();
                        if ($adminUser && class_exists(SyncTodoToGoogleCalendar::class)) {
                            SyncTodoToGoogleCalendar::dispatch($todo, $adminUser, 'create');
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Google Calendar sync failed: '.$e->getMessage());
                    }

                    return redirect()->back()->with('message', __('general.scheduled_task_created_and_billed_successfully'));

                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }

            });
        } catch (LockTimeoutException $e) {
            return back()->withErrors(['error' => 'Booking system is currently busy. Please try again.']);
        } catch (\Throwable $e) {
            Log::error('Admin storeClientTodo failed: '.$e->getMessage(), [
                'user_id' => $client->id,
            ]);

            return back()->withErrors(['error' => 'Failed to create and bill task.']);
        }
    }

    /**
     * Store and instantly bill a focus calendar Todo directly from the Admin Task Calendar.
     */
    public function storeAndBillCalendarTodo(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'checklist_items' => 'nullable|array',
            'checklist_items.*.title' => 'required|string|max:255',
        ]);

        $client = User::findOrFail($request->client_id);

        $startStr = $request->date.' '.$request->start_time.':00';
        $endStr = $request->date.' '.$request->end_time.':00';
        $start = Carbon::parse($startStr, 'Africa/Cairo');
        $end = Carbon::parse($endStr, 'Africa/Cairo');

        if (! $end->gt($start)) {
            return back()->withErrors(['error' => 'End time must be after start time.']);
        }

        if (Todo::focusCalendarSlotTaken($start, $end)) {
            return back()->withErrors(['error' => 'This time slot overlaps with an existing scheduled focus task.']);
        }

        $finalCostEgp = $this->getClientHourlyRateInEgp($client);
        $durationHours = $start->diffInMinutes($end) / 60;
        $totalCostEgp = $finalCostEgp * $durationHours;

        $userCurrencyId = (int) $client->currency;
        $amountInUserCurrency = CurrenciesExchange::RateToday((float) $totalCostEgp, 2, $userCurrencyId);

        if ($client->available_balance() < $amountInUserCurrency) {
            return back()->withErrors(['error' => 'Client has insufficient balance ('.FinanceHelper::instance()->format_money($client->available_balance(), $client->currency).' available, requires '.FinanceHelper::instance()->format_money($amountInUserCurrency, $client->currency).').']);
        }

        try {
            return Cache::lock('client_focus_calendar_slot', 30)->block(10, function () use ($request, $client, $start, $end, $userCurrencyId, $amountInUserCurrency, $totalCostEgp) {

                if (Todo::focusCalendarSlotTaken($start, $end)) {
                    return back()->withErrors(['error' => 'This time slot overlaps with an existing booking.']);
                }

                DB::beginTransaction();
                try {
                    $todo = new Todo;
                    $todo->title = $request->title;
                    $todo->user_id = $client->id;
                    if (Schema::hasColumn('todos', 'tenant_id')) {
                        $todo->tenant_id = session('tenant_id');
                    }
                    $todo->start_at = $start->toDateTimeString();
                    $todo->end_at = $end->toDateTimeString();
                    $todo->is_paid = true;
                    $todo->cost = round($totalCostEgp, 2);
                    $todo->currency_id = 2; // Stored in base EGP
                    $todo->save();

                    if (! empty($request->checklist_items)) {
                        foreach ($request->checklist_items as $itemData) {
                            $checklistItem = new TodoChecklistItem;
                            $checklistItem->todo_id = $todo->id;
                            $checklistItem->title = $itemData['title'];
                            $checklistItem->is_completed = false;
                            $checklistItem->save();
                        }
                    }

                    $invoice = new Invoice;
                    $invoice->user_id = $client->id;
                    $invoice->currency = $userCurrencyId;
                    $invoice->project_id = null;
                    $invoice->status = 'unpaid';
                    $invoice->schedule = [
                        'start_date' => $start->toDateTimeString(),
                        'end_date' => $end->toDateTimeString(),
                    ];
                    $client->invoices()->save($invoice);

                    $item = new InvoiceItem;
                    $item->item_title = Str::limit($todo->title, 255);
                    $item->amount = 0;
                    $item->qty = 1;
                    $item->item_type = 'timer';
                    $invoice->items()->save($item);

                    $timer = new InvoiceItemTimer;
                    $timer->invoice_item_id = $item->id;
                    $timer->user_id = $client->id;
                    $timer->project_id = $invoice->project_id;
                    $timer->date_start = $start->toDateTimeString();
                    $timer->date_end = $end->toDateTimeString();
                    $timer->amount = round($amountInUserCurrency, 2);
                    $timer->currency_id = $invoice->currency;
                    $item->timers()->save($timer);

                    $invoice->unpaid = $invoice->total();
                    $invoice->save();
                    $invoice->bill_invoice();

                    DB::commit();

                    try {
                        $adminUser = auth()->user() ?? User::role('super-admin')->first();
                        if ($adminUser && class_exists(SyncTodoToGoogleCalendar::class)) {
                            SyncTodoToGoogleCalendar::dispatch($todo, $adminUser, 'create');
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Google Calendar sync failed: '.$e->getMessage());
                    }

                    return redirect()->back()->with('message', __('general.scheduled_task_created_and_billed_successfully'));

                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }

            });
        } catch (LockTimeoutException $e) {
            return back()->withErrors(['error' => 'Booking system is currently busy. Please try again.']);
        } catch (\Throwable $e) {
            Log::error('Admin storeAndBillCalendarTodo failed: '.$e->getMessage(), [
                'user_id' => $client->id,
            ]);

            return back()->withErrors(['error' => 'Failed to create and bill task.']);
        }
    }

    /**
     * Update todo details.
     */
    public function updateTodo(Request $request, Todo $todo)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'paused' => 'nullable|boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
        ]);

        $todo->title = $data['title'];
        $todo->description = $data['description'] ?? null;
        if (array_key_exists('priority', $data)) {
            $todo->priority = $data['priority'];
            $colors = [
                'urgent' => '#f56565',
                'high' => '#dd6b20',
                'normal' => '#11cdef',
                'low' => '#a0aec0',
            ];
            if (isset($colors[$data['priority']])) {
                $todo->priorityColor = $colors[$data['priority']];
            }
        }
        if (array_key_exists('paused', $data)) {
            $todo->paused = (bool) $data['paused'];
        }
        if (array_key_exists('start_at', $data)) {
            $todo->start_at = $data['start_at'] ? Carbon::parse($data['start_at'], 'Africa/Cairo')->toDateTimeString() : null;
        }
        if (array_key_exists('end_at', $data)) {
            $todo->end_at = $data['end_at'] ? Carbon::parse($data['end_at'], 'Africa/Cairo')->toDateTimeString() : null;
        }

        $todo->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('general.task_status_updated_successfully'),
                'todo' => $todo,
            ]);
        }

        return redirect()->back()->with('message', __('general.task_status_updated_successfully'));
    }

    /**
     * Display a paginated, searchable listing of all project board items.
     */
    public function boardExplorer(Request $request): InertiaResponse
    {
        $perPage = (int) $request->get('per_page', 25);
        $search = $request->get('search');
        $projectId = $request->get('project_id');
        $clientId = $request->get('client_id');
        $lane = $request->get('lane');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ProjectBoardItem::query()
            ->with(['project.client', 'category', 'itemable']);

        // Filter by project
        if ($projectId && $projectId !== 'all') {
            $query->where('project_id', $projectId);
        }

        // Filter by client
        if ($clientId && $clientId !== 'all') {
            $query->whereHas('project', function ($q) use ($clientId) {
                $q->where('user_id', $clientId);
            });
        }

        // Filter by lane
        if ($lane && $lane !== 'all') {
            $query->where('lane', $lane);
        }

        // Filter by item type
        if ($type && $type !== 'all' && array_key_exists($type, ProjectBoardItem::MORPH_MAP)) {
            $query->where('itemable_type', ProjectBoardItem::MORPH_MAP[$type]);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('for_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('for_date', '<=', $dateTo);
        }

        // Search text in itemables
        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHasMorph('itemable', [
                    ProjectBoardNote::class,
                    Task::class,
                    ProjectReport::class,
                    Todo::class,
                    ProjectFile::class
                ], function ($subQuery, $type) use ($searchTerm) {
                    if ($type === ProjectBoardNote::class) {
                        $subQuery->where('title', 'like', $searchTerm)->orWhere('content', 'like', $searchTerm);
                    } elseif ($type === Task::class) {
                        $subQuery->where('task_name', 'like', $searchTerm)->orWhere('task_description', 'like', $searchTerm);
                    } elseif ($type === ProjectReport::class) {
                        $subQuery->where('title', 'like', $searchTerm)->orWhere('summary', 'like', $searchTerm);
                    } elseif ($type === Todo::class) {
                        $subQuery->where('title', 'like', $searchTerm)->orWhere('description', 'like', $searchTerm);
                    } elseif ($type === ProjectFile::class) {
                        $subQuery->where('original_name', 'like', $searchTerm);
                    }
                });
            });
        }

        // Order by date desc, then sort order
        $query->orderBy('for_date', 'desc')->orderBy('sort', 'asc');

        // Paginate
        $paginator = $query->paginate($perPage)->withQueryString();

        // Map items
        $items = collect($paginator->items())->map(function ($item) {
            $title = '';
            $description = '';
            $typeAlias = array_search($item->itemable_type, ProjectBoardItem::MORPH_MAP) ?: 'note';
            
            $itemable = $item->itemable;
            if ($itemable) {
                if ($itemable instanceof ProjectBoardNote) {
                    $title = $itemable->title;
                    $description = $itemable->content;
                } elseif ($itemable instanceof Task) {
                    $title = $itemable->task_name;
                    $description = $itemable->task_description;
                } elseif ($itemable instanceof ProjectReport) {
                    $title = $itemable->title;
                    $description = $itemable->summary;
                } elseif ($itemable instanceof Todo) {
                    $title = $itemable->title;
                    $description = $itemable->description;
                } elseif ($itemable instanceof ProjectFile) {
                    $title = $itemable->original_name;
                    $description = $itemable->humanSize();
                }
            }

            return [
                'id' => $item->id,
                'project_id' => $item->project_id,
                'project_name' => $item->project?->project_name ?? __('general.unknown'),
                'client_name' => $item->project?->client?->name ?? __('general.unknown'),
                'lane' => $item->lane,
                'for_date' => $item->for_date ? Carbon::parse($item->for_date)->toDateString() : null,
                'type' => $typeAlias,
                'itemable_id' => $item->itemable_id,
                'title' => $title ?: __('general.untitled') ?: 'Untitled',
                'description' => $description,
                'category_name' => $item->category?->localizedName() ?? $item->category?->name,
                'category_color' => $item->category?->color,
            ];
        });

        // Get drop-downs
        $projects = Project::where('archived', 0)
            ->orderBy('project_name')
            ->get(['id', 'project_name']);

        $clients = User::role('client')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Stats (unfiltered baseline counts by type)
        $stats = [
            'total' => ProjectBoardItem::count(),
            'notes' => ProjectBoardItem::where('itemable_type', ProjectBoardNote::class)->count(),
            'tasks' => ProjectBoardItem::where('itemable_type', Task::class)->count(),
            'reports' => ProjectBoardItem::where('itemable_type', ProjectReport::class)->count(),
            'todos' => ProjectBoardItem::where('itemable_type', Todo::class)->count(),
            'files' => ProjectBoardItem::where('itemable_type', ProjectFile::class)->count(),
        ];

        return Inertia::render('Admin/Tasks/BoardExplorer', $this->sanitizeUtf8([
            'items' => $items,
            'projects' => $projects,
            'clients' => $clients,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'project_id' => $projectId ?: 'all',
                'client_id' => $clientId ?: 'all',
                'lane' => $lane ?: 'all',
                'type' => $type ?: 'all',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]));
    }

    /**
     * Display a unified view of all pending tasks with segmented tabs,
     * client loyalty tiers, billing status, SLA timers, and ignore audit logs.
     */
    public function pending(Request $request): InertiaResponse
    {
        $search = trim((string) $request->input('search', ''));
        $tab = (string) $request->input('tab', 'all');
        $billingType = (string) $request->input('billing_type', 'all');
        $priority = (string) $request->input('priority', 'all');
        $perPage = max(10, min(100, (int) $request->input('per_page', 20)));

        // Global stats for the tabs
        $stats = [
            'all' => Task::whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('billing_status', '!=', 'ignored')->orWhereNull('billing_status');
                })
                ->where('archived', 0)
                ->count(),
            'waiting_client' => Task::whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('billing_status', '!=', 'ignored')->orWhereNull('billing_status');
                })
                ->where('pending_reason', 'waiting_client')
                ->count(),
            'waiting_execution' => Task::whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('billing_status', '!=', 'ignored')->orWhereNull('billing_status');
                })
                ->where(function ($q) {
                    $q->where('pending_reason', 'waiting_execution')
                      ->orWhere(function ($sub) {
                          $sub->whereNull('pending_reason')
                              ->where(function ($s) {
                                  $s->where('billing_status', 'open')->orWhereNull('billing_status');
                              });
                      });
                })
                ->count(),
            'waiting_review' => Task::whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('billing_status', '!=', 'ignored')->orWhereNull('billing_status');
                })
                ->where('pending_reason', 'waiting_review')
                ->count(),
            'ready_to_invoice' => Task::whereNull('deleted_at')
                ->where('billing_status', 'ready_to_invoice')
                ->count(),
            'ignored' => Task::whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('billing_status', 'ignored')->orWhereNotNull('ignored_at');
                })
                ->count(),
        ];

        $query = Task::query()
            ->whereNull('deleted_at')
            ->with([
                'project.client',
                'user',
                'ignoredByUser',
                'auditLogs.user',
            ]);

        // Tab filtering
        if ($tab === 'ignored') {
            $query->where(function ($q) {
                $q->where('billing_status', 'ignored')->orWhereNotNull('ignored_at');
            });
        } else {
            $query->where(function ($q) {
                $q->where('billing_status', '!=', 'ignored')->orWhereNull('billing_status');
            })->whereNull('ignored_at');

            if ($tab === 'waiting_client') {
                $query->where('pending_reason', 'waiting_client');
            } elseif ($tab === 'waiting_execution') {
                $query->where(function ($q) {
                    $q->where('pending_reason', 'waiting_execution')
                      ->orWhere(function ($sub) {
                          $sub->whereNull('pending_reason')
                              ->where(function ($s) {
                                  $s->where('billing_status', 'open')->orWhereNull('billing_status');
                              });
                      });
                });
            } elseif ($tab === 'waiting_review') {
                $query->where('pending_reason', 'waiting_review');
            } elseif ($tab === 'ready_to_invoice') {
                $query->where('billing_status', 'ready_to_invoice');
            }
        }

        // Billing type filter
        if (in_array($billingType, ['billable', 'non_billable'], true)) {
            $query->where('billing_type', $billingType);
        }

        // Priority filter
        if (in_array($priority, ['urgent', 'high', 'medium', 'low'], true)) {
            $query->where('priority', $priority);
        }

        // Search filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('task_name', 'like', "%{$search}%")
                  ->orWhere('task_description', 'like', "%{$search}%")
                  ->orWhereHas('project', function ($pq) use ($search) {
                      $pq->where('project_name', 'like', "%{$search}%")
                         ->orWhereHas('client', function ($cq) use ($search) {
                             $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                         });
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Ordering
        if ($tab === 'ignored') {
            $query->orderByDesc('ignored_at');
        } else {
            $query->orderByRaw('CASE WHEN sla_due_at IS NOT NULL THEN 0 ELSE 1 END, sla_due_at ASC')
                  ->orderByDesc('created_at');
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $loyaltyService = app(\App\Services\LoyaltyService::class);

        $items = collect($paginator->items())->map(function (Task $task) use ($loyaltyService) {
            $client = $task->project?->client ?? $task->user;
            $clientSummary = null;

            if ($client) {
                try {
                    $summary = $loyaltyService->getUserSummary($client);
                    $clientSummary = [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'tier_name' => $summary['current_tier']['name'] ?? 'Bronze',
                        'tier_slug' => $summary['current_tier']['slug'] ?? 'bronze',
                        'tier_badge' => $summary['current_tier']['badge_url'] ?? '/images/tiers/bronze.png',
                        'points_balance' => $summary['points_balance'] ?? 0,
                        'points_discount_value' => $summary['points_discount_value'] ?? 0,
                    ];
                } catch (\Throwable $e) {
                    $clientSummary = [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'tier_name' => 'Bronze',
                        'tier_slug' => 'bronze',
                        'tier_badge' => '/images/tiers/bronze.png',
                        'points_balance' => 0,
                        'points_discount_value' => 0,
                    ];
                }
            }

            return [
                'id' => $task->id,
                'task_name' => $task->task_name,
                'task_description' => $task->task_description,
                'priority' => $task->priority ?? 'medium',
                'billing_type' => $task->billing_type ?? 'billable',
                'billing_status' => $task->billing_status ?? 'open',
                'pending_reason' => $task->pending_reason ?? 'waiting_execution',
                'ignore_reason' => $task->ignore_reason,
                'ignore_notes' => $task->ignore_notes,
                'ignored_at' => $task->ignored_at ? Carbon::parse($task->ignored_at)->timezone('Africa/Cairo')->format('Y-m-d H:i') : null,
                'ignored_by_user' => $task->ignoredByUser ? ['id' => $task->ignoredByUser->id, 'name' => $task->ignoredByUser->name] : null,
                'sla_hours' => $task->sla_hours,
                'sla_due_at' => $task->sla_due_at ? Carbon::parse($task->sla_due_at)->timezone('Africa/Cairo')->format('Y-m-d H:i') : null,
                'created_at' => Carbon::parse($task->created_at)->timezone('Africa/Cairo')->format('Y-m-d H:i'),
                'due_date' => $task->due_date,
                'project' => $task->project ? [
                    'id' => $task->project->id,
                    'name' => $task->project->project_name ?? $task->project->name ?? ('#' . $task->project->id),
                ] : null,
                'client' => $clientSummary,
                'audit_logs' => $task->auditLogs->take(8)->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'reason' => $log->reason,
                    'notes' => $log->notes,
                    'user_name' => $log->user?->name ?? 'System',
                    'created_at' => Carbon::parse($log->created_at)->timezone('Africa/Cairo')->format('Y-m-d H:i'),
                ]),
            ];
        });

        return Inertia::render('Admin/Tasks/PendingTasks', $this->sanitizeUtf8([
            'tasks' => $items,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'tab' => $tab,
                'billing_type' => $billingType,
                'priority' => $priority,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]));
    }

    /**
     * Formal ignore action for a pending task.
     * Records mandatory reason and notes into task_audit_logs with Cairo timezone.
     */
    public function ignoreTask(Request $request, Task $task)
    {
        $validated = $request->validate([
            'ignore_reason' => ['required', 'string', 'in:non_billable,duplicate,entry_error,free_promo,client_cancelled,other'],
            'ignore_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cairoNow = Carbon::now('Africa/Cairo');
        $oldBillingStatus = $task->billing_status;

        $task->update([
            'billing_status' => 'ignored',
            'ignore_reason' => $validated['ignore_reason'],
            'ignore_notes' => $validated['ignore_notes'] ?? null,
            'ignored_by' => Auth::id(),
            'ignored_at' => $cairoNow,
        ]);

        TaskAuditLog::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'ignored',
            'reason' => $validated['ignore_reason'],
            'notes' => $validated['ignore_notes'] ?? null,
            'old_values' => ['billing_status' => $oldBillingStatus],
            'new_values' => [
                'billing_status' => 'ignored',
                'ignore_reason' => $validated['ignore_reason'],
                'ignored_at' => $cairoNow->toDateTimeString(),
            ],
            'created_at' => $cairoNow,
        ]);

        return back()->with('success', 'Task has been moved to the Ignored list.');
    }

    /**
     * Restore an ignored task back to pending execution.
     */
    public function restoreTask(Request $request, Task $task)
    {
        $cairoNow = Carbon::now('Africa/Cairo');
        $oldReason = $task->ignore_reason;

        $task->update([
            'billing_status' => 'open',
            'pending_reason' => 'waiting_execution',
            'ignore_reason' => null,
            'ignore_notes' => null,
            'ignored_by' => null,
            'ignored_at' => null,
        ]);

        TaskAuditLog::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'restored',
            'reason' => 'Restored from ignored list',
            'notes' => $request->input('notes', 'Admin restored task to execution pipeline'),
            'old_values' => ['billing_status' => 'ignored', 'ignore_reason' => $oldReason],
            'new_values' => ['billing_status' => 'open', 'pending_reason' => 'waiting_execution'],
            'created_at' => $cairoNow,
        ]);

        return back()->with('success', 'Task restored to active pending queue.');
    }

    /**
     * Update billing status, classification, or pending reason on a task.
     */
    public function updateBillingStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'billing_type' => ['nullable', 'string', 'in:billable,non_billable'],
            'billing_status' => ['nullable', 'string', 'in:open,in_progress,ready_to_invoice,invoiced,ignored'],
            'pending_reason' => ['nullable', 'string', 'in:waiting_client,waiting_execution,waiting_review,ready_to_invoice,other'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $cairoNow = Carbon::now('Africa/Cairo');
        $oldValues = [
            'billing_type' => $task->billing_type,
            'billing_status' => $task->billing_status,
            'pending_reason' => $task->pending_reason,
            'sla_hours' => $task->sla_hours,
        ];

        $updateData = [];
        if (isset($validated['billing_type'])) {
            $updateData['billing_type'] = $validated['billing_type'];
        }
        if (isset($validated['billing_status'])) {
            $updateData['billing_status'] = $validated['billing_status'];
        }
        if (isset($validated['pending_reason'])) {
            $updateData['pending_reason'] = $validated['pending_reason'];
        }
        if (isset($validated['sla_hours'])) {
            $updateData['sla_hours'] = $validated['sla_hours'];
            $updateData['sla_due_at'] = $cairoNow->copy()->addHours($validated['sla_hours']);
        }

        $task->update($updateData);

        TaskAuditLog::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'action' => 'status_updated',
            'reason' => 'Manual admin update',
            'notes' => 'Updated status, billing type, or SLA',
            'old_values' => $oldValues,
            'new_values' => $updateData,
            'created_at' => $cairoNow,
        ]);

        return back()->with('success', 'Task updated successfully.');
    }

    /**
     * Bulk actions on pending tasks (ignore, restore, mark ready to invoice).
     */
    public function bulkPendingAction(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:tasks,id'],
            'action' => ['required', 'string', 'in:ignore,restore,mark_ready_to_invoice,mark_billable,mark_non_billable'],
            'ignore_reason' => ['required_if:action,ignore', 'nullable', 'string'],
            'ignore_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cairoNow = Carbon::now('Africa/Cairo');
        $tasks = Task::whereIn('id', $validated['task_ids'])->get();

        DB::transaction(function () use ($tasks, $validated, $cairoNow) {
            foreach ($tasks as $task) {
                $oldStatus = $task->billing_status;

                if ($validated['action'] === 'ignore') {
                    $task->update([
                        'billing_status' => 'ignored',
                        'ignore_reason' => $validated['ignore_reason'],
                        'ignore_notes' => $validated['ignore_notes'] ?? null,
                        'ignored_by' => Auth::id(),
                        'ignored_at' => $cairoNow,
                    ]);

                    TaskAuditLog::create([
                        'task_id' => $task->id,
                        'user_id' => Auth::id(),
                        'action' => 'ignored',
                        'reason' => $validated['ignore_reason'],
                        'notes' => $validated['ignore_notes'] ?? null,
                        'old_values' => ['billing_status' => $oldStatus],
                        'new_values' => ['billing_status' => 'ignored'],
                        'created_at' => $cairoNow,
                    ]);
                } elseif ($validated['action'] === 'restore') {
                    $task->update([
                        'billing_status' => 'open',
                        'pending_reason' => 'waiting_execution',
                        'ignore_reason' => null,
                        'ignore_notes' => null,
                        'ignored_by' => null,
                        'ignored_at' => null,
                    ]);

                    TaskAuditLog::create([
                        'task_id' => $task->id,
                        'user_id' => Auth::id(),
                        'action' => 'restored',
                        'reason' => 'Bulk restore',
                        'old_values' => ['billing_status' => $oldStatus],
                        'new_values' => ['billing_status' => 'open'],
                        'created_at' => $cairoNow,
                    ]);
                } elseif ($validated['action'] === 'mark_ready_to_invoice') {
                    $task->update([
                        'billing_status' => 'ready_to_invoice',
                        'pending_reason' => 'ready_to_invoice',
                    ]);

                    TaskAuditLog::create([
                        'task_id' => $task->id,
                        'user_id' => Auth::id(),
                        'action' => 'status_updated',
                        'reason' => 'Marked ready to invoice in bulk',
                        'old_values' => ['billing_status' => $oldStatus],
                        'new_values' => ['billing_status' => 'ready_to_invoice'],
                        'created_at' => $cairoNow,
                    ]);
                } elseif ($validated['action'] === 'mark_billable') {
                    $task->update(['billing_type' => 'billable']);
                } elseif ($validated['action'] === 'mark_non_billable') {
                    $task->update(['billing_type' => 'non_billable']);
                }
            }
        });

        return back()->with('success', 'Bulk task action completed successfully.');
    }
}
