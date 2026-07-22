<?php

namespace Modules\Marketplace\Services;

use App\Models\Project;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Marketplace\Services\EscrowService;

class CustomProjectService
{
    /**
     * Create client custom project brief.
     */
    public function createProject(User $client, array $data): Project
    {
        return Project::create([
            'user_id' => $client->id,
            'project_name' => $data['title'],
            'description' => $data['description'],
            'budget' => $data['budget'],
            'currency_id' => $data['currency_id'] ?? 1,
            'deadline' => $data['deadline'] ?? null,
            'status' => 'open',
            'created_at' => now('Africa/Cairo'),
        ]);
    }


    /**
     * Submit freelancer bid / proposal on project brief.
     */
    public function submitProposal(Project $project, User $freelancer, array $data): array
    {
        if ($project->status !== 'open') {
            throw new Exception("This project brief is no longer accepting bids.");
        }

        if ($project->user_id === $freelancer->id) {
            throw new Exception("You cannot bid on your own project brief.");
        }

        $proposals = $project->proposals ?? [];
        $proposalId = count($proposals) + 1;

        $newProposal = [
            'id' => $proposalId,
            'freelancer_id' => $freelancer->id,
            'freelancer_name' => $freelancer->name,
            'price' => $data['price'],
            'delivery_days' => $data['delivery_days'],
            'proposal_letter' => $data['proposal_letter'],
            'status' => 'pending',
            'submitted_at' => now('Africa/Cairo')->toDateTimeString(),
        ];

        $proposals[] = $newProposal;
        $project->update(['proposals' => $proposals]);

        return $newProposal;
    }

    /**
     * Accept proposal and initialize milestone contract.
     */
    public function acceptProposalAndCreateContract(Project $project, int $freelancerId, float $contractAmount): Contract
    {
        return DB::transaction(function () use ($project, $freelancerId, $contractAmount) {
            $project->update(['status' => 'awarded']);

            $contract = Contract::create([
                'user_id' => $project->user_id,
                'freelancer_id' => $freelancerId,
                'project_id' => $project->id,
                'project_name' => $project->project_name ?? 'Milestone Contract',
                'total_amount' => $contractAmount,
                'status' => 'active',
                'created_at' => now('Africa/Cairo'),
            ]);



            return $contract;
        });
    }
}
