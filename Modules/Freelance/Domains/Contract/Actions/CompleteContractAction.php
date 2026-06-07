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

            $freelancer = User::findOrFail($contract->freelancer_id);
            $clientModel = User::findOrFail($client->id); // Ensure we have latest from DB for locking

            if ($contract->amount > 0) {
                // Deduct from client
                $clientModel->decrement('user_balance', $contract->amount);
                
                // Add to freelancer
                $freelancer->increment('user_balance', $contract->amount);
                
                // Client transaction record
                \App\Models\Transaction::create([
                    'user_id' => $clientModel->id,
                    'amount' => $contract->amount,
                    'type' => 'used',
                    'reason' => "Paid for completed freelance contract #{$contract->id}",
                    'currency_id' => $contract->currency_id ?? $clientModel->currency_id
                ]);
                
                // Freelancer transaction record
                \App\Models\Transaction::create([
                    'user_id' => $freelancer->id,
                    'amount' => $contract->amount,
                    'type' => 'earned',
                    'reason' => "Earned from completed freelance contract #{$contract->id}",
                    'currency_id' => $contract->currency_id ?? $freelancer->currency_id
                ]);
            }

            ActivityService::log(
                event: 'contract.completed',
                description: "Contract completed for job: {$contract->job->title}",
                subject: $contract,
                workspace: 'freelance'
            );
        });
    }
}
