<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $withdrawal;

    public function __construct($withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Withdrawal Approved')
                    ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
                    ->line('Your withdrawal request for ' . ($this->withdrawal->amount ?? '') . ' ' . ($this->withdrawal->currency ?? '') . ' has been approved.')
                    ->line('The funds will be transferred to your selected payment method shortly.')
                    ->action('View Wallet', url('/app/wallet'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'withdrawal_id' => $this->withdrawal->id ?? null,
            'amount' => $this->withdrawal->amount ?? null,
            'currency' => $this->withdrawal->currency ?? null,
            'message' => 'Your withdrawal of ' . ($this->withdrawal->amount ?? '') . ' has been approved.',
        ];
    }
}
