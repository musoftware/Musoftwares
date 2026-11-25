<?php

namespace App\Http\Middleware;

use App\Models\ClientSystem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Client systems sign each request: X-System-Signature = hex HMAC-SHA256 of "{timestamp}.{raw body}"
 * with their API secret. Old or future timestamps are refused so a captured request cannot be replayed later.
 */
class VerifyClientSystemSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('X-System-Key');
        $timestamp = (string) $request->header('X-System-Timestamp');
        $signature = (string) $request->header('X-System-Signature');

        if ($key === '' || ! ctype_digit($timestamp) || $signature === '') {
            return $this->refuse('missing_signature', 401);
        }
        if (abs(now()->getTimestamp() - (int) $timestamp) > (int) config('client_systems.signature_window_seconds')) {
            return $this->refuse('clock_skew', 401);
        }

        $system = ClientSystem::where('api_key', $key)->first();
        $expected = $system ? hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $system->api_secret) : '';
        if ($system === null || ! hash_equals($expected, strtolower($signature))) {
            return $this->refuse('bad_signature', 403);
        }

        $request->attributes->set('client_system', $system);

        return $next($request);
    }

    private function refuse(string $error, int $status): Response
    {
        return response()->json(['ok' => false, 'error' => $error], $status);
    }
}
