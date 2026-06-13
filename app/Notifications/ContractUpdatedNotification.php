<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Contract;

class ContractUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $contract;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = url('/c/' . $this->contract->uuid);

        return (new MailMessage)
                    ->subject(__('general.contract_update_subject', ['project' => $this->contract->project_name]))
                    ->greeting(__('general.hello'))
                    ->line(__('general.contract_update_message', ['project' => $this->contract->project_name]))
                    ->action(__('general.view_contract'), $actionUrl)
                    ->line(__('general.thank_you_for_business'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'uuid' => $this->contract->uuid,
        ];
    }
}
