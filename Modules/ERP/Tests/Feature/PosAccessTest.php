<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class PosAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
            ]
        ]);
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_user_cannot_access_pos_without_addon(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1
        ]);
        
        $response = $this->actingAs($user)
                         ->withSession(['tenant_id' => $tenant->id])
                         ->get(route('erp.pos.index'));
        
        $response->assertStatus(403);
    }

    public function test_user_can_access_pos_with_addon(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1
        ]);
        
        $user->subscriptions()->create([
            'object' => 'erp-pos',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);
        
        $response = $this->actingAs($user)
                         ->withSession(['tenant_id' => $tenant->id])
                         ->get(route('erp.pos.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ERP/Pos/Index'));
    }
}
