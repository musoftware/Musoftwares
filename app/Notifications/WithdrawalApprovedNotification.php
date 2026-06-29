<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalApprovedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $withdrawal;

    public function __construct($withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Withdrawal Approved')
            ->greeting('Hello '.($notifiable->name ?? 'User').',')
            ->line('Your withdrawal request for '.($this->withdrawal->amount ?? '').' '.($this->withdrawal->currency ?? '').' has been approved.')
            ->line(__('general.the_funds_will_be_transferred_to_your_selected_payment_method_shortly'))
            ->action('View Wallet', url('/app/wallet'));
    }

    public function toFcm(object $notifiable)
    {
        $amount = ($this->withdrawal->amount ?? '');
        $currency = ($this->withdrawal->currency ?? '');

        return $this->fcmMessage(
            __('general.notif_withdrawal_approved_title'),
            __('general.notif_withdrawal_approved_body', ['amount' => $amount, 'currency' => $currency]),
            [
                'url' => '/app/wallet',
                'type' => 'withdrawal_approved',
                'id' => (string) ($this->withdrawal->id ?? ''),
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'withdrawal_id' => $this->withdrawal->id ?? null,
            'amount' => $this->withdrawal->amount ?? null,
            'currency' => $this->withdrawal->currency ?? null,
            'message' => 'Your withdrawal of '.($this->withdrawal->amount ?? '').' has been approved.',
        ];
    }
}
