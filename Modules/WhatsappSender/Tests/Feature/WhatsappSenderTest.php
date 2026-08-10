<?php

namespace Modules\WhatsappSender\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappLog;
use Tests\TestCase;

class WhatsappSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_dashboard_requires_auth()
    {
        $response = $this->get('/whatsapp-sender');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_whatsapp_dashboard()
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'object' => 'whatsapp-sender',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->get('/whatsapp-sender');
        $response->assertStatus(200);
    }

    public function test_user_can_store_whatsapp_account_credentials()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/whatsapp-sender/accounts', [
            'name' => 'Test Business WhatsApp',
            'phone_number_id' => '102938475610293',
            'waba_id' => '9876543210',
            'access_token' => 'EAATestAccessTokenString',
        ]);

        $response->assertRedirect('/whatsapp-sender');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'user_id' => $user->id,
            'name' => 'Test Business WhatsApp',
            'phone_number_id' => '102938475610293',
        ]);
    }

    public function test_user_can_update_whatsapp_account()
    {
        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'phone_number_id' => '111222333',
            'waba_id' => '444555666',
            'access_token' => 'old_token',
        ]);

        $response = $this->actingAs($user)->put('/whatsapp-sender/accounts/' . $account->id, [
            'name' => 'Updated Name',
            'phone_number_id' => '999888777',
            'waba_id' => '777666555',
            'access_token' => 'new_token',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'name' => 'Updated Name',
            'phone_number_id' => '999888777',
        ]);
    }

    public function test_user_can_delete_whatsapp_account()
    {
        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'To Delete Account',
            'phone_number_id' => '999888777',
            'access_token' => 'dummy',
        ]);

        $response = $this->actingAs($user)->delete('/whatsapp-sender/accounts/' . $account->id);

        $response->assertRedirect('/whatsapp-sender');
        $this->assertDatabaseMissing('whatsapp_accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_user_can_test_whatsapp_account_connection()
    {
        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'Sandbox Test Account',
            'phone_number_id' => '114811102562039',
            'waba_id' => '109283748291029',
            'access_token' => 'SANDBOX_TOKEN_DEMO',
        ]);

        $response = $this->actingAs($user)->post('/whatsapp-sender/accounts/' . $account->id . '/test');

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('meta_response');
    }

    public function test_api_requires_sanctum_authentication()
    {
        $response = $this->postJson('/api/v1/whatsapp/send', [
            'recipient_phone' => '201001234567',
            'message_body' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_api_send_validates_e164_recipient_phone()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'recipient_phone' => 'invalid-phone-abc',
            'message_body' => 'Hello World',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'error',
            ]);
    }

    public function test_api_send_fails_gracefully_when_no_active_account()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'recipient_phone' => '201001234567',
            'message_body' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'error',
            ]);
    }

    public function test_api_accounts_returns_connected_user_accounts()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'API Account 1',
            'phone_number_id' => '111222333',
            'access_token' => 'token123',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/whatsapp/accounts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);
    }

    public function test_user_can_register_whatsapp_account_with_pin()
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*/register' => \Illuminate\Support\Facades\Http::response(['success' => true], 200),
        ]);

        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'To Register Account',
            'phone_number_id' => '123456789',
            'access_token' => 'EAATestToken',
            'status' => 'unregistered',
        ]);

        $response = $this->actingAs($user)->post("/whatsapp-sender/accounts/{$account->id}/register", [
            'pin' => '123456',
        ]);

        $response->assertRedirect('/whatsapp-sender');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_sync_whatsapp_account_status()
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response([
                'id' => '123456789',
                'verified_name' => 'Test Phone',
                'display_phone_number' => '+123456789',
                'quality_rating' => 'GREEN',
                'status' => 'CONNECTED',
            ], 200),
        ]);

        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'To Sync Account',
            'phone_number_id' => '123456789',
            'access_token' => 'EAATestToken',
            'status' => 'unregistered',
        ]);

        $response = $this->actingAs($user)->post("/whatsapp-sender/accounts/{$account->id}/sync");

        $response->assertRedirect('/whatsapp-sender');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'status' => 'active',
        ]);

        $updatedAccount = WhatsappAccount::find($account->id);
        $this->assertEquals('CONNECTED', $updatedAccount->metadata['status']);
    }

    public function test_guest_client_can_access_landing_page()
    {
        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Guest test biz',
            'wallet_balance' => 10.0,
            'currency' => 'USD',
            'per_message_fee' => 0.001,
        ]);

        $response = $this->get("/whatsapp-sender/guest/connect/{$business->uuid}");
        $response->assertStatus(200);
    }

    public function test_user_can_create_template()
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*/message_templates' => \Illuminate\Support\Facades\Http::response([
                'id' => '1122334455',
                'status' => 'APPROVED',
            ], 200),
        ]);

        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Test templates biz',
            'wallet_balance' => 10.0,
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Active Account',
            'phone_number_id' => '123456',
            'waba_id' => '7890',
            'access_token' => 'dummy_token',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/whatsapp-sender/templates', [
            'whatsapp_business_id' => $business->id,
            'name' => 'welcome_back_coupon',
            'category' => 'UTILITY',
            'language' => 'en_US',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'Hello {{1}}, here is your code {{2}}',
                ]
            ],
        ]);

        $response->assertRedirect('/whatsapp-sender');
        $this->assertDatabaseHas('whatsapp_templates', [
            'whatsapp_business_id' => $business->id,
            'name' => 'welcome_back_coupon',
            'status' => 'APPROVED',
        ]);
    }

    public function test_user_can_create_contact_group_and_import_contacts()
    {
        $user = User::factory()->create();

        // 1. Create contact group
        $response = $this->actingAs($user)->post('/whatsapp-sender/contact-groups', [
            'name' => 'Test list group',
            'description' => 'A descriptive segment',
        ]);

        $response->assertRedirect('/whatsapp-sender');
        $group = \Modules\WhatsappSender\Models\WhatsappContactGroup::first();
        $this->assertEquals('Test list group', $group->name);

        // 2. Import contacts manually
        $response2 = $this->actingAs($user)->post("/whatsapp-sender/contact-groups/{$group->id}/contacts", [
            'contacts_text' => "201001234567,John Doe\n201009876543,Alice Smith",
        ]);

        $response2->assertRedirect('/whatsapp-sender');
        $this->assertDatabaseHas('whatsapp_contacts', [
            'whatsapp_contact_group_id' => $group->id,
            'phone' => '201001234567',
            'name' => 'John Doe',
        ]);
        $this->assertDatabaseHas('whatsapp_contacts', [
            'whatsapp_contact_group_id' => $group->id,
            'phone' => '201009876543',
            'name' => 'Alice Smith',
        ]);
    }

    public function test_user_can_schedule_individual_message_in_cairo_time()
    {
        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Scheduling biz',
            'wallet_balance' => 10.0,
        ]);
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Sender',
            'phone_number_id' => '123456',
            'access_token' => 'dummy',
            'status' => 'active',
        ]);

        // Cairo time target is in the future
        $cairoFutureTime = \Carbon\Carbon::now('Africa/Cairo')->addHours(2);
        
        $response = $this->actingAs($user)->post('/whatsapp-sender/schedules', [
            'whatsapp_business_id' => $business->id,
            'whatsapp_account_id' => $account->id,
            'recipient_phone' => '201009999999',
            'message_type' => 'text',
            'message_body' => 'Scheduled body text',
            'scheduled_at' => $cairoFutureTime->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect("/whatsapp-sender/businesses/{$business->id}");
        $this->assertDatabaseHas('whatsapp_schedules', [
            'user_id' => $user->id,
            'recipient_phone' => '201009999999',
            'status' => 'pending',
        ]);
    }

    public function test_process_scheduled_messages_command_executes_pending_schedules()
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['messages' => [['id' => 'meta-123']]], 200),
        ]);

        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Execution biz',
            'wallet_balance' => 10.0,
        ]);
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Sender',
            'phone_number_id' => '123456',
            'access_token' => 'dummy',
            'status' => 'active',
        ]);

        // Create schedule set in past
        $schedule = \Modules\WhatsappSender\Models\WhatsappSchedule::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'whatsapp_account_id' => $account->id,
            'recipient_phone' => '201009999999',
            'message_type' => 'text',
            'message_body' => 'Body text',
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $exitCode = $this->artisan('whatsapp:process-scheduled');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('whatsapp_schedules', [
            'id' => $schedule->id,
            'status' => 'sent',
        ]);
    }

    public function test_api_endpoints_for_templates_groups_and_scheduling()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'API features biz',
            'wallet_balance' => 10.0,
        ]);

        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Sender',
            'phone_number_id' => '123456',
            'access_token' => 'dummy',
            'status' => 'active',
        ]);

        // Get templates API
        $response = $this->getJson('/api/v1/whatsapp/templates');
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Get groups API
        $response2 = $this->getJson('/api/v1/whatsapp/groups');
        $response2->assertStatus(200)
            ->assertJson(['success' => true]);

        // Schedule API
        $cairoFutureTime = \Carbon\Carbon::now('Africa/Cairo')->addHours(3);
        $response3 = $this->postJson('/api/v1/whatsapp/schedule', [
            'whatsapp_account_id' => $account->id,
            'recipient_phone' => '201008888888',
            'message_type' => 'text',
            'message_body' => 'API scheduled text',
            'scheduled_at' => $cairoFutureTime->format('Y-m-d H:i'),
        ]);

        $response3->assertStatus(201)
            ->assertJson([
                'success' => true,
                'status' => 'scheduled',
            ]);
    }

    public function test_user_can_search_businesses_directory()
    {
        $user = User::factory()->create();
        \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Acme Corporate',
            'client_name' => 'John Wick',
            'wallet_balance' => 50.0,
        ]);
        \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Beta Industry',
            'client_name' => 'Sara Connor',
            'wallet_balance' => 30.0,
        ]);

        $response = $this->actingAs($user)->get('/whatsapp-sender?search=Acme');
        $response->assertStatus(200);
        $response->assertSee('Acme Corporate');
        $response->assertDontSee('Beta Industry');
    }

    public function test_user_can_view_workspace_page()
    {
        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Workspace Biz',
            'wallet_balance' => 20.0,
        ]);

        $response = $this->actingAs($user)->get("/whatsapp-sender/businesses/{$business->id}");
        $response->assertStatus(200);
    }

    public function test_user_can_register_telegram_bot()
    {
        \Illuminate\Support\Facades\Http::fake([
            'api.telegram.org/botdummy_token/getMe' => \Illuminate\Support\Facades\Http::response([
                'ok' => true,
                'result' => [
                    'first_name' => 'TestBot',
                    'username' => 'musoftware_test_bot',
                ]
            ], 200),
            'api.telegram.org/botdummy_token/setWebhook' => \Illuminate\Support\Facades\Http::response([
                'ok' => true,
            ], 200),
        ]);

        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Telegram Biz',
            'wallet_balance' => 10.0,
        ]);

        $response = $this->actingAs($user)->post('/whatsapp-sender/telegram-bots', [
            'whatsapp_business_id' => $business->id,
            'token' => 'dummy_token',
        ]);

        $response->assertRedirect("/whatsapp-sender/businesses/{$business->id}");
        $this->assertDatabaseHas('telegram_bots', [
            'whatsapp_business_id' => $business->id,
            'username' => 'musoftware_test_bot',
        ]);
    }

    public function test_telegram_webhook_creates_contact_subscribers()
    {
        \Illuminate\Support\Facades\Http::fake();

        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Telegram Webhook Biz',
            'wallet_balance' => 10.0,
        ]);
        $bot = \Modules\WhatsappSender\Models\TelegramBot::create([
            'whatsapp_business_id' => $business->id,
            'name' => 'Botty',
            'username' => 'botty_bot',
            'token' => 'botty_token',
            'status' => 'active',
        ]);

        // Trigger webhook simulation
        $response = $this->postJson("/api/v1/telegram/webhook/{$bot->id}", [
            'message' => [
                'chat' => [
                    'id' => 99887766,
                    'first_name' => 'Tony',
                    'last_name' => 'Stark',
                    'username' => 'ironman',
                ],
                'text' => '/start',
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('telegram_subscribers', [
            'telegram_bot_id' => $bot->id,
            'chat_id' => '99887766',
            'first_name' => 'Tony',
            'last_name' => 'Stark',
            'username' => 'ironman',
        ]);
    }

    public function test_user_can_schedule_telegram_message()
    {
        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Telegram Scheduling Biz',
            'wallet_balance' => 15.0,
        ]);
        $bot = \Modules\WhatsappSender\Models\TelegramBot::create([
            'whatsapp_business_id' => $business->id,
            'name' => 'Botty',
            'token' => 'token',
            'status' => 'active',
        ]);

        $cairoFutureTime = \Carbon\Carbon::now('Africa/Cairo')->addHours(4);

        $response = $this->actingAs($user)->post('/whatsapp-sender/schedules', [
            'whatsapp_business_id' => $business->id,
            'telegram_bot_id' => $bot->id,
            'recipient_phone' => '99887766',
            'channel' => 'telegram',
            'message_type' => 'text',
            'message_body' => 'Future telegram message',
            'scheduled_at' => $cairoFutureTime->format('Y-m-d H:i'),
        ]);

        $response->assertRedirect("/whatsapp-sender/businesses/{$business->id}");
        $this->assertDatabaseHas('whatsapp_schedules', [
            'telegram_bot_id' => $bot->id,
            'recipient_phone' => '99887766',
            'channel' => 'telegram',
            'status' => 'pending',
        ]);
    }

    public function test_telegram_webhook_triggers_chatbot_flow_and_charges_fee()
    {
        // Fake Telegram API call
        \Illuminate\Support\Facades\Http::fake([
            'api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Flow Testing Biz',
            'wallet_balance' => 10.0000,
            'bot_reply_fee' => 0.0005,
        ]);
        $bot = \Modules\WhatsappSender\Models\TelegramBot::create([
            'whatsapp_business_id' => $business->id,
            'name' => 'FlowBot',
            'username' => 'flow_bot',
            'token' => 'flow_bot_token',
            'status' => 'active',
        ]);

        // Create a chatbot flow: Trigger on keyword "help" -> Send message
        $flow = \Modules\WhatsappSender\Models\BotFlow::create([
            'whatsapp_business_id' => $business->id,
            'channel' => 'telegram',
            'telegram_bot_id' => $bot->id,
            'name' => 'Help Flow',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_keywords' => ['help', 'support'],
            'nodes' => [
                ['id' => 'node_trigger', 'type' => 'trigger', 'data' => []],
                ['id' => 'node_msg_1', 'type' => 'message', 'data' => ['message_text' => 'How can I assist you?']],
            ],
            'edges' => [
                ['source' => 'node_trigger', 'target' => 'node_msg_1', 'sourceHandle' => 'out', 'targetHandle' => 'in'],
            ],
        ]);

        // Trigger message webhook
        $response = $this->postJson("/api/v1/telegram/webhook/{$bot->id}", [
            'message' => [
                'chat' => [
                    'id' => 11223344,
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                ],
                'text' => 'help',
            ]
        ]);

        $response->assertStatus(200);

        // Verify balance was deducted by the reduced bot_reply_fee (0.0005)
        $business->refresh();
        $this->assertEquals(9.9995, (float) $business->wallet_balance);

        // Verify transaction and log entry
        $this->assertDatabaseHas('whatsapp_transactions', [
            'whatsapp_business_id' => $business->id,
            'amount' => 0.0005,
        ]);
        $this->assertDatabaseHas('whatsapp_logs', [
            'whatsapp_business_id' => $business->id,
            'telegram_bot_id' => $bot->id,
            'channel' => 'telegram',
            'message_body' => 'How can I assist you?',
            'cost_charged' => 0.0005,
        ]);
    }

    public function test_flow_engine_gates_execution_when_insufficient_balance()
    {
        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Broke Biz',
            'wallet_balance' => 0.0000,
            'bot_reply_fee' => 0.0005,
        ]);
        $bot = \Modules\WhatsappSender\Models\TelegramBot::create([
            'whatsapp_business_id' => $business->id,
            'name' => 'FlowBot',
            'token' => 'token',
            'status' => 'active',
        ]);

        $flow = \Modules\WhatsappSender\Models\BotFlow::create([
            'whatsapp_business_id' => $business->id,
            'channel' => 'telegram',
            'telegram_bot_id' => $bot->id,
            'name' => 'Help Flow',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_keywords' => ['help'],
            'nodes' => [
                ['id' => 'node_trigger', 'type' => 'trigger', 'data' => []],
                ['id' => 'node_msg_1', 'type' => 'message', 'data' => ['message_text' => 'Broke reply']],
            ],
            'edges' => [
                ['source' => 'node_trigger', 'target' => 'node_msg_1', 'sourceHandle' => 'out', 'targetHandle' => 'in'],
            ],
        ]);

        // Trigger message webhook
        $response = $this->postJson("/api/v1/telegram/webhook/{$bot->id}", [
            'message' => [
                'chat' => [
                    'id' => 11223344,
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                ],
                'text' => 'help',
            ]
        ]);

        $response->assertStatus(200);

        // Balance remains 0.0000 and no message logs are generated
        $business->refresh();
        $this->assertEquals(0.0000, (float) $business->wallet_balance);
        $this->assertDatabaseMissing('whatsapp_logs', [
            'whatsapp_business_id' => $business->id,
        ]);
    }

    public function test_telegram_callback_query_advances_flow_node()
    {
        \Illuminate\Support\Facades\Http::fake([
            'api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true, 'result' => ['message_id' => 12345]], 200),
        ]);

        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Flow Testing Biz',
            'wallet_balance' => 10.0000,
            'bot_reply_fee' => 0.0005,
        ]);
        $bot = \Modules\WhatsappSender\Models\TelegramBot::create([
            'whatsapp_business_id' => $business->id,
            'name' => 'FlowBot',
            'username' => 'flow_bot',
            'token' => 'flow_bot_token',
            'status' => 'active',
        ]);

        // Flow: Message 1 (has button pointing to Message 2)
        $flow = \Modules\WhatsappSender\Models\BotFlow::create([
            'whatsapp_business_id' => $business->id,
            'channel' => 'telegram',
            'telegram_bot_id' => $bot->id,
            'name' => 'Button Flow',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_keywords' => ['start'],
            'nodes' => [
                ['id' => 'node_trigger', 'type' => 'trigger', 'data' => []],
                ['id' => 'node_msg_1', 'type' => 'message', 'data' => ['message_text' => 'Option select', 'buttons' => [['label' => 'Choose', 'target_node_id' => 'node_msg_2', 'value' => 'Choose']]]],
                ['id' => 'node_msg_2', 'type' => 'message', 'data' => ['message_text' => 'Option selected successfully!']],
            ],
            'edges' => [
                ['source' => 'node_trigger', 'target' => 'node_msg_1', 'sourceHandle' => 'out', 'targetHandle' => 'in'],
            ],
        ]);

        // Send start to initiate session
        $this->postJson("/api/v1/telegram/webhook/{$bot->id}", [
            'message' => [
                'chat' => [
                    'id' => 55556666,
                    'first_name' => 'Clark',
                    'last_name' => 'Kent',
                ],
                'text' => 'start',
            ]
        ])->assertStatus(200);

        // Verify session is active and waiting at node_msg_1
        $this->assertDatabaseHas('bot_flow_sessions', [
            'telegram_bot_id' => $bot->id,
            'subscriber_identifier' => '55556666',
            'current_node_id' => 'node_msg_1',
        ]);

        // Send callback query (simulate clicking the button)
        $this->postJson("/api/v1/telegram/webhook/{$bot->id}", [
            'callback_query' => [
                'id' => 'cb_123',
                'from' => [
                    'id' => 55556666,
                    'first_name' => 'Clark',
                    'last_name' => 'Kent',
                ],
                'data' => 'node_msg_2', // Node target
            ]
        ])->assertStatus(200);

        // Flow completes: Option selected message is sent, session is deleted
        $this->assertDatabaseMissing('bot_flow_sessions', [
            'subscriber_identifier' => '55556666',
        ]);

        // Log contains final message body
        $this->assertDatabaseHas('whatsapp_logs', [
            'whatsapp_business_id' => $business->id,
            'message_body' => 'Option selected successfully!',
        ]);
    }

    public function test_fetch_whatsapp_accounts_from_meta_token_discovers_accounts()
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/debug_token*' => \Illuminate\Support\Facades\Http::response([
                'data' => [
                    'granular_scopes' => [
                        [
                            'scope' => 'whatsapp_business_management',
                            'target_ids' => ['100200300400']
                        ]
                    ]
                ]
            ], 200),
            'https://graph.facebook.com/v21.0/me/whatsapp_business_accounts*' => \Illuminate\Support\Facades\Http::response([
                'data' => [
                    ['id' => '100200300400', 'name' => 'My WABA Account']
                ]
            ], 200),
            'https://graph.facebook.com/v21.0/me/client_whatsapp_business_accounts*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            'https://graph.facebook.com/v21.0/me/shared_whatsapp_business_accounts*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            'https://graph.facebook.com/v21.0/me/businesses*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            'https://graph.facebook.com/v21.0/me?*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            'https://graph.facebook.com/v21.0/100200300400/phone_numbers*' => \Illuminate\Support\Facades\Http::response([
                'data' => [
                    [
                        'id' => '9988776655',
                        'display_phone_number' => '+20 100 123 4567',
                        'verified_name' => 'Verified Business Name'
                    ]
                ]
            ], 200),
        ]);

        $service = app(\Modules\WhatsappSender\Services\MetaWhatsappService::class);
        $accounts = $service->fetchWhatsAppAccountsFromMetaToken('mock_user_token', 'mock_app_id', 'mock_app_secret');

        $this->assertCount(1, $accounts);
        $this->assertEquals('100200300400', $accounts[0]['waba_id']);
        $this->assertEquals('9988776655', $accounts[0]['phone_number_id']);
        $this->assertEquals('+20 100 123 4567', $accounts[0]['display_phone_number']);
        $this->assertEquals('Verified Business Name', $accounts[0]['verified_name']);
    }

    public function test_user_can_request_verification_code_via_sms_or_voice()
    {
        $user = User::factory()->create();
        $account = WhatsappAccount::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'phone_number_id' => '123456789',
            'access_token' => 'sandbox_test_token_123',
        ]);

        $response = $this->actingAs($user)
            ->from('/whatsapp-sender')
            ->post("/whatsapp-sender/accounts/{$account->id}/request-code", [
                'code_method' => 'SMS',
                'language' => 'ar',
            ]);

        $response->assertRedirect('/whatsapp-sender');
        $response->assertSessionHas('success');
    }

    public function test_whatsapp_account_display_phone_number_attribute()
    {
        $user = User::factory()->create();
        $business = \Modules\WhatsappSender\Models\WhatsappBusiness::create([
            'user_id' => $user->id,
            'name' => 'Test Biz',
            'client_whatsapp' => '+201015218548',
        ]);

        $accountWithMeta = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Acc With Meta',
            'phone_number_id' => '111222333',
            'access_token' => 'token123',
            'metadata' => [
                'display_phone_number' => '+201001234567',
            ],
        ]);

        $accountWithFallback = WhatsappAccount::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $business->id,
            'name' => 'Acc Fallback',
            'phone_number_id' => '444555666',
            'access_token' => 'token456',
        ]);

        $accountWithFallback->load('business');

        $this->assertEquals('+201001234567', $accountWithMeta->display_phone_number);
        $this->assertEquals('+201015218548', $accountWithFallback->display_phone_number);
    }
}
