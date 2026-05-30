<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs\ProcessWhatsAppWebhookJob;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhooks.
     * This endpoint has NO auth middleware — it's called by the WhatsApp provider.
     */
    public function handle(Request $request, WhatsAppAccount $account)
    {
        // 1. Validate webhook signature
        if (!$this->validateSignature($request, $account)) {
            Log::warning('Invalid webhook signature', [
                'account_id' => $account->id,
                'ip'         => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 2. Rate limiting
        $key = "webhook_rate:{$account->id}";
        if (cache()->get($key, 0) > 100) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }
        cache()->increment($key);
        cache()->put($key, cache()->get($key), 60);

        // 3. Determine event type from payload
        $eventType = $this->determineEventType($request->all());

        // 4. Dispatch to queue
        ProcessWhatsAppWebhookJob::dispatch(
            $account,
            $request->all(),
            $eventType
        )->onQueue('whatsapp-incoming');

        // 5. Respond immediately (webhook best practice)
        return response()->json(['status' => 'queued'], 200);
    }

    /**
     * Validate webhook signature using HMAC-SHA256.
     */
    protected function validateSignature(Request $request, WhatsAppAccount $account): bool
    {
        $secret = $account->provider_config['webhook_secret'] ?? null;

        // If no secret configured, skip validation (development mode)
        if (!$secret) {
            return true;
        }

        $signature = $request->header('X-Webhook-Signature')
                  ?? $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        // Handle "sha256=" prefix
        $signature = str_replace('sha256=', '', $signature);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Determine the event type from the webhook payload.
     */
    protected function determineEventType(array $payload): string
    {
        // Adapt to different provider payload formats
        if (isset($payload['event'])) {
            return $payload['event'];
        }

        if (isset($payload['entry'][0]['changes'][0]['value']['messages'])) {
            return 'message'; // WhatsApp Cloud API format
        }

        if (isset($payload['entry'][0]['changes'][0]['value']['statuses'])) {
            return 'status_update';
        }

        if (isset($payload['data']['event']) && str_contains($payload['data']['event'], 'connection')) {
            return 'connection';
        }

        if (isset($payload['type'])) {
            return match ($payload['type']) {
                'message', 'chat'         => 'message',
                'status', 'ack'           => 'status_update',
                'qr', 'ready', 'logout'   => 'connection',
                default                    => $payload['type'],
            };
        }

        return 'unknown';
    }
}
