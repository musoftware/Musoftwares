<?php

namespace App\Mail;

use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoyaltyProgressMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $progressPct;
    public int $pointsNeeded;
    public int $currentBalance;
    public string $nextTierName;
    public string $currentTierName;
    public string $clientFirstName;

    public function __construct(
        private readonly User $user,
        private readonly LoyaltyTier $nextTier,
        int $progressPct,
    ) {
        $currentTier = $user->loyaltyTier;
        $lifetimePoints = (int) ($user->loyalty_lifetime_points ?? 0);

        $this->progressPct      = $progressPct;
        $this->pointsNeeded     = max(0, $nextTier->min_lifetime_points - $lifetimePoints);
        $this->currentBalance   = (int) ($user->loyalty_points_balance ?? 0);
        $this->nextTierName     = $nextTier->name;
        $this->currentTierName  = $currentTier?->name ?? 'Bronze';
        $this->clientFirstName  = explode(' ', $user->name)[0];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're {$this->pointsNeeded} points away from {$this->nextTierName} tier!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loyalty.progress',
            with: [
                'clientFirstName' => $this->clientFirstName,
                'progressPct'     => $this->progressPct,
                'pointsNeeded'    => $this->pointsNeeded,
                'currentBalance'  => $this->currentBalance,
                'nextTierName'    => $this->nextTierName,
                'currentTierName' => $this->currentTierName,
                'nextTierColor'   => $this->nextTier->badge_color,
                'nextTierDiscount' => $this->nextTier->discount_percentage,
                'nextTierPriority' => $this->nextTier->ticket_priority_level,
                'nextTierSlug'    => strtolower($this->nextTier->slug ?? $this->nextTier->name),
                'currentTierSlug' => strtolower($this->user->loyaltyTier?->slug ?? 'bronze'),
                'perks'           => $this->nextTier->perks_payload['perks'] ?? [],
            ],
        );
    }
}
