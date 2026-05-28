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

class ProposalController extends Controller
{
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

        if ($user->points_balance < $proposalCost) {
            return back()->withErrors(['points' => 'Insufficient points to submit a proposal.']);
        }

        if ($job->proposals()->where('freelancer_id', $user->id)->exists()) {
            return back()->withErrors(['proposal' => 'You have already submitted a proposal for this job.']);
        }

        DB::transaction(function () use ($validated, $user, $job, $proposalCost) {
            $job->proposals()->create([
                'freelancer_id' => $user->id,
                'cover_letter' => $validated['cover_letter'],
                'bid_amount' => $validated['bid_amount'],
                'currency_id' => $job->currency_id,
                'status' => 'pending',
            ]);

            $user->points_balance -= $proposalCost;
            $user->save();

            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $proposalCost,
                'type' => 'spent',
                'description' => "Submitted proposal for job: {$job->title}",
            ]);
        });

        return back()->with('success', 'Proposal submitted successfully.');
    }

    public function accept(Request $request, Proposal $proposal)
    {
        $job = $proposal->job;

        if ($job->client_id !== $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($job, $proposal) {
            $proposal->update(['status' => 'accepted']);
            $job->update(['status' => 'in_progress']);

            // Refund and reject other pending proposals
            $rejectedProposals = $job->proposals()->where('id', '!=', $proposal->id)->where('status', 'pending')->get();
            $job->proposals()->where('id', '!=', $proposal->id)->where('status', 'pending')->update(['status' => 'rejected']);

            $proposalCost = 2; // Fixed cost of proposal submission
            foreach ($rejectedProposals as $rejected) {
                $freelancer = $rejected->freelancer;
                if ($freelancer) {
                    $freelancer->points_balance += $proposalCost;
                    $freelancer->save();

                    PointTransaction::create([
                        'user_id' => $freelancer->id,
                        'points' => $proposalCost,
                        'type' => 'credit',
                        'description' => "Refunded staked points for job: {$job->title}",
                    ]);
                }
            }

            Contract::create([
                'job_id' => $job->id,
                'proposal_id' => $proposal->id,
                'client_id' => $job->client_id,
                'freelancer_id' => $proposal->freelancer_id,
                'amount' => $proposal->bid_amount,
                'currency_id' => $proposal->currency_id,
                'status' => 'active',
                'started_at' => now(),
            ]);
        });

        event(new ProposalAccepted($proposal));

        return back()->with('success', 'Proposal accepted and contract created.');
    }

    public function reject(Request $request, Proposal $proposal)
    {
        if ($proposal->job->client_id !== $request->user()->id) {
            abort(403);
        }

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
        if ($proposal->freelancer_id !== $request->user()->id) {
            abort(403);
        }

        if ($proposal->status !== 'pending') {
            return back()->withErrors(['proposal' => 'Cannot withdraw a non-pending proposal.']);
        }

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
