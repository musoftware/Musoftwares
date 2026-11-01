<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Models\AdminSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataRetentionService
{
    public const DEFAULT_UNVERIFIED_DAYS = 30;

    public const DEFAULT_INACTIVE_MONTHS = 24;

    /**
     * Run full automated PDPL data retention cleanup and return summary metrics.
     *
     * @return array<string, int>
     */
    public function runScheduledCleanup(): array
    {
        $unverifiedDays = max(7, (int) AdminSettings::GetValue('data_retention_unverified_days', self::DEFAULT_UNVERIFIED_DAYS));
        $inactiveMonths = max(6, (int) AdminSettings::GetValue('data_retention_inactive_months', self::DEFAULT_INACTIVE_MONTHS));

        return [
            'expired_tokens_purged' => $this->purgeExpiredVerificationAndAuthTokens(),
            'temp_logs_purged' => $this->purgeTemporaryLogs(),
            'unverified_accounts_anonymized' => $this->anonymizeUnverifiedAccounts($unverifiedDays),
            'abandoned_accounts_anonymized' => $this->anonymizeAbandonedAccounts($inactiveMonths),
        ];
    }

    /**
     * Scrub PII while keeping invoice and tax ledger references intact via SoftDeletes.
     */
    public function anonymizeAndSoftDeleteUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $token = bin2hex(random_bytes(4));
            $existingColumns = array_flip(Schema::getColumnListing('users'));

            $user->emails()->delete();
            $user->tokens()->delete();

            $candidateScrub = [
                'name' => "Anonymized User #{$user->id}",
                'email' => "deleted-{$user->id}-{$token}@anonymized.invalid",
                'phone' => null,
                'phone_number' => null,
                'mobile_1' => null,
                'mobile_2' => null,
                'whatsapp_number' => null,
                'telegram_username' => null,
                'avatar' => null,
                'openai_api_key' => null,
                'gemini_api' => null,
                'autosms_verification_secret' => null,
                'kyc_notes' => null,
                'enable_notifications' => false,
                'winback_unsubscribed_at' => now(),
            ];

            $scrubbed = array_intersect_key($candidateScrub, $existingColumns);
            $user->forceFill($scrubbed)->save();

            $user->delete();
        });
    }

    private function purgeExpiredVerificationAndAuthTokens(): int
    {
        $purged = 0;

        foreach (['password_reset_tokens', 'password_resets'] as $resetTable) {
            if (Schema::hasTable($resetTable)) {
                $purged += DB::table($resetTable)
                    ->where('created_at', '<', now()->subHours(24))
                    ->delete();
            }
        }

        if (Schema::hasTable('sso_tokens')) {
            $purged += DB::table('sso_tokens')
                ->where(function ($q): void {
                    $q->where('expires_at', '<', now())
                        ->orWhere('created_at', '<', now()->subHours(24));
                })
                ->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $purged += DB::table('personal_access_tokens')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->delete();
        }

        return $purged;
    }

    private function purgeTemporaryLogs(): int
    {
        $purged = 0;

        if (Schema::hasTable('sessions')) {
            $purged += DB::table('sessions')
                ->where('last_activity', '<', now()->subDays(30)->getTimestamp())
                ->delete();
        }

        if (Schema::hasTable('email_tracking_events')) {
            $purged += DB::table('email_tracking_events')
                ->where('created_at', '<', now()->subDays(90))
                ->delete();
        }

        if (Schema::hasTable('outgoing_emails')) {
            $purged += DB::table('outgoing_emails')
                ->where('created_at', '<', now()->subDays(180))
                ->delete();
        }

        return $purged;
    }

    private function anonymizeUnverifiedAccounts(int $days): int
    {
        $cutoff = now()->subDays($days);
        $count = 0;

        $candidates = User::query()
            ->whereNull('email_verified_at')
            ->whereNull('last_login')
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('invoices')
            ->limit(200)
            ->get();

        foreach ($candidates as $user) {
            if ($this->isProtectedAccount($user)) {
                continue;
            }
            $this->anonymizeAndSoftDeleteUser($user);
            $count++;
        }

        return $count;
    }

    private function anonymizeAbandonedAccounts(int $months): int
    {
        $cutoff = now()->subMonths($months);
        $count = 0;

        $candidates = User::query()
            ->where('created_at', '<', $cutoff)
            ->where('updated_at', '<', $cutoff)
            ->where(function ($q) use ($cutoff): void {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $cutoff);
            })
            ->where(function ($q) use ($cutoff): void {
                $q->whereNull('last_login')->orWhere('last_login', '<', $cutoff);
            })
            ->limit(200)
            ->get();

        foreach ($candidates as $user) {
            if ($this->isProtectedAccount($user)) {
                continue;
            }
            $this->anonymizeAndSoftDeleteUser($user);
            $count++;
        }

        return $count;
    }

    private function isProtectedAccount(User $user): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }
        if ((float) ($user->user_balance ?? 0) > 0) {
            return true;
        }
        if (method_exists($user, 'hasUnpaidInvoices') && $user->hasUnpaidInvoices()) {
            return true;
        }
        if (method_exists($user, 'hasSubscription') && $user->hasSubscription()) {
            return true;
        }

        return false;
    }
}
