<?php

namespace Tests\Feature\Admin;

use App\Models\Prompt;
use App\Models\User;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PromptGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'promptadmin@musoftwares.com',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create([
            'email' => 'regularclient@musoftwares.com',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $this->regularUser->assignRole('client');
    }

    public function test_guest_cannot_access_prompt_gallery(): void
    {
        $response = $this->get(route('admin.prompts.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_forbidden_from_prompt_gallery(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.prompts.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_prompt_gallery(): void
    {
        Prompt::factory()->create([
            'title' => 'Test UI Prompt',
            'category' => 'UI',
            'prompt' => 'Clean and accessible prompt instructions',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.prompts.index'));
        $response->assertStatus(200);
        $response->assertSee('Test UI Prompt');
    }

    public function test_admin_can_create_prompt(): void
    {
        $payload = [
            'title' => 'Security Hardening Protocol',
            'category' => 'Security',
            'description' => 'Mandatory OWASP audit check',
            'prompt' => 'Inspect all route definitions for explicit admin auth and policy guards.',
            'is_featured' => true,
            'tags' => ['security', 'audit'],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.prompts.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prompts', [
            'title' => 'Security Hardening Protocol',
            'category' => 'Security',
            'is_featured' => 1,
        ]);
    }

    public function test_admin_can_update_prompt(): void
    {
        $prompt = Prompt::create([
            'title' => 'Initial Title',
            'category' => 'Backend',
            'prompt' => 'Initial prompt body with sufficient length.',
            'user_id' => $this->adminUser->id,
        ]);

        $payload = [
            'title' => 'Updated Backend Pipeline',
            'category' => 'Backend',
            'prompt' => 'Updated prompt body content.',
            'description' => 'Updated description.',
            'is_featured' => false,
        ];

        $response = $this->actingAs($this->adminUser)->put(route('admin.prompts.update', $prompt->id), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prompts', [
            'id' => $prompt->id,
            'title' => 'Updated Backend Pipeline',
        ]);
    }

    public function test_admin_can_increment_copy_count(): void
    {
        $prompt = Prompt::create([
            'title' => 'Copyable Prompt',
            'category' => 'DevOps',
            'prompt' => 'Docker multi-stage build script.',
            'copy_count' => 5,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.prompts.copy', $prompt->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'copy_count' => 6,
        ]);

        $this->assertEquals(6, $prompt->fresh()->copy_count);
    }

    public function test_admin_can_soft_delete_prompt(): void
    {
        $prompt = Prompt::create([
            'title' => 'Prompt To Delete',
            'category' => 'Architecture',
            'prompt' => 'Domain decoupling strategy.',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.prompts.destroy', $prompt->id));

        $response->assertSessionHasNoErrors();
        $this->assertSoftDeleted('prompts', ['id' => $prompt->id]);
    }

    public function test_prompt_gallery_filters_by_search_term(): void
    {
        Prompt::create([
            'title' => 'Specific Unique Alpha Title',
            'category' => 'UI',
            'prompt' => 'Body text for alpha.',
            'user_id' => $this->adminUser->id,
        ]);

        Prompt::create([
            'title' => 'Beta Title',
            'category' => 'UI',
            'prompt' => 'Body text for beta.',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.prompts.index', ['search' => 'Unique Alpha']));
        $response->assertStatus(200);
        $response->assertSee('Specific Unique Alpha Title');
        $response->assertDontSee('Beta Title');
    }
}
