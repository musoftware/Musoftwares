<?php

namespace Tests\Feature\Admin;

use App\Models\BlockedIp;
use App\Models\RateLimit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The owner unblocks an IP the site blocked (SecurityEnforcement), and the same IP can be blocked again later.
 * The unblock route took {id} while the controller asked for $blockedIp, so nothing was removed.
 */
class BlockedIpUnblockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_unblocking_lets_the_ip_in_again(): void
    {
        $this->get('/.env', ['REMOTE_ADDR' => '203.0.113.9'])->assertForbidden();
        $block = BlockedIp::where('ip_address', '203.0.113.9')->sole();
        $this->get('/', ['REMOTE_ADDR' => '203.0.113.9'])->assertForbidden();

        $this->actingAs($this->admin)->delete("/admin/settings/security/unblock-ip/{$block->id}")->assertRedirect();

        $this->assertSoftDeleted($block);
        $this->app['auth']->forgetGuards();
        $this->get('/', ['REMOTE_ADDR' => '203.0.113.9'])->assertOk();
    }

    public function test_an_unblocked_ip_can_be_blocked_again(): void
    {
        $this->get('/.env', ['REMOTE_ADDR' => '203.0.113.10'])->assertForbidden();
        $block = BlockedIp::where('ip_address', '203.0.113.10')->sole();
        $this->actingAs($this->admin)->delete("/admin/settings/security/unblock-ip/{$block->id}")->assertRedirect();
        $this->app['auth']->forgetGuards();

        $this->get('/wp-login.php', ['REMOTE_ADDR' => '203.0.113.10'])->assertForbidden();

        $this->assertSame(1, BlockedIp::withTrashed()->where('ip_address', '203.0.113.10')->count());
        $this->assertFalse(BlockedIp::withTrashed()->where('ip_address', '203.0.113.10')->sole()->trashed());
        $this->get('/', ['REMOTE_ADDR' => '203.0.113.10'])->assertForbidden();
    }

    public function test_deleting_a_rate_limit_removes_it(): void
    {
        $limit = RateLimit::create(['module' => 'api', 'max_requests' => 60, 'decay_minutes' => 1, 'is_active' => true]);

        $this->actingAs($this->admin)->delete("/admin/settings/security/rate-limits/{$limit->id}")->assertRedirect();

        $this->assertSoftDeleted($limit);
    }
}
