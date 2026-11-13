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
    use \Modules\AppBuilder\Tests\SignsInWithGoogle, RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInWithGoogle();
        Storage::fake('local');
        // the request flow on its own; the owner's approval in front of it is OwnerReviewTest
        config(['builder.worker_token' => self::TOKEN, 'builder.owner_approval' => false, 'builder.free_changes_days' => 0]);
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function admin(): \App\Models\User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function importAs(string $mode, array $extra = []): BuilderProject
    {
        $this->actingAs($this->admin())->post('/admin/builder/import', $extra + [
            'title' => 'Shop ERP',
            'description' => 'Laravel admin and a Flutter app for a shop.',
            'import_url' => 'https://drive.google.com/file/d/1AbCdEf/view?usp=sharing',
            'locale' => 'ar',
            'release_mode' => $mode,
            'release_after_changes' => 3,
            'release_quiet_minutes' => 60,
            'deploy_enabled' => true,
        ])->assertRedirect();
        $this->actingAs($this->customer);

        return BuilderProject::firstOrFail();
    }

    /** The worker takes the import and reports what it found; the chat is then ready for the customer. */
    private function imported(string $mode): BuilderProject
    {
        $project = $this->importAs($mode);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        // the repo comes from the project's own .git folder, read by the worker
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/imported', ['profile' => 'Laravel 11, Flutter app in /mobile.', 'github_repo' => 'acme/shop-erp'], $this->worker())->assertOk();

        return $project->refresh();
    }

    private function deliverChange(BuilderProject $project, array $release = []): void
    {
        $project->update(['status' => BuilderProject::QUEUED, 'job_kind' => BuilderProject::JOB_BUILD, 'paid_at' => now()]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.kind', 'build');
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/complete', $release + ['summary' => 'تم التعديل.', 'cost_usd' => 1], $this->worker())->assertOk();
    }

    public function test_the_owner_rolls_a_delivered_change_back_and_the_worker_reverts_its_commits(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_MANUAL);
        $item = $project->requests()->create(['title' => 'قسم البلس', 'spec' => 'Plus section.', 'kind' => 'build', 'amount' => 17, 'status' => \Modules\AppBuilder\Models\BuilderRequest::BUILDING]);
        $this->deliverChange($project, ['git_from' => 'aaaaaaa1', 'git_to' => 'bbbbbbb2']);
        $this->assertSame(['aaaaaaa1', 'bbbbbbb2'], [$item->refresh()->git_from, $item->git_to]);

        $this->actingAs($this->admin())->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/rollback')->assertRedirect();
        $this->actingAs($this->customer);

        $job = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job');
        $this->assertSame('rollback', $job['kind']);
        $this->assertSame(['from' => 'aaaaaaa1', 'to' => 'bbbbbbb2', 'title' => 'قسم البلس'], $job['rollback']);

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/released', ['deployed' => 1, 'note' => 'reverted and deployed'], $this->worker())->assertOk();

        $this->assertNotNull($item->refresh()->rolled_back_at);
        $this->assertSame(BuilderProject::READY, $project->refresh()->status);
        $this->assertTrue($project->messages()->where('content', 'like', '%قسم البلس%')->where('role', 'team')->exists());
        // the same change cannot be rolled back twice
        $this->actingAs($this->admin())->post('/admin/builder/'.$project->uuid.'/requests/'.$item->id.'/rollback')->assertStatus(409);
    }

    public function test_the_worker_gets_the_import_with_its_release_rules_and_nothing_else_is_asked(): void
    {
        $project = $this->importAs(BuilderProject::RELEASE_BATCH);

        $job = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job');
        $this->assertSame('import', $job['kind']);
        $this->assertSame('https://drive.google.com/file/d/1AbCdEf/view?usp=sharing', $job['import_url']);
        $this->assertTrue($job['deploy']);
        $this->assertFalse($job['release_now']);
        $this->assertSame([], $job['secrets']);
        $this->assertNull($project->github_repo);
    }

    public function test_an_imported_project_opens_as_a_delivered_chat_without_download_or_trial(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_BATCH);

        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertSame(1, $project->version);
        $this->assertSame('acme/shop-erp', $project->github_repo);
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

    public function test_a_delivered_change_whose_release_failed_waits_and_the_owner_hears(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_EACH);

        $this->deliverChange($project, ['release_error' => 'Release failed: Could not push to GitHub']);

        $project->refresh();
        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertSame(1, $project->changesWaiting());
        $this->assertStringContainsString('Could not push', $project->error);
    }

    public function test_only_an_imported_project_may_finish_a_build_without_files(): void
    {
        $other = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'en', 'currency' => 'USD', 'status' => BuilderProject::QUEUED, 'paid_at' => now(), 'title' => 'Page']);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        $this->postJson('/api/builder/worker/jobs/'.$other->uuid.'/complete', ['summary' => 'done', 'cost_usd' => 1], $this->worker())->assertStatus(422);
    }

    /** The chat model answers with a brief of this kind: "fix" for what the program has, "build" for something new. */
    private function chatAnswers(string $kind): void
    {
        $turn = \Modules\AppBuilder\Services\Chat\ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'تعديل', 'spec' => 'Short note.', 'estimated_output_tokens' => 400000, 'kind' => $kind, 'complex' => false],
        ]);
        $this->app->instance(\Modules\AppBuilder\Services\Chat\ChatModel::class, new class($turn) implements \Modules\AppBuilder\Services\Chat\ChatModel
        {
            public function __construct(private \Modules\AppBuilder\Services\Chat\ChatTurn $turn) {}

            public function reply(string $system, array $turns): \Modules\AppBuilder\Services\Chat\ChatTurn
            {
                return $this->turn;
            }
        });
    }

    /** The chat model hears "upload it" and answers with the order for the worker. */
    private function chatReleases(): void
    {
        $turn = \Modules\AppBuilder\Services\Chat\ChatTurn::fromArray(['reply' => 'تمام، بنرفعه دلوقتي', 'needs_team' => false, 'release_now' => true]);
        $this->app->instance(\Modules\AppBuilder\Services\Chat\ChatModel::class, new class($turn) implements \Modules\AppBuilder\Services\Chat\ChatModel
        {
            public function __construct(private \Modules\AppBuilder\Services\Chat\ChatTurn $turn) {}

            public function reply(string $system, array $turns): \Modules\AppBuilder\Services\Chat\ChatTurn
            {
                return $this->turn;
            }
        });
    }

    public function test_upload_it_in_the_chat_releases_the_changes_now(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_MANUAL);
        $this->deliverChange($project);
        $this->chatReleases();

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'انا فاهم ارفع التطبيق بقي علشان اشوفه'])->assertOk();

        $this->assertSame([BuilderProject::QUEUED, BuilderProject::JOB_RELEASE], [$project->refresh()->status, $project->job_kind]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.kind', 'release');
    }

    public function test_a_release_with_the_new_app_puts_its_download_in_the_chat(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_MANUAL);
        $this->deliverChange($project);
        app(ProjectImports::class)->queueRelease($project->refresh());
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $apk = \Illuminate\Http\UploadedFile::fake()->createWithContent('app-release.apk', 'PK'.str_repeat('x', 100));

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/released', ['deployed' => 1, 'note' => 'deployed', 'apk' => $apk], $this->worker())->assertOk();

        $state = $this->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        $this->assertStringContainsString('/app/release/app-release.apk', (string) $state->json('delivery.apk_url'));
        $this->assertSame(__('builder.import.live_with_app', [], 'ar'), collect($state->json('messages'))->last()['text']);
        $this->assertSame($project->refresh()->version, collect($state->json('messages'))->last()['delivered_version']);
    }

    public function test_upload_it_during_a_build_goes_out_right_after_the_build(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_MANUAL);
        $this->deliverChange($project);
        $project->refresh()->update(['status' => BuilderProject::QUEUED, 'job_kind' => BuilderProject::JOB_BUILD, 'paid_at' => now(), 'attempts' => 0]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $this->chatReleases();

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'ارفعه'])->assertOk();
        $this->assertNotNull($project->refresh()->release_asked_at);
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        $this->assertSame([BuilderProject::QUEUED, BuilderProject::JOB_RELEASE], [$project->refresh()->status, $project->job_kind]);
        $this->assertNull($project->release_asked_at);
    }

    public function test_a_fix_in_the_warranty_starts_at_once_from_the_customers_own_words(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_MANUAL);
        $this->chatAnswers('fix');

        // big work, but a fix during the two months after the import: free, with no count or size limit
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'زرار الاشتراك مش بيفتح'])->assertOk()->assertJsonPath('requests.items.0.free', true);

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertTrue($project->free_fix);
        $spec = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job.spec');
        $this->assertStringContainsString('زرار الاشتراك مش بيفتح', $spec);
        $this->assertStringContainsString('Short note.', $spec);
    }

    public function test_something_new_still_gets_a_price_and_the_study_follows_each_change(): void
    {
        $project = $this->imported(BuilderProject::RELEASE_MANUAL);
        $this->chatAnswers('build');

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'عايز نظام اشتراكات جديد'])->assertOk()
            ->assertJsonPath('requests.items.0.status', 'unpaid')->assertJsonPath('requests.items.0.free', false);
        $this->assertSame(BuilderProject::READY, $project->refresh()->status);

        $this->deliverChange($project, ['study' => '# ZainArena\n- Paywall for news and reels']);
        $profile = $project->refresh()->import_profile;
        $this->assertStringContainsString('Paywall for news and reels', $profile);
        $this->assertStringNotContainsString('Flutter app in /mobile', $profile);
        $this->assertStringContainsString('Laravel admin and a Flutter app', $profile);
    }

    public function test_the_import_form_takes_only_a_drive_link(): void
    {
        $this->actingAs($this->admin())->post('/admin/builder/import', [
            'title' => 'X', 'description' => 'Y', 'import_url' => 'https://example.com/x.zip',
            'locale' => 'ar', 'release_mode' => 'each', 'release_after_changes' => 1, 'release_quiet_minutes' => 60,
        ])->assertSessionHasErrors(['import_url']);

        $this->assertSame(0, BuilderProject::count());
    }
}
