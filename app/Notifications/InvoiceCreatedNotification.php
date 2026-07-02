<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return \App\Models\AdminSettings::invoiceNotificationChannels('invoice_created');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('general.notif_invoice_created_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_invoice_created_body', ['invoice' => ($this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''))]))
            ->action(__('general.view_invoice'), url('/app/invoices/'.($this->invoice->id ?? '')));
    }

    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('general.notif_invoice_created_title'),
            __('general.notif_invoice_created_body', ['invoice' => ($this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''))]),
            [
                'url' => '/app/invoices/'.($this->invoice->id ?? ''),
                'type' => 'invoice_created',
                'id' => (string) ($this->invoice->id ?? ''),
            ]
        );
    }

    /**
     * SMS payload — kept short to fit per-gateway segment limits.
     */
    public function toSms(object $notifiable): ?string
    {
        $amount = $this->invoice->total() ?? $this->invoice->unpaid ?? '';
        $currency = $this->invoice->currency_id ?? '';

        return sprintf(
            'New invoice %s for %s %s. View: %s',
            $this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''),
            $amount,
            $currency,
            url('/app/invoices/'.($this->invoice->id ?? ''))
        );
    }

    /**
     * WhatsApp payload — mirrors the mail body but in plain text.
     */
    public function toWhatsapp(object $notifiable): ?string
    {
        return sprintf(
            "%s\nInvoice %s has been created.\nView: %s",
            __('general.hello_name', ['name' => $notifiable->name ?? '']),
            $this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''),
            url('/app/invoices/'.($this->invoice->id ?? ''))
        );
    }
}
