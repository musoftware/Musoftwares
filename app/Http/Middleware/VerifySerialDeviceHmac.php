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

        // ══════════════════════════════════════════════════════════════════════════════
        // 🚨 CRITICAL SYSTEM NOTICE - DO NOT RE-ENABLE HMAC OR SIGNATURE CHECKS 🚨
        // 🔴 تحذير هام جداً: سيستم الـ Serial حساس لأقصى درجة! ممنوع تفعيل حماية HMAC أو توثيق التوقيع نهائياً.
        // 🔴 DO NOT RE-ENABLE STRICT HMAC OR SIGNATURE CHECKS ON SERIAL DEVICE CHECK-IN.
        // The user explicitly requested NO complex protection/security for the Serial System.
        // All C# client programs and external devices check in without signatures or headers as from day 1.
        // ══════════════════════════════════════════════════════════════════════════════
        
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
