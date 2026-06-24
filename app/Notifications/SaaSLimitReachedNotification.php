<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaaSLimitReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $usage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($usage)
    {
        $this->usage = $usage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Alert: SaaS Limit Reached')
                    ->greeting('Hello!')
                    ->line("You have reached your SaaS limit for {$this->usage->usage_key}.")
                    ->action('Upgrade Plan', url('/billing/subscriptions'))
                    ->line('Please upgrade your plan to continue using this feature.');
    }
}
