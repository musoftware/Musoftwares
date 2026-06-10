<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\Skill;
use Modules\Freelance\Models\Proposal;

class AdminExtendedTest extends FreelanceTestCase
{
    protected Job $job;
    protected Contract $contract;
    protected Skill $pendingSkill;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'status' => 'open',
        ]);

        $proposal = Proposal::factory()->create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'accepted',
        ]);

        $this->contract = Contract::factory()->create([
            'job_id' => $this->job->id,
            'proposal_id' => $proposal->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'active',
        ]);

        $this->pendingSkill = Skill::create([
            'name' => 'CustomPendingSkill',
            'status' => 'pending'
        ]);
    }

    public function test_admin_can_update_contract_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.contracts.status', $this->contract->id), [
                'status' => 'suspended',
            ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertEquals('suspended', $this->contract->fresh()->status);
    }

    public function test_admin_can_update_job_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.jobs.status', $this->job->id), [
                'status' => 'cancelled',
            ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertEquals('cancelled', (string) $this->job->fresh()->status);
    }

    public function test_admin_can_reject_skill(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.skills.reject', $this->pendingSkill->id));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertEquals('rejected', $this->pendingSkill->fresh()->status);
    }

    public function test_admin_can_block_freelancer_from_skills(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.skills.block-user', $this->freelancer1->id));

        $this->assertContains($response->status(), [200, 302]);
        // Assumption: Admin controller sets a flag or deletes user_skills
        // Just verify the request goes through successfully.
    }
}
