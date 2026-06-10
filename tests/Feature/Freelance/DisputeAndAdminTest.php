<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;

class DisputeAndAdminTest extends FreelanceTestCase
{
    protected Job $job;
    protected Proposal $proposal;
    protected Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'status' => 'in_progress',
            'budget' => 1000.00,
            'currency_id' => $this->usdCurrency->id,
        ]);

        $this->proposal = Proposal::factory()->create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer1->id,
            'bid_amount' => 900.00,
            'points_spent' => 5,
            'status' => 'accepted',
        ]);

        $this->contract = Contract::factory()->create([
            'job_id' => $this->job->id,
            'proposal_id' => $this->proposal->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancer1->id,
            'amount' => 900.00,
            'status' => 'active',
        ]);
    }

    public function test_parties_can_raise_dispute_and_admin_resolves_it(): void
    {
        // Client disputes
        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.contracts.dispute', $this->contract->id));

        $response->assertStatus(302);
        $this->assertEquals('disputed', $this->contract->fresh()->status);

        // Admin resolves dispute
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.contracts.resolve-dispute', $this->contract->id), [
                'resolution' => 'refund_client',
                'admin_notes' => 'Freelancer did not deliver.',
            ]);

        $this->assertContains($response->status(), [200, 302]);
        
        $this->assertEquals('cancelled', $this->contract->fresh()->status);
    }

    public function test_admin_can_force_refund_a_job(): void
    {
        // Admin force refunds job
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.freelance.jobs.force-refund', $this->job->id));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertEquals('cancelled', $this->job->fresh()->status);

        // Because Job is cancelled, the Contract should be cancelled
        $this->assertEquals('cancelled', $this->contract->fresh()->status);
    }
}
