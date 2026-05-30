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

    public function execute(SubmitProposalData $data, Job $job, User $user, int $proposalCost = 2)
    {
        if ($user->points_balance < $proposalCost) {
            throw new \Exception('Insufficient points to submit a proposal.');
        }

        if ($job->proposals()->where('freelancer_id', $user->id)->exists()) {
            throw new \Exception('You have already submitted a proposal for this job.');
        }

        return DB::transaction(function () use ($data, $user, $job, $proposalCost) {
            $proposal = $job->proposals()->create([
                'freelancer_id' => $data->freelancerId,
                'cover_letter' => $data->coverLetter,
                'bid_amount' => $data->bidAmount,
                'currency_id' => $data->currencyId,
                'status' => 'pending',
            ]);

            $this->deductPointsAction->execute(
                $user->id,
                $proposalCost,
                "Submitted proposal for job: {$job->title}",
                'proposal',
                $proposal->id
            );

            return $proposal;
        });
    }
}
