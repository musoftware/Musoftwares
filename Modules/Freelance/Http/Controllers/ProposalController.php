<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Events\ProposalAccepted;
use Modules\Freelance\Domains\Contract\Actions\AcceptProposalAction;
use Modules\Freelance\Domains\Proposal\Actions\SubmitProposalAction;
use Modules\Freelance\Domains\Proposal\DTOs\SubmitProposalData;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
    public function __construct(
        private AcceptProposalAction $acceptProposalAction,
        private SubmitProposalAction $submitProposalAction
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $proposals = Proposal::where('freelancer_id', $user->id)
            ->with('job:id,title,budget,currency_id,type,status')
            ->latest()
            ->paginate(20);

        $stats = [
            'total'    => Proposal::where('freelancer_id', $user->id)->count(),
            'pending'  => Proposal::where('freelancer_id', $user->id)->where('status', 'pending')->count(),
            'accepted' => Proposal::where('freelancer_id', $user->id)->where('status', 'accepted')->count(),
            'rejected' => Proposal::where('freelancer_id', $user->id)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Freelance/Proposals/Index', [
            'proposals' => $proposals,
            'stats'     => $stats,
        ]);
    }

    public function store(Request $request, Job $job)
    {
        $validated = $request->validate([
            'cover_letter' => 'required|string',
            'bid_amount' => 'required|numeric|min:0',
        ]);

        $proposalCost = 2; // Cost to submit proposal
        $user = $request->user();

        $data = new SubmitProposalData(
            jobId: $job->id,
            freelancerId: $user->id,
            coverLetter: $validated['cover_letter'],
            bidAmount: $validated['bid_amount'],
            currencyId: $job->currency_id,
        );

        try {
            $this->submitProposalAction->execute($data, $job, $user, $proposalCost);
        } catch (\Exception $e) {
            return back()->withErrors(['proposal' => $e->getMessage()]);
        }

        return back()->with('success', 'Proposal submitted successfully.');
    }

    public function accept(Request $request, Proposal $proposal)
    {
        Gate::authorize('accept', $proposal);

        try {
            $this->acceptProposalAction->execute($proposal, $request->user());
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        event(new ProposalAccepted($proposal));

        return back()->with('success', 'Proposal accepted and contract created.');
    }

    public function reject(Request $request, Proposal $proposal)
    {
        Gate::authorize('reject', $proposal);

        DB::transaction(function () use ($proposal) {
            if ($proposal->status === 'pending') {
                $proposal->update(['status' => 'rejected']);

                $freelancer = $proposal->freelancer;
                $proposalCost = 2; // Fixed cost of proposal submission
                $freelancer->points_balance += $proposalCost;
                $freelancer->save();

                PointTransaction::create([
                    'user_id' => $freelancer->id,
                    'points' => $proposalCost,
                    'type' => 'credit',
                    'description' => "Refunded staked points for job: {$proposal->job->title}",
                ]);
            }
        });

        return back()->with('success', 'Proposal rejected.');
    }

    public function withdraw(Request $request, Proposal $proposal)
    {
        Gate::authorize('withdraw', $proposal);

        DB::transaction(function () use ($proposal) {
            $freelancer = $proposal->freelancer;
            $proposalCost = 2; // Fixed cost of proposal submission
            $freelancer->points_balance += $proposalCost;
            $freelancer->save();

            PointTransaction::create([
                'user_id' => $freelancer->id,
                'points' => $proposalCost,
                'type' => 'credit',
                'description' => "Refunded staked points (withdrawn) for job: {$proposal->job->title}",
            ]);

            $proposal->delete();
        });

        return back()->with('success', 'Proposal withdrawn.');
    }
}
