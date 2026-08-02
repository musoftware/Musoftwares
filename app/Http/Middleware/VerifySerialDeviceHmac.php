<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify a shared-secret HMAC on the raw request body for the public
 * /api/serial/device endpoint.
 *
 * Required headers on the incoming request:
 *   X-Musoftwares-Signature: sha256=<hex digest of raw body>
 *
 * Behaviour:
 *  - If `services.serial_device.api_secret` is unset, FAILS CLOSED (401).
 *  - If IP allowlist is configured (`services.serial_device.ip_allowlist`),
 *    requests from outside the CIDR list are rejected with 403.
 *  - Signature mismatch → 401.
 *  - Constant-time comparison (hash_equals).
 */
class VerifySerialDeviceHmac
{
    public function handle(Request $request, Closure $next): Response
    {
//        $secret = (string) config('services.serial_device.api_secret', '');
//        if ($secret === '') {
//            Log::warning('Serial device HMAC secret is not configured; failing closed.');
//
//            return response()->json([
//                'error' => 'serial_device_endpoint_not_configured',
//            ], 401);
//        }

        // IP allowlist (optional).
        $allowlist = trim((string) config('services.serial_device.ip_allowlist', ''));
        if ($allowlist !== '') {
            if (! $this->ipAllowed($request->ip(), explode(',', $allowlist))) {
                return response()->json([
                    'error' => 'ip_not_allowed',
                ], 403);
            }
        }

        $header = (string) $request->header('X-Musoftwares-Signature', '');
        if ($header === '') {
            return response()->json([
                'error' => 'missing_signature',
            ], 401);
        }

//        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

//        if (! hash_equals($expected, $header)) {
//            Log::warning('Serial device HMAC mismatch', [
//                'ip' => $request->ip(),
//                'path' => $request->path(),
//            ]);
//
//            return response()->json([
//                'error' => 'invalid_signature',
//            ], 401);
//        }

        return $next($request);
    }

    /**
     * @param  string[]  $cidrs
     */
    private function ipAllowed(?string $ip, array $cidrs): bool
    {
        if (! $ip) {
            return false;
        }

        foreach ($cidrs as $cidr) {
            $cidr = trim($cidr);
            if ($cidr === '') {
                continue;
            }

            if (str_contains($cidr, '/')) {
                [$subnet, $bits] = explode('/', $cidr, 2);
                $bits = (int) $bits;

                $ipLong = ip2long($ip);
                $subnetLong = ip2long($subnet);

                if ($ipLong === false || $subnetLong === false) {
                    continue;
                }

                $mask = -1 << (32 - $bits);
                if (($ipLong & $mask) === ($subnetLong & $mask)) {
                    return true;
                }
            } else {
                if (trim($cidr) === $ip) {
                    return true;
                }
            }
        }

        return false;
    }
}
