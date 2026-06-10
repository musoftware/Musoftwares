<?php

namespace Modules\Freelance\Domains\Proposal\Actions;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\PointTransaction;
use App\Models\User;

class RejectPendingProposalsAction
{
    public function execute(Job $job, string $reasonContext = 'Job Cancelled/Completed'): void
    {
        $pendingProposals = Proposal::where('job_id', $job->id)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingProposals as $proposal) {
            $proposal->update(['status' => 'rejected']);

            $proposalFreelancer = User::find($proposal->freelancer_id);
            if ($proposalFreelancer) {
                $proposalCost = $proposal->points_spent ?? 2;
                $proposalFreelancer->increment('points_balance', $proposalCost);

                PointTransaction::create([
                    'user_id' => $proposalFreelancer->id,
                    'points' => $proposalCost,
                    'type' => 'credit',
                    'description' => "Refunded staked points for job: {$job->title} ({$reasonContext})",
                ]);
            }
        }
    }
}
