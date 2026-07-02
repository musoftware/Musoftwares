<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicProjectBoardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->project = Project::create([
            'user_id' => $this->admin->id,
            'project_name' => 'Secret Launch Board',
            'share_token' => 'test-token-1234567890-abcdef',
        ]);
    }

    public function test_admin_board_view_passes_share_token_and_url(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.projects.board', ['project' => $this->project->id, 'date' => '2026-07-01']));

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->has('project.share_token')
            ->has('project.share_url')
            ->where('project.share_token', $this->project->share_token)
        );
    }

    public function test_guest_can_access_board_with_valid_signature(): void
    {
        $signedUrl = URL::signedRoute('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => '2026-07-01',
        ]);

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('project.name', 'Secret Launch Board')
            ->where('date', '2026-07-01')
        );
    }

    public function test_guest_cannot_access_board_without_signature(): void
    {
        $unsignedUrl = route('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => '2026-07-01',
        ]);

        $response = $this->get($unsignedUrl);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_board_if_date_tempered(): void
    {
        $signedUrl = URL::signedRoute('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => '2026-07-01',
        ]);

        // Tamper with the date manually in the URL
        $temperedUrl = str_replace('2026-07-01', '2026-07-02', $signedUrl);

        $response = $this->get($temperedUrl);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_board_with_invalid_token(): void
    {
        $signedUrl = URL::signedRoute('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => '2026-07-01',
        ]);

        // Tamper with the token
        $temperedUrl = str_replace('test-token-1234567890-abcdef', 'fake-token', $signedUrl);

        $response = $this->get($temperedUrl);

        $response->assertStatus(403);
    }
}
