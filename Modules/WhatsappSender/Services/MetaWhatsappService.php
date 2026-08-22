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
            || str_contains(strtoupper($token), 'EAAG_META_TEST')
            || str_contains(strtoupper($token), 'SANDBOX')
            || str_contains(strtoupper($token), 'TEST')
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
        ?array $templateData = null,
        ?array $extraData = null
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
        } elseif ($type === 'image') {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'image',
                'image' => [
                    'link' => $extraData['link'] ?? $extraData['url'] ?? $body,
                ],
            ];
            $caption = $extraData['caption'] ?? (!str_starts_with($body, 'http') && !empty($body) ? $body : null);
            if ($caption) {
                $payload['image']['caption'] = $caption;
            }
        } elseif ($type === 'document') {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'document',
                'document' => [
                    'link' => $extraData['link'] ?? $extraData['url'] ?? $body,
                    'filename' => $extraData['filename'] ?? 'document.pdf',
                ],
            ];
            $caption = $extraData['caption'] ?? (!str_starts_with($body, 'http') && !empty($body) ? $body : null);
            if ($caption) {
                $payload['document']['caption'] = $caption;
            }
        } elseif ($type === 'audio') {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'audio',
                'audio' => [
                    'link' => $extraData['link'] ?? $extraData['url'] ?? $body,
                ],
            ];
        } elseif ($type === 'video') {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'video',
                'video' => [
                    'link' => $extraData['link'] ?? $extraData['url'] ?? $body,
                ],
            ];
            if (!empty($extraData['caption'])) {
                $payload['video']['caption'] = $extraData['caption'];
            }
        } elseif ($type === 'interactive') {
            $rawButtons = $extraData['buttons'] ?? [];
            $buttons = [];
            foreach (array_slice($rawButtons, 0, 3) as $i => $btn) {
                $title = is_array($btn) ? ($btn['title'] ?? $btn['text'] ?? "Option " . ($i + 1)) : (string) $btn;
                $id = is_array($btn) ? ($btn['id'] ?? "btn_" . ($i + 1)) : "btn_" . ($i + 1);
                $buttons[] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => (string) $id,
                        'title' => mb_substr(trim($title), 0, 20),
                    ],
                ];
            }
            if (empty($buttons)) {
                $buttons[] = [
                    'type' => 'reply',
                    'reply' => ['id' => 'btn_1', 'title' => 'Yes'],
                ];
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $body ?: 'Please choose an option:'],
                    'action' => [
                        'buttons' => $buttons,
                    ],
                ],
            ];
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
                $metadata = $account->metadata ?? [];
                $metadata['is_registered'] = false;
                $account->update([
                    'status' => 'unregistered',
                    'metadata' => $metadata,
                ]);
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
     * Perform deep diagnostic test of a WhatsApp Account against Meta Graph API for both Phone Number ID and WABA ID.
     */
    public function testAccountConnection(WhatsappAccount $account): array
    {
        if ($this->isSandboxToken($account->access_token)) {
            $mockData = [
                'mode' => 'sandbox_test_mode',
                'phone_number_details' => [
                    'id' => $account->phone_number_id,
                    'verified_name' => 'Meta Sandbox Test Number',
                    'display_phone_number' => '+1 555-0199',
                    'quality_rating' => 'GREEN',
                    'status' => 'CONNECTED',
                    'code_verification_status' => 'VERIFIED',
                    'is_official_business_account' => false,
                    'platform_type' => 'CLOUD_API',
                    'throughput' => ['level' => 'STANDARD'],
                ],
                'waba_details' => [
                    'id' => $account->waba_id ?? '1717790922679702',
                    'name' => 'Meta Sandbox WABA Account',
                    'account_review_status' => 'APPROVED',
                    'business_verification_status' => 'VERIFIED',
                    'currency' => 'USD',
                    'timezone_id' => 'Africa/Cairo',
                ],
                'summary' => 'Sandbox mode active. Connection test simulated successfully.',
            ];

            $account->update([
                'status' => 'active',
                'metadata' => array_merge($account->metadata ?? [], $mockData['phone_number_details']),
            ]);

            return [
                'success' => true,
                'message' => 'WhatsApp account test passed (Sandbox Mode).',
                'data' => $mockData,
            ];
        }

        $phoneFields = 'id,verified_name,display_phone_number,quality_rating,status,code_verification_status,is_official_business_account,platform_type,throughput,name_status,messaging_limit_tier';
        $phoneUrl = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}";

        $phoneResponse = null;
        $wabaResponse = null;
        $phoneData = null;
        $wabaData = null;

        try {
            $res = Http::withToken($account->access_token)
                ->acceptJson()
                ->get($phoneUrl, ['fields' => $phoneFields]);

            $phoneResponse = [
                'status' => $res->status(),
                'body' => $res->json(),
            ];

            if ($res->successful()) {
                $phoneData = $res->json();
            }
        } catch (\Throwable $e) {
            $phoneResponse = ['error' => $e->getMessage()];
        }

        if (!empty($account->waba_id)) {
            $wabaFields = 'id,name,account_review_status,business_verification_status,currency,timezone_id,owner_business_info';
            $wabaUrl = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->waba_id}";

            try {
                $resWaba = Http::withToken($account->access_token)
                    ->acceptJson()
                    ->get($wabaUrl, ['fields' => $wabaFields]);

                $wabaResponse = [
                    'status' => $resWaba->status(),
                    'body' => $resWaba->json(),
                ];

                if ($resWaba->successful()) {
                    $wabaData = $resWaba->json();
                }
            } catch (\Throwable $e) {
                $wabaResponse = ['error' => $e->getMessage()];
            }
        }

        $combinedResult = [
            'phone_number_id' => $account->phone_number_id,
            'waba_id' => $account->waba_id,
            'phone_number_api_response' => $phoneResponse,
            'waba_api_response' => $wabaResponse,
        ];

        if ($phoneData) {
            $metaStatus = strtoupper($phoneData['status'] ?? 'CONNECTED');
            $isConnected = in_array($metaStatus, ['CONNECTED', 'APPROVED', 'ACTIVE']);

            $existingMeta = $account ? ($account->metadata ?? []) : [];
            $isRegistered = !empty($existingMeta['is_registered']);

            if (!$isConnected) {
                $dbStatus = 'disconnected';
            } elseif ($isRegistered) {
                $dbStatus = 'active';
            } else {
                $dbStatus = 'unregistered';
            }

            if ($account) {
                $account->update([
                    'status' => $dbStatus,
                    'metadata' => array_merge($existingMeta, $phoneData, [
                        'waba_info' => $wabaData,
                        'meta_connection_status' => $metaStatus,
                        'is_registered' => $isRegistered,
                    ]),
                ]);
            }

            return [
                'success' => $isConnected,
                'is_connected' => $isConnected,
                'meta_status' => $metaStatus,
                'message' => $isConnected
                    ? '✓ Connected - Ready to send messages'
                    : '⚠️ Action Required: Your WhatsApp number is disconnected from Meta API. Reconnect to continue.',
                'data' => $combinedResult,
            ];
        }

        return [
            'success' => false,
            'is_connected' => false,
            'error' => $phoneResponse['body']['error']['message'] ?? ($phoneResponse['error'] ?? 'Failed to test phone number API endpoint.'),
            'data' => $combinedResult,
        ];
    }

    /**
     * Request a 6-digit verification code via SMS or VOICE from Meta Cloud API.
     */
    public function requestVerificationCode(WhatsappAccount $account, string $codeMethod = 'SMS', string $language = 'ar'): array
    {
        if ($this->isSandboxToken($account->access_token)) {
            return [
                'success' => true,
                'message' => 'Verification code requested successfully (Sandbox Test Mode: Code is 123456).',
                'response' => ['success' => true],
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/request_code";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'code_method' => strtoupper($codeMethod),
                    'language' => $language,
                ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                return [
                    'success' => true,
                    'message' => "Verification code sent successfully via {$codeMethod}.",
                    'response' => $responseData,
                ];
            }

            $errorMessage = $this->extractMetaErrorMessage($responseData, 'Failed to request verification code from Meta API.');

            return [
                'success' => false,
                'error' => $errorMessage,
                'response' => $responseData,
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] Request Verification Code Exception: ' . $e->getMessage());

            return [
                'success' => false,
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
            $metadata = $account->metadata ?? [];
            $metadata['status'] = 'CONNECTED';
            $metadata['meta_connection_status'] = 'CONNECTED';
            $metadata['is_registered'] = true;
            $metadata['registered_at'] = now()->toDateTimeString();

            $account->update([
                'status' => 'active',
                'metadata' => $metadata,
            ]);

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
                // Update status of the account to active and set is_registered to true
                $metadata = $account->metadata ?? [];
                $metadata['status'] = 'CONNECTED';
                $metadata['meta_connection_status'] = 'CONNECTED';
                $metadata['is_registered'] = true;
                $metadata['registered_at'] = now()->toDateTimeString();

                $account->update([
                    'status' => 'active',
                    'metadata' => $metadata,
                ]);

                return [
                    'success' => true,
                    'message' => 'Phone number successfully registered and activated on Meta Cloud API.',
                    'response' => $responseData,
                ];
            }

            $errorMessage = $this->extractMetaErrorMessage($responseData, 'Failed to register phone number with Meta API.');
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
     * Extract human-readable error title & message from Meta Graph API error payload.
     */
    public function extractMetaErrorMessage(array $responseData, string $defaultFallback = 'Meta API Error'): string
    {
        $error = $responseData['error'] ?? (isset($responseData['body']['error']) ? $responseData['body']['error'] : []);
        $userMsg = $error['error_user_msg'] ?? null;
        $userTitle = $error['error_user_title'] ?? null;
        $details = $error['error_data']['details'] ?? null;
        $rawMsg = $error['message'] ?? null;
        $subcode = $error['error_subcode'] ?? null;
        $code = $error['code'] ?? null;

        if ($userMsg) {
            return $userTitle ? "{$userTitle}: {$userMsg}" : $userMsg;
        }

        if ($details) {
            return $details;
        }

        if ($rawMsg && $rawMsg !== 'Request code error' && $rawMsg !== 'Error') {
            return $rawMsg;
        }

        if ($userTitle) {
            return $userTitle;
        }

        if ($subcode || $code) {
            $codeStr = $subcode ? "Code {$code} (Subcode {$subcode})" : "Code {$code}";
            return "Meta Error {$codeStr}: " . ($defaultFallback);
        }

        return $defaultFallback;
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

    /**
     * Get WhatsApp Business Profile from Meta Graph API.
     */
    public function getBusinessProfile(WhatsappAccount $account): array
    {
        if ($this->isSandboxToken($account->access_token)) {
            $metadata = $account->metadata ?? [];
            return [
                'success' => true,
                'data' => $metadata['business_profile'] ?? [
                    'about' => 'Welcome to our official WhatsApp service!',
                    'description' => 'We provide top-quality digital solutions and 24/7 customer support.',
                    'address' => '123 Business Avenue, Suite 400',
                    'email' => 'contact@example.com',
                    'websites' => ['https://musoftwares.com'],
                    'vertical' => 'PROF_SERVICES',
                    'profile_picture_url' => $metadata['profile_picture_url'] ?? null,
                ],
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/whatsapp_business_profile";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->get($url, [
                    'fields' => 'about,address,description,email,profile_picture_url,websites,vertical',
                ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['data'][0])) {
                $profileData = $json['data'][0];

                // Cache to account metadata
                $metadata = $account->metadata ?? [];
                $metadata['business_profile'] = $profileData;
                if (!empty($profileData['profile_picture_url'])) {
                    $metadata['profile_picture_url'] = $profileData['profile_picture_url'];
                }
                $account->update(['metadata' => $metadata]);

                return [
                    'success' => true,
                    'data' => $profileData,
                ];
            }

            return [
                'success' => false,
                'error' => $json['error']['message'] ?? 'Failed to retrieve WhatsApp business profile.',
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] getBusinessProfile error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update WhatsApp Business Profile on Meta Graph API.
     */
    public function updateBusinessProfile(WhatsappAccount $account, array $data): array
    {
        if ($this->isSandboxToken($account->access_token)) {
            $metadata = $account->metadata ?? [];
            $existing = $metadata['business_profile'] ?? [];
            $merged = array_merge($existing, $data);
            $metadata['business_profile'] = $merged;
            $account->update(['metadata' => $metadata]);

            return [
                'success' => true,
                'data' => $merged,
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/whatsapp_business_profile";

        $payload = [
            'messaging_product' => 'whatsapp',
        ];

        if (isset($data['about'])) {
            $payload['about'] = mb_substr($data['about'], 0, 139);
        }
        if (isset($data['description'])) {
            $payload['description'] = mb_substr($data['description'], 0, 512);
        }
        if (isset($data['address'])) {
            $payload['address'] = mb_substr($data['address'], 0, 256);
        }
        if (isset($data['email'])) {
            $payload['email'] = $data['email'];
        }
        if (isset($data['vertical'])) {
            $payload['vertical'] = $data['vertical'];
        }
        if (isset($data['websites']) && is_array($data['websites'])) {
            $payload['websites'] = array_values(array_filter(array_slice($data['websites'], 0, 2)));
        }

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($url, $payload);

            $json = $response->json();

            if ($response->successful() && ($json['success'] ?? false) === true) {
                // Update local cached metadata
                $metadata = $account->metadata ?? [];
                $metadata['business_profile'] = array_merge($metadata['business_profile'] ?? [], $data);
                $account->update(['metadata' => $metadata]);

                return [
                    'success' => true,
                    'data' => $metadata['business_profile'],
                ];
            }

            return [
                'success' => false,
                'error' => $json['error']['message'] ?? 'Failed to update WhatsApp business profile.',
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] updateBusinessProfile error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload & update WhatsApp Business Profile Picture.
     */
    public function updateProfilePicture(WhatsappAccount $account, \Illuminate\Http\UploadedFile $file): array
    {
        $business = $account->business;
        $appId = $business->facebook_client_id ?? config('services.facebook.client_id');

        if ($this->isSandboxToken($account->access_token)) {
            // In Sandbox/Test mode, store locally
            $path = $file->store('whatsapp-profiles', 'public');
            $url = asset('storage/' . $path);

            $metadata = $account->metadata ?? [];
            $metadata['profile_picture_url'] = $url;
            $metadata['business_profile']['profile_picture_url'] = $url;
            $account->update(['metadata' => $metadata]);

            return [
                'success' => true,
                'profile_picture_url' => $url,
            ];
        }

        try {
            $fileLength = $file->getSize();
            $mimeType = $file->getMimeType();

            // Step 1: Create Resumable Upload Session
            $sessionUrl = "https://graph.facebook.com/{$this->graphApiVersion}/app/uploads";
            if (!empty($appId)) {
                $sessionUrl = "https://graph.facebook.com/{$this->graphApiVersion}/{$appId}/uploads";
            }

            $sessionRes = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($sessionUrl, [
                    'file_length' => $fileLength,
                    'file_type' => $mimeType,
                ]);

            $sessionJson = $sessionRes->json();
            $uploadSessionId = $sessionJson['id'] ?? null;

            if (!$uploadSessionId) {
                // Fallback: Store locally and return error from Meta
                $errorMsg = $sessionJson['error']['message'] ?? 'Failed to initiate photo upload session on Meta.';
                return ['success' => false, 'error' => $errorMsg];
            }

            // Step 2: Upload file bytes
            $uploadRes = Http::withToken($account->access_token)
                ->withHeaders([
                    'file_offset' => 0,
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody(file_get_contents($file->getRealPath()), 'application/octet-stream')
                ->post("https://graph.facebook.com/{$this->graphApiVersion}/{$uploadSessionId}");

            $uploadJson = $uploadRes->json();
            $profileHandle = $uploadJson['h'] ?? null;

            if (!$profileHandle) {
                return ['success' => false, 'error' => $uploadJson['error']['message'] ?? 'Failed to transfer photo bytes to Meta.'];
            }

            // Step 3: Attach profile picture handle to business profile
            $attachUrl = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/whatsapp_business_profile";
            $attachRes = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($attachUrl, [
                    'messaging_product' => 'whatsapp',
                    'profile_picture_handle' => $profileHandle,
                ]);

            $attachJson = $attachRes->json();

            if ($attachRes->successful() && ($attachJson['success'] ?? false) === true) {
                // Save locally to cache
                $path = $file->store('whatsapp-profiles', 'public');
                $localUrl = asset('storage/' . $path);

                $metadata = $account->metadata ?? [];
                $metadata['profile_picture_url'] = $localUrl;
                $metadata['business_profile']['profile_picture_url'] = $localUrl;
                $account->update(['metadata' => $metadata]);

                return [
                    'success' => true,
                    'profile_picture_url' => $localUrl,
                ];
            }

            return ['success' => false, 'error' => $attachJson['error']['message'] ?? 'Failed to set profile picture.'];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] updateProfilePicture error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete/Reset Profile Picture.
     */
    public function deleteProfilePicture(WhatsappAccount $account): array
    {
        $metadata = $account->metadata ?? [];
        unset($metadata['profile_picture_url']);
        if (isset($metadata['business_profile']['profile_picture_url'])) {
            unset($metadata['business_profile']['profile_picture_url']);
        }
        $account->update(['metadata' => $metadata]);

        return ['success' => true];
    }

    /**
     * Set / Update Two-Step Verification PIN (6 digits).
     */
    public function setTwoStepVerificationPin(WhatsappAccount $account, string $pin): array
    {
        if (!preg_match('/^\d{6}$/', $pin)) {
            return ['success' => false, 'error' => 'PIN must be exactly 6 numeric digits.'];
        }

        if ($this->isSandboxToken($account->access_token)) {
            $metadata = $account->metadata ?? [];
            $metadata['has_2fa_pin'] = true;
            $account->update(['metadata' => $metadata]);

            return ['success' => true];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/two_step_verification";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->post($url, [
                    'pin' => $pin,
                ]);

            $json = $response->json();

            if ($response->successful() && ($json['success'] ?? false) === true) {
                $metadata = $account->metadata ?? [];
                $metadata['has_2fa_pin'] = true;
                $account->update(['metadata' => $metadata]);

                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $json['error']['message'] ?? 'Failed to set Two-Step Verification PIN.',
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] setTwoStepVerificationPin error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Phone Quality, Tier Limits, and Verification info.
     */
    public function getPhoneHealthAndLimits(WhatsappAccount $account): array
    {
        if ($this->isSandboxToken($account->access_token)) {
            return [
                'success' => true,
                'data' => [
                    'id' => $account->phone_number_id,
                    'verified_name' => $account->name . ' (Sandbox)',
                    'display_phone_number' => $account->display_phone_number ?? '+1 555-0199',
                    'quality_rating' => 'GREEN',
                    'name_status' => 'APPROVED',
                    'code_verification_status' => 'VERIFIED',
                    'messaging_limit_tier' => 'TIER_1K',
                ],
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}";

        try {
            $response = Http::withToken($account->access_token)
                ->acceptJson()
                ->get($url, [
                    'fields' => 'verified_name,code_verification_status,display_phone_number,quality_rating,name_status,messaging_limit_tier',
                ]);

            $json = $response->json();

            if ($response->successful() && isset($json['id'])) {
                // Update local metadata
                $metadata = $account->metadata ?? [];
                $metadata['quality_rating'] = $json['quality_rating'] ?? $metadata['quality_rating'] ?? null;
                $metadata['name_status'] = $json['name_status'] ?? $metadata['name_status'] ?? null;
                $metadata['messaging_limit_tier'] = $json['messaging_limit_tier'] ?? $metadata['messaging_limit_tier'] ?? null;
                $account->update([
                    'display_phone_number' => $json['display_phone_number'] ?? $account->display_phone_number,
                    'metadata' => $metadata,
                ]);

                return [
                    'success' => true,
                    'data' => $json,
                ];
            }

            return [
                'success' => false,
                'error' => $json['error']['message'] ?? 'Failed to retrieve phone number health status.',
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] getPhoneHealthAndLimits error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload rich media file to Meta Graph API Media endpoint.
     */
    public function uploadMedia(WhatsappAccount $account, \Illuminate\Http\UploadedFile $file, string $type = 'image'): array
    {
        $path = $file->store('whatsapp-media', 'public');
        $publicUrl = asset('storage/' . $path);
        $filename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        if ($this->isSandboxToken($account->access_token)) {
            return [
                'success' => true,
                'media_id' => 'sandbox_media_' . uniqid(),
                'url' => $publicUrl,
                'filename' => $filename,
                'mime_type' => $mimeType,
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphApiVersion}/{$account->phone_number_id}/media";

        try {
            $response = Http::withToken($account->access_token)
                ->attach('file', file_get_contents($file->getRealPath()), $filename, ['Content-Type' => $mimeType])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            $json = $response->json();

            if ($response->successful() && !empty($json['id'])) {
                return [
                    'success' => true,
                    'media_id' => $json['id'],
                    'url' => $publicUrl,
                    'filename' => $filename,
                    'mime_type' => $mimeType,
                ];
            }

            // If Meta API media endpoint fails, return public URL as fallback link
            return [
                'success' => true,
                'media_id' => null,
                'url' => $publicUrl,
                'filename' => $filename,
                'mime_type' => $mimeType,
            ];
        } catch (\Throwable $e) {
            Log::error('[MetaWhatsappService] uploadMedia error: ' . $e->getMessage());
            return [
                'success' => true,
                'media_id' => null,
                'url' => $publicUrl,
                'filename' => $filename,
                'mime_type' => $mimeType,
            ];
        }
    }
}

