<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Currency;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelanceContractControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected User $freelancerUser;
    protected Job $job;
    protected Proposal $proposal;
    protected Contract $contract;

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

        $this->contract = Contract::create([
            'job_id' => $this->job->id,
            'proposal_id' => $this->proposal->id,
            'client_id' => $this->clientUser->id,
            'freelancer_id' => $this->freelancerUser->id,
            'amount' => 100,
            'currency_id' => $currency->id,
            'contract_points' => 100,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function test_admin_can_view_contracts_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/freelance/contracts');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_contracts_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/freelance/contracts');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_contract_show(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/freelance/contracts/{$this->contract->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_contract_status(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/freelance/contracts/{$this->contract->id}/status", [
            'status' => 'completed'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('completed', $this->contract->fresh()->status);
    }

    public function test_admin_can_delete_contract(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/freelance/contracts/{$this->contract->id}");

        $response->assertRedirect(route('admin.freelance.contracts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('freelance_contracts', ['id' => $this->contract->id]);
    }

    public function test_admin_can_resolve_dispute_refund_client(): void
    {
        $this->contract->update(['status' => 'disputed']);

        $response = $this->actingAs($this->admin)->post("/admin/freelance/contracts/{$this->contract->id}/resolve-dispute", [
            'resolution' => 'refund_client'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('cancelled', $this->contract->fresh()->status);
    }
}
