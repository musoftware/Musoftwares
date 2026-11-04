<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Models\AdminSettings;
use App\Models\PolicyAgreement;
use App\Models\TermsAgreement;
use App\Models\User;
use App\Models\UserConsentLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConsentAuditService
{
    public const DEFAULT_POLICY_VERSION = 'v2.0-2026';

    public const ESSENTIAL_COOKIE_NAME = 'pdpl_essential_consent';

    public const TRACKING_COOKIE_NAME = 'pdpl_cookies_tracking';

    public const DECLINED_COOKIE_NAME = 'pdpl_essential_declined';

    public function currentPolicyVersion(): string
    {
        $configured = AdminSettings::GetValue('privacy_policy_version', self::DEFAULT_POLICY_VERSION);

        return is_string($configured) && trim($configured) !== ''
            ? trim($configured)
            : self::DEFAULT_POLICY_VERSION;
    }

    public function record(
        ?User $user,
        ConsentType $consentType,
        ConsentAction $action,
        ?Request $request = null,
        ?string $policyVersion = null
    ): UserConsentLog {
        $version = $policyVersion ?: $this->currentPolicyVersion();
        $sessionId = $this->resolveSessionIdentifier($request);

        $entry = UserConsentLog::create([
            'user_id' => $user?->id,
            'session_identifier' => $sessionId,
            'consent_type' => $consentType,
            'action' => $action,
            'policy_version' => $version,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        $user?->unsetRelation('consentLogs');

        if ($user && $consentType === ConsentType::PrivacyPolicy && $action === ConsentAction::Granted) {
            $this->syncLegacyAgreementTables($user, $version, $request);
        }

        return $entry;
    }

    /**
     * @return array<string, array{granted: bool, policy_version: string|null, updated_at: string|null}>
     */
    public function currentStatesForUser(User $user): array
    {
        $states = [];
        foreach (ConsentType::cases() as $case) {
            $latest = $this->latestConsent($user, $case);

            $states[$case->value] = [
                'granted' => $this->actionValue($latest) === ConsentAction::Granted->value,
                'policy_version' => $latest?->policy_version,
                'updated_at' => $latest?->created_at?->timezone('Africa/Cairo')->toIso8601String(),
            ];
        }

        return $states;
    }

    public function hasAcceptedCurrentEssentialPolicies(User $user): bool
    {
        $version = $this->currentPolicyVersion();

        $privacyLatest = $this->latestConsent($user, ConsentType::PrivacyPolicy);

        if (! $privacyLatest || $privacyLatest->policy_version !== $version) {
            return false;
        }

        return $this->actionValue($privacyLatest) === ConsentAction::Granted->value;
    }

    public function hasExplicitlyWithdrawnEssentialPolicy(User $user): bool
    {
        $latest = $this->latestConsent($user, ConsentType::PrivacyPolicy);

        return $this->actionValue($latest) === ConsentAction::Withdrawn->value;
    }

    /**
     * Latest log entry of one consent type. All of the user's logs load once per request
     * (record() clears the cache), so several checks share one query.
     */
    private function latestConsent(User $user, ConsentType $type): ?UserConsentLog
    {
        $user->loadMissing(['consentLogs' => fn ($query) => $query->orderByDesc('id')]);

        return $user->consentLogs->first(function (UserConsentLog $log) use ($type): bool {
            $logType = $log->consent_type instanceof ConsentType ? $log->consent_type : ConsentType::tryFrom((string) $log->consent_type);

            return $logType === $type;
        });
    }

    private function actionValue(?UserConsentLog $log): string
    {
        if (! $log) {
            return '';
        }

        return $log->action instanceof ConsentAction ? $log->action->value : (string) $log->action;
    }

    /**
     * Resolve active consent and tracking state for the current HTTP request (user or guest cookie).
     *
     * @return array{policyVersion: string, hasEssentialConsent: bool, isBlocked: bool, cookiesTracking: string}
     */
    public function resolveRequestConsentState(Request $request): array
    {
        $version = $this->currentPolicyVersion();
        $user = $request->user();

        $hasUserEssential = $user ? $this->hasAcceptedCurrentEssentialPolicies($user) : false;
        $hasCookieEssential = $request->cookie(self::ESSENTIAL_COOKIE_NAME) === $version;
        $isBlocked = $request->cookie(self::DECLINED_COOKIE_NAME) === '1'
            || ($user ? $this->hasExplicitlyWithdrawnEssentialPolicy($user) : false);

        $cookiesTracking = $this->resolveTrackingPreference($request, $user);

        return [
            'policyVersion' => $version,
            'hasEssentialConsent' => ! $isBlocked && ($hasUserEssential || $hasCookieEssential),
            'isBlocked' => $isBlocked,
            'cookiesTracking' => $isBlocked ? ConsentAction::Withdrawn->value : $cookiesTracking,
        ];
    }

    private function resolveTrackingPreference(Request $request, ?User $user): string
    {
        $latest = $user ? $this->latestConsent($user, ConsentType::CookiesTracking) : null;
        if ($latest) {
            return $this->actionValue($latest);
        }

        $cookieVal = (string) $request->cookie(self::TRACKING_COOKIE_NAME, '');
        if (in_array($cookieVal, [ConsentAction::Granted->value, ConsentAction::Withdrawn->value], true)) {
            return $cookieVal;
        }

        return 'unset';
    }

    public function canSendMarketingEmail(User $user): bool
    {
        if (empty($user->email) || $user->winback_unsubscribed_at !== null) {
            return false;
        }

        return $user->hasGrantedConsent(ConsentType::DirectMarketingEmail);
    }

    public function canSendMarketingSms(User $user): bool
    {
        return $user->hasGrantedConsent(ConsentType::DirectMarketingSms);
    }

    /**
     * Scope query to users whose latest direct_marketing_email consent entry is granted.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeGrantedMarketingEmail(Builder $query): Builder
    {
        return $query
            ->whereNull('winback_unsubscribed_at')
            ->whereRaw(
                "(SELECT ucl.action FROM user_consents_log ucl WHERE ucl.user_id = users.id AND ucl.consent_type = ? ORDER BY ucl.id DESC LIMIT 1) = ?",
                [ConsentType::DirectMarketingEmail->value, ConsentAction::Granted->value]
            );
    }

    public function generateUnsubscribeUrl(User $user): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute(
            'marketing.unsubscribe',
            ['user' => $user->id],
            now()->addYears(3)
        );
    }

    private function resolveSessionIdentifier(?Request $request): ?string
    {
        if (! $request || ! $request->hasSession()) {
            return null;
        }

        return hash('sha256', (string) $request->session()->getId());
    }

    private function syncLegacyAgreementTables(User $user, string $version, ?Request $request): void
    {
        $shared = [
            'full_name' => $user->name ?: 'User',
            'phone' => $user->mobile_1 ?? $user->phone,
            'ip_address' => $request?->ip() ?? '0.0.0.0',
            'user_agent' => $request?->userAgent(),
            'agreed_at' => now(),
            'version' => $version,
        ];

        PolicyAgreement::updateOrCreate(['user_id' => $user->id], $shared);
        TermsAgreement::updateOrCreate(['user_id' => $user->id], array_merge($shared, ['is_latest_version' => true]));
    }
}
