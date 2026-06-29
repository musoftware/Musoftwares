<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSignedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $contract;

    public function __construct($contract)
    {
        $this->contract = $contract;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('general.notif_contract_signed_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_contract_signed_body'))
            ->action(__('general.view_contracts'), url('/app/contracts'));
    }

    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('general.notif_contract_signed_title'),
            __('general.notif_contract_signed_body'),
            [
                'url' => '/app/contracts',
                'type' => 'contract_signed',
                'id' => (string) ($this->contract->id ?? ''),
            ]
        );
    }
}
