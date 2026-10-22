<?php

namespace App\Services;

use App\Mail\ClientSoftwareWinbackMail;
use App\Models\AdminSettings;
use App\Models\ClientSoftwareWinbackLog;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ClientSoftwareWinbackService extends BaseService
{
    public const INITIAL_INACTIVITY_DAYS = 180;
    public const CADENCE_DAYS = 90;
    public const MAX_REMINDERS = 4;
    public const COUPON_CODE = 'WELCOMEBACK50';
    public const WHATSAPP_PHONE = '201015218548';

    /**
     * Process all eligible inactive clients and send or queue their winback emails.
     *
     * @return array{processed: int, sent: int, skipped: int, errors: int}
     */
    public function processAllEligible(bool $dryRun = false): array
    {
        $stats = [
            'processed' => 0,
            'sent'      => 0,
            'skipped'   => 0,
            'errors'    => 0,
        ];

        $coupon = $this->ensureWinbackCoupon();

        $candidates = $this->getCandidateUsers();

        foreach ($candidates as $user) {
            $stats['processed']++;

            $reminderNumber = $this->determineNextReminderNumber($user);
            if ($reminderNumber === null) {
                $stats['skipped']++;
                continue;
            }

            try {
                $dispatched = $this->sendReminder($user, $reminderNumber, $coupon->code, $dryRun);
                if ($dispatched) {
                    $stats['sent']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('Error processing software winback for user ' . $user->id, [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Send or preview a specific reminder for a user.
     */
    public function sendReminder(User $user, int $reminderNumber, string $couponCode = self::COUPON_CODE, bool $dryRun = false): bool
    {
        if ($this->shouldSuppress($user)) {
            return false;
        }

        if (ClientSoftwareWinbackLog::alreadySent($user->id, $reminderNumber)) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        return $this->executeInTransaction(function () use ($user, $reminderNumber, $couponCode) {
            ClientSoftwareWinbackLog::create([
                'user_id'         => $user->id,
                'reminder_number' => $reminderNumber,
                'status'          => 'sent',
                'coupon_code'     => $couponCode,
                'sent_at'         => now(),
                'metadata'        => [
                    'last_activity_at' => $user->last_activity_at?->toIso8601String(),
                ],
            ]);

            $whatsappUrl = $this->generateWhatsAppUrl($couponCode);
            $projectUrl = $this->generateProjectUrl($couponCode);
            $unsubscribeUrl = $this->generateUnsubscribeUrl($user);

            ClientSoftwareWinbackMail::sendToUser(
                $user,
                $reminderNumber,
                $couponCode,
                $whatsappUrl,
                $projectUrl,
                $unsubscribeUrl
            );

            return true;
        }, 'Failed sending software winback reminder');
    }

    /**
     * Determine the next reminder number (1 to 4) for a user, or null if not eligible.
     */
    public function determineNextReminderNumber(User $user): ?int
    {
        if ($this->shouldSuppress($user)) {
            return null;
        }

        if (! $this->hasPastEngagement($user)) {
            return null;
        }

        if ($this->hasRecentActivity($user)) {
            return null;
        }

        $logs = ClientSoftwareWinbackLog::where('user_id', $user->id)
            ->whereIn('status', ['sent', 'opened', 'clicked'])
            ->orderBy('reminder_number')
            ->get();

        $count = $logs->count();

        if ($count >= self::MAX_REMINDERS) {
            return null;
        }

        if ($count === 0) {
            return 1;
        }

        $lastLog = $logs->last();
        if (! $lastLog || ! $lastLog->sent_at) {
            return null;
        }

        $daysSinceLastLog = (int) $lastLog->sent_at->diffInDays(now());
        if ($daysSinceLastLog >= self::CADENCE_DAYS) {
            return $count + 1;
        }

        return null;
    }

    /**
     * Check if the user should be suppressed from receiving winback emails.
     */
    public function shouldSuppress(User $user): bool
    {
        if (empty($user->email)) {
            return true;
        }

        if ($user->winback_unsubscribed_at !== null) {
            return true;
        }

        // Check for open platform support tickets
        $hasOpenTicket = $user->tickets()
            ->whereNotIn('ticket_status', ['closed'])
            ->exists();

        if ($hasOpenTicket) {
            return true;
        }

        return false;
    }

    /**
     * Verify that the user was an active paying client or project client in the past.
     */
    public function hasPastEngagement(User $user): bool
    {
        $hasPaidInvoice = $user->invoices()
            ->where('status', 'paid')
            ->exists();

        if ($hasPaidInvoice) {
            return true;
        }

        return $user->projects()->exists();
    }

    /**
     * Verify if the user had any activity in the last 180 days.
     */
    public function hasRecentActivity(User $user): bool
    {
        $cutoff = now()->subDays(self::INITIAL_INACTIVITY_DAYS);

        if ($user->last_activity_at && $user->last_activity_at > $cutoff) {
            return true;
        }

        $recentPaidInvoice = $user->invoices()
            ->where('status', 'paid')
            ->where('updated_at', '>', $cutoff)
            ->exists();

        if ($recentPaidInvoice) {
            return true;
        }

        $recentProject = $user->projects()
            ->where('updated_at', '>', $cutoff)
            ->exists();

        return $recentProject;
    }

    /**
     * Fetch all potential candidates for software winback.
     *
     * @return Collection<int, User>
     */
    public function getCandidateUsers(): Collection
    {
        return User::whereNotNull('email')
            ->whereNull('winback_unsubscribed_at')
            ->where(function ($query) {
                $query->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<=', now()->subDays(self::INITIAL_INACTIVITY_DAYS));
            })
            ->get();
    }

    /**
     * Ensure the 50% discount coupon exists in the system.
     */
    public function ensureWinbackCoupon(): Coupon
    {
        return Coupon::firstOrCreate(
            ['code' => self::COUPON_CODE],
            [
                'name'                => 'Winback 50% Off Custom Software',
                'description'         => 'خصم 50% للعملاء السابقين على أي مشروع أو تطوير برمجي جديد',
                'type'                => 'percentage',
                'discount_percentage' => 50.00,
                'is_active'           => true,
                'currency_id'         => AdminSettings::business_currency(),
            ]
        );
    }

    /**
     * Generate WhatsApp chat URL with pre-filled message.
     */
    public function generateWhatsAppUrl(string $couponCode): string
    {
        $message = "أهلاً م/ محمود، أنا عميل سابق في Musoftwares وحابب استفيد من كود خصم الـ 50% ({$couponCode}) على مشروع برمجي جديد.";

        return 'https://wa.me/' . self::WHATSAPP_PHONE . '?text=' . rawurlencode($message);
    }

    /**
     * Generate project start URL with pre-filled coupon code.
     */
    public function generateProjectUrl(string $couponCode): string
    {
        return url('/start-project?coupon=' . $couponCode);
    }

    /**
     * Generate signed unsubscribe URL valid for 90 days.
     */
    public function generateUnsubscribeUrl(User $user): string
    {
        return URL::signedRoute('winback.unsubscribe', ['user' => $user->id], now()->addDays(90));
    }
}
