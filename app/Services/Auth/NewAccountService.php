<?php

namespace App\Services\Auth;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\IpGeolocationService;
use App\Services\LoyaltyService;
use App\Services\Privacy\ConsentAuditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/** What every new account needs, however it was opened: sign-up page or ordering as a guest. */
class NewAccountService
{
    public function __construct(
        private readonly ConsentAuditService $consents,
        private readonly IpGeolocationService $geolocation,
        private readonly LoyaltyService $loyalty,
    ) {}

    public function finish(User $user, Request $request): void
    {
        $this->recordConsents($user, $request);
        $this->assignCurrency($user, $request);
        $this->rewardReferrer($user, $this->linkReferrer($user, $request));

        event(new Registered($user));
    }

    /** Ties a user with no referrer to the marketer whose link they opened in this session. */
    public function linkReferrer(User $user, Request $request): ?User
    {
        $key = $request->session()->get('referral');
        if (! $key || $user->ref_user_id) {
            return null;
        }

        $referral = UserReferral::resolveRef((string) $key);
        if (! $referral || ! $referral->user_id || $referral->user_id === $user->id) {
            return null;
        }

        $user->forceFill(['ref_user_id' => $referral->user_id])->save();

        return $referral->user;
    }

    private function recordConsents(User $user, Request $request): void
    {
        $version = $this->consents->currentPolicyVersion();
        $types = [ConsentType::PrivacyPolicy, ConsentType::CrossBorderTransfer];
        if ($request->boolean('marketing_consent')) {
            $types = [...$types, ConsentType::DirectMarketingEmail, ConsentType::DirectMarketingSms];
        }

        foreach ($types as $type) {
            $this->consents->record($user, $type, ConsentAction::Granted, $request, $version);
        }

        Cookie::queue(Cookie::make(ConsentAuditService::ESSENTIAL_COOKIE_NAME, $version, 60 * 24 * 365));
    }

    private function assignCurrency(User $user, Request $request): void
    {
        $currency = $this->geolocation->getCurrencyForIp($request->ip()) ?? \App\Models\Currency::getDefault();
        if ($currency) {
            $user->forceFill(['currency_id' => $currency->id])->save();
        }
    }

    private function rewardReferrer(User $user, ?User $referrer): void
    {
        if (! $referrer) {
            return;
        }

        try {
            $this->loyalty->awardPointsForEvent(
                user: $referrer,
                eventType: 'referral_registered',
                reference: $user,
                context: ['referred_user_id' => $user->id, 'referred_user_name' => $user->name, 'channel' => 'referral_link'],
            );
        } catch (\Throwable $e) {
            Log::warning('Registration: referral loyalty award failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
