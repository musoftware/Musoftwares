<?php

namespace App\Http\Controllers\Isaas;

use App\Http\Controllers\Controller;
use App\Models\Billing\ProjectProposal;
use App\Models\Billing\PlatformContract;
use App\Services\AI\PriceCalculatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProjectProposalController extends Controller
{
    public function index(Request $request)
    {
        $proposals = ProjectProposal::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('iSaaS/Proposals/Index', [
            'proposals' => $proposals
        ]);
    }

    public function create()
    {
        return Inertia::render('iSaaS/Proposals/Create');
    }

    public function estimate(Request $request, PriceCalculatorService $calculator)
    {
        $request->validate([
            'requirements' => 'required|string|min:10',
            'project_name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        // Optional: rate limit here based on $request->user() to prevent abuse

        $estimate = $calculator->estimate($request->requirements);

        if (!$estimate) {
            return back()->withErrors(['error' => 'Failed to generate AI estimate. Please try again.']);
        }

        $proposal = ProjectProposal::create([
            'user_id' => $request->user()->id,
            'project_name' => $request->project_name,
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'requirements' => $request->requirements,
            'ai_estimate' => $estimate,
            'total_amount' => $estimate['total_cost'] ?? 0,
            'status' => 'draft',
        ]);

        return redirect()->route('isaas.proposals.show', $proposal->id)->with('success', __('general.ai_estimate_generated_successfully'));
    }

    public function show(Request $request, $id)
    {
        $proposal = ProjectProposal::where('user_id', $request->user()->id)->findOrFail($id);

        return Inertia::render('iSaaS/Proposals/Show', [
            'proposal' => $proposal
        ]);
    }

    public function update(Request $request, $id)
    {
        $proposal = ProjectProposal::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'ai_estimate' => 'required|array',
            'total_amount' => 'required|numeric|min:0',
            'project_name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
        ]);

        $proposal->update($request->only(['ai_estimate', 'total_amount', 'project_name', 'client_name']));

        return back()->with('success', __('general.proposal_updated_successfully'));
    }

    public function convert(Request $request, $id)
    {
        $proposal = ProjectProposal::where('user_id', $request->user()->id)->findOrFail($id);

        if ($proposal->status === 'converted') {
            return back()->withErrors(['error' => 'This proposal has already been converted to a contract.']);
        }

        try {
            DB::beginTransaction();

            $contract = PlatformContract::create([
                'user_id' => $request->user()->id,
                'project_proposal_id' => $proposal->id,
                'project_name' => $proposal->project_name,
                'client_name' => $proposal->client_name,
                'description' => $proposal->requirements,
                'items' => $proposal->ai_estimate['items'] ?? [],
                'total_amount' => $proposal->total_amount,
                'currency_id' => $proposal->currency_id,
                'status' => 'draft',
            ]);

            $proposal->update(['status' => 'converted']);

            DB::commit();

            return redirect()->route('isaas.contracts.edit', $contract->id)->with('success', __('general.proposal_successfully_converted_to_contract'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to convert to contract: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $id)
    {
        $proposal = ProjectProposal::where('user_id', $request->user()->id)->findOrFail($id);
        $proposal->delete();

        return redirect()->route('isaas.proposals.index')->with('success', __('general.proposal_deleted'));
    }
}
