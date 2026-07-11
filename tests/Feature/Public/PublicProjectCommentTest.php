<?php

namespace Tests\Feature\Public;

use App\Models\Project;
use App\Models\ProjectBoardNote;
use App\Models\ProjectFile;
use App\Models\ProjectReport;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicProjectCommentTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): Project
    {
        $userId = (int) \DB::table('users')->insertGetId([
            'name' => 'Guest Tester',
            'email' => 'owner-'.Str::random(6).'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Project::create([
            'user_id' => $userId,
            'project_name' => 'Demo project',
            'share_token' => Str::random(32),
        ]);
    }

    public function test_guest_can_post_comment_on_a_published_report(): void
    {
        $project = $this->makeProject();
        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $project->user_id,
            'title' => 'Open report',
            'body' => 'hi',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->postJson(route('public.comments.store', [
            'token' => $project->share_token,
        ]), [
            'type' => 'report',
            'commentable_id' => $report->id,
            'body' => 'Hello from a guest!',
            'guest_name' => 'Visitor',
            'guest_email' => 'visitor@example.com',
        ]);

        $response->assertOk()->assertJsonStructure(['ok', 'comment']);
        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'author_id' => null,
            'guest_name' => 'Visitor',
            'guest_email' => 'visitor@example.com',
            'commentable_type' => ProjectReport::class,
            'commentable_id' => $report->id,
            'body' => 'Hello from a guest!',
        ]);
    }

    public function test_guest_post_requires_name_and_email(): void
    {
        $project = $this->makeProject();
        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $project->user_id,
            'title' => 'Open',
            'body' => 'hi',
            'published_at' => now()->subDay(),
        ]);

        $this->postJson(route('public.comments.store', [
            'token' => $project->share_token,
        ]), [
            'type' => 'report',
            'commentable_id' => $report->id,
            'body' => 'Anonymous',
        ])->assertStatus(422)->assertJsonValidationErrors(['guest_name', 'guest_email']);

        $this->assertDatabaseCount('project_comments', 0);
    }

    public function test_guest_index_returns_thread_for_a_card(): void
    {
        $project = $this->makeProject();
        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $project->user_id,
            'title' => 'Open',
            'body' => 'hi',
            'published_at' => now()->subDay(),
        ]);

        $this->postJson(route('public.comments.store', [
            'token' => $project->share_token,
        ]), [
            'type' => 'report',
            'commentable_id' => $report->id,
            'body' => 'First comment',
            'guest_name' => 'Visitor',
            'guest_email' => 'visitor@example.com',
        ])->assertOk();

        $response = $this->getJson(route('public.comments.index', [
            'token' => $project->share_token,
            'type' => 'report',
            'id' => $report->id,
        ]));

        $response->assertOk()->assertJsonStructure(['comments', 'count']);
        $this->assertSame(1, $response->json('count'));
        $this->assertSame('First comment', $response->json('comments.0.body'));
        $this->assertSame('Visitor', $response->json('comments.0.guest_name'));
        $this->assertTrue($response->json('comments.0.is_guest'));
    }

    public function test_invalid_share_token_returns_404(): void
    {
        $this->getJson(route('public.comments.index', [
            'token' => 'definitely-not-real',
            'type' => 'report',
            'id' => 1,
        ]))->assertNotFound();

        $this->postJson(route('public.comments.store', [
            'token' => 'definitely-not-real',
        ]), [
            'type' => 'report',
            'commentable_id' => 1,
            'body' => 'no',
        ])->assertNotFound();
    }

    public function test_guest_can_comment_on_note_task_todo_and_file(): void
    {
        $project = $this->makeProject();

        $note = ProjectBoardNote::create([
            'project_id' => $project->id,
            'author_id' => $project->user_id,
            'content' => 'A note',
            'color' => 'yellow',
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
            'for_date' => now()->toDateString(),
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'task_name' => 'A task',
            'task_description' => '',
            'priority' => 'normal',
        ]);

        $todo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'title' => 'A todo',
            'description' => null,
            'completed' => false,
            'inDate' => now()->toDateString(),
            'priority' => 'normal',
            'priorityColor' => '#000000',
            'tags' => '',
        ]);

        $file = ProjectFile::create([
            'project_id' => $project->id,
            'uploaded_by' => $project->user_id,
            'original_name' => 'hello.txt',
            'mime' => 'text/plain',
            'size' => 12,
            'disk_path' => 'project_files/'.Str::random(8).'.txt',
        ]);

        $types = [
            'note' => $note->id,
            'task' => $task->id,
            'todo' => $todo->id,
            'file' => $file->id,
        ];

        foreach ($types as $type => $id) {
            $resp = $this->postJson(route('public.comments.store', [
                'token' => $project->share_token,
            ]), [
                'type' => $type,
                'commentable_id' => $id,
                'body' => "Comment on $type",
                'guest_name' => 'Visitor',
                'guest_email' => 'visitor@example.com',
            ]);
            $resp->assertOk();
            $this->assertDatabaseHas('project_comments', [
                'project_id' => $project->id,
                'commentable_id' => $id,
                'body' => "Comment on $type",
                'guest_name' => 'Visitor',
            ]);
        }
    }

    public function test_authenticated_user_comment_attributes_to_their_account(): void
    {
        $project = $this->makeProject();
        $userId = (int) \DB::table('users')->insertGetId([
            'name' => 'Auth Visitor',
            'email' => 'auth-'.Str::random(6).'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $userId,
            'title' => 'Open',
            'body' => 'hi',
            'published_at' => now()->subDay(),
        ]);

        $user = User::find($userId);
        $this->actingAs($user)->postJson(route('public.comments.store', [
            'token' => $project->share_token,
        ]), [
            'type' => 'report',
            'commentable_id' => $report->id,
            'body' => 'Logged-in commenter',
        ])->assertOk();

        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'author_id' => $userId,
            'guest_name' => null,
            'guest_email' => null,
            'commentable_id' => $report->id,
            'body' => 'Logged-in commenter',
        ]);
    }
}
