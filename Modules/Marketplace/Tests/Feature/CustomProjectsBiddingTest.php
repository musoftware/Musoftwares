<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Services\CustomProjectService;

class CustomProjectsBiddingTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_project_brief_creation_and_proposal_bidding()
    {
        $client = User::factory()->create();
        $freelancer = User::factory()->create();

        $projectService = new CustomProjectService();

        // 1. Client posts custom project brief
        $project = $projectService->createProject($client, [
            'title' => 'Build a Custom Laravel SaaS Module',
            'description' => 'Need an independent module created in Laravel',
            'budget' => 500,
            'deadline' => now()->addDays(14)->toDateString(),
        ]);

        $this->assertEquals($client->id, $project->user_id);
        $this->assertEquals('open', $project->status);

        // 2. Freelancer submits bid/proposal
        $proposal = $projectService->submitProposal($project, $freelancer, [
            'price' => 450,
            'delivery_days' => 10,
            'proposal_letter' => 'I can build this SaaS module cleanly.',
        ]);

        $this->assertEquals($freelancer->id, $proposal['freelancer_id']);
        $this->assertEquals(450, $proposal['price']);

        // 3. Client accepts proposal and creates contract
        $contract = $projectService->acceptProposalAndCreateContract($project, $freelancer->id, 450);

        $this->assertEquals($client->id, $contract->user_id);
        $this->assertEquals('active', $contract->status);


        $project->refresh();
        $this->assertEquals('awarded', $project->status);
    }
}
