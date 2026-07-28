<?php

namespace Modules\WhatsappSender\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\WhatsappSender\Models\TelegramBot;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Models\WhatsappTransaction;

class TelegramBotService
{
    /**
     * Get details of a bot using its token.
     */
    public function getBotDetails(string $token): array
    {
        try {
            $response = Http::acceptJson()->post("https://api.telegram.org/bot{$token}/getMe");
            $data = $response->json();

            if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
                return [
                    'success' => true,
                    'name' => $data['result']['first_name'] ?? 'Telegram Bot',
                    'username' => $data['result']['username'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $data['description'] ?? 'Failed to authenticate Telegram Bot token.',
            ];
        } catch (\Throwable $e) {
            Log::error('[TelegramBotService] getBotDetails exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Register bot webhook with Telegram.
     */
    public function setWebhook(TelegramBot $bot): array
    {
        $url = url("/api/v1/telegram/webhook/{$bot->id}");

        try {
            $response = Http::acceptJson()->post("https://api.telegram.org/bot{$bot->token}/setWebhook", [
                'url' => $url,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $data['description'] ?? 'Failed to set Telegram Bot webhook.',
            ];
        } catch (\Throwable $e) {
            Log::error('[TelegramBotService] setWebhook exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remove bot webhook from Telegram.
     */
    public function deleteWebhook(TelegramBot $bot): array
    {
        try {
            $response = Http::acceptJson()->post("https://api.telegram.org/bot{$bot->token}/deleteWebhook");
            $data = $response->json();

            if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $data['description'] ?? 'Failed to delete Telegram Bot webhook.',
            ];
        } catch (\Throwable $e) {
            Log::error('[TelegramBotService] deleteWebhook exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send message using the Telegram Bot API.
     */
    public function sendMessage(
        TelegramBot $bot,
        string $chatId,
        string $text,
        string $type = 'text',
        ?array $media = null
    ): array {
        $business = $bot->business;
        $fee = $business ? (float) $business->per_message_fee : 0.0010;

        // Wallet Balance Check
        if ($business && (float) $business->wallet_balance < $fee) {
            return [
                'success' => false,
                'error' => "Insufficient business wallet balance (\${$business->wallet_balance} USD available, \${$fee} USD required).",
            ];
        }

        try {
            $url = "https://api.telegram.org/bot{$bot->token}/sendMessage";
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            $response = Http::acceptJson()->post($url, $payload);
            $data = $response->json();

            if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
                $telegramMessageId = $data['result']['message_id'] ?? null;

                // Deduct platform fee atomically from business wallet
                if ($business) {
                    DB::transaction(function () use ($business, $fee, $chatId) {
                        $lockedBiz = WhatsappBusiness::where('id', $business->id)->lockForUpdate()->first();
                        $newBalance = max(0, (float) $lockedBiz->wallet_balance - $fee);
                        $lockedBiz->update(['wallet_balance' => $newBalance]);

                        WhatsappTransaction::create([
                            'whatsapp_business_id' => $lockedBiz->id,
                            'user_id' => $lockedBiz->user_id,
                            'type' => 'debit_message_fee',
                            'amount' => $fee,
                            'balance_after' => $newBalance,
                            'description' => "Telegram message fee ($0.0010) for Chat ID {$chatId}",
                        ]);
                    });
                }

                // Create log
                $log = WhatsappLog::create([
                    'user_id' => $business ? $business->user_id : auth()->id(),
                    'telegram_bot_id' => $bot->id,
                    'whatsapp_business_id' => $business?->id,
                    'recipient_phone' => $chatId,
                    'channel' => 'telegram',
                    'cost_charged' => $fee,
                    'message_type' => $type,
                    'message_body' => $text,
                    'status' => 'sent',
                    'meta_message_id' => $telegramMessageId,
                    'payload' => $payload,
                ]);

                return [
                    'success' => true,
                    'message_id' => $telegramMessageId,
                    'log_id' => $log->id,
                    'cost_charged' => $fee,
                ];
            }

            $errorMessage = $data['description'] ?? 'Telegram Bot API rejected request.';

            // Create failed log
            $log = WhatsappLog::create([
                'user_id' => $business ? $business->user_id : auth()->id(),
                'telegram_bot_id' => $bot->id,
                'whatsapp_business_id' => $business?->id,
                'recipient_phone' => $chatId,
                'channel' => 'telegram',
                'cost_charged' => 0.0000,
                'message_type' => $type,
                'message_body' => $text,
                'status' => 'failed',
                'error_message' => $errorMessage,
                'payload' => $payload ?? null,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'log_id' => $log->id,
            ];
        } catch (\Throwable $e) {
            Log::error('[TelegramBotService] sendMessage exception: ' . $e->getMessage());

            // Create failed log
            $log = WhatsappLog::create([
                'user_id' => $business ? $business->user_id : auth()->id(),
                'telegram_bot_id' => $bot->id,
                'whatsapp_business_id' => $business?->id,
                'recipient_phone' => $chatId,
                'channel' => 'telegram',
                'cost_charged' => 0.0000,
                'message_type' => $type,
                'message_body' => $text,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
            ];
        }
    }
}
