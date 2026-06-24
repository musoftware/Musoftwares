<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaaSLimitApproachingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $usage;
    public $percentage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($usage, $percentage)
    {
        $this->usage = $usage;
        $this->percentage = $percentage;
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
                    ->subject('Warning: SaaS Limit Approaching')
                    ->greeting('Hello!')
                    ->line("Your SaaS limit for {$this->usage->usage_key} is approaching its threshold. You have used {$this->percentage}% of your limit.")
                    ->action('Upgrade Plan', url('/billing/subscriptions'))
                    ->line('Please consider upgrading your plan to avoid service interruption.');
    }
}
