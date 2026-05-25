<?php

namespace Modules\SmsPaymentGateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWallet;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayOrderLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\SmsPaymentGateway\Services\SmsPaymentGatewayParserService;

class SmsPaymentGatewayPaymentHubController extends Controller
{
    /**
     * Connect device using connection code
     * POST /api/sms-payment-gateway/connect
     * Public endpoint (no auth required, uses connection code)
     */
    public function connect(Request $request)
    {
        $request->validate([
            'connection_code' => 'required|string|size:32',
            'device_token' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'sim_slot' => 'nullable|integer|min:0|max:1',
        ]);

        // Find device by connection code
        $device = SmsPaymentGatewayDevice::where('connection_code', $request->connection_code)
            ->where('status', 'pending')
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired connection code'
            ], 404);
        }

        // Check if connection code is still valid
        if (!$device->isConnectionCodeValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Connection code has expired'
            ], 400);
        }

        // Check if device token already exists for another user
        $existingDevice = SmsPaymentGatewayDevice::where('device_token', $request->device_token)
            ->where('id', '!=', $device->id)
            ->first();

        if ($existingDevice) {
            return response()->json([
                'success' => false,
                'message' => 'Device token already registered to another account'
            ], 409);
        }

        // Update device with connection info
        $device->update([
            'device_token' => $request->device_token,
            'device_name' => $request->device_name ?? 'AutoSMS Device',
            'phone_number' => $request->phone_number,
            'sim_slot' => $request->sim_slot,
            'status' => 'connected',
            'connected_at' => now(),
            'last_seen_at' => now(),
            'connection_code' => null, // Clear connection code after use
            'connection_code_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $device->id,
                'user_id' => $device->user_id,
                'status' => 'connected',
                'connected_at' => $device->connected_at->toIso8601String(),
            ],
            'message' => 'Device connected successfully'
        ]);
    }

    /**
     * Get allowed SMS senders
     * GET /api/sms-payment-gateway/allowed-senders
     * Public endpoint (no auth required)
     */
    public function getAllowedSenders(Request $request)
    {
        // Return array of allowed sender names
        // This can be configured in config file or database
        $allowedSenders = config('sms-payment-gateway.allowed_senders', [

        ]);

        // Return as array (not wrapped in object)
        return response()->json($allowedSenders);
    }

    /**
     * Receive SMS message from device
     * POST /api/sms-payment-gateway/sms
     * Public endpoint (no auth required, uses device token)
     */
    public function receiveSms(Request $request)
    {
        // Sanitize input to ensure valid UTF-8
        $input = $request->all();
        array_walk_recursive($input, function (&$item) {
            if (is_string($item)) {
                if (!mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
                // Strip control characters but preserve newlines and tabs
                $item = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $item);
            }
        });
        $request->merge($input);

        $request->validate([
            'sender' => 'required|string|max:255',
            'message' => 'required|string',
            'timestamp' => 'required|integer',
            'message_id' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'phoneNumber' => 'nullable|string|max:20',
            'simSlot' => 'nullable|integer|min:0|max:1',
            'device_token' => 'required|string|max:255',
        ]);

        // Find device by token
        $device = SmsPaymentGatewayDevice::where('device_token', $request->device_token)
            ->where('status', 'connected')
            ->first();



        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found or not connected'
            ], 404);
        }

        // Update device last seen
        $device->updateLastSeen();

        // Check for duplicate by message_id if provided
        if ($request->has('message_id') && !empty($request->message_id)) {
            $existingTransaction = SmsPaymentGatewayTransaction::where('message_id', $request->message_id)
                ->where('device_id', $device->id)
                ->first();

            if ($existingTransaction) {
                Log::info('AutoSMS Payment Hub - Duplicate SMS detected by message_id', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'message_id' => $request->message_id,
                    'existing_transaction_id' => $existingTransaction->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS already processed (duplicate)',
                    'transaction_detected' => true,
                    'duplicate' => true,
                    'existing_transaction_id' => $existingTransaction->id,
                ]);
            }
        }



        // Check for duplicate by exact SMS message content within 5 minutes
        // Skip check if in test mode
        if (!$request->is_test) {
            $existingMessageTransaction = SmsPaymentGatewayTransaction::where('sms_message', $request->message)
                ->where('device_id', $device->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($existingMessageTransaction) {
                Log::info('AutoSMS Payment Hub - Duplicate SMS detected by exact message content', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'message_preview' => substr($request->message, 0, 100),
                    'existing_transaction_id' => $existingMessageTransaction->id,
                    'time_difference_seconds' => now()->diffInSeconds($existingMessageTransaction->created_at),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS already processed (duplicate message within 5 minutes)',
                    'transaction_detected' => true,
                    'duplicate' => true,
                    'existing_transaction_id' => $existingMessageTransaction->id,
                ]);
            }
        }




        // Check if sender is in allowed list
        // Bypass if in test mode
        if (!$request->is_test) {
            $allowedSenders = config('sms-payment-gateway.allowed_senders', [
            ]);

            $senderName = $request->name ?? $request->sender;
            $isAllowedSender = in_array(strtolower($senderName), array_map('strtolower', $allowedSenders));

            if (!$isAllowedSender) {
                Log::info('SMS received from non-allowed sender', [
                    'sender' => $senderName,
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                ]);

                // Still return success but don't process as payment
                return response()->json([
                    'success' => true,
                    'message' => 'SMS received but sender not in allowed list',
                    'processed' => false,
                ]);
            }
        }



        // Ensure senderName is set for logic below
        $senderName = $request->name ?? $request->sender;

        // Process SMS for transaction detection using the parser service
        $parserService = app(SmsPaymentGatewayParserService::class);
        $transactionData = $parserService->detectTransaction($request->message, $senderName, $device);

        // Prepare debug information
        $debugInfo = null;
        if ($transactionData === null) {
            $debugInfo = $parserService->getDebugInfo($request->message, $senderName, $device);
        }



        // Log SMS message
        Log::info('AutoSMS Payment Hub - SMS Received', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'sender' => $senderName,
            'message' => $request->message,
            'transaction_detected' => $transactionData !== null,
            'transaction_data' => $transactionData,
            'debug_info' => $debugInfo,
        ]);

        // If transaction detected, process it
        if ($transactionData) {
            $this->processTransaction($device, $transactionData, $request->all());
        }

        $response = [
            'success' => true,
            'message' => 'SMS received and processed',
            'transaction_detected' => $transactionData !== null,
            'transaction_data' => $transactionData,
        ];

        // Add debug info when transaction not detected
        if ($transactionData === null && $debugInfo) {
            $response['debug'] = $debugInfo;
        }

        // Sanitize response to ensure valid JSON
        $response = $this->sanitizeForJson($response);

        return response()->json($response);
    }

    /**
     * Recursive helper to sanitize array/string for JSON encoding
     * Removes non-UTF-8 characters and control characters
     */
    protected function sanitizeForJson($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeForJson($value);
            }
            return $data;
        }

        if (is_string($data)) {
            // Convert to UTF-8 if needed
            if (!mb_check_encoding($data, 'UTF-8')) {
                $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            }
            // Remove control characters (except newline, return, tab)
            return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
        }

        return $data;
    }

    /**
     * Extract transaction information using AI
     */
    protected function extractTransactionWithAI(string $message, string $sender, User $user): ?array
    {
        $defaultProvider = $user->default_ai_model ?? 'gemini';

        if ($defaultProvider === 'openai') {
            $apiKey = $user->openai_api_key ?? null;
            $model = $user->openai_model ?? 'gpt-4o-mini';
        } else {
            $apiKey = $user->gemini_api ?? null;
            $model = $user->gemini_model ?? 'gemini-2.0-flash';
        }

        if (empty($apiKey)) {
            return null;
        }

        $prompt = $this->buildGeminiPrompt($message, $sender);

        try {
            if ($defaultProvider === 'openai') {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt[0]['parts'][0]['text']] // Convert Gemini format
                        ],
                        'temperature' => 0.7,
                    ]);
            } else {
                $geminiBase = 'https://generativelanguage.googleapis.com/v1beta/models';
                $endpoint = sprintf(
                    '%s/%s:generateContent?key=%s',
                    $geminiBase,
                    $model,
                    $apiKey
                );
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($endpoint, ['contents' => $prompt]);
            }

            if ($response->failed()) {
                Log::warning('AutoSMS Payment Hub - AI API failed', [
                    'user_id' => $user->id,
                    'provider' => $defaultProvider,
                    'status_code' => $response->status(),
                    'error' => $response->json()['error']['message'] ?? 'Unknown error',
                ]);
                return null;
            }

            $aiResponse = $response->json();
            if ($defaultProvider === 'openai') {
                $rawText = $aiResponse['choices'][0]['message']['content'] ?? null;
            } else {
                $rawText = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            if (!$rawText) {
                return null;
            }

            // Extract JSON from response (may be wrapped in markdown code blocks)
            $jsonData = $this->extractJsonFromAIResponse($rawText);

            if (!$jsonData || !is_array($jsonData)) {
                return null;
            }

            // Validate and normalize the extracted data
            return $this->normalizeAITransactionData($jsonData, $sender);

        } catch (\Exception $e) {
            Log::warning('AutoSMS Payment Hub - AI extraction error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build prompt for Gemini to extract transaction information
     */
    protected function buildGeminiPrompt(string $message, string $sender): array
    {
        $systemPrompt = "You are an expert at extracting financial transaction information from SMS messages, especially Arabic and English payment notifications from services like e&money, VF-Cash, CIB bank, and similar Egyptian mobile wallet and banking services.

Your task is to analyze the SMS message and extract the following information in JSON format:
- amount: The transaction amount (numeric value only, no currency symbols)
- balance: The account balance after the transaction (numeric value only, no currency symbols). This is OPTIONAL for bank transfers.
- currency: The currency code (usually 'EGP')
- phone_number: The recipient's phone number (normalized format: 010XXXXXXXX) OR null if not available
- reference_number: The transaction reference number (e.g., \"38518e7b\") for bank transfers
- sender_name: The name of the person who sent/received the money (if mentioned)
- date: Transaction date in ISO 8601 format if available, otherwise null

IMPORTANT RULES:
1. Only extract information if this is a REAL transaction (money received/deposited/transferred), NOT promotional messages, cashback, gifts, or offers
2. Phone number must be in format: 010XXXXXXXX (10-11 digits starting with 0) OR null
3. If NEITHER phone number NOR reference number is found, return null for the entire response
4. Amount must be a positive number
5. Balance is OPTIONAL - for bank transfers (CIB, etc.), balance may not be present
6. Balance is usually shown as \"Available Balance: X\", \"رصيد: X\", \"Balance: X\", or \"الرصيد: X\"
7. Reference number is shown as \"برقم مرجعي\", \"reference number\", \"ref:\", etc.
8. Ignore promotional messages, cashback offers, gifts, and marketing messages
9. Return ONLY valid JSON, no markdown, no explanations, no code blocks
10. If transaction information is incomplete or unclear, return null

Example valid response (wallet with phone):
{\"amount\": 100.50, \"balance\": 500.75, \"currency\": \"EGP\", \"phone_number\": \"01015218548\", \"reference_number\": null, \"sender_name\": \"Ahmed Mohamed\", \"date\": \"2025-01-15T10:30:00Z\"}

Example valid response (bank transfer with reference):
{\"amount\": 250.00, \"balance\": null, \"currency\": \"EGP\", \"phone_number\": null, \"reference_number\": \"38518e7b\", \"sender_name\": null, \"date\": \"2026-01-21T15:49:00Z\"}

Example for non-transaction (return null):
null";

        $userPrompt = "SMS Message from sender '{$sender}':\n\n{$message}\n\nExtract transaction information as JSON. IMPORTANT: Either phone_number OR reference_number must be present. Balance is optional for bank transfers.";

        return [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemPrompt . "\n\n" . $userPrompt]
                ]
            ]
        ];
    }

    /**
     * Extract JSON from AI response (handles markdown code blocks)
     */
    protected function extractJsonFromAIResponse(string $text): ?array
    {
        // Remove markdown code blocks if present
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        // Try to find JSON object in the text
        if (preg_match('/\{[^}]+\}/s', $text, $matches)) {
            $jsonText = $matches[0];
        } else {
            $jsonText = $text;
        }

        // Check if response is "null"
        if (strtolower(trim($jsonText)) === 'null') {
            return null;
        }

        $decoded = json_decode($jsonText, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Normalize and validate AI-extracted transaction data
     */
    protected function normalizeAITransactionData(array $data, string $sender): ?array
    {
        // Validate required fields - need either phone_number OR reference_number
        if (!isset($data['amount'])) {
            return null;
        }

        $amount = floatval($data['amount'] ?? 0);
        $phoneNumber = $data['phone_number'] ?? null;
        $referenceNumber = $data['reference_number'] ?? null;

        // Must have either phone number or reference number
        if (!$phoneNumber && !$referenceNumber) {
            return null;
        }

        // Validate amount
        $minAmount = config('sms-payment-gateway.min_transaction_amount', 1);
        $maxAmount = config('sms-payment-gateway.max_transaction_amount', 1000000);

        if ($amount <= 0 || $amount < $minAmount || $amount > $maxAmount) {
            return null;
        }

        // Normalize phone number if present
        if ($phoneNumber) {
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            if (!$this->isPhoneNumber($phoneNumber)) {
                $phoneNumber = null;
            }
        }

        // If we have reference number but no valid phone, use reference as identifier
        if (!$phoneNumber && !$referenceNumber) {
            return null;
        }

        // Parse date if provided
        $transactionDate = null;
        if (isset($data['date']) && $data['date']) {
            try {
                $transactionDate = Carbon::parse($data['date']);
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        // Extract balance - OPTIONAL for bank transfers
        $balance = null;
        if (isset($data['balance']) && $data['balance'] !== null) {
            $balance = floatval($data['balance']);
            // Validate balance is reasonable (positive and not too large)
            if ($balance < 0 || $balance > 10000000) {
                $balance = null;
            }
        }

        // Balance is required only for wallet transactions (with phone number)
        // Bank transfers (with reference number) don't require balance
        if ($balance === null && $phoneNumber && !$referenceNumber) {
            Log::info('AutoSMS Payment Hub - Wallet transaction rejected: No balance found', [
                'sender' => $sender,
                'phone_number' => $phoneNumber,
                'amount' => $amount,
            ]);
            return null;
        }

        return [
            'amount' => $amount,
            'balance' => $balance,
            'currency' => $data['currency'] ?? 'EGP',
            'sender' => $sender,
            'sender_name' => $data['sender_name'] ?? null,
            'phone_number' => $phoneNumber,
            'reference_number' => $referenceNumber,
            'date' => $transactionDate ? $transactionDate->toIso8601String() : null,
        ];
    }


    /**
     * Clean message by removing non-transaction numbers (dates, times, phone numbers, etc.)
     * This helps avoid conflicts when extracting transaction amounts
     */
    protected function cleanMessageForAmountExtraction(string $message): string
    {
        $cleaned = $message;

        // Remove date patterns (e.g., "Nov 25, 2025" or "25/11/2025")
        $cleaned = preg_replace('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},?\s+\d{4}/i', '', $cleaned);
        $cleaned = preg_replace('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', '', $cleaned);

        // Remove time patterns (e.g., "8:33:31 PM" or "8:33 PM")
        $cleaned = preg_replace('/\b\d{1,2}:\d{1,2}(?::\d{1,2})?\s*(?:AM|PM|am|pm)?\b/i', '', $cleaned);

        // Remove phone numbers (10+ digits, with various formats)
        // Pattern: 00201015218548, 01015218548, 20XXXXXXXXX, etc.
        $cleaned = preg_replace('/\b(?:00|0)?\d{10,15}\b/', '', $cleaned);

        // Remove reference numbers (usually after "Ref:" or "رقم")
        $cleaned = preg_replace('/(?:ref|reference|رقم|رقم المرجع)[\s:]*\d{10,15}/i', '', $cleaned);

        // Remove account numbers (e.g., "Account Number 2523")
        $cleaned = preg_replace('/account\s+number\s+\d+/i', '', $cleaned);
        $cleaned = preg_replace('/رقم\s+حساب\s+\d+/i', '', $cleaned);

        // Remove balance amounts (e.g., "Available Balance: 595.63")
        // But be careful not to remove transaction amounts - only remove if it's clearly a balance
        $cleaned = preg_replace('/(?:available\s+)?balance[\s:]*\d+(?:\.\d+)?/i', '', $cleaned);
        // Only remove balance patterns that are clearly balance (with "محفظتك" or "الحالى" context)
        $cleaned = preg_replace('/رصيد.*?محفظتك.*?(\d+(?:\.\d+)?)/i', '', $cleaned);
        // Don't remove simple "رصيد" as it might be part of transaction context

        // Remove years (4 digits 1900-2100 that are standalone)
        $cleaned = preg_replace('/\b(19|20)\d{2}\b/', '', $cleaned);

        // Clean up multiple spaces
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        return $cleaned;
    }

    /**
     * Check if a string is a phone number
     */
    protected function isPhoneNumber(string $value): bool
    {
        // Remove all non-numeric characters
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Check if it's all numeric and has phone number length (10-15 digits)
        if (!ctype_digit($cleanValue) || strlen($cleanValue) < 10 || strlen($cleanValue) > 15) {
            return false;
        }

        // Check if it looks like a phone number (starts with 0, 00, 20, or country code)
        // Egyptian numbers: 0XXXXXXXXX, 0020XXXXXXXXX, 20XXXXXXXXX
        if (
            str_starts_with($cleanValue, '0') ||
            str_starts_with($cleanValue, '00') ||
            str_starts_with($cleanValue, '20')
        ) {
            return true;
        }

        // If it's 10-11 digits, it could be a phone number
        if (strlen($cleanValue) >= 10 && strlen($cleanValue) <= 11) {
            return true;
        }

        return false;
    }

    /**
     * Normalize phone number
     * Converts formats like 00201015218548 to 01015218548
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading 00 and convert to format starting with 0
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        // If phone starts with country code (20 for Egypt), remove it and add 0
        if (str_starts_with($phone, '20') && strlen($phone) >= 12) {
            $phone = '0' . substr($phone, 2);
        }

        // If phone doesn't start with 0 and is 10 digits, add 0
        if (!str_starts_with($phone, '0') && strlen($phone) == 10) {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    /**
     * Get debug information about why transaction detection failed
     */
    protected function getTransactionDetectionDebugInfo(string $message, string $sender, ?SmsPaymentGatewayDevice $device = null): array
    {
        $debugInfo = [
            'reasons' => [],
            'extracted_data' => [],
            'checks' => [],
        ];

        // Check 1: Phone number extraction
        $phoneNumber = null;
        $phonePatterns = [
            '/من\s+رقم\s+حساب\s+(\d{10,15})/i',
            '/من\s+رقم\s+(\d{10,15})/i',
            '/(?:from|received\s+from)[\s:]+(\d{10,15})/i',
            '/(?:رقم\s+حساب|phone|mobile|tel)[\s:]*(\d{10,15})/i',
        ];

        foreach ($phonePatterns as $pattern) {
            if (preg_match($pattern, $message, $phoneMatches)) {
                $phoneNumber = $this->normalizePhoneNumber($phoneMatches[1]);
                if ($this->isPhoneNumber($phoneNumber)) {
                    break;
                } else {
                    $phoneNumber = null;
                }
            }
        }

        $debugInfo['extracted_data']['phone_number'] = $phoneNumber;
        $debugInfo['checks']['phone_number_found'] = $phoneNumber !== null;

        if (!$phoneNumber) {
            $debugInfo['reasons'][] = 'No valid phone number found in SMS message';
        }

        // Check 2: Balance extraction
        $balance = null;
        $balancePatterns = [
            '/(?:available\s+)?balance[\s:]*(\d+(?:\.\d+)?)/i',
            '/رصيد.*?محفظتك.*?الحالى\s*(\d+(?:\.\d+)?)/i',  // "رصيد محفظتك الحالى 10754.73"
            '/رصيد.*?محفظتك\s*(\d+(?:\.\d+)?)/i',  // "رصيد محفظتك 10754.73"
            '/رصيد.*?(\d+(?:\.\d+)?)\s*ج\.م/i',  // "رصيد 10754.73 ج.م"
            '/رصيد[\s:]*(\d+(?:\.\d+)?)/i',  // "رصيد: 10754.73"
            '/الرصيد.*?(\d+(?:\.\d+)?)\s*ج\.م/i',  // "الرصيد 10754.73 ج.م"
            '/الرصيد[\s:]*(\d+(?:\.\d+)?)/i',  // "الرصيد: 10754.73"
            '/balance[\s:]*(\d+(?:\.\d+)?)/i',
        ];

        foreach ($balancePatterns as $pattern) {
            if (preg_match($pattern, $message, $balanceMatches)) {
                $balance = floatval($balanceMatches[1]);
                if ($balance >= 0 && $balance <= 10000000) {
                    break;
                } else {
                    $balance = null;
                }
            }
        }

        $debugInfo['extracted_data']['balance'] = $balance;
        $debugInfo['checks']['balance_found'] = $balance !== null;

        if (!$balance) {
            $debugInfo['reasons'][] = 'No balance found in SMS message (balance is required)';
        }

        // Check 3: Amount extraction
        $cleanedMessage = $this->cleanMessageForAmountExtraction($message);
        $amount = null;
        // Use the same patterns as detectTransaction
        $patterns = [
            '/تم.*?إستلام.*?مبلغ\s*(\d+(?:\.\d+)?)/i',  // "تم إستلام مبلغ 10.00"
            '/تم.*?استلام.*?مبلغ\s*(\d+(?:\.\d+)?)/i',  // "تم استلام مبلغ 10.00"
            '/مبلغ\s*(\d+(?:\.\d+)?)\s*ج\.م/i',  // "مبلغ 10.00 ج.م"
            '/مبلغ\s*(\d+(?:\.\d+)?)\s*جنيه/i',  // "مبلغ 10.00 جنيه"
            '/تم.*?استلام.*?(\d+(?:\.\d+)?)\s+(?:جنيه|EGP|LE|ج\.م)/i',
            '/تم.*?إستلام.*?(\d+(?:\.\d+)?)\s+(?:جنيه|EGP|LE|ج\.م)/i',
            '/received\s+(\d+(?:\.\d+)?)\s+(?:EGP|LE|جنيه)/i',
            '/amount\s+(\d+(?:\.\d+)?)\s+(?:EGP|LE|جنيه)/i',
        ];

        // Try original message first (before cleaning)
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $amount = floatval($matches[1]);
                break;
            }
        }

        // If not found, try cleaned message
        if ($amount === null) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $cleanedMessage, $matches)) {
                    $amount = floatval($matches[1]);
                    break;
                }
            }
        }

        $debugInfo['extracted_data']['amount'] = $amount;
        $debugInfo['checks']['amount_found'] = $amount !== null;

        if (!$amount) {
            $debugInfo['reasons'][] = 'No transaction amount found in SMS message';
        }

        // Check 4: Promotional message check
        $exclusionPatterns = config('sms-payment-gateway.exclusion_patterns', []);
        $isPromotional = false;
        $matchedPattern = null;

        foreach ($exclusionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $isPromotional = true;
                $matchedPattern = $pattern;
                $debugInfo['reasons'][] = 'Message matches promotional/cashback pattern: ' . $pattern;
                break;
            }
        }

        $debugInfo['checks']['is_promotional'] = $isPromotional;
        $debugInfo['checks']['matched_exclusion_pattern'] = $matchedPattern;

        // Check 5: Gemini availability
        $geminiAvailable = false;
        $geminiUsed = false;
        if ($device && $device->user) {
            $geminiAvailable = !empty($device->user->gemini_api) && config('sms-payment-gateway.enable_gemini_extraction', true);
        }
        $debugInfo['checks']['gemini_available'] = $geminiAvailable;
        $debugInfo['checks']['gemini_used'] = $geminiUsed;

        // Check 6: Minimum amount validation
        if ($amount !== null) {
            $minAmount = config('sms-payment-gateway.min_transaction_amount', 1);
            $maxAmount = config('sms-payment-gateway.max_transaction_amount', 1000000);

            if ($amount < $minAmount) {
                $debugInfo['reasons'][] = sprintf('Amount %.2f is below minimum %.2f', $amount, $minAmount);
            }
            if ($amount > $maxAmount) {
                $debugInfo['reasons'][] = sprintf('Amount %.2f exceeds maximum %.2f', $amount, $maxAmount);
            }
        }

        // Summary
        $debugInfo['summary'] = [
            'has_phone' => $phoneNumber !== null,
            'has_balance' => $balance !== null,
            'has_amount' => $amount !== null,
            'is_promotional' => $isPromotional,
            'primary_reason' => !empty($debugInfo['reasons']) ? $debugInfo['reasons'][0] : 'Unknown reason',
        ];

        return $debugInfo;
    }

    /**
     * Check for SMS spoofing by comparing balance with previous and future transactions
     * Works backward and forward to validate balance consistency
     * IMPORTANT: Balance is wallet-wide (based on sender), not phone-specific
     * Returns array with 'is_spoofed' (bool) and 'reason' (string|null)
     */
    protected function checkForSpoofing(string $sender, ?float $currentBalance, float $currentAmount, int $userId, ?int $smsTimestamp = null): array
    {
        // If no balance provided, cannot check for spoofing
        if ($currentBalance === null) {
            return [
                'is_spoofed' => false,
                'reason' => null,
            ];
        }

        // Get tolerance from config, default to 100 EGP to allow for fees, other transactions, etc.
        $tolerance = config('sms-payment-gateway.spoofing_tolerance', 100.00);
        $reasons = [];

        // IMPORTANT: Balance is wallet-wide (sender-based), not phone-specific
        // All transactions from the same sender (e.g., "e& money") share the same wallet balance
        // Check backward: Find the most recent transaction BEFORE this one from the SAME sender (by timestamp)
        $previousTransaction = null;
        if ($smsTimestamp) {
            // Convert milliseconds timestamp to seconds for date comparison
            $timestampSeconds = intval($smsTimestamp / 1000);
            $timestampDate = Carbon::createFromTimestamp($timestampSeconds);

            // Use timestamp if available for more accurate ordering
            // Filter by sender (wallet) not phone_number, since balance is wallet-wide
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

        // Fallback to date-based ordering if no timestamp
        if (!$previousTransaction) {
            // Filter by sender (wallet) not phone_number
            $previousTransaction = SmsPaymentGatewayTransaction::where('sender', $sender)
                ->where('user_id', $userId)
                ->where('balance', '!=', null)
                ->where('is_spoofed', false)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // Check forward: Find the earliest transaction AFTER this one from the SAME sender
        $nextTransaction = null;
        if ($smsTimestamp) {
            // Convert milliseconds timestamp to seconds for date comparison
            $timestampSeconds = intval($smsTimestamp / 1000);
            $timestampDate = Carbon::createFromTimestamp($timestampSeconds);

            // Filter by sender (wallet) not phone_number
            $nextTransaction = SmsPaymentGatewayTransaction::where('sender', $sender)
                ->where('user_id', $userId)
                ->where('balance', '!=', null)
                ->where('is_spoofed', false)
                ->where(function ($query) use ($smsTimestamp, $timestampDate) {
                    $query->where('sms_timestamp', '>', $smsTimestamp)
                        ->orWhere(function ($q) use ($timestampDate) {
                            $q->whereNull('sms_timestamp')
                                ->where('transaction_date', '>', $timestampDate);
                        });
                })
                ->orderByRaw('COALESCE(sms_timestamp, UNIX_TIMESTAMP(transaction_date) * 1000) ASC')
                ->first();
        }

        // Validate backward (previous transaction by timestamp)
        // If previous transaction has older timestamp and lower balance, current should be higher
        if ($previousTransaction) {
            $previousBalance = floatval($previousTransaction->balance);
            $previousAmount = floatval($previousTransaction->amount);
            $previousTimestamp = $previousTransaction->sms_timestamp ?? ($previousTransaction->transaction_date ? $previousTransaction->transaction_date->timestamp * 1000 : null);

            // Determine chronological order based on timestamps
            $isPreviousOlder = true;
            if ($smsTimestamp && $previousTimestamp) {
                $isPreviousOlder = $previousTimestamp < $smsTimestamp;
            }

            if ($isPreviousOlder) {
                // Previous transaction is older, so current balance should be: previous_balance + current_amount
                $expectedBalance = $previousBalance + $currentAmount;
                $balanceDifference = abs($currentBalance - $expectedBalance);

                // Only flag if difference exceeds tolerance
                if ($balanceDifference > $tolerance) {
                    $reasons[] = sprintf(
                        'Balance mismatch (backward): Expected %.2f (previous balance %.2f + amount %.2f), but received %.2f. Difference: %.2f',
                        $expectedBalance,
                        $previousBalance,
                        $currentAmount,
                        $currentBalance,
                        $balanceDifference
                    );
                }
            } else {
                // Current transaction is older, so previous balance should be: current_balance + previous_amount
                // This means the previous transaction was processed out of order
                $expectedPreviousBalance = $currentBalance + $previousAmount;
                $balanceDifference = abs($previousBalance - $expectedPreviousBalance);

                // If previous transaction balance doesn't match, it might be spoofed, but we're validating current
                // So we check if current makes sense: current should be less than previous (since previous is newer)
                if ($currentBalance >= $previousBalance && $balanceDifference > $tolerance) {
                    // Current balance is higher or equal to newer transaction - suspicious
                    $reasons[] = sprintf(
                        'Balance mismatch (chronological): Current transaction (older) has balance %.2f, but newer transaction has balance %.2f. Expected current to be less by %.2f. Difference: %.2f',
                        $currentBalance,
                        $previousBalance,
                        $previousAmount,
                        $balanceDifference
                    );
                }
            }
        }

        // Validate forward (next transaction by timestamp)
        // If next transaction has newer timestamp and higher balance, current should be lower
        if ($nextTransaction) {
            $nextBalance = floatval($nextTransaction->balance);
            $nextAmount = floatval($nextTransaction->amount);
            $nextTimestamp = $nextTransaction->sms_timestamp ?? ($nextTransaction->transaction_date ? $nextTransaction->transaction_date->timestamp * 1000 : null);

            // Determine chronological order based on timestamps
            $isNextNewer = true;
            if ($smsTimestamp && $nextTimestamp) {
                $isNextNewer = $nextTimestamp > $smsTimestamp;
            }

            if ($isNextNewer) {
                // Next transaction is newer, so next balance should be: current_balance + next_amount
                $expectedNextBalance = $currentBalance + $nextAmount;
                $balanceDifference = abs($nextBalance - $expectedNextBalance);

                // Only flag if difference exceeds tolerance
                if ($balanceDifference > $tolerance) {
                    $reasons[] = sprintf(
                        'Balance mismatch (forward): Expected next balance %.2f (current balance %.2f + next amount %.2f), but next balance is %.2f. Difference: %.2f',
                        $expectedNextBalance,
                        $currentBalance,
                        $nextAmount,
                        $nextBalance,
                        $balanceDifference
                    );
                }
            } else {
                // Current transaction is newer, so current balance should be: next_balance + current_amount
                // This means the next transaction was processed out of order
                $expectedCurrentBalance = $nextBalance + $currentAmount;
                $balanceDifference = abs($currentBalance - $expectedCurrentBalance);

                // If current balance doesn't match, flag it
                if ($balanceDifference > $tolerance) {
                    $reasons[] = sprintf(
                        'Balance mismatch (chronological): Current transaction (newer) has balance %.2f, but older transaction has balance %.2f. Expected current to be %.2f. Difference: %.2f',
                        $currentBalance,
                        $nextBalance,
                        $expectedCurrentBalance,
                        $balanceDifference
                    );
                }
            }
        }

        // If we have reasons, transaction is spoofed
        if (!empty($reasons)) {
            $reason = implode(' | ', $reasons);

            Log::warning('AutoSMS Payment Hub - Potential SMS Spoofing Detected', [
                'user_id' => $userId,
                'sender' => $sender,
                'current_balance' => $currentBalance,
                'current_amount' => $currentAmount,
                'previous_transaction_id' => $previousTransaction?->id,
                'next_transaction_id' => $nextTransaction?->id,
                'reasons' => $reasons,
            ]);

            return [
                'is_spoofed' => true,
                'reason' => $reason,
            ];
        }

        // Balance matches expected values - transaction appears legitimate
        return [
            'is_spoofed' => false,
            'reason' => null,
        ];
    }

    /**
     * Process detected transaction
     */
    protected function processTransaction(SmsPaymentGatewayDevice $device, array $transactionData, array $smsData): void
    {
        try {
            $user = $device->user;

            // Parse transaction date if available
            $transactionDate = null;
            if (isset($transactionData['date']) && $transactionData['date']) {
                try {
                    $transactionDate = Carbon::parse($transactionData['date']);
                } catch (\Exception $e) {
                    // Use current time if parsing fails
                    $transactionDate = now();
                }
            } else {
                $transactionDate = now();
            }

            // Find latest pending order link for this user and matching phone_number
            $orderId = null;
            $phoneNumber = $transactionData['phone_number'] ?? null;

            if ($phoneNumber) {
                $normalizedPhoneNumber = $this->normalizePhoneNumber($phoneNumber);
                $orderLink = SmsPaymentGatewayOrderLink::where('user_id', $user->id)
                    ->where('phone_number', $normalizedPhoneNumber)
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($orderLink) {
                    $orderId = $orderLink->order_id;
                    // Update link status to "matched"
                    $orderLink->update(['status' => 'matched']);

                    Log::info('AutoSMS Payment Hub - Order Link Matched', [
                        'user_id' => $user->id,
                        'order_link_id' => $orderLink->id,
                        'order_id' => $orderId,
                        'phone_number' => $normalizedPhoneNumber,
                    ]);
                }
            }

            // Check for SMS spoofing (only if enabled for this device)
            // IMPORTANT: Use sender (wallet) not phone_number, since balance is wallet-wide
            $smsTimestamp = isset($smsData['timestamp']) ? intval($smsData['timestamp']) : null;

            // Check if spoof detection is enabled for this device
            $spoofDetectionEnabled = $device->enable_spoof_detection ?? true;

            if ($spoofDetectionEnabled) {
                $spoofingCheck = $this->checkForSpoofing(
                    $transactionData['sender'] ?? '',
                    $transactionData['balance'] ?? null,
                    $transactionData['amount'],
                    $user->id,
                    $smsTimestamp
                );
            } else {
                // Spoof detection is disabled for this device
                $spoofingCheck = [
                    'is_spoofed' => false,
                    'reason' => null,
                ];
            }

            // Create AutoSMS transaction record
            $metadata = [
                'name' => $smsData['name'] ?? null,
                'phoneNumber' => $smsData['phoneNumber'] ?? null,
                'simSlot' => $smsData['simSlot'] ?? null,
                'timestamp' => $smsData['timestamp'] ?? null,
            ];

            // Attach order_id to transaction metadata if found
            if ($orderId) {
                $metadata['order_id'] = $orderId;
            }

            // Always add reference_number to metadata as fallback
            if (isset($transactionData['reference_number']) && $transactionData['reference_number']) {
                $metadata['reference_number'] = $transactionData['reference_number'];
            }

            // Build transaction data array
            $transactionCreateData = [
                'device_id' => $device->id,
                'user_id' => $user->id,
                'amount' => $transactionData['amount'],
                'currency' => $transactionData['currency'],
                'sender' => $transactionData['sender'],
                'phone_number' => $phoneNumber,
                'reference_number' => $transactionData['reference_number'] ?? null,
                'sender_name' => $transactionData['sender_name'] ?? null,
                'transaction_date' => $transactionDate,
                'sms_message' => $smsData['message'] ?? '',
                'message_id' => $smsData['message_id'] ?? null,
                'sms_timestamp' => isset($smsData['timestamp']) ? intval($smsData['timestamp']) : null,
                'status' => 'pending',
                'metadata' => $metadata,
                'is_test' => $smsData['is_test'] ?? false,
            ];

            // Add balance and spoofing fields if columns exist (migration has been run)
            // Check if balance column exists by trying to get schema
            try {
                $columns = Schema::getColumnListing('sms_payment_gateway_transactions');
                if (in_array('balance', $columns)) {
                    $transactionCreateData['balance'] = $transactionData['balance'] ?? null;
                }
                if (in_array('is_spoofed', $columns)) {
                    $transactionCreateData['is_spoofed'] = $spoofingCheck['is_spoofed'];
                }
                if (in_array('spoofing_reason', $columns)) {
                    $transactionCreateData['spoofing_reason'] = $spoofingCheck['reason'];
                }
                // message_id and sms_timestamp are already added above, but check if columns exist
                if (!in_array('message_id', $columns)) {
                    unset($transactionCreateData['message_id']);
                }
                if (!in_array('sms_timestamp', $columns)) {
                    unset($transactionCreateData['sms_timestamp']);
                }
                if (!in_array('reference_number', $columns)) {
                    unset($transactionCreateData['reference_number']);
                }
            } catch (\Exception $schemaException) {
                // If we can't check schema, try without new fields
                Log::warning('AutoSMS Payment Hub - Could not check schema, creating transaction without new fields', [
                    'error' => $schemaException->getMessage(),
                ]);
                // Remove message_id and sms_timestamp if schema check fails
                unset($transactionCreateData['message_id']);
                unset($transactionCreateData['sms_timestamp']);



            }

            $autoSmsTransaction = SmsPaymentGatewayTransaction::create($transactionCreateData);

            Log::info('AutoSMS Payment Hub - Transaction Created', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'sms_payment_gateway_transaction_id' => $autoSmsTransaction->id,
                'message_id' => $smsData['message_id'] ?? null,
                'sms_timestamp' => isset($smsData['timestamp']) ? intval($smsData['timestamp']) : null,
                'amount' => $transactionData['amount'],
                'balance' => $transactionData['balance'] ?? null,
                'currency' => $transactionData['currency'],
                'sender' => $transactionData['sender'],
                'sender_name' => $transactionData['sender_name'] ?? null,
                'phone_number' => $phoneNumber,
                'order_id' => $orderId,
                'is_spoofed' => $spoofingCheck['is_spoofed'],
                'spoofing_reason' => $spoofingCheck['reason'],
            ]);

            // Send webhook if configured
            \App\Http\Controllers\Api\SmsPaymentGatewayWebhookController::sendTransactionWebhook($autoSmsTransaction);

        } catch (\Exception $e) {
            Log::error('AutoSMS Payment Hub - Transaction Processing Failed', [
                'device_id' => $device->id,
                'user_id' => $device->user_id ?? null,
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'transaction_data' => $transactionData,
                'sms_data' => $smsData,
            ]);
            // Re-throw the exception so we can see it in the response or logs
            // But only in development mode
            if (config('app.debug')) {
                throw $e;
            }
        }
    }

    /**
     * Verify transaction by phone number
     * POST /api/sms-payment-gateway/verify-transaction
     * Requires authentication - users can only verify their own transactions
     */
    public function verifyTransaction(Request $request)
    {
        // Require authentication
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $request->validate([
            'phone_number' => 'required|string|max:20',
        ]);

        $phoneNumber = $this->normalizePhoneNumber($request->phone_number);

        // Find the most recent pending or completed transaction from this phone number
        // Only check transactions that belong to the authenticated user
        // Check within last 24 hours
        $transaction = SmsPaymentGatewayTransaction::with('device') // Eager load device for security check
            ->where('phone_number', $phoneNumber)
            ->where('user_id', $user->id) // Security: Only user's own transactions
            ->where('created_at', '>=', now()->subHours(24))
            ->whereIn('status', ['pending', 'completed', 'processed'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($transaction) {
            // Additional security: Verify the transaction device belongs to the user
            if ($transaction->device && $transaction->device->user_id !== $user->id) {
                Log::warning('AutoSMS Verify - Security violation: Transaction device mismatch', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'device_user_id' => $transaction->device->user_id,
                ]);

                return response()->json([
                    'success' => false,
                    'transaction' => null,
                    'message' => 'No transaction found for this phone number'
                ]);
            }

            // Prepare response data
            $responseData = [
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'phone_number' => $transaction->phone_number,
                    'sender_name' => $transaction->sender_name,
                    'sender' => $transaction->sender,
                    'message_id' => $transaction->message_id,
                    'status' => $transaction->status,
                    'transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->toIso8601String() : null,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ],
                'message' => 'Transaction found',
                'timestamp' => now()->toIso8601String(),
            ];

            // Generate HMAC signature for response security
            // Use the user's verification secret (separate from API token for security)
            $verificationSecret = $user->getSmsPaymentGatewayVerificationSecret();

            $responseJson = json_encode($responseData);
            $signature = hash_hmac('sha256', $responseJson, $verificationSecret);

            return response()->json($responseData)
                ->header('X-AutoSMS-Signature', $signature)
                ->header('X-AutoSMS-Timestamp', now()->toIso8601String());
        }

        // Prepare response data
        $responseData = [
            'success' => false,
            'transaction' => null,
            'message' => 'No transaction found for this phone number',
            'timestamp' => now()->toIso8601String(),
        ];

        // Generate HMAC signature for response security
        // Use the user's verification secret (separate from API token for security)
        $verificationSecret = $user->getSmsPaymentGatewayVerificationSecret();

        $responseJson = json_encode($responseData);
        $signature = hash_hmac('sha256', $responseJson, $verificationSecret);

        return response()->json($responseData)
            ->header('X-AutoSMS-Signature', $signature)
            ->header('X-AutoSMS-Timestamp', now()->toIso8601String());
    }

    /**
     * Link order to phone number
     * POST /api/sms-payment-gateway/link-order
     * Requires authentication - users can only link orders to their own account
     */
    public function linkOrder(Request $request)
    {
        // Require authentication
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $request->validate([
            'order_id' => 'required|integer',
            'phone_number' => 'required|string|max:20',
        ]);

        // Normalize phone number (remove +20, spaces, etc.)
        $phoneNumber = $this->normalizePhoneNumber($request->phone_number);

        // Create order link record with status "pending"
        $orderLink = SmsPaymentGatewayOrderLink::create([
            'user_id' => $user->id,
            'order_id' => (string) $request->order_id,
            'phone_number' => $phoneNumber,
            'status' => 'pending',
        ]);

        Log::info('AutoSMS Payment Hub - Order Linked', [
            'user_id' => $user->id,
            'order_id' => $request->order_id,
            'phone_number' => $phoneNumber,
            'order_link_id' => $orderLink->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order linked successfully'
        ]);
    }

    /**
     * Link order to phone number (Public API - Bearer token authentication)
     * POST /api/sms-payment-gateway/public/link-order
     * Requires Bearer token authentication (no Sanctum)
     */
    public function linkOrderPublic(Request $request)
    {
        // Extract token from Authorization header
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token'
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        // Lookup user via api_token
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token'
            ], 401);
        }

        // Validate request
        $request->validate([
            'order_id' => 'required|integer',
            'phone_number' => 'required|string|max:20',
        ]);

        // Normalize phone_number using existing method
        $phoneNumber = $this->normalizePhoneNumber($request->phone_number);

        // Create SmsPaymentGatewayOrderLink record
        $orderLink = SmsPaymentGatewayOrderLink::create([
            'user_id' => $user->id,
            'order_id' => (string) $request->order_id,
            'phone_number' => $phoneNumber,
            'status' => 'pending',
        ]);

        Log::info('AutoSMS Payment Hub - Public Order Linked', [
            'user_id' => $user->id,
            'order_id' => $request->order_id,
            'phone_number' => $phoneNumber,
            'order_link_id' => $orderLink->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order linked successfully'
        ]);
    }

    /**
     * Debug API: Check transactions with empty phone_number and test parser
     * GET /api/sms-payment-gateway/debug/empty-phone-numbers
     * POST /api/sms-payment-gateway/debug/empty-phone-numbers?update=true (to update transactions)
     * Public endpoint for debugging
     */
    public function debugEmptyPhoneNumbers(Request $request)
    {
        // Get limit from request or default to 50
        $limit = $request->input('limit', 50);
        $update = $request->input('update', false);

        // Get all transactions with null phone_number
        $transactions = SmsPaymentGatewayTransaction::query()
            ->with('device.user') // Eager load device and user for Gemini API access
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $results = [];
        $updatedCount = 0;

        foreach ($transactions as $transaction) {
            // Get the sender from transaction
            $sender = $transaction->sender;

            // Get device for Gemini API access
            $device = $transaction->device;

            // Re-run the parser on the SMS message
            $parsedData = $this->detectTransaction($transaction->sms_message, $sender, $device);

            $parserFoundPhone = $parsedData && isset($parsedData['phone_number']) && !empty($parsedData['phone_number']);
            $parserFoundSenderName = $parsedData && isset($parsedData['sender_name']) && !empty($parsedData['sender_name']);
            $parserFoundAmount = $parsedData && isset($parsedData['amount']) && !empty($parsedData['amount']);

            $updateData = [];
            $wasUpdated = false;

            // Update transaction if update flag is set and parser found data
            if ($update && $parsedData) {
                if ($parserFoundPhone) {
                    $updateData['phone_number'] = $parsedData['phone_number'];
                }

                if ($parserFoundSenderName) {
                    $updateData['sender_name'] = $parsedData['sender_name'];
                }

                if ($parserFoundAmount) {
                    $updateData['amount'] = $parsedData['amount'];
                }

                if (!empty($updateData)) {
                    $transaction->update($updateData);
                    $updatedCount++;
                    $wasUpdated = true;
                    // Refresh transaction to get updated values
                    $transaction->refresh();
                }
            }

            $results[] = [
                'transaction_id' => $transaction->id,
                'created_at' => $transaction->created_at->toIso8601String(),
                'sms_message' => $transaction->sms_message,
                'sender' => $sender,
                'stored_amount' => $transaction->amount,
                'stored_currency' => $transaction->currency,
                'stored_phone_number' => $transaction->phone_number,
                'stored_sender_name' => $transaction->sender_name,
                'parsed_data' => $parsedData,
                'parser_found_phone' => $parserFoundPhone,
                'parser_found_sender_name' => $parserFoundSenderName,
                'parser_found_amount' => $parsedData && isset($parsedData['amount']) && $parsedData['amount'] > 0,
                'phone_number_match' => $parsedData &&
                    isset($parsedData['phone_number']) &&
                    $parsedData['phone_number'] === $transaction->phone_number,
                'updated' => $wasUpdated,
            ];
        }

        return response()->json([
            'success' => true,
            'total_found' => $transactions->count(),
            'limit' => $limit,
            'update_mode' => $update,
            'updated_count' => $updatedCount,
            'results' => $results,
            'summary' => [
                'with_parsed_phone' => collect($results)->where('parser_found_phone', true)->count(),
                'with_parsed_sender_name' => collect($results)->where('parser_found_sender_name', true)->count(),
                'with_parsed_amount' => collect($results)->where('parser_found_amount', true)->count(),
            ],
        ]);
    }

    /**
     * Get a random active wallet for payment
     * GET /api/sms-payment-gateway/get-random-wallet
     * Public endpoint (no auth required)
     */
    public function getRandomWallet(Request $request)
    {
        // Get all active wallets
        $wallets = SmsPaymentGatewayWallet::where('is_active', true)
            ->inRandomOrder()
            ->first();

        if (!$wallets) {
            return response()->json([
                'success' => false,
                'message' => 'No active wallets available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_id' => $wallets->id,
                'payment_type' => $wallets->payment_type,
                'phone_number' => $wallets->phone_number,
            ],
            'message' => 'Random wallet retrieved successfully'
        ]);
    }

    /**
     * Verify payment by phone number
     * POST /api/sms-payment-gateway/verify-payment
     * Public endpoint (no auth required)
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'wallet_id' => 'nullable|integer|exists:sms_payment_gateway_wallets,id',
        ]);

        $phoneNumber = $request->phone_number;
        $walletId = $request->wallet_id;

        // If wallet_id is provided, verify against that specific wallet
        if ($walletId) {
            $wallet = SmsPaymentGatewayWallet::where('id', $walletId)
                ->where('is_active', true)
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid wallet'
                ], 404);
            }

            // Check if phone number matches the wallet's phone number
            if ($wallet->phone_number === $phoneNumber) {
                return response()->json([
                    'success' => true,
                    'verified' => true,
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'wallet_id' => $wallet->id,
                        'payment_type' => $wallet->payment_type,
                        'phone_number' => $wallet->phone_number,
                    ]
                ]);
            }
        }

        // Otherwise, search for any transaction with this phone number
        // This allows verification by checking if a payment was received
        $transaction = SmsPaymentGatewayTransaction::where('phone_number', $phoneNumber)
            ->where('status', 'processed')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($transaction) {
            return response()->json([
                'success' => true,
                'verified' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'transaction_date' => $transaction->transaction_date?->toIso8601String(),
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'verified' => false,
            'message' => 'Payment not found or not verified'
        ], 404);
    }
}
