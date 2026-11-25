<?php

namespace App\Notifications;

use App\Models\InvoiceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Asks the client to rate one finished task from a short link with a random code (no sign-in). */
class TaskRatingRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public InvoiceItem $item) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('service_pricing.mail_subject', ['title' => $this->item->item_title]))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('service_pricing.mail_body', ['title' => $this->item->item_title]))
            ->action(__('service_pricing.mail_action'), route('task-rating.show', $this->item->rating_token))
            ->line(__('service_pricing.mail_footer'));
    }
}
