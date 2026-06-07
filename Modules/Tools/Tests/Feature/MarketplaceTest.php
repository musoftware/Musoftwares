<?php

namespace Modules\Tools\Tests\Feature;

use App\Models\User;
use Modules\Tools\Models\ToolSubscription;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MarketplaceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_explore_page_hides_extension_card_for_guests()
    {
        $response = $this->get('/tools');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Explore')
                     ->where('hasBrowserSubscription', false)
                 );
    }

    public function test_explore_page_hides_extension_card_for_unsubscribed_users()
    {
        $this->actingAs($this->user);

        $response = $this->get('/tools');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Explore')
                     ->where('hasBrowserSubscription', false)
                 );
    }

    public function test_explore_page_shows_extension_card_for_users_subscribed_to_browser_tool()
    {
        $this->actingAs($this->user);

        // Subscribe to a browser tool (e.g. facebook-extractor)
        ToolSubscription::create([
            'user_id'       => $this->user->id,
            'tool_guid'     => 'fbe12345-0000-0000-0000-000000000000', // facebook-extractor GUID from config/tools.php
            'plan_guid'     => 'fbe12345-0000-0000-0000-000000000001',
            'billing_cycle' => 'monthly',
            'amount_paid'   => 0,
            'currency_id'   => 1,
            'status'        => 'active',
            'starts_at'     => now(),
            'expires_at'    => now()->addMonth(),
        ]);

        $response = $this->get('/tools');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Explore')
                     ->where('hasBrowserSubscription', true)
                 );
    }

    public function test_explore_page_hides_extension_card_for_users_only_subscribed_to_desktop_tool()
    {
        $this->actingAs($this->user);

        // Subscribe to a desktop tool (e.g. tiktok-intelligence)
        ToolSubscription::create([
            'user_id'       => $this->user->id,
            'tool_guid'     => '832b99f8-26dc-405b-b741-36c3b982a32e', // tiktok-intelligence GUID
            'plan_guid'     => '9e8b5cfc-30e4-4bf3-944c-a11214d6b58f',
            'billing_cycle' => 'monthly',
            'amount_paid'   => 0,
            'currency_id'   => 1,
            'status'        => 'active',
            'starts_at'     => now(),
            'expires_at'    => now()->addMonth(),
        ]);

        $response = $this->get('/tools');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Explore')
                     ->where('hasBrowserSubscription', false)
                 );
    }

    public function test_cannot_access_tutorial_without_active_subscription()
    {
        $this->actingAs($this->user);

        $response = $this->get('/tools/facebook-extractor/tutorial');

        // Should redirect back to tools show page with error since there is no subscription
        $response->assertRedirect('/tools/facebook-extractor');
    }

    public function test_can_access_tutorial_with_active_subscription()
    {
        $this->actingAs($this->user);

        ToolSubscription::create([
            'user_id'       => $this->user->id,
            'tool_guid'     => 'fbe12345-0000-0000-0000-000000000000', // facebook-extractor GUID
            'plan_guid'     => 'fbe12345-0000-0000-0000-000000000001',
            'billing_cycle' => 'monthly',
            'amount_paid'   => 0,
            'currency_id'   => 1,
            'status'        => 'active',
            'starts_at'     => now(),
            'expires_at'    => now()->addMonth(),
        ]);

        $response = $this->get('/tools/facebook-extractor/tutorial');

        $response->assertStatus(200)
                 ->assertInertia(fn (AssertableInertia $page) => $page
                     ->component('Tools/Tutorial')
                     ->has('tool')
                     ->where('tool.slug', 'facebook-extractor')
                     ->where('tool.is_browser_tool', true)
                 );
    }

    public function test_subscribing_redirects_to_tutorial_page()
    {
        $this->actingAs($this->user);

        // Give user balance
        $this->user->user_balance = 500;
        $this->user->save();

        // We subscribe to facebook-extractor free plan fbe12345-0000-0000-0000-000000000001
        $response = $this->post('/tools/facebook-extractor/subscribe/fbe12345-0000-0000-0000-000000000001', [
            'billing_cycle' => 'monthly',
            'payment_method' => 'wallet',
        ]);

        $response->assertRedirect('/tools/facebook-extractor/tutorial');
    }
}
