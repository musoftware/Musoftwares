<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Services\RecoveryDiscount;

/** One reminder for a store order that was started and not paid, with the real recovery discount when it is on. */
class SoftwareOrderReminderMail extends Mailable
{
    public function __construct(
        public readonly SoftwareOrder $order,
        public readonly string $resumeUrl,
    ) {}

    public function envelope(): Envelope
    {
        $isAr = $this->isArabic();

        $percent = $this->discountedAmount() === null ? 0 : app(RecoveryDiscount::class)->percent();

        return new Envelope(
            subject: match (true) {
                $percent > 0 && $isAr => 'طلبك '.$this->order->reference().': خصم '.$percent.'% لو كمّلت خلال 72 ساعة',
                $percent > 0 => 'Your order '.$this->order->reference().': '.$percent.'% off if you finish within 72 hours',
                $isAr => 'طلبك '.$this->order->reference().' لسه ما اتدفعش',
                default => 'Your order '.$this->order->reference().' is not paid yet',
            },
        );
    }

    public function content(): Content
    {
        $product = $this->order->product;
        $isAr = $this->isArabic();
        $discounted = $this->discountedAmount();

        return new Content(
            view: 'emails.software_order_reminder',
            with: [
                'isAr' => $isAr,
                'name' => $this->order->client_name,
                'reference' => $this->order->reference(),
                'productName' => $isAr ? ($product->name_ar ?: $product->name_en) : ($product->name_en ?: $product->name_ar),
                'planName' => $this->order->plan_name,
                'amount' => number_format((float) $this->order->amount, 2).' '.$this->order->currency,
                'discountPercent' => $discounted === null ? 0 : app(RecoveryDiscount::class)->percent(),
                'discountedAmount' => $discounted === null ? null : number_format($discounted, 2).' '.$this->order->currency,
                'resumeUrl' => $this->resumeUrl,
                'whatsappUrl' => 'https://wa.me/201015218548',
            ],
        );
    }

    private function discountedAmount(): ?float
    {
        return app(RecoveryDiscount::class)->discountedAmount($this->order);
    }

    private function isArabic(): bool
    {
        return ($this->order->locale ?? 'ar') !== 'en';
    }
}
