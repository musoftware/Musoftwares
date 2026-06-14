<?php

namespace App\Services;

/**
 * HMAC signatures for /payment/instapay links so amounts + user_id cannot be forged.
 */
class InstapayPayLinkService
{
    public static function secret(): string
    {
        $s = config('services.instapay_pay_link.secret');

        return (is_string($s) && $s !== '') ? $s : (string) config('app.key');
    }

    public static function normAmount($value): string
    {
        return number_format(round((float) $value, 2), 2, '.', '');
    }

    public static function payloadMessage(int $userId, string $amount, string $balanceEgp, string $youWillReceive): string
    {
        return "{$userId}|{$amount}|{$balanceEgp}|{$youWillReceive}";
    }

    public static function sign(int $userId, string $amount, string $balanceEgp, string $youWillReceive): string
    {
        return hash_hmac(
            'sha256',
            self::payloadMessage($userId, $amount, $balanceEgp, $youWillReceive),
            self::secret()
        );
    }

    public static function verify(int $userId, string $amount, string $balanceEgp, string $youWillReceive, string $providedSig): bool
    {
        if ($providedSig === '' || !preg_match('/^[a-f0-9]{64}$/', $providedSig)) {
            return false;
        }

        $expected = self::sign($userId, $amount, $balanceEgp, $youWillReceive);

        return hash_equals($expected, $providedSig);
    }

    public static function buildSignedUrl(int $userId, float $amountToPay, float $balanceEgp, float $youWillReceive): string
    {
        $a = self::normAmount($amountToPay);
        $b = self::normAmount($balanceEgp);
        $y = self::normAmount($youWillReceive);
        $sig = self::sign($userId, $a, $b, $y);

        return route('payment.instapay') . '?' . http_build_query([
            'amount' => $a,
            'balance_egp' => $b,
            'you_will_receive' => $y,
            'user_id' => $userId,
            'sig' => $sig,
        ]);
    }
}
