<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalRequestedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $withdrawal;

    public function __construct($withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = ($this->withdrawal->amount ?? '');
        $currency = ($this->withdrawal->currency ?? '');

        return (new MailMessage)
            ->subject(__('general.notif_withdrawal_requested_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_withdrawal_requested_body', ['amount' => $amount, 'currency' => $currency]))
            ->action(__('general.view_wallet'), url('/app/wallet'));
    }

    public function toFcm(object $notifiable)
    {
        $amount = ($this->withdrawal->amount ?? '');
        $currency = ($this->withdrawal->currency ?? '');

        return $this->fcmMessage(
            __('general.notif_withdrawal_requested_title'),
            __('general.notif_withdrawal_requested_body', ['amount' => $amount, 'currency' => $currency]),
            [
                'url' => '/app/wallet',
                'type' => 'withdrawal_requested',
                'id' => (string) ($this->withdrawal->id ?? ''),
            ]
        );
    }
}
