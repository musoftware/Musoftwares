<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AmountReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $amount;
    public $currencyId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($amount, $currencyId)
    {
        $this->amount = $amount;
        $this->currencyId = $currencyId;
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
        $currencyName = \App\Models\Currency::find($this->currencyId)?->currency ?? '';

        return (new MailMessage)
                    ->subject('Amount Received')
                    ->greeting('Hello!')
                    ->line("We have successfully received an amount of {$this->amount} {$currencyName}.")
                    ->line('Thank you for using our application!');
    }
}
