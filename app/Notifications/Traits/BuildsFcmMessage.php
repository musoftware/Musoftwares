<?php

namespace App\Notifications\Traits;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Standardises the FCM payload across all notifications.
 *
 * The MuFcmChannel sends the SAME message to every device token via sendMulticast(),
 * which overrides the target — so we intentionally build the message WITHOUT a target.
 */
trait BuildsFcmMessage
{
    /**
     * Build a Kreait CloudMessage from a title/body plus deep-link data.
     *
     * @param  array<string,string>  $data  Deep-link payload (url/type/id). Values cast to string.
     */
    protected function fcmMessage(string $title, string $body, array $data = []): CloudMessage
    {
        $data = array_map('strval', $data);

        return CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);
    }
}
