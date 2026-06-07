<?php

namespace Modules\Tools\Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MaxSubscriptionMonthsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['currency_id' => 1]);
    }

    // The checkout page redirects to the central subscriptions.plans route, so we don't assert Inertia directly here anymore.

    public function test_subscribing_yearly_is_blocked_when_max_subscription_months_is_one()
    {
        $this->actingAs($this->user);

        $toolSlug = 'facebook-extractor';
        $planGuid = 'fbe12345-0000-0000-0000-000000000001';

        // Set the restriction
        config(["tools.fbe12345-0000-0000-0000-000000000000.max_subscription_months" => 1]);

        $response = $this->post("/tools/{$toolSlug}/subscribe/{$planGuid}", [
            'billing_cycle' => 'yearly',
            'payment_method' => 'wallet',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_subscribing_monthly_is_allowed_when_max_subscription_months_is_one()
    {
        $this->actingAs($this->user);

        $toolSlug = 'facebook-extractor';
        $planGuid = 'fbe12345-0000-0000-0000-000000000001';

        // Set the restriction
        config(["tools.fbe12345-0000-0000-0000-000000000000.max_subscription_months" => 1]);

        $this->user->add_balance(1000, 'Test balance', 'received');
        $response = $this->post("/tools/{$toolSlug}/subscribe/{$planGuid}", [
            'billing_cycle' => 'monthly',
            'payment_method' => 'wallet',
        ]);

        $response->assertRedirect("/tools/{$toolSlug}/tutorial");
    }

    public function test_subscribing_yearly_is_allowed_when_no_restriction()
    {
        $this->actingAs($this->user);

        $toolSlug = 'facebook-extractor';
        $planGuid = 'fbe12345-0000-0000-0000-000000000001';

        // Ensure no restriction
        $tools = config('tools');
        unset($tools['fbe12345-0000-0000-0000-000000000000']['max_subscription_months']);
        config(['tools' => $tools]);

        $this->user->add_balance(2000, 'Test balance', 'received');
        $response = $this->post("/tools/{$toolSlug}/subscribe/{$planGuid}", [
            'billing_cycle' => 'yearly',
            'payment_method' => 'wallet',
        ]);

        $response->assertRedirect("/tools/{$toolSlug}/tutorial");
    }
}
