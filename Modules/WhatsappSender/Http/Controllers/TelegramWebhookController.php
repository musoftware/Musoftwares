<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\TelegramSubscriber;
use Modules\WhatsappSender\Models\TelegramSubscriberGroup;
use Modules\WhatsappSender\Services\BotFlowEngineService;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Telegram.
     */
    public function handle(Request $request, int $botId): JsonResponse
    {
        $bot = TelegramBot::findOrFail($botId);
        $payload = $request->all();
        $botFlowEngine = app(BotFlowEngineService::class);

        // 1. Handle Inline Button Click (callback_query)
        if (isset($payload['callback_query'])) {
            $callbackQuery = $payload['callback_query'];
            $chatId = (string) ($callbackQuery['from']['id'] ?? '');
            $callbackData = $callbackQuery['data'] ?? '';

            if ($chatId && $callbackData) {
                // Ensure subscriber exists
                $firstName = $callbackQuery['from']['first_name'] ?? '';
                $lastName = $callbackQuery['from']['last_name'] ?? '';
                $username = $callbackQuery['from']['username'] ?? null;

                $group = TelegramSubscriberGroup::firstOrCreate([
                    'telegram_bot_id' => $bot->id,
                    'name' => 'All Subscribers',
                ], [
                    'description' => 'Default group for all bot subscribers.',
                ]);

                TelegramSubscriber::updateOrCreate(
                    [
                        'telegram_bot_id' => $bot->id,
                        'chat_id' => $chatId,
                    ],
                    [
                        'telegram_subscriber_group_id' => $group->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => $username,
                    ]
                );

                $botFlowEngine->handleTelegramCallbackQuery($bot, $chatId, $callbackData);
            }

            // Answer callback query to prevent Telegram loading spinner
            $url = "https://api.telegram.org/bot{$bot->token}/answerCallbackQuery";
            \Illuminate\Support\Facades\Http::post($url, [
                'callback_query_id' => $callbackQuery['id']
            ]);

            return response()->json(['ok' => true]);
        }

        // 2. Handle Text Messages
        if (isset($payload['message'])) {
            $message = $payload['message'];
            $chat = $message['chat'] ?? null;
            $text = $message['text'] ?? '';

            if ($chat && isset($chat['id'])) {
                $chatId = (string) $chat['id'];
                $firstName = $chat['first_name'] ?? '';
                $lastName = $chat['last_name'] ?? '';
                $username = $chat['username'] ?? null;

                // Auto-subscribe the user in dedicated subscriber tables
                $group = TelegramSubscriberGroup::firstOrCreate([
                    'telegram_bot_id' => $bot->id,
                    'name' => 'All Subscribers',
                ], [
                    'description' => 'Default group for all bot subscribers.',
                ]);

                TelegramSubscriber::updateOrCreate(
                    [
                        'telegram_bot_id' => $bot->id,
                        'chat_id' => $chatId,
                    ],
                    [
                        'telegram_subscriber_group_id' => $group->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => $username,
                    ]
                );

                // Dispatch to flow engine
                $botFlowEngine->handleIncomingMessage('telegram', $bot->whatsapp_business_id, $chatId, $text, $bot->id, $payload);
            }
        }

        return response()->json(['ok' => true]);
    }
}
