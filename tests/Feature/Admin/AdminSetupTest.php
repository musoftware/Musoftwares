<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSetupTest extends TestCase
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

    /** @return array<string, array<string, mixed>> */
    private function itemsByKey(): array
    {
        $groups = $this->actingAs($this->admin)->get('/admin/setup')->assertOk()->viewData('page')['props']['groups'];

        return collect($groups)->flatMap(fn (array $group) => $group['items'])->keyBy('key')->all();
    }

    public function test_admin_sees_every_group_of_steps(): void
    {
        $this->actingAs($this->admin)->get('/admin/setup')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/Setup')
            ->has('groups', 3)
            ->where('texts.title', __('setup.title')));
    }

    public function test_automatic_steps_follow_the_real_settings(): void
    {
        $this->assertFalse($this->itemsByKey()['analytics']['done']);

        AdminSettings::SetValue('google_tag_manager_id', 'GTM-TEST');

        $this->assertTrue($this->itemsByKey()['analytics']['done']);
    }

    public function test_admin_can_tick_and_untick_a_manual_step(): void
    {
        $this->actingAs($this->admin)->post('/admin/setup/search_console_sitemap', ['done' => true])->assertRedirect();
        $this->assertTrue($this->itemsByKey()['search_console_sitemap']['done']);

        $this->actingAs($this->admin)->post('/admin/setup/search_console_sitemap', ['done' => false])->assertRedirect();
        $this->assertFalse($this->itemsByKey()['search_console_sitemap']['done']);
    }

    public function test_automatic_steps_cannot_be_ticked_by_hand(): void
    {
        $this->actingAs($this->admin)->post('/admin/setup/analytics', ['done' => true])->assertNotFound();
    }

    public function test_non_admin_cannot_open_the_page(): void
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');

        $this->actingAs($client)->get('/admin/setup')->assertForbidden();
    }
}
