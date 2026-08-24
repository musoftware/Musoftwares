<?php

namespace App\Services;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\PartnerClient;
use App\Models\PartnerCreditLease;
use App\Models\PartnerUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerGatewayService
{
    /**
     * Get balance and configuration info for a partner client.
     */
    public function getBalanceInfo(PartnerClient $client): array
    {
        return [
            'success' => true,
            'clientName' => $client->client_name,
            'walletBalance' => (float)$client->wallet_balance,
            'costPerMessage' => (float)$client->cost_per_message,
            'currency' => 'USD',
            'lowBalanceThreshold' => (float)$client->low_balance_threshold,
            'isActive' => (bool)$client->is_active,
            'pricingModel' => $client->pricing_model,
        ];
    }

    /**
     * Atomically reserve credits and issue an active lease to the partner.
     *
     * @throws RuntimeException If wallet balance is insufficient.
     */
    public function acquireLease(PartnerClient $client, int $requestedMessages = 500, int $durationHours = 2): array
    {
        $requestedMessages = max(1, $requestedMessages);
        $costPerMsg = (float)$client->cost_per_message;
        $reserveAmount = round($requestedMessages * $costPerMsg, 4);

        return DB::transaction(function () use ($client, $requestedMessages, $costPerMsg, $reserveAmount, $durationHours) {
            /** @var PartnerClient|null $lockedClient */
            $lockedClient = PartnerClient::where('id', $client->id)->lockForUpdate()->first();

            if (!$lockedClient || (float)$lockedClient->wallet_balance < $reserveAmount) {
                $currentBal = $lockedClient ? (float)$lockedClient->wallet_balance : 0.0;
                throw new RuntimeException("Insufficient partner wallet balance. Available: {$currentBal}, Required: {$reserveAmount}", 402);
            }

            $lockedClient->decrement('wallet_balance', $reserveAmount);
            $lockedClient->refresh();

            $leaseId = 'lease_' . Str::random(24);
            $expiresAt = now()->addHours($durationHours);

            PartnerCreditLease::create([
                'partner_client_id' => $lockedClient->id,
                'lease_id' => $leaseId,
                'granted_messages' => $requestedMessages,
                'settled_messages' => 0,
                'reserved_amount' => $reserveAmount,
                'final_charged_amount' => 0.0000,
                'status' => 'ACTIVE',
                'expires_at' => $expiresAt,
            ]);

            PartnerUsageLog::create([
                'partner_client_id' => $lockedClient->id,
                'lease_id' => $leaseId,
                'type' => 'LEASE_RESERVE',
                'amount' => -$reserveAmount,
                'balance_after' => $lockedClient->wallet_balance,
                'description' => "Reserved {$requestedMessages} messages lease",
                'metadata' => [
                    'granted_messages' => $requestedMessages,
                    'cost_per_message' => $costPerMsg,
                    'reserved_amount' => $reserveAmount,
                ],
            ]);

            return [
                'success' => true,
                'leaseId' => $leaseId,
                'grantedMessages' => $requestedMessages,
                'costPerMessage' => $costPerMsg,
                'totalReservedAmount' => $reserveAmount,
                'remainingWalletBalance' => (float)$lockedClient->wallet_balance,
                'expiresAt' => $expiresAt->toISOString(),
            ];
        });
    }

    /**
     * Atomically settle an existing lease and refund any unspent balance.
     *
     * @throws RuntimeException If lease is not found or already settled.
     */
    public function settleLease(
        PartnerClient $client,
        string $leaseId,
        int $actualSent,
        bool $requestNewLease = false,
        int $newLeaseSize = 500
    ): array {
        $actualSent = max(0, $actualSent);

        $settlementData = DB::transaction(function () use ($client, $leaseId, $actualSent) {
            /** @var PartnerCreditLease|null $lease */
            $lease = PartnerCreditLease::where('lease_id', $leaseId)
                ->where('partner_client_id', $client->id)
                ->lockForUpdate()
                ->first();

            if (!$lease) {
                throw new RuntimeException("Lease [{$leaseId}] not found for this partner client", 404);
            }

            if ($lease->status !== 'ACTIVE') {
                throw new RuntimeException("Lease [{$leaseId}] is already {$lease->status} and cannot be re-settled", 400);
            }

            /** @var PartnerClient $lockedClient */
            $lockedClient = PartnerClient::where('id', $client->id)->lockForUpdate()->firstOrFail();

            $costPerMsg = (float)$lockedClient->cost_per_message;
            $actualCharged = round($actualSent * $costPerMsg, 4);
            $reservedAmount = (float)$lease->reserved_amount;

            // Refund any unused reserve amount back to partner balance
            $refundAmount = max(0.0, round($reservedAmount - $actualCharged, 4));

            if ($refundAmount > 0) {
                $lockedClient->increment('wallet_balance', $refundAmount);
                $lockedClient->refresh();
            }

            $lease->update([
                'settled_messages' => $actualSent,
                'final_charged_amount' => $actualCharged,
                'status' => 'SETTLED',
            ]);

            PartnerUsageLog::create([
                'partner_client_id' => $lockedClient->id,
                'lease_id' => $lease->lease_id,
                'type' => 'LEASE_SETTLE',
                'amount' => -$actualCharged,
                'balance_after' => $lockedClient->wallet_balance,
                'description' => "Settled {$actualSent} msgs (Charged: \${$actualCharged}, Refunded: \${$refundAmount})",
                'metadata' => [
                    'settled_messages' => $actualSent,
                    'final_charged' => $actualCharged,
                    'refunded_amount' => $refundAmount,
                ],
            ]);

            return [
                'settledMessages' => $actualSent,
                'totalCharged' => $actualCharged,
                'refundedUnused' => $refundAmount,
                'newBalance' => (float)$lockedClient->wallet_balance,
                'lockedClient' => $lockedClient,
            ];
        });

        $newLeaseResponse = null;
        if ($requestNewLease) {
            /** @var PartnerClient $lockedClient */
            $lockedClient = $settlementData['lockedClient'];
            $reqCost = round($newLeaseSize * (float)$lockedClient->cost_per_message, 4);
            if ((float)$lockedClient->wallet_balance >= $reqCost) {
                try {
                    $newLeaseResponse = $this->acquireLease($lockedClient, $newLeaseSize);
                } catch (\Throwable $e) {
                    $newLeaseResponse = null;
                }
            }
        }

        return [
            'success' => true,
            'settledMessages' => $settlementData['settledMessages'],
            'totalCharged' => $settlementData['totalCharged'],
            'refundedUnused' => $settlementData['refundedUnused'],
            'newBalance' => $newLeaseResponse ? $newLeaseResponse['remainingWalletBalance'] : $settlementData['newBalance'],
            'newLease' => $newLeaseResponse,
        ];
    }

    /**
     * Top-up partner credit balance directly.
     */
    public function topUpBalance(
        PartnerClient $client,
        float $amount,
        string $type = 'TOP_UP',
        string $description = 'Partner Balance Top-up',
        ?array $metadata = null
    ): float {
        if ($amount <= 0) {
            throw new RuntimeException('Top-up amount must be greater than zero', 422);
        }

        return DB::transaction(function () use ($client, $amount, $type, $description, $metadata) {
            /** @var PartnerClient $lockedClient */
            $lockedClient = PartnerClient::where('id', $client->id)->lockForUpdate()->firstOrFail();

            $lockedClient->increment('wallet_balance', $amount);
            $lockedClient->refresh();

            PartnerUsageLog::create([
                'partner_client_id' => $lockedClient->id,
                'lease_id' => null,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $lockedClient->wallet_balance,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            return (float)$lockedClient->wallet_balance;
        });
    }

    /**
     * Recharge partner balance by deducting from the user's platform account wallet balance.
     */
    public function topUpFromUserWallet(User $user, PartnerClient $client, float $amountUsd): array
    {
        if ($amountUsd <= 0) {
            throw new RuntimeException('Recharge amount must be greater than zero', 422);
        }

        if ($client->user_id && $client->user_id !== $user->id) {
            throw new RuntimeException('Unauthorized partner client ownership', 403);
        }

        $usdCurrency = Currency::where('currency', 'USD')->first();
        $userCurrencyId = $user->currency_id ?? optional($usdCurrency)->id;

        // Calculate cost in User's native wallet currency
        $costInUserCurrency = $amountUsd;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id !== $userCurrencyId) {
            $costInUserCurrency = CurrenciesExchange::RateToday($amountUsd, $usdCurrency->id, $userCurrencyId);
        }

        return DB::transaction(function () use ($user, $client, $amountUsd, $costInUserCurrency, $usdCurrency) {
            /** @var User $lockedUser */
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ((float)$lockedUser->user_balance < (float)$costInUserCurrency) {
                throw new RuntimeException("Insufficient wallet balance. Available: {$lockedUser->user_balance}, Required: {$costInUserCurrency}", 402);
            }

            // Deduct from User's account balance
            $lockedUser->add_balance(
                -$amountUsd,
                "Partner API Recharge for [{$client->client_name}]",
                'used',
                optional($usdCurrency)->id
            );

            // Add to Partner Client Balance
            $newPartnerBalance = $this->topUpBalance(
                $client,
                $amountUsd,
                'TOP_UP',
                "Recharged via User Wallet by {$user->name} (#{$user->id})",
                [
                    'user_id' => $user->id,
                    'debited_user_balance' => $costInUserCurrency,
                    'credited_partner_usd' => $amountUsd,
                ]
            );

            $lockedUser->refresh();

            return [
                'success' => true,
                'rechargedAmount' => $amountUsd,
                'newPartnerBalance' => $newPartnerBalance,
                'remainingUserBalance' => (float)$lockedUser->user_balance,
            ];
        });
    }

    /**
     * Admin manual balance adjustment (Credit or Debit).
     */
    public function adjustBalanceAdmin(PartnerClient $client, float $amount, string $reason, ?int $adminId = null): array
    {
        if ($amount == 0) {
            throw new RuntimeException('Adjustment amount cannot be zero', 422);
        }

        return DB::transaction(function () use ($client, $amount, $reason, $adminId) {
            /** @var PartnerClient $lockedClient */
            $lockedClient = PartnerClient::where('id', $client->id)->lockForUpdate()->firstOrFail();

            if ($amount < 0 && (float)$lockedClient->wallet_balance < abs($amount)) {
                throw new RuntimeException('Cannot deduct more than the current partner balance', 422);
            }

            $lockedClient->increment('wallet_balance', $amount);
            $lockedClient->refresh();

            PartnerUsageLog::create([
                'partner_client_id' => $lockedClient->id,
                'lease_id' => null,
                'type' => 'ADJUSTMENT',
                'amount' => $amount,
                'balance_after' => $lockedClient->wallet_balance,
                'description' => "Admin Adjustment: {$reason}" . ($adminId ? " (Admin #{$adminId})" : ''),
                'metadata' => [
                    'admin_id' => $adminId,
                    'reason' => $reason,
                    'adjustment_amount' => $amount,
                ],
            ]);

            return [
                'success' => true,
                'adjustmentAmount' => $amount,
                'newBalance' => (float)$lockedClient->wallet_balance,
            ];
        });
    }
}
