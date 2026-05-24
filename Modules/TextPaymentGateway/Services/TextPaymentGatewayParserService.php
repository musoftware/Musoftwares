<?php

namespace Modules\TextPaymentGateway\Services;

use Modules\TextPaymentGateway\Models\TextPaymentGatewayDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextPaymentGatewayParserService
{
    /**
     * Detect transaction from SMS message
     */
    public function detectTransaction(string $message, string $sender, ?TextPaymentGatewayDevice $device = null): ?array
    {
        // Try AI extraction first if enabled and user has API key
        if (config('text-payment-gateway.enable_gemini_extraction', true) && $device && $device->user) {
            $aiResult = $this->extractTransactionWithAI($message, $sender, $device->user);
            if ($aiResult !== null) {
                Log::info('AutoSMS Payment Hub - Transaction extracted using AI', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'transaction_data' => $aiResult,
                ]);
                return $aiResult;
            }
        }

        // Fallback to regex-based extraction
        return $this->extractTransactionWithRegex($message, $sender);
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

        $prompt = $this->buildAIPrompt($message, $sender);

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
                            ['role' => 'user', 'content' => $prompt[0]['parts'][0]['text']]
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

            // Extract JSON from response
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
     * Build prompt for AI to extract transaction information
     */
    protected function buildAIPrompt(string $message, string $sender): array
    {
        $systemPrompt = "You are an expert at extracting financial transaction information from SMS messages, especially Arabic and English payment notifications from services like e&money, VF-Cash, CIB bank, and similar Egyptian mobile wallet and banking services.

Your task is to analyze the SMS message and extract the following information in JSON format:
- amount: The transaction amount (numeric value only, no currency symbols)
- balance: The account balance after the transaction (numeric value only, no currency symbols). This is OPTIONAL for bank transfers.
- currency: The currency code (usually 'EGP')
- phone_number: The recipient's phone number (normalized format: 010XXXXXXXX) OR null if not available
- reference_number: The transaction reference number (e.g., \"38518e7b\", or number after \"رقم العملية\") for bank transfers and wallet payments
- sender_name: The name of the person who sent/received the money (if mentioned)
- date: Transaction date in ISO 8601 format if available, otherwise null

IMPORTANT RULES:
1. Only extract information if this is a REAL transaction (money received/deposited/transferred), NOT promotional messages, cashback, gifts, or offers
2. Phone number must be in format: 010XXXXXXXX (10-11 digits starting with 0) OR null
3. If NEITHER phone number NOR reference number is found, return null for the entire response
4. Amount must be a positive number
5. Balance is OPTIONAL - for bank transfers (CIB, etc.) or InstaPay, balance may not be present
6. Balance is usually shown as \"Available Balance: X\", \"رصيد: X\", \"Balance: X\", or \"الرصيد: X\"
7. Reference number is shown as \"برقم مرجعي\", \"reference number\", \"ref:\", \"رقم العملية\", \"trx id\" etc.
8. Ignore promotional messages, cashback offers, gifts, and marketing messages
9. Return ONLY valid JSON, no markdown, no explanations, no code blocks
10. If transaction information is incomplete or unclear, return null

Example valid response (wallet with phone):
{\"amount\": 100.50, \"balance\": 500.75, \"currency\": \"EGP\", \"phone_number\": \"01015218548\", \"reference_number\": null, \"sender_name\": \"Ahmed Mohamed\", \"date\": \"2025-01-15T10:30:00Z\"}

Example valid response (bank transfer with reference):
{\"amount\": 250.00, \"balance\": null, \"currency\": \"EGP\", \"phone_number\": null, \"reference_number\": \"38518e7b\", \"sender_name\": null, \"date\": \"2026-01-21T15:49:00Z\"}

Example for non-transaction (return null):
null";

        $userPrompt = "SMS Message from sender '{$sender}':\n\n{$message}\n\nExtract transaction information as JSON. IMPORTANT: Either phone_number OR reference_number must be present. Balance is optional for bank transfers and InstaPay.";

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
        $minAmount = config('text-payment-gateway.min_transaction_amount', 1);
        $maxAmount = config('text-payment-gateway.max_transaction_amount', 1000000);

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
            if ($balance < 0 || $balance > 10000000) {
                $balance = null;
            }
        }

        // Balance is required only for wallet transactions (with phone number)
        // Bank transfers (with reference number) and InstaPay don't require balance
        // Let's also check if the message looks like a typical Vodafone Cash receipt which *should* have a balance,
        // but InstaPay ("تحويل لحظي") does not.
        $isInstapay = (mb_stripos($data['sender_name'] ?? '', 'instapay') !== false) || 
                      (isset($data['original_message']) && mb_stripos($data['original_message'], 'تحويل لحظي') !== false);
                      
        if ($balance === null && $phoneNumber && !$referenceNumber && !$isInstapay) {
            // We still allow it if we confidently extracted the amount and phone number, 
            // as some wallets (e.g. Etisalat Cash) might not always include balance, 
            // or the regex missed it. Just log a notice, but don't reject.
            Log::info('AutoSMS Payment Hub - Wallet transaction has no balance, but accepting it', [
                'sender' => $sender,
                'phone_number' => $phoneNumber,
                'amount' => $amount,
            ]);
            // Do not return null anymore for missing balance if amount & phone exist.
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
     * Extract transaction using regex patterns
     */
    protected function extractTransactionWithRegex(string $message, string $sender): ?array
    {
        // Check for promotional messages first
        if (!$this->hasPhoneNumber($message) && !$this->hasReferenceNumber($message)) {
            $exclusionPatterns = config('text-payment-gateway.exclusion_patterns', [
                '/كسبت.*?كاش\s+باك/i',
                '/كاش\s+باك/i',
                '/cashback/i',
                '/مبروك\s+كسبت/i',
                '/هدية/i',
                '/gift/i',
            ]);

            foreach ($exclusionPatterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    return null;
                }
            }
        }

        // Extract phone number and reference number
        $phoneNumber = $this->extractPhoneNumber($message);
        $referenceNumber = $this->extractReferenceNumber($message);

        // Must have either phone number or reference number
        if ($phoneNumber === null && $referenceNumber === null) {
            return null;
        }

        // Extract amount
        $amount = $this->extractAmount($message);

        if ($amount === null || $amount <= 0) {
            return null;
        }

        // Extract balance - OPTIONAL
        $balance = $this->extractBalance($message);

        $isInstapay = mb_stripos($message, 'تحويل لحظي') !== false || mb_stripos($message, 'instapay') !== false || mb_stripos($sender, 'instapay') !== false;

        if ($balance === null && $phoneNumber && !$referenceNumber && !$isInstapay) {
            Log::info('AutoSMS Payment Hub - Wallet transaction missing balance, accepting anyway as fallback', [
                'sender' => $sender,
                'phone_number' => $phoneNumber,
                'amount' => $amount,
                'message_preview' => substr($message, 0, 100),
            ]);
            // No longer rejecting based on missing balance to improve acceptance rate
        }

        // Extract sender name
        $senderName = $this->extractSenderName($message);

        // Extract date
        $transactionDate = $this->extractDate($message);

        return [
            'amount' => $amount,
            'balance' => $balance,
            'currency' => 'EGP',
            'sender' => $sender,
            'sender_name' => $senderName,
            'phone_number' => $phoneNumber,
            'reference_number' => $referenceNumber,
            'date' => $transactionDate ? $transactionDate->toIso8601String() : null,
        ];
    }

    /**
     * Check if message contains a phone number
     */
    protected function hasPhoneNumber(string $message): bool
    {
        return preg_match('/\d{10,15}/', $message) === 1;
    }

    /**
     * Check if message contains a reference number
     */
    protected function hasReferenceNumber(string $message): bool
    {
        return preg_match('/(?:برقم\s+مرجعي|رقم\s+العملية|reference|ref|trx[\s_]*id)[\s:]+([a-zA-Z0-9\-]+)/i', $message) === 1;
    }

    /**
     * Extract phone number from message
     */
    protected function extractPhoneNumber(string $message): ?string
    {
        $patterns = [
            '/من\s+رقم\s+حساب\s+(\d{10,15})/i',
            '/من\s+رقم\s+(\d{10,15})/i',
            '/(?:from|received\s+from)[\s:]+(\d{10,15})/i',
            '/(?:رقم\s+حساب|phone|mobile|tel)[\s:]*(\d{10,15})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return $this->normalizePhoneNumber($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract amount from message
     */
    protected function extractAmount(string $message): ?float
    {
        $patterns = [
            '/بمبلغ\s+(\d+(?:\.\d+)?)\s*ج\.?م/i',
            '/بمبلغ\s+(\d+(?:\.\d+)?)\s*جنيه/i',
            '/مبلغ\s+(\d+(?:\.\d+)?)\s*ج\.?م/i',
            '/مبلغ\s+(\d+(?:\.\d+)?)\s*جنيه/i',
            '/تم\s+إستلام\s+مبلغ\s+(\d+(?:\.\d+)?)/i',
            '/تم\s+استلام\s+مبلغ\s+(\d+(?:\.\d+)?)/i',
            '/تحويل\s+لحظي\s+بمبلغ\s+(\d+(?:\.\d+)?)/i',
            '/received\s+(?:EGP|LE|جنيه)\s*(\d+(?:\.\d+)?)/i',
            '/(\d+(?:\.\d+)?)\s+(?:EGP|LE|جنيه)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return floatval($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract reference number from message
     */
    protected function extractReferenceNumber(string $message): ?string
    {
        $patterns = [
            '/رقم\s+مرجعي\s+([a-zA-Z0-9\-]+)/iu',
            '/رقم\s+العملية\s+([a-zA-Z0-9\-]+)/iu',
            '/برقم\s*مرجعي\s*[:\s]*([a-zA-Z0-9\-]+)/iu',
            '/مرجعي\s*[:\s]*([a-zA-Z0-9\-]+)/iu',
            '/Transaction\s*ID\s*[:\-]?\s*([a-zA-Z0-9\-]+)/iu',
            '/trx[_\-\s]?id\s*[:\-]?\s*([a-zA-Z0-9\-]+)/iu',
            '/reference\s+(?:number|no\.?|#)?\s*[:\s]*([a-zA-Z0-9\-]+)/i',
            '/ref\s*[:\s]+([a-zA-Z0-9\-]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract balance from message
     */
    protected function extractBalance(string $message): ?float
    {
        $patterns = [
            '/(?:available\s+)?balance[\s:]*(\d+(?:\.\d+)?)/i',
            '/رصيدك?.*?الحال[يى].*?(\d+(?:\.\d+)?)/iu', // Ultra-permissive: "رصيدك" ... "الحالي" ... number
            '/رصيد.*?محفظتك.*?الحال[يى].*?(\d+(?:\.\d+)?)/iu',
            '/رصيد.*?محفظتك\s*(\d+(?:\.\d+)?)/i',
            '/رصيد.*?(\d+(?:\.\d+)?)\s*ج\.م/i',
            '/رصيد[\s:]*(\d+(?:\.\d+)?)/i',
            '/الرصيد.*?(\d+(?:\.\d+)?)\s*ج\.م/i',
            '/الرصيد[\s:]*(\d+(?:\.\d+)?)/i',
            '/balance[\s:]*(\d+(?:\.\d+)?)/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $balance = floatval($matches[1]);
                if ($balance >= 0 && $balance <= 10000000) {
                    return $balance;
                }
            }
        }

        return null;
    }

    /**
     * Extract sender name from message
     */
    protected function extractSenderName(string $message): ?string
    {
        $patterns = [
            // Handle "المسجل باسم/بإسم" properly
            '/المسجل\s+ب[اإآ]سم\s*([A-Za-z][A-Za-z\s\.]{1,50}?)(?=\s*[ا-ي]|\.\s|$|\s*بنجاح|\s+تم|رصيد|محفظة)/iu',
            // Generic patterns (lower priority)
            '/(?:اسم|name|المسجل)[\s:]*([A-Za-z\p{Arabic}][A-Za-z\p{Arabic}\s\.]{1,50}?)(?=\s*[ا-ي]|\.\s|$|\s*بنجاح|رصيد|محفظة)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $name = trim($matches[1]);
                $name = preg_replace('/^[ا-ي\s]+|[ا-ي\s]+$/u', '', $name);
                $name = preg_replace('/\s*(?:باسم|بإسم|بنجاح|تم|رصيد|محفظة)\s*/iu', '', $name);
                return trim($name);
            }
        }

        return null;
    }

    /**
     * Extract date from message
     */
    protected function extractDate(string $message): ?Carbon
    {
        // Try to match date with time (e.g., "21-01-2026 15:49" or "بتاريخ 21-01-2026 15:49")
        if (preg_match('/(?:بتاريخ\s+)?(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}\s+\d{1,2}:\d{2})/', $message, $matches)) {
            try {
                return Carbon::parse($matches[1]);
            } catch (\Exception $e) {
                // Continue to try other patterns
            }
        }

        // Try to match simple date (e.g., "21-01-2026" or "21/01/2026")
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $message, $matches)) {
            try {
                return Carbon::parse($matches[1]);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Remove country code if present
        if (str_starts_with($phoneNumber, '20')) {
            $phoneNumber = substr($phoneNumber, 2);
        }

        return $phoneNumber;
    }

    /**
     * Check if string is a valid phone number
     */
    protected function isPhoneNumber(string $phoneNumber): bool
    {
        return preg_match('/^0\d{9,10}$/', $phoneNumber) === 1;
    }

    /**
     * Get debug information for transaction detection
     */
    public function getDebugInfo(string $message, string $sender, ?TextPaymentGatewayDevice $device = null): array
    {
        $debugInfo = [
            'message_preview' => substr($message, 0, 200),
            'sender' => $sender,
            'reasons' => [],
        ];

        $phoneNumber = $this->extractPhoneNumber($message);
        $referenceNumber = $this->extractReferenceNumber($message);
        $amount = $this->extractAmount($message);
        $balance = $this->extractBalance($message);

        if (!$phoneNumber && !$referenceNumber) {
            $debugInfo['reasons'][] = 'No phone number or reference number found';
        }

        if (!$amount) {
            $debugInfo['reasons'][] = 'No amount found';
        }

        if (!$balance && $phoneNumber && !$referenceNumber) {
            $debugInfo['reasons'][] = 'No balance found (REQUIRED for wallet transactions)';
        }

        $debugInfo['extracted'] = [
            'phone_number' => $phoneNumber,
            'reference_number' => $referenceNumber,
            'amount' => $amount,
            'balance' => $balance,
        ];

        return $debugInfo;
    }
}
