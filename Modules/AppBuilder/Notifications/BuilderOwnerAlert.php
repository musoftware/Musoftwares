<?php

namespace Modules\AppBuilder\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;

/**
 * A push to the owner's phone and browsers about the AI builder, sent through the site's own FCM channel (the one
 * the gold system uses too). Tapping it opens the page where the owner acts on it.
 */
class BuilderOwnerAlert extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public function __construct(public string $title, public string $body, public string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['fcm'];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        return $this->fcmMessage($this->title, $this->body, ['type' => 'builder_owner', 'url' => $this->url]);
    }
}
