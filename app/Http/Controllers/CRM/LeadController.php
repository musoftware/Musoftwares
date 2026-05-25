<?php

namespace App\Http\Controllers\CRM;

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

        $leads = $query->latest()->paginate(20)->through(fn($l) => (new LeadResource($l))->resolve());

        return Inertia::render('CRM/Leads/Index', [
            'leads' => $leads,
            'currentTab' => $status,
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead)
    {
        $this->leadService->updateStatus($lead, $request->validated('status'));

        return redirect()->back()->with('success', 'Lead status updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->leadService->deleteLead($lead);

        return redirect()->back()->with('success', 'Lead deleted successfully.');
    }
}
