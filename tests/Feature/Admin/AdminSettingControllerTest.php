<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminSettingControllerTest extends TestCase
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

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
        
        // Ensure standard currencies are seeded or exist
        if (Currency::count() === 0) {
            Currency::create([
                'id' => 1,
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'is_default' => 1,
                'exchange_rate' => 1,
            ]);
        }
    }

    public function test_admin_can_view_settings_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_settings_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.store'), [
            'business_name' => 'Test Business',
            'business_email' => 'test@example.com',
            'ownwallet' => true,
            'payoneer_active' => false,
            'paymob_active' => true,
            'friday_work_allowed' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Test Business', AdminSettings::GetValue('business_name'));
        $this->assertEquals('test@example.com', AdminSettings::GetValue('business_email'));
        $this->assertEquals('1', AdminSettings::GetValue('ownwallet'));
    }

    public function test_admin_can_do_update_prices(): void
    {
        $currency = Currency::first();

        $response = $this->actingAs($this->admin)->post(route('admin.settings.do-update-prices'), [
            'hour_rate' => 50,
            'currency' => $currency->id,
            'update_projects' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(50, $this->clientUser->fresh()->hour_rate);
        $this->assertEquals($currency->id, $this->clientUser->fresh()->hour_rate_currency_id);
    }

    public function test_admin_can_recalculate_overhead_hourly_rate(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.recalculate-overhead-hourly-rate'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
