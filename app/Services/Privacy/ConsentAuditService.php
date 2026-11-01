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
            $latest = $user->consentLogs()
                ->where('consent_type', $case->value)
                ->orderByDesc('id')
                ->first();

            $actionValue = $latest?->action instanceof ConsentAction
                ? $latest->action->value
                : (string) ($latest?->action ?? '');

            $states[$case->value] = [
                'granted' => $actionValue === ConsentAction::Granted->value,
                'policy_version' => $latest?->policy_version,
                'updated_at' => $latest?->created_at?->timezone('Africa/Cairo')->toIso8601String(),
            ];
        }

        return $states;
    }

    public function hasAcceptedCurrentEssentialPolicies(User $user): bool
    {
        $version = $this->currentPolicyVersion();

        $privacyLatest = $user->consentLogs()
            ->where('consent_type', ConsentType::PrivacyPolicy->value)
            ->orderByDesc('id')
            ->first();

        if (! $privacyLatest || $privacyLatest->policy_version !== $version) {
            return false;
        }

        $actionValue = $privacyLatest->action instanceof ConsentAction
            ? $privacyLatest->action->value
            : (string) $privacyLatest->action;

        return $actionValue === ConsentAction::Granted->value;
    }

    public function hasExplicitlyWithdrawnEssentialPolicy(User $user): bool
    {
        $latest = $user->consentLogs()
            ->where('consent_type', ConsentType::PrivacyPolicy->value)
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return false;
        }

        $actionValue = $latest->action instanceof ConsentAction
            ? $latest->action->value
            : (string) $latest->action;

        return $actionValue === ConsentAction::Withdrawn->value;
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
