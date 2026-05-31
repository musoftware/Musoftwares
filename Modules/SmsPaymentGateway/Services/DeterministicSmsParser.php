<?php

namespace Modules\SmsPaymentGateway\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 1. Provider Detector & Arabic Telecom Parser
 */
class DeterministicSmsParser
{
    /**
     * Parse the SMS message deterministically.
     */
    public function parse(string $message, string $sender): ?array
    {
        // 1. Filter promotional/non-financial messages
        if ($this->isPromotional($message)) {
            return null;
        }

        // 2. Provider Detection
        $isInstaPay = $this->isInstaPay($message, $sender);

        // 3. Regex Extraction Pipeline
        $phoneNumber = $this->extractPhoneNumber($message);
        $referenceNumber = $this->extractReferenceNumber($message);

        // Fallback: Longest alphanumeric token if no strict reference found
        if (!$referenceNumber && !$phoneNumber) {
            $referenceNumber = $this->extractFallbackReference($message);
        }

        if (!$phoneNumber && !$referenceNumber) {
            return null; // Must have at least one identifier
        }

        // 4. Amount Extraction
        $amount = $this->extractAmount($message);
        if (!$amount || $amount <= 0) {
            return null;
        }

        // 5. Balance Extraction
        $balance = $this->extractBalance($message);

        // Wallet transactions expect a balance. InstaPay does not.
        if ($balance === null && !$isInstaPay && $phoneNumber && !$referenceNumber) {
            Log::warning('Wallet transaction missing balance, but accepting via fallback.', [
                'sender' => $sender,
                'phone' => $phoneNumber,
                'amount' => $amount
            ]);
        }

        return [
            'amount' => $amount,
            'balance' => $balance,
            'currency' => 'EGP',
            'phone_number' => $phoneNumber ? $this->normalizePhoneNumber($phoneNumber) : null,
            'reference_number' => $referenceNumber,
            'sender' => $sender,
            'sender_name' => $sender,
            'is_instapay' => $isInstaPay,
            'date' => $this->extractDate($message)?->toIso8601String(),
        ];
    }

    private function isPromotional(string $message): bool
    {
        $blacklists = [
            '/كسبت.*?كاش\s+باك/iu',
            '/كاش\s+باك/iu',
            '/cashback/iu',
            '/مبروك\s+كسبت/iu',
            '/هدية/iu',
            '/gift/iu',
        ];

        foreach ($blacklists as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        return false;
    }

    private function isInstaPay(string $message, string $sender): bool
    {
        $indicators = [
            'instapay',
            'insta pay',
            'تحويل لحظي',
            'إلى حسابك المنتهي'
        ];

        foreach ($indicators as $indicator) {
            if (mb_stripos($message, $indicator) !== false || mb_stripos($sender, $indicator) !== false) {
                return true;
            }
        }
        return false;
    }

    private function extractReferenceNumber(string $message): ?string
    {
        $patterns = [
            '/رقم\s+مرجعي\s*[:\-\s]*([a-zA-Z0-9\-]{6,})/iu',
            '/رقم\s+العملية\s*[:\-\s]*([a-zA-Z0-9\-]{6,})/iu',
            '/برقم\s*مرجعي\s*[:\-\s]*([a-zA-Z0-9\-]{6,})/iu',
            '/Transaction\s*ID\s*[:\-\s]*([a-zA-Z0-9\-]{6,})/iu',
            '/trx[_\-\s]?id\s*[:\-\s]*([a-zA-Z0-9\-]{6,})/iu',
            '/reference\s+(?:number|no\.?|#)?\s*[:\-\s]*([a-zA-Z0-9\-]{6,})/iu',
            '/ref\s*[:\-\s]+([a-zA-Z0-9\-]{6,})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function extractFallbackReference(string $message): ?string
    {
        // Extract longest alphanumeric token (min 6 chars) as fallback reference
        if (preg_match_all('/[a-zA-Z0-9\-]{6,}/', $message, $matches)) {
            $longest = '';
            foreach ($matches[0] as $token) {
                if (strlen($token) > strlen($longest) && !preg_match('/^\d{10,15}$/', $token)) {
                    $longest = $token;
                }
            }
            return $longest ?: null;
        }
        return null;
    }

    private function extractPhoneNumber(string $message): ?string
    {
        $patterns = [
            '/من\s+رقم\s+حساب\s+(\d{10,15})/iu',
            '/من\s+رقم\s+(\d{10,15})/iu',
            '/(?:from|received\s+from)[\s:]+(\d{10,15})/iu',
            '/(?:رقم\s+حساب|phone|mobile|tel)[\s:]*(\d{10,15})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function extractAmount(string $message): ?float
    {
        $patterns = [
            '/بمبلغ\s+(\d+(?:\.\d+)?)\s*ج\.?م/iu',
            '/بمبلغ\s+(\d+(?:\.\d+)?)\s*جنيه/iu',
            '/مبلغ\s+(\d+(?:\.\d+)?)\s*ج\.?م/iu',
            '/مبلغ\s+(\d+(?:\.\d+)?)\s*جنيه/iu',
            '/تم\s+إستلام\s+مبلغ\s+(\d+(?:\.\d+)?)/iu',
            '/تم\s+استلام\s+مبلغ\s+(\d+(?:\.\d+)?)/iu',
            '/تحويل\s+لحظي\s+بمبلغ\s+(\d+(?:\.\d+)?)/iu',
            '/received\s+(?:EGP|LE|جنيه)\s*(\d+(?:\.\d+)?)/iu',
            '/(?:amount|received)\s+(?:of\s+)?(?:EGP|LE)\s*(\d+(?:\.\d+)?)/iu',
            '/(\d+(?:\.\d+)?)\s+(?:EGP|LE|جنيه)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return floatval($matches[1]);
            }
        }
        return null;
    }

    private function extractBalance(string $message): ?float
    {
        $patterns = [
            '/(?:available\s+)?balance[\s:]*(\d+(?:\.\d+)?)/iu',
            '/رصيدك?.*?الحال[يى].*?(\d+(?:\.\d+)?)/iu',
            '/رصيد.*?محفظتك.*?الحال[يى].*?(\d+(?:\.\d+)?)/iu',
            '/رصيد.*?(\d+(?:\.\d+)?)\s*ج\.م/iu',
            '/الرصيد.*?(\d+(?:\.\d+)?)\s*ج\.م/iu',
            '/الرصيد[\s:]*(\d+(?:\.\d+)?)/iu',
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

    private function extractDate(string $message): ?Carbon
    {
        if (preg_match('/(?:بتاريخ\s+)?(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}\s+\d{1,2}:\d{2})/', $message, $matches)) {
            try { return Carbon::parse($matches[1]); } catch (\Exception $e) {}
        }
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $message, $matches)) {
            try { return Carbon::parse($matches[1]); } catch (\Exception $e) {}
        }
        return null;
    }

    /**
     * Egyptian Phone Normalization
     */
    public function normalizePhoneNumber(string $phone): string
    {
        // 1. remove non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // 2. remove 00 prefix
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        
        // 3. convert 20xxxxxxxxxx -> 01xxxxxxxxx
        if (str_starts_with($phone, '20') && strlen($phone) >= 12) {
            $phone = '0' . substr($phone, 2);
        }
        
        // 4. prepend 0 if missing
        if (!str_starts_with($phone, '0') && strlen($phone) == 10) {
            $phone = '0' . $phone;
        }
        
        return $phone;
    }
}
