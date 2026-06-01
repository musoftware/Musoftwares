<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Proposal;
use Inertia\Inertia;

class FreelanceProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = Proposal::with(['job', 'freelancer']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->get('search');
            $query->whereHas('job', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })->orWhereHas('freelancer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $proposals = $query->latest()->paginate(20)->withQueryString();

        // Add formatted bid amount for the view
        $proposals->getCollection()->transform(function ($proposal) {
            // Assuming currency model relation exists or fallback to admin settings.
            // Simplified here just formatting the amount.
            $currency = $proposal->currency ? $proposal->currency->symbol : '$';
            $proposal->formatted_bid_amount = $currency . ' ' . number_format($proposal->bid_amount, 2);
            return $proposal;
        });

        return Inertia::render('Admin/Freelance/Proposals/Index', [
            'proposals' => $proposals,
            'filters'   => $request->only(['search', 'status'])
        ]);
    }

    public function show($id)
    {
        $proposal = Proposal::with(['job', 'freelancer'])->findOrFail($id);

        $currency = $proposal->currency ? $proposal->currency->symbol : '$';
        $proposal->formatted_bid_amount = $currency . ' ' . number_format($proposal->bid_amount, 2);

        return Inertia::render('Admin/Freelance/Proposals/Show', [
            'proposal' => $proposal
        ]);
    }

    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->delete();

        return back()->with('success', __('freelance.proposal_deleted', [], 'en'));
    }
}
