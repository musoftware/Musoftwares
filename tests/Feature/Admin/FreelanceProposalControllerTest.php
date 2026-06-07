<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Currency;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelanceProposalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected User $freelancerUser;
    protected Job $job;
    protected Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->freelancerUser = User::factory()->create(['onboarding_completed' => true]);

        $currency = Currency::first();
        if (!$currency) {
            $currency = Currency::create([
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1,
            ]);
        }

        $this->job = Job::create([
            'client_id' => $this->clientUser->id,
            'title' => 'Test Job',
            'description' => 'Test Desc',
            'budget' => 100,
            'currency_id' => $currency->id,
            'status' => 'open',
            'type' => 'fixed',
        ]);

        $this->proposal = Proposal::create([
            'job_id' => $this->job->id,
            'freelancer_id' => $this->freelancerUser->id,
            'cover_letter' => 'Test letter',
            'bid_amount' => 100,
            'currency_id' => $currency->id,
            'duration' => '1 week',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_view_proposals_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/freelance/proposals');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_proposals_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/freelance/proposals');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_proposal_show(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/freelance/proposals/{$this->proposal->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_delete_proposal(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/freelance/proposals/{$this->proposal->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('freelance_proposals', ['id' => $this->proposal->id]);
    }
}
