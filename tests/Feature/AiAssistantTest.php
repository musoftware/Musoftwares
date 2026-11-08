<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\AiAssistant\AssistantModel;
use App\Services\IpGeolocationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    private FakeAssistantModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.assistant_limiter_store' => 'array']);
        Cache::store('array')->flush();

        $this->model = new FakeAssistantModel;
        $this->app->instance(AssistantModel::class, $this->model);
    }

    private function ask(string $message = 'hello')
    {
        return $this->postJson('/assistant/message', ['message' => $message]);
    }

    private function clientOwing(float $amount): User
    {
        $currency = Currency::firstOrCreate(['currency' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']);
        $user = User::factory()->create(['currency_id' => $currency->id]);
        $user->assignRole('client');
        $invoice = Invoice::create(['user_id' => $user->id, 'currency_id' => $currency->id, 'paid' => 0, 'status' => 'unpaid', 'is_suspended' => false, 'due_date' => now()->addDay()->toDateString()]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Work', 'amount' => $amount]);
        $invoice->updateCachedTotal();

        return $user;
    }

    public function test_a_guest_gets_a_reply_and_cannot_use_the_account_tool(): void
    {
        $this->model->replies = [['text' => 'Welcome', 'tool_calls' => [], 'content' => []]];

        $this->ask()->assertOk()->assertJson(['reply' => 'Welcome']);

        $this->assertSame(['get_project_price_guide'], array_column($this->model->calls[0]['tools'], 'name'));
        $this->assertStringContainsString('not signed in', $this->model->calls[0]['system']);
    }

    public function test_the_account_tool_returns_only_the_signed_in_clients_money(): void
    {
        $this->clientOwing(777);
        $client = $this->clientOwing(123);
        $this->model->replies = [
            ['text' => '', 'tool_calls' => [['id' => 'call_1', 'name' => 'get_my_account']], 'content' => ['tool-use-block']],
            ['text' => 'You owe 123', 'tool_calls' => [], 'content' => []],
        ];

        $this->actingAs($client);
        $this->ask('do I owe money?')->assertOk()->assertJson(['reply' => 'You owe 123']);

        $toolResult = json_encode($this->model->calls[1]['messages'][2]['content'], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('123', $toolResult);
        $this->assertStringNotContainsString('777', $toolResult);
    }

    public function test_the_account_tool_is_refused_for_a_guest_even_if_the_model_asks(): void
    {
        $this->model->replies = [
            ['text' => '', 'tool_calls' => [['id' => 'call_1', 'name' => 'get_my_account']], 'content' => []],
            ['text' => 'Please sign in', 'tool_calls' => [], 'content' => []],
        ];

        $this->ask()->assertOk();

        $this->assertStringContainsString('not available', json_encode($this->model->calls[1]['messages'][2]['content']));
    }

    public function test_the_price_guide_gives_prices_in_pounds(): void
    {
        $this->model->replies = [
            ['text' => '', 'tool_calls' => [['id' => 'call_1', 'name' => 'get_project_price_guide']], 'content' => []],
            ['text' => 'About 5000', 'tool_calls' => [], 'content' => []],
        ];

        $this->ask()->assertOk();

        $this->assertStringContainsString('price_egp_per_unit', json_encode($this->model->calls[1]['messages'][2]['content']));
    }

    public function test_the_conversation_is_kept_as_text_and_can_be_reset(): void
    {
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []], ['text' => 'Two', 'tool_calls' => [], 'content' => []]];

        $this->ask('first')->assertOk();
        $this->ask('second')->assertOk();

        $this->assertCount(3, $this->model->calls[1]['messages']);
        $this->getJson('/assistant/history')->assertJsonCount(4, 'messages');

        $this->postJson('/assistant/reset')->assertOk();
        $this->getJson('/assistant/history')->assertJsonCount(0, 'messages');
    }

    public function test_too_many_messages_are_stopped_before_the_model_is_called(): void
    {
        config(['services.anthropic.assistant_visitor_per_minute' => 1]);
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []]];

        $this->ask()->assertOk();
        $this->ask()->assertStatus(429);

        $this->assertCount(1, $this->model->calls);
    }

    public function test_a_model_failure_gives_a_friendly_message_with_whatsapp(): void
    {
        $this->model->failure = new RuntimeException('connection lost');

        $this->ask()->assertStatus(503)->assertJsonPath('reply', fn (string $reply) => str_contains($reply, 'wa.me'));
    }

    public function test_an_empty_or_long_message_is_rejected(): void
    {
        $this->ask('')->assertStatus(422);
        $this->ask(str_repeat('a', 501))->assertStatus(422);
        $this->assertCount(0, $this->model->calls);
    }

    public function test_clearing_cookies_does_not_reset_the_limit_for_the_same_ip(): void
    {
        config(['services.anthropic.assistant_ip_per_day' => 1]);
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []]];
        $this->withCredentials(); // JSON test requests send cookies only with this

        $this->withServerVariables(['REMOTE_ADDR' => '41.32.0.5'])->ask('first')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '41.32.0.5'])->withCookie('ai_assistant_device', 'c56a4180-65aa-42ec-a945-5fd21dec0538')->ask('second')->assertStatus(429);

        $this->assertCount(1, $this->model->calls);
    }

    public function test_changing_ip_does_not_reset_the_limit_for_the_same_device(): void
    {
        config(['services.anthropic.assistant_visitor_per_day' => 1]);
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []]];
        $device = 'c56a4180-65aa-42ec-a945-5fd21dec0538';
        $this->withCredentials(); // JSON test requests send cookies only with this

        $this->withServerVariables(['REMOTE_ADDR' => '41.32.0.6'])->withCookie('ai_assistant_device', $device)->ask('first')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '197.32.0.6'])->withCookie('ai_assistant_device', $device)->ask('second')->assertStatus(429);

        $this->assertCount(1, $this->model->calls);
    }

    public function test_a_new_visitor_gets_a_device_cookie(): void
    {
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []]];

        $this->ask()->assertOk()->assertCookie('ai_assistant_device');
    }

    public function test_the_same_message_again_does_not_call_the_model(): void
    {
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []]];

        $this->ask('same')->assertOk()->assertJson(['reply' => 'One']);
        $this->ask('same')->assertOk()->assertJson(['reply' => 'One']);

        $this->assertCount(1, $this->model->calls);
    }

    public function test_one_address_cannot_get_around_the_limit_with_many_accounts(): void
    {
        config(['services.anthropic.assistant_ip_per_day' => 1]);
        $this->model->replies = [['text' => 'One', 'tool_calls' => [], 'content' => []]];

        $this->actingAs(User::factory()->create());
        $this->ask('first')->assertOk();
        $this->actingAs(User::factory()->create());
        $this->ask('second')->assertStatus(429);

        $this->assertCount(1, $this->model->calls);
    }

    public function test_the_button_shows_on_the_site_only_when_a_key_exists(): void
    {
        $this->get('/?lang=ar')->assertOk()->assertSee('id="ai-assistant"', false)->assertSee('اسأل المساعد');

        config(['services.anthropic.key' => null]);
        $this->get('/?lang=ar')->assertOk()->assertDontSee('id="ai-assistant"', false);
    }

    public function test_visitors_in_egypt_do_not_see_the_english_link_in_the_footer(): void
    {
        $this->app->instance(IpGeolocationService::class, new class extends IpGeolocationService
        {
            public function getCountryFromIp($ip)
            {
                return 'EG';
            }
        });

        $this->withServerVariables(['REMOTE_ADDR' => '41.32.0.1'])->get('/')->assertOk()->assertDontSee('lang=en', false);
    }
}

class FakeAssistantModel implements AssistantModel
{
    /** @var list<array<string, mixed>> */
    public array $replies = [];

    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public ?RuntimeException $failure = null;

    public function reply(string $system, array $messages, array $tools): array
    {
        $this->calls[] = compact('system', 'messages', 'tools');

        if ($this->failure) {
            throw $this->failure;
        }

        return array_shift($this->replies) ?? ['text' => '', 'tool_calls' => [], 'content' => []];
    }
}
