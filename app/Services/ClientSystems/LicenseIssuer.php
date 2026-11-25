<?php

namespace App\Services\ClientSystems;

use App\Models\ClientSystem;
use App\Models\Currency;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;

/**
 * Builds the signed license a client system stores and checks before it works.
 * Only we hold the private key, so the client side cannot make or stretch a license.
 * Token format: base64url(json) "." base64url(DER ECDSA P-256 / SHA-256 signature of the first part).
 */
final class LicenseIssuer
{
    public function issue(ClientSystem $system, CarbonInterface $now): array
    {
        $status = SystemStatus::of($system, $now);
        $payload = [
            'v' => 1,
            'system' => $system->uuid,
            'state' => $status->state,
            'issued_at' => $now->getTimestamp(),
            'valid_until' => $this->validUntil($status, $now)->getTimestamp(),
            'lock_at' => $status->state === SystemStatus::DUE ? $status->changesAt->getTimestamp() : null,
            ...$this->amountDue($status->bill?->invoice),
            'message' => $this->messages($status),
        ];

        return ['token' => $this->sign($system, $payload), 'license' => $payload];
    }

    public static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /** A locked license is already expired; others last until the state could change, at most the offline window. */
    private function validUntil(SystemStatus $status, CarbonInterface $now): CarbonInterface
    {
        if ($status->state === SystemStatus::LOCKED) {
            return $now;
        }
        $offlineLimit = $now->copy()->addHours((int) config('client_systems.offline_hours'));

        return $status->changesAt?->isBefore($offlineLimit) ? $status->changesAt : $offlineLimit;
    }

    private function amountDue(?Invoice $invoice): array
    {
        if ($invoice === null) {
            return ['amount_due' => null, 'currency' => null, 'pay_url' => null];
        }

        return [
            'amount_due' => round($invoice->unpaid_total(), 2),
            'currency' => Currency::findCached($invoice->currency_id)?->currency,
            'pay_url' => URL::signedRoute('guest.invoices.show', ['invoice' => $invoice->id]),
        ];
    }

    private function messages(SystemStatus $status): array
    {
        $key = ['active' => null, 'due' => 'client_systems.license_due', 'locked' => 'client_systems.license_locked'][$status->state];
        if ($key === null) {
            return ['ar' => '', 'en' => ''];
        }
        $date = $status->changesAt?->timezone('Africa/Cairo')->format('Y-m-d');

        return ['ar' => __($key, ['date' => $date], 'ar'), 'en' => __($key, ['date' => $date], 'en')];
    }

    private function sign(ClientSystem $system, array $payload): string
    {
        $body = self::base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $body.'.'.self::base64Url(LicenseKeys::sign($body, $system->signing_secret_key));
    }
}
