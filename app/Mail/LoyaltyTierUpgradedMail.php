<?php

namespace App\Mail;

use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyTierUpgradedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientFirstName;

    public function __construct(
        private readonly User $user,
        private readonly LoyaltyTier $newTier,
        private readonly ?LoyaltyTier $previousTier,
    ) {
        $this->clientFirstName = explode(' ', $user->name)[0];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Congratulations! You've reached {$this->newTier->name} tier",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loyalty.tier_upgraded',
            with: [
                'clientFirstName'     => $this->clientFirstName,
                'newTierName'         => $this->newTier->name,
                'newTierColor'        => $this->newTier->badge_color,
                'newTierDiscount'     => $this->newTier->discount_percentage,
                'newTierPriority'     => $this->newTier->ticket_priority_level,
                'previousTierName'    => $this->previousTier?->name ?? 'Bronze',
                'loyaltyBalance'      => (int) ($this->user->loyalty_points_balance ?? 0),
            ],
        );
    }
}
