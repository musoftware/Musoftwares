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
    use \Modules\AppBuilder\Tests\SignsInWithGoogle, RefreshDatabase;

    private const TOKEN = 'worker-secret-token';

    /** @var list<ChatTurn|ChatUnavailable> */
    private array $script = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInWithGoogle();
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

    private function answer(string $reply, int $tokens = 0, bool $needsTeam = false, string $askEngineer = ''): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => $reply,
            'needs_team' => $needsTeam,
            'ask_engineer' => $askEngineer,
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

    /** The order id of the payment link the customer just made (each link pays only its own amount, once). */
    private function lastOrderId(): string
    {
        return \Modules\AppBuilder\Models\BuilderPaymentOrder::latest('id')->value('order_id');
    }

    /** Makes a payment link the way the customer does and returns its order id. */
    private function payLink(BuilderProject $project, array $extra = []): string
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::firstOrCreate(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id], ['rate' => 50]);
        $this->postJson('/ai/'.$project->uuid.'/pay', ['client_name' => 'أحمد', 'client_email' => 'a@example.com', 'client_whatsapp' => '01012345678'] + $extra)->assertOk();

        return $this->lastOrderId();
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

    public function test_a_plain_question_after_the_price_keeps_the_price(): void
    {
        $project = $this->quotedProject();
        $amount = $project->amount;
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'هيتبني بـ Laravel، والسعر ظاهر تحت الرسالة.',
            'needs_team' => false,
            'keep_quote' => true,
            'quote' => ['ready' => false, 'title' => '', 'spec' => '', 'estimated_output_tokens' => 0],
        ]);

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'لغة برمجة ايه؟'])->assertOk()->assertJsonPath('quote.amount', number_format((float) $amount, 2));

        $project->refresh();
        $this->assertSame(BuilderProject::QUOTED, $project->status);
        $this->assertSame($amount, $project->amount);
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
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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

    public function test_when_the_free_model_fails_claude_answers_and_signed_in_customers_always_get_claude(): void
    {
        config(['builder.free_chat.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['error' => 'quota'], 429)]);
        $this->answer('Claude هنا');
        $uuid = $this->postJson('/ai/messages', ['text' => 'إزيك'])->assertOk()->json('uuid');
        $this->assertSame('Claude هنا', BuilderProject::where('uuid', $uuid)->firstOrFail()->messages()->reorder('id', 'desc')->value('content'));

        $this->geminiAnswers('مجاني');
        $this->actingAs(\App\Models\User::factory()->google()->create(['onboarding_completed' => true]));
        $this->answer('Claude للعميل المسجّل');
        $signed = $this->postJson('/ai/messages', ['text' => 'إزيك'])->assertOk()->json('uuid');
        $this->assertSame('Claude للعميل المسجّل', BuilderProject::where('uuid', $signed)->firstOrFail()->messages()->reorder('id', 'desc')->value('content'));
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
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
        $paid = (float) $this->quotedProjectPrice();
        $project = $this->deliverPaid(1.00, $customer);

        // the real cost plus 20%, and the rest back as balance for the next program
        $this->assertSame(1.20, (float) $project->amount);
        $this->assertEqualsWithDelta($paid - 1.20, $this->balanceInUsd($customer), 0.05);
        $this->assertSame(BuilderMessage::TEAM, $project->messages()->reorder('id', 'desc')->value('role'));
        $this->assertSame($project->quote_version, $project->price_settled_version);
        // money already counted when the customer paid: it goes back as earned balance, not as new income
        $this->assertSame('earned', \App\Models\Transaction::where('user_id', $customer->id)->where('reason', 'like', 'AI builder: the build cost less%')->value('type'));
        // and what the build cost Claude is kept for the costs page
        $this->assertEqualsWithDelta(1.00, (float) \Modules\AppBuilder\Models\BuilderAiSpending::where('project_id', $project->id)->where('source', 'build')->sum('usd'), 0.0001);
    }

    public function test_a_price_an_admin_marked_paid_gives_nothing_back_when_the_work_costs_less(): void
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
        $this->usdAndEgpRates();
        $this->actingAs($customer);
        $project = $this->quotedProject();
        $price = (float) $project->amount;

        $this->assertTrue(app(\Modules\AppBuilder\Services\ProjectPayment::class)->markPaidByAdmin($project, 1));
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk();
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 0.10], $this->worker())->assertOk();

        $project->refresh();
        $this->assertSame($price, (float) $project->amount);
        $this->assertEqualsWithDelta(0, $this->balanceInUsd($customer), 0.001);
        $this->assertNull($project->price_settled_version);
    }

    public function test_a_guest_keeps_the_price_they_paid_and_a_cost_above_the_price_changes_nothing(): void
    {
        $guest = $this->deliverPaid(1.00);
        $this->assertSame((float) $guest->quote_paid, (float) $guest->amount);

        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
        $paid = (float) $this->quotedProjectPrice();
        $costly = $this->deliverPaid(500.00, $customer);
        $this->assertSame($paid, (float) $costly->amount);
        $this->assertEqualsWithDelta(0, $this->balanceInUsd($customer), 0.01);
    }

    public function test_a_referred_customer_pays_the_cost_plus_half(): void
    {
        $referrer = $this->referrer();
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true, 'currency_id' => $usd->id]);
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

    public function test_a_bigger_project_is_never_free(): void
    {
        $this->actingAs(\App\Models\User::factory()->google()->create(['onboarding_completed' => true]));
        $project = $this->quotedProject();
        $this->assertNull($this->getJson('/ai/'.$project->uuid.'/state')->json('quote.free_build'));
    }

    public function test_a_signed_in_customer_pays_from_the_wallet_only_when_it_covers_the_price(): void
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true, 'currency_id' => $egp->id]);
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

    public function test_paying_the_open_quote_queues_the_build(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP']);
        CurrenciesExchange::create(['date_string' => now()->toDateString(), 'currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50]);
        $project = $this->quotedProject();

        $this->postJson('/ai/'.$project->uuid.'/pay', ['client_name' => 'أحمد', 'client_email' => 'a@example.com', 'client_whatsapp' => '01012345678'])
            ->assertOk()->assertJson(fn ($json) => $json->where('url', fn ($url) => str_contains($url, 'payments.kashier.io')));

        [$body, $headers] = $this->signedWebhook($this->lastOrderId());
        $this->postJson('/ai/webhook', $body, $headers)->assertOk()->assertJsonPath('status', 'processed');

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertSame('6.00', $project->paid_total);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('progress', __('builder.steps.queued'))
            // paid and started: the customer can keep writing, and new changes join the list of requests
            ->assertJsonPath('open_for_chat', true);
    }

    public function test_an_older_cheaper_link_pays_only_its_own_amount(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $project = $this->quotedProject();

        // a 25% link first, then a full link: paying the cheap one must not count as the full price
        $cheap = $this->payLink($project, ['share' => 25]);
        $this->payLink($project);
        [$body, $headers] = $this->signedWebhook($cheap);
        $this->postJson('/ai/webhook', $body, $headers)->assertOk()->assertJsonPath('status', 'processed');

        $project->refresh();
        $this->assertSame('2.00', $project->paid_total);
        $this->assertTrue($project->hasBalanceDue());

        // the same link paid again (a replay with another transaction id) is not counted twice
        [$again, $againHeaders] = $this->signedWebhook($cheap, 'TX-REPLAY');
        $this->postJson('/ai/webhook', $again, $againHeaders)->assertOk()->assertJsonPath('status', 'ignored');
        $this->assertSame('2.00', $project->refresh()->paid_total);
    }

    public function test_a_signed_amount_below_the_link_or_an_unknown_link_credits_nothing(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $project = $this->quotedProject();
        $order = $this->payLink($project);

        $data = ['status' => 'SUCCESS', 'merchantOrderId' => $order, 'transactionId' => 'TX-LOW', 'amount' => '1.00', 'currency' => 'EGP', 'signatureKeys' => ['amount', 'currency', 'merchantOrderId', 'status', 'transactionId']];
        $keys = $data['signatureKeys'];
        sort($keys);
        $query = http_build_query(array_combine($keys, array_map(fn ($key) => $data[$key], $keys)), '', '&', PHP_QUERY_RFC3986);
        $this->postJson('/ai/webhook', ['data' => $data], ['x-kashier-signature' => hash_hmac('sha256', $query, 'secret')])->assertJsonPath('status', 'ignored');

        [$body, $headers] = $this->signedWebhook('bld_'.$project->id.'-1_made-up');
        $this->postJson('/ai/webhook', $body, $headers)->assertJsonPath('status', 'ignored');

        $this->assertSame(BuilderProject::QUOTED, $project->refresh()->status);
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

    public function test_the_admin_page_keeps_the_teams_own_chats_apart_from_customers(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $customer = \App\Models\User::factory()->google()->create();
        $make = fn (array $fields) => BuilderProject::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => BuilderProject::CHATTING, 'currency' => 'USD'] + $fields);

        $own = $make(['title' => 'admin test', 'user_id' => $admin->id, 'ip' => '156.1.1.1']);
        $ownSignedOut = $make(['title' => 'admin signed out', 'ip' => '156.1.1.1']);
        $buyer = $make(['title' => 'customer', 'user_id' => $customer->id, 'ip' => '156.1.1.1']);
        $guest = $make(['title' => 'guest', 'ip' => '41.2.2.2']);
        $noAddress = $make(['title' => 'guest without address']);

        $titles = fn (string $url) => collect($this->actingAs($admin)->get($url)->assertOk()->viewData('page')['props']['projects']['data'])->pluck('title')->sort()->values()->all();

        $this->assertSame(['customer', 'guest', 'guest without address'], $titles('/admin/builder'));
        $this->assertSame(['admin signed out', 'admin test'], $titles('/admin/builder?tab=team'));
        $this->actingAs($admin)->get('/admin/builder')->assertInertia(fn ($page) => $page->where('tabCounts', ['customers' => 3, 'working' => 0, 'paid' => 0, 'team' => 2])->where('tab', 'customers'));

        // the paid tab holds the real work, whoever started it: money paid in, or a project the owner imported
        $buyer->update(['paid_total' => 50]);
        $own->update(['import_url' => 'https://github.com/musoftware/shop', 'customer_project' => false]);
        $this->assertSame(['admin test', 'customer'], $titles('/admin/builder?tab=paid'));
        // the working tab is the paid work the worker has now or next; a guest's unpaid chat never shows there
        $buyer->update(['status' => BuilderProject::BUILDING]);
        $guest->update(['status' => BuilderProject::QUEUED]);
        $this->assertSame(['customer'], $titles('/admin/builder?tab=working'));
    }

    public function test_a_build_cut_off_by_a_worker_restart_is_claimed_again_at_once(): void
    {
        $project = $this->buildingProject();
        // the lease still has two hours; without the hand-back nobody could build it until then
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();

        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/abandoned', [], $this->worker())->assertOk();

        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.uuid', $project->uuid);
        $this->assertSame(2, $project->refresh()->attempts);
        // a job that is not being built cannot be handed back
        $this->postJson('/api/builder/worker/jobs/'.$this->quotedProject()->uuid.'/abandoned', [], $this->worker())->assertStatus(409);
    }

    public function test_the_owner_can_send_a_failed_build_back_to_the_worker(): void
    {
        $project = $this->buildingProject();
        $this->postJson('/api/builder/worker/jobs/'.$project->uuid.'/fail', ['error' => 'The pipe is being closed.', 'cost_usd' => 40], $this->worker())->assertOk();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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

    public function test_a_crashed_build_restarts_by_itself_twice_and_a_real_failure_waits_for_the_owner(): void
    {
        $crashed = $this->buildingProject();
        $crashed->update(['budget_usd' => 10, 'job_cost_usd' => 3]);
        $this->travel(-10)->minutes();
        $crashed->update(['status' => BuilderProject::FAILED, 'error' => 'The agent stopped with an error: Claude Code process terminated by signal SIGABRT']);
        $spent = $this->buildingProject();
        $spent->update(['status' => BuilderProject::FAILED, 'error' => 'The agent stopped: the budget for this job is used up.', 'budget_usd' => 10, 'job_cost_usd' => 3]);
        $this->travelBack();
        $retries = app(\Modules\AppBuilder\Services\CrashRetries::class);

        $this->assertSame(1, $retries->retryAll());
        $this->assertSame(BuilderProject::QUEUED, $crashed->refresh()->status);
        $this->assertSame(1, $crashed->auto_retries);
        $this->assertSame(BuilderProject::FAILED, $spent->refresh()->status);

        // after two tries it waits for the owner, so a broken build never loops
        $crashed->update(['auto_retries' => 2, 'status' => BuilderProject::FAILED]);
        $this->travel(10)->minutes();
        $this->assertSame(0, $retries->retryAll());
    }

    public function test_the_owner_sees_how_often_a_build_needs_no_programmer(): void
    {
        $stats = app(\Modules\AppBuilder\Services\DeliveryStats::class);
        $alone = $this->buildingProject();
        $alone->update(['paid_total' => 100]);
        $stats->record($alone, 20);
        $helped = $this->buildingProject();
        $helped->update(['paid_total' => 100, 'owner_touched' => true]);
        $stats->record($helped, 30);
        // the customer of the second one asked for a fix soon after
        \Modules\AppBuilder\Models\BuilderRequest::create(['project_id' => $helped->id, 'kind' => \Modules\AppBuilder\Models\BuilderRequest::FIX, 'title' => 'زرار مش شغال', 'spec' => 'fix', 'status' => \Modules\AppBuilder\Models\BuilderRequest::READY]);

        $summary = $stats->summary();

        $this->assertSame(2, $summary['count']);
        $this->assertSame(50, $summary['hands_off']);
        $this->assertSame(50, $summary['accepted']);
        $this->assertSame(25, $summary['cost_share']);
    }

    public function test_the_customer_sees_the_builds_phases_in_plain_words(): void
    {
        config(['builder.retell_delivery' => true]);
        $calls = 0;
        $this->app->instance(\Modules\AppBuilder\Services\Chat\ChatModel::class, new class($calls) implements \Modules\AppBuilder\Services\Chat\ChatModel
        {
            public function __construct(public int &$calls) {}

            public function reply(string $system, array $turns): \Modules\AppBuilder\Services\Chat\ChatTurn
            {
                $this->calls++;

                return \Modules\AppBuilder\Services\Chat\ChatTurn::fromArray(['reply' => "الأساس والدخول\nالمنيو والطلبات\nشاشات الموقع", 'quote' => []]);
            }
        });
        $project = $this->buildingProject();
        $url = '/api/builder/worker/jobs/'.$project->uuid.'/snapshot';
        $plan = "# Progress (phases)\n- [x] 1 Foundation: DB migrations, auth\n- [ ] 2 Orders API, SSE\n- [ ] 3 Web: dashboard shell\n";

        $before = $calls;

        $this->post($url, ['percent' => 30, 'caption' => 'الأساس خلص', 'plan' => $plan], $this->worker())->assertCreated();
        $this->post($url, ['percent' => 50, 'caption' => 'الطلبات خلصت', 'plan' => str_replace('[ ] 2', '[x] 2', $plan)], $this->worker())->assertCreated();
        // the same phases are retold once; only the ticks change after that
        $this->assertSame(1, $calls - $before);
        // the worker's own update from the ticked phases moves the bar and keeps the last line
        $this->post($url, ['percent' => 63, 'plan' => str_replace('[ ] 2', '[x] 2', $plan)], $this->worker())->assertCreated();
        $this->assertSame(63, $project->refresh()->progress_percent);
        $this->assertSame('الطلبات خلصت', $project->progress_note);

        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('phases', [
            ['title' => 'الأساس والدخول', 'state' => 'done'],
            ['title' => 'المنيو والطلبات', 'state' => 'done'],
            ['title' => 'شاشات الموقع', 'state' => 'now'],
        ]);
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
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);

        $this->answer('تمام، احكيلي أكتر');
        $uuid = $this->actingAs($customer)->postJson('/ai/messages', ['text' => 'عايز سيستم'])->assertOk()->json('uuid');
        $this->assertSame($customer->id, BuilderProject::where('uuid', $uuid)->value('user_id'));

        // a chat started as a guest joins the account when its link is opened signed in
        $guest = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'متجر قديم']);
        $this->actingAs($customer)->get('/ai/'.$guest->uuid)->assertOk();
        $this->assertSame($customer->id, $guest->refresh()->user_id);

        $this->actingAs($customer)->get('/ai')->assertOk()
            ->assertSee(__('builder.chats'))->assertSee('متجر قديم')->assertSee('/ai/'.$uuid, false)
            ->assertSee(json_encode(route('company.contact')), false)->assertDontSee('data-size="big"', false);
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
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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

    public function test_a_change_to_a_delivered_program_teaches_the_price_of_changes_only(): void
    {
        $project = $this->buildingProject();
        $project->update(['version' => 1]);

        app(\Modules\AppBuilder\Services\BuildCostHistory::class)->record($project->refresh(), 5);

        $this->assertSame(\Modules\AppBuilder\Services\QuotePricer::CHANGE, \Modules\AppBuilder\Models\BuilderBuildCost::where('project_id', $project->id)->sole()->project_type);
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
        // the page's pictures are for Claude; the customer is not shown the page they sent
        $this->assertSame([], $customer['images']);
        // the "New chat" link the chat may point to is really on the page
        $this->get('/ai/'.$uuid)->assertOk()->assertSee(__('builder.new_chat'));
        $this->assertSame([], $customer['files']);
        $this->assertSame('شوفت الموقع: حديقة بتعرض السور بشكل شجر، وفيها تقدم للطفل.', $state->json('messages.1.text'));

        // Claude got the two sharp pieces (not the full page) and the page text
        $blocks = app(ChatModel::class)->seen[0][0]['content'];
        $this->assertCount(2, collect($blocks)->where('type', 'image'));
        $this->assertStringContainsString('سورة الفاتحة', collect($blocks)->where('type', 'text')->first()['text']);
        $this->assertStringContainsString('real browser', collect($blocks)->where('type', 'text')->first()['text']);
    }

    public function test_a_customer_gets_two_links_opened_a_day_and_is_told_to_send_more_tomorrow(): void
    {
        $this->getJson('/api/builder/worker/looks', $this->worker())->assertOk();
        $uuid = $this->postJson('/ai/messages', ['text' => 'زي دول https://quran.example.com/one https://quran.example.com/two'])->assertJsonPath('looking', 'link')->json('uuid');
        $looks = $this->getJson('/api/builder/worker/looks', $this->worker())->json('looks');
        $this->assertCount(2, $looks);
        $this->answer('شوفت الموقعين.');
        foreach ($looks as $look) {
            $this->post('/api/builder/worker/looks/'.$look['message_id'], ['index' => $look['index'], 'title' => 'x', 'full_page' => $this->png()], $this->worker())->assertOk();
        }

        // the third link is not opened at all: the chat answers right away and says to send it tomorrow
        $this->answer('ابعته بكرة.');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'وده كمان https://quran.example.com/three'])->assertOk()->assertJsonPath('looking', null);

        $this->assertSame([], $this->getJson('/api/builder/worker/looks', $this->worker())->json('looks'));
        $last = collect(app(ChatModel::class)->seen)->last();
        $this->assertStringContainsString('send it again tomorrow', json_encode($last, JSON_UNESCAPED_UNICODE));
    }

    public function test_what_the_chat_does_not_know_goes_to_the_programmer_and_the_answer_comes_back(): void
    {
        $this->answer('سؤال مهم، هسأل المبرمج وأرجعلك بالرد هنا.', askEngineer: 'Can an iPhone app be installed without the App Store?');
        $uuid = $this->postJson('/ai/messages', ['text' => 'ينفع التطبيق ينزل على الايفون من غير اب ستور؟'])->assertOk()->json('uuid');
        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('asking_engineer', true);

        $questions = $this->getJson('/api/builder/worker/engineer-questions', $this->worker())->assertOk()->json('questions');
        $this->assertSame('Can an iPhone app be installed without the App Store?', $questions[0]['question']);
        $this->assertStringContainsString('اب ستور', $questions[0]['chat']);
        // handed out once while the programmer works on it
        $this->assertSame([], $this->getJson('/api/builder/worker/engineer-questions', $this->worker())->json('questions'));

        $this->answer('المبرمج بيقول: الايفون لازم يتنشر من خلال اب ستور أو TestFlight.');
        $this->postJson('/api/builder/worker/engineer-questions/'.$questions[0]['id'], ['answer' => 'No. iPhone apps go through the App Store or TestFlight.'], $this->worker())->assertOk();
        // the same answer sent twice writes nothing more
        $this->postJson('/api/builder/worker/engineer-questions/'.$questions[0]['id'], ['answer' => 'again'], $this->worker())->assertOk();

        $state = $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('asking_engineer', false);
        $this->assertSame('المبرمج بيقول: الايفون لازم يتنشر من خلال اب ستور أو TestFlight.', collect($state->json('messages'))->last()['text']);
        $this->assertStringContainsString('iPhone apps go through the App Store', json_encode(collect(app(ChatModel::class)->seen)->last(), JSON_UNESCAPED_UNICODE));
        $this->assertCount(3, BuilderProject::where('uuid', $uuid)->firstOrFail()->messages);
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
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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

    public function test_the_customers_own_words_set_the_dialect_and_it_stays_for_the_whole_chat(): void
    {
        $this->answer('أهلاً فيك');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عندي بروجكت شغال بدي تزبطو', 'country' => 'EG'])->assertOk()->json('uuid');
        $this->assertStringContainsString('Levantine Arabic', app(ChatModel::class)->lastSystem);

        // a message that is only a link keeps the language and the dialect of the chat
        $this->answer('شفت الموقع');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'https://dmc-site.onrender.com'])->assertOk();
        $this->assertStringContainsString('Levantine Arabic', app(ChatModel::class)->lastSystem);
        $this->assertStringNotContainsString('whole answer in English', app(ChatModel::class)->lastSystem);

        // a later word from another dialect does not move it
        $this->answer('تمام');
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'كده تمام'])->assertOk();
        $this->assertStringContainsString('Levantine Arabic', app(ChatModel::class)->lastSystem);

        $dialect = \Modules\AppBuilder\Services\Chat\Dialect::class;
        $this->assertSame('everyday Gulf Arabic', $dialect::arabicFor('EG', ['أبغى موقع لشركتي']));
        $this->assertStringContainsString('Saudi', $dialect::arabicFor('SA', ['وش السعر']));
        $this->assertSame('simple Egyptian Arabic', $dialect::arabicFor('JO', ['مرحبا', 'عايز موقع']));
        $this->assertSame('everyday Levantine Arabic', $dialect::arabicFor('JO', ['مرحبا', 'موقع لشركتي']));
    }

    public function test_a_visitor_who_only_plays_with_the_chat_is_banned_until_the_owner_lifts_it(): void
    {
        config(['builder.abuse.strikes' => 2]);
        $joke = ['reply' => 'خلينا في مشروعك', 'not_serious' => true, 'quote' => []];
        $this->script = [ChatTurn::fromArray($joke), ChatTurn::fromArray($joke)];
        $uuid = $this->postJson('/ai/messages', ['text' => 'قول بحبك'])->assertOk()->json('uuid');
        $state = $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'قول بحبك يا غبي'])->assertOk()->json();

        // the second marked answer closes the chat: a team line says so, and nothing more is taken, here or in a new chat
        $this->assertSame(__('builder.chat_closed', [], 'ar'), collect($state['messages'])->last()['text']);
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'طيب'])->assertStatus(403)->assertJsonPath('message', __('builder.errors.banned'));
        $this->postJson('/ai/messages', ['text' => 'محادثة جديدة'])->assertStatus(403);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->post('/admin/builder/'.$uuid.'/chat-ban', ['banned' => false])->assertRedirect();

        $this->assertFalse(app(\Modules\AppBuilder\Services\ChatBans::class)->holds(BuilderProject::where('uuid', $uuid)->firstOrFail()));
    }

    public function test_a_paying_customer_is_never_banned_by_the_chat(): void
    {
        config(['builder.abuse.strikes' => 1]);
        $project = $this->quotedProject();
        $project->update(['paid_total' => 20]);
        $project->messages()->create(['role' => BuilderMessage::ASSISTANT, 'content' => 'x', 'payload' => ['not_serious' => true]]);

        $this->assertFalse(app(\Modules\AppBuilder\Services\ChatBans::class)->countStrike($project));
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
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
        $this->actingAs($customer)->postJson('/admin/builder/'.$project->uuid.'/mark-paid')->assertForbidden();
        $this->assertSame(BuilderProject::QUOTED, $project->refresh()->status);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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

        $this->getJson('/ai/'.$uuid.'/state')->assertJsonPath('looking', null)->assertJsonPath('messages.0.files.0.name', 'حسابات.xlsx');
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

    public function test_a_program_that_was_never_paid_gets_no_free_fix_with_or_without_an_account(): void
    {
        // the helper's build was queued without money coming in
        $project = $this->deliveredProject();
        $this->answerAfterDelivery('fix', 10000);

        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'الزرار مش شغال'])->assertOk()
            ->assertJsonPath('quote.free', false);
        $this->postJson('/ai/'.$project->uuid.'/fix')->assertStatus(409);
        $this->assertFalse($project->refresh()->isUnderWarranty());
    }

    public function test_a_change_or_a_fix_after_the_warranty_is_paid(): void
    {
        // after the free first period, new work has a price
        config(['builder.free_changes_days' => 0]);
        $project = $this->deliveredProject();
        $this->answerAfterDelivery('build', 10000);
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'ضيف صفحة جديدة'])->assertOk()->assertJsonPath('quote.free', false);
        $this->postJson('/ai/'.$project->uuid.'/fix')->assertStatus(409);

        $this->travel((int) config('builder.warranty_days') + 1)->days();
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

        $this->assertSame(['logo.png', 'menu.pdf'], array_column($response->json('messages.0.files'), 'name'));
        // the picture has a preview, the PDF is shown by name
        $this->assertNotNull($response->json('messages.0.files.0.url'));
        $this->assertNull($response->json('messages.0.files.1.url'));
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
        $user = \App\Models\User::factory()->google()->create(['currency_id' => $currency->id]);
        \App\Models\UserReferral::create(['user_id' => $user->id, 'title' => 'Default', 'key' => 'abc123', 'slug' => 'ahmed']);

        return $user;
    }

    public function test_the_owner_sees_who_started_a_chat_their_address_and_device(): void
    {
        $customer = \App\Models\User::factory()->google()->create(['onboarding_completed' => true, 'name' => 'Sara Ali', 'email' => 'sara@example.com']);
        $this->answer('Sure.');
        $uuid = $this->actingAs($customer)->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/130.0'])
            ->postJson('/ai/messages', ['text' => 'a menu page'])->assertOk()->json('uuid');

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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

        [$body, $headers] = $this->signedWebhook($this->payLink($project));
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

        [$body, $headers] = $this->signedWebhook($this->payLink($project));
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();
        $this->assertSame(BuilderProject::DESIGNING, $project->refresh()->status);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertNoContent();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->google()->create(['onboarding_completed' => true]);
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
        [$body, $headers] = $this->signedWebhook($this->lastOrderId());
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();
        $this->postJson('/ai/webhook', $body, $headers)->assertOk();

        $project->refresh();
        $this->assertSame(BuilderProject::QUEUED, $project->status);
        $this->assertSame('3.00', $project->paid_total);
        $project->update(['status' => BuilderProject::BUILDING]);
        $files = ['index.html' => '<h1>Menu</h1>', 'css/site.css' => 'h1{}', 'package.json' => '{}', 'src/server.ts' => 'secret()', 'dist/server/app.js' => 'x', 'SETUP.md' => '# setup'];
        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip($files), 'summary' => 'تم', 'cost_usd' => 1000], $this->worker())->assertOk();

        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('balance.due', '3.00')->assertJsonPath('delivery.download_url', null);
        $this->get('/ai/'.$project->uuid.'/download')->assertStatus(402);
        $this->get('/ai/'.$project->uuid.'/app/index.html')->assertOk();
        $this->get('/ai/'.$project->uuid.'/app/css/site.css')->assertOk();
        // the source stays locked until the rest is paid, also through the preview link
        foreach (['package.json', 'src/server.ts', 'dist/server/app.js', 'SETUP.md'] as $source) {
            $this->get('/ai/'.$project->uuid.'/app/'.$source)->assertStatus(402);
        }
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'عايز تعديل'])->assertStatus(409);

        $this->postJson('/ai/'.$project->uuid.'/pay', $client)->assertOk();
        $this->assertSame('3.00', $project->refresh()->payment_due);
        [$rest, $restHeaders] = $this->signedWebhook($this->lastOrderId(), 'TX-2');
        $this->postJson('/ai/webhook', $rest, $restHeaders)->assertOk();

        $this->assertSame('6.00', $project->refresh()->paid_total);
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('balance', null);
        $this->get('/ai/'.$project->uuid.'/download')->assertOk();
        $this->get('/ai/'.$project->uuid.'/app/src/server.ts')->assertOk();
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

    public function test_a_long_copy_of_the_project_shows_a_line_that_moves_and_then_clears(): void
    {
        $project = $this->buildingProject();
        $url = '/api/builder/worker/jobs/'.$project->uuid.'/progress';

        $this->postJson($url, ['step' => 'importing'], $this->worker())->assertOk();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertJsonPath('progress', __('builder.steps.importing'));

        $this->postJson($url, ['step' => 'importing', 'percent' => 60], $this->worker())->assertOk();
        $this->getJson('/ai/'.$project->uuid.'/state')
            ->assertJsonPath('progress', __('builder.steps.copying', ['percent' => 60]))
            ->assertJsonPath('progress_percent', 3);

        $this->postJson($url, ['step' => 'importing', 'percent' => 101], $this->worker())->assertStatus(422);

        // the engineer starts: the copy line is gone and the bar keeps what it reached
        $this->postJson($url, ['step' => 'started'], $this->worker())->assertOk();
        $this->getJson('/ai/'.$project->uuid.'/state')
            ->assertJsonPath('progress', __('builder.steps.started'))
            ->assertJsonPath('progress_percent', 3);
    }

    public function test_what_the_engineer_tried_comes_with_the_delivery_as_a_clean_table(): void
    {
        $project = $this->buildingProject();
        $checks = json_encode([
            ['what' => 'ضفت منتج وظهر في القائمة', 'passed' => true, 'note' => ''],
            ['what' => '<b>الدفع بالفيزا</b>', 'passed' => false, 'note' => 'محتاج مفتاح البنك'],
            ['what' => 'نتيجة مش واضحة', 'passed' => 'yes'],
            ['what' => '   ', 'passed' => true],
            'words only',
        ], JSON_UNESCAPED_UNICODE);

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1, 'checks' => $checks], $this->worker())->assertOk();

        $last = collect($this->getJson('/ai/'.$project->uuid.'/state')->assertOk()->json('messages'))->last();
        $this->assertSame([
            ['what' => 'ضفت منتج وظهر في القائمة', 'passed' => true, 'note' => ''],
            ['what' => 'الدفع بالفيزا', 'passed' => false, 'note' => 'محتاج مفتاح البنك'],
            ['what' => 'نتيجة مش واضحة', 'passed' => false, 'note' => ''],
        ], $last['checks']);
    }

    public function test_a_delivery_without_the_table_still_arrives(): void
    {
        $project = $this->buildingProject();

        $this->post('/api/builder/worker/jobs/'.$project->uuid.'/complete', ['archive' => $this->zip(['index.html' => '<h1>Menu</h1>']), 'summary' => 'تم', 'cost_usd' => 1, 'checks' => 'not json'], $this->worker())->assertOk();

        $last = collect($this->getJson('/ai/'.$project->uuid.'/state')->assertOk()->json('messages'))->last();
        $this->assertSame([], $last['checks']);
    }

    /** A real cards file with one ready program, as the chat and the worker read it. */
    private function oneStarter(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'starters');
        file_put_contents($file, json_encode([[
            'slug' => 'clinic-book', 'type' => 'system',
            'title_en' => 'Clinic system', 'title_ar' => 'نظام عيادة',
            'summary_en' => 'Bookings, patients and invoices for a clinic.', 'summary_ar' => 'حجوزات ومرضى وفواتير للعيادة.',
            'features_en' => ['Bookings', 'Patient files'], 'features_ar' => ['الحجوزات', 'ملفات المرضى'],
        ]]));
        config(['builder.starters_file' => $file]);
    }

    private function answerWithStarter(string $starter, int $tokens = 0): void
    {
        $this->script[] = ChatTurn::fromArray([
            'reply' => 'عندنا نظام عيادة جاهز نطوره لفكرتك',
            'needs_team' => false,
            'quote' => ['ready' => $tokens > 0, 'title' => $tokens ? 'نظام عيادة' : '', 'spec' => $tokens ? 'Starts from the ready program clinic-book.' : '', 'estimated_output_tokens' => $tokens],
            'starter' => $starter,
        ], 1200, 300);
    }

    public function test_the_chat_offers_a_ready_program_and_the_first_build_starts_from_it(): void
    {
        $this->oneStarter();
        $this->answerWithStarter('clinic-book', 40000);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز نظام لعيادتي'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        $this->assertSame('clinic-book', $project->starter);
        $this->assertStringContainsString('`clinic-book` (system)', app(ChatModel::class)->lastSystem);
        $card = collect($this->getJson('/ai/'.$uuid.'/state')->json('messages'))->last()['starter'];
        $this->assertSame('clinic-book', $card['slug']);
        $this->assertNotEmpty($card['features']);

        $project->update(['status' => BuilderProject::QUEUED, 'paid_at' => now()]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()->assertJsonPath('job.starter', 'clinic-book');
    }

    public function test_an_unknown_or_dropped_ready_program_is_not_kept(): void
    {
        $this->oneStarter();
        $this->answerWithStarter('clinic-book');
        $this->answerWithStarter('');
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز نظام لعيادتي'])->assertOk()->json('uuid');
        $this->assertSame('clinic-book', BuilderProject::where('uuid', $uuid)->value('starter'));

        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'لا عايزه من الصفر'])->assertOk();
        $this->assertNull(BuilderProject::where('uuid', $uuid)->value('starter'));

        $this->assertNull(app(\Modules\AppBuilder\Services\Starters::class)->find('../secret'));
    }

    public function test_a_big_ordinary_job_builds_on_sonnet_and_only_a_hard_one_on_opus(): void
    {
        $pricer = app(\Modules\AppBuilder\Services\QuotePricer::class);

        $this->assertSame('claude-sonnet-5-5', $pricer->price(200000, complex: false)['model']);
        $this->assertSame('claude-opus-5-5', $pricer->price(20000, complex: true)['model']);
    }

    /** The card writer is the only outside part replaced: a fixed card, as Claude would write it. */
    private function learnedCards(): void
    {
        $this->app->instance(\Modules\AppBuilder\Services\Starters\StarterCardWriter::class, new class implements \Modules\AppBuilder\Services\Starters\StarterCardWriter
        {
            public function write(string $brief): array
            {
                return ['title' => 'Restaurant menu page', 'summary' => 'A menu page for a restaurant.', 'features' => ['Menu with prices', 'WhatsApp order button']];
            }
        });
    }

    public function test_a_paid_delivery_becomes_an_internal_ready_program_at_its_price(): void
    {
        $this->learnedCards();
        $paid = $this->deliveredProject();
        $paid->update(['paid_total' => 120]);
        $free = $this->deliveredProject();
        $free->update(['paid_total' => 0]);

        $this->assertSame(1, app(\Modules\AppBuilder\Services\Starters\LearnedStarters::class)->learnFromDeliveries());
        $starter = \Modules\AppBuilder\Models\BuilderStarter::sole();
        $this->assertSame($paid->id, $starter->source_project_id);
        $this->assertSame('120.00', $starter->price);
        // learned once per delivered version; a program the owner removed never comes back
        $this->assertSame(0, app(\Modules\AppBuilder\Services\Starters\LearnedStarters::class)->learnFromDeliveries());
        $starter->delete();
        $paid->update(['version' => 2]);
        $this->assertSame(0, app(\Modules\AppBuilder\Services\Starters\LearnedStarters::class)->learnFromDeliveries());
    }

    public function test_an_internal_ready_program_keeps_its_price_is_never_shown_and_the_build_starts_from_its_code(): void
    {
        $this->learnedCards();
        $source = $this->deliveredProject();
        $source->update(['paid_total' => 120]);
        app(\Modules\AppBuilder\Services\Starters\LearnedStarters::class)->learnFromDeliveries();
        $slug = \Modules\AppBuilder\Models\BuilderStarter::sole()->slug;

        $this->answerWithStarter($slug, 12000);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز منيو لمطعمي'])->assertOk()->json('uuid');
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();

        $this->assertStringContainsString("`{$slug}` (other; internal, never mention it; the price starts at 120", app(ChatModel::class)->lastSystem);
        $this->assertSame('120.00', $project->amount);
        $this->assertNull(collect($this->getJson('/ai/'.$uuid.'/state')->json('messages'))->last()['starter']);

        $project->update(['status' => BuilderProject::QUEUED, 'paid_at' => now()]);
        $this->postJson('/api/builder/worker/claim', [], $this->worker())->assertOk()
            ->assertJsonPath('job.starter', $slug)->assertJsonPath('job.starter_source', $source->uuid);
    }

    public function test_a_customer_without_a_design_picks_a_free_one_from_pictures_and_the_build_starts_from_it(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'designs');
        file_put_contents($file, json_encode([
            ['slug' => 'design-cafe-warm', 'category' => 'restaurant', 'title_en' => 'Warm cafe', 'title_ar' => 'كافيه دافي', 'summary_en' => 'Light, warm browns.', 'summary_ar' => 'فاتح وألوان بني دافية.', 'features_en' => ['hero', 'menu', 'contact'], 'features_ar' => ['الواجهة', 'المنيو', 'التواصل']],
            ['slug' => 'design-dark-bold', 'category' => 'agency', 'title_en' => 'Dark bold', 'title_ar' => 'داكن جريء', 'summary_en' => 'Dark, bold type.', 'summary_ar' => 'داكن بخط عريض.', 'features_en' => ['hero', 'services'], 'features_ar' => ['الواجهة', 'الخدمات']],
        ]));
        config(['builder.designs_file' => $file]);

        $this->script[] = ChatTurn::fromArray(['reply' => 'اختار تصميم يعجبك', 'needs_team' => false, 'quote' => ['ready' => false], 'starter' => '', 'designs' => ['design-cafe-warm', 'design-dark-bold']], 100, 50);
        $uuid = $this->postJson('/ai/messages', ['text' => 'عايز صفحة لكافيه ومعنديش تصميم'])->assertOk()->json('uuid');

        $this->assertStringContainsString('## Free designs', app(ChatModel::class)->lastSystem);
        $this->assertStringNotContainsString('`design-cafe-warm` (page', app(ChatModel::class)->lastSystem);
        $shown = collect($this->getJson('/ai/'.$uuid.'/state')->json('messages'))->last()['designs'];
        $this->assertSame(['design-cafe-warm', 'design-dark-bold'], array_column($shown, 'slug'));
        $this->assertSame('كافيه دافي', $shown[0]['title']);

        $this->answerWithStarter('design-cafe-warm', 12000);
        $this->postJson('/ai/'.$uuid.'/messages', ['text' => 'اخترت التصميم: كافيه دافي (design-cafe-warm)'])->assertOk();
        $project = BuilderProject::where('uuid', $uuid)->firstOrFail();
        $this->assertSame('design-cafe-warm', $project->starter);
        $this->assertSame([], collect($this->getJson('/ai/'.$uuid.'/state')->json('messages'))->last()['designs']);
    }
}
