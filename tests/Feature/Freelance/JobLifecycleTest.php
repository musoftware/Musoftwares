<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Job;
use App\Models\PointTransaction;

class JobLifecycleTest extends FreelanceTestCase
{
    public function test_client_can_post_a_job_and_points_are_deducted(): void
    {
        $initialPoints = $this->clientUser->points_balance;

        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.jobs.store'), [
                'title' => 'E-Commerce Website',
                'description' => 'Need a complete online store.',
                'budget' => 2500.00,
                'currency_id' => $this->usdCurrency->id,
                'type' => 'fixed',
                'service_type' => 'remote',
                'duration' => '2 months',
                'min_proposal_points' => 5,
                'skills' => [$this->skill->id],
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_jobs', [
            'client_id' => $this->clientUser->id,
            'title' => 'E-Commerce Website',
            'status' => 'open',
        ]);

        // Points should be deducted (default is usually around 27 for a job post)
        $this->assertLessThan($initialPoints, $this->clientUser->fresh()->points_balance);
        
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'spent',
        ]);
    }

    public function test_client_can_edit_their_job(): void
    {
        $job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'title' => 'Old Title',
            'status' => 'open'
        ]);

        $response = $this->actingAs($this->clientUser)
            ->put(route('freelance.jobs.update', $job->id), [
                'title' => 'Updated E-Commerce Website',
                'description' => 'Need a complete online store.',
                'budget' => 3000.00,
                'currency_id' => $this->usdCurrency->id,
                'type' => 'fixed',
                'duration' => '3 months',
                'min_proposal_points' => 5,
                'skills' => [$this->skill->id],
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_jobs', [
            'id' => $job->id,
            'title' => 'Updated E-Commerce Website',
            'budget' => 3000.00,
        ]);
    }

    public function test_client_can_cancel_job_and_refund_proposals(): void
    {
        $job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'status' => 'open'
        ]);

        // Freelancer proposes
        $this->actingAs($this->freelancer1)
            ->post(route('freelance.proposals.store', $job->id), [
                'bid_amount' => 900.00,
                'cover_letter' => 'I can do it.',
                'delivery_time' => 10,
                'points_spent' => 10, // 10 points spent
            ]);

        $freelancerPointsAfterBid = $this->freelancer1->fresh()->points_balance;

        // Client cancels job
        $this->actingAs($this->clientUser)
            ->delete(route('freelance.jobs.destroy', $job->id));

        $this->assertEquals('cancelled', (string) $job->fresh()->status);

        // Verify freelancer got points back
        $this->assertEquals($freelancerPointsAfterBid + 10, $this->freelancer1->fresh()->points_balance);
        
        // Proposal should be rejected
        $this->assertDatabaseHas('freelance_proposals', [
            'job_id' => $job->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'rejected',
        ]);
    }
}
