<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification as KreaitNotification;

class NotificationHelper
{
    protected static ?self $instance = null;

    public static function instance(): ?self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function get_string(DatabaseNotification $notification): string
    {
        return (string) ($notification->data['string_data'] ?? $notification->data['title'] ?? '');
    }

    public function get_icon(DatabaseNotification $notification): string
    {
        return (string) ($notification->data['icon'] ?? '');
    }

    public function NotifyUser(User $user, object $notification, ?string $link = null, array $customData = []): bool
    {
        $tokens = $this->resolveTokens($user);

        if (empty($tokens)) {
            return false;
        }

        $title = (string) ($notification->title ?? '');
        $body = (string) ($notification->body ?? '');

        if ($title === '' && method_exists($notification, 'toFcm')) {
            return $this->sendViaNotificationObject($user, $notification, $link);
        }

        return $this->sendMulticast($user, $title, $body, $link, $customData);
    }

    public function sendToUser(User $user, object $notification, ?string $link = null, array $customData = []): bool
    {
        return $this->NotifyUser($user, $notification, $link, $customData);
    }

    public function sendFcmMessage(
        User|string|array $target,
        string $title,
        string $body,
        ?string $image = null,
        ?string $link = null,
        array $customData = [],
        bool $android = true
    ): bool {
        $tokens = $this->resolveTokens($target);

        if (empty($tokens)) {
            return false;
        }

        return $this->sendMulticast($target instanceof User ? $target : null, $title, $body, $link, $customData, $image, $android);
    }

    public function NotifyBySystemUser(?User $user, object $notification, ?string $link = null): void
    {
        if ($user === null) {
            return;
        }

        if (method_exists($notification, 'toFcm')) {
            $this->sendViaNotificationObject($user, $notification, $link);

            return;
        }

        $tokens = $this->resolveTokens($user);
        if (empty($tokens)) {
            return;
        }

        $this->sendMulticast(
            $user,
            (string) ($notification->title ?? ''),
            (string) ($notification->body ?? ''),
            $link,
            ['type' => 'system']
        );
    }

    protected function sendViaNotificationObject(User $user, object $notification, ?string $link): bool
    {
        $tokens = $this->resolveTokens($user);
        if (empty($tokens)) {
            return false;
        }

        $messaging = $this->messaging();

        $message = $notification->toFcm($user);

        if ($link !== null && $link !== '') {
            $message = $message->withData(['url' => $link, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
        }

        try {
            $messaging->sendMulticast($message, $tokens);

            return true;
        } catch (MessagingException) {
            return false;
        }
    }

    protected function sendMulticast(
        ?User $user,
        string $title,
        string $body,
        ?string $link,
        array $customData = [],
        ?string $image = null,
        bool $android = true
    ): bool {
        $tokens = $user !== null ? $this->resolveTokens($user) : [];
        if (empty($tokens)) {
            return false;
        }

        $data = array_merge([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'title' => $title,
            'body' => $body,
            'link' => (string) ($link ?? ''),
        ], $this->stringify($customData));

        $kreaitNotification = KreaitNotification::create($title, $body);

        if ($image !== null && $image !== '') {
            $kreaitNotification = $kreaitNotification->withImageUrl($image);
        }

        $message = CloudMessage::new()
            ->withNotification($kreaitNotification)
            ->withData($data);

        if ($android) {
            $message = $message->withAndroidConfig([
                'notification' => [
                    'sound' => 'notification',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'color' => '#1976D2',
                ],
                'fcm_options' => [
                    'analytics_label' => $data['analytics_label'] ?? 'android_general',
                ],
            ]);
        }

        $message = $message->withApnsConfig([
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                ],
            ],
            'fcm_options' => [
                'analytics_label' => $data['analytics_label'] ?? 'ios_general',
            ],
        ]);

        try {
            $report = $this->messaging()->sendMulticast($message, $tokens);

            return $report instanceof MulticastSendReport;
        } catch (MessagingException) {
            return false;
        }
    }

    protected function resolveTokens(User|string|array $target): array
    {
        if (is_array($target)) {
            return array_values(array_filter($target, fn ($t) => is_string($t) && $t !== ''));
        }

        if (is_string($target)) {
            return $target === '' ? [] : [$target];
        }

        if (method_exists($target, 'routeNotificationForFcm')) {
            $routed = $target->routeNotificationForFcm(null);
            if (is_array($routed)) {
                return array_values(array_filter($routed, fn ($t) => is_string($t) && $t !== ''));
            }
            if (is_string($routed) && $routed !== '') {
                return [$routed];
            }
        }

        $tokens = $target->deviceTokens()->pluck('token')->all();

        if (empty($tokens) && ! empty($target->fcm_token)) {
            return [$target->fcm_token];
        }

        return array_values(array_filter($tokens, fn ($t) => is_string($t) && $t !== ''));
    }

    protected function stringify(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        return $out;
    }

    protected function messaging(): Messaging
    {
        return app('firebase.messaging');
    }
}