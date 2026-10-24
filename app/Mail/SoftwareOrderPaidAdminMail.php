<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\SoftwareStore\Models\SoftwareOrder;

/** Short note to the owner when a store order is paid, so the hand-over on WhatsApp starts fast. */
class SoftwareOrderPaidAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SoftwareOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'طلب جديد مدفوع '.$this->order->reference().' - '.$this->order->product->name_en);
    }

    public function content(): Content
    {
        $order = $this->order;

        return new Content(
            view: 'emails.software_order_paid_admin',
            with: [
                'reference' => $order->reference(),
                'productName' => $order->product->name_ar ?: $order->product->name_en,
                'planName' => $order->plan_name,
                'amount' => number_format((float) $order->amount, 2).' '.$order->currency,
                'clientName' => $order->client_name,
                'clientEmail' => $order->client_email,
                'clientWhatsapp' => $order->client_whatsapp,
                'deviceId' => $order->device_id,
                'adminUrl' => rtrim((string) config('app.url'), '/').'/admin/software-products',
            ],
        );
    }
}
