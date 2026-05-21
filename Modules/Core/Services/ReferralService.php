<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Invoice;
use Modules\Core\Models\SiteSetting;
use Modules\Core\Models\ReferralEarning;
use Modules\Core\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function processInvoicePaid(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $tenantOwner = $invoice->tenant->owner ?? null;
            if (!$tenantOwner) {
                return;
            }

            $referredUserId = $tenantOwner->id;

            $level1ReferrerId = $tenantOwner->referrer_id;
            if (!$level1ReferrerId) {
                return;
            }

            $level1Referrer = User::find($level1ReferrerId);
            if (!$level1Referrer) {
                return;
            }

            $level2ReferrerId = $level1Referrer->referrer_id ?? null;

            // Fetch commission rates from SiteSetting
            $rate1 = (float) (SiteSetting::where('key', 'referral_level_1_rate')->value('value') ?? 0);
            $rate2 = (float) (SiteSetting::where('key', 'referral_level_2_rate')->value('value') ?? 0);

            $invoiceTotal = (float) $invoice->total;
            $currency = $invoice->currency_code;

            if ($rate1 > 0 && $level1ReferrerId) {
                $commission1 = $invoiceTotal * ($rate1 / 100);

                ReferralEarning::create([
                    'referrer_id' => $level1ReferrerId,
                    'referred_user_id' => $referredUserId,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'level' => 1,
                    'amount' => $commission1,
                    'currency' => $currency,
                ]);

                // Credit Wallet
                $wallet1 = Wallet::firstOrCreate([
                    'owner_id' => $level1ReferrerId,
                    'owner_type' => User::class,
                    'context' => 'default'
                ], ['balance' => 0, 'currency' => $currency]); // assuming default currency if creating

                $this->walletService->credit(
                    $wallet1,
                    $commission1,
                    $currency,
                    'referral_commission',
                    (string)$invoice->id,
                    "Level 1 referral commission for invoice {$invoice->number}"
                );
            }

            if ($rate2 > 0 && $level2ReferrerId) {
                $commission2 = $invoiceTotal * ($rate2 / 100);

                ReferralEarning::create([
                    'referrer_id' => $level2ReferrerId,
                    'referred_user_id' => $referredUserId,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'level' => 2,
                    'amount' => $commission2,
                    'currency' => $currency,
                ]);

                // Credit Wallet
                $wallet2 = Wallet::firstOrCreate([
                    'owner_id' => $level2ReferrerId,
                    'owner_type' => User::class,
                    'context' => 'default'
                ], ['balance' => 0, 'currency' => $currency]);

                $this->walletService->credit(
                    $wallet2,
                    $commission2,
                    $currency,
                    'referral_commission',
                    (string)$invoice->id,
                    "Level 2 referral commission for invoice {$invoice->number}"
                );
            }
        });
    }
}
