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

    public function execute(Proposal $proposal, User $client, int $proposalCostRefund = 2): Contract
    {
        $job = $proposal->job;

        if ($job->client_id !== $client->id) {
            throw new \Exception('Unauthorized to accept proposals for this job.');
        }

        if ($client->user_balance < $proposal->bid_amount) {
            throw new \Exception('Insufficient funds in your platform wallet to start this contract.');
        }

        return DB::transaction(function () use ($job, $proposal, $client, $proposalCostRefund) {
            // Escrow: Deduct funds from client immediately
            $client->add_balance(
                -$proposal->bid_amount,
                "Escrow locked for contract on job: {$job->title}",
                'sent', // 'sent' deducts balance
                $proposal->currency_id
            );

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
                        $proposalCostRefund,
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
                'amount' => $proposal->bid_amount,
                'currency_id' => $proposal->currency_id,
                'status' => 'active',
                'started_at' => now(),
            ]);

            event(new ProposalAccepted($proposal));

            return $contract;
        });
    }
}
