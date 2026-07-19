<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PricingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_users_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_user_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_new_user(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => 'client',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_store_user_requires_last_name(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'John', // No space, so no last name
            'email' => 'john.doe@example.com',
            'role' => 'client',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_view_edit_user_page(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/edit");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_user(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->clientUser->id}", [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'role' => 'client',
            'account_status' => 'active',
        ]);

        $response->assertRedirect(route('admin.users.show', $this->clientUser->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->clientUser->id,
            'email' => 'jane.doe@example.com',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $userToDelete = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$userToDelete->id}");

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Check if soft deleted or permanently deleted
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}");

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_can_toggle_block_user(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/toggle-block", [
            'reason' => 'Spamming',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('blocked', $this->clientUser->fresh()->account_status);
        $this->assertEquals('Spamming', $this->clientUser->fresh()->block_reason);
    }

    public function test_admin_can_login_as_user(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/login-as");

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($this->clientUser->id, auth()->id());
        $this->assertEquals($this->admin->id, session('impersonator_id'));
    }

    public function test_admin_can_view_create_subscription_page(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/subscriptions/create");
        $response->assertStatus(200);
    }

    public function test_admin_can_activate_membership(): void
    {
        $this->mock(PricingService::class, function ($mock) {
            $mock->shouldReceive('getServiceItems')->andReturn([
                ['id' => 'erp', 'name' => 'ERP'],
            ]);
        });

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->clientUser->id}/membership", [
            'object' => 'erp',
            'duration_days' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->clientUser->id,
            'object' => 'erp',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_membership(): void
    {
        $subscription = UserSubscription::create([
            'user_id' => $this->clientUser->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->clientUser->id}/membership/{$subscription->id}", [
            'status' => 'expired',
            'expires_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscription->id,
            'status' => 'expired',
        ]);
    }

    public function test_admin_can_delete_membership(): void
    {
        $subscription = UserSubscription::create([
            'user_id' => $this->clientUser->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->clientUser->id}/membership/{$subscription->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('user_subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    public function test_admin_can_search_user_by_alias_email(): void
    {
        // Create an alias email for our clientUser
        $alias = \App\Models\UserEmail::create([
            'user_id' => $this->clientUser->id,
            'email' => 'alias-search-target@example.com',
            'verified_at' => now(),
            'source' => \App\Models\UserEmail::SOURCE_ADMIN,
        ]);

        // Create another client user who doesn't match
        $otherUser = User::factory()->create(['onboarding_completed' => true]);
        $otherUser->assignRole('client');

        // Request with search query matching the alias email
        $response = $this->actingAs($this->admin)->get('/admin/users?search=alias-search-target');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.email', $this->clientUser->email)
        );
    }
}
