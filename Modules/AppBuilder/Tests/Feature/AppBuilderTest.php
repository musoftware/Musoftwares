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

            public string $lastSystem = '';

            public function __construct(private array &$script) {}

            public function reply(string $system, array $turns): ChatTurn
            {
                $this->seen[] = $turns;
                $this->lastSystem = $system;
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

    public function test_an_admin_can_start_as_many_chats_as_they_need(): void
    {
        config(['builder.limits.projects_per_ip_per_day' => 1]);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        foreach (['أول', 'تاني', 'تالت'] as $text) {
            $this->answer('أهلاً');
            $this->postJson('/ai/messages', ['text' => $text])->assertOk();
        }
    }

    private function geminiAnswers(string $reply): void
    {
        $json = json_encode(['reply' => $reply, 'needs_team' => false, 'secrets' => [], 'quote' => ['ready' => false, 'title' => '', 'spec' => '', 'estimated_output_tokens' => 0, 'kind' => 'build', 'complex' => false, 'design_fee' => 0, 'project_type' => 'other']], JSON_UNESCAPED_UNICODE);
        \Illuminate\Support\Facades\Http::fake(['generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['candidates' => [['content' => ['parts' => [['text' => $json]]]]]])]);
    }

    public function test_a_guest_before_a_price_talks_with_the_free_model_and_costs_nothing(): void
    {
        config(['builder.free_chat.key' => 'test-key']);
        $this->geminiAnswers('أهلاً، احكيلي عن شغلك');

        $uuid = $this->postJson('/ai/messages', ['text' => 'إزيك'])->assertOk()->json('uuid');

        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $this->assertSame('أهلاً، احكيلي عن شغلك', $project->messages()->reorder('id', 'desc')->value('content'));
        $this->assertSame(0.0, $project->chatCostUsd());
        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
            && $request['generationConfig']['responseMimeType'] === 'application/json');
    }

    public function test_when_the_free_model_fails_claude_answers_and_signed_in_customers_always_get_claude(): void
    {
        config(['builder.free_chat.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['error' => 'quota'], 429)]);
        $this->answer('Claude هنا');
        $uuid = $this->postJson('/ai/messages', ['text' => 'إزيك'])->assertOk()->json('uuid');
        $this->assertSame('Claude هنا', BuilderProject::where('uuid', $uuid)->firstOrFail()->messages()->reorder('id', 'desc')->value('content'));

        $this->geminiAnswers('مجاني');
        $this->actingAs(\App\Models\User::factory()->create(['onboarding_completed' => true]));
        $this->answer('Claude للعميل المسجّل');
        $signed = $this->postJson('/ai/messages', ['text' => 'إزيك'])->assertOk()->json('uuid');
        $this->assertSame('Claude للعميل المسجّل', BuilderProject::where('uuid', $signed)->firstOrFail()->messages()->reorder('id', 'desc')->value('content'));
    }

    public function test_a_guest_who_talks_long_without_a_price_is_asked_to_sign_in(): void
    {
        config(['builder.limits.guest_messages_before_sign_in' => 2]);
        $this->answer('تمام');
        $this->answer('تمام');
        $uuid = $this->postJson('/ai/messages', ['text' => 'سؤال'])->assertOk()->json('uuid');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'سؤال تاني'])->assertOk();

        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'سؤال تالت'])->assertForbidden()->assertJsonPath('message', __('builder.errors.sign_in'));
    }

    /** A paid quote, built and delivered by the worker at this real cost. */
    private function deliverPaid(float $costUsd, ?\App\Models\User $customer = null): BuilderProject
    {
        $this->usdAndEgpRates();
        if ($customer) $this->actingAs($customer);
        $project = $this->quotedProject();
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($project, $project->quote_version, 'TX-SETTLE');
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => $costUsd], $this->worker())->assertOk();

        return $project->refresh();
    }

    public function test_a_signed_in_customer_gets_back_what_the_work_did_not_cost(): void
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
        $paid = (float) $this->quotedProjectPrice();
        $project = $this->deliverPaid(1.00, $customer);

        // the real cost plus 20%, and the rest back as balance for the next program
        $this->assertSame(1.20, (float) $project->amount);
        $this->assertEqualsWithDelta($paid - 1.20, $this->balanceInUsd($customer), 0.05);
        $this->assertSame(BuilderMessage::TEAM, $project->messages()->reorder('id', 'desc')->value('role'));
        $this->assertSame($project->quote_version, $project->price_settled_version);
    }

    public function test_a_guest_keeps_the_price_they_paid_and_a_cost_above_the_price_changes_nothing(): void
    {
        $guest = $this->deliverPaid(1.00);
        $this->assertSame((float) $guest->quote_paid, (float) $guest->amount);

        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
        $paid = (float) $this->quotedProjectPrice();
        $costly = $this->deliverPaid(500.00, $customer);
        $this->assertSame($paid, (float) $costly->amount);
        $this->assertEqualsWithDelta(0, $this->balanceInUsd($customer), 0.01);
    }

    public function test_a_referred_customer_pays_the_cost_plus_half(): void
    {
        $referrer = $this->referrer();
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
        $this->withSession(['referral' => 'ahmed']);
        $project = $this->deliverPaid(4.00, $customer);

        $this->assertSame($referrer->id, $project->referrer_user_id);
        // cost plus 50%, but never less than the cost plus the referrer's share
        $this->assertSame(max(6.00, 4.00 + (float) $project->referral_commission), (float) $project->amount);
    }

    private function usdAndEgpRates(): void
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::firstOrCreate(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id], ['rate' => 50]);
        CurrenciesExchange::firstOrCreate(['date_string' => now()->toDateString(), 'currency1' => $egp->id, 'currency2' => $usd->id], ['rate' => 0.02]);
    }

    /** A balance in the customer's own currency, read back in dollars. */
    private function balanceInUsd(\App\Models\User $customer): float
    {
        return (float) CurrenciesExchange::RateToday((float) $customer->refresh()->balance(), $customer->currency_id, Currency::resolve('USD')->id);
    }

    /** What quotedProject() charges, from the same scripted answer. */
    private function quotedProjectPrice(): string
    {
        $probe = $this->quotedProject();
        $price = $probe->amount;
        $probe->delete();

        return $price;
    }

    /** Every mail the array mailer has sent so far, as subject => recipient. */
    private function sentMails(): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->mapWithKeys(fn ($sent) => [$sent->getOriginalMessage()->getSubject() => $sent->getEnvelope()->getRecipients()[0]->getAddress()])
            ->all();
    }

    public function test_a_guest_who_pays_gets_an_account_and_the_project_link_by_email(): void
    {
        config(['mail.default' => 'array']);
        $project = $this->quotedProject();
        $project->update(['client_name' => 'منى', 'client_email' => 'Mona@Example.com', 'client_whatsapp' => '01012345678']);
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($project, $project->quote_version, 'TX-ACCOUNT');

        $user = \App\Models\User::where('email', 'mona@example.com')->firstOrFail();
        $this->assertSame($user->id, $project->refresh()->user_id);
        $this->assertSame('mona@example.com', strtolower($this->sentMails()[__('builder.mail_paid_subject', [], 'ar')] ?? ''));

        // an existing account is used, never a second one
        $again = $this->quotedProject();
        $again->update(['client_email' => 'mona@example.com']);
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($again, $again->quote_version, 'TX-ACCOUNT-2');
        $this->assertSame($user->id, $again->refresh()->user_id);
        $this->assertSame(1, \App\Models\User::where('email', 'mona@example.com')->count());

        // and the delivery is announced too
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1000], $this->worker())->assertOk();
        $this->assertArrayHasKey(__('builder.mail_ready_subject', [], 'ar'), $this->sentMails());
    }

    public function test_a_quiet_customer_gets_a_reminder_then_the_discount_by_email_once_each(): void
    {
        config(['mail.default' => 'array']);
        $quiet = $this->quotedProject();
        $quiet->update(['client_email' => 'quiet@example.com']);
        $paid = $this->quotedProject();
        $paid->update(['client_email' => 'paid@example.com']);
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($paid, $paid->quote_version, 'TX-PAID');
        $price = (float) $quiet->amount;
        $followUp = app(\Modules\AppBuilder\Services\ChatFollowUp::class);

        // a day later nothing goes out; after two days the reminder, only once
        $this->travel(1)->days();
        $this->assertSame(0, $followUp->sendDue());
        $this->travel(2)->days();
        $this->assertSame(1, $followUp->sendDue());
        $this->assertSame(0, $followUp->sendDue());
        $this->assertNotNull($quiet->refresh()->follow_up_at);

        // after a week the discount is taken off the price and announced
        $this->travel(5)->days();
        $this->assertSame(1, $followUp->sendDue());
        $quiet->refresh();
        $this->assertLessThan($price, (float) $quiet->amount);
        $this->assertNotNull($quiet->follow_up_offer_at);
        $this->assertSame(0, $followUp->sendDue());

        // the paid customer was never mailed by the follow-up
        $this->assertNull($paid->refresh()->follow_up_at);
    }

    private function quotePage(): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'صفحة منيو', 'spec' => 'One menu page.', 'estimated_output_tokens' => 25000, 'kind' => 'build', 'complex' => false, 'design_fee' => 0, 'project_type' => 'page'],
        ]);
    }

    public function test_a_signed_in_customers_first_simple_page_is_free_once_with_a_capped_budget(): void
    {
        // a guest sees the offer as a reason to sign in, not as a button
        $this->quotePage();
        $guestChat = $this->postJson('/ai/messages', ['text' => 'صفحة منيو'])->assertOk()->json('uuid');
        $this->assertNotNull($this->getJson('/ai/'.$guestChat.'/state')->json('quote.free_build.sign_in_url'));
        $this->postJson('/ai/'.$guestChat.'/free')->assertUnauthorized();

        $this->actingAs(\App\Models\User::factory()->create(['onboarding_completed' => true]));
        $this->quotePage();
        $uuid = $this->postJson('/ai/messages', ['text' => 'صفحة منيو'])->assertOk()->json('uuid');
        $url = $this->getJson('/ai/'.$uuid.'/state')->json('quote.free_build.url');
        $this->postJson($url)->assertOk()->assertJsonPath('status', BuilderProject::QUEUED);

        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $this->assertTrue($project->free_build);
        $this->assertSame(0.0, (float) $project->paid_total);
        $this->assertLessThanOrEqual(1.0, (float) $project->budget_usd);

        // one per account: the next page is paid
        $this->quotePage();
        $next = $this->postJson('/ai/messages', ['text' => 'صفحة تانية'])->assertOk()->json('uuid');
        $this->assertNull($this->getJson('/ai/'.$next.'/state')->json('quote.free_build'));
        $this->postJson('/ai/'.$next.'/free')->assertStatus(409);
    }

    public function test_a_bigger_project_is_never_free(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['onboarding_completed' => true]));
        $project = $this->quotedProject();
        $this->assertNull($this->getJson('/ai/'.$project->uuid.'/state')->json('quote.free_build'));
    }

    public function test_a_signed_in_customer_pays_from_the_wallet_only_when_it_covers_the_price(): void
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true, 'currency_id' => $egp->id]);
        $this->actingAs($customer);
        $project = $this->quotedProject();
        $priceInEgp = (float) $project->amount * 50;

        // a guest sees no wallet; a customer whose balance is short sees the top-up link, not the button
        $this->assertNotNull($this->getJson('/ai/'.$project->uuid.'/state')->json('wallet'));
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('wallet.enough', false);
        $this->postJson('/ai/'.$project->uuid.'/pay/wallet')->assertStatus(409);
        $this->assertSame(BuilderProject::QUOTED, $project->refresh()->status);

        $customer->add_balance($priceInEgp + 100, 'test top-up', 'received');
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('wallet.enough', true);
        $this->postJson('/ai/'.$project->uuid.'/pay/wallet')->assertOk()->assertJsonPath('status', BuilderProject::QUEUED);

        $project->refresh();
        $this->assertSame((float) $project->amount, (float) $project->paid_total);
        $this->assertSame($customer->id, $project->user_id);
        $this->assertEqualsWithDelta(100, (float) $customer->refresh()->balance(), 0.01);
        $this->assertSame($project->uuid, $this->postJson('/api/builder/worker/claim', [], $this->worker())->json('job.uuid'));
    }

    public function test_a_guest_cannot_pay_from_a_wallet(): void
    {
        $project = $this->quotedProject();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('wallet', null);
        $this->postJson('/ai/'.$project->uuid.'/pay/wallet')->assertUnauthorized();
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
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('progress', __('builder.steps.writing'));

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

    public function test_the_build_shows_the_customer_screenshots_of_finished_parts(): void
    {
        config(['builder.limits.snapshots_per_build' => 2]);
        $project = $this->buildingProject();
        $url = '/api/builder/worker/jobs/'.$project->uuid.'/snapshot';

        // a line with no picture only moves the bar; nothing is added to the chat
        $this->post($url, ['percent' => 10, 'caption' => 'قاعدة البيانات جاهزة'], $this->worker())->assertCreated();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('progress_percent', 10)->assertJsonPath('progress', 'قاعدة البيانات جاهزة');

        $this->post($url, ['percent' => 35, 'image' => $this->png(), 'caption' => 'لوحة الأدمن خلصت'], $this->worker())->assertCreated();
        $this->post($url, ['percent' => 60, 'image' => $this->png(), 'caption' => 'صفحة المسوق خلصت'], $this->worker())->assertCreated();
        // pictures are limited per build, and the bar never goes backwards or shows 100 before delivery
        $this->post($url, ['percent' => 40, 'image' => $this->png(), 'caption' => 'كمان واحدة'], $this->worker())->assertStatus(429);
        $this->assertSame(60, $project->refresh()->progress_percent);
        $this->post($url, ['percent' => 100, 'caption' => 'خلاص تقريباً'], $this->worker())->assertCreated();
        $this->assertSame(99, $project->refresh()->progress_percent);

        $line = collect($this->getJson('/ai/'.$project->uuid.'/state')->json('messages'))->firstWhere('role', 'progress');
        $this->assertSame('لوحة الأدمن خلصت', $line['text']);
        $this->assertSame([], $line['files']);
        $this->get($line['images'][0])->assertOk()->assertHeader('Content-Type', 'image/png');

        // the picture is for the customer; the chat model only reads the line under it
        $message = BuilderMessage::where('role', BuilderMessage::PROGRESS)->firstOrFail();
        $this->assertIsString(app(\Modules\AppBuilder\Services\AttachmentStore::class)->contentFor($message->load('attachments')));

        // another project's link cannot open this picture
        $other = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD']);
        $this->get('/ai/'.$other->uuid.'/files/'.$message->attachments->first()->id)->assertNotFound();
    }

    public function test_a_signed_in_customer_finds_their_projects_on_the_start_page(): void
    {
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true]);

        $this->answer('تمام، احكيلي أكتر');
        $uuid = $this->actingAs($customer)->postJson('/ai/messages', ['text' => 'عايز سيستم'])->assertOk()->json('uuid');
        $this->assertSame($customer->id, BuilderProject::where('uuid', $uuid)->value('user_id'));

        // a chat started as a guest joins the account when its link is opened signed in
        $guest = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'متجر قديم']);
        $this->actingAs($customer)->get('/ai/'.$guest->uuid)->assertOk();
        $this->assertSame($customer->id, $guest->refresh()->user_id);

        $this->actingAs($customer)->get('/ai')->assertOk()
            ->assertSee(__('builder.my_projects'))->assertSee('متجر قديم')->assertSee('/ai/'.$uuid, false)
            ->assertSee('data-size="big"', false)->assertDontSee('id="login-ask"', false);
    }

    public function test_a_guest_choosing_a_big_project_is_asked_to_sign_in_first(): void
    {
        $page = $this->get('/ai')->assertOk();

        $page->assertSee(__('builder.start_big'))->assertSee(__('builder.start_small'))->assertSee(__('builder.start_login'));
        // sign-in brings them back to the chat, past the choice
        $page->assertSee(e(route('login', ['redirect' => '/ai?size=big'])), false);
        $page->assertSee('id="login-ask"', false)->assertDontSee('class="projects"', false);
    }

    public function test_the_chat_speaks_the_customers_language_and_never_sends_them_to_the_team(): void
    {
        app()->setLocale('en');
        $this->answer('تمام، المتجر هيبيع إيه؟', 0, needsTeam: true);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز متجر وتطبيق اندرويد'])->assertOk()->json('uuid');

        // the customer writes Arabic, so the page answers in Arabic even in an English browser
        $this->get('/ai/'.$uuid)->assertOk()->assertSee('dir="rtl"', false)->assertSee(__('builder.placeholder', [], 'ar'));

        // the customer serves themselves: nothing in the chat points to the team
        $state = $this->getJson('/ai/'.$uuid.'/state')->assertOk();
        $this->assertArrayNotHasKey('team_url', $state->json());
        $this->assertArrayNotHasKey('needs_team', $state->json('messages.1'));
    }

    public function test_the_owner_sees_the_price_the_payment_and_what_the_chat_and_build_cost(): void
    {
        $project = $this->quotedProject();
        $project->update(['paid_total' => 20, 'build_cost_usd' => 3.5]);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        // one chat turn: 1200 tokens in x $0.10 + 300 out x $0.50 per million = $0.00027
        $this->actingAs($admin)->get('/admin/builder/'.$project->uuid)->assertOk()->assertInertia(fn ($page) => $page
            ->where('project.amount', $project->amount)
            ->where('project.paid_total', '20.00')
            ->where('project.build_cost_usd', '3.5000')
            ->where('project.chat_cost_usd', 0.0003));
        $this->actingAs($admin)->get('/admin/builder')->assertOk()->assertInertia(fn ($page) => $page->where('projects.data.0.chat_cost_usd', 0.0003));
    }

    public function test_a_windows_program_gets_run_install_how_to_and_source_buttons(): void
    {
        $project = $this->buildingProject();
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', [
            'archive' => $this->zip(['index.html' => '<h1>How to install</h1>', 'SETUP.md' => '# setup', 'release/app-portable.exe' => "MZ portable", 'release/app-setup.exe' => "MZ setup"]),
            'summary' => 'برنامجك جاهز',
            'cost_usd' => 2,
        ], $this->worker())->assertOk();

        $actions = $this->getJson('/ai/'.$project->uuid.'/state')->json('delivery.actions');
        $this->assertSame([__('builder.run_exe'), __('builder.installer'), __('builder.how_to_install'), __('builder.source_code')], array_column($actions, 'label'));
        $this->assertSame([true, false, false, false], array_column($actions, 'main'));

        // the program is saved as a file, never opened in the browser
        $this->get($actions[0]['url'])->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="app-portable.exe"');

        // after a deposit only the how-to page stays; the program, the installer and the source wait for the rest
        $project->update(['quote_paid' => $project->amount / 2]);
        $this->assertSame([__('builder.how_to_install')], array_column($this->getJson('/ai/'.$project->uuid.'/state')->json('delivery.actions'), 'label'));
        $this->get('/ai/'.$project->uuid.'/app/release/app-setup.exe')->assertStatus(402);
    }

    public function test_an_android_app_is_delivered_with_an_install_button_and_a_working_preview(): void
    {
        $project = $this->buildingProject();
        $apk = "PK\x03\x04 fake apk bytes";
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', [
            'archive' => $this->zip(['SETUP.md' => '# setup', 'release/app-release.apk' => $apk, 'release/upload-keystore.jks' => 'key', 'preview/index.html' => '<h1>app</h1>', 'preview/main.dart.wasm' => 'wasm']),
            'summary' => 'تطبيقك جاهز',
            'cost_usd' => 2,
        ], $this->worker())->assertOk();

        $delivery = $this->getJson('/ai/'.$project->uuid.'/state')->json('delivery');
        $this->assertStringEndsWith('/app/release/app-release.apk', $delivery['apk_url']);
        $this->assertStringEndsWith('/app/preview/index.html', $delivery['preview_url']);
        $this->get($delivery['apk_url'])->assertOk()->assertHeader('Content-Type', 'application/vnd.android.package-archive');

        // the app's web build may load its own files, still inside the sandbox and without cookies
        $preview = $this->get('/ai/'.$project->uuid.'/app/preview/main.dart.wasm')->assertOk()->assertHeader('Content-Type', 'application/wasm')->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertStringContainsString("connect-src 'self'", $preview->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('sandbox', $preview->headers->get('Content-Security-Policy'));
        // other delivered pages keep the closed policy
        $this->assertStringContainsString("connect-src 'none'", $this->get('/ai/'.$project->uuid.'/app/SETUP.md')->headers->get('Content-Security-Policy'));

        // after a deposit the app and its key wait for the rest of the payment, while the preview stays open
        $project->update(['quote_paid' => $project->amount / 2]);
        $this->assertNull($this->getJson('/ai/'.$project->uuid.'/state')->json('delivery.apk_url'));
        $this->get('/ai/'.$project->uuid.'/app/release/app-release.apk')->assertStatus(402);
        $this->get('/ai/'.$project->uuid.'/app/release/upload-keystore.jks')->assertStatus(402);
        $this->get('/ai/'.$project->uuid.'/app/preview/index.html')->assertOk();
    }

    public function test_a_full_android_app_is_priced_at_least_at_its_minimum(): void
    {
        // the feasibility study has its own test; here only the price matters
        config(['builder.study.for_types' => []]);
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام، ده السعر',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'تطبيق توصيل', 'spec' => 'Kind: Android app (full).', 'estimated_output_tokens' => 1000, 'kind' => 'build', 'complex' => true, 'design_fee' => 0, 'project_type' => 'app_full'],
        ], 1200, 300);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز تطبيق توصيل'])->assertOk()->json('uuid');

        $this->assertSame('200.00', BuilderProject::where('uuid', $uuid)->value('amount'));
    }

    private function pastBuilds(string $type, array $factors): void
    {
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD']);
        foreach ($factors as $factor) {
            \Modules\AppBuilder\Models\BuilderBuildCost::create(['project_id' => $project->id, 'project_type' => $type, 'model' => 'claude-opus-5-5', 'estimated_tokens' => 100000, 'cost_usd' => $factor * 2, 'work_factor' => $factor]);
        }
    }

    public function test_prices_learn_the_real_cost_of_each_kind_of_project(): void
    {
        $history = app(\Modules\AppBuilder\Services\BuildCostHistory::class);

        // too few builds of a kind: the configured factor
        $this->pastBuilds('system', [9, 9, 9, 9]);
        $this->assertSame(4.0, $history->workFactor('system'));

        // enough builds: their median, so one very cheap build does not pull the price down
        $this->pastBuilds('system', [1]);
        $this->assertSame(9.0, $history->workFactor('system'));
        $this->assertSame(4.0, $history->workFactor('page'));

        // a broken record cannot make prices absurd
        $this->pastBuilds('web_app', [500, 500, 500, 500, 500]);
        $this->assertSame(20.0, $history->workFactor('web_app'));

        // the price uses it: 100k tokens x $20 per million (strong model) x 9 = $18 of real cost
        $price = app(\Modules\AppBuilder\Services\QuotePricer::class)->price(100000, true, 0, 'system');
        $this->assertSame(18.0, $price['cost']);
    }

    public function test_a_delivered_build_records_its_whole_cost_including_failed_tries(): void
    {
        $project = $this->buildingProject();
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'stopped', 'cost_usd' => 3], $this->worker())->assertOk();
        $project->update(['status' => BuilderProject::QUEUED, 'attempts' => 0]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>منيو</h1>']), 'summary' => 'جاهز', 'cost_usd' => 2], $this->worker())->assertOk();

        $record = \Modules\AppBuilder\Models\BuilderBuildCost::where('project_id', $project->id)->sole();
        $this->assertSame('5.0000', $record->cost_usd);
        $this->assertSame(0.0, (float) $project->refresh()->job_cost_usd);
        // 5 USD over 50k estimated tokens at the standard model's $10 per million: a real work factor of 10
        $this->assertSame('10.000', $record->work_factor);
    }

    public function test_a_shared_link_is_opened_in_the_workers_browser_before_the_chat_answers(): void
    {
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk()->assertJsonPath('looks', []);

        // the worker is there, so the chat waits for it instead of answering blind
        $uuid = $this->postJson('/ai/messages', ['text' => 'تعرف تعمل زي الموقع ده https://quran.example.com/garden ؟'])->assertOk()->assertJsonPath('looking', 'link')->json('uuid');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'وكمان'])->assertStatus(409);

        $looks = $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk()->json('looks');
        $this->assertSame('https://quran.example.com/garden', $looks[0]['url']);
        // handed out once
        $this->assertSame([], $this->getJson('/api/builder/worker/looks', $this->worker())->json('looks'));

        $this->answer('شوفت الموقع: حديقة بتعرض السور بشكل شجر، وفيها تقدم للطفل.');
        $this->post('/api/builder/worker/looks/'.$looks[0]['message_id'], [
            'index' => 0, 'title' => 'حديقة القرآن', 'description' => 'تعلم القرآن للأطفال', 'text' => 'سورة الفاتحة سورة الناس',
            'full_page' => $this->png(), 'pieces' => [$this->png(), $this->png()],
        ], $this->worker())->assertOk();

        $state = $this->getJson('/ai/'.$uuid.'/state')->assertOk()->assertJsonPath('looking', null)->assertJsonPath('answering', false);
        $customer = $state->json('messages.0');
        $this->assertCount(1, $customer['images']);
        $this->assertSame([], $customer['files']);
        $this->assertSame('شوفت الموقع: حديقة بتعرض السور بشكل شجر، وفيها تقدم للطفل.', $state->json('messages.1.text'));

        // Claude got the two sharp pieces (not the full page) and the page text
        $blocks = app(ChatModel::class)->seen[0][0]['content'];
        $this->assertCount(2, collect($blocks)->where('type', 'image'));
        $this->assertStringContainsString('سورة الفاتحة', collect($blocks)->where('type', 'text')->first()['text']);
        $this->assertStringContainsString('real browser', collect($blocks)->where('type', 'text')->first()['text']);
    }

    public function test_the_page_keeps_waiting_while_the_answer_after_a_look_is_written(): void
    {
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();
        $uuid = $this->postJson('/ai/messages', ['text' => 'زي ده https://quran.example.com/garden'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // the worker's request has taken the turn (the look is done) and Claude is still writing
        $project->update(['awaiting_look' => false]);
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('looking', null)->assertJsonPath('answering', true);

        // an answer that never came stops the waiting after a few minutes
        $this->travel(4)->minutes();
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('answering', false);
    }

    public function test_a_customer_leaving_the_price_gets_a_discount_once_and_the_build_budget_follows(): void
    {
        $project = $this->quotedProject();
        $project->update(['amount' => 100, 'design_fee' => 20, 'budget_usd' => 40]);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('quote.leave_offer', 10)->assertJsonPath('quote.discount', null);

        $state = $this->postJson('/ai/'.$project->uuid.'/discount')->assertOk();

        // 10% of the programming part (100 - 20 for the designer) comes off; the designer's fee stays whole
        $project->refresh();
        $this->assertSame('92.00', $project->amount);
        $this->assertSame('8.00', $project->leave_discount_usd);
        $this->assertSame('36.0000', $project->budget_usd);
        $state->assertJsonPath('quote.amount', '92.00')->assertJsonPath('quote.discount.before', '100.00')->assertJsonPath('quote.leave_offer', null);

        // once only
        $this->postJson('/ai/'.$project->uuid.'/discount')->assertStatus(409);
        $this->assertSame('92.00', $project->refresh()->amount);
    }

    public function test_there_is_no_leave_discount_before_a_price_or_once_payment_started(): void
    {
        $this->answer('أهلاً، قولي أكتر.');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز برنامج'])->assertOk()->json('uuid');
        $this->postJson('/ai/'.$uuid.'/discount')->assertStatus(409);

        $project = $this->quotedProject();
        $project->update(['payment_due' => 25]);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('quote.leave_offer', null);
        $this->postJson('/ai/'.$project->uuid.'/discount')->assertStatus(409);
    }

    public function test_a_new_price_for_the_same_project_keeps_the_discount_taken(): void
    {
        $project = $this->quotedProject();
        $before = (float) $project->amount;
        $this->postJson('/ai/'.$project->uuid.'/discount')->assertOk();

        // the customer changes the scope and gets a new price of the same size
        $this->answer('تمام، ده السعر الجديد', 50000);
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'زود صفحة تانية'])->assertOk();

        $this->assertEqualsWithDelta($before * 0.9, (float) $project->refresh()->amount, 0.01);
    }

    public function test_notifications_are_asked_for_from_the_first_message(): void
    {
        config(['services.fcm.web' => ['api_key' => 'key', 'project_id' => 'p', 'messaging_sender_id' => '1', 'app_id' => 'app', 'vapid_key' => 'vapid']]);
        $this->answer('أهلاً، قولي أكتر.');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز برنامج'])->assertOk()->json('uuid');

        // not only after payment: a reply, a discount or a problem must reach them too
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('push', route('builder.push', $uuid));
    }

    public function test_a_customer_who_closed_the_page_on_a_price_gets_the_discount_as_a_notification_once(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendFcmNotificationJob::class]);
        $leaving = app(\Modules\AppBuilder\Services\LeaveDiscount::class);
        $project = $this->quotedProject();
        $project->update(['push_token' => 'device-token']);

        $this->post('/ai/'.$project->uuid.'/left')->assertNoContent();
        $this->assertNotNull($project->refresh()->left_price_at);

        // not before half an hour
        $this->assertSame(0, $leaving->sendReminders());
        $this->travel(31)->minutes();
        $this->assertSame(1, $leaving->sendReminders());
        $this->assertSame(0, $leaving->sendReminders());
        \Illuminate\Support\Facades\Bus::assertDispatchedTimes(\App\Jobs\SendFcmNotificationJob::class, 1);

        // back on the page from the notification, the offer shows at once
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('quote.offer_now', true)->assertJsonPath('quote.leave_offer', 10);
    }

    public function test_no_discount_reminder_after_the_discount_was_taken_or_payment_started(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendFcmNotificationJob::class]);
        $taken = $this->quotedProject();
        $taken->update(['push_token' => 'a', 'left_price_at' => now()->subHour()]);
        $this->postJson('/ai/'.$taken->uuid.'/discount')->assertOk();
        $paying = $this->quotedProject();
        $paying->update(['push_token' => 'b', 'left_price_at' => now()->subHour(), 'payment_due' => 10]);

        $this->assertSame(0, app(\Modules\AppBuilder\Services\LeaveDiscount::class)->sendReminders());
        \Illuminate\Support\Facades\Bus::assertNotDispatched(\App\Jobs\SendFcmNotificationJob::class);
    }

    public function test_a_failed_build_tells_the_customer(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendFcmNotificationJob::class]);
        $project = $this->buildingProject();
        $project->update(['push_token' => 'device-token']);

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'tests failed', 'cost_usd' => 1], $this->worker())->assertOk();

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendFcmNotificationJob::class);
    }

    public function test_the_page_polling_does_not_use_up_the_limit_for_sending_or_starting(): void
    {
        $this->answer('أهلاً، قولي أكتر.');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز برنامج'])->assertOk()->json('uuid');

        // a minute of waiting on the page: more polls than the send and start limits allow
        foreach (range(1, 20) as $poll) {
            $this->getJson('/ai/'.$uuid.'/state')->assertOk();
        }

        $this->answer('تمام.');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'لمحل ملابس'])->assertOk();
        $this->answer('أهلاً.');
        $this->postJson('/ai/messages', ['text' => 'مشروع تاني'])->assertOk();
    }

    public function test_without_the_worker_the_site_reads_the_page_itself(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('<title>متجر ورد</title><meta name="description" content="ورد طبيعي"><script>x()</script><h1>أجمل باقات الورد</h1>')]);
        $this->answer('شوفت المتجر، بيبيع باقات ورد.');

        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز زي ده https://93.184.215.14/shop'])->assertOk()->assertJsonPath('looking', null)->json('uuid');

        $text = app(ChatModel::class)->seen[0][0]['content'];
        $this->assertStringContainsString('أجمل باقات الورد', $text);
        $this->assertStringContainsString('plain HTML', $text);
        $this->assertStringNotContainsString('x()', $text);
        $this->assertSame('شوفت المتجر، بيبيع باقات ورد.', $this->getJson('/ai/'.$uuid.'/state')->json('messages.1.text'));
    }

    public function test_a_private_address_is_never_opened(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        $this->answer('الرابط ده مش موقع عام.');

        $this->postJson('/ai/messages', ['text' => 'شوف ده http://192.168.1.1/admin'])->assertOk();

        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertStringContainsString('could not be opened', app(ChatModel::class)->seen[0][0]['content']);
    }

    public function test_the_chat_answers_anyway_when_the_browser_is_too_slow(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('<title>عيادة</title><p>احجز موعدك</p>')]);
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();
        $uuid = $this->postJson('/ai/messages', ['text' => 'زي ده https://93.184.215.14/clinic'])->assertJsonPath('looking', 'link')->json('uuid');
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();

        $this->answer('شوفت صفحة العيادة.');
        $this->travel(60)->seconds();
        $this->getJson('/ai/'.$uuid.'/state')->assertOk()->assertJsonPath('looking', null)->assertJsonPath('messages.1.text', 'شوفت صفحة العيادة.');
        $this->assertStringContainsString('احجز موعدك', app(ChatModel::class)->seen[0][0]['content']);

        // the late result changes nothing
        $message = BuilderMessage::where('role', BuilderMessage::USER)->firstOrFail();
        $this->post('/api/builder/worker/looks/'.$message->id, ['index' => 0, 'full_page' => $this->png()], $this->worker())->assertOk();
        $this->assertSame(2, BuilderMessage::count());
    }

    public function test_a_link_in_a_later_message_is_the_one_read_when_the_browser_is_slow(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('<title>عيادة</title><p>احجز موعدك</p>')]);
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();
        $this->answer('أهلاً، عندك مثال؟');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز موقع لعيادتي'])->assertOk()->json('uuid');

        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'زي ده https://93.184.215.14/clinic'])->assertJsonPath('looking', 'link');
        $this->answer('شوفت صفحة العيادة.');
        $this->travel(60)->seconds();

        // the second message's link is read and answered, not the first message
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('looking', null)->assertJsonPath('messages.3.text', 'شوفت صفحة العيادة.');
        $turns = app(ChatModel::class)->seen[1];
        $this->assertStringContainsString('احجز موعدك', json_encode(end($turns)['content'], JSON_UNESCAPED_UNICODE));
    }

    public function test_a_figma_link_gets_longer_before_the_chat_answers_without_it(): void
    {
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();
        $uuid = $this->postJson('/ai/messages', ['text' => 'التصميم ده https://www.figma.com/file/abc123?node-id=1-2'])->assertJsonPath('looking', 'link')->json('uuid');
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();

        // a normal page would be given up on by now; a Figma design is still being drawn
        $this->travel(60)->seconds();
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('looking', 'link');

        $this->answer('معلش مقدرتش أفتح التصميم، ابعتلي صور.');
        $this->travel(100)->seconds();
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('looking', null);
    }

    /** A quoted project whose design picture reached the chat as a smaller preview. */
    private function quotedWithPreview(): BuilderProject
    {
        $this->answer('تمام، ده السعر', 50000);
        $uuid = $this->post('/ai/messages', ['text' => 'ده التصميم', 'files' => [$this->png()], 'previews' => ['logo.png']], ['Accept' => 'application/json'])->assertOk()->json('uuid');

        return BuilderProject::where('uuid', $uuid)->firstOrFail();
    }

    public function test_a_picture_made_smaller_by_the_page_is_kept_as_a_preview(): void
    {
        $project = $this->quotedWithPreview();

        $this->assertSame(\Modules\AppBuilder\Models\BuilderAttachment::PREVIEW, $project->attachments()->sole()->quality);
    }

    public function test_after_payment_the_build_waits_for_the_originals_and_then_uses_them(): void
    {
        $project = $this->quotedWithPreview();
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($project, $project->quote_version, 'TX-1');
        $this->assertNotNull($project->refresh()->originals_due_at);

        // waiting: the worker gets nothing, and the page asks for the originals by name
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('originals.names', ['logo.png']);

        $this->post('/ai/'.$project->uuid.'/originals', ['files' => [$this->upload('design.png', (string) file_get_contents(__FILE__) === '' ? '' : base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='))]], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('originals', null);

        // the build starts and gets the original instead of the preview
        $job = $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->json('job');
        $this->assertSame(['design.png'], array_column($job['attachments'], 'name'));
    }

    public function test_the_originals_are_never_sent_to_the_chat_model(): void
    {
        $project = $this->quotedWithPreview();
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($project, $project->quote_version, 'TX-1');
        $this->post('/ai/'.$project->uuid.'/originals', ['files' => [$this->png()]], ['Accept' => 'application/json'])->assertOk();

        $note = BuilderMessage::where('project_id', $project->id)->where('role', BuilderMessage::USER)->orderByDesc('id')->firstOrFail();
        $this->assertCount(1, $note->attachments);
        $this->assertIsString(app(\Modules\AppBuilder\Services\AttachmentStore::class)->contentFor($note->load('attachments')));
    }

    public function test_the_build_starts_without_the_originals_when_skipped_or_after_the_wait(): void
    {
        $skipped = $this->quotedWithPreview();
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($skipped, $skipped->quote_version, 'TX-1');
        $this->postJson('/ai/'.$skipped->uuid.'/originals/skip')->assertOk()->assertJsonPath('originals', null);
        $this->assertSame($skipped->uuid, $this->postJson('/api/builder/worker/claim', [], $this->worker())->json('job.uuid'));

        $waited = $this->quotedWithPreview();
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($waited, $waited->quote_version, 'TX-2');
        $this->travel(31)->minutes();
        $this->assertSame($waited->uuid, $this->postJson('/api/builder/worker/claim', [], $this->worker())->json('job.uuid'));
    }

    public function test_the_price_range_reads_like_a_person_wrote_it(): void
    {
        $this->assertSame(['from' => 120.0, 'to' => 200.0], \Modules\AppBuilder\Services\PriceRange::around(135));
        $this->assertSame(['from' => 1.0, 'to' => 2.0], \Modules\AppBuilder\Services\PriceRange::around(1));
    }

    public function test_the_quote_shows_a_range_and_the_owner_can_ask_for_more_up_to_its_top(): void
    {
        $project = $this->quotedProject();
        $range = $this->getJson('/ai/'.$project->uuid.'/state')->json('quote.range');
        $project->refresh();
        $this->assertSame(number_format((float) $project->price_max, 2), $range['to']);
        $this->assertGreaterThan((float) $project->amount, (float) $project->price_max);

        // nothing can be asked before the customer paid
        $payment = app(\Modules\AppBuilder\Services\ProjectPayment::class);
        $this->assertSame(0.0, $payment->askForMore($project, 10));

        $payment->markPaid($project, $project->quote_version, 'TX-1');
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $room = (float) $project->price_max - (float) $project->amount;
        // asking for more than the range allows stops at its top
        $this->actingAs($admin)->post('/admin/builder/'.$project->uuid.'/extra', ['amount' => $room + 500])->assertRedirect();

        $project->refresh();
        $this->assertSame((float) $project->price_max, (float) $project->amount);
        $this->assertEqualsWithDelta($room, $project->balanceDue(), 0.01);
        $this->assertSame(BuilderMessage::TEAM, $project->messages()->reorder('id', 'desc')->value('role'));
        $this->actingAs($admin)->post('/admin/builder/'.$project->uuid.'/extra', ['amount' => 5])->assertStatus(409);
    }

    public function test_a_customer_from_saudi_arabia_is_answered_in_saudi_arabic(): void
    {
        $this->answer('هلا');
        $uuid = $this->postJson('/ai/messages', ['text' => 'أبغى موقع لمحلي', 'country' => 'sa', 'timezone' => 'Africa/Cairo'])->assertOk()->json('uuid');

        // the browser's country wins over its time zone, and the chat is told which Arabic to write
        $this->assertSame('SA', BuilderProject::where('uuid', $uuid)->value('country'));
        $this->assertStringContainsString('Saudi Arabic', app(ChatModel::class)->lastSystem);
    }

    public function test_without_a_country_the_time_zone_decides_and_unknown_stays_egyptian(): void
    {
        $this->answer('هلا');
        $kuwait = $this->postJson('/ai/messages', ['text' => 'مرحبا', 'country' => '', 'timezone' => 'Asia/Kuwait'])->assertOk()->json('uuid');
        $this->assertSame('KW', BuilderProject::where('uuid', $kuwait)->value('country'));
        $this->assertStringContainsString('Gulf Arabic', app(ChatModel::class)->lastSystem);

        $this->assertSame('simple Egyptian Arabic', \Modules\AppBuilder\Services\Chat\Dialect::arabicFor(null));
        $this->assertSame('simple Egyptian Arabic', \Modules\AppBuilder\Services\Chat\Dialect::arabicFor('EG'));
    }

    public function test_the_chat_answers_in_the_language_of_the_customers_last_message(): void
    {
        $this->answer('Sure.');
        $uuid = $this->postJson('/ai/messages', ['text' => 'a complex system of satellite images'])->assertOk()->json('uuid');
        $this->assertStringContainsString('is in English', app(ChatModel::class)->lastSystem);

        $this->answer('تمام.');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'ممكن نكمل بالعربي؟'])->assertOk();
        $this->assertStringContainsString('is in Arabic', app(ChatModel::class)->lastSystem);
    }

    public function test_only_an_admin_can_mark_the_price_as_paid_and_it_starts_the_build(): void
    {
        $project = $this->quotedProject();
        // a customer never gets the button, and cannot call the route
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('admin_paid_url', null);
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $this->actingAs($customer)->postJson('/admin/builder/'.$project->uuid.'/mark-paid')->assertForbidden();
        $this->assertSame(BuilderProject::QUOTED, $project->refresh()->status);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $url = $this->actingAs($admin)->getJson('/ai/'.$project->uuid.'/state')->json('admin_paid_url');
        $this->assertNotNull($url);

        $this->postJson($url)->assertOk()->assertJsonPath('status', BuilderProject::QUEUED)->assertJsonPath('quote', null);
        $project->refresh();
        $this->assertSame((float) $project->amount, (float) $project->paid_total);
        $this->assertSame($project->uuid, $this->postJson('/api/builder/worker/claim', [], $this->worker())->json('job.uuid'));

        // nothing open to pay any more
        $this->postJson($url)->assertStatus(409);
    }

    public function test_a_project_without_previews_starts_right_after_payment(): void
    {
        $project = $this->quotedProject();
        app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaid($project, $project->quote_version, 'TX-1');

        $this->assertNull($project->refresh()->originals_due_at);
        $this->assertSame($project->uuid, $this->postJson('/api/builder/worker/claim', [], $this->worker())->json('job.uuid'));
    }

    private function xlsx(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xl').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<Types/>');
        $zip->addFromString('xl/workbook.xml', '<workbook/>');
        $zip->close();

        return new UploadedFile($path, 'حسابات.xlsx', 'application/octet-stream', null, true);
    }

    public function test_a_spreadsheet_is_read_by_the_worker_and_claude_gets_its_formulas(): void
    {
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();
        $response = $this->post('/ai/messages', ['text' => 'عايز الحسابات دي تبقى سيستم', 'files' => [$this->xlsx()]], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('looking', 'file');
        $uuid = $response->json('uuid');

        $item = $this->getJson('/api/builder/worker/looks', $this->worker())->json('looks.0');
        $this->assertSame('file', $item['kind']);
        $this->assertSame($uuid, $item['project']);
        // the worker downloads the original with its own token
        $this->get('/api/builder/worker/jobs/'.$uuid.'/attachments/'.$item['attachment_id'], $this->worker())->assertOk();

        $this->answer('فهمت: سعر البيع = التكلفة × 1.3 والإجمالي مجموع الفواتير.');
        $this->post('/api/builder/worker/looks/'.$item['message_id'], ['index' => $item['index'], 'text' => "Sheet فواتير\nD2 = =B2*C2 -> 150\nD10 = =SUM(D2:D9) -> 900"], $this->worker())->assertOk();

        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('looking', null)->assertJsonPath('messages.0.files.0', 'حسابات.xlsx');
        $sent = app(ChatModel::class)->seen[0][0]['content'];
        // the spreadsheet itself is not sent to Claude, only what the worker read from it
        $this->assertIsString($sent);
        $this->assertStringContainsString('D10 = =SUM(D2:D9) -> 900', $sent);
    }

    public function test_a_renamed_file_is_not_taken_as_a_spreadsheet(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fake');
        file_put_contents($path, 'MZ this is a program, not a spreadsheet');

        $this->post('/ai/messages', ['files' => [new UploadedFile($path, 'invoice.xlsx', 'application/octet-stream', null, true)]], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('files.0');
    }

    public function test_without_the_worker_the_chat_says_plainly_it_could_not_read_the_file(): void
    {
        $this->answer('معلش مقدرتش أقرا الملف دلوقتي، ابعته تاني كمان شوية.');

        $this->post('/ai/messages', ['text' => 'ده ملف الحسابات', 'files' => [$this->xlsx()]], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('looking', null);

        $this->assertStringContainsString('could not be read', app(ChatModel::class)->seen[0][0]['content']);
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

    public function test_the_owner_sees_who_started_a_chat_their_address_and_device(): void
    {
        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true, 'name' => 'Sara Ali', 'email' => 'sara@example.com']);
        $this->answer('Sure.');
        $uuid = $this->actingAs($customer)->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/130.0'])
            ->postJson('/ai/messages', ['text' => 'a menu page'])->assertOk()->json('uuid');

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/admin/builder/'.$uuid)->assertOk()->assertInertia(fn ($page) => $page
            ->where('project.visitor.user.name', 'Sara Ali')
            ->where('project.visitor.user.email', 'sara@example.com')
            ->where('project.visitor.ip', '127.0.0.1')
            ->where('project.visitor.user_agent', 'Mozilla/5.0 (Linux; Android 14) Chrome/130.0')
            ->where('project.visitor.referrer', null));
        $this->actingAs($admin)->get('/admin/builder')->assertOk()->assertInertia(fn ($page) => $page->where('projects.data.0.visitor.user.id', $customer->id));
    }

    public function test_the_chat_is_told_the_real_payment_state_not_what_the_customer_claims(): void
    {
        $project = $this->quotedProject();
        $this->answer('The payment has not reached the page yet.');
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'I paid'])->assertOk();

        $system = app(ChatModel::class)->lastSystem;
        $this->assertStringContainsString('it is NOT paid', $system);
        // saying it in the chat never marks anything paid
        $project->refresh();
        $this->assertNull($project->paid_at);
        $this->assertSame(0.0, (float) $project->paid_total);
    }

    public function test_an_admin_opening_a_guest_chat_does_not_take_it_but_a_customer_does(): void
    {
        $this->answer('Sure.');
        $uuid = $this->postJson('/ai/messages', ['text' => 'a menu page'])->assertOk()->json('uuid');

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/ai/'.$uuid)->assertOk();
        $this->assertNull(BuilderProject::where('uuid', $uuid)->value('user_id'));

        $customer = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $this->actingAs($customer)->get('/ai/'.$uuid)->assertOk();
        $this->assertSame($customer->id, BuilderProject::where('uuid', $uuid)->value('user_id'));
    }

    public function test_the_short_ai_link_keeps_the_referral_and_opens_the_chat(): void
    {
        $referrer = $this->referrer();
        $this->get('/r/ahmed/ai')->assertRedirect('/ai');

        $this->answer('تمام، ده السعر', 50000);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز منيو'])->assertOk()->json('uuid');
        $this->assertSame($referrer->id, BuilderProject::where('uuid', $uuid)->value('referrer_user_id'));
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

    public function test_a_game_is_never_priced_like_a_small_page(): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'لعبة عربيات', 'spec' => 'First playable round of a racing game.', 'estimated_output_tokens' => 25000, 'kind' => 'build', 'complex' => true, 'design_fee' => 0, 'project_type' => 'game'],
        ]);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز لعبة عربيات'])->json('uuid');

        // the same 25k tokens as a $3 page, but a game is a joint project with design and play-testing rounds
        $this->assertSame('300.00', BuilderProject::where('uuid', $uuid)->value('amount'));
    }

    public function test_a_one_screen_page_is_priced_by_its_real_cost_and_its_build_can_never_spend_more(): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'تمام',
            'needs_team' => false,
            'quote' => ['ready' => true, 'title' => 'برنامج فواتير', 'spec' => 'Invoice maker that prints.', 'estimated_output_tokens' => 25000, 'kind' => 'build', 'complex' => false, 'design_fee' => 0, 'project_type' => 'page'],
        ]);
        $uuid = $this->postJson('/ai/messages', ['text' => 'برنامج فواتير بيطبع'])->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        // 25k tokens on Sonnet is about $1 of work, x3 = $3: a cheap first project, not a $60 minimum
        $this->assertSame('3.00', $project->amount);
        // and the worker stops at 1.5x this cap: the $3 price itself
        $this->assertSame('2.0000', $project->budget_usd);
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
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1000], $this->worker())->assertOk();

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
