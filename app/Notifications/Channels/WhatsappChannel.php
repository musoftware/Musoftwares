<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp delivery channel.
 *
 * Resolves the recipient's whatsapp_number via the notifiable's
 * routeNotificationForWhatsapp() hook (User model exposes this),
 * then delegates to App\Services\WhatsAppNotificationService when present.
 *
 * Like SmsChannel, this is intentionally defensive: if the underlying service
 * is not registered (class does not exist) we log the payload so the rest of
 * the pipeline does not break.
 */
class WhatsappChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $phone = method_exists($notifiable, 'routeNotificationForWhatsapp')
            ? $notifiable->routeNotificationForWhatsapp($notification)
            : ($notifiable->whatsapp_number ?? null);

        if (empty($phone)) {
            return;
        }

        $message = method_exists($notification, 'toWhatsapp')
            ? $notification->toWhatsapp($notifiable)
            : null;

        if ($message === null) {
            return;
        }

        if (class_exists(\App\Services\WhatsAppNotificationService::class)) {
            try {
                app(\App\Services\WhatsAppNotificationService::class)
                    ->send($phone, (string) $message);

                return;
            } catch (\Throwable $e) {
                Log::warning('WhatsappChannel: service threw, falling back to log', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('WhatsappChannel: no service configured, payload logged only', [
            'phone' => $phone,
            'message' => (string) $message,
            'notification' => get_class($notification),
        ]);
    }
}
