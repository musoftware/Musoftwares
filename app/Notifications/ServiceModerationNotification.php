<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Marketplace\Models\Service;

/** Tells a seller that an admin approved or rejected their service. */
class ServiceModerationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Service $service,
        public string $decision,
        public ?string $reason = null,
    ) {
        $this->locale(config('app.locale'));
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = ['title' => $this->service->title];
        $mail = (new MailMessage)
            ->subject(__("marketplace.service_{$this->decision}_subject", $title))
            ->line(__("marketplace.service_{$this->decision}_body", $title));

        if ($this->reason) {
            $mail->line(__('marketplace.service_rejected_reason', ['reason' => $this->reason]));
        }

        return $mail->action(__('marketplace.service_moderation_action'), route('marketplace.services.edit', $this->service->id));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'service_id' => $this->service->id,
            'decision' => $this->decision,
            'reason' => $this->reason,
            'message' => __("marketplace.service_{$this->decision}_body", ['title' => $this->service->title]),
            'url' => route('marketplace.services.edit', $this->service->id),
        ];
    }
}
