<?php

namespace Modules\Series\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Series\Models\SeriesPlaylist;
use Modules\Series\Models\SeriesVideo;
use Modules\Series\Models\SeriesVideoProgress;
use Modules\Series\Services\YouTubeSyncService;
use Tests\TestCase;

class SeriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the YouTubeSyncService falls back to generating a valid mock playlist
     * with 5 lessons when no Google API key is configured.
     */
    public function test_youtube_sync_service_syncs_mock_playlist(): void
    {
        $user = User::factory()->create();
        $service = new YouTubeSyncService();

        // Trigger sync with mock fallback
        $playlist = $service->syncPlaylist($user->id, 'mock_laravel_basics');

        $this->assertNotNull($playlist);
        $this->assertEquals('Laravel 11 Basics (أساسيات لارافيل 11)', $playlist->title);
        $this->assertEquals('Musoftwares Code Academy', $playlist->channel_title);
        
        // Assert 5 videos were generated
        $this->assertCount(5, $playlist->videos);
        $this->assertDatabaseHas('series_playlists', [
            'youtube_playlist_id' => 'mock_laravel_basics',
            'user_id' => $user->id,
        ]);
        
        $this->assertDatabaseHas('series_videos', [
            'series_playlist_id' => $playlist->id,
            'youtube_video_id' => 'ImtZ5yENzgE',
            'position' => 0,
        ]);
    }

    /**
     * Test that an authenticated subscribed user can toggle the completion progress of a lesson.
     */
    public function test_user_can_toggle_video_complete_status(): void
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'object' => 'series',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $playlist = SeriesPlaylist::create([
            'user_id' => $user->id,
            'youtube_playlist_id' => 'mock_id',
            'title' => 'Test Playlist',
        ]);

        $video = SeriesVideo::create([
            'series_playlist_id' => $playlist->id,
            'youtube_video_id' => 'vid_123',
            'title' => 'Test Video Title',
            'position' => 0,
        ]);

        $this->actingAs($user)
            ->post("/series/video/{$video->id}/complete", [
                'is_completed' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('series_video_progress', [
            'user_id' => $user->id,
            'series_video_id' => $video->id,
            'is_completed' => true,
        ]);

        // Toggle back to false
        $this->actingAs($user)
            ->post("/series/video/{$video->id}/complete", [
                'is_completed' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('series_video_progress', [
            'user_id' => $user->id,
            'series_video_id' => $video->id,
            'is_completed' => false,
        ]);
    }

    /**
     * Test that an authenticated subscribed user can save study notes on a video.
     */
    public function test_user_can_save_notes_on_video(): void
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'object' => 'series',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $playlist = SeriesPlaylist::create([
            'user_id' => $user->id,
            'youtube_playlist_id' => 'mock_id',
            'title' => 'Test Playlist',
        ]);

        $video = SeriesVideo::create([
            'series_playlist_id' => $playlist->id,
            'youtube_video_id' => 'vid_123',
            'title' => 'Test Video Title',
            'position' => 0,
        ]);

        $notesContent = 'Laravel is a PHP framework. Must remember to run migration commands.';

        $this->actingAs($user)
            ->post("/series/video/{$video->id}/notes", [
                'notes' => $notesContent,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('series_video_progress', [
            'user_id' => $user->id,
            'series_video_id' => $video->id,
            'notes' => $notesContent,
        ]);
    }

    /**
     * Test dashboard routing permissions and subscription gates.
     */
    public function test_dashboard_requires_auth_and_subscription(): void
    {
        // 1. Guest redirected to login
        $this->get('/series')
            ->assertRedirect('/login');

        // 2. Auth user without active subscription redirected to pricing plans
        $unsubscribedUser = User::factory()->create();
        $this->actingAs($unsubscribedUser)
            ->get('/series')
            ->assertRedirect(route('subscriptions.plans', ['module' => 'series']));

        // 3. Auth user with active subscription gets successful page rendering
        $subscribedUser = User::factory()->create();
        $subscribedUser->subscriptions()->create([
            'object' => 'series',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($subscribedUser)
            ->get('/series')
            ->assertStatus(200);
    }
}
