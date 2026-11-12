<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\ProjectImports;
use Tests\TestCase;

/** The owner imports a customer's project; the customer changes it in the chat; changes go to GitHub and the server. */
class ProjectImportTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['builder.worker_token' => self::TOKEN]);
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function admin(): \App\Models\User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function importAs(string $mode, array $extra = []): BuilderProject
    {
        $this->actingAs($this->admin())->post('/admin/builder/import', $extra + [
            'title' => 'Shop ERP',
            'description' => 'Laravel admin and a Flutter app for a shop.',
            'import_url' => 'https://drive.google.com/file/d/1AbCdEf/view?usp=sharing',
            'github_repo' => 'https://github.com/acme/shop-erp.git',
            'github_token' => 'github_pat_secret',
            'locale' => 'ar',
            'release_mode' => $mode,
            'release_after_changes' => 3,
            'release_quiet_minutes' => 60,
            'deploy_enabled' => true,
            'deploy_command' => 'bash deploy.sh',
        ])->assertRedirect();
        auth()->logout();

        return BuilderProject::firstOrFail();
    }

    /** The worker takes the import and reports what it found; the chat is then ready for the customer. */
    private function imported(string $mode): BuilderProject
    {
        $project = $this->importAs($mode);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/imported', ['profile' => 'Laravel 11, Flutter app in /mobile.'], $this->worker())->assertOk();

        return $project->refresh();
    }

    private function deliverChange(BuilderProject $project, array $release = []): void
    {
        $project->update(['status' => BuilderProject::QUEUED, 'job_kind' => BuilderProject::JOB_BUILD, 'paid_at' => now()]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.kind', 'build');
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/complete', $release + ['summary' => 'تم التعديل.', 'cost_usd' => 1], $this->worker())->assertOk();
    }

    public function test_the_worker_gets_the_import_with_its_repo_and_rules_and_the_token_only_as_a_secret(): void
    {
        $project = $this->importAs(BuilderProject::RELEASE_BATCH);

        $this->assertSame('acme/shop-erp', $project->github_repo);
        $job = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job');
        $this->assertSame('import', $job['kind']);
        $this->assertSame('https://drive.google.com/file/d/1AbCdEf/view?usp=sharing', $job['import_url']);
        $this->assertSame('acme/shop-erp', $job['github_repo']);
        $this->assertTrue($job['deploy']);
        $this->assertFalse($job['release_now']);
        $this->assertSame('github_pat_secret', collect($job['secrets'])->firstWhere('name', 'github_token')['value']);
    }

    public function test_an_imported_project_opens_as_a_delivered_chat_without_download_or_trial(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_BATCH);

        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertSame(1, $project->version);
        $this->assertStringContainsString('Flutter app in /mobile', $project->import_profile);
        $state = $this->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        $state->assertJsonPath('open_for_chat', true)->assertJsonPath('delivery.download_url', null)->assertJsonPath('trial', fn ($trial) => $trial['can_request'] === false);
        $this->assertStringContainsString('Shop ERP', $state->json('messages.0.text'));
        // the customer's own project is never learned as a template for others
        $project->update(['paid_total' => 100]);
        $this->assertSame(0, app(\Modules\AppBuilder\Services\Starters\LearnedStarters::class)->learnFromDeliveries());
    }

    public function test_after_every_change_the_build_reports_its_pull_request_and_deploy(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_EACH);

        $this->deliverChange($project, ['released' => true, 'deployed' => true, 'pull_request_url' => 'https://github.com/acme/shop-erp/pull/7']);

        $project->refresh();
        $this->assertSame(2, $project->version);
        $this->assertSame(2, $project->released_version);
        $this->assertSame('https://github.com/acme/shop-erp/pull/7', $project->pull_request_url);
        $this->assertSame(__('builder.import.live', [], 'ar'), $project->messages()->where('role', BuilderMessage::TEAM)->latest('id')->value('content'));
    }

    public function test_a_batch_waits_for_enough_changes_or_a_quiet_customer_then_releases(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_BATCH);
        $imports = app(ProjectImports::class);

        $this->deliverChange($project);
        $project->messages()->create(['role' => BuilderMessage::USER, 'content' => 'كمان تعديل']);
        $this->assertSame(0, $imports->queueDueReleases());

        // an hour of silence after the last word and the last delivery
        $this->travel(61)->minutes();
        $this->assertSame(1, $imports->queueDueReleases());
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.kind', 'release');
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/released', ['pull_request_url' => 'https://github.com/acme/shop-erp/pull/8', 'deployed' => true], $this->worker())->assertOk();

        $project->refresh();
        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertSame(0, $project->changesWaiting());
    }

    public function test_a_failed_release_tells_only_the_owner_and_waits_for_release_now(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_BATCH);
        $project->update(['release_after_changes' => 1]);
        $this->deliverChange($project);
        $imports = app(ProjectImports::class);
        $imports->queueDueReleases();
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'ssh: connect timed out', 'cost_usd' => 0], $this->worker())->assertOk();

        $project->refresh();
        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertSame(1, $project->changesWaiting());
        $this->assertSame(0, $project->messages()->where('content', __('builder.failed', [], 'ar'))->count());
        // not retried by itself every few minutes
        $this->assertSame(0, $imports->queueDueReleases());
        $this->actingAs($this->admin())->post('/admin/builder/'.$project->uuid.'/release')->assertRedirect();
        $this->assertSame(BuilderProject::JOB_RELEASE, $project->refresh()->job_kind);
    }

    public function test_only_an_imported_project_may_finish_a_build_without_files(): void
    {
        $other = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'en', 'currency' => 'USD', 'status' => BuilderProject::QUEUED, 'paid_at' => now(), 'title' => 'Page']);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        $this->postJson('/api/builder/worker/jobs/'.$other->uuid.'/complete', ['summary' => 'done', 'cost_usd' => 1], $this->worker())->assertStatus(422);
    }

    public function test_the_import_form_takes_only_drive_and_github_links(): void
    {
        $this->actingAs($this->admin())->post('/admin/builder/import', [
            'title' => 'X', 'description' => 'Y', 'import_url' => 'https://example.com/x.zip', 'github_repo' => 'https://gitlab.com/a/b',
            'locale' => 'ar', 'release_mode' => 'each', 'release_after_changes' => 1, 'release_quiet_minutes' => 60,
        ])->assertSessionHasErrors(['import_url', 'github_repo']);

        $this->assertSame(0, BuilderProject::count());
    }
}
