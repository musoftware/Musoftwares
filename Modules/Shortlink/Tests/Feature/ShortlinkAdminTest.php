<?php

namespace Modules\Shortlink\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shortlink\Models\ShortlinkLink;
use Tests\TestCase;

class ShortlinkAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function client(): User
    {
        $client = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $client->assignRole('client');

        return $client;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.shortlinks.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs($this->client())
            ->get(route('admin.shortlinks.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.shortlinks.index'))
            ->assertOk();
    }

    public function test_super_admin_can_view_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.shortlinks.index'))
            ->assertOk();
    }

    public function test_admin_can_store_a_short_link(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.shortlinks.store'), [
                'destination_url' => 'https://example.com/some/long/path?with=query',
                'label' => 'Marketing campaign',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shortlink_links', [
            'destination_url' => 'https://example.com/some/long/path?with=query',
            'label' => 'Marketing campaign',
        ]);
    }

    public function test_store_validates_the_destination_url(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.shortlinks.store'), [
                'destination_url' => 'not-a-valid-url',
            ])
            ->assertSessionHasErrors('destination_url');
    }

    public function test_admin_can_toggle_a_link_status(): void
    {
        $link = ShortlinkLink::create([
            'short_code' => 'TOGGLE0001',
            'destination_url' => 'https://example.com',
            'is_active' => true,
            'clicks' => 0,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.shortlinks.toggle', $link))
            ->assertRedirect();

        $this->assertFalse((bool) ShortlinkLink::whereKey($link->id)->value('is_active'));
    }

    public function test_admin_can_soft_delete_a_link(): void
    {
        $link = ShortlinkLink::create([
            'short_code' => 'DELETE0001',
            'destination_url' => 'https://example.com',
            'is_active' => true,
            'clicks' => 0,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.shortlinks.destroy', $link))
            ->assertRedirect();

        $this->assertSoftDeleted('shortlink_links', ['id' => $link->id]);
    }

    public function test_non_admin_cannot_store_a_short_link(): void
    {
        $this->actingAs($this->client())
            ->post(route('admin.shortlinks.store'), [
                'destination_url' => 'https://example.com',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('shortlink_links', [
            'destination_url' => 'https://example.com',
        ]);
    }
}
