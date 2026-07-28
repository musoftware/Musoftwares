<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappLog;

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

        // Ensure business has a verify token
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
        $business = WhatsappBusiness::findOrFail($businessId);
        $payload = $request->all();

        Log::info("WhatsApp Meta Webhook Event Received for Business Client #{$businessId} ({$business->name})", [
            'business_id' => $businessId,
            'payload' => $payload,
        ]);

        if (empty($payload['entry'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        foreach ($payload['entry'] as $entry) {
            if (empty($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];

                // 1. Process Status Updates (sent, delivered, read, failed)
                if (! empty($value['statuses'])) {
                    foreach ($value['statuses'] as $statusItem) {
                        $metaMsgId = $statusItem['id'] ?? null;
                        $status = $statusItem['status'] ?? null;

                        if ($metaMsgId && $status) {
                            $log = WhatsappLog::where('whatsapp_business_id', $businessId)
                                ->where(function ($query) use ($metaMsgId) {
                                    $query->where('metadata->messages->0->id', $metaMsgId)
                                        ->orWhere('metadata->id', $metaMsgId);
                                })
                                ->first();

                            if ($log) {
                                $log->update(['status' => $status]);
                            }
                        }
                    }
                }

                // 2. Process Inbound Customer Messages
                if (! empty($value['messages'])) {
                    $botFlowEngine = app(\Modules\WhatsappSender\Services\BotFlowEngineService::class);
                    foreach ($value['messages'] as $msg) {
                        $senderPhone = $msg['from'] ?? null;
                        if (!$senderPhone) continue;

                        $text = '';
                        if (isset($msg['interactive']) && $msg['interactive']['type'] === 'button_reply') {
                            // Extract quick reply button ID (which is the target node ID)
                            $text = $msg['interactive']['button_reply']['id'] ?? '';
                        } elseif (isset($msg['text']['body'])) {
                            $text = $msg['text']['body'];
                        }

                        Log::info("WhatsApp Inbound Customer Message for Business #{$businessId}", [
                            'business_id' => $businessId,
                            'from' => $senderPhone,
                            'type' => $msg['type'] ?? null,
                            'body' => $text,
                        ]);

                        if ($text !== '') {
                            $botFlowEngine->handleIncomingMessage('whatsapp', $businessId, $senderPhone, $text);
                        }
                    }
                }
            }
        }

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

        return response()->json(['status' => 'success'], 200);
    }
}
