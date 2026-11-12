<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Tests\TestCase;

/**
 * A project that started and was paid is a running conversation: the customer writes whenever they like, each change
 * becomes a request in the list beside the chat, fixes are free during the two-month warranty and start by
 * themselves, development is priced and paid from the list, and the engineer takes every ready request together.
 */
class ProjectRequestsTest extends TestCase
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

    /** A delivered, paid project, opened by the browser that started it. */
    private function deliveredProject(): BuilderProject
    {
        $project = BuilderProject::create([
            'ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'متجر', 'spec' => 'A shop.', 'project_type' => 'app_full',
            'status' => BuilderProject::READY, 'version' => 1, 'paid_total' => 150, 'built_at' => now(),
            'warranty_until' => now()->addDays((int) config('builder.warranty_days')),
        ]);
        $project->messages()->create(['role' => BuilderMessage::ASSISTANT, 'content' => 'تم التسليم.']);
        $this->withSession(['builder.started' => [$project->uuid]]);

        return $project;
    }

    /** The chat model answers every message with a brief of this kind. */
    private function chatAnswers(string $kind, string $title): void
    {
        $turn = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => $title, 'spec' => 'Note for '.$title, 'estimated_output_tokens' => 20000, 'kind' => $kind, 'complex' => false],
        ]);
        $this->app->instance(ChatModel::class, new class($turn) implements ChatModel
        {
            public function __construct(private ChatTurn $turn) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                return $this->turn;
            }
        });
    }

    private function say(BuilderProject $project, string $text): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => $text])->assertOk();
    }

    public function test_a_fix_in_the_warranty_is_free_and_starts_by_itself_with_the_customers_words(): void
    {
        $project = $this->deliveredProject();
        $this->chatAnswers('fix', 'زرار الاشتراك');

        $state = $this->say($project, 'زرار الاشتراك مش بيفتح');

        $state->assertJsonPath('requests.items.0.kind', 'fix')->assertJsonPath('requests.items.0.free', true)->assertJsonPath('requests.warranty.active', true);
        $this->assertSame(BuilderProject::QUEUED, $project->refresh()->status);
        $spec = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job.spec');
        $this->assertStringContainsString('زرار الاشتراك مش بيفتح', $spec);
        $this->assertStringContainsString('Note for زرار الاشتراك', $spec);
    }

    public function test_the_customer_keeps_writing_during_a_build_and_the_next_build_takes_what_waited(): void
    {
        $project = $this->deliveredProject();
        $this->chatAnswers('fix', 'أول تصليح');
        $this->say($project, 'صلح الأول');
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $this->assertSame(BuilderProject::BUILDING, $project->refresh()->status);

        // like a messaging app: the chat stays open while the engineer works
        $this->chatAnswers('fix', 'تاني تصليح');
        $this->say($project, 'وصلح التاني كمان')->assertJsonPath('open_for_chat', true);
        $this->assertSame(BuilderRequest::READY, $project->requests()->reorder('id', 'desc')->value('status'));

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(), 'summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        $this->assertSame([BuilderRequest::DONE, BuilderRequest::BUILDING], $project->requests()->pluck('status')->all());
        $this->assertSame(BuilderProject::QUEUED, $project->refresh()->status);
        $this->assertStringContainsString('وصلح التاني كمان', $this->postJson('/api/builder/worker/claim', [], $this->worker())->json('job.spec'));
    }

    public function test_development_is_priced_in_the_list_and_starts_once_paid(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $usd = \App\Models\Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = \App\Models\Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        \App\Models\CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
        $project = $this->deliveredProject();
        $this->chatAnswers('build', 'قسم بلس في لوحة التحكم');

        $state = $this->say($project, 'عايز قسم في الداشبورد لكل حاجة تخص البلس');

        $item = $state->json('requests.items.0');
        $this->assertSame(['build', false, 'unpaid'], [$item['kind'], $item['free'], $item['status']]);
        $this->assertGreaterThan(0, (float) $item['amount']);
        // a change to a Flutter app with its backend is priced by its own work, never as a whole new app of that type
        $this->assertLessThan((float) config('builder.project_types.app_full'), (float) $item['amount']);
        $this->assertSame(BuilderProject::READY, $project->refresh()->status);

        $this->postJson('/ai/'.$project->uuid.'/requests/pay', ['ids' => (string) $item['id'], 'client_name' => 'أحمد', 'client_email' => 'a@example.com', 'client_whatsapp' => '01012345678'])
            ->assertOk()->assertJson(fn ($json) => $json->where('url', fn ($url) => str_contains($url, 'payments.kashier.io')));
        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-r'.$item['id'].'_abc');
        $this->postJson('/ai/webhook', $body, $headers)->assertOk()->assertJsonPath('status', 'processed');
        // the gateway may report it twice; it counts once
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertEqualsWithDelta(150 + (float) $item['amount'], (float) $project->paid_total, 0.01);
    }

    public function test_after_the_warranty_a_fix_is_priced_too(): void
    {
        $project = $this->deliveredProject();
        $this->travel((int) config('builder.warranty_days') + 1)->days();
        $this->chatAnswers('fix', 'تصليح بعد الضمان');

        $state = $this->say($project, 'صلح الصفحة');

        $state->assertJsonPath('requests.items.0.kind', 'fix')->assertJsonPath('requests.items.0.free', false)->assertJsonPath('requests.items.0.status', 'unpaid')->assertJsonPath('requests.warranty.active', false);
        $this->assertSame(BuilderProject::READY, $project->refresh()->status);
    }

    public function test_a_chat_before_payment_has_no_list(): void
    {
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD']);
        $this->withSession(['builder.started' => [$project->uuid]]);

        $this->getJson('/ai/'.$project->uuid.'/state')->assertOk()->assertJsonPath('requests', null);
    }

    private function zip(): \Illuminate\Http\UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cbz');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('index.html', '<h1>v2</h1>');
        $zip->close();

        return new \Illuminate\Http\UploadedFile($path, 'program.zip', 'application/zip', null, true);
    }

    private function signedWebhook(string $merchantOrderId): array
    {
        $data = ['status' => 'SUCCESS', 'merchantOrderId' => $merchantOrderId, 'transactionId' => 'TX-9', 'signatureKeys' => ['merchantOrderId', 'status', 'transactionId']];
        $keys = $data['signatureKeys'];
        sort($keys);
        $query = http_build_query(array_combine($keys, array_map(fn ($key) => $data[$key], $keys)), '', '&', PHP_QUERY_RFC3986);

        return [['data' => $data], ['x-kashier-signature' => hash_hmac('sha256', $query, 'secret')]];
    }
}
