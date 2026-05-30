<?php

namespace Modules\Freelance\Domains\Contract\Actions;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Events\ProposalAccepted;
use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;

class AcceptProposalAction
{
    public function __construct(private AddPointsAction $addPointsAction) {}

    public function execute(Proposal $proposal, User $client): Contract
    {
        $job = $proposal->job;

        if ($job->client_id !== $client->id) {
            throw new \Exception('Unauthorized to accept proposals for this job.');
        }

        return DB::transaction(function () use ($job, $proposal, $client) {
            $job = Job::where('id', $job->id)->lockForUpdate()->first();

            if (! $job->status->equals(\Modules\Freelance\Domains\Job\States\Open::class)) {
                throw new \Exception('Job is no longer open for accepting proposals.');
            }

            $proposal->update(['status' => 'accepted']);
            $job->update(['status' => 'in_progress']);

            // Reject other pending proposals
            $rejectedProposals = $job->proposals()->where('id', '!=', $proposal->id)->where('status', 'pending')->get();
            $job->proposals()->where('id', '!=', $proposal->id)->where('status', 'pending')->update(['status' => 'rejected']);

            // Refund points to rejected freelancers
            foreach ($rejectedProposals as $rejected) {
                $freelancer = $rejected->freelancer;
                if ($freelancer) {
                    $this->addPointsAction->execute(
                        $freelancer->id,
                        $rejected->points_spent,
                        "Refunded staked points for job: {$job->title}",
                        'job_refund',
                        $job->id
                    );
                }
            }

            $contract = Contract::create([
                'job_id' => $job->id,
                'proposal_id' => $proposal->id,
                'client_id' => $job->client_id,
                'freelancer_id' => $proposal->freelancer_id,
                'contract_points' => $proposal->proposed_budget_points,
                'status' => 'active',
                'started_at' => now(),
            ]);

            event(new ProposalAccepted($proposal));

            return $contract;
        });
    }
}
