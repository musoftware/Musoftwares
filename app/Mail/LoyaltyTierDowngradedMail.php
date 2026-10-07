<?php

namespace App\Mail;

use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyTierDowngradedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientFirstName;

    public function __construct(
        private readonly User $user,
        private readonly LoyaltyTier $newTier,
        private readonly ?LoyaltyTier $previousTier,
        private readonly ?string $reason = null,
    ) {
        $this->clientFirstName = explode(' ', $user->name)[0];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Partnership Tier Update: {$this->newTier->name} Tier",
        );
    }

    public function content(): Content
    {
        $currentLifetime = (int) ($this->user->loyalty_lifetime_points ?? 0);
        $previousMin = $this->previousTier ? (int) $this->previousTier->min_lifetime_points : 0;
        $pointsToRegain = max(0, $previousMin - $currentLifetime);

        return new Content(
            view: 'emails.loyalty.tier_downgraded',
            with: [
                'clientFirstName'  => $this->clientFirstName,
                'newTierName'      => $this->newTier->name,
                'newTierColor'     => $this->newTier->badge_color ?? '#64748b',
                'newTierDiscount'  => $this->newTier->discount_percentage,
                'newTierPriority'  => $this->newTier->ticket_priority_level,
                'previousTierName' => $this->previousTier?->name ?? 'Higher Tier',
                'loyaltyBalance'   => (int) ($this->user->loyalty_points_balance ?? 0),
                'lifetimePoints'   => $currentLifetime,
                'pointsToRegain'   => $pointsToRegain,
                'newTierSlug'      => strtolower($this->newTier->slug ?? $this->newTier->name),
                'perks'            => $this->newTier->perks_payload['perks'] ?? [],
                'reason'           => $this->reason,
            ],
        );
    }
}
