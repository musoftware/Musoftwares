<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_keeps_their_own_favorites_in_their_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['onboarding_completed' => true, 'workspace_settings' => ['hide_values' => true]]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post('/admin/sidebar-favorites', ['urls' => ['/admin/builder', '/admin/users']])->assertSessionHasNoErrors();

        // the order is kept, and the other settings stay
        $this->assertSame(['admin_favorites' => ['/admin/builder', '/admin/users'], 'hide_values' => true], $admin->fresh()->workspace_settings);
        $this->actingAs($admin)->post('/admin/sidebar-favorites', ['urls' => ['https://evil.com']])->assertSessionHasErrors('urls.0');
        $this->actingAs($admin)->post('/admin/sidebar-favorites', ['urls' => []])->assertSessionHasNoErrors();
        $this->assertSame([], $admin->fresh()->workspace_settings['admin_favorites']);
    }

    public function test_a_customer_cannot_save_admin_favorites(): void
    {
        $customer = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($customer)->post('/admin/sidebar-favorites', ['urls' => ['/admin/users']]);

        $this->assertArrayNotHasKey('admin_favorites', $customer->fresh()->workspace_settings ?? []);
    }
}
