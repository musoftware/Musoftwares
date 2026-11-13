<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSecret;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Tests\TestCase;

/** Access details typed in the chat, questions from a running build, and the owner stepping in. */
class BuilderSecretsAndQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    private const PASSWORD = 'S3cret-Pass-9921';

    /** @var list<ChatTurn> */
    private array $script = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        // the array mailer really sends, into memory, so the test can read who got what
        config(['mail.default' => 'array']);
        config(['builder.worker_token' => self::TOKEN]);

        // Claude is the only part replaced: a scripted model that answers in order and counts its calls.
        $this->app->instance(ChatModel::class, new class($this->script) implements ChatModel
        {
            public int $calls = 0;

            public function __construct(private array &$script) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                $this->calls++;

                return array_shift($this->script);
            }
        });
    }

    private function answer(string $reply, array $secrets = []): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => $reply,
            'needs_team' => false,
            'quote' => ['ready' => false, 'title' => '', 'spec' => '', 'estimated_output_tokens' => 0, 'kind' => 'build', 'complex' => false],
            'secrets' => $secrets,
        ]);
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function buildingProject(): BuilderProject
    {
        $this->answer('أهلاً');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز موقع'])->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $project->update(['status' => BuilderProject::BUILDING, 'client_email' => 'client@example.com']);

        return $project;
    }

    public function test_access_details_typed_in_the_chat_are_saved_encrypted_and_masked(): void
    {
        $this->answer('تمام، استلمت بيانات السيرفر', [['name' => 'ssh_host', 'value' => '203.0.113.10'], ['name' => 'ssh_password', 'value' => self::PASSWORD]]);

        $uuid = $this->postJson('/ai/messages', ['text' => 'السيرفر 203.0.113.10 والباسورد '.self::PASSWORD])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        $this->assertSame(self::PASSWORD, $project->secrets()->where('name', 'ssh_password')->first()->value);
        $this->assertStringNotContainsString(self::PASSWORD, (string) DB::table('builder_project_secrets')->where('name', 'ssh_password')->value('value'));
        $this->assertStringNotContainsString(self::PASSWORD, $project->messages()->first()->content);
        $this->assertStringNotContainsString(self::PASSWORD, $this->getJson('/ai/'.$uuid.'/state')->getContent());
    }

    public function test_the_worker_gets_the_access_details_with_the_job(): void
    {
        $project = $this->buildingProject();
        BuilderSecret::create(['project_id' => $project->id, 'name' => 'ssh_host', 'value' => '203.0.113.10', 'source' => 'team']);
        $project->update(['status' => BuilderProject::QUEUED]);

        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()
            ->assertJsonPath('job.secrets.0', ['name' => 'ssh_host', 'value' => '203.0.113.10']);
    }

    public function test_a_build_question_waits_for_the_customer_who_answers_in_the_chat(): void
    {
        $project = $this->buildingProject();

        $asked = $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/question', ['question' => 'اسم الدومين إيه؟'], $this->worker())->assertOk()->json('message_id');
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('open_for_chat', true)->assertJsonPath('waiting_for_answer', true);
        $recipients = collect(app('mailer')->getSymfonyTransport()->messages())
            ->flatMap(fn ($sent) => array_map(fn ($address) => $address->getAddress(), $sent->getEnvelope()->getRecipients()));
        $this->assertContains('client@example.com', $recipients->all());

        $callsBefore = $this->app->make(ChatModel::class)->calls;
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'mysite.com'])->assertOk()->assertJsonPath('waiting_for_answer', false);
        $this->assertSame($callsBefore, $this->app->make(ChatModel::class)->calls);

        $this->getJson('/api/builder/worker/jobs/'.$project->uuid.'/answers?after='.$asked, $this->worker())->assertOk()
            ->assertJsonPath('answers.0.text', 'mysite.com')->assertJsonPath('answers.0.from', 'customer');
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'لسه؟'])->assertStatus(409);
    }

    public function test_the_owner_answers_and_adds_access_details_from_the_admin_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $project = $this->buildingProject();
        $asked = $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/question', ['question' => 'بيانات الـ FTP؟'], $this->worker())->json('message_id');

        $this->actingAs($admin)->get('/admin/builder')->assertOk();
        $this->actingAs($admin)->get('/admin/builder/'.$project->uuid)->assertOk();
        $this->actingAs($admin)->post('/admin/builder/'.$project->uuid.'/secrets', ['name' => 'ftp_password', 'value' => self::PASSWORD])->assertRedirect();
        $this->actingAs($admin)->post('/admin/builder/'.$project->uuid.'/messages', ['text' => 'ضفت الباسورد '.self::PASSWORD])->assertRedirect();

        $answers = $this->getJson('/api/builder/worker/jobs/'.$project->uuid.'/answers?after='.$asked, $this->worker())->assertOk();
        $answers->assertJsonPath('answers.0.from', 'team')->assertJsonPath('secrets.0.name', 'ftp_password');
        $this->assertStringNotContainsString(self::PASSWORD, $project->messages()->where('role', BuilderMessage::TEAM)->first()->content);
    }

    public function test_only_an_admin_can_open_the_admin_pages(): void
    {
        $project = $this->buildingProject();
        $customer = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($customer)->get('/admin/builder/'.$project->uuid)->assertForbidden();
        $this->actingAs($customer)->post('/admin/builder/'.$project->uuid.'/secrets', ['name' => 'x', 'value' => 'y'])->assertForbidden();
    }

    public function test_the_worker_pulls_the_whole_chat_with_secrets_masked(): void
    {
        $this->answer('تمام', [['name' => 'ssh_password', 'value' => self::PASSWORD]]);
        $uuid = $this->postJson('/ai/messages', ['text' => 'الباسورد '.self::PASSWORD])->json('uuid');
        BuilderProject::where('uuid', $uuid)->update(['status' => BuilderProject::BUILDING]);

        $chat = $this->getJson('/api/builder/worker/jobs/'.$uuid.'/chat', $this->worker())->assertOk();

        $chat->assertJsonCount(2, 'messages')->assertJsonPath('messages.0.role', 'user')->assertJsonPath('messages.1.text', 'تمام');
        $this->assertStringNotContainsString(self::PASSWORD, $chat->getContent());
        $this->getJson('/api/builder/worker/jobs/'.$uuid.'/chat')->assertUnauthorized();
    }

    public function test_a_customer_who_allowed_notifications_gets_one_when_the_build_asks(): void
    {
        config(['services.fcm.web.api_key' => 'public-key', 'services.fcm.web.vapid_key' => 'vapid']);
        \Illuminate\Support\Facades\Queue::fake();
        $project = $this->buildingProject();
        $project->update(['paid_at' => now()]);

        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('push', route('builder.push', $project));
        $this->postJson('/ai/'.$project->uuid.'/push', ['token' => 'browser-token-1'])->assertOk();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('push', null);

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/question', ['question' => 'اسم الدومين؟'], $this->worker())->assertOk();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendFcmNotificationJob::class);
    }
}
