<?php

namespace Modules\WhatsappSender\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappLog;

class MetaWhatsappService
{
    protected string $graphApiVersion = 'v21.0';

    /**
     * Send a WhatsApp message via Meta Cloud API.
     */
    public function sendMessage(
        WhatsappAccount $account,
        string $recipient,
        string $body,
        string $type = 'text',
        ?array $templateData = null
    ): array {
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipient);

        if ($type === 'template' && ! empty($templateData)) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateData['name'] ?? 'hello_world',
                    'language' => ['code' => $templateData['language'] ?? 'en_US'],
                ],
            ];
            if (! empty($templateData['components'])) {
                $payload['template']['components'] = $templateData['components'];
            }
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/messages";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                $metaMessageId = $responseData['messages'][0]['id'] ?? null;

                $log = WhatsappLog::create([
                    'user_id' => $account->user_id,
                    'whatsapp_account_id' => $account->id,
                    'recipient_phone' => $cleanPhone,
                    'message_type' => $type,
                    'message_body' => $body,
                    'status' => 'sent',
                    'meta_message_id' => $metaMessageId,
                    'payload' => $payload,
                ]);

                return [
                    'success' => true,
                    'meta_message_id' => $metaMessageId,
                    'log_id' => $log->id,
                    'response' => $responseData,
                ];
            }

            $errorMessage = $responseData['error']['message'] ?? $response->body();

            $log = WhatsappLog::create([
                'user_id' => $account->user_id,
                'whatsapp_account_id' => $account->id,
                'recipient_phone' => $cleanPhone,
                'message_type' => $type,
                'message_body' => $body,
                'status' => 'failed',
                'error_message' => $errorMessage,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'log_id' => $log->id,
                'response' => $responseData,
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] API Exception: ' . $e->getMessage());

            $log = WhatsappLog::create([
                'user_id' => $account->user_id,
                'whatsapp_account_id' => $account->id,
                'recipient_phone' => $cleanPhone,
                'message_type' => $type,
                'message_body' => $body,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
            ];
        }
    }

    /**
     * Verify phone number ID and access token against Meta Graph API.
     */
    public function verifyAccountCredentials(string $phoneNumberId, string $accessToken): array
    {
        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$phoneNumberId}";

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url, [
                    'fields' => 'id,verified_name,display_phone_number,quality_rating',
                ]);

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'valid' => false,
                'error' => $response->json()['error']['message'] ?? 'Failed to verify Meta WhatsApp Account credentials.',
            ];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
