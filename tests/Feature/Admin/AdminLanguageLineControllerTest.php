<?php

namespace Tests\Feature\Admin;

use App\Models\LanguageLine;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLanguageLineControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_language_lines_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.language-lines.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_language_lines_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.language-lines.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_language_line(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.language-lines.store'), [
            'group' => 'general',
            'key' => 'welcome_message',
            'text' => [
                'en' => 'Welcome',
                'ar' => 'مرحباً',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('language_lines', [
            'group' => 'general',
            'key' => 'welcome_message',
        ]);
    }

    public function test_admin_can_update_language_line(): void
    {
        $line = LanguageLine::create([
            'group' => 'general',
            'key' => 'hello',
            'text' => ['en' => 'Hello'],
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.language-lines.update', $line->id), [
            'group' => 'general',
            'key' => 'hello',
            'text' => [
                'en' => 'Hello Updated',
                'ar' => 'أهلا',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $line->refresh();
        $this->assertEquals('Hello Updated', $line->text['en']);
    }

    public function test_admin_can_destroy_language_line(): void
    {
        $line = LanguageLine::create([
            'group' => 'general',
            'key' => 'delete_me',
            'text' => ['en' => 'Delete me'],
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.language-lines.destroy', $line->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('language_lines', [
            'id' => $line->id,
        ]);
    }
}
