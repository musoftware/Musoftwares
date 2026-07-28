<?php

namespace Modules\WhatsappSender\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Models\WhatsappTransaction;
use Modules\WhatsappSender\Models\WhatsappTemplate;

class MetaWhatsappService
{
    protected string $graphApiVersion = 'v21.0';

    /**
     * Send a WhatsApp message via Meta Cloud API with wallet fee deduction.
     */
    public function sendMessage(
        WhatsappAccount $account,
        string $recipient,
        string $body,
        string $type = 'text',
        ?array $templateData = null
    ): array {
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipient);

        // 1. Wallet Balance Check
        $business = $account->business;
        $fee = $business ? (float) $business->per_message_fee : 0.0010;

        if ($business && (float) $business->wallet_balance < $fee) {
            $formattedFee = number_format($fee, 4);
            $formattedBalance = number_format((float) $business->wallet_balance, 4);

            return [
                'success' => false,
                'error' => "Insufficient wallet balance (\${$formattedBalance} USD available, \${$formattedFee} USD required). Please recharge your business wallet balance to continue sending messages.",
                'log_id' => null,
            ];
        }

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

                // Deduct platform fee atomically from business wallet
                if ($business) {
                    DB::transaction(function () use ($business, $fee, $cleanPhone) {
                        $lockedBiz = WhatsappBusiness::where('id', $business->id)->lockForUpdate()->first();
                        $newBalance = max(0, (float) $lockedBiz->wallet_balance - $fee);
                        $lockedBiz->update(['wallet_balance' => $newBalance]);

                        WhatsappTransaction::create([
                            'whatsapp_business_id' => $lockedBiz->id,
                            'user_id' => $lockedBiz->user_id,
                            'type' => 'debit_message_fee',
                            'amount' => $fee,
                            'balance_after' => $newBalance,
                            'description' => "Platform message fee ($0.0010) for recipient {$cleanPhone}",
                        ]);
                    });
                }

                $log = WhatsappLog::create([
                    'user_id' => $account->user_id,
                    'whatsapp_account_id' => $account->id,
                    'whatsapp_business_id' => $business?->id,
                    'recipient_phone' => $cleanPhone,
                    'cost_charged' => $fee,
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
                    'cost_charged' => $fee,
                    'response' => $responseData,
                ];
            }

            $errorMessage = $responseData['error']['message'] ?? $response->body();
            $errorCode = $responseData['error']['code'] ?? null;

            if ($errorCode === 133010) {
                $account->update(['status' => 'unregistered']);
            }

            $log = WhatsappLog::create([
                'user_id' => $account->user_id,
                'whatsapp_account_id' => $account->id,
                'whatsapp_business_id' => $business?->id,
                'recipient_phone' => $cleanPhone,
                'cost_charged' => 0.0000,
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
                'whatsapp_business_id' => $business?->id,
                'recipient_phone' => $cleanPhone,
                'cost_charged' => 0.0000,
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
                    'fields' => 'id,verified_name,display_phone_number,quality_rating,status',
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

    /**
     * Register a phone number with Meta Cloud API using a 6-digit PIN.
     */
    public function registerPhoneNumber(WhatsappAccount $account, string $pin): array
    {
        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/register";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'pin' => $pin,
                ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                // Update status of the account to active
                $account->update(['status' => 'active']);

                // Also update status in metadata
                $metadata = $account->metadata ?? [];
                $metadata['status'] = 'CONNECTED';
                $account->update(['metadata' => $metadata]);

                return [
                    'success' => true,
                    'message' => 'Phone number successfully registered and activated on Meta Cloud API.',
                    'response' => $responseData,
                ];
            }

            $errorMessage = $responseData['error']['message'] ?? 'Failed to register phone number with Meta API.';
            return [
                'success' => false,
                'error' => $errorMessage,
                'response' => $responseData,
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] Register Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch WhatsApp Business Accounts and Phone Numbers linked to an OAuth user token across all Meta endpoints.
     */
    public function fetchWhatsAppAccountsFromMetaToken(string $accessToken): array
    {
        $foundAccounts = [];

        try {
            // Strategy 1: Direct /me/whatsapp_business_accounts
            $res1 = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/me/whatsapp_business_accounts", [
                'fields' => 'id,name,phone_numbers{id,display_phone_number,verified_name}',
            ]);

            if ($res1->successful()) {
                $wabas = $res1->json()['data'] ?? [];
                foreach ($wabas as $waba) {
                    $phones = $waba['phone_numbers']['data'] ?? [];
                    foreach ($phones as $phone) {
                        $foundAccounts[$phone['id']] = [
                            'waba_id' => $waba['id'],
                            'waba_name' => $waba['name'] ?? null,
                            'phone_number_id' => $phone['id'],
                            'display_phone_number' => $phone['display_phone_number'] ?? null,
                            'verified_name' => $phone['verified_name'] ?? null,
                        ];
                    }
                }
            }

            // Strategy 2: Client WABAs /me/client_whatsapp_business_accounts
            $res2 = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/me/client_whatsapp_business_accounts", [
                'fields' => 'id,name,phone_numbers{id,display_phone_number,verified_name}',
            ]);

            if ($res2->successful()) {
                $wabas = $res2->json()['data'] ?? [];
                foreach ($wabas as $waba) {
                    $phones = $waba['phone_numbers']['data'] ?? [];
                    foreach ($phones as $phone) {
                        $foundAccounts[$phone['id']] = [
                            'waba_id' => $waba['id'],
                            'waba_name' => $waba['name'] ?? null,
                            'phone_number_id' => $phone['id'],
                            'display_phone_number' => $phone['display_phone_number'] ?? null,
                            'verified_name' => $phone['verified_name'] ?? null,
                        ];
                    }
                }
            }

            // Strategy 3: Business Managers /me/businesses
            $res3 = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/me/businesses", [
                'fields' => 'id,name,whatsapp_business_accounts{id,name,phone_numbers{id,display_phone_number,verified_name}}',
            ]);

            if ($res3->successful()) {
                $businesses = $res3->json()['data'] ?? [];
                foreach ($businesses as $biz) {
                    $wabas = $biz['whatsapp_business_accounts']['data'] ?? [];
                    foreach ($wabas as $waba) {
                        $phones = $waba['phone_numbers']['data'] ?? [];
                        foreach ($phones as $phone) {
                            $foundAccounts[$phone['id']] = [
                                'waba_id' => $waba['id'],
                                'waba_name' => $waba['name'] ?? $biz['name'] ?? null,
                                'phone_number_id' => $phone['id'],
                                'display_phone_number' => $phone['display_phone_number'] ?? null,
                                'verified_name' => $phone['verified_name'] ?? null,
                            ];
                        }
                    }
                }
            }

            return array_values($foundAccounts);
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] fetchWhatsAppAccountsFromMetaToken error: ' . $e->getMessage());
            return array_values($foundAccounts);
        }
    }

    /**
     * Create a message template on Facebook.
     */
    public function createMetaTemplate(WhatsappAccount $account, WhatsappTemplate $template): array
    {
        if (empty($account->waba_id)) {
            return ['success' => false, 'error' => 'WABA ID is not connected to this account. Cannot create template.'];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->waba_id}/message_templates";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($url, [
                    'name' => $template->name,
                    'category' => $template->category,
                    'language' => $template->language,
                    'components' => $template->components,
                ]);

            $data = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? 'PENDING',
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['message'] ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] createMetaTemplate exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync message templates from Facebook WABA.
     */
    public function syncMetaTemplates(WhatsappAccount $account): array
    {
        if (empty($account->waba_id)) {
            return ['success' => false, 'error' => 'WABA ID is not connected to this account. Cannot sync templates.'];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->waba_id}/message_templates";
        $businessId = $account->whatsapp_business_id;

        if (!$businessId) {
            return ['success' => false, 'error' => 'Account is not linked to any business client.'];
        }

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->get($url, ['limit' => 1000]);

            if ($response->successful()) {
                $templates = $response->json()['data'] ?? [];

                foreach ($templates as $tpl) {
                    WhatsappTemplate::updateOrCreate(
                        [
                            'whatsapp_business_id' => $businessId,
                            'name' => $tpl['name'],
                        ],
                        [
                            'category' => $tpl['category'] ?? 'UTILITY',
                            'language' => $tpl['language'] ?? 'en_US',
                            'components' => $tpl['components'] ?? [],
                            'status' => $tpl['status'] ?? 'PENDING',
                            'meta_template_id' => $tpl['id'] ?? null,
                        ]
                    );
                }

                return [
                    'success' => true,
                    'count' => count($templates),
                ];
            }

            $error = $response->json()['error']['message'] ?? $response->body();
            return ['success' => false, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] syncMetaTemplates exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a message template from Facebook WABA.
     */
    public function deleteMetaTemplate(WhatsappAccount $account, string $name): array
    {
        if (empty($account->waba_id)) {
            return ['success' => false, 'error' => 'WABA ID is not connected to this account. Cannot delete template.'];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->waba_id}/message_templates";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->delete($url, [
                    'name' => $name,
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['success']) && $data['success'] === true) {
                // Delete locally
                WhatsappTemplate::where('whatsapp_business_id', $account->whatsapp_business_id)
                    ->where('name', $name)
                    ->delete();

                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $data['error']['message'] ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] deleteMetaTemplate exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
