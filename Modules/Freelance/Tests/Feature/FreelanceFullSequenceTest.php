<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Skill;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Support\Facades\Queue;
use Modules\Freelance\Domains\Job\Actions\PostJobAction;
use Modules\Freelance\Domains\Proposal\Actions\SubmitProposalAction;
use Modules\Freelance\Domains\Contract\Actions\AcceptProposalAction;
use Modules\Freelance\Domains\Job\DTOs\PostJobData;
use Modules\Freelance\Domains\Proposal\DTOs\SubmitProposalData;

beforeEach(function () {
    Queue::fake();

    $this->usdCurrency = Currency::firstOrCreate(
        ['currency' => 'USD'],
        ['symbol' => '$', 'string_format' => '$ %s', 'exchange_rate' => 1]
    );

    $this->skillPhp = Skill::create(['name' => 'PHP', 'status' => 'approved']);
    $this->skillLaravel = Skill::create(['name' => 'Laravel', 'status' => 'approved']);
    $this->skillPython = Skill::create(['name' => 'Python', 'status' => 'approved']);

    // Setup Client
    $this->client = User::factory()->create([
        'points_balance' => 200, 
        'currency_id' => $this->usdCurrency->id,
        'onboarding_completed' => true
    ]);

    // Setup Freelancer 1 (Has Skills, High Points)
    $this->freelancer1 = User::factory()->create([
        'points_balance' => 100,
        'currency_id' => $this->usdCurrency->id,
        'onboarding_completed' => true
    ]);
    $this->freelancer1->freelanceSkills()->attach([$this->skillPhp->id, $this->skillLaravel->id]);

    // Setup Freelancer 2 (Has Skills, Low Points)
    $this->freelancer2 = User::factory()->create([
        'points_balance' => 50,
        'currency_id' => $this->usdCurrency->id,
        'onboarding_completed' => true
    ]);
    $this->freelancer2->freelanceSkills()->attach([$this->skillPhp->id]);

    // Setup Freelancer 3 (No Matching Skills)
    $this->freelancer3 = User::factory()->create([
        'points_balance' => 100,
        'currency_id' => $this->usdCurrency->id,
        'onboarding_completed' => true
    ]);
    $this->freelancer3->freelanceSkills()->attach([$this->skillPython->id]);
});

it('executes a full end-to-end sequence between a client and multiple freelancers', function () {
    // 1. Client posts a job with a minimum bid
    $postJobData = new PostJobData(
        clientId: $this->client->id,
        title: 'Full Sequence Job',
        description: 'Testing the sequence',
        budgetPoints: 500,
        minProposalPoints: 20, // Client wants at least 20 points per proposal
        type: 'fixed',
        duration: '1 week',
        skills: [$this->skillPhp->id]
    );

    $job = app(PostJobAction::class)->execute($postJobData, $this->client);
    
    // Assert Client Points Deducted: 25 Base + 20 Min Bid = 45 points spent
    expect($this->client->fresh()->points_balance)->toBe(200 - 45);

    // 2. Freelancer 1 bids successfully
    $submitProposal1Data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $this->freelancer1->id,
        coverLetter: 'I am Freelancer 1',
        proposedBudgetPoints: 400,
        pointsSpent: 30 // Bidding 30 points (above min 20)
    );
    app(SubmitProposalAction::class)->execute($submitProposal1Data, $job, $this->freelancer1);
    expect($this->freelancer1->fresh()->points_balance)->toBe(100 - 30);

    // 3. Freelancer 2 bids successfully but spends exact minimum
    $submitProposal2Data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $this->freelancer2->id,
        coverLetter: 'I am Freelancer 2',
        proposedBudgetPoints: 450,
        pointsSpent: 20 // Bidding exactly 20 points
    );
    app(SubmitProposalAction::class)->execute($submitProposal2Data, $job, $this->freelancer2);
    expect($this->freelancer2->fresh()->points_balance)->toBe(50 - 20);

    // 4. Freelancer 3 tries to bid below minimum -> Should Fail
    $submitProposal3Data = new SubmitProposalData(
        jobId: $job->id,
        freelancerId: $this->freelancer3->id,
        coverLetter: 'I am Freelancer 3',
        proposedBudgetPoints: 300,
        pointsSpent: 10 // Below minimum 20
    );
    expect(fn () => app(SubmitProposalAction::class)->execute($submitProposal3Data, $job, $this->freelancer3))
        ->toThrow(\Exception::class);

    // 5. Proposal Ranking (API/Controller logic simulation)
    // Client wants to see proposals. They should be ranked by points_spent descending.
    $proposals = $job->proposals()->orderBy('points_spent', 'desc')->get();
    expect($proposals->first()->freelancer_id)->toBe($this->freelancer1->id); // F1 spent 30
    expect($proposals->last()->freelancer_id)->toBe($this->freelancer2->id); // F2 spent 20

    // 6. Job Awarding: Client hires Freelancer 1
    app(AcceptProposalAction::class)->execute($proposals->first(), $this->client);

    // 7. Rejection Refund Rule: Freelancer 2 should get their 20 points back
    expect($this->freelancer2->fresh()->points_balance)->toBe(50); // 50 - 20 + 20 = 50

    // Freelancer 1 does NOT get points back because they won
    expect($this->freelancer1->fresh()->points_balance)->toBe(70);
});

it('prevents freelancers from accessing or editing other freelancer proposals (Security)', function () {
    // Client posts Job
    $job = Job::factory()->create(['client_id' => $this->client->id, 'min_proposal_points' => 0]);
    
    // F1 Submits Proposal
    $proposal = Proposal::factory()->create([
        'job_id' => $job->id,
        'freelancer_id' => $this->freelancer1->id,
        'points_spent' => 5
    ]);

    // F2 tries to edit F1's Proposal via API (Assume we have an edit endpoint)
    $response = $this->actingAs($this->freelancer2)->putJson("/api/freelance/proposals/{$proposal->id}", [
        'cover_letter' => 'Hacked'
    ]);

    // Depending on if route exists, it should be 403 or 404
    if ($response->status() !== 404) {
        $response->assertStatus(403);
    }
});

it('tests inactivity refund rule (Unhappy Path)', function () {
    // Client posts Job with premium 
    $job = Job::factory()->create([
        'client_id' => $this->client->id, 
        'min_proposal_points' => 10,
        'created_at' => now()->subDays(8), // Over 7 days ago
        'status' => 'open'
    ]);

    // Initial points balance is 200 (since the factory gave 200, but they didn't really 'pay' for this factory job)
    // For test purposes, assume they paid 35 points (25 base + 10 min bid)
    $this->client->update(['points_balance' => 165]); // 200 - 35 = 165

    // Execute the refund action
    $refundCount = app(\Modules\Freelance\Domains\Job\Actions\RefundInactiveJobsAction::class)->execute();
    
    expect($refundCount)->toBe(1);
    
    // The client should get 35 points back -> 165 + 35 = 200
    expect($this->client->fresh()->points_balance)->toBe(200);
    expect($job->fresh()->status->getValue())->toBe('cancelled');
});
