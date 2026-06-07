<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        // Removed migrate:fresh
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_settings_update_without_multi_currency_throws_exception(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1 // USD
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('errors.multi_currency_addon_required'));

        $this->withoutExceptionHandling();
        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put('/erp/settings', [
                'workspaceName' => 'Updated Workspace',
                'taxRate' => '14.00',
                'defaultCurrency' => 'EUR', // EUR (ID 3)
            ]);
    }

    public function test_settings_update_with_multi_currency_does_not_update_client_currencies(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1 // USD
        ]);

        // Create the active subscription for erp-multi-currency
        \App\Models\UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-multi-currency',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        
        $client1 = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Client One',
            'email' => 'one@test.com',
            'currency_id' => 1 // USD
        ]);

        $client2 = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Client Two',
            'email' => 'two@test.com',
            'currency_id' => 1 // USD
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put('/erp/settings', [
                'workspaceName' => 'Updated Workspace',
                'taxRate' => '14.00',
                'defaultCurrency' => 'EUR', // EUR (ID 3)
            ]);

        $response->assertRedirect();
        
        $tenant->refresh();
        $this->assertEquals(3, $tenant->base_currency_id);

        $client1->refresh();
        $client2->refresh();

        // Since user HAS 'erp-multi-currency' subscription, client currencies must NOT be updated
        $this->assertEquals(1, $client1->currency_id);
        $this->assertEquals(1, $client2->currency_id);
    }
}
