<?php

namespace App\Notifications;

use App\Helpers\FinanceHelper;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Received: '.($this->invoice->invoice_number ?? 'Invoice'))
            ->greeting('Hello '.($notifiable->name ?? 'Customer').',')
            ->line('We have successfully received your payment of '.FinanceHelper::instance()->format_money($this->invoice->amount ?? $this->invoice->total ?? 0, $this->invoice->currency_id ?? null).'.')
            ->line(__('general.thank_you_for_your_business'));
    }

    public function toFcm(object $notifiable)
    {
        $formatted = FinanceHelper::instance()->format_money($this->invoice->amount ?? $this->invoice->total ?? 0, $this->invoice->currency_id ?? null);

        return $this->fcmMessage(
            __('general.notif_invoice_paid_title'),
            __('general.notif_invoice_paid_body', ['amount' => $formatted]),
            [
                'url' => '/app/invoices/'.($this->invoice->id ?? ''),
                'type' => 'invoice_paid',
                'id' => (string) ($this->invoice->id ?? ''),
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id ?? null,
            'amount' => $this->invoice->amount ?? $this->invoice->total ?? null,
            'currency' => $this->invoice->currency?->currency ?? null,
            'message' => 'Invoice '.($this->invoice->invoice_number ?? '').' has been marked as paid.',
        ];
    }
}
