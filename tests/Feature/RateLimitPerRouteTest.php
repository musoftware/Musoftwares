<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimitPerRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_limit/poll', fn () => 'ok')->middleware('throttle:30,1')->name('test.poll');
        Route::get('/_limit/send', fn () => 'ok')->middleware('throttle:3,1')->name('test.send');
    }

    public function test_each_route_counts_its_own_requests(): void
    {
        foreach (range(1, 10) as $poll) {
            $this->get('/_limit/poll')->assertOk();
        }

        // ten polls do not use up the three sends
        foreach (range(1, 3) as $send) {
            $this->get('/_limit/send')->assertOk();
        }
        $this->get('/_limit/send')->assertStatus(429);
        $this->get('/_limit/poll')->assertOk();
    }

    public function test_an_address_that_keeps_hitting_limits_is_blocked_for_a_day_not_forever(): void
    {
        foreach (range(1, 3) as $send) {
            $this->getJson('/_limit/send');
        }
        foreach (range(1, 6) as $refused) {
            $this->getJson('/_limit/send')->assertStatus(429);
        }

        $block = BlockedIp::where('ip_address', '127.0.0.1')->sole();
        $this->assertNotNull($block->blocked_until);
        $this->assertTrue($block->blocked_until->between(now()->addHours(23), now()->addHours(25)));
    }
}
