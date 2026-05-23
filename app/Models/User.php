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
        'preferred_currency',
        'preferred_currency_locked_at',
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
    ];

    protected $appends = [
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
            'preferred_currency_locked_at' => 'datetime',
            'tour_completed' => 'boolean',
            'tour_skipped' => 'boolean',
            'current_tour_step' => 'integer',
            'kyc_verified' => 'boolean',
            'kyc_verified_at' => 'datetime',
        ];
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(\App\Models\SupportTicket::class, 'client_id');
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

    public function client()
    {
        // Uses user_id FK (added in fix migration 2026_05_20_000001).
        // Falls back to email match for legacy records that pre-date the FK.
        return $this->hasOne(\Modules\ERP\Models\TenantClient::class, 'user_id');
    }

    /**
     * Resolve the ERP client profile for this user.
     * Legacy method - retained for backward compatibility with older controllers.
     * For platform billing, we now use the User model directly via IsPlatformClient trait.
     */
    public function resolveClient()
    {
        return \Modules\ERP\Models\TenantClient::where('user_id', $this->id)->first();
    }



    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function locked_balance()
    {
        $locked = 0;

        // 1. Pending withdrawals
        if (class_exists(\App\Models\UserWithdrawal::class)) {
            $locked += $this->withdrawals()->where('status', 'pending')->sum('amount');
        }

        // 2. Active Freelance Contracts
        if (class_exists(\Modules\Freelance\Models\Contract::class)) {
            $contracts = \Modules\Freelance\Models\Contract::where('freelancer_id', $this->id)
                ->where('status', 'active')
                ->get();
            foreach ($contracts as $contract) {
                $locked += \App\Models\CurrenciesExchange::RateToday($contract->amount, $contract->currency_code ?? 'USD', $this->preferred_currency ?? 'USD');
            }
        }

        // 3. Pending invoices
        $unpaidInvoices = $this->invoices()
            ->where('unpaid', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get();
            
        foreach ($unpaidInvoices as $invoice) {
            $schedule = $invoice->getSchedule();
            $invoiceTotal = \App\Models\CurrenciesExchange::RateToday($invoice->total(), $invoice->currency, $this->preferred_currency ?? 'USD');
            $invoicePaid = \App\Models\CurrenciesExchange::RateToday($invoice->paid, $invoice->currency, $this->preferred_currency ?? 'USD');
            $invoiceUnpaid = \App\Models\CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, $this->preferred_currency ?? 'USD');

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
            $invoiceTotal = \App\Models\CurrenciesExchange::RateToday($invoice->total(), $invoice->currency, $this->preferred_currency ?? 'USD');
            $invoicePaid = \App\Models\CurrenciesExchange::RateToday($invoice->paid, $invoice->currency, $this->preferred_currency ?? 'USD');
            $invoiceUnpaid = \App\Models\CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, $this->preferred_currency ?? 'USD');

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

        if ($currency && $currency != ($this->preferred_currency ?? 'USD')) {
            return \App\Models\CurrenciesExchange::RateToday($available, $this->preferred_currency ?? 'USD', $currency);
        }

        return $available;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
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
        return \App\Models\CurrenciesExchange::RateToday($this->user_balance, $this->currency ?? $this->preferred_currency, $currency);
    }

    public function add_balance($amount, $reason, $type, $currency = null, $project = null)
    {
        if ($amount == 0) {
            return null;
        }
        if ($currency != null) {
            $amount = \App\Models\CurrenciesExchange::RateToday($amount, $currency, $this->currency ?? $this->preferred_currency);
        }
        $currency = $this->currency ?? $this->preferred_currency ?? 'USD';

        $client_balance = new Transaction();
        $client_balance->project_id = optional($project)->id;
        $client_balance->user_id = $this->id;
        $client_balance->amount = $amount;
        $client_balance->type = $type;
        if (!empty($reason)) {
            $client_balance->reason = $reason;
        }
        $client_balance->currency = $currency;

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

    /**
     * Serial license assignments for this user.
     * Used by SerialUserDeviceController::updateUserStatus() (bulk status change).
     * temp_valid_until is a field on users table for temporary license override.
     */
    public function serialUserDevices(): HasMany
    {
        return $this->hasMany(SerialUserDevice::class, 'user_id');
    }
}

