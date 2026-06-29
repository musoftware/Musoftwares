<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentFailedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $moduleName;

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed: Subscription Downgraded')
            ->greeting('Hello '.($notifiable->name ?? 'Customer').',')
            ->line("We were unable to process the automatic renewal for your {$this->moduleName} subscription because your wallet balance is insufficient.")
            ->line('As a result, your access to this module has been downgraded. Please add funds to your wallet and renew your subscription to restore your access.')
            ->action('Manage Subscriptions', url('/subscriptions/manage'))
            ->line(__('general.thank_you_for_your_business'));
    }

    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('general.notif_subscription_payment_failed_title'),
            __('general.notif_subscription_payment_failed_body', ['module' => $this->moduleName]),
            [
                'url' => '/subscriptions/manage',
                'type' => 'subscription_payment_failed',
                'module' => (string) $this->moduleName,
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'module_name' => $this->moduleName,
            'message' => "Automatic renewal for {$this->moduleName} failed due to insufficient funds.",
        ];
    }
}
