<?php

namespace Modules\Freelance\Domains\Contract\Actions;

use Modules\Freelance\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityService;

class CompleteContractAction
{
    public function execute(Contract $contract, User $client): void
    {
        if ($contract->client_id !== $client->id) {
            throw new \Exception('Unauthorized to complete this contract.');
        }

        if ($contract->status !== 'active') {
            throw new \Exception('Only active contracts can be completed.');
        }

        DB::transaction(function () use ($contract, $client) {
            // Update contract and job status
            $contract->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $contract->job->update(['status' => 'completed']);

            // Refund points for all other pending proposals
            app(\Modules\Freelance\Domains\Proposal\Actions\RejectPendingProposalsAction::class)
                ->execute($contract->job, 'Job Completed');

            ActivityService::log(
                event: 'contract.completed',
                description: "Contract completed for job: {$contract->job->title}",
                subject: $contract,
                workspace: 'freelance'
            );
        });
    }
}
