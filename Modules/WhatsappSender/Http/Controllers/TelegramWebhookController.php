<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\WhatsappContactGroup;
use Modules\WhatsappSender\Models\WhatsappContact;
use Modules\WhatsappSender\Services\TelegramBotService;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Telegram.
     */
    public function handle(Request $request, int $botId): JsonResponse
    {
        $bot = TelegramBot::findOrFail($botId);
        $payload = $request->all();

        // Check if webhook has a message
        if (isset($payload['message'])) {
            $message = $payload['message'];
            $chat = $message['chat'] ?? null;
            $text = $message['text'] ?? '';

            if ($chat && isset($chat['id'])) {
                $chatId = (string) $chat['id'];
                $firstName = $chat['first_name'] ?? '';
                $lastName = $chat['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName) ?: 'Telegram Subscriber';
                $username = $chat['username'] ?? null;

                // Find or create "Telegram Bot Subscribers" contact group for this business
                $group = WhatsappContactGroup::firstOrCreate([
                    'whatsapp_business_id' => $bot->whatsapp_business_id,
                    'user_id' => $bot->business->user_id,
                    'name' => 'Telegram Bot Subscribers',
                ], [
                    'description' => 'Automatically generated list of subscribers who messaged your Telegram bot.',
                ]);

                // Create or update contact under this group
                WhatsappContact::updateOrCreate(
                    [
                        'whatsapp_contact_group_id' => $group->id,
                        'phone' => $chatId,
                    ],
                    [
                        'name' => $fullName,
                        'custom_fields' => [
                            'telegram_username' => $username,
                            'chat_id' => $chatId,
                        ]
                    ]
                );

                // Auto-reply with simple welcome if they typed /start
                if ($text === '/start') {
                    $telegramBotService = app(TelegramBotService::class);
                    $telegramBotService->sendMessage($bot, $chatId, "Welcome! You have successfully subscribed to notifications from <b>" . $bot->business->name . "</b>.");
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
