<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
            ]
        ]);
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_settings_update_without_multi_currency_updates_all_client_currencies(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1 // USD
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
        $this->assertEquals('Updated Workspace', $tenant->name);

        $client1->refresh();
        $client2->refresh();

        // Since user doesn't have 'erp-multi-currency' subscription, currencies must be updated to 3 (EUR)
        $this->assertEquals(3, $client1->currency_id);
        $this->assertEquals(3, $client2->currency_id);
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
            'client_id' => $user->id,
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
