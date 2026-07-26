<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Handle Meta Webhook Verification GET Request (hub.mode, hub.verify_token, hub.challenge).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = AdminSettings::GetValue('whatsapp_webhook_verify_token', 'musoftware_whatsapp_verify_token_2026');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('WhatsApp Meta Webhook Verification SUCCESSFUL', [
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge,
            ]);

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Meta Webhook Verification FAILED - Mismatched Token', [
            'received_token' => $token,
            'expected_token' => $expectedToken,
            'mode' => $mode,
        ]);

        return response('Forbidden - Invalid verify token', 403);
    }

    /**
     * Handle Meta Incoming Webhook Events (Messages, Delivery Statuses: delivered, read, failed).
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WhatsApp Meta Webhook Event Received', ['payload' => $payload]);

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
                            $log = \Modules\WhatsappSender\Models\WhatsappLog::where('metadata->messages->0->id', $metaMsgId)
                                ->orWhere('metadata->id', $metaMsgId)
                                ->first();

                            if ($log) {
                                $log->update([
                                    'status' => $status,
                                ]);
                            }
                        }
                    }
                }

                // 2. Process Inbound Customer Messages
                if (! empty($value['messages'])) {
                    foreach ($value['messages'] as $msg) {
                        Log::info('WhatsApp Inbound Customer Message Received', [
                            'from' => $msg['from'] ?? null,
                            'type' => $msg['type'] ?? null,
                            'body' => $msg['text']['body'] ?? null,
                        ]);
                    }
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
