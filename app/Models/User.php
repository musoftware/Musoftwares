<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\IsPlatformClient;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Searchable, IsPlatformClient;

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
        'tour_completed',
        'tour_skipped',
        'current_tour_step',
        'kyc_verified',
        'kyc_verified_at',
        'kyc_verified_by',
        'kyc_provider',
        'kyc_reference_id',
        'kyc_notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'autosms_verification_secret',
    ];

    protected $appends = [
    ];

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

    public function getPreferredCurrencyAttribute(): ?string
    {
        $name = $this->currency_name();
        return $name === '--' ? null : $name;
    }

    public function setPreferredCurrencyAttribute($value)
    {
        $currency = \App\Models\Currency::where('currency', $value)->first();
        if ($currency) {
            $this->attributes['currency_id'] = $currency->id;
        }
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(\App\Models\Ticket::class, 'user_id');
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(\App\Models\ConversationParticipant::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(\App\Models\Message::class, 'sender_id');
    }

    public function freelanceSkills()
    {
        return $this->belongsToMany(\Modules\Freelance\Models\Skill::class, 'freelance_user_skills')
            ->withTimestamps();
    }


    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function locked_balance()
    {
        $locked = 0;

        // 1. Pending withdrawals
        if (class_exists(\App\Models\UserReferralRequestWithdraw::class)) {
            $locked += $this->withdraw()->where('status', 'pending')->sum('amount');
        }

        // 2. Active Freelance Contracts
        if (class_exists(\Modules\Freelance\Models\Contract::class)) {
            $contracts = \Modules\Freelance\Models\Contract::where('freelancer_id', $this->id)
                ->where('status', 'active')
                ->get();
            foreach ($contracts as $contract) {
                $locked += \App\Models\CurrenciesExchange::RateToday($contract->amount, $contract->currency_code, $this->currency_id);
            }
        }

        // 3. Pending invoices
        $unpaidInvoices = $this->invoices()
            ->where('unpaid', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get();
            
        foreach ($unpaidInvoices as $invoice) {
            $schedule = $invoice->getSchedule();
            $invoiceTotal = \App\Models\CurrenciesExchange::RateToday($invoice->total(), $invoice->currency, $this->currency_id);
            $invoicePaid = \App\Models\CurrenciesExchange::RateToday($invoice->paid, $invoice->currency, $this->currency_id);
            $invoiceUnpaid = \App\Models\CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, $this->currency_id);

            if ($schedule) {
                $startDate = \Carbon\Carbon::parse($schedule['start_date'] ?? $invoice->created_at);
                if (now()->gte($startDate)) {
                    $splits = (int)($schedule['months'] ?? 1);
                    if ($splits < 1) $splits = 1;
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
            $invoiceTotal = \App\Models\CurrenciesExchange::RateToday($invoice->total(), $invoice->currency, $this->currency_id);
            $invoicePaid = \App\Models\CurrenciesExchange::RateToday($invoice->paid, $invoice->currency, $this->currency_id);
            $invoiceUnpaid = \App\Models\CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, $this->currency_id);

            if ($schedule) {
                // Parse schedule
                $startDate = \Carbon\Carbon::parse($schedule['start_date'] ?? $invoice->created_at);
                $splits = (int)($schedule['months'] ?? 1); // 1 or 12
                if ($splits < 1) $splits = 1;

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
            return \App\Models\CurrenciesExchange::RateToday($available, $this->currency_id, $currency);
        }

        return $available;
    }

    public function unpaid_invoices_amount($include_pending = false)
    {
        $invoices = $this->invoices()
            ->whereIn('status', ['unpaid', 'partially_paid']);

        if (!$include_pending) {
            $invoices->whereIn('job_status', ['processing', 'done']);
        }

        $invoices = $invoices->get();
        $unpaid = 0;
        foreach ($invoices as $invoice) {
            $unpaid += \App\Models\CurrenciesExchange::RateToday($invoice->unpaid_total(), $invoice->currency, $this->currency_id);
        }
        return $unpaid;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function withdraw()
    {
        return $this->hasMany(\App\Models\UserReferralRequestWithdraw::class);
    }

    public function my_ref_users()
    {
        return $this->hasMany(User::class, 'ref_user_id');
    }

    public function referrals()
    {
        return $this->hasMany(\App\Models\UserReferral::class, 'user_id');
    }

    public function invoice_item_timers()
    {
        return $this->hasMany(\App\Models\InvoiceItemTimer::class);
    }

    public function timer_report()
    {
        return $this->invoice_item_timers()
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(date_start) as ds, min(date_end) as min_date, max(date_end) as max_date, sum(amount) as sum_amount, SUM(TIMESTAMPDIFF(SECOND, date_start, date_end)) as sum_seconds'))
            ->groupBy(\Illuminate\Support\Facades\DB::raw('ds'));
    }

    public function currency_name()
    {
        $currency = \App\Models\Currency::query()->find($this->currency_id);
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
                ->get()
            as $invoice
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
        return \App\Models\CurrenciesExchange::RateToday($this->user_balance, $this->currency_id, $currency);
    }

    public function add_balance($amount, $reason, $type, $currency = null, $project = null)
    {
        if ($amount == 0) {
            return null;
        }
        if ($currency != null) {
            $amount = \App\Models\CurrenciesExchange::RateToday($amount, $currency, $this->currency_id);
        }
        $currency = $this->currency_id;

        $client_balance = new Transaction();
        $client_balance->project_id = optional($project)->id;
        $client_balance->user_id = $this->id;
        $client_balance->amount = $amount;
        $client_balance->type = $type;
        if (!empty($reason)) {
            $client_balance->reason = $reason;
        }
        $client_balance->currency_id = $currency;

        \Illuminate\Support\Facades\DB::transaction(function () use ($client_balance, $project, $amount, $type, $currency) {
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
        return $this->hasMany(\App\Models\PayoutMethod::class, 'user_id');
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
        return $this->hasMany(\App\Models\UserSubscription::class, 'client_id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(\App\Models\UserSubscription::class, 'client_id')->where('status', 'active')->latest();
    }

    public function hasSubscription(): bool
    {
        if ($this->plan_id && $this->subscription_date) {
            return \Carbon\Carbon::parse($this->subscription_date)->isFuture();
        }
        return $this->subscriptions()->where('status', 'active')->exists();
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class, 'plan_id');
    }

    /**
     * Get or generate AutoSMS verification secret for HMAC signing
     * @return string
     */
    public function getAutoSmsVerificationSecret(): string
    {
        if (!$this->autosms_verification_secret) {
            $this->autosms_verification_secret = bin2hex(random_bytes(32)); // 64 character hex string
            $this->save();
        }

        return $this->autosms_verification_secret;
    }

    /**
     * Regenerate AutoSMS verification secret
     * @return string The new secret
     */
    public function regenerateAutoSmsVerificationSecret(): string
    {
        $this->autosms_verification_secret = bin2hex(random_bytes(32));
        $this->save();

        return $this->autosms_verification_secret;
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|array|null
     */
    public function routeNotificationForFcm($notification)
    {
        return $this->fcm_token;
    }
}

