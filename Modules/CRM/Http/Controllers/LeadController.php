<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use Modules\CRM\Services\LeadService;
use App\Http\Requests\CRM\Lead\UpdateLeadStatusRequest;
use App\Http\Resources\LeadResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Lead::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $leads = $query->with(['assignee'])->latest()->paginate(20)->through(fn($l) => (new LeadResource($l))->resolve());

        return Inertia::render('CRM/Leads/Index', [
            'leads' => $leads,
            'currentTab' => $status,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        try {
            $isTeam = \Illuminate\Support\Facades\Auth::guard('crm_team')->check();
            $agentId = $isTeam ? \Illuminate\Support\Facades\Auth::guard('crm_team')->id() : auth()->id();

            $lead = new Lead();
            $lead->fill([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'message' => $validated['message'] ?? '',
                'status' => 'new',
                'pipeline_stage' => 'NEW',
                'source' => 'Manual',
                'assigned_to' => $agentId,
            ]);

            $tenantContext = app(\Modules\CRM\Infrastructure\Context\TenantContext::class);
            $lead->workspace_id = $tenantContext->getWorkspaceId() ?? session('crm_workspace_id');
            if ($tenantContext->getBranchId()) {
                $lead->branch_id = $tenantContext->getBranchId();
            }

            $lead->save();

            return redirect()->back()->with('success', __('crm.lead_created_success'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to manually create lead: " . $e->getMessage());
            return redirect()->back()->with('error', __('crm.lead_creation_failed'));
        }
    }

    public function show(Lead $lead, \Modules\CRM\app\Core\TimelineEngine $timeline)
    {
        $lead->load(['notes.author', 'tags', 'assignee']);
        
        return response()->json([
            'lead' => (new LeadResource($lead))->resolve(),
            'timeline' => $timeline->getFeed($lead),
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead)
    {
        $this->leadService->updateStatus($lead, $request->validated('status'));

        return redirect()->back()->with('success', __('crm.lead_status_updated'));
    }

    public function assign(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $lead->update([
            'assigned_to' => $validated['assigned_to'],
        ]);

        return redirect()->back()->with('success', __('crm.lead_assignment_updated'));
    }

    public function destroy(Lead $lead)
    {
        $this->leadService->deleteLead($lead);

        return redirect()->back()->with('success', __('crm.lead_deleted'));
    }
}
