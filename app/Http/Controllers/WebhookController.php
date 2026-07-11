<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookJob;
use App\Models\IncomingWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhooks centrally.
     */
    public function handle(Request $request, $source)
    {
        // 1. Basic validation of payload
        $payload = $request->all();
        $headers = $request->headers->all();

        if (empty($payload)) {
            Log::warning("Received empty webhook from {$source}");

            return response()->json(['error' => 'Empty payload'], 400);
        }

        // 2. Store the webhook for idempotency and processing
        try {
            $webhook = IncomingWebhook::create([
                'source' => $source,
                'event_type' => $this->determineEventType($source, $payload, $request),
                'payload' => $payload,
                'headers' => $headers,
                'status' => 'pending',
            ]);

            // 3. Dispatch Job to process in background (Queue)
            ProcessWebhookJob::dispatch($webhook);

            return response()->json(['status' => 'accepted', 'id' => $webhook->id], 202);
        } catch (\Exception $e) {
            Log::error("Failed to store incoming webhook from {$source}: ".$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Helper to determine event type based on source and payload.
     */
    private function determineEventType($source, $payload, Request $request)
    {
        switch ($source) {
            case 'kashier':
                return $payload['data']['status'] ?? 'unknown';
            case 'whatsapp':
                // Adjust based on whatsapp actual event types
                return $payload['entry'][0]['changes'][0]['field'] ?? 'unknown';
            case 'stripe':
                return $payload['type'] ?? 'unknown';
            default:
                return $request->header('X-Event-Type', 'unknown');
        }
    }
}
