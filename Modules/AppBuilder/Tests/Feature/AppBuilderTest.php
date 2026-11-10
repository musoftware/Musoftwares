<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatModel;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Modules\AppBuilder\Services\Chat\ChatUnavailable;
use Tests\TestCase;
use ZipArchive;

class AppBuilderTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    /** @var list<ChatTurn|ChatUnavailable> */
    private array $script = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['builder.worker_token' => self::TOKEN]);
        // these tests check the markup arithmetic; the minimum per project type has its own test
        config(['builder.project_types.other' => 0]);

        // Claude itself is the only part replaced: a scripted model that answers in order.
        $this->app->instance(ChatModel::class, new class($this->script) implements ChatModel
        {
            public array $seen = [];

            public function __construct(private array &$script) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                $this->seen[] = $turns;
                $next = array_shift($this->script);

                return $next instanceof ChatUnavailable ? throw $next : $next;
            }
        });
    }

    private function answer(string $reply, int $tokens = 0, bool $needsTeam = false): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => $reply,
            'needs_team' => $needsTeam,
            'quote' => ['ready' => $tokens > 0, 'title' => $tokens ? 'منيو مطعم' : '', 'spec' => $tokens ? 'Build a restaurant menu page.' : '', 'estimated_output_tokens' => $tokens],
        ], 1200, 300);
    }

    private function quotedProject(): BuilderProject
    {
        $this->answer('تمام، ده السعر', 50000);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز منيو لمطعمي'])->assertOk()->json('uuid');

        return BuilderProject::where('uuid', $uuid)->firstOrFail();
    }

    private function worker(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN, 'Accept' => 'application/json'];
    }

    private function zip(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bld').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, 'program.zip', 'application/zip', null, true);
    }

    private function signedWebhook(string $merchantOrderId, string $transactionId = 'TX-1'): array
    {
        $data = ['status' => 'SUCCESS', 'merchantOrderId' => $merchantOrderId, 'transactionId' => $transactionId, 'signatureKeys' => ['merchantOrderId', 'status', 'transactionId']];
        $keys = $data['signatureKeys'];
        sort($keys);
        $signed = array_combine($keys, array_map(fn ($key) => $data[$key], $keys));
        $query = http_build_query($signed, '', '&', PHP_QUERY_RFC3986);

        return [['data' => $data], ['x-kashier-signature' => hash_hmac('sha256', $query, 'secret')]];
    }

    private function buildingProject(): BuilderProject
    {
        $project = $this->quotedProject();
        $project->update(['status' => BuilderProject::QUEUED, 'paid_at' => now()]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        return $project->refresh();
    }

    public function test_the_page_is_a_plain_chat(): void
    {
        $this->get('/ai')->assertOk()->assertSee(__('builder.greeting'))->assertSee('id="composer"', false);
    }

    public function test_the_first_message_opens_a_chat_and_stores_both_lines(): void
    {
        $this->answer('أهلاً، المطعم اسمه إيه؟');

        $response = $this->postJson('/ai/messages', ['text' => 'عايز منيو لمطعمي'])->assertOk();

        $response->assertJsonPath('status', BuilderProject::CHATTING)->assertJsonPath('quote', null);
        $response->assertJsonPath('messages.1.text', 'أهلاً، المطعم اسمه إيه؟');
        $project = BuilderProject::firstOrFail();
        $this->assertSame(2, $project->messages()->count());
        $this->assertSame(1200, (int) $project->chat_input_tokens);
    }

    public function test_a_ready_brief_becomes_a_price_without_showing_the_spec(): void
    {
        $project = $this->quotedProject();

        // 50k tokens on the standard model: x $10/MTok x work factor 4 = $2 cost, x markup 3 = $6
        $this->assertSame(BuilderProject::QUOTED, $project->status);
        $this->assertSame('6.00', $project->amount);
        $this->assertSame('4.0000', $project->budget_usd);
        $state = $this->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        $state->assertJsonPath('quote.amount', '6.00')->assertJsonPath('quote.currency', 'USD');
        $this->assertStringNotContainsString('Build a restaurant menu page', $state->getContent());
    }

    public function test_claude_sees_its_earlier_quote_and_a_changed_scope_withdraws_the_price(): void
    {
        $project = $this->quotedProject();
        $this->answer('تمام، هنضيف صفحة حجز. محتاج أعرف عدد الترابيزات');

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'ضيف حجز ترابيزات'])->assertOk()->assertJsonPath('quote', null);

        $this->assertSame(BuilderProject::CHATTING, $project->refresh()->status);
        $seen = $this->app->make(ChatModel::class)->seen[1];
        $this->assertStringContainsString('"estimated_output_tokens":50000', $seen[1]['content']);
    }

    public function test_a_failed_claude_turn_drops_the_unanswered_line(): void
    {
        $project = $this->quotedProject();
        $this->script[] = new ChatUnavailable('overloaded');

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'سؤال'])->assertStatus(503)->assertJsonPath('message', __('builder.errors.unavailable'));

        $this->assertSame(2, $project->messages()->count());
    }

    public function test_one_address_gets_a_limited_number_of_chats_per_day(): void
    {
        config(['builder.limits.projects_per_ip_per_day' => 1]);
        $this->answer('أهلاً');
        $this->postJson('/ai/messages', ['text' => 'أول'])->assertOk();

        $this->postJson('/ai/messages', ['text' => 'تاني'])->assertStatus(429);
    }

    public function test_paying_the_open_quote_queues_the_build(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
        $project = $this->quotedProject();

        $this->postJson('/ai/'.$project->uuid.'/pay', ['client_name' => 'أحمد', 'client_email' => 'a@example.com', 'client_whatsapp' => '01012345678'])
            ->assertOk()->assertJson(fn ($json) => $json->where('url', fn ($url) => str_contains($url, 'payments.kashier.io')));

        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-1_abc123');
        $this->postJson('/ai/webhook', $body, $headers)->assertOk()->assertJsonPath('status', 'processed');

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertSame('6.00', $project->paid_total);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('progress', __('builder.steps.queued'))->assertJsonPath('open_for_chat', false);
    }

    public function test_paying_an_old_quote_or_an_unsigned_webhook_starts_nothing(): void
    {
        config(['services.kashier.secret_key' => 'secret']);
        $project = $this->quotedProject();

        $this->postJson('/ai/webhook', ['data' => ['status' => 'SUCCESS', 'merchantOrderId' => 'bld_'.$project->id.'-1_x']])->assertStatus(400);
        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-9_abc');
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();

        $this->assertSame(BuilderProject::QUOTED, $project->refresh()->status);
    }

    public function test_the_worker_api_needs_the_token(): void
    {
        $this->postJson('/api/builder/worker/claim')->assertStatus(401);
        $this->postJson('/api/builder/worker/claim', [], ['Authorization' => 'Bearer wrong'])->assertStatus(401);
        config(['builder.worker_token' => '']);
        $this->postJson('/api/builder/worker/claim', [], ['Authorization' => 'Bearer '])->assertStatus(401);
    }

    public function test_the_worker_claims_reports_and_delivers_a_program(): void
    {
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();
        $project = $this->quotedProject();
        $project->update(['status' => BuilderProject::QUEUED, 'paid_at' => now()]);

        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()
            ->assertJsonPath('job.uuid', $project->uuid)->assertJsonPath('job.spec', 'Build a restaurant menu page.')->assertJsonPath('job.locale', 'ar')->assertJsonPath('job.budget_usd', 4)->assertJsonPath('job.model', 'claude-sonnet-5-5');
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/progress', ['step' => 'writing', 'file' => 'index.html'], $this->worker())->assertOk();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('progress', __('builder.steps.writing').' — index.html');

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', [
            'archive' => $this->zip(['index.html' => '<h1>Menu</h1><script src="app.js"></script>', 'app.js' => 'console.log(1)']),
            'summary' => 'المنيو جاهز',
            'cost_usd' => 3.25,
        ], $this->worker())->assertOk()->assertJsonPath('version', 1);

        $project->refresh();
        $this->assertSame(BuilderProject::READY, $project->status);
        $this->assertSame('3.2500', $project->build_cost_usd);
        $this->assertSame('المنيو جاهز', $project->messages()->get()->last()->content);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('delivery.version', 1)->assertJsonPath('open_for_chat', true);
    }

    public function test_the_delivered_program_runs_in_a_sandbox_and_cannot_leave_its_folder(): void
    {
        $project = $this->buildingProject();
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>', 'css/site.css' => 'h1{}']), 'summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        // relative links only resolve inside /app/ when the page is opened as /app/index.html
        $this->get('/ai/'.$project->uuid.'/app')->assertRedirect('/ai/'.$project->uuid.'/app/index.html');
        $page = $this->get('/ai/'.$project->uuid.'/app/index.html')->assertOk();
        $this->assertStringStartsWith('sandbox allow-scripts', $page->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('allow-same-origin', $page->headers->get('Content-Security-Policy'));
        $this->get('/ai/'.$project->uuid.'/app/css/site.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->get('/ai/'.$project->uuid.'/app/missing.html')->assertNotFound();
        $this->get('/ai/'.$project->uuid.'/download')->assertOk();
        // the site firewall refuses "../" and ".env" with 403 and then blocks the address; either way nothing is served
        $this->assertContains($this->get('/ai/'.$project->uuid.'/app/../v1.zip')->status(), [403, 404]);
        $this->assertContains($this->get('/ai/'.$project->uuid.'/app/.env')->status(), [403, 404]);
    }

    public function test_an_archive_with_unsafe_names_or_no_entry_page_is_refused(): void
    {
        $project = $this->buildingProject();

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => 'x', '../escape.php' => '<?php']), 'summary' => 'x', 'cost_usd' => 0], $this->worker())->assertStatus(422);
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['main.html' => 'x']), 'summary' => 'x', 'cost_usd' => 0], $this->worker())->assertStatus(422);

        $this->assertSame(BuilderProject::BUILDING, $project->refresh()->status);
        $this->assertSame(0, $project->version);
    }

    public function test_a_worker_failure_is_recorded_and_shown_and_a_dead_worker_is_retried(): void
    {
        $project = $this->buildingProject();
        $project->update(['lease_expires_at' => now()->subMinute()]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.uuid', $project->uuid);
        $this->assertSame(2, $project->refresh()->attempts);

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'budget reached', 'cost_usd' => 8.1], $this->worker())->assertOk();

        $this->assertSame(BuilderProject::FAILED, $project->refresh()->status);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('failed', true)->assertJsonPath('open_for_chat', false);
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/progress', ['step' => 'writing'], $this->worker())->assertStatus(409);
    }

    public function test_the_owner_can_send_a_failed_build_back_to_the_worker(): void
    {
        $project = $this->buildingProject();
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'The pipe is being closed.', 'cost_usd' => 40], $this->worker())->assertOk();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->post('/admin/builder/'.$project->uuid.'/retry')->assertRedirect();

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertNull($project->error);
        // what the first try cost stays on the books
        $this->assertSame(40.0, (float) $project->build_cost_usd);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.uuid', $project->uuid);

        // only a failed build can be retried
        $this->actingAs($admin)->post('/admin/builder/'.$project->uuid.'/retry')->assertStatus(409);
    }

    public function test_the_chat_is_closed_while_the_program_is_being_built(): void
    {
        $project = $this->buildingProject();

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'لسه؟'])->assertStatus(409);
        $this->assertSame(1, $project->messages()->where('role', BuilderMessage::USER)->count());
    }

    /** Claude's answer after a delivery: a brief for a fix (free when allowed) or for a paid change. */
    private function answerAfterDelivery(string $kind, int $tokens, bool $complex = false): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'منيو مطعم', 'spec' => 'Fix the menu.', 'estimated_output_tokens' => $tokens, 'kind' => $kind, 'complex' => $complex],
        ], 100, 50);
    }

    private function deliveredProject(): BuilderProject
    {
        $project = $this->buildingProject();
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        return $project->refresh();
    }

    public function test_a_complex_job_gets_the_strong_model_and_its_price(): void
    {
        $this->answerAfterDelivery('build', 50000, complex: true);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز نظام كامل'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // 50k tokens x $20/MTok x 4 = $4 cost, x 3 = $12
        $this->assertSame('claude-opus-5-5', $project->build_model);
        $this->assertSame('12.00', $project->amount);
    }

    public function test_a_bug_fix_after_delivery_is_free_and_starts_without_payment(): void
    {
        $project = $this->deliveredProject();
        $paidBefore = $project->paid_total;
        $this->answerAfterDelivery('fix', 10000);

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'الزرار مش شغال'])->assertOk()
            ->assertJsonPath('quote.free', true);
        $this->postJson('/ai/'.$project->uuid.'/fix')->assertOk()->assertJsonPath('status', BuilderProject::QUEUED);

        $project->refresh();
        $this->assertSame(1, $project->free_fixes_used);
        $this->assertSame($paidBefore, $project->paid_total);
    }

    public function test_a_change_or_a_fix_past_the_limit_is_paid(): void
    {
        $project = $this->deliveredProject();
        $this->answerAfterDelivery('build', 10000);
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'ضيف صفحة جديدة'])->assertOk()->assertJsonPath('quote.free', false);
        $this->postJson('/ai/'.$project->uuid.'/fix')->assertStatus(409);

        $project->update(['free_fixes_used' => 3]);
        $this->answerAfterDelivery('fix', 10000);
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'فيه عطل تاني'])->assertOk()->assertJsonPath('quote.free', false);
        $this->postJson('/ai/'.$project->uuid.'/fix')->assertStatus(409);
    }

    public function test_a_server_program_is_delivered_with_its_setup_guide_and_no_preview(): void
    {
        $project = $this->buildingProject();

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['SETUP.md' => '# Setup', 'artisan' => '<?php', '.env.example' => 'APP_KEY=']), 'summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('delivery.version', 1)->assertJsonPath('delivery.preview_url', null);
    }

    public function test_a_real_env_file_is_never_accepted(): void
    {
        $project = $this->buildingProject();

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['SETUP.md' => '# Setup', '.env' => 'DB_PASSWORD=secret']), 'summary' => 'x', 'cost_usd' => 0], $this->worker())->assertStatus(422);
    }

    private function model(): object
    {
        return $this->app->make(ChatModel::class);
    }

    /** A real file on disk: Laravel's fake files report a type from the name, real ones from the content. */
    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'att');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function png(): UploadedFile
    {
        return $this->upload('logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    private function pdf(): UploadedFile
    {
        return $this->upload('menu.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }

    public function test_the_customer_can_send_images_and_pdfs_and_claude_sees_them(): void
    {
        $this->answer('شفت اللوجو والمنيو');

        $response = $this->post('/ai/messages', ['text' => 'ده اللوجو والمنيو', 'files' => [$this->png(), $this->pdf()]], ['Accept' => 'application/json'])->assertOk();

        $response->assertJsonPath('messages.0.files', ['logo.png', 'menu.pdf']);
        $sent = $this->model()->seen[0][0]['content'];
        $this->assertSame(['image', 'document', 'text'], array_column($sent, 'type'));
        $this->assertSame('image/png', $sent[0]['source']['mediaType']);
        $this->assertSame('ده اللوجو والمنيو', $sent[2]['text']);
    }

    public function test_files_alone_are_enough_and_other_file_types_are_refused(): void
    {
        $this->answer('تمام');
        $this->post('/ai/messages', ['files' => [$this->png()]], ['Accept' => 'application/json'])->assertOk();

        // a program renamed to .png is refused because its content is not an image
        $exe = $this->upload('logo.png', "MZ\x90\x00\x03\x00\x00\x00\x04\x00 this is not an image");
        $this->post('/ai/messages', ['text' => 'x', 'files' => [$exe]], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_a_failed_turn_also_drops_its_files(): void
    {
        $this->script[] = new ChatUnavailable('down');

        $this->post('/ai/messages', ['text' => 'ده اللوجو', 'files' => [$this->png()]], ['Accept' => 'application/json'])->assertStatus(503);

        $this->assertSame(0, \Modules\AppBuilder\Models\BuilderAttachment::withTrashed()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_worker_gets_the_customers_files_and_only_its_own(): void
    {
        $this->answer('تمام', 20000);
        $uuid = $this->post('/ai/messages', ['text' => 'ده اللوجو', 'files' => [$this->png()]], ['Accept' => 'application/json'])->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $project->update(['status' => BuilderProject::QUEUED, 'paid_at' => now()]);

        $job = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job');
        $this->assertSame('logo.png', $job['attachments'][0]['name']);
        $this->assertSame('image/png', $job['attachments'][0]['mime']);
        $this->get('/api/builder/worker/jobs/'.$uuid.'/attachments/'.$job['attachments'][0]['id'], $this->worker())->assertOk();

        $other = $this->quotedProject();
        $this->get('/api/builder/worker/jobs/'.$other->uuid.'/attachments/'.$job['attachments'][0]['id'], $this->worker())->assertNotFound();
    }

    private function referrer(): \App\Models\User
    {
        $currency = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $user = \App\Models\User::factory()->create(['currency_id' => $currency->id]);
        \App\Models\UserReferral::create(['user_id' => $user->id, 'title' => 'Default', 'key' => 'abc123', 'slug' => 'ahmed']);

        return $user;
    }

    public function test_a_referred_customer_pays_the_share_and_the_referrer_earns_it_after_clearing(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $referrer = $this->referrer();
        $this->answer('تمام، ده السعر', 50000);

        $uuid = $this->withSession(['referral' => 'ahmed'])->postJson('/ai/messages', ['text' => 'عايز منيو'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // normal price $6, plus 30% = $7.80, rounded up to $8; the extra $2 is the referrer's
        $this->assertSame($referrer->id, $project->referrer_user_id);
        $this->assertSame('8.00', $project->amount);
        $this->assertSame('2.00', $project->referral_commission);

        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-1_abc123');
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();

        $earning = \App\Models\Earning::where('user_id', $referrer->id)->firstOrFail();
        $this->assertEquals(2.0, (float) $earning->amount);
        $this->assertNull($earning->transaction_id);
        $this->assertTrue(\Illuminate\Support\Carbon::parse($earning->convert_to_balance_on)->between(now()->addDays(13), now()->addDays(15)));
    }

    public function test_without_a_referral_or_from_your_own_link_there_is_no_share(): void
    {
        $referrer = $this->referrer();
        $this->answer('تمام، ده السعر', 50000);
        $this->answer('تمام، ده السعر', 50000);

        $plain = $this->postJson('/ai/messages', ['text' => 'عايز منيو'])->json('uuid');
        $own = $this->actingAs($referrer)->withSession(['referral' => 'ahmed'])->postJson('/ai/messages', ['text' => 'عايز منيو'])->json('uuid');

        foreach ([$plain, $own] as $uuid) {
            $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
            $this->assertNull($project->referrer_user_id);
            $this->assertSame('6.00', $project->amount);
        }
    }

    public function test_a_paid_design_is_a_fixed_fee_and_the_build_waits_for_the_figma_link(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'موقع الشركة', 'spec' => 'Company site.', 'estimated_output_tokens' => 50000, 'kind' => 'build', 'complex' => false, 'design_fee' => 120],
        ]);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز موقع تعريفي بتصميم احترافي'])->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // programming $6 + design at the $200 minimum (a design fee is never multiplied)
        $this->assertSame('206.00', $project->amount);
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('quote.design_fee', '200.00');

        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-1_abc123');
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();
        $this->assertSame(BuilderProject::DESIGNING, $project->refresh()->status);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->post('/admin/builder/'.$uuid.'/design', ['design_url' => 'https://www.figma.com/design/abc/Company'])->assertRedirect();

        $this->assertSame(BuilderProject::QUEUED, $project->refresh()->status);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.design_url', 'https://www.figma.com/design/abc/Company');
    }

    public function test_each_project_type_has_a_minimum_price_and_the_budget_follows_the_price(): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'منصة أفلييت', 'spec' => 'Affiliate system.', 'estimated_output_tokens' => 120000, 'kind' => 'build', 'complex' => true, 'design_fee' => 0, 'project_type' => 'system'],
        ]);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز سيستم افلييت'])->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // 120k tokens on Opus is $29 by markup, but a full system costs at least $150
        $this->assertSame('150.00', $project->amount);
        // the build may spend 2/3 of the price, and the worker stops at 1.5x that: the price itself
        $this->assertSame('100.0000', $project->budget_usd);
    }

    public function test_a_deposit_starts_the_build_and_the_files_open_when_the_rest_is_paid(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
        $project = $this->quotedProject();
        $client = ['client_name' => 'أحمد', 'client_email' => 'a@example.com', 'client_whatsapp' => '01012345678'];

        $this->postJson('/ai/'.$project->uuid.'/pay', $client + ['share' => 50])->assertOk();
        $this->assertSame('3.00', $project->refresh()->payment_due);
        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-1_abc123');
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertSame('3.00', $project->paid_total);
        $project->update(['status' => BuilderProject::BUILDING]);
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1], $this->worker())->assertOk();

        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('balance.due', '3.00')->assertJsonPath('delivery.download_url', null);
        $this->get('/ai/'.$project->uuid.'/download')->assertStatus(402);
        $this->get('/ai/'.$project->uuid.'/app/index.html')->assertOk();
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'عايز تعديل'])->assertStatus(409);

        $this->postJson('/ai/'.$project->uuid.'/pay', $client)->assertOk();
        $this->assertSame('3.00', $project->refresh()->payment_due);
        [$rest, $restHeaders] = $this->signedWebhook('bld_'.$project->id.'-1_def456', 'TX-2');
        $this->postJson('/ai/webhook', $rest, $restHeaders)->assertOk();

        $this->assertSame('6.00', $project->refresh()->paid_total);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('balance', null);
        $this->get('/ai/'.$project->uuid.'/download')->assertOk();
    }

    public function test_a_backup_worker_takes_jobs_only_while_the_main_one_is_silent(): void
    {
        $project = $this->quotedProject();
        $project->update(['status' => BuilderProject::QUEUED, 'paid_at' => now()]);
        $backup = $this->worker() + ['X-Worker-Role' => 'backup'];

        // the main worker was just heard from (it asked for trials): the backup stays idle
        $this->getJson('/api/builder/worker/trials', $this->worker())->assertOk();
        $this->postJson('/api/builder/worker/claim', [], $backup)->assertNoContent();

        $this->travel(6)->minutes();
        $this->postJson('/api/builder/worker/claim', [], $backup)->assertOk()->assertJsonPath('job.uuid', $project->uuid);
    }

    public function test_the_engineers_ideas_come_with_the_delivery_cleaned_and_at_most_four(): void
    {
        $project = $this->buildingProject();
        $ideas = json_encode([
            ['title' => 'حجز أونلاين', 'description' => 'الزبون يحجز من الموقع.'],
            ['title' => '<b>زرار واتساب</b>', 'description' => 'طلب سريع.'],
            ['title' => 'تقارير'], ['title' => 'برنامج ولاء'], ['title' => 'خامسة زيادة'],
        ], JSON_UNESCAPED_UNICODE);

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1, 'ideas' => $ideas], $this->worker())->assertOk();

        $state = $this->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        $last = collect($state->json('messages'))->last();
        $this->assertCount(4, $last['ideas']);
        $this->assertSame('حجز أونلاين', $last['ideas'][0]['title']);
        $this->assertSame('زرار واتساب', $last['ideas'][1]['title']);
    }
}
