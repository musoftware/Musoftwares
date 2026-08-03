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
     * Check if access token is a Sandbox / Test mode placeholder token.
     */
    public function isSandboxToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        return $token === 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO'
            || str_contains($token, 'EAAG_META_TEST')
            || str_contains($token, 'TEST_SANDBOX')
            || $token === config('services.facebook.test_access_token');
    }

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

        if ($this->isSandboxToken($account->access_token)) {
            $metaMessageId = 'wamid.sandbox.' . uniqid();

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
                        'description' => "Platform message fee ($0.0010) for recipient {$cleanPhone} (Sandbox Test)",
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
                'response' => ['messages' => [['id' => $metaMessageId]]],
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
        if ($this->isSandboxToken($accessToken)) {
            return [
                'valid' => true,
                'data' => [
                    'id' => $phoneNumberId,
                    'verified_name' => 'Meta Sandbox Test Number',
                    'display_phone_number' => '+1 555-0199',
                    'quality_rating' => 'GREEN',
                    'status' => 'APPROVED',
                ],
            ];
        }

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
        if ($this->isSandboxToken($account->access_token)) {
            $account->update(['status' => 'active']);
            $metadata = $account->metadata ?? [];
            $metadata['status'] = 'CONNECTED';
            $account->update(['metadata' => $metadata]);

            return [
                'success' => true,
                'message' => 'Phone number successfully registered in Meta Sandbox mode.',
                'response' => ['success' => true],
            ];
        }

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
    public function fetchWhatsAppAccountsFromMetaToken(string $accessToken, ?string $clientId = null, ?string $clientSecret = null): array
    {
        if ($this->isSandboxToken($accessToken)) {
            return [
                [
                    'waba_id' => config('services.facebook.test_waba_id', '109283748291029'),
                    'waba_name' => 'Meta Sandbox WABA Test',
                    'phone_number_id' => config('services.facebook.test_phone_number_id', '114811102562039'),
                    'display_phone_number' => '+1 555-0199',
                    'verified_name' => 'Sandbox Test Number',
                ]
            ];
        }

        $clientId = $clientId ?: config('services.facebook.client_id');
        $clientSecret = $clientSecret ?: config('services.facebook.client_secret');

        $rawResponses = [];
        $discoveredWabas = []; // waba_id => waba_name
        $targetPhoneIds = [];  // phone_number_id => true
        $foundAccounts = [];   // phone_number_id => account_data

        // Strategy 0: Inspect Meta Token via /debug_token if app credentials provided
        if (!empty($clientId) && !empty($clientSecret)) {
            try {
                $appToken = "{$clientId}|{$clientSecret}";
                $debugRes = Http::get("https://graph.facebook.com/debug_token", [
                    'input_token' => $accessToken,
                    'access_token' => $appToken,
                ]);
                $rawResponses['strategy_0_debug_token'] = [
                    'status' => $debugRes->status(),
                    'body' => $debugRes->json(),
                ];

                if ($debugRes->successful()) {
                    $granularScopes = $debugRes->json()['data']['granular_scopes'] ?? [];
                    foreach ($granularScopes as $gs) {
                        $scope = $gs['scope'] ?? '';
                        $targetIds = $gs['target_ids'] ?? [];

                        if (in_array($scope, ['whatsapp_business_management', 'whatsapp_business_messaging'])) {
                            foreach ($targetIds as $tid) {
                                $discoveredWabas[(string) $tid] = null;
                                $targetPhoneIds[(string) $tid] = true;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[MetaWhatsappService] debug_token inspection warning: ' . $e->getMessage());
                $rawResponses['strategy_0_debug_token'] = ['error' => $e->getMessage()];
            }
        }

        // Strategy 1: User Permissions inspection via /me/permissions
        try {
            $permRes = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/me/permissions");
            $rawResponses['strategy_1_me_permissions'] = [
                'status' => $permRes->status(),
                'body' => $permRes->json(),
            ];
        } catch (\Throwable $e) {
            $rawResponses['strategy_1_me_permissions'] = ['error' => $e->getMessage()];
        }

        // Strategy 2: Business Managers /me/businesses & WABAs per business
        try {
            $resBiz = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/me/businesses", [
                'fields' => 'id,name',
            ]);
            $rawResponses['strategy_2_me_businesses'] = [
                'status' => $resBiz->status(),
                'body' => $resBiz->json(),
            ];

            if ($resBiz->successful()) {
                foreach ($resBiz->json()['data'] ?? [] as $biz) {
                    $bizId = $biz['id'] ?? null;
                    if (!$bizId) continue;

                    // Query owned WABAs under this business
                    try {
                        $ownedRes = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/{$bizId}/owned_whatsapp_business_accounts", [
                            'fields' => 'id,name',
                        ]);
                        $rawResponses["biz_{$bizId}_owned_wabas"] = [
                            'status' => $ownedRes->status(),
                            'body' => $ownedRes->json(),
                        ];
                        if ($ownedRes->successful()) {
                            foreach ($ownedRes->json()['data'] ?? [] as $waba) {
                                if (!empty($waba['id'])) {
                                    $discoveredWabas[(string) $waba['id']] = $waba['name'] ?? $biz['name'] ?? null;
                                }
                            }
                        }
                    } catch (\Throwable) {}

                    // Query client WABAs under this business
                    try {
                        $clientRes = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/{$bizId}/client_whatsapp_business_accounts", [
                            'fields' => 'id,name',
                        ]);
                        $rawResponses["biz_{$bizId}_client_wabas"] = [
                            'status' => $clientRes->status(),
                            'body' => $clientRes->json(),
                        ];
                        if ($clientRes->successful()) {
                            foreach ($clientRes->json()['data'] ?? [] as $waba) {
                                if (!empty($waba['id'])) {
                                    $discoveredWabas[(string) $waba['id']] = $waba['name'] ?? $biz['name'] ?? null;
                                }
                            }
                        }
                    } catch (\Throwable) {}
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[MetaWhatsappService] businesses strategy warning: ' . $e->getMessage());
            $rawResponses['strategy_2_me_businesses'] = ['error' => $e->getMessage()];
        }

        // Strategy 3: Query Phone Numbers for all Discovered WABAs via /{waba_id}/phone_numbers
        foreach ($discoveredWabas as $wabaId => $wabaName) {
            if (empty($wabaId)) continue;

            try {
                if (empty($wabaName)) {
                    $wabaInfoRes = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/{$wabaId}", [
                        'fields' => 'id,name',
                    ]);
                    $rawResponses["waba_info_{$wabaId}"] = [
                        'status' => $wabaInfoRes->status(),
                        'body' => $wabaInfoRes->json(),
                    ];
                    if ($wabaInfoRes->successful()) {
                        $wabaName = $wabaInfoRes->json()['name'] ?? null;
                        $discoveredWabas[$wabaId] = $wabaName;
                    }
                }

                $phoneRes = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/{$wabaId}/phone_numbers", [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating,status',
                ]);
                $rawResponses["waba_phone_numbers_{$wabaId}"] = [
                    'status' => $phoneRes->status(),
                    'body' => $phoneRes->json(),
                ];

                if ($phoneRes->successful()) {
                    $phones = $phoneRes->json()['data'] ?? [];
                    foreach ($phones as $phone) {
                        if (!empty($phone['id'])) {
                            $foundAccounts[(string) $phone['id']] = [
                                'waba_id' => (string) $wabaId,
                                'waba_name' => $wabaName,
                                'phone_number_id' => (string) $phone['id'],
                                'display_phone_number' => $phone['display_phone_number'] ?? null,
                                'verified_name' => $phone['verified_name'] ?? $wabaName,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("[MetaWhatsappService] Failed to fetch phone numbers for WABA {$wabaId}: " . $e->getMessage());
                $rawResponses["waba_phone_numbers_{$wabaId}"] = ['error' => $e->getMessage()];
            }
        }

        // Query any direct Phone Number IDs discovered via debug_token target_ids
        foreach (array_keys($targetPhoneIds) as $phoneId) {
            if (isset($foundAccounts[$phoneId]) || isset($discoveredWabas[$phoneId])) {
                continue;
            }

            try {
                $pRes = Http::withToken($accessToken)->get("https://graph.facebook.com/{$this->graphApiVersion}/{$phoneId}", [
                    'fields' => 'id,display_phone_number,verified_name,status,waba_id',
                ]);
                $rawResponses["target_phone_details_{$phoneId}"] = [
                    'status' => $pRes->status(),
                    'body' => $pRes->json(),
                ];

                if ($pRes->successful()) {
                    $pData = $pRes->json();
                    if (!empty($pData['id']) && (isset($pData['display_phone_number']) || isset($pData['waba_id']))) {
                        $foundAccounts[(string) $pData['id']] = [
                            'waba_id' => $pData['waba_id'] ?? $phoneId,
                            'waba_name' => $pData['verified_name'] ?? 'Meta WhatsApp Account',
                            'phone_number_id' => (string) $pData['id'],
                            'display_phone_number' => $pData['display_phone_number'] ?? null,
                            'verified_name' => $pData['verified_name'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Not a phone number ID, ignore
            }
        }

        return array_values($foundAccounts);
    }

    /**
     * Create a message template on Facebook.
     */
    public function createMetaTemplate(WhatsappAccount $account, WhatsappTemplate $template): array
    {
        if ($this->isSandboxToken($account->access_token)) {
            $template->update([
                'status' => 'APPROVED',
                'meta_template_id' => 'sandbox_tpl_' . rand(100000, 999999),
            ]);

            return [
                'success' => true,
                'id' => $template->meta_template_id,
                'status' => 'APPROVED',
            ];
        }

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
        $businessId = $account->whatsapp_business_id;

        if (!$businessId) {
            return ['success' => false, 'error' => 'Account is not linked to any business client.'];
        }

        if ($this->isSandboxToken($account->access_token)) {
            $sampleTemplates = [
                [
                    'name' => 'hello_world',
                    'category' => 'UTILITY',
                    'language' => 'en_US',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Welcome'],
                        ['type' => 'BODY', 'text' => 'Hello World! Welcome to Meta WhatsApp Sandbox mode.'],
                        ['type' => 'FOOTER', 'text' => 'Sent via Musoftware WhatsApp Sender'],
                    ],
                    'status' => 'APPROVED',
                    'meta_template_id' => 'sandbox_tpl_101',
                ],
                [
                    'name' => 'order_confirmation',
                    'category' => 'UTILITY',
                    'language' => 'ar',
                    'components' => [
                        ['type' => 'BODY', 'text' => 'مرحباً، تم تأكيد طلبك بنجاح. شكراً لتواصلك معنا!'],
                    ],
                    'status' => 'APPROVED',
                    'meta_template_id' => 'sandbox_tpl_102',
                ],
                [
                    'name' => '3p_direct_integration_test_template',
                    'category' => 'UTILITY',
                    'language' => 'en_US',
                    'components' => [
                        ['type' => 'BODY', 'text' => 'Welcome and congratulations!! This message demonstrates your ability to send a WhatsApp message notification from the Cloud API, hosted by Meta.'],
                    ],
                    'status' => 'APPROVED',
                    'meta_template_id' => 'sandbox_tpl_103',
                ],
            ];

            foreach ($sampleTemplates as $tpl) {
                WhatsappTemplate::updateOrCreate(
                    [
                        'whatsapp_business_id' => $businessId,
                        'name' => $tpl['name'],
                    ],
                    [
                        'category' => $tpl['category'],
                        'language' => $tpl['language'],
                        'components' => $tpl['components'],
                        'status' => $tpl['status'],
                        'meta_template_id' => $tpl['meta_template_id'],
                    ]
                );
            }

            return [
                'success' => true,
                'count' => count($sampleTemplates),
            ];
        }

        if (empty($account->waba_id)) {
            return ['success' => false, 'error' => 'WABA ID is not connected to this account. Cannot sync templates.'];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->waba_id}/message_templates";

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
        if ($this->isSandboxToken($account->access_token)) {
            WhatsappTemplate::where('whatsapp_business_id', $account->whatsapp_business_id)
                ->where('name', $name)
                ->delete();

            return ['success' => true];
        }

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
