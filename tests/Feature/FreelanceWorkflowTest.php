<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Freelance\Models\Skill;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Models\PointTransaction;
use Modules\Core\Models\Wallet;
use Tests\TestCase;

class FreelanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $clientUser;
    protected User $freelancerUser;
    protected Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->freelancerUser = User::factory()->create(['onboarding_completed' => true]);
        $this->freelancerUser->assignRole('client');

        $this->skill = Skill::create([
            'name' => 'Laravel',
            'description' => 'PHP framework development',
        ]);
    }

    public function test_freelance_lifecycle_workflow(): void
    {
        // 1. Give client and freelancer initial points and wallets
        PointTransaction::create([
            'user_id' => $this->clientUser->id,
            'points' => 100,
            'type' => 'earned',
            'description' => 'Initial signup points',
        ]);

        PointTransaction::create([
            'user_id' => $this->freelancerUser->id,
            'points' => 50,
            'type' => 'earned',
            'description' => 'Initial signup points',
        ]);

        $clientWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $this->clientUser->id,
            'context' => 'user',
            'balance' => 2000.00,
            'currency' => 'USD',
        ]);

        $freelancerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $this->freelancerUser->id,
            'context' => 'user',
            'balance' => 0.00,
            'currency' => 'USD',
        ]);

        // 2. Client posts a Job (costs 10 points)
        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.jobs.store'), [
                'title' => 'Build a Laravel SaaS platform',
                'description' => 'Need a stunning modular application with Stripe integration.',
                'budget' => 1200.00,
                'currency_code' => 'USD',
                'type' => 'fixed',
                'duration' => '1 month',
                'skills' => [$this->skill->id],
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_jobs', [
            'client_id' => $this->clientUser->id,
            'title' => 'Build a Laravel SaaS platform',
            'status' => 'open',
        ]);

        $job = Job::where('title', 'Build a Laravel SaaS platform')->first();

        // Verify client's point balance is decremented by 10
        $this->assertEquals(90, $this->clientUser->fresh()->points_balance);

        // 3. Freelancer submits a proposal (costs 2 points)
        $response = $this->actingAs($this->freelancerUser)
            ->post(route('freelance.proposals.store', $job->id), [
                'cover_letter' => 'I am an expert Laravel engineer. Here is my portfolio.',
                'bid_amount' => 1150.00,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('freelance_proposals', [
            'job_id' => $job->id,
            'freelancer_id' => $this->freelancerUser->id,
            'bid_amount' => 1150.00,
            'status' => 'pending',
        ]);

        $proposal = Proposal::where('job_id', $job->id)->first();

        // Verify freelancer's point balance is decremented by 2
        $this->assertEquals(48, $this->freelancerUser->fresh()->points_balance);

        // 4. Client accepts the proposal (creates contract)
        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.proposals.accept', $proposal->id));

        $response->assertStatus(302);
        $this->assertEquals('accepted', $proposal->fresh()->status);
        $this->assertEquals('in_progress', $job->fresh()->status);

        $this->assertDatabaseHas('freelance_contracts', [
            'job_id' => $job->id,
            'proposal_id' => $proposal->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancerUser->id,
            'amount' => 1150.00,
            'status' => 'active',
        ]);

        $contract = Contract::where('job_id', $job->id)->first();

        // 5. Client completes the contract (triggers payout fund transfer)
        $response = $this->actingAs($this->clientUser)
            ->post(route('freelance.contracts.complete', $contract->id));

        $response->assertStatus(302);
        $this->assertEquals('completed', $contract->fresh()->status);
        $this->assertEquals('completed', $job->fresh()->status);

        // Verify wallet funds have been successfully debited and credited
        $this->assertEquals(850.00, $clientWallet->fresh()->balance);
        $this->assertEquals(1150.00, $freelancerWallet->fresh()->balance);
    }
}
