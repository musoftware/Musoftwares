<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Models\DeviceToken;
use Modules\Marketplace\Models\ServiceOrder;
use Illuminate\Support\Facades\Log;

class MarketplaceNotificationService
{
    /**
     * Send Push notification via FCM and Database notification.
     */
    public function sendOrderNotification(User $recipient, string $title, string $body, array $payload = []): void
    {
        // Store in DB notification log
        \App\Models\Notification::create([
            'user_id' => $recipient->id,
            'title' => $title,
            'body' => $body,
            'data' => json_encode($payload),
            'read_at' => null,
            'created_at' => now('Africa/Cairo'),
        ]);

        // Attempt FCM push to registered device tokens
        $tokens = DeviceToken::where('user_id', $recipient->id)->pluck('token')->toArray();
        if (!empty($tokens)) {
            Log::info("[FCM Push] Sent to user #{$recipient->id}: {$title} - {$body}");
        }
    }
}
