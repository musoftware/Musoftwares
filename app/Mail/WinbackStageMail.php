<?php

namespace App\Mail;

use App\Models\User;
use App\Models\WinbackEngagement;
use App\Models\WinbackStage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class WinbackStageMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientFirstName;
    public int $loyaltyBalance;
    public int $loyaltyLifetimePoints;
    public string $currentTierName;
    public int $pointsToNextTier;

    public function __construct(
        private readonly User $user,
        private readonly WinbackStage $stage,
        private readonly WinbackEngagement $engagement,
        private readonly string $unsubscribeUrl,
    ) {
        $currentTier = $user->loyaltyTier;
        $nextTier = $currentTier ? \App\Models\LoyaltyTier::nextAfter($currentTier) : null;

        $this->clientFirstName      = explode(' ', $user->name)[0];
        $this->loyaltyBalance       = (int) ($user->loyalty_points_balance ?? 0);
        $this->loyaltyLifetimePoints = (int) ($user->loyalty_lifetime_points ?? 0);
        $this->currentTierName      = $currentTier?->name ?? 'Bronze';
        $this->pointsToNextTier     = $nextTier
            ? max(0, $nextTier->min_lifetime_points - $this->loyaltyLifetimePoints)
            : 0;
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'gentle_reminder'   => 'We miss you — and your points are waiting',
            'value_reminder'    => 'Look what\'s new on Musoftwares',
            'urgency_incentive' => 'A special bonus is waiting for you — limited time',
            'direct_check'      => 'Is everything okay? We\'d love to hear from you',
        ];

        $subject = $subjects[$this->stage->stage_slug] ?? 'We miss you at Musoftwares';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.winback.stage',
            with: [
                'stageSlug'          => $this->stage->stage_slug,
                'clientFirstName'    => $this->clientFirstName,
                'loyaltyBalance'     => $this->loyaltyBalance,
                'loyaltyLifetime'    => $this->loyaltyLifetimePoints,
                'currentTierName'    => $this->currentTierName,
                'pointsToNextTier'   => $this->pointsToNextTier,
                'unsubscribeUrl'     => $this->unsubscribeUrl,
                'hasIncentive'       => $this->stage->incentive_rule_id !== null,
                'incentivePoints'    => $this->stage->incentiveRule?->base_points ?? 0,
            ],
        );
    }

    /**
     * Static helper called by WinbackService — sends synchronously via queue.
     */
    public static function sendToUser(
        User $user,
        WinbackStage $stage,
        WinbackEngagement $engagement,
        string $unsubscribeUrl,
    ): void {
        if (empty($user->email)) {
            return;
        }

        Mail::to($user->email)->queue(new self($user, $stage, $engagement, $unsubscribeUrl));
    }
}
