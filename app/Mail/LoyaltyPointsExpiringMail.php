<?php

namespace App\Mail;

use App\Models\User;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class LoyaltyPointsExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientName;
    public string $clientFirstName;
    public int $pointsBalance;
    public string $pointsCashValueFormatted;
    public int $daysRemaining;
    public string $expiryDateFormatted;
    public string $expiryDateArabic;
    public string $quarterName;
    public string $tierName;
    public string $tierSlug;
    public int $tierDiscount;
    public string $portalUrl;
    public string $ticketsUrl;
    public string $invoicesUrl;

    public function __construct(
        public readonly User $user,
        ?int $pointsBalance = null,
        ?int $daysRemaining = null,
        ?Carbon $quarterEnd = null
    ) {
        $now = Carbon::now('Africa/Cairo');
        $quarterEnd = $quarterEnd ?? $now->copy()->endOfQuarter()->endOfDay();
        
        $this->clientName = $user->name ?? 'Valued Client';
        $this->clientFirstName = explode(' ', $this->clientName)[0];
        
        $this->pointsBalance = $pointsBalance ?? (int) ($user->loyalty_points_balance ?? 0);
        $rate = LoyaltyService::POINTS_TO_CURRENCY_RATE;
        $currencySymbol = '$';
        $cashVal = $this->pointsBalance * $rate;
        $this->pointsCashValueFormatted = $currencySymbol . number_format($cashVal, 2);

        $this->daysRemaining = $daysRemaining ?? max(0, (int) $now->diffInDays($quarterEnd, false));
        $this->expiryDateFormatted = $quarterEnd->format('M d, Y');
        $this->expiryDateArabic = $quarterEnd->locale('ar')->isoFormat('D MMMM YYYY');
        $this->quarterName = 'Q' . $quarterEnd->quarter . ' ' . $quarterEnd->year;

        $tier = $user->loyaltyTier;
        $this->tierName = $tier?->name ?? 'Standard';
        $this->tierSlug = strtolower($tier?->slug ?? 'standard');
        $this->tierDiscount = (int) ($tier?->discount_percentage ?? 0);

        $baseUrl = rtrim(config('app.url'), '/');
        $this->portalUrl = $baseUrl . '/client/loyalty';
        $this->ticketsUrl = $baseUrl . '/tickets/create';
        $this->invoicesUrl = $baseUrl . '/client/invoices';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تنبيه هام: رصيدك من نقاط الولاء ({$this->pointsBalance} PTS) ينتهي خلال {$this->daysRemaining} يوماً",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loyalty.points_expiring',
            with: [
                'clientName'                => $this->clientName,
                'clientFirstName'           => $this->clientFirstName,
                'pointsBalance'             => $this->pointsBalance,
                'pointsCashValueFormatted'  => $this->pointsCashValueFormatted,
                'daysRemaining'             => $this->daysRemaining,
                'expiryDateFormatted'       => $this->expiryDateFormatted,
                'expiryDateArabic'          => $this->expiryDateArabic,
                'quarterName'               => $this->quarterName,
                'tierName'                  => $this->tierName,
                'tierSlug'                  => $this->tierSlug,
                'tierDiscount'              => $this->tierDiscount,
                'portalUrl'                 => $this->portalUrl,
                'ticketsUrl'                => $this->ticketsUrl,
                'invoicesUrl'               => $this->invoicesUrl,
            ],
        );
    }

    /**
     * Helper to send directly to a user.
     */
    public static function sendToUser(User $user, ?int $pointsBalance = null, ?int $daysRemaining = null, ?Carbon $quarterEnd = null): void
    {
        if (empty($user->email)) {
            return;
        }

        Mail::to($user->email)->send(new self($user, $pointsBalance, $daysRemaining, $quarterEnd));
    }
}
