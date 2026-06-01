<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Currency;
use Modules\Freelance\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelanceJobControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

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
    }

    public function test_admin_can_view_jobs_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/freelance/jobs');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_jobs_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/freelance/jobs');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_job_show(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/freelance/jobs/{$this->job->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_view_job_create(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/freelance/jobs/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_job(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/freelance/jobs', [
            'title' => 'New Job',
            'description' => 'New Desc',
            'budget' => 200,
            'client_id' => $this->clientUser->id,
            'type' => 'fixed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('freelance_jobs', ['title' => 'New Job']);
    }

    public function test_admin_can_view_job_edit(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/freelance/jobs/{$this->job->id}/edit");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_job(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/freelance/jobs/{$this->job->id}", [
            'title' => 'Updated Job',
            'description' => 'Updated Desc',
            'budget' => 300,
            'type' => 'fixed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('freelance_jobs', ['title' => 'Updated Job']);
    }

    public function test_admin_can_update_job_status(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/freelance/jobs/{$this->job->id}/status", [
            'status' => 'completed'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('freelance_jobs', ['id' => $this->job->id, 'status' => 'completed']);
    }

    public function test_admin_can_delete_job(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/freelance/jobs/{$this->job->id}");

        $response->assertRedirect(route('admin.freelance.jobs.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('freelance_jobs', ['id' => $this->job->id]);
    }

    public function test_admin_can_force_refund(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/freelance/jobs/{$this->job->id}/force-refund");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('freelance_jobs', ['id' => $this->job->id, 'status' => 'cancelled']);
    }
}
