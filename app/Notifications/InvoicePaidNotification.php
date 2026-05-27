<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Payment Received: ' . ($this->invoice->invoice_number ?? 'Invoice'))
                    ->greeting('Hello ' . ($notifiable->name ?? 'Customer') . ',')
                    ->line('We have successfully received your payment of ' . \App\Helpers\FinanceHelper::instance()->format_money($this->invoice->amount ?? 0, $this->invoice->currency_id ?? null) . '.')
                    ->line('Thank you for your business!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id ?? null,
            'amount' => $this->invoice->amount ?? null,
            'currency' => $this->invoice->currency?->currency ?? null,
            'message' => 'Invoice ' . ($this->invoice->invoice_number ?? '') . ' has been marked as paid.',
        ];
    }
}
