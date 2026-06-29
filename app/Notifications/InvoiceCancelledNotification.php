<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCancelledNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('general.notif_invoice_cancelled_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_invoice_cancelled_body', ['invoice' => ($this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''))]))
            ->action(__('general.view_invoice'), url('/app/invoices/'.($this->invoice->id ?? '')));
    }

    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('general.notif_invoice_cancelled_title'),
            __('general.notif_invoice_cancelled_body', ['invoice' => ($this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''))]),
            [
                'url' => '/app/invoices/'.($this->invoice->id ?? ''),
                'type' => 'invoice_cancelled',
                'id' => (string) ($this->invoice->id ?? ''),
            ]
        );
    }
}
