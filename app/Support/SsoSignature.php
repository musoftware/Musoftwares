<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Verifies HMAC signatures on server-to-server SSO requests.
 *
 * Fails closed: an unknown system or an empty shared secret is always rejected.
 */
final class SsoSignature
{
    public const MAX_CLOCK_DRIFT_SECONDS = 300;

    private const ALLOWED_SYSTEMS = [
        'erp',
        'crm',
        'affsys',
        'bookingsys',
        'goldsaversys',
        'investorsys',
        'toolsys',
    ];

    private const HEADER_PREFIXES = ['X-ToolSys', 'X-GoldSaver', 'X-Investor', 'X-Sso'];

    /**
     * Read the calling system name from the request headers.
     */
    public static function systemFromHeaders(Request $request): ?string
    {
        $system = self::firstHeader($request, 'System');

        return $system === null ? null : strtolower(trim($system));
    }

    /**
     * Verify the request signature. Returns null when valid, or an error code.
     *
     * The signed message is "{timestamp}.{$payload}".
     */
    public static function verify(Request $request, ?string $system, string $payload): ?string
    {
        $signature = self::firstHeader($request, 'Signature');
        $timestamp = self::firstHeader($request, 'Timestamp');

        if ($signature === null || $timestamp === null || $system === null) {
            return 'missing_signature_headers';
        }

        $secret = self::secretFor($system);
        if ($secret === '') {
            Log::warning('[SSO] Rejected request from unknown or unconfigured system', ['system' => $system, 'ip' => $request->ip()]);

            return 'invalid_signature';
        }

        if (abs(now()->timestamp - (int) $timestamp) > self::MAX_CLOCK_DRIFT_SECONDS) {
            return 'signature_expired';
        }

        if (! hash_equals(hash_hmac('sha256', $timestamp.'.'.$payload, $secret), $signature)) {
            Log::warning('[SSO] Signature mismatch', ['system' => $system, 'ip' => $request->ip()]);

            return 'invalid_signature';
        }

        return null;
    }

    /**
     * Shared secret for an allowed system, or an empty string.
     */
    private static function secretFor(string $system): string
    {
        if (! in_array($system, self::ALLOWED_SYSTEMS, true)) {
            return '';
        }

        return trim((string) config("services.{$system}.shared_secret", ''));
    }

    private static function firstHeader(Request $request, string $suffix): ?string
    {
        foreach (self::HEADER_PREFIXES as $prefix) {
            $value = $request->header($prefix.'-'.$suffix);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
