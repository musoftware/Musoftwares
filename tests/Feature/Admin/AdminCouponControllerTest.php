<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Coupon;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->currency = Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '%v $'
        ]);
    }

    public function test_admin_can_view_coupons_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_coupons_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.coupons.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_new_coupon(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'name' => 'Test Coupon',
            'code' => 'TEST10',
            'type' => 'percentage',
            'discount_percentage' => 10,
            'currency' => $this->currency->id,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'name' => 'Test Coupon',
            'code' => 'TEST10',
        ]);
    }

    public function test_store_coupon_requires_name(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'type' => 'percentage',
            'discount_percentage' => 10,
            'currency' => $this->currency->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_view_coupon_details(): void
    {
        $coupon = Coupon::create([
            'name' => 'Test',
            'code' => 'TEST' . rand(100,999),
            'type' => 'fixed',
            'discount_amount' => 5,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.show', $coupon->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_coupon(): void
    {
        $coupon = Coupon::create([
            'name' => 'Old Name',
            'code' => 'OLD_CODE_' . rand(100, 999),
            'type' => 'fixed',
            'discount_amount' => 5,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.coupons.update', $coupon->id), [
            'name' => 'New Name',
            'code' => 'NEW_CODE_' . rand(100, 999),
            'type' => 'fixed',
            'discount_amount' => 10,
            'currency' => $this->currency->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'name' => 'New Name',
            'discount_amount' => 10,
        ]);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $coupon = Coupon::create([
            'name' => 'To Delete',
            'code' => 'DELETE_CODE_' . rand(100, 999),
            'type' => 'fixed',
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.coupons.destroy', $coupon->id));

        $response->assertRedirect(route('admin.coupons.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
    }
}
