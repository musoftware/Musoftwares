<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Traits\IsPlatformClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, IsPlatformClient, Notifiable, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'onboarding_completed',
        'country',
        'city',
        'mobile_1',
        'mobile_2',
        'telegram_username',
        'whatsapp_number',
        'enable_notifications',
        'tour_completed',
        'tour_skipped',
        'current_tour_step',
        'kyc_verified',
        'kyc_verified_at',
        'kyc_verified_by',
        'kyc_provider',
        'kyc_reference_id',
        'kyc_notes',
        'workspace_settings',
        'max_devices',
        'temp_valid_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'autosms_verification_secret',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }
        if (empty($this->email)) {
            return null;
        }
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?s=200";
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
            'tour_completed' => 'boolean',
            'tour_skipped' => 'boolean',
            'current_tour_step' => 'integer',
            'kyc_verified' => 'boolean',
            'kyc_verified_at' => 'datetime',
            'workspace_settings' => 'array',
            'max_devices' => 'integer',
            'enable_notifications' => 'boolean',
        ];
    }

    public function getCurrencyAttribute()
    {
        return $this->attributes['currency_id'] ?? null;
    }

    public function setCurrencyAttribute($value)
    {
        $this->attributes['currency_id'] = $value;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(UserEmail::class, 'user_id');
    }

    /**
     * Resolve a login identifier (email) to a User account.
     * Looks up `users.email` first, then falls back to `user_emails.email`.
     * Email comparison is case-insensitive. Returns null when no match.
     */
    public static function findForLogin(string $identifier): ?self
    {
        $needle = strtolower(trim($identifier));
        if ($needle === '') {
            return null;
        }

        $user = static::query()
            ->whereRaw('LOWER(email) = ?', [$needle])
            ->first();
        if ($user) {
            return $user;
        }

        $alias = DB::table('user_emails')
            ->whereRaw('LOWER(email) = ?', [$needle])
            ->first();

        if (! $alias) {
            return null;
        }

        return static::query()->find($alias->user_id);
    }

    /**
     * Whether this email belongs to the user (primary OR an alias).
     */
    public function ownsEmail(string $identifier): bool
    {
        $needle = strtolower(trim($identifier));
        if ($needle === '') {
            return false;
        }

        if (strtolower((string) $this->email) === $needle) {
            return true;
        }

        return $this->emails()
            ->whereRaw('LOWER(email) = ?', [$needle])
            ->exists();
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function hasUnpaidInvoices(): bool
    {
        return $this->invoices()
            ->where('unpaid', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->exists();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    public function locked_balance()
    {
        $locked = 0;

        // 1. Pending withdrawals
        if (class_exists(UserReferralRequestWithdraw::class)) {
            $locked += $this->withdraw()->where('status', 'pending')->sum('amount');
        }

        // 3. Pending invoices
        $unpaidInvoices = $this->invoices()
            ->where('unpaid', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get();

        foreach ($unpaidInvoices as $invoice) {
            $schedule = $invoice->getSchedule();
            $invoiceTotal = CurrenciesExchange::RateToday($invoice->total(), $invoice->currency, $this->currency_id);
            $invoicePaid = CurrenciesExchange::RateToday($invoice->paid, $invoice->currency, $this->currency_id);
            $invoiceUnpaid = CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, $this->currency_id);

            if ($schedule) {
                $startDate = Carbon::parse($schedule['start_date'] ?? $invoice->created_at);
                if (now()->gte($startDate)) {
                    $splits = (int) ($schedule['months'] ?? 1);
                    if ($splits < 1) {
                        $splits = 1;
                    }
                    $monthsSinceStart = now()->diffInMonths($startDate);
                    $paymentsDue = min($splits, $monthsSinceStart + 1);
                    $totalDueByNow = ($invoiceTotal / $splits) * $paymentsDue;

                    if ($invoicePaid < $totalDueByNow) {
                        $deductionForInvoice = $totalDueByNow - $invoicePaid;
                        $locked += min($deductionForInvoice, $invoiceUnpaid);
                    }
                }
            } else {
                $locked += $invoiceUnpaid;
            }
        }

        return $locked;
    }

    public function available_balance($currency = null)
    {
        $currentBalance = $this->balance(); // Current Wallet Balance in User Currency

        $unpaidInvoices = $this->invoices()
            ->where('unpaid', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get();

        $totalDeduction = 0;

        foreach ($unpaidInvoices as $invoice) {
            $schedule = $invoice->getSchedule();
            $deductionForInvoice = 0;

            // Convert everything to User Currency for calculation
            $invoiceTotal = CurrenciesExchange::RateToday($invoice->total(), $invoice->currency, $this->currency_id);
            $invoicePaid = CurrenciesExchange::RateToday($invoice->paid, $invoice->currency, $this->currency_id);
            $invoiceUnpaid = CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, $this->currency_id);

            if ($schedule) {
                // Parse schedule
                $startDate = Carbon::parse($schedule['start_date'] ?? $invoice->created_at);
                $splits = (int) ($schedule['months'] ?? 1); // 1 or 12
                if ($splits < 1) {
                    $splits = 1;
                }

                if (now()->lt($startDate)) {
                    // Future schedule: No deduction yet
                    $deductionForInvoice = 0;
                } else {
                    // Start date passed
                    $monthsPassed = $startDate->diffInMonths(now()) + 1;

                    if ($splits > 1) {
                        // Split logic
                        $monthlyAmount = $invoiceTotal / $splits;
                        $totalExpected = $monthlyAmount * min($splits, $monthsPassed);

                        // Due is what we EXPECT to have paid minus what we actually paid
                        $due = max(0, $totalExpected - $invoicePaid);

                        // Cannot invoke more than what is strictly unpaid on the invoice
                        $deductionForInvoice = min($invoiceUnpaid, $due);
                    } else {
                        // Single payment, due now
                        $deductionForInvoice = $invoiceUnpaid;
                    }
                }
            } else {
                // No schedule, strictly due immediately
                $deductionForInvoice = $invoiceUnpaid;
            }

            $totalDeduction += $deductionForInvoice;
        }

        $available = round($currentBalance - $totalDeduction, 2);

        if ($currency && $currency != $this->currency_id) {
            return CurrenciesExchange::RateToday($available, $this->currency_id, $currency);
        }

        return $available;
    }

    public function unpaid_invoices_amount($include_pending = false)
    {
        $invoices = $this->invoices()
            ->whereIn('status', ['unpaid', 'partially_paid']);

        if (! $include_pending) {
            $invoices->whereIn('job_status', ['processing', 'done']);
        }

        $invoices = $invoices->get();
        $unpaid = 0;
        foreach ($invoices as $invoice) {
            $unpaid += CurrenciesExchange::RateToday($invoice->unpaid_total(), $invoice->currency, $this->currency_id);
        }

        return $unpaid;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    public function costTransactions()
    {
        return $this->hasMany(CostTransaction::class, 'user_id');
    }

    public function withdraw()
    {
        return $this->hasMany(UserReferralRequestWithdraw::class);
    }

    public function my_ref_users()
    {
        return $this->hasMany(User::class, 'ref_user_id');
    }

    public function referrals()
    {
        return $this->hasMany(UserReferral::class, 'user_id');
    }

    public function invoice_item_timers()
    {
        return $this->hasMany(InvoiceItemTimer::class);
    }

    public function timer_report()
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $secondsSql = 'SUM(strftime(\'%s\', date_end) - strftime(\'%s\', date_start))';
        } else {
            $secondsSql = 'SUM(TIMESTAMPDIFF(SECOND, date_start, date_end))';
        }

        return $this->invoice_item_timers()
            ->select(DB::raw("DATE(date_start) as ds, min(date_end) as min_date, max(date_end) as max_date, sum(amount) as sum_amount, {$secondsSql} as sum_seconds"))
            ->groupBy(DB::raw('ds'));
    }

    public function currency_name()
    {
        $currency = Currency::query()->find($this->currency_id);

        return $currency ? $currency->currency : '--';
    }

    public function client_balance()
    {
        return $this->hasMany(Transaction::class);
    }

    public function try_pay_unpaid_invoices()
    {
        foreach (
            $this->invoices()
                ->where('unpaid', '>', '0')
                ->where('unpaid', '<=', $this->user_balance)
                ->orderBy('id')
                ->get() as $invoice
        ) {
            $client_balance = $this->user_balance;
            $invoice_total = $invoice->total();
            if ((float) $client_balance >= (float) $invoice_total) {
                $invoice->bill_invoice();
            }
        }
    }

    public function balance($currency = null)
    {
        return CurrenciesExchange::RateToday($this->user_balance, $this->currency_id, $currency);
    }

    public function add_balance($amount, $reason, $type, $currencyId = null, ?Project $project = null, $createdAt = null)
    {
        if ($amount == 0) {
            return null;
        }

        if ($currencyId !== null) {
            $amount = CurrenciesExchange::RateToday($amount, (int) $currencyId, $this->currency_id);
        }
        $currency = $this->currency_id;

        $client_balance = new Transaction;
        $client_balance->project_id = optional($project)->id;
        $client_balance->user_id = $this->id;
        $client_balance->amount = $amount;
        $client_balance->type = $type;
        if (! empty($reason)) {
            $client_balance->reason = $reason;
        }
        $client_balance->currency_id = $currency;
        if ($createdAt) {
            try {
                $client_balance->created_at = Carbon::parse($createdAt);
                $client_balance->updated_at = Carbon::parse($createdAt);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to parse createdAt date in User::add_balance: " . $e->getMessage(), [
                    'createdAt' => $createdAt,
                    'user_id' => $this->id
                ]);
            }
        }

        DB::transaction(function () use ($client_balance, $project, $amount, $type) {
            $client_balance->save();

            if (in_array($type, ['received', 'sent', 'refunded'])) {
                $this->increment('total_paid', $amount);
                optional($project)->increment('total_paid', $amount);
            }

            $this->increment('user_balance', $amount);
            optional($project)->increment('project_balance', $amount);

            if ($type != 'used') {
                // ActionHelper::add_action_coins(...) // Omitted for now unless requested
            }
        });

        return $client_balance->id;
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class, 'user_id');
    }

    public function kycVerifier()
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    public function payoutMethods(): HasMany
    {
        return $this->hasMany(PayoutMethod::class, 'user_id');
    }

    /**
     * Serial license assignments for this user.
     * Used by SerialUserDeviceController::updateUserStatus() (bulk status change).
     * temp_valid_until is a field on users table for temporary license override.
     */
    public function serialUserDevices(): HasMany
    {
        return $this->hasMany(SerialUserDevice::class, 'user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'user_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(UserLoan::class, 'user_id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class, 'user_id')->where('status', 'active')->latest();
    }

    public function hasSubscription(): bool
    {
        // Check new module-based subscriptions first
        if ($this->subscriptions()->where('status', 'active')->where('expires_at', '>', now())->exists()) {
            return true;
        }

        // Legacy fallback
        if ($this->plan_id && $this->subscription_date) {
            return Carbon::parse($this->subscription_date)->isFuture();
        }

        return false;
    }

    /**
     * Check if user has an active subscription to a specific module (e.g. 'erp', 'crm', 'erp-backup').
     */
    public function hasModuleSubscription(string $module): bool
    {
        return $this->subscriptions()
            ->where('object', $module)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Get or generate AutoSMS verification secret for HMAC signing
     */
    public function getAutoSmsVerificationSecret(): string
    {
        if (! $this->autosms_verification_secret) {
            $this->autosms_verification_secret = bin2hex(random_bytes(32)); // 64 character hex string
            $this->save();
        }

        return $this->autosms_verification_secret;
    }

    /**
     * Regenerate AutoSMS verification secret
     *
     * @return string The new secret
     */
    public function regenerateAutoSmsVerificationSecret(): string
    {
        $this->autosms_verification_secret = bin2hex(random_bytes(32));
        $this->save();

        return $this->autosms_verification_secret;
    }

    /**
     * Get the affiliate commission percentage for this user.
     *
     * IMPORTANT: This method returns the percentage as a MULTIPLIER
     * (e.g. 1.01 = 1%, 1.10 = 10%). It is NOT a percent value. If you want
     * the percent value for display, use `(getAffiliateCommissionPercentage() - 1) * 100`.
     *
     * The underlying column `affiliate_commission_percentage` is stored as a
     * percent number where 1.00 means 1%. Do not multiply amounts by
     * $this->affiliate_commission_percentage directly — that yields 100x the
     * intended commission.
     */
    public function getAffiliateCommissionPercentage()
    {
        $pct = $this->affiliateCommissionPercent();

        return $pct / 100.0 + 1;
    }

    /**
     * Raw percent value (e.g. 1.0 = 1%, 10.0 = 10%). Use this for display.
     */
    public function affiliateCommissionPercent(): float
    {
        return (float) ($this->affiliate_commission_percentage ?? 1);
    }

    /**
     * Check if commission should be added to invoice total
     */
    public function shouldAddCommissionToTotal()
    {
        return $this->add_commission_to_total ?? false;
    }

    /**
     * Calculate commission amount based on the base amount.
     *
     * When a referred user is supplied AND has made their first payment within
     * the boosted window (default: 1 month), a higher commission percent is
     * applied (default: 10%). See
     * 2026_03_02_000000_add_first_referral_payment_at_to_users_table migration.
     */
    public function calculateCommissionAmount($baseAmount, $currencyId = null, $referredUser = null)
    {
        $percent = $this->affiliateCommissionPercent();

        if ($referredUser instanceof User && ! empty($referredUser->first_referral_payment_at)) {
            $boostPercent = (float) config('referrals.boost_percent', 10);
            $boostDays = (int) config('referrals.boost_days', 30);
            $windowEnd = $referredUser->first_referral_payment_at->copy()->addDays($boostDays);
            if (now()->lessThan($windowEnd)) {
                $percent = max($percent, $boostPercent);
            }
        }

        $commissionMultiplier = $percent / 100.0 + 1;
        $commissionAmount = $baseAmount * ($commissionMultiplier - 1);

        if ($currencyId && $currencyId != $this->currency_id) {
            $commissionAmount = CurrenciesExchange::RateToday($commissionAmount, $this->currency_id, $currencyId);
        }

        return round($commissionAmount, 2);
    }

    /**
     * Legacy referral calculation method.
     * Preserved to prevent undefined method errors in Invoice.php.
     */
    public function calc_ref($amount, $invoice_id, $currency_id)
    {
        // Modern referral logic is handled elsewhere (e.g. at invoice generation)
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @param  Notification  $notification
     * @return string|array|null
     */
    public function routeNotificationForFcm($notification)
    {
        $tokens = $this->deviceTokens()->pluck('token')->toArray();

        // Fallback to the old fcm_token column if no tokens found in the new table
        if (empty($tokens) && $this->fcm_token) {
            return $this->fcm_token;
        }

        return $tokens;
    }

    /**
     * Route notifications for the SMS channel.
     *
     * @param  Notification  $notification
     * @return string|null
     */
    public function routeNotificationForSms($notification)
    {
        return $this->mobile_1 ?: $this->mobile_2 ?: null;
    }

    /**
     * Route notifications for the WhatsApp channel.
     *
     * @param  Notification  $notification
     * @return string|null
     */
    public function routeNotificationForWhatsapp($notification)
    {
        return $this->whatsapp_number ?: null;
    }

    /**
     * Check if the user is an admin.
     * Consolidates various role casings used across the application to ensure DRY.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin', 'superadmin', 'Admin']);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Override the notify method to intercept client notifications.
     */
    public function notify($instance)
    {
        if (! ($this->enable_notifications ?? true) && ! ($instance instanceof ResetPasswordNotification)) {
            return;
        }

        app(\Illuminate\Contracts\Notifications\Dispatcher::class)->send($this, $instance);
    }
}
