<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CRM\Models\Lead;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = Lead::where('user_id', $request->user()->id)
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('CRM/Leads/Index', [
            'leads' => $leads
        ]);
    }

    public function show(Request $request, Lead $lead)
    {
        if ($lead->user_id !== $request->user()->id) {
            abort(403);
        }

        $lead->load('campaign');

        return Inertia::render('CRM/Leads/Show', [
            'lead' => $lead
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        if ($lead->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|string|max:50'
        ]);

        $lead->status = $validated['status'];
        $lead->save();

        return redirect()->back()->with('success', 'Lead status updated.');
    }
}
