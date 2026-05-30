<?php

namespace Modules\Freelance\Domains\Proposal\Actions;

use Modules\Freelance\Domains\Proposal\DTOs\SubmitProposalData;
use Modules\Freelance\Models\Job;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use Modules\Freelance\Domains\Finance\Actions\DeductPointsAction;

class SubmitProposalAction
{
    public function __construct(private DeductPointsAction $deductPointsAction) {}

    public function execute(SubmitProposalData $data, Job $job, User $user)
    {
        if ($data->pointsSpent < $job->min_proposal_points) {
            throw new \Exception("Minimum proposal bid is {$job->min_proposal_points} points.");
        }

        if ($user->points_balance < $data->pointsSpent) {
            throw new \Exception('Insufficient points to submit a proposal.');
        }

        if ($job->proposals()->where('freelancer_id', $user->id)->exists()) {
            throw new \Exception('You have already submitted a proposal for this job.');
        }

        return DB::transaction(function () use ($data, $user, $job) {
            $proposal = $job->proposals()->create([
                'freelancer_id' => $data->freelancerId,
                'cover_letter' => $data->coverLetter,
                'proposed_budget_points' => $data->proposedBudgetPoints,
                'points_spent' => $data->pointsSpent,
                'status' => 'pending',
            ]);

            $this->deductPointsAction->execute(
                $user->id,
                $data->pointsSpent,
                "Submitted proposal for job: {$job->title}",
                'proposal',
                $proposal->id
            );

            return $proposal;
        });
    }
}
