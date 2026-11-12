<?php

namespace Modules\AppBuilder\Services;

use App\Helpers\FcmHelper;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Browser notifications for a customer who allowed them on the chat page, through the site's Firebase setup.
 * Sent when the build asks something, when the program is delivered and when a live trial is ready.
 */
class BuilderPush
{
    /** The page offers notifications only when the site has the web push key. */
    public static function enabled(): bool
    {
        return (string) config('services.fcm.web.vapid_key') !== '' && (string) config('services.fcm.web.api_key') !== '';
    }

    /** The public Firebase web config the chat page needs to ask for notifications. */
    public static function webConfig(): array
    {
        $web = (array) config('services.fcm.web');

        return [
            'config' => [
                'apiKey' => $web['api_key'] ?? '',
                'authDomain' => $web['auth_domain'] ?? '',
                'projectId' => $web['project_id'] ?? '',
                'messagingSenderId' => $web['messaging_sender_id'] ?? '',
                'appId' => $web['app_id'] ?? '',
            ],
            'vapidKey' => $web['vapid_key'] ?? '',
        ];
    }

    public function register(BuilderProject $project, string $token): void
    {
        $project->update(['push_token' => mb_substr($token, 0, 500)]);
    }

    /** A notification problem never breaks the build or the delivery. */
    public function notify(BuilderProject $project, string $key, array $replace = []): void
    {
        $language = $project->customerLanguage();
        $data = [
            'title' => __("builder.push.{$key}_title", $replace, $language),
            'description' => __("builder.push.{$key}_body", $replace + ['title' => (string) $project->title], $language),
        ];
        $this->toTelegram($project, $data, $language);
        if (! $project->push_token) {
            return;
        }

        try {
            FcmHelper::send_push_notif_to_device($project->push_token, $data, route('builder.show', $project));
        } catch (\Throwable $e) {
            Log::warning('App builder: push notification failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }

    /** The same news reaches the customer's Telegram when their account is linked there, with the chat one tap away. */
    private function toTelegram(BuilderProject $project, array $data, string $language): void
    {
        try {
            app(\App\Services\Telegram\CustomerAlerts::class)->send($project->user, $data['title']."\n".$data['description'], [
                [__('telegram.chat_open_page', [], $language), route('builder.show', $project)],
            ]);
        } catch (\Throwable $e) {
            Log::warning('App builder: Telegram alert failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }
}
