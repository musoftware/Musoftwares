<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use App\Services\LeadService;
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

        $leads = $query->with(['assignable'])->latest()->paginate(20)->through(fn($l) => (new LeadResource($l))->resolve());

        return Inertia::render('CRM/Leads/Index', [
            'leads' => $leads,
            'currentTab' => $status,
        ]);
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

        return redirect()->back()->with('success', 'Lead status updated.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'assignable_id' => 'required|integer',
            'assignable_type' => 'required|string|in:App\Models\User,Modules\ERP\Models\TeamMember',
        ]);

        $lead->update([
            'assignable_id' => $validated['assignable_id'],
            'assignable_type' => $validated['assignable_type'],
        ]);

        return redirect()->back()->with('success', 'Lead assignment updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->leadService->deleteLead($lead);

        return redirect()->back()->with('success', 'Lead deleted successfully.');
    }
}
