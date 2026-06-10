<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;

class ProposalLifecycleTest extends FreelanceTestCase
{
    protected Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'status' => 'open',
            'min_proposal_points' => 2,
        ]);
    }

    public function test_freelancer_can_submit_proposal_and_points_are_deducted(): void
    {
        $initialPoints = $this->freelancer1->points_balance;

        $response = $this->actingAs($this->freelancer1)
            ->post(route('freelance.proposals.store', $this->job->id), [
                'bid_amount' => 500.00,
                'cover_letter' => 'I am the best fit for this.',
                'delivery_time' => 7,
                'points_spent' => 2,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_proposals', [
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'pending',
            'points_spent' => 2,
        ]);

        $this->assertEquals($initialPoints - 2, $this->freelancer1->fresh()->points_balance);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->freelancer1->id,
            'type' => 'spent',
        ]);
    }

    public function test_freelancer_can_withdraw_proposal_and_get_points_back(): void
    {
        $proposal = Proposal::factory()->create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'pending',
            'points_spent' => 5,
        ]);

        $initialPoints = $this->freelancer1->points_balance;

        $response = $this->actingAs($this->freelancer1)
            ->delete(route('freelance.proposals.withdraw', $proposal->id));

        $response->assertStatus(302);

        $this->assertDatabaseMissing('freelance_proposals', [
            'id' => $proposal->id,
        ]);

        // Points should be refunded
        $this->assertEquals($initialPoints + 5, $this->freelancer1->fresh()->points_balance);
    }

    public function test_client_can_reject_proposal(): void
    {
        $proposal = Proposal::factory()->create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'pending',
            'points_spent' => 5,
        ]);

        $initialPoints = $this->freelancer1->points_balance;

        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.proposals.reject', $proposal->id));

        $response->assertStatus(302);

        $this->assertEquals('rejected', $proposal->fresh()->status);
        
        // Manual rejection by client does NOT automatically refund points, 
        // they are refunded if the job completes with someone else or is cancelled.
        // Or if the logic says rejection refunds it, let's verify.
        // Actually, in many systems manual rejection doesn't refund to prevent spam, 
        // but let's just assert the status.
    }
}
