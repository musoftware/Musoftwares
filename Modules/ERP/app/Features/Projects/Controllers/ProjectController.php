<?php

namespace Modules\ERP\app\Features\Projects\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Closure;
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

class ProjectController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, Closure $next) {
                $user = Auth::user();
                if (Auth::guard('erp_team')->check()) {
                    $user = Auth::guard('erp_team')->user()->tenant->user;
                }
                if (!$user || !$user->hasModuleSubscription('erp-projects')) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json(['message' => 'Projects addon required.'], 403);
                    }
                    return Inertia::render('ERP/UpgradePreview', ['module' => 'erp-projects']);
                }
                return $next($request);
            }),
        ];
    }

    public function create()
    {
        return Inertia::render('ERP/Projects/Create', [
            'clients' => [], // Searched dynamically on the frontend via API
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return back()->withErrors(['error' => __('errors.no_active_workspace')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $client = TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'currency_id' => $client->currency_id,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogger::log(
            'project_created',
            "Project '{$project->name}' was created.",
            $project,
            $project->client_id
        );

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', __('erp.project_created_success'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        // Only load the client associated with the project to pre-fill the combobox
        $clients = TenantClient::where('id', $project->client_id)->get(['id', 'name']);

        return Inertia::render('ERP/Projects/Edit', [
            'project' => $project,
            'clients' => $clients,
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->input('q');
        $clientId = $request->input('client_id');
        $tenant = $request->user()->tenant;
        
        if (!$tenant) {
            return response()->json([]);
        }

        $projects = Project::where('tenant_id', $tenant->id)
            ->when($clientId, function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'client_id']);
            
        return response()->json($projects);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $client = TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);

        $project->update([
            'name' => $validated['name'],
            'client_id' => $validated['client_id'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'currency_id' => $client->currency_id,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        ActivityLogger::log(
            'project_updated',
            "Project '{$project->name}' was updated.",
            $project,
            $project->client_id
        );

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', __('erp.project_updated_success'));
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $name = $project->name;
        $project->delete();

        ActivityLogger::log(
            'project_deleted',
            "Project '{$name}' was deleted.",
            null,
            null
        );

        return back()->with('success', __('erp.project_deleted_success'));
    }

    /**
     * Display the specified project with detailed statistics, invoices, expenses, tasks, and tickets.
     */
    public function show(Project $project)
    {
        $this->authorize('update', $project);

        $user = auth()->user();
        $tenant = $user->tenant;
        if (!$tenant || !$tenant->base_currency_id) {
            abort(400, "Tenant configuration missing base currency.");
        }
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : null;

        // Load project relationships
        $project->load(['client', 'creator', 'currency']);

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

        // Get transactions linked to this project
        $transactions = \Modules\ERP\Models\WalletTransaction::where('project_id', $project->id)
            ->with(['creator', 'currency'])
            ->latest()
            ->get();

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
                'currency' => $project->currency ? [
                    'id' => $project->currency->id,
                    'currency' => $project->currency->currency,
                ] : null,
            ],
            'stats' => [
                'businessCurrency' => $businessCurrency,
                'projectCurrency' => $project->currency?->currency ?? null,
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
            'transactions' => $transactions->map(function ($txn) use ($businessCurrency) {
                return [
                    'id' => $txn->id,
                    'reference_id' => '#TXN-' . str_pad($txn->id, 4, '0', STR_PAD_LEFT),
                    'type' => $txn->type,
                    'note' => $txn->note ?? 'No details provided',
                    'direction' => strtoupper($txn->direction),
                    'amount' => round($txn->amount, 2),
                    'business_amount' => round($txn->business_amount ?? $txn->amount, 2),
                    'currency' => $txn->currency?->currency ?? $businessCurrency,
                    'date' => $txn->created_at?->format('Y-m-d H:i'),
                    'authorizer' => $txn->creator?->name ?? 'System Core',
                ];
            }),
            'expenses' => $expenses->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'title' => $exp->title,
                    'description' => $exp->description,
                    'amount' => round((float) $exp->amount, 2),
                    'business_amount' => round((float) $exp->business_amount, 2),
                    'currency' => $exp->currency?->currency ?? null,
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

