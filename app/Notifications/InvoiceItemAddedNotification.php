<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceItemAddedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $invoice;

    public $item;

    public function __construct($invoice, $item)
    {
        $this->invoice = $invoice;
        $this->item = $item;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = ($this->item->item_title ?? __('general.notif_invoice_item_added_title'));

        return (new MailMessage)
            ->subject(__('general.notif_invoice_item_added_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_invoice_item_added_body', ['item' => $title]))
            ->action(__('general.view_invoice'), url('/app/invoices/'.($this->invoice->id ?? '')));
    }

    public function toFcm(object $notifiable)
    {
        $title = ($this->item->item_title ?? __('general.notif_invoice_item_added_title'));

        return $this->fcmMessage(
            __('general.notif_invoice_item_added_title'),
            __('general.notif_invoice_item_added_body', ['item' => $title]),
            [
                'url' => '/app/invoices/'.($this->invoice->id ?? ''),
                'type' => 'invoice_item_added',
                'id' => (string) ($this->invoice->id ?? ''),
            ]
        );
    }
}
