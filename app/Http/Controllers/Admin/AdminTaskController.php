<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\Task;
use App\Models\User;
use App\Models\Currency;
use App\Models\InvoiceItemTimer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminTaskController extends Controller
{
    /**
     * Display a listing of active checklist items for all clients.
     */
    public function asList(Request $request): InertiaResponse
    {
        $search = $request->get('search');
        $clientId = $request->get('client_id') ?? $request->get('tenant_id');

        $query = Todo::where('completed', false)
            ->where('paused', false)
            ->whereNull('parent_id')
            ->whereHas('task', function ($q) {
                $q->where('archived', false);
            })
            ->with([
                'task.user',
                'user',
                'children' => function ($q) {
                    $q->orderBy('sort_index')->orderBy('id');
                },
            ]);

        // Filter by client
        if ($clientId) {
            $query->where('user_id', $clientId);
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $todos = $query->orderBy('user_id')->orderBy('id')->get();

        $data = [];

        foreach ($todos as $todo) {
            $task = $todo->task;
            $client = $todo->user;
            if (!$task || !$client) {
                continue;
            }

            $userId = $client->id;
            $taskId = $task->id;

            if (!isset($data[$userId])) {
                $data[$userId] = [
                    'client' => [
                        'id'    => $client->id,
                        'name'  => $client->name,
                        'email' => $client->email,
                    ],
                    'tasks' => [],
                ];
            }

            if (!isset($data[$userId]['tasks'][$taskId])) {
                $data[$userId]['tasks'][$taskId] = [
                    'id'        => $task->id,
                    'task_name' => $task->task_name,
                    'status'    => $task->status ?? 'open',
                    'todos'     => [],
                ];
            }

            // Resolve currency
            $currencyName = 'EGP';
            if ($todo->currency_id) {
                $curr = Currency::find($todo->currency_id);
                if ($curr) {
                    $currencyName = $curr->currency;
                }
            } else {
                $currencyName = $client->currency_name();
            }

            // Parse tags safely
            $tags = [];
            if ($todo->tags) {
                if (is_array($todo->tags)) {
                    $tags = $todo->tags;
                } else {
                    $tags = json_decode($todo->tags, true) ?? [];
                }
            }

            $data[$userId]['tasks'][$taskId]['todos'][] = [
                'id'             => $todo->id,
                'title'          => $todo->title,
                'description'    => $todo->description,
                'priority'       => $todo->priority ?? 'normal',
                'priority_color' => $todo->priorityColor,
                'paused'         => (bool)$todo->paused,
                'is_paid'        => (bool)$todo->is_paid,
                'cost'           => $todo->cost,
                'cost_currency'  => $currencyName,
                'start_at'       => $todo->start_at ? \Carbon\Carbon::parse($todo->start_at)->toISOString() : null,
                'end_at'         => $todo->end_at ? \Carbon\Carbon::parse($todo->end_at)->toISOString() : null,
                'tags'           => $tags,
                'created_at'     => $todo->created_at->toISOString(),
            ];
        }

        // Convert to indexed arrays
        $arrangedClients = array_values($data);
        foreach ($arrangedClients as &$clientData) {
            $clientData['tasks'] = array_values($clientData['tasks']);
        }

        // All platform clients for the filter dropdown
        $clients = User::role('client')->orderBy('name')->get()->map(fn($u) => [
            'id'   => $u->id,
            'name' => $u->name,
        ]);

        // Stats
        $totalActive = Todo::where('completed', false)
            ->where('paused', false)
            ->whereNull('parent_id')
            ->count();

        return Inertia::render('Admin/Tasks/AsList', [
            'arrangedClients' => $arrangedClients,
            'clients'         => $clients,
            'filters'         => $request->only(['search', 'client_id', 'tenant_id']),
            'stats'           => [
                'total_active_todos' => $totalActive,
                'total_clients'      => count($clients),
            ],
        ]);
    }

    /**
     * Mark a platform todo item as complete.
     */
    public function completeTodo(Request $request, Todo $todo)
    {
        $todo->completed = $request->boolean('completed');
        $todo->completed_at = $todo->completed ? now() : null;
        $todo->save();

        return response()->json(['success' => true]);
    }

    /**
     * Display a calendar showing all tasks, scheduled todos, and busy times.
     */
    public function calendar(Request $request): InertiaResponse
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('n'));
        $clientId = $request->get('client_id') ?? $request->get('tenant_id');

        // We want to fetch tasks, todos, and busy times for this month
        $startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        // Expand range to cover startOfWeek of startOfMonth and endOfWeek of endOfMonth
        $startDate = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endDate = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        // 1. Fetch scheduled Todos in the range
        $todosQuery = Todo::whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                  ->orWhereBetween('end_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            })
            ->whereNull('parent_id')
            ->with(['task.user', 'user']);

        if ($clientId) {
            $todosQuery->where('user_id', $clientId);
        }

        $todos = $todosQuery->get();

        // 2. Fetch Tasks due in this range
        $tasksQuery = Task::whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('user');

        if ($clientId) {
            $tasksQuery->where('user_id', $clientId);
        }

        $tasks = $tasksQuery->get();

        // 3. Fetch Recurring & Specific Busy Times
        $busyTimes = \App\Models\RecurringBusyTime::where('is_active', true)->get();

        // Group everything by date string "Y-m-d" Cairo local time
        $events = [];

        // Pre-fill days in the interval
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()
        );

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $events[$dateStr] = [
                'tasks'      => [],
                'todos'      => [],
                'busy_times' => [],
            ];
        }

        // Add busy times day by day
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayOfWeek = $date->format('l');

            foreach ($busyTimes as $bt) {
                $matches = false;
                if ($bt->is_recurring && strcasecmp($bt->day_of_week, $dayOfWeek) === 0) {
                    $matches = true;
                } elseif (!$bt->is_recurring && $bt->specific_date && $bt->specific_date->format('Y-m-d') === $dateStr) {
                    $matches = true;
                }

                if ($matches) {
                    $events[$dateStr]['busy_times'][] = [
                        'id'          => 'busy-' . $bt->id,
                        'title'       => $bt->reason ?: 'Busy',
                        'is_full_day' => (bool)$bt->is_full_day,
                        'start_time'  => $bt->start_time ? \Carbon\Carbon::parse($bt->start_time)->format('H:i') : null,
                        'end_time'    => $bt->end_time ? \Carbon\Carbon::parse($bt->end_time)->format('H:i') : null,
                    ];
                }
            }
        }

        // Add Tasks to events map
        foreach ($tasks as $task) {
            $dateStr = \Carbon\Carbon::parse($task->due_date)->format('Y-m-d');
            if (isset($events[$dateStr])) {
                $events[$dateStr]['tasks'][] = [
                    'id'        => $task->id,
                    'title'     => $task->task_name,
                    'priority'  => $task->priority ?? 'normal',
                    'completed' => $task->completed(),
                    'client'    => $task->user?->name ?? 'Unknown',
                ];
            }
        }

        // Add Todos to events map
        foreach ($todos as $todo) {
            $dateStr = \Carbon\Carbon::parse($todo->start_at)->format('Y-m-d');
            if (isset($events[$dateStr])) {
                $events[$dateStr]['todos'][] = [
                    'id'             => $todo->id,
                    'title'          => $todo->title,
                    'priority'       => $todo->priority ?? 'normal',
                    'priority_color' => $todo->priorityColor,
                    'completed'      => (bool)$todo->completed,
                    'task_id'        => $todo->task_id,
                    'client'         => $todo->user?->name ?? 'Unknown',
                    'start_time'     => \Carbon\Carbon::parse($todo->start_at)->format('H:i'),
                ];
            }
        }

        // List of all clients for selection filter
        $clients = User::role('client')->orderBy('name')->get()->map(fn($u) => [
            'id'   => $u->id,
            'name' => $u->name,
        ]);

        return Inertia::render('Admin/Tasks/TaskCalendar', [
            'events'  => $events,
            'year'    => $year,
            'month'   => $month,
            'clients' => $clients,
            'filters' => [
                'client_id' => $clientId,
            ],
        ]);
    }

    /**
     * Display client focus board and checklist items queue for a specific client.
     */
    public function clientTasks(Request $request): InertiaResponse
    {
        $clientId = $request->get('client_id') ?? $request->get('tenant_id');

        // Fetch all platform clients with task/todo metrics
        // User has no todos() relationship, so we use raw subquery selects
        $clients = User::role('client')
            ->selectRaw('users.*, (SELECT COUNT(*) FROM todos WHERE todos.user_id = users.id AND todos.parent_id IS NULL) as total_tasks')
            ->selectRaw('(SELECT COUNT(*) FROM todos WHERE todos.user_id = users.id AND todos.parent_id IS NULL AND todos.completed = 1) as completed_tasks')
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                // Get initials
                $words = explode(' ', $u->name);
                $initials = '';
                foreach ($words as $w) {
                    $initials .= strtoupper(substr($w, 0, 1));
                }
                $initials = substr($initials, 0, 2);

                return [
                    'id'              => $u->id,
                    'name'            => $u->name,
                    'email'           => $u->email,
                    'initials'        => $initials ?: 'C',
                    'total_tasks'     => (int) $u->total_tasks,
                    'completed_tasks' => (int) $u->completed_tasks,
                ];
            });

        $selectedClient = null;
        $todos = [];

        if ($clientId) {
            $clientUser = User::role('client')->findOrFail($clientId);
            $totalTasks = Todo::where('user_id', $clientUser->id)->whereNull('parent_id')->count();
            $completedTasks = Todo::where('user_id', $clientUser->id)->whereNull('parent_id')->where('completed', true)->count();
            $latestTask = Task::where('user_id', $clientUser->id)->latest()->first();

            $selectedClient = [
                'id'              => $clientUser->id,
                'name'            => $clientUser->name,
                'email'           => $clientUser->email,
                'balance'         => (float) $clientUser->user_balance,
                'currency'        => $clientUser->currency_name(),
                'total_tasks'     => $totalTasks,
                'completed_tasks' => $completedTasks,
                'latest_task_id'  => $latestTask ? $latestTask->id : null,
            ];

            $todos = Todo::where('user_id', $clientUser->id)
                ->where('completed', false)
                ->whereNull('parent_id')
                ->with('task')
                ->orderBy('start_at', 'asc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($todo) use ($clientUser) {
                    $clientCurrency = $clientUser->currency_name();
                    $userCurrencyId = (int) $clientUser->currency;

                    // Convert EGP cost to client's currency for displaying on refund dialogs
                    $costInClientCurrency = (float) \App\Models\CurrenciesExchange::RateToday((float) $todo->cost, $todo->currency_id ?? 2, $userCurrencyId);

                    // show_refund logic
                    $invalidSlot = $todo->start_at === null || $todo->end_at === null;
                    if (!$invalidSlot) {
                        $start = \Carbon\Carbon::parse($todo->start_at);
                        $end = \Carbon\Carbon::parse($todo->end_at);
                        $invalidSlot = !$end->gt($start);
                    }

                    $showRefund = false;
                    if ($todo->is_paid && !$todo->refunded) {
                        if ($invalidSlot) {
                            $showRefund = true;
                        } elseif ($todo->end_at && \Carbon\Carbon::parse($todo->end_at, 'Africa/Cairo')->isFuture()) {
                            $showRefund = true;
                        }
                    }

                    return [
                        'id'                      => $todo->id,
                        'task_id'                 => $todo->task_id,
                        'task_name'               => $todo->task?->task_name ?? 'My Focus',
                        'title'                   => $todo->title,
                        'description'             => $todo->description,
                        'completed'               => (bool)$todo->completed,
                        'paused'                  => (bool)$todo->paused,
                        'is_paid'                 => (bool)$todo->is_paid,
                        'cost'                    => (float)$todo->cost,
                        'cost_in_client_currency' => $costInClientCurrency,
                        'client_currency'         => $clientCurrency,
                        'start_at'                => $todo->start_at ? \Carbon\Carbon::parse($todo->start_at)->toISOString() : null,
                        'end_at'                  => $todo->end_at ? \Carbon\Carbon::parse($todo->end_at)->toISOString() : null,
                        'refunded'                => (bool)$todo->refunded,
                        'refund_amount'           => (float)$todo->refund_amount,
                        'show_refund'             => $showRefund,
                    ];
                });
        }

        return Inertia::render('Admin/Tasks/ClientTasks', [
            'clients'        => $clients,
            'selectedClient' => $selectedClient,
            'todos'          => $todos,
            'filters'        => [
                'client_id' => $clientId,
            ],
        ]);
    }

    /**
     * Refund remaining time for a paid todo that has not finished yet.
     */
    public function refundTodo(Request $request, Todo $todo)
    {
        $user = $todo->user;
        if (!$todo->is_paid || $todo->refunded || !$user) {
            return redirect()->back()->withErrors(['error' => 'This item cannot be refunded.']);
        }

        $now = now('Africa/Cairo');
        $invalidSlot = $todo->start_at === null || $todo->end_at === null;
        if (!$invalidSlot) {
            $start = \Carbon\Carbon::parse($todo->start_at, 'Africa/Cairo');
            $end = \Carbon\Carbon::parse($todo->end_at, 'Africa/Cairo');
            $invalidSlot = !$end->gt($start);
        } else {
            $start = null;
            $end = null;
        }

        if ($invalidSlot) {
            $this->refundInvalidSlotTodo($todo, $user);
            return redirect()->back()->with('message', 'Refund processed successfully!');
        }

        if ($now->gte($end)) {
            return redirect()->back()->withErrors(['error' => 'This booking slot has already fully elapsed.']);
        }

        // Use seconds so sub-minute slots are not misclassified (diffInMinutes can be 0)
        $totalSeconds = $start->diffInSeconds($end);
        if ($totalSeconds <= 0) {
            $this->refundInvalidSlotTodo($todo, $user);
            return redirect()->back()->with('message', 'Refund processed successfully!');
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
            \Illuminate\Support\Facades\DB::beginTransaction();
            $project = $todo->task ? $todo->task->project : null;

            // Refund user balance
            $reason = 'Refund for todo: ' . \Illuminate\Support\Str::limit($todo->title, 80) . ' [todo_time_refund]';
            $user->add_balance(
                $refundAmount,
                $reason,
                'earned',
                (int) $todo->currency_id,
                $project
            );

            // SYNC InvoiceItemTimer if it exists
            $oldEnd = \Carbon\Carbon::parse($todo->end_at)->toDateTimeString();
            $timerRecord = InvoiceItemTimer::where('user_id', $user->id)
                ->where('date_start', \Carbon\Carbon::parse($todo->start_at)->toDateTimeString())
                ->where('date_end', $oldEnd)
                ->whereHas('invoiceItem', function($q) use ($todo) {
                    $q->where('item_title', \Illuminate\Support\Str::limit($todo->title, 255));
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

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Todo refund remaining time failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to process refund.']);
        }

        // WhatsApp notification if service exists
        try {
            if (class_exists('App\Services\WhatsAppNotificationService')) {
                $whatsApp = app(\App\Services\WhatsAppNotificationService::class);
                $message = $whatsApp->generateRefundMessage($user, $todo, $refundAmount);
                $whatsApp->sendMessage($user, $message);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WhatsApp refund notification failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);
        }

        return redirect()->back()->with('message', 'Refund processed successfully!');
    }

    /**
     * Full refund for paid todos with an unusable window, then remove the todo.
     */
    protected function refundInvalidSlotTodo(Todo $todo, $user): void
    {
        $refundAmount = round((float) $todo->cost, 2);
        $refundReason = 'Refund for todo: ' . \Illuminate\Support\Str::limit($todo->title, 80) . ' [todo_time_refund]';

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
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
            $oldEnd = $todo->end_at ? \Carbon\Carbon::parse($todo->end_at)->toDateTimeString() : null;
            $dateStartStr = $todo->start_at ? \Carbon\Carbon::parse($todo->start_at)->toDateTimeString() : null;
            if ($dateStartStr !== null && $oldEnd !== null) {
                $timerRecord = InvoiceItemTimer::where('user_id', $user->id)
                    ->where('date_start', $dateStartStr)
                    ->where('date_end', $oldEnd)
                    ->whereHas('invoiceItem', function ($q) use ($todo) {
                        $q->where('item_title', \Illuminate\Support\Str::limit($todo->title, 255));
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
            $todo->delete();

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Todo refund invalid slot failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);
            throw $e;
        }

        // WhatsApp notification if service exists
        if ($refundAmount > 0) {
            try {
                if (class_exists('App\Services\WhatsAppNotificationService')) {
                    $whatsApp = app(\App\Services\WhatsAppNotificationService::class);
                    $message = $whatsApp->generateRefundMessage($user, $todo, $refundAmount);
                    $whatsApp->sendMessage($user, $message);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WhatsApp refund notification failed: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'todo_id' => $todo->id,
                ]);
            }
        }
    }
}
