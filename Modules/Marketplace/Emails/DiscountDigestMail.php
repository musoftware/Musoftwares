<?php

namespace Modules\Marketplace\Emails;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DiscountDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param User $user
     * @param Collection $discountedServices
     */
    public function __construct(
        public readonly User $user,
        public readonly Collection $discountedServices
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔥 عروض وخصومات مميزة اليوم على خدمات المتجر | Exclusive Daily Marketplace Discounts',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.discount_digest',
            with: [
                'user' => $this->user,
                'discountedServices' => $this->discountedServices,
            ]
        );
    }
}
