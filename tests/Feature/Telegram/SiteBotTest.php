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
            // a picture someone sent the bot: where it is, then its bytes
            'api.telegram.org/bottest-token/getFile' => Http::response(['ok' => true, 'result' => ['file_path' => 'photos/p.jpg', 'file_size' => 70]]),
            'api.telegram.org/file/bottest-token/photos/p.jpg' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')),
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

    private function admin(): User
    {
        \Spatie\Permission\Models\Role::findOrCreate('admin');
        $this->user->assignRole('admin');
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        config(['builder.owner_approval' => true]);

        return $this->user;
    }

    private function waitingRequest(): \Modules\AppBuilder\Models\BuilderRequest
    {
        $project = \Modules\AppBuilder\Models\BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'ZainArena', 'status' => 'ready', 'version' => 1, 'warranty_until' => now()->addDays(60)]);

        return $project->requests()->create(['title' => 'قسم البلس', 'spec' => 'Plus section.', 'customer_words' => 'عايز قسم للبلس', 'kind' => 'build', 'amount' => 5, 'estimated_tokens' => 40000, 'budget_usd' => 3, 'model' => 'claude-sonnet-5-5', 'status' => 'unpaid']);
    }

    private function presses(string $data): void
    {
        $this->postJson('/api/telegram/webhook', ['update_id' => 2, 'callback_query' => ['id' => 'cb1', 'data' => $data, 'message' => ['chat' => ['id' => 788, 'type' => 'private']]]], [
            'X-Telegram-Bot-Api-Secret-Token' => app(TelegramApi::class)->webhookSecret(),
        ])->assertNoContent();
    }

    public function test_the_admin_sees_the_waiting_approvals_as_cards_with_buttons(): void
    {
        $this->admin();
        $request = $this->waitingRequest();

        $this->telegramSends('الموافقات');

        Http::assertSent(fn ($sent) => str_ends_with($sent->url(), '/sendMessage') && str_contains($sent['text'], 'عايز قسم للبلس')
            && collect($sent['reply_markup']['inline_keyboard'])->flatten(1)->pluck('callback_data')->contains('ap:r:'.$request->id));
    }

    public function test_the_admin_changes_the_price_then_approves_from_telegram(): void
    {
        $this->admin();
        $request = $this->waitingRequest();

        $this->presses('pr:r:'.$request->id);
        $this->telegramSends('١٧');
        $this->assertSame('17.00', $request->refresh()->amount);

        $this->presses('ap:r:'.$request->id);
        $this->assertNotNull($request->refresh()->approved_at);
    }

    public function test_the_admin_approves_for_free_or_turns_down(): void
    {
        $this->admin();
        $free = $this->waitingRequest();
        $this->presses('fr:r:'.$free->id);
        $this->assertTrue($free->refresh()->free);
        $this->assertNotNull($free->approved_at);

        $other = $this->waitingRequest();
        $this->presses('rj:r:'.$other->id);
        $this->assertSoftDeleted($other);
    }

    public function test_a_button_from_someone_who_is_not_an_admin_does_nothing(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        $request = $this->waitingRequest();

        $this->presses('ap:r:'.$request->id);

        $this->assertNull($request->refresh()->approved_at);
    }

    private function customersProject(): \Modules\AppBuilder\Models\BuilderProject
    {
        $this->app->instance(\Modules\AppBuilder\Services\Chat\ChatModel::class, new class implements \Modules\AppBuilder\Services\Chat\ChatModel
        {
            public function reply(string $system, array $turns): \Modules\AppBuilder\Services\Chat\ChatTurn
            {
                return \Modules\AppBuilder\Services\Chat\ChatTurn::fromArray(['reply' => 'تمام، فهمت الصورة. **قسم البلس** هيتعمل.', 'quote' => []]);
            }
        });
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();

        return \Modules\AppBuilder\Models\BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'ZainArena', 'status' => 'chatting', 'user_id' => $this->user->id]);
    }

    public function test_the_customer_talks_to_a_projects_ai_inside_telegram(): void
    {
        $project = $this->customersProject();

        $this->presses('ch:'.$project->id);
        $this->telegramSends('عايز قسم للبلس في لوحة التحكم');

        $this->assertSame('عايز قسم للبلس في لوحة التحكم', $project->messages()->where('role', 'user')->value('content'));
        // the AI's answer came back to Telegram, as plain text
        $this->assertContains('تمام، فهمت الصورة. قسم البلس هيتعمل.', $this->sentText());
    }

    public function test_a_picture_sent_in_telegram_reaches_the_chat_as_an_attachment(): void
    {
        $project = $this->customersProject();
        $this->presses('ch:'.$project->id);

        $this->postJson('/api/telegram/webhook', ['update_id' => 3, 'message' => ['chat' => ['id' => 788, 'type' => 'private'], 'caption' => 'زي دي', 'photo' => [['file_id' => 'small'], ['file_id' => 'big']]]], [
            'X-Telegram-Bot-Api-Secret-Token' => app(TelegramApi::class)->webhookSecret(),
        ])->assertNoContent();

        $question = $project->messages()->where('role', 'user')->firstOrFail();
        $this->assertSame('زي دي', $question->content);
        $this->assertSame(1, $question->attachments()->count());
    }

    public function test_someone_cannot_enter_another_accounts_chat(): void
    {
        $project = $this->customersProject();
        $project->update(['user_id' => User::factory()->create()->id]);

        $this->presses('ch:'.$project->id);
        $this->telegramSends('ورّيني المحادثة');

        $this->assertSame(0, $project->messages()->count());
    }

    public function test_the_customer_sees_balance_tickets_and_the_invite_link(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788', 'loyalty_points_balance' => 120])->save();
        \App\Models\Ticket::create(['user_id' => $this->user->id, 'ticket_subject' => 'البرنامج مش بيفتح', 'ticket_message' => 'x', 'ticket_status' => 'agent_replied']);

        $this->telegramSends('رصيدي');
        $this->telegramSends('تذاكري');
        $this->telegramSends('ادعي صاحبك');

        [$balance, $tickets, $invite] = $this->sentText();
        $this->assertStringContainsString('120', $balance);
        $this->assertStringContainsString('البرنامج مش بيفتح · الفريق رد', $tickets);
        $this->assertStringContainsString('/r/', $invite);
        $this->assertStringContainsString('/ai', $invite);
    }

    public function test_settings_stop_the_alerts_and_switch_the_language(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();

        $this->telegramSends('الإعدادات');
        $this->presses('al:t');
        $this->assertTrue($this->user->refresh()->telegram_alerts_off);

        $this->presses('lg:en');
        $this->assertSame('en', $this->user->refresh()->telegram_locale);
        // the menu comes back in English, and the old Arabic button still works
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage') && ($request['reply_markup']['keyboard'][0][0]['text'] ?? '') === 'My invoices');
        $this->telegramSends('رصيدي');
        $this->assertStringContainsString('Your balance on the site', collect($this->sentText())->last());
    }

    public function test_build_news_reaches_the_linked_telegram_unless_alerts_are_off(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        $project = \Modules\AppBuilder\Models\BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'ZainArena', 'status' => 'building', 'user_id' => $this->user->id]);

        app(\Modules\AppBuilder\Services\BuilderPush::class)->notify($project, 'delivered');
        $this->assertStringContainsString('ZainArena اتسلّم', collect($this->sentText())->last());

        $this->user->forceFill(['telegram_alerts_off' => true])->save();
        $before = count($this->sentText());
        app(\Modules\AppBuilder\Services\BuilderPush::class)->notify($project->refresh(), 'question');
        $this->assertCount($before, $this->sentText());
    }

    public function test_a_project_being_built_shows_how_far_it_is(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        \Modules\AppBuilder\Models\BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'منيو المطعم', 'status' => 'building', 'user_id' => $this->user->id, 'progress_percent' => 83,
            'progress_phases' => ['hash' => 'h', 'items' => [['title' => 'الأساس', 'done' => true], ['title' => 'الشاشات الأخيرة', 'done' => false]]]]);

        $this->telegramSends('مشاريعي');

        $text = collect($this->sentText())->last();
        $this->assertStringContainsString('83%', $text);
        $this->assertStringContainsString('الشاشات الأخيرة', $text);
    }

    public function test_an_unpaid_invoice_is_mentioned_once_every_few_days(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        $invoice = Invoice::create(['user_id' => $this->user->id, 'status' => 'unpaid']);
        $invoice->forceFill(['created_at' => now()->subDays(2)])->save();
        \Illuminate\Support\Facades\Cache::store('file')->forget('telegram_invoice_reminder_'.$invoice->id);
        $reminders = app(\App\Services\Telegram\InvoiceReminders::class);

        $this->assertSame(1, $reminders->sendDue());
        $this->assertSame(0, $reminders->sendDue());
        $this->assertStringContainsString('فاتورة #'.$invoice->id.' لسه مش مدفوعة', collect($this->sentText())->last());
        \Illuminate\Support\Facades\Cache::store('file')->forget('telegram_invoice_reminder_'.$invoice->id);
    }

    public function test_the_customer_hears_when_the_team_answers_a_ticket(): void
    {
        $this->user->forceFill(['telegram_chat_id' => '788'])->save();
        $ticket = \App\Models\Ticket::create(['user_id' => $this->user->id, 'ticket_subject' => 'الدفع', 'ticket_message' => 'x', 'ticket_status' => 'open']);

        $ticket->update(['ticket_status' => 'agent_replied']);

        $this->assertStringContainsString("تذكرتك #{$ticket->id} (الدفع)\nالحالة دلوقتي: الفريق رد", collect($this->sentText())->last());
    }
}
