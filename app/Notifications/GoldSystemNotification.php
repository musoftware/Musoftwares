<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoldSystemNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    /**
     * Create a new notification instance.
     *
     * @param array<string> $channels
     */
    public function __construct(
        public string $title,
        public string $messageContent,
        public array $channels
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line($this->messageContent);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): ?string
    {
        return $this->title . ': ' . $this->messageContent;
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsapp(object $notifiable): ?string
    {
        return sprintf(
            "%s\n\n*%s*\n\n%s",
            __('general.hello_name', ['name' => $notifiable->name ?? '']),
            $this->title,
            $this->messageContent
        );
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return mixed
     */
    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            $this->title,
            $this->messageContent,
            [
                'type' => 'gold_notification',
            ]
        );
    }
}
