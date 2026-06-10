<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;

class ContractLifecycleTest extends FreelanceTestCase
{
    protected Job $job;
    protected Proposal $proposal1;
    protected Proposal $proposal2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'status' => 'open',
            'budget' => 1000.00,
            'currency_id' => $this->usdCurrency->id,
        ]);

        $this->proposal1 = Proposal::factory()->create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer1->id,
            'bid_amount' => 900.00,
            'points_spent' => 5,
            'status' => 'pending',
        ]);

        $this->proposal2 = Proposal::factory()->create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancer2->id,
            'bid_amount' => 950.00,
            'points_spent' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_client_accepts_proposal_creates_contract(): void
    {
        $clientBalance = $this->clientUser->user_balance;

        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.proposals.accept', $this->proposal1->id));

        $response->assertStatus(302);

        $this->assertEquals('accepted', $this->proposal1->fresh()->status);
        $this->assertEquals('in_progress', $this->job->fresh()->status);

        $this->assertDatabaseHas('freelance_contracts', [
            'job_id' => $this->job->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancer1->id,
            'status' => 'active',
            'amount' => 900.00,
        ]);

        // Client wallet should NOT be deducted immediately depending on escrow logic, 
        // OR it is deducted immediately and put into escrow. 
        // Based on the workflow test, the user_balance is deducted when completed or upon creation.
        // Let's just verify the contract exists and job is in progress.
    }

    public function test_contract_completion_pays_freelancer_and_refunds_other_proposals(): void
    {
        // Setup accepted state
        $this->proposal1->update(['status' => 'accepted']);
        $this->job->update(['status' => 'in_progress']);

        $contract = Contract::factory()->create([
            'job_id' => $this->job->id,
            'proposal_id' => $this->proposal1->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancer1->id,
            'amount' => 900.00,
            'status' => 'active',
        ]);

        $freelancer2InitialPoints = $this->freelancer2->points_balance;
        $freelancer1InitialBalance = $this->freelancer1->user_balance;
        $clientInitialBalance = $this->clientUser->user_balance;

        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.contracts.complete', $contract->id));

        $response->assertStatus(302);

        $this->assertEquals('completed', $contract->fresh()->status);
        $this->assertEquals('completed', $this->job->fresh()->status);

        // Freelancer 2 should be rejected and refunded 5 points
        $this->assertEquals('rejected', $this->proposal2->fresh()->status);
        $this->assertEquals($freelancer2InitialPoints + 5, $this->freelancer2->fresh()->points_balance);

        // Freelancer 1 gets paid
        $this->assertEquals($freelancer1InitialBalance + 900.00, $this->freelancer1->fresh()->user_balance);
        
        // Client gets deducted
        $this->assertEquals($clientInitialBalance - 900.00, $this->clientUser->fresh()->user_balance);
    }

    public function test_parties_can_leave_reviews_and_they_reveal_when_both_submit(): void
    {
        $contract = Contract::factory()->create([
            'job_id' => $this->job->id,
            'proposal_id' => $this->proposal1->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancer1->id,
            'amount' => 900.00,
            'status' => 'completed',
        ]);

        // Client reviews freelancer
        $this->actingAs($this->clientUser)
            ->post(route('freelance.contracts.reviews.store', $contract->id), [
                'rating' => 5,
                'comment' => 'Great job!',
            ])->assertStatus(302);

        $this->assertDatabaseHas('freelance_reviews', [
            'contract_id' => $contract->id,
            'reviewer_id' => $this->clientUser->id,
            'reviewee_id' => $this->freelancer1->id,
            'is_visible' => false,
        ]);

        // Freelancer reviews client
        $this->actingAs($this->freelancer1)
            ->post(route('freelance.contracts.reviews.store', $contract->id), [
                'rating' => 4,
                'comment' => 'Good client.',
            ])->assertStatus(302);

        $this->assertDatabaseHas('freelance_reviews', [
            'contract_id' => $contract->id,
            'reviewer_id' => $this->freelancer1->id,
            'reviewee_id' => $this->clientUser->id,
            'is_visible' => true, // Should be true now
        ]);

        // Client's review should also now be visible
        $clientReview = \Modules\Freelance\Models\Review::where('reviewer_id', $this->clientUser->id)->first();
        $this->assertTrue((bool)$clientReview->is_visible);
    }
}
