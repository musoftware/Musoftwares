<?php

namespace Tests\Feature\Telegram;

use App\Models\Invoice;
use App\Models\User;
use App\Services\AiAssistant\AssistantModel;
use App\Services\Telegram\TelegramApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The site's Telegram bot: an account links it from the settings page (Start in the bot), then sees its invoices and
 * asks the site's AI assistant there. A chat that is not linked sees nothing about any account.
 */
class SiteBotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.site_telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/bottest-token/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'musoftwarescom_bot']]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);
        $this->app->instance(AssistantModel::class, new class implements AssistantModel
        {
            public function reply(string $system, array $messages, array $tools): array
            {
                return ['text' => 'الفاتورة بتتدفع من صفحة الفواتير.', 'tool_calls' => [], 'content' => []];
            }
        });
        $this->user = User::factory()->create(['email_verified_at' => now(), 'lang' => 'ar']);
    }

    private function telegramSends(string $text, int $chatId = 788): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/telegram/webhook', ['update_id' => 1, 'message' => ['chat' => ['id' => $chatId, 'type' => 'private'], 'text' => $text]], [
            'X-Telegram-Bot-Api-Secret-Token' => app(TelegramApi::class)->webhookSecret(),
        ]);
    }

    private function sentText(): array
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn ($request) => str_ends_with($request->url(), '/sendMessage'))->map(fn ($request) => $request['text'])->values()->all();
    }

    public function test_start_with_the_settings_code_links_the_account_and_shows_the_menu(): void
    {
        $url = $this->actingAs($this->user)->postJson('/settings/telegram/link')->assertOk()->json('url');
        $code = substr($url, strlen('https://t.me/musoftwarescom_bot?start='));

        $this->telegramSends('/start '.$code)->assertNoContent();

        $this->assertSame('788', $this->user->refresh()->telegram_chat_id);
        $this->actingAs($this->user)->getJson('/settings/telegram')->assertJsonPath('linked', true);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage') && isset($request['reply_markup']['keyboard']));
    }

    public function test_a_linked_account_sees_its_invoices_and_asks_the_assistant(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        Invoice::create(['user_id' => $this->user->id, 'status' => 'unpaid']);

        $this->telegramSends('فواتيري')->assertNoContent();
        $this->telegramSends('ازاي ادفع الفاتورة؟')->assertNoContent();

        $sent = $this->sentText();
        $this->assertStringContainsString('آخر فواتيرك', $sent[0]);
        $this->assertSame('الفاتورة بتتدفع من صفحة الفواتير.', $sent[1]);
    }

    public function test_a_chat_that_is_not_linked_sees_nothing_and_a_wrong_signature_is_refused(): void
    {
        Invoice::create(['user_id' => $this->user->id, 'status' => 'unpaid']);

        $this->telegramSends('فواتيري', 999)->assertNoContent();
        $this->postJson('/api/telegram/webhook', ['message' => ['chat' => ['id' => 1, 'type' => 'private'], 'text' => 'hi']], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();

        $this->assertCount(1, $this->sentText());
        $this->assertStringContainsString('اربط حسابك', $this->sentText()[0]);
    }
}
