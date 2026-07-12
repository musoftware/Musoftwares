<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
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

        $this->seed(RolesAndPermissionsSeeder::class);

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

    public function test_non_guest_can_access_today_board_without_signature(): void
    {
        $unsignedUrl = route('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => \Carbon\Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get($unsignedUrl);

        $response->assertStatus(200);
    }

    public function test_non_guest_can_access_past_board_without_signature(): void
    {
        $unsignedUrl = route('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => \Carbon\Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get($unsignedUrl);

        $response->assertStatus(200);
    }

    public function test_non_guest_cannot_access_future_board_without_signature(): void
    {
        $unsignedUrl = route('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => \Carbon\Carbon::tomorrow()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get($unsignedUrl);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_see_unpublished_or_future_reports(): void
    {
        $today = \Carbon\Carbon::today()->toDateString();

        // 1. Past published report (should be visible)
        $pastReport = \App\Models\ProjectReport::create([
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'title' => 'Past Published Report',
            'body' => 'Should see this',
            'published_at' => \Carbon\Carbon::now()->subMinutes(10),
        ]);

        // 2. Future published report (should be hidden)
        $futureReport = \App\Models\ProjectReport::create([
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'title' => 'Future Published Report',
            'body' => 'Should NOT see this',
            'published_at' => \Carbon\Carbon::now()->addMinutes(10),
        ]);

        // 3. Unpublished report (should be hidden)
        $unpublishedReport = \App\Models\ProjectReport::create([
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'title' => 'Unpublished Report',
            'body' => 'Should NOT see this',
            'published_at' => null,
        ]);

        $signedUrl = URL::signedRoute('shared-board.show', [
            'token' => $this->project->share_token,
            'date' => $today,
        ]);

        $response = $this->get($signedUrl);

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use ($pastReport, $futureReport, $unpublishedReport) {
            $cards = $page->toArray()['props']['cards'] ?? [];
            $cardIds = collect($cards)->where('type', 'report')->pluck('id')->all();

            $this->assertContains($pastReport->id, $cardIds, 'Past published report should be visible');
            $this->assertNotContains($futureReport->id, $cardIds, 'Future scheduled report should be hidden');
            $this->assertNotContains($unpublishedReport->id, $cardIds, 'Unpublished report should be hidden');
        });
    }
}

