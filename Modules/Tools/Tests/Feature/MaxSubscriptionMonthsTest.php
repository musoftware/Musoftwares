<?php

namespace Modules\Tools\Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaxSubscriptionMonthsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_checkout_page_passes_max_subscription_months_to_frontend()
    {
        $this->actingAs($this->user);

        // Use a tool that exists in config — facebook-extractor is always free and active
        $toolSlug = 'facebook-extractor';
        $planGuid = 'fbe12345-0000-0000-0000-000000000001';

        // Temporarily set a max_subscription_months on the tool via config
        config(["tools.fbe12345-0000-0000-0000-000000000000.max_subscription_months" => 1]);

        $response = $this->get("/tools/{$toolSlug}/subscribe/{$planGuid}");

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Subscribe')
                     ->where('maxSubscriptionMonths', 1)
                 );
    }

    public function test_checkout_page_passes_null_when_no_restriction()
    {
        $this->actingAs($this->user);

        $toolSlug = 'facebook-extractor';
        $planGuid = 'fbe12345-0000-0000-0000-000000000001';

        // Ensure no max_subscription_months is set
        $tools = config('tools');
        unset($tools['fbe12345-0000-0000-0000-000000000000']['max_subscription_months']);
        config(['tools' => $tools]);

        $response = $this->get("/tools/{$toolSlug}/subscribe/{$planGuid}");

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Subscribe')
                     ->where('maxSubscriptionMonths', null)
                 );
    }

    public function test_subscribing_yearly_is_blocked_when_max_subscription_months_is_one()
    {
        $this->actingAs($this->user);

        $toolSlug = 'facebook-extractor';
        $planGuid = 'fbe12345-0000-0000-0000-000000000001';

        // Set the restriction
        config(["tools.fbe12345-0000-0000-0000-000000000000.max_subscription_months" => 1]);

        $response = $this->post("/tools/{$toolSlug}/subscribe/{$planGuid}", [
            'billing_cycle' => 'yearly',
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

        $response = $this->post("/tools/{$toolSlug}/subscribe/{$planGuid}", [
            'billing_cycle' => 'monthly',
        ]);

        // For a free tool, it should succeed and redirect to tutorial
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

        $response = $this->post("/tools/{$toolSlug}/subscribe/{$planGuid}", [
            'billing_cycle' => 'yearly',
        ]);

        // For a free tool with no restriction, should redirect to tutorial
        $response->assertRedirect("/tools/{$toolSlug}/tutorial");
    }
}
