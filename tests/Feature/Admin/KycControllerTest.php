<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KycControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true, 'kyc_verified' => false]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_kyc_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('kyc.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_kyc_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('kyc.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_approve_kyc(): void
    {
        // Add a pending document for the user
        $this->clientUser->kycDocuments()->create([
            'document_type' => 'passport',
            'file_path' => 'fake_path.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('kyc.approve', $this->clientUser->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($this->clientUser->fresh()->kyc_verified);
    }

    public function test_admin_can_reject_kyc(): void
    {
        // Add a pending document for the user
        $this->clientUser->kycDocuments()->create([
            'document_type' => 'passport',
            'file_path' => 'fake_path.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('kyc.reject', $this->clientUser->id), [
            'reason' => 'Blurry image',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($this->clientUser->fresh()->kyc_verified);
        $this->assertEquals('Blurry image', $this->clientUser->fresh()->kyc_notes);
    }

    public function test_admin_reject_kyc_validation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('kyc.reject', $this->clientUser->id), [
            'reason' => '', // Reason required
        ]);

        $response->assertSessionHasErrors('reason');
    }
}
