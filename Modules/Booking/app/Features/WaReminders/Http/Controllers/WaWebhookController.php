<?php

namespace Modules\Booking\app\Features\WaReminders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\WaReminders\Services\WhatsAppProviderInterface;
use Modules\Booking\app\Features\WaReminders\Services\WhatsAppDeliveryTracker;

class WaWebhookController extends Controller
{
    protected $provider;
    protected $tracker;

    public function __construct(WhatsAppProviderInterface $provider, WhatsAppDeliveryTracker $tracker)
    {
        $this->provider = $provider;
        $this->tracker = $tracker;
    }

    public function handle(Request $request)
    {
        // Example structure. Actual implementation depends heavily on the provider (e.g. Twilio, Meta)
        // 1. Verify signature
        $signature = $request->header('X-Hub-Signature') ?? '';
        if (!$this->provider->verifyWebhookSignature($request->all(), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 2. Parse payload
        $events = $request->input('entry.0.changes.0.value.statuses', []); // Meta example structure

        foreach ($events as $event) {
            $messageId = $event['id'];
            $status = $event['status']; // sent, delivered, read, failed
            $errorReason = $event['errors'][0]['title'] ?? null;

            // 3. Update delivery tracker
            $this->tracker->updateDeliveryStatus($messageId, $status, $errorReason);
        }

        return response()->json(['status' => 'ok']);
    }
}
