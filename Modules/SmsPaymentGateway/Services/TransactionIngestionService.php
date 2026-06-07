<?php

namespace Modules\SmsPaymentGateway\Services;

use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayOrderLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TransactionIngestionService
{
    protected DeterministicSmsParser $parser;
    protected RealtimePaymentMatchingEngine $matchingEngine;

    public function __construct(DeterministicSmsParser $parser, RealtimePaymentMatchingEngine $matchingEngine)
    {
        $this->parser = $parser;
        $this->matchingEngine = $matchingEngine;
    }

    /**
     * Ingest an incoming SMS message
     */
    public function ingestSms(SmsPaymentGatewayDevice $device, array $smsData, string $senderName): array
    {
        $message = $smsData['message'] ?? '';
        $isTest = $smsData['is_test'] ?? false;

        // Check for duplicate by message_id if provided
        if (!empty($smsData['message_id'])) {
            $existingTransaction = SmsPaymentGatewayTransaction::where('message_id', $smsData['message_id'])
                ->where('device_id', $device->id)
                ->first();

            if ($existingTransaction) {
                Log::info('AutoSMS Payment Hub - Duplicate SMS detected by message_id', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'message_id' => $smsData['message_id'],
                    'existing_transaction_id' => $existingTransaction->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS already processed (duplicate)',
                    'transaction_detected' => true,
                    'duplicate' => true,
                    'existing_transaction_id' => $existingTransaction->id,
                ];
            }
        }

        // Check for duplicate by exact SMS message content within 5 minutes
        if (!$isTest) {
            $existingMessageTransaction = SmsPaymentGatewayTransaction::where('sms_message', $message)
                ->where('device_id', $device->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($existingMessageTransaction) {
                Log::info('AutoSMS Payment Hub - Duplicate SMS detected by exact message content', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'message_preview' => substr($message, 0, 100),
                    'existing_transaction_id' => $existingMessageTransaction->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS already processed (duplicate message within 5 minutes)',
                    'transaction_detected' => true,
                    'duplicate' => true,
                    'existing_transaction_id' => $existingMessageTransaction->id,
                ];
            }
        }

        // Check if sender is in allowed list
        if (!$isTest) {
            // Get user's whitelist settings
            $settings = \Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting::where('user_id', $device->user_id)->first();
            $userWhitelist = [];
            if ($settings && !empty($settings->whitelist_senders)) {
                $userWhitelist = is_array($settings->whitelist_senders) 
                    ? $settings->whitelist_senders 
                    : array_map('trim', explode(',', $settings->whitelist_senders));
            }

            $allowedSenders = array_merge(config('text-payment-gateway.allowed_senders', []), $userWhitelist);
            
            // Allow if whitelist is empty, otherwise must be in the list
            $isAllowedSender = empty($allowedSenders) || in_array(strtolower($senderName), array_map('strtolower', $allowedSenders));

            if (!$isAllowedSender) {
                Log::info('SMS received from non-allowed sender', [
                    'sender' => $senderName,
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS received but sender not in allowed list',
                    'processed' => false,
                ];
            }
        }

        // Process SMS for transaction detection using the parser service
        $transactionData = $this->parser->parse($message, $senderName);

        $debugInfo = null;

        Log::info('AutoSMS Payment Hub - SMS Received', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'sender' => $senderName,
            'transaction_detected' => $transactionData !== null,
            'transaction_data' => $transactionData,
            'debug_info' => $debugInfo,
        ]);

        // If transaction detected, process it
        if ($transactionData) {
            $this->processTransaction($device, $transactionData, $smsData);
        }

        $response = [
            'success' => true,
            'message' => 'SMS received and processed',
            'transaction_detected' => $transactionData !== null,
            'transaction_data' => $transactionData,
        ];

        if ($transactionData === null && $debugInfo) {
            $response['debug'] = $debugInfo;
        }

        return $response;
    }

    /**
     * Process detected transaction
     */
    protected function processTransaction(SmsPaymentGatewayDevice $device, array $transactionData, array $smsData): void
    {
        try {
            $user = $device->user;

            $transactionDate = null;
            if (isset($transactionData['date']) && $transactionData['date']) {
                try {
                    $transactionDate = Carbon::parse($transactionData['date']);
                } catch (\Exception $e) {
                    $transactionDate = now();
                }
            } else {
                $transactionDate = now();
            }

            $phoneNumber = $transactionData['phone_number'] ?? null;

            $smsTimestamp = isset($smsData['timestamp']) ? intval($smsData['timestamp']) : null;
            $spoofDetectionEnabled = false; // Disabled completely as per user request

            if ($spoofDetectionEnabled) {
                $spoofingCheck = $this->checkForSpoofing(
                    $transactionData['sender'] ?? '',
                    $transactionData['balance'] ?? null,
                    $transactionData['amount'],
                    $user->id,
                    $smsTimestamp
                );
            } else {
                $spoofingCheck = [
                    'is_spoofed' => false,
                    'reason' => null,
                ];
            }

            $metadata = [
                'name' => $smsData['name'] ?? null,
                'phoneNumber' => $smsData['phoneNumber'] ?? null,
                'simSlot' => $smsData['simSlot'] ?? null,
                'timestamp' => $smsData['timestamp'] ?? null,
            ];

            if (isset($transactionData['reference_number']) && $transactionData['reference_number']) {
                $metadata['reference_number'] = $transactionData['reference_number'];
            }

            $autoSmsTransaction = SmsPaymentGatewayTransaction::create([
                'device_id' => $device->id,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'sender' => $transactionData['sender'] ?? '',
                'sender_name' => $smsData['sender'] ?? '',
                'amount' => $transactionData['amount'],
                'currency_id' => \App\Models\Currency::where('currency', strtoupper($transactionData['currency'] ?? null))->value('id') ?? 1,
                'balance' => $transactionData['balance'] ?? null,
                'phone_number' => $phoneNumber,
                'reference_number' => $transactionData['reference_number'] ?? null,
                'sms_message' => $smsData['message'] ?? '',
                'message_id' => $smsData['message_id'] ?? null,
                'sms_timestamp' => $smsTimestamp,
                'transaction_date' => $transactionDate,
                'metadata' => $smsData,
                'is_spoofed' => $spoofingCheck['is_spoofed'],
                'spoofing_reason' => $spoofingCheck['reason'],
                'is_test' => $smsData['is_test'] ?? false,
                'status' => $spoofingCheck['is_spoofed'] ? 'spoofed' : 'pending',
            ]);

            Log::info('AutoSMS Payment Hub - Transaction Created', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'sms_payment_gateway_transaction_id' => $autoSmsTransaction->id,
                'amount' => $transactionData['amount'],
                'is_spoofed' => $spoofingCheck['is_spoofed'],
            ]);

            // Realtime matching was removed here because the flow is strictly manual now via the Widget.

            // Dispatch webhook asynchronously using the dedicated service
            $webhookService = app(\Modules\SmsPaymentGateway\Services\WebhookDispatchService::class);
            $webhookService->dispatchTransactionWebhook($autoSmsTransaction);

        } catch (\Exception $e) {
            Log::error('AutoSMS Payment Hub - Transaction Processing Failed', [
                'device_id' => $device->id,
                'user_id' => $device->user_id ?? null,
                'error' => $e->getMessage(),
                'transaction_data' => $transactionData,
            ]);
            if (config('app.debug')) {
                throw $e;
            }
        }
    }

    protected function checkForSpoofing(string $sender, ?float $currentBalance, float $currentAmount, int $userId, ?int $smsTimestamp = null): array
    {
        if ($currentBalance === null) {
            return ['is_spoofed' => false, 'reason' => null];
        }

        $tolerance = config('text-payment-gateway.spoofing_tolerance', 100.00);
        $reasons = [];

        $previousTransaction = null;
        if ($smsTimestamp) {
            $timestampSeconds = intval($smsTimestamp / 1000);
            $timestampDate = Carbon::createFromTimestamp($timestampSeconds);

            $previousTransaction = SmsPaymentGatewayTransaction::where('sender', $sender)
                ->where('user_id', $userId)
                ->where('balance', '!=', null)
                ->where('is_spoofed', false)
                ->where(function ($query) use ($smsTimestamp, $timestampDate) {
                    $query->where('sms_timestamp', '<', $smsTimestamp)
                        ->orWhere(function ($q) use ($timestampDate) {
                            $q->whereNull('sms_timestamp')
                                ->where('transaction_date', '<', $timestampDate);
                        });
                })
                ->orderByRaw('COALESCE(sms_timestamp, UNIX_TIMESTAMP(transaction_date) * 1000) DESC')
                ->first();
        }

        if (!$previousTransaction) {
            $previousTransaction = SmsPaymentGatewayTransaction::where('sender', $sender)
                ->where('user_id', $userId)
                ->where('balance', '!=', null)
                ->where('is_spoofed', false)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if ($previousTransaction) {
            $previousBalance = floatval($previousTransaction->balance);
            $expectedBalance = $previousBalance + $currentAmount;
            $balanceDifference = abs($currentBalance - $expectedBalance);

            if ($balanceDifference > $tolerance) {
                // Determine if we should flag it. Sometimes an intermediate transaction is missed.
                // But for robust anti-spoofing, we flag it for manual review.
                if ($currentBalance >= $previousBalance) {
                     $reasons[] = sprintf(
                         'Balance mismatch: Expected %.2f (previous %.2f + amount %.2f), got %.2f. Diff: %.2f',
                         $expectedBalance, $previousBalance, $currentAmount, $currentBalance, $balanceDifference
                     );
                }
            }
        }

        if (!empty($reasons)) {
            $reason = implode(' | ', $reasons);
            Log::warning('AutoSMS Payment Hub - Potential SMS Spoofing Detected', [
                'user_id' => $userId,
                'sender' => $sender,
                'current_balance' => $currentBalance,
                'reasons' => $reasons,
            ]);
            return ['is_spoofed' => true, 'reason' => $reason];
        }

        return ['is_spoofed' => false, 'reason' => null];
    }

    /**
     * Normalize Egyptian Phone Number
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        return $this->parser->normalizePhoneNumber($phone);
    }
}

