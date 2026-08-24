<?php

namespace App\Services;

use App\Models\PartnerClient;
use App\Models\PartnerCreditLease;
use App\Models\PartnerUsageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
}
