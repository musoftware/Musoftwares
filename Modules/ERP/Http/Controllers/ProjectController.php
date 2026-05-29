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
use Modules\ERP\Http\Requests\StoreProjectRequest;
use Modules\ERP\Http\Requests\UpdateProjectRequest;
use Modules\ERP\Services\ProjectService;
use Modules\ERP\Transformers\ProjectDashboardResource;
use Modules\ERP\Transformers\InvoiceResource;
use Modules\ERP\Transformers\TransactionResource;
use Modules\ERP\Transformers\ExpenseResource;
use Modules\ERP\Transformers\ProjectTaskResource;
use Modules\ERP\Transformers\TicketResource;
use Modules\ERP\Transformers\ActivityResource;

class ProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }
    public function create()
    {
        return Inertia::render('ERP/Projects/Create', [
            'clients' => [], // Searched dynamically on the frontend via API
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        
        if (!$tenant) {
            return back()->withErrors(['error' => __('errors.no_active_workspace')]);
        }

        $this->projectService->createProject($request->validated(), $tenant, $user->id);

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

    /**
     * Update the specified project in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        $this->projectService->updateProject($project, $request->validated(), $tenant);

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', __('erp.project_updated_success'));
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $this->projectService->deleteProject($project);

        return back()->with('success', __('erp.project_deleted_success'));
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : 'USD';

        $data = $this->projectService->getProjectDashboardStats($project);

        $stats = $data['stats'];
        $stats['businessCurrency'] = $businessCurrency;
        $stats['projectCurrency'] = $project->currency?->currency ?? 'USD';

        return Inertia::render('ERP/Projects/Show', [
            'project' => ProjectDashboardResource::make($data['project'])->resolve(),
            'stats' => $stats,
            'invoices' => InvoiceResource::collection($data['invoices'])->resolve(),
            'transactions' => TransactionResource::collection($data['transactions'])->resolve(),
            'expenses' => ExpenseResource::collection($data['expenses'])->resolve(),
            'tasks' => ProjectTaskResource::collection($data['tasks'])->resolve(),
            'tickets' => TicketResource::collection($data['tickets'])->resolve(),
            'activities' => ActivityResource::collection($data['activities'])->resolve(),
            'hasTickets' => $user->hasModuleSubscription('erp-tickets'),
        ]);
    }
}
