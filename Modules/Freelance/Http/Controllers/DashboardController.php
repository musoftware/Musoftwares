<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Proposal;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Fetch Real Active Proposals
        $activeProposals = Proposal::where('freelancer_id', $user->id)
            ->whereIn('status', ['submitted', 'under_review', 'shortlisted'])
            ->with('job:id,title')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($proposal) {
                return [
                    'id' => $proposal->id,
                    'title' => $proposal->job->title ?? 'Unknown Job',
                    'status' => $proposal->status,
                    'budget' => $proposal->bid_amount ?? 0,
                    'submittedAt' => $proposal->created_at->format('Y-m-d'),
                    'connectsUsed' => $proposal->connects_used ?? 0,
                ];
            });

        // 2. Fetch Real Active Contracts
        $activeContracts = Contract::where('freelancer_id', $user->id)
            ->where('status', 'in-progress')
            ->with('client:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'clientName' => $contract->client->name ?? 'Unknown Client',
                    'startDate' => $contract->created_at->format('Y-m-d'),
                    'value' => $contract->total_amount ?? 0,
                    'progress' => $this->calculateContractProgress($contract),
                    'status' => $contract->status,
                ];
            });

        // 3. Compute Real Stats
        $stats = [
            'pointsBalance' => collect($user->wallet)->sum('balance') ?? 0, // Using user's wallet logic
            'activeProposals' => Proposal::where('freelancer_id', $user->id)->whereIn('status', ['submitted', 'under_review'])->count(),
            'activeContracts' => Contract::where('freelancer_id', $user->id)->where('status', 'in-progress')->count(),
            'totalEarnings' => Contract::where('freelancer_id', $user->id)->where('status', 'completed')->sum('total_amount'),
        ];

        return Inertia::render('Freelance/Dashboard', [
            'activeProposals' => $activeProposals,
            'activeContracts' => $activeContracts,
            'stats' => $stats
        ]);
    }

    private function calculateContractProgress($contract)
    {
        // Fallback progress mechanism; could count completed milestones
        return 25; // Placeholder algorithm until milestone system is fully hydrated
    }
}
