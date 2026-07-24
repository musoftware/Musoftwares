<?php

namespace App\Notifications;

use App\Models\Message;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public Message $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        $senderName = $this->message->sender?->name ?? __('general.someone');
        $title = __('general.new_message_from', ['name' => $senderName]);
        $body = Str::limit($this->message->body ?? '', 100);

        $url = '/messages/' . $this->message->conversation_id;
        if ($this->message->conversation && $this->message->conversation->conversable_type === 'Modules\Marketplace\Models\ServiceOrder') {
            $url = '/marketplace/orders/' . $this->message->conversation->conversable_id;
        }

        return $this->fcmMessage(
            $title,
            $body,
            [
                'url' => $url,
                'type' => 'new_message',
                'conversation_id' => (string) $this->message->conversation_id,
                'message_id' => (string) $this->message->id,
            ]
        );
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $senderName = $this->message->sender?->name ?? __('general.someone');

        return [
            'conversation_id' => $this->message->conversation_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $senderName,
            'title' => __('general.new_message_from', ['name' => $senderName]),
            'body' => Str::limit($this->message->body ?? '', 100),
        ];
    }
}
