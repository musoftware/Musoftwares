<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceCost;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\SupportTicket;
use Modules\ERP\Models\Activity;
use Modules\ERP\Services\ActivityLogger;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        $clients = TenantClient::where('tenant_id', $tenant->id)->get(['id', 'name']);

        return Inertia::render('ERP/Projects/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        
        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $user->id,
        ]);

        ActivityLogger::log(
            'project_created',
            "Project '{$project->name}' was created.",
            $project,
            $project->client_id
        );

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        if ($project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $clients = TenantClient::where('tenant_id', $tenant->id)->get(['id', 'name']);

        return Inertia::render('ERP/Projects/Edit', [
            'project' => $project,
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        // Ensure user owns this project via tenant
        if (!$tenant || $project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $project->update([
            'name' => $validated['name'],
            'client_id' => $validated['client_id'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        ActivityLogger::log(
            'project_updated',
            "Project '{$project->name}' was updated.",
            $project,
            $project->client_id
        );

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $name = $project->name;
        $project->delete();

        ActivityLogger::log(
            'project_deleted',
            "Project '{$name}' was deleted.",
            null,
            null
        );

        return back()->with('success', 'Project deleted successfully.');
    }

    /**
     * Display the specified project with detailed statistics, invoices, expenses, tasks, and tickets.
     */
    public function show(Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        if ($project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        // Get tenant base currency
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : 'USD';

        // Load project relationships
        $project->load(['tenantClient', 'platformClient', 'creator']);

        // Get invoices linked to this project
        $invoices = Invoice::where('project_id', $project->id)
            ->with(['currency'])
            ->latest()
            ->get();

        // Stats calculation
        $paidInvoices = $invoices->where('status', 'paid');
        $unpaidInvoices = $invoices->whereIn('status', ['sent', 'partial']);
        
        $paidCount = $paidInvoices->count();
        $unpaidCount = $unpaidInvoices->count();
        $totalCount = $invoices->count();

        // Calculate invoiced totals in base currency (business_amount)
        $totalInvoicedBusiness = (float) $invoices->sum('business_amount');
        $totalPaidBusiness = (float) $paidInvoices->sum('business_amount');
        $totalUnpaidBusiness = (float) $unpaidInvoices->sum('business_amount');

        // Project expenses (Invoice costs of linked invoices)
        $invoiceIds = $invoices->pluck('id');
        $expenses = InvoiceCost::whereIn('invoice_id', $invoiceIds)
            ->with(['payer', 'currency'])
            ->latest()
            ->get();

        $totalExpensesBusiness = (float) $expenses->sum('business_amount');

        // Net income/revenue
        $netRevenueBusiness = $totalPaidBusiness - $totalExpensesBusiness;

        // Fetch tasks
        $tasks = ERPTask::where('project_id', $project->id)
            ->with(['creator', 'assignee'])
            ->latest()
            ->get()
            ->map(function ($task) {
                $category = 'Todo';
                if ($task->status === 'in_progress') $category = 'In Progress';
                if ($task->status === 'review') $category = 'In Review';
                if ($task->status === 'completed') $category = 'Done';
                return [
                    'id' => $task->id,
                    'title' => $task->task_name,
                    'due' => $task->due_date ? $task->due_date->format('M j, Y') : 'No due date',
                    'assignee' => $task->assignee ? $task->assignee->name : ($task->creator ? $task->creator->name : 'Unassigned'),
                    'priority' => ucfirst($task->priority ?? 'Normal'),
                    'category' => $category,
                ];
            });

        // Fetch support tickets
        $tickets = SupportTicket::where('project_id', $project->id)
            ->with(['assignee', 'creator'])
            ->latest()
            ->get();

        // Activity log
        $activities = Activity::with('causer')
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'title' => $activity->action,
                    'time' => $activity->created_at?->diffForHumans(),
                    'description' => $activity->description,
                    'user' => $activity->causer?->name ?? 'System',
                ];
            });

        return Inertia::render('ERP/Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'budget' => round((float) $project->budget, 2),
                'deadline' => $project->due_date?->format('Y-m-d'),
                'created_at' => $project->created_at?->format('Y-m-d'),
                'client' => $project->client ? [
                    'id' => $project->client->id,
                    'name' => $project->client->name,
                    'email' => $project->client->email,
                ] : null,
                'leader' => $project->creator?->name ?? '-',
            ],
            'stats' => [
                'businessCurrency' => $businessCurrency,
                'paidInvoicesCount' => $paidCount,
                'unpaidInvoicesCount' => $unpaidCount,
                'totalInvoicesCount' => $totalCount,
                'totalInvoiced' => round($totalInvoicedBusiness, 2),
                'totalPaid' => round($totalPaidBusiness, 2),
                'totalUnpaid' => round($totalUnpaidBusiness, 2),
                'totalExpenses' => round($totalExpensesBusiness, 2),
                'netRevenue' => round($netRevenueBusiness, 2),
            ],
            'invoices' => $invoices->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'status' => $inv->status,
                    'amount' => round((float) $inv->amount, 2),
                    'business_amount' => round((float) $inv->business_amount, 2),
                    'currency' => $inv->amount_currency,
                    'created_at' => $inv->created_at?->format('Y-m-d'),
                ];
            }),
            'expenses' => $expenses->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'title' => $exp->title,
                    'description' => $exp->description,
                    'amount' => round((float) $exp->amount, 2),
                    'business_amount' => round((float) $exp->business_amount, 2),
                    'currency' => $exp->currency?->currency ?? 'USD',
                    'date' => $exp->created_at?->format('Y-m-d'),
                    'payer' => $exp->payer?->name ?? 'System',
                ];
            }),
            'tasks' => $tasks,
            'tickets' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'assignee' => $ticket->assignee?->name ?? 'Unassigned',
                    'created_at' => $ticket->created_at?->format('Y-m-d'),
                ];
            }),
            'activities' => $activities,
            'hasTickets' => $user->hasModuleSubscription('erp-tickets'),
        ]);
    }
}
