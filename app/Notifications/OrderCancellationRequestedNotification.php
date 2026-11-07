<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\OrderCancellation;

/** Tells the other side of an order that a cancellation was requested and that they need to answer. */
class OrderCancellationRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ServiceOrder $order,
        public string $reason,
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
        return (new MailMessage)
            ->subject(__('marketplace.order_cancel_notice_subject', ['id' => $this->order->id]))
            ->line($this->body())
            ->line(__('marketplace.service_rejected_reason', ['reason' => $this->reason]))
            ->action(__('marketplace.order_cancel_notice_action'), route('marketplace.orders.show', $this->order->id));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'reason' => $this->reason,
            'message' => $this->body(),
            'url' => route('marketplace.orders.show', $this->order->id),
        ];
    }

    private function body(): string
    {
        return __('marketplace.order_cancel_notice_body', ['id' => $this->order->id, 'days' => OrderCancellation::RESPONSE_DAYS]);
    }
}
