<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVoucherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->currency = Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '%v $',
        ]);
    }

    public function test_admin_can_view_vouchers_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.vouchers.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_vouchers_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.vouchers.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_new_voucher(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.vouchers.store'), [
            'name' => 'Test Voucher',
            'spend_amount' => 100,
            'spend_currency' => $this->currency->id,
            'reward_amount' => 10,
            'reward_currency' => $this->currency->id,
            'type' => 'fixed',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vouchers', [
            'name' => 'Test Voucher',
        ]);
    }

    public function test_store_voucher_requires_name(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.vouchers.store'), [
            'spend_amount' => 100,
            'spend_currency' => $this->currency->id,
            'reward_amount' => 10,
            'reward_currency' => $this->currency->id,
            'type' => 'fixed',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_view_voucher_details(): void
    {
        $voucher = Voucher::create([
            'name' => 'Test',
            'spend_amount' => 100,
            'spend_currency_id' => $this->currency->id,
            'reward_amount' => 10,
            'reward_currency_id' => $this->currency->id,
            'type' => 'fixed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.vouchers.show', $voucher->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_voucher(): void
    {
        $voucher = Voucher::create([
            'name' => 'Old Name',
            'spend_amount' => 100,
            'spend_currency_id' => $this->currency->id,
            'reward_amount' => 10,
            'reward_currency_id' => $this->currency->id,
            'type' => 'fixed',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.vouchers.update', $voucher->id), [
            'name' => 'New Name',
            'spend_amount' => 100,
            'spend_currency' => $this->currency->id,
            'reward_amount' => 10,
            'reward_currency' => $this->currency->id,
            'type' => 'fixed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_can_delete_voucher(): void
    {
        $voucher = Voucher::create([
            'name' => 'To Delete',
            'spend_amount' => 100,
            'spend_currency_id' => $this->currency->id,
            'reward_amount' => 10,
            'reward_currency_id' => $this->currency->id,
            'type' => 'fixed',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.vouchers.destroy', $voucher->id));

        $response->assertRedirect(route('admin.vouchers.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('vouchers', ['id' => $voucher->id]);
    }
}
