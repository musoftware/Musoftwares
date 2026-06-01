<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Currency;
use App\Models\AdminSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingControllerTest extends TestCase
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

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_settings_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_settings_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.settings.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_update_settings()
    {
        $admin = $this->createAdmin();

        $payload = [
            'business_name' => 'Test Business',
            'business_email' => 'test@business.com',
            'ownwallet' => true,
        ];

        $response = $this->actingAs($admin)->post(route('admin.settings.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Test Business', AdminSettings::GetValue('business_name'));
        $this->assertEquals('test@business.com', AdminSettings::GetValue('business_email'));
        $this->assertEquals('1', AdminSettings::GetValue('ownwallet'));
    }


    public function test_admin_can_do_update_prices()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $currency = Currency::first() ?? Currency::factory()->create();

        $payload = [
            'hour_rate' => 50,
            'currency' => $currency->id,
            'update_projects' => false,
        ];

        $response = $this->actingAs($admin)->post(route('admin.settings.do-update-prices'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'hour_rate' => 50,
            'hour_rate_currency_id' => $currency->id,
        ]);
    }

    public function test_admin_can_recalculate_overhead_hourly_rate()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.settings.recalculate-overhead-hourly-rate'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
