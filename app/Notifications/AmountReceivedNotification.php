<?php

namespace App\Notifications;

use App\Models\Currency;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AmountReceivedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

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
        return ['mail', 'fcm'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $currencyName = Currency::find($this->currencyId)?->currency ?? '';

        return (new MailMessage)
            ->subject(__('general.notif_amount_received_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_amount_received_body', ['amount' => $this->amount, 'currency' => $currencyName]))
            ->line(__('general.thank_you_for_your_business'));
    }

    public function toFcm($notifiable)
    {
        $currencyName = Currency::find($this->currencyId)?->currency ?? '';

        return $this->fcmMessage(
            __('general.notif_amount_received_title'),
            __('general.notif_amount_received_body', ['amount' => $this->amount, 'currency' => $currencyName]),
            [
                'url' => '/app/wallet',
                'type' => 'amount_received',
                'amount' => (string) $this->amount,
            ]
        );
    }
}
