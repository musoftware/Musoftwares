<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\TenantUsage;
use App\Services\MeteredBillingService;
use App\Exceptions\SaaSLimitExceededException;
use App\Events\SaaSLimitApproaching;
use App\Events\SaaSLimitReached;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class SaaSMeteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_unlimited_usage()
    {
        $user = User::forceCreate([
            'name' => 'Admin', 
            'email' => 'admin@test.com', 
            'password' => 'test',
        ]);
        $this->actingAs($user);
        app()->instance('currentTenant', (object)['id' => 1]);

        TenantUsage::create([
            'tenant_id' => 1,
            'usage_key' => 'unlimited_key',
            'used_amount' => 5000,
            'limit_amount' => null, // Unlimited
        ]);

        $service = new MeteredBillingService();
        $this->assertTrue($service->canUse('unlimited_key'));
        
        $service->incrementUsage('unlimited_key', 500);
        
        $usage = TenantUsage::where('usage_key', 'unlimited_key')->first();
        $this->assertEquals(5500, $usage->used_amount);
    }

    public function test_it_enforces_limits_and_throws_exception()
    {
        $user = User::forceCreate([
            'name' => 'Admin', 
            'email' => 'admin@test.com', 
            'password' => 'test',
        ]);
        $this->actingAs($user);
        app()->instance('currentTenant', (object)['id' => 1]);

        TenantUsage::create([
            'tenant_id' => 1,
            'usage_key' => 'limited_key',
            'used_amount' => 99,
            'limit_amount' => 100,
        ]);

        $service = new MeteredBillingService();
        $this->assertTrue($service->canUse('limited_key', 1));
        $this->assertFalse($service->canUse('limited_key', 2));

        $service->incrementUsage('limited_key', 1);

        $this->expectException(SaaSLimitExceededException::class);
        $service->incrementUsage('limited_key', 1); // This should throw
    }

    public function test_it_dispatches_events_at_thresholds()
    {
        Event::fake();

        $user = User::forceCreate([
            'name' => 'Admin', 
            'email' => 'admin@test.com', 
            'password' => 'test',
        ]);
        $this->actingAs($user);
        app()->instance('currentTenant', (object)['id' => 1]);

        TenantUsage::create([
            'tenant_id' => 1,
            'usage_key' => 'alert_key',
            'used_amount' => 75,
            'limit_amount' => 100,
        ]);

        $service = new MeteredBillingService();
        
        // Push it to 85%
        $service->incrementUsage('alert_key', 10);
        Event::assertDispatched(SaaSLimitApproaching::class);

        // Push it to 100%
        $service->incrementUsage('alert_key', 15);
        Event::assertDispatched(SaaSLimitReached::class);
    }
}
