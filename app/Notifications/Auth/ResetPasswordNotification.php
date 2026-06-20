<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('general.reset_password_notification'))
            ->greeting(__('general.hello') . ' ' . ($notifiable->name ?? '') . ',')
            ->line(__('general.you_are_receiving_this_email_because_we_received_a_password_reset_request_for_your_account'))
            ->action(__('general.reset_password_1'), route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]))
            ->line(__('general.this_password_reset_link_will_expire_in_minutes', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('general.if_you_did_not_request_a_password_reset_no_further_action_is_required'));
    }
}
