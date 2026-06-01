<?php

namespace Modules\ERP\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\DebtTransaction;
use App\Models\User;
use App\Models\UserSubscription;

class DebtControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Business',
            'base_currency_id' => 1,
            'domain' => 'test' . rand(1000, 9999),
        ]);
    }

    private function giveSubscription()
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp-debts',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    public function test_user_without_subscription_cannot_access_debts()
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)->get(route('erp.debts.index'));
        $response->assertStatus(403);
    }

    public function test_user_can_access_debts_index()
    {
        $this->giveSubscription();

        $client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);
        
        DebtTransaction::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'type' => 'given',
            'amount' => 100,
            'date' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('erp.debts.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_client_debt()
    {
        $this->giveSubscription();

        $client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Test Client',
            'email' => 'client3@test.com',
        ]);
        
        $response = $this->actingAs($this->user)->get(route('erp.debts.show', $client));
        $response->assertStatus(200);
    }
}
