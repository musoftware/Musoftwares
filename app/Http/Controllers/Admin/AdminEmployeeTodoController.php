<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRecurringTodo;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminEmployeeTodoController extends Controller
{
    /**
     * Display a paginated list of all employee recurring todos, with optional filters.
     */
    public function index(Request $request)
    {
        $query = EmployeeRecurringTodo::with(['user', 'transactions']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('recurring')) {
            $query->where('recurring', $request->recurring);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $todos = $query
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($todo) => [
                'id'                     => $todo->id,
                'title'                  => $todo->title,
                'description'            => $todo->description,
                'priority'               => $todo->priority,
                'recurring'              => $todo->recurring,
                'recurring_times'        => $todo->recurring_times,
                'recurring_times_week'   => $todo->recurring_times_week,
                'recurring_times_month'  => $todo->recurring_times_month,
                'recurring_times_year'   => $todo->recurring_times_year,
                'current_date'           => $todo->current_date,
                'transactions_count'     => $todo->transactions->count(),
                'created_at'             => $todo->created_at->toDateString(),
                'user'                   => $todo->user ? [
                    'id'    => $todo->user->id,
                    'name'  => $todo->user->name,
                    'email' => $todo->user->email,
                ] : null,
            ]);

        $stats = [
            'total'   => EmployeeRecurringTodo::count(),
            'daily'   => EmployeeRecurringTodo::where('recurring', 'day')->count(),
            'weekly'  => EmployeeRecurringTodo::where('recurring', 'week')->count(),
            'monthly' => EmployeeRecurringTodo::where('recurring', 'month')->count(),
            'yearly'  => EmployeeRecurringTodo::where('recurring', 'year')->count(),
        ];

        $users = User::orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

        return Inertia::render('Admin/EmployeeTodos/Index', [
            'todos'   => $todos,
            'filters' => $request->only(['user_id', 'recurring', 'priority', 'search']),
            'stats'   => $stats,
            'users'   => $users,
        ]);
    }

    /**
     * Store a new employee recurring todo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'               => 'required|exists:users,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'priority'              => 'required|in:high,medium,low',
            'recurring'             => 'required|in:day,week,month,year',
            'recurring_times'       => 'required|integer|min:1',
            'recurring_times_week'  => 'nullable|string',
            'recurring_times_month' => 'nullable|string',
            'recurring_times_year'  => 'nullable|string',
            'current_date'          => 'required|date',
        ]);

        EmployeeRecurringTodo::create($validated);

        return redirect()->back()->with('success', 'Employee todo created successfully.');
    }

    /**
     * Update an existing employee recurring todo.
     */
    public function update(Request $request, EmployeeRecurringTodo $employeeTodo)
    {
        $validated = $request->validate([
            'user_id'               => 'required|exists:users,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'priority'              => 'required|in:high,medium,low',
            'recurring'             => 'required|in:day,week,month,year',
            'recurring_times'       => 'required|integer|min:1',
            'recurring_times_week'  => 'nullable|string',
            'recurring_times_month' => 'nullable|string',
            'recurring_times_year'  => 'nullable|string',
            'current_date'          => 'required|date',
        ]);

        $employeeTodo->update($validated);

        return redirect()->back()->with('success', 'Employee todo updated successfully.');
    }

    /**
     * Delete an employee recurring todo and all its transactions.
     */
    public function destroy(EmployeeRecurringTodo $employeeTodo)
    {
        $employeeTodo->delete_with_transactions();

        return redirect()->route('admin.employee-todos.index')->with('success', 'Employee todo deleted.');
    }
}
