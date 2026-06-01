<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminPointsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
            'points_balance' => 100,
        ]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_points_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.points.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_points_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.points.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_add_points(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.points.adjust', $this->clientUser->id), [
            'amount' => 50,
            'reason' => 'Bonus points',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(150, $this->clientUser->fresh()->points_balance);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'earned',
            'points' => 50,
        ]);
    }

    public function test_admin_can_deduct_points(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.points.adjust', $this->clientUser->id), [
            'amount' => -30,
            'reason' => 'Penalty',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(70, $this->clientUser->fresh()->points_balance);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'used',
            'points' => -30,
        ]);
    }

    public function test_admin_can_view_points_history(): void
    {
        PointTransaction::create([
            'user_id' => $this->clientUser->id,
            'type' => 'earned',
            'points' => 10,
            'description' => 'Test Transaction'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.points.history', $this->clientUser->id));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'coins_reward' => 10,
        ]);
    }
}
