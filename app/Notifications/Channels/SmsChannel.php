<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🚨 CRITICAL SYSTEM NOTICE - DO NOT MODIFY SMS SYSTEM 🚨
 * 🔴 تحذير هام جداً: سيستم الـ SMS حساس لأقصى درجة! ممنوع التعديل عليه أو تغيير منطق العمل نهائياً.
 * 🔴 DO NOT ALTER OR TOUCH THE SMS SYSTEM / SMS CHANNEL LOGIC. EXTREMELY SENSITIVE.
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * SMS delivery channel.
 *
 * The actual SMS gateway (Twilio, Vonage, Infobip, etc.) is intentionally
 * abstracted. If a gateway service class is bound we delegate to it; otherwise
 * we log the payload so operators can wire the channel without breaking the
 * rest of the notification flow.
 */
class SmsChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $phone = method_exists($notifiable, 'routeNotificationForSms')
            ? $notifiable->routeNotificationForSms($notification)
            : ($notifiable->phone ?? $notifiable->mobile ?? null);

        if (empty($phone)) {
            return;
        }

        $message = method_exists($notification, 'toSms')
            ? $notification->toSms($notifiable)
            : null;

        if ($message === null) {
            return;
        }

        $service = config('services.sms.class');

        if ($service && class_exists($service)) {
            try {
                app($service)->send($phone, (string) $message);

                return;
            } catch (\Throwable $e) {
                Log::warning('SmsChannel: gateway threw, falling back to log', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SmsChannel: no SMS gateway configured, payload logged only', [
            'phone' => $phone,
            'message' => (string) $message,
            'notification' => get_class($notification),
        ]);
    }
}
