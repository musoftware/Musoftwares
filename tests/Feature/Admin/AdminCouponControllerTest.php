<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_admin_can_access_coupons_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_coupons_index()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);

        $response = $this->actingAs($user)->get(route('admin.coupons.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_store_coupon()
    {
        $admin = $this->createAdmin();

        $payload = [
            'name' => 'Summer Sale',
            'code' => 'SUMMER2026',
            'type' => 'percentage',
            'discount_percentage' => 20,
            'currency' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->post(route('admin.coupons.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('coupons', [
            'code' => 'SUMMER2026',
            'type' => 'percentage',
        ]);
    }

    public function test_admin_can_access_coupon_show_page()
    {
        $admin = $this->createAdmin();
        $coupon = Coupon::create([
            'name' => 'Test Coupon',
            'code' => 'TESTCODE',
            'type' => 'fixed',
            'discount_amount' => 50,
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.coupons.show', $coupon->id));

        $response->assertStatus(200);
    }

    public function test_admin_can_update_coupon()
    {
        $admin = $this->createAdmin();
        $coupon = Coupon::create([
            'name' => 'Old Coupon',
            'code' => 'OLDCODE',
            'type' => 'fixed',
            'discount_amount' => 10,
            'currency_id' => 1,
        ]);

        $payload = [
            'name' => 'Updated Coupon',
            'code' => 'OLDCODE',
            'type' => 'fixed',
            'discount_amount' => 20,
            'currency' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->put(route('admin.coupons.update', $coupon->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'name' => 'Updated Coupon',
            'discount_amount' => 20,
        ]);
    }

    public function test_admin_can_delete_coupon()
    {
        $admin = $this->createAdmin();
        $coupon = Coupon::create([
            'name' => 'To Delete',
            'code' => 'DELETE',
            'type' => 'fixed',
            'discount_amount' => 10,
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon->id));

        $response->assertRedirect(route('admin.coupons.index'));

        $this->assertSoftDeleted('coupons', [
            'id' => $coupon->id,
        ]);
    }
}
