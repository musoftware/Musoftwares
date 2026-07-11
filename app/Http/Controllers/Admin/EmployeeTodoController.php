<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTodo;
use App\Models\User;
use App\Notifications\EmployeeTodoAssigned;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeTodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'recurring', 'priority', 'search']);
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $query = EmployeeTodo::with('user');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['recurring'])) {
            $query->where('recurring', $filters['recurring']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        // Apply sorting
        if (in_array($sort, ['id', 'title', 'current_date', 'created_at'])) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $todos = $query->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => EmployeeTodo::count(),
            'daily' => EmployeeTodo::where('recurring', 'day')->count(),
            'weekly' => EmployeeTodo::where('recurring', 'week')->count(),
            'monthly' => EmployeeTodo::where('recurring', 'month')->count(),
            'yearly' => EmployeeTodo::where('recurring', 'year')->count(),
        ];

        // Fetch users (employees) - non-client users
        $users = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'client');
        })->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        });

        return Inertia::render('Admin/EmployeeTodos/Index', [
            'todos' => $todos,
            'filters' => $filters,
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:high,medium,low',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
            'recurring_times_week' => 'nullable|string',
            'recurring_times_month' => 'nullable|string',
            'recurring_times_year' => 'nullable|string',
            'current_date' => 'required|date',
        ]);

        $todo = EmployeeTodo::create($validated);

        // Send Notification to Employee
        $todo->user->notify(new EmployeeTodoAssigned($todo));

        return redirect()->back()->with('success', __('erp.todo_created_success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeTodo $employeeTodo)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:high,medium,low',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
            'recurring_times_week' => 'nullable|string',
            'recurring_times_month' => 'nullable|string',
            'recurring_times_year' => 'nullable|string',
            'current_date' => 'required|date',
        ]);

        $employeeTodo->update($validated);

        return redirect()->back()->with('success', __('erp.todo_updated_success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeTodo $employeeTodo)
    {
        $employeeTodo->delete();

        return redirect()->back()->with('success', __('erp.todo_deleted_success'));
    }
}
