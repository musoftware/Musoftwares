<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappContact;
use Modules\WhatsappSender\Models\WhatsappContactGroup;
use Modules\WhatsappSender\Models\WhatsappLog;
use Modules\WhatsappSender\Services\BotFlowEngineService;

class WhatsappWebhookController extends Controller
{
    /**
     * Handle Meta Webhook Verification GET Request for a specific Business Client.
     * Route: /api/v1/whatsapp/webhook/biz/{businessId}
     */
    public function verifyBusiness(Request $request, int $businessId)
    {
        $business = WhatsappBusiness::findOrFail($businessId);

        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if (empty($business->webhook_verify_token)) {
            $business->update(['webhook_verify_token' => 'biz_wt_' . Str::random(24)]);
        }

        $expectedToken = $business->webhook_verify_token;

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info("WhatsApp Meta Webhook Verification SUCCESSFUL for Business Client #{$businessId} ({$business->name})", [
                'business_id' => $businessId,
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge,
            ]);

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("WhatsApp Meta Webhook Verification FAILED for Business Client #{$businessId} - Mismatched Token", [
            'business_id' => $businessId,
            'received_token' => $token,
            'expected_token' => $expectedToken,
            'mode' => $mode,
        ]);

        return response('Forbidden - Invalid verify token for this business profile', 403);
    }

    /**
     * Handle Meta Incoming Webhook Events for a specific Business Client.
     * Route: /api/v1/whatsapp/webhook/biz/{businessId}
     */
    public function handleBusiness(Request $request, int $businessId)
    {
        $payload = $request->all();

        Log::info("WhatsApp Meta Webhook Event Received for Business Client #{$businessId}", [
            'business_id' => $businessId,
            'payload' => $payload,
        ]);

        $this->processWebhookPayload($payload, $businessId);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Fallback Global Webhook Verification GET Request.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = AdminSettings::GetValue('whatsapp_webhook_verify_token', 'musoftware_whatsapp_verify_token_2026');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('WhatsApp Meta Webhook Global Verification SUCCESSFUL', [
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge,
            ]);

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden - Invalid verify token', 403);
    }

    /**
     * Fallback Global Webhook Event Handler.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WhatsApp Meta Webhook Global Event Received', ['payload' => $payload]);

        $this->processWebhookPayload($payload, null);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Process incoming Meta Webhook payload for inbound messages, status updates, and CTWA ad referrals.
     */
    private function processWebhookPayload(array $payload, ?int $routeBusinessId = null): void
    {
        if (empty($payload['entry'])) {
            return;
        }

        foreach ($payload['entry'] as $entry) {
            if (empty($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                // 1. Resolve matching accounts and businesses via phone_number_id or routeBusinessId
                $matchingAccounts = collect();
                if ($phoneNumberId) {
                    $matchingAccounts = WhatsappAccount::where('phone_number_id', $phoneNumberId)->get();
                }

                if ($matchingAccounts->isEmpty() && $routeBusinessId) {
                    $matchingAccounts = WhatsappAccount::where('whatsapp_business_id', $routeBusinessId)->get();
                }

                if ($matchingAccounts->isEmpty()) {
                    $fallbackBusinessId = WhatsappBusiness::value('id');
                    if ($fallbackBusinessId) {
                        $fallbackAccount = WhatsappAccount::where('whatsapp_business_id', $fallbackBusinessId)->first();
                        if ($fallbackAccount) {
                            $matchingAccounts->push($fallbackAccount);
                        }
                    }
                }

                if ($matchingAccounts->isEmpty()) {
                    Log::warning('WhatsApp Webhook: Unable to resolve any Business Account for incoming payload', [
                        'phone_number_id' => $phoneNumberId,
                        'payload' => $value,
                    ]);
                    continue;
                }

                foreach ($matchingAccounts as $account) {
                    $businessId = $account->whatsapp_business_id;
                    $business = WhatsappBusiness::find($businessId);
                    if (!$business) {
                        continue;
                    }

                    // 2. Process Status Updates (sent, delivered, read, failed)
                    if (!empty($value['statuses'])) {
                        foreach ($value['statuses'] as $statusItem) {
                            $metaMsgId = $statusItem['id'] ?? null;
                            $status = $statusItem['status'] ?? null;

                            if ($metaMsgId && $status) {
                                $log = WhatsappLog::where('whatsapp_business_id', $businessId)
                                    ->where(function ($query) use ($metaMsgId) {
                                        $query->where('metadata->messages->0->id', $metaMsgId)
                                            ->orWhere('metadata->id', $metaMsgId)
                                            ->orWhere('meta_message_id', $metaMsgId);
                                    })
                                    ->first();

                                if ($log) {
                                    $log->update(['status' => $status]);
                                }
                            }
                        }
                    }

                    // 3. Process Inbound Customer Messages
                    if (!empty($value['messages'])) {
                        $botFlowEngine = app(BotFlowEngineService::class);
                        $contactsList = $value['contacts'] ?? [];
                        $senderName = null;
                        if (!empty($contactsList)) {
                            $senderName = $contactsList[0]['profile']['name'] ?? null;
                        }

                        foreach ($value['messages'] as $msg) {
                            $senderPhone = $msg['from'] ?? null;
                            if (!$senderPhone) {
                                continue;
                            }

                            // Deduplication: skip if this meta_message_id was already stored for this business
                            $metaMsgId = $msg['id'] ?? null;
                            if ($metaMsgId) {
                                $duplicateExists = WhatsappLog::where('whatsapp_business_id', $businessId)
                                    ->where('meta_message_id', $metaMsgId)
                                    ->exists();
                                if ($duplicateExists) {
                                    Log::info("WhatsApp Webhook: Skipping duplicate message {$metaMsgId} for Business #{$businessId}");
                                    continue;
                                }
                            }

                            $text = '';
                            $msgType = $msg['type'] ?? 'text';
                            if (isset($msg['interactive']) && $msg['interactive']['type'] === 'button_reply') {
                                $text = $msg['interactive']['button_reply']['title'] ?? $msg['interactive']['button_reply']['id'] ?? '';
                            } elseif (isset($msg['text']['body'])) {
                                $text = $msg['text']['body'];
                            } elseif (isset($msg['caption'])) {
                                $text = $msg['caption'];
                            }

                            $referral = $msg['referral'] ?? null;

                            Log::info("WhatsApp Inbound Customer Message Processed for Business #{$businessId}", [
                                'business_id' => $businessId,
                                'phone_number_id' => $phoneNumberId,
                                'from' => $senderPhone,
                                'type' => $msgType,
                                'body' => $text,
                                'referral' => $referral,
                            ]);

                            // Store inbound message into WhatsappLog for live CRM Inbox
                            WhatsappLog::create([
                                'user_id' => $business->user_id,
                                'whatsapp_account_id' => $account->id,
                                'whatsapp_business_id' => $businessId,
                                'recipient_phone' => $senderPhone,
                                'channel' => 'whatsapp',
                                'cost_charged' => 0.0000,
                                'message_type' => $msgType,
                                'message_body' => $text ?: "[{$msgType} message]",
                                'status' => 'inbound',
                                'direction' => 'inbound',
                                'meta_message_id' => $metaMsgId,
                                'payload' => array_merge($msg, [
                                    'referral' => $referral,
                                ]),
                            ]);

                            // Auto-upsert contact to business contact group if available
                            $group = WhatsappContactGroup::firstOrCreate(
                                ['whatsapp_business_id' => $businessId, 'name' => 'Inbound Customers'],
                                ['description' => 'Automatically created group for incoming customer inquiries']
                            );

                            $customFields = [];
                            if ($referral) {
                                $customFields['ctwa_clid'] = $referral['ctwa_clid'] ?? null;
                                $customFields['last_ad_headline'] = $referral['headline'] ?? null;
                                $customFields['last_ad_id'] = $referral['source_id'] ?? null;
                            }

                            WhatsappContact::updateOrCreate(
                                [
                                    'whatsapp_contact_group_id' => $group->id,
                                    'phone' => $senderPhone,
                                ],
                                [
                                    'name' => $senderName ?: "Customer {$senderPhone}",
                                    'custom_fields' => $customFields,
                                ]
                            );

                            if ($text !== '') {
                                $botFlowEngine->handleIncomingMessage('whatsapp', $businessId, $senderPhone, $text);
                            }
                        }
                    }
                }
            }
        }
    }
}
