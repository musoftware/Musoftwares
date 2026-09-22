<?php

namespace App\Mail;

use App\Models\MicroServiceOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminMicroServiceOrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly MicroServiceOrder $order
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->order->user?->name ?? 'العميل';
        $serviceTitle = $this->order->microService?->title ?? 'خدمة مصغرة';

        return new Envelope(
            subject: "طلب خدمة مصغرة جديد (#{$this->order->id}) - {$serviceTitle} من {$clientName}",
        );
    }

    public function content(): Content
    {
        $order = $this->order->loadMissing(['user', 'microService', 'currency']);
        $clientName = $order->user?->name ?? 'غير معروف';
        $clientEmail = $order->user?->email ?? 'غير محدد';
        $serviceTitle = $order->microService?->title ?? 'خدمة مصغرة';
        $amountFormatted = number_format((float) $order->amount_paid, 2) . ' ' . ($order->currency?->symbol ?? 'EGP');
        $createdAtCairo = Carbon::parse($order->created_at)->setTimezone('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $adminOrderUrl = $baseUrl . '/admin/micro-services';

        return new Content(
            view: 'emails.micro_services.order_admin',
            with: [
                'orderId' => $order->id,
                'serviceTitle' => $serviceTitle,
                'clientName' => $clientName,
                'clientEmail' => $clientEmail,
                'amountFormatted' => $amountFormatted,
                'requirements' => $order->requirements,
                'createdAtCairo' => $createdAtCairo,
                'adminOrderUrl' => $adminOrderUrl,
            ],
        );
    }
}
