<?php

namespace App\Helpers;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as KreaitNotification;

class FcmHelper
{
    public const DEFAULT_ANDROID_CHANNEL_ID = 'musoftwares';

    /**
     * Send a push notification to a single device.
     *
     * @param  string|array<string>  $fcmToken  Single token or list of tokens.
     * @param  array<string, mixed>  $data  Payload. Recognised keys: title, description, image,
     *                                      order_id, type, data_id, advertisement_id,
     *                                      conversation_id, module_id, sender_type, order_type.
     * @param  string|null  $webPushLink  Optional deep-link / click action URL.
     */
    public static function send_push_notif_to_device(string|array $fcmToken, array $data, ?string $webPushLink = null): bool
    {
        $tokens = is_array($fcmToken) ? array_values(array_filter($fcmToken, fn ($t) => is_string($t) && $t !== '')) : [$fcmToken];

        if (empty($tokens)) {
            return false;
        }

        $message = self::buildMessage($data, $webPushLink, withOrderId: true);

        try {
            \App\Jobs\SendFcmNotificationJob::dispatch($tokens, $message->toArray());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send a push notification to a topic.
     *
     * @param  array<string, mixed>  $data  Payload. Recognised keys: title, description, image,
     *                                      order_id, type, module_id, order_type, zone_id.
     * @param  string  $topic  FCM topic name.
     * @param  string  $type  Notification type — also used as the iOS body loc key.
     * @param  string|null  $webPushLink  Optional deep-link / click action URL.
     */
    public static function send_push_notif_to_topic(array $data, string $topic, string $type, ?string $webPushLink = null): bool
    {
        $message = self::buildMessage($data, $webPushLink, withOrderId: isset($data['order_id']), type: $type)
            ->withTopic($topic);

        try {
            \App\Jobs\SendFcmNotificationJob::dispatch([], $message->toArray());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Build a Kreait CloudMessage from a chart.cash-style data array.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function buildMessage(array $data, ?string $webPushLink, bool $withOrderId, ?string $type = null): CloudMessage
    {
        $title = (string) ($data['title'] ?? '');
        $body = (string) ($data['description'] ?? '');
        $image = (string) ($data['image'] ?? '');

        $payload = [
            'title' => $title,
            'body' => $body,
            'image' => $image,
            'order_id' => (string) ($data['order_id'] ?? ''),
            'type' => (string) ($data['type'] ?? $type ?? ''),
            'data_id' => (string) ($data['data_id'] ?? ''),
            'advertisement_id' => (string) ($data['advertisement_id'] ?? ''),
            'conversation_id' => (string) ($data['conversation_id'] ?? ''),
            'module_id' => (string) ($data['module_id'] ?? ''),
            'sender_type' => (string) ($data['sender_type'] ?? ''),
            'order_type' => (string) ($data['order_type'] ?? ''),
            'zone_id' => (string) ($data['zone_id'] ?? ''),
            'click_action' => (string) ($webPushLink ?? ''),
            'sound' => 'notification.wav',
        ];

        if ($type !== null) {
            $payload['body_loc_key'] = $type;
        }

        if (! $withOrderId) {
            $payload['order_id'] = '';
        }

        $payload = self::stringify($payload);

        $kreaitNotification = KreaitNotification::create($title, $body);

        if ($image !== '') {
            $kreaitNotification = $kreaitNotification->withImageUrl($image);
        }

        $channelId = (string) config('services.fcm.android_channel_id', self::DEFAULT_ANDROID_CHANNEL_ID);

        return CloudMessage::new()
            ->withNotification($kreaitNotification)
            ->withData($payload)
            ->withAndroidConfig([
                'notification' => [
                    'channel_id' => $channelId,
                    'sound' => 'notification.wav',
                ],
            ])
            ->withApnsConfig([
                'payload' => [
                    'aps' => [
                        'sound' => 'notification.wav',
                    ],
                ],
            ]);
    }

    /**
     * Coerce every value to a string — FCM data payloads must be string scalars.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected static function stringify(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        return $out;
    }

    protected static function messaging(): Messaging
    {
        return app('firebase.messaging');
    }
}
