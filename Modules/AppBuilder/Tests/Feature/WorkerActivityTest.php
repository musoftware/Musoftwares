<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSecret;
use Modules\AppBuilder\Models\BuilderWorkerEvent;
use Tests\TestCase;

/** Every worker call on a project, and each commit of its build, shows on the owner's page for that project. */
class WorkerActivityTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-activity-token';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['builder.worker_token' => self::TOKEN, 'builder.owner_approval' => false]);
    }

    private function worker(string $role = 'main'): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json', 'X-Worker-Role' => $role];
    }

    private function project(string $status): BuilderProject
    {
        return BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'متجر', 'amount' => 20, 'paid_total' => 20, 'paid_at' => now(), 'status' => $status]);
    }

    /** @return list<array{0: string, 1: string|null, 2: int}> kind, detail and repeats, oldest first */
    private function log(BuilderProject $project): array
    {
        return BuilderWorkerEvent::where('project_id', $project->id)->orderBy('id')->get()
            ->map(fn (BuilderWorkerEvent $event) => [$event->kind, $event->detail, $event->repeats])->all();
    }

    public function test_a_build_is_logged_from_the_claim_to_its_commits(): void
    {
        $project = $this->project(BuilderProject::QUEUED);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $jobs = '/api/builder/worker/jobs/'.$project->uuid;
        $this->postJson($jobs.'/progress', ['step' => 'writing', 'file' => 'index.html'], $this->worker())->assertOk();
        $this->postJson($jobs.'/progress', ['step' => 'writing', 'file' => 'index.html'], $this->worker())->assertOk();
        $this->postJson($jobs.'/commits', ['commits' => [
            ['hash' => str_repeat('a', 40), 'at' => 1700000000, 'message' => 'Add the menu page'],
            ['hash' => str_repeat('b', 40), 'at' => 1700000060, 'message' => 'Fix the prices'],
        ]], $this->worker())->assertOk();

        $this->assertSame([
            ['claim', 'build · متجر', 1],
            // the same step again only counts up
            ['progress', 'writing index.html', 2],
            ['commit', 'aaaaaaa Add the menu page', 1],
            ['commit', 'bbbbbbb Fix the prices', 1],
        ], $this->log($project));
    }

    public function test_a_refused_call_is_logged_and_an_access_detail_never_shows(): void
    {
        $project = $this->project(BuilderProject::BUILDING);
        BuilderSecret::create(['project_id' => $project->id, 'name' => 'ssh_password', 'value' => 'Very-Secret-99', 'source' => 'team']);
        $jobs = '/api/builder/worker/jobs/'.$project->uuid;

        $this->postJson($jobs.'/question', ['question' => 'The password Very-Secret-99 is refused, which one?'], $this->worker('backup'))->assertOk();
        $project->update(['status' => BuilderProject::READY]);
        $this->postJson($jobs.'/progress', ['step' => 'writing'], $this->worker())->assertStatus(409);

        $log = $this->log($project);
        $this->assertSame('question', $log[0][0]);
        $this->assertStringNotContainsString('Very-Secret-99', (string) $log[0][1]);
        $this->assertSame('backup', BuilderWorkerEvent::where('project_id', $project->id)->orderBy('id')->value('worker'));
        $this->assertSame(['refused', 'progress: 409 This job is not being built.'], array_slice($log[1], 0, 2));
    }

    public function test_commits_are_checked_before_they_are_logged(): void
    {
        $project = $this->project(BuilderProject::BUILDING);

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/commits', ['commits' => [['hash' => 'not-a-hash; rm -rf /', 'at' => 1]]], $this->worker())->assertStatus(422);

        $this->assertSame(0, BuilderWorkerEvent::where('kind', 'commit')->count());
    }

    public function test_the_owner_reads_the_log_on_the_project_page(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $project = $this->project(BuilderProject::BUILDING);
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/progress', ['step' => 'testing'], $this->worker())->assertOk();

        $this->actingAs($admin)->get('/admin/builder/'.$project->uuid)
            ->assertInertia(fn ($page) => $page->where('workerEvents.0.kind', 'progress')->where('workerEvents.0.detail', 'testing'));
    }
}
