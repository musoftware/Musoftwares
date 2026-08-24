<?php

namespace App\Http\Middleware;

use App\Models\PartnerClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPartnerHmac
{
    /**
     * Handle an incoming request and verify the HMAC signature.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientKey = $request->header('x-partner-key');
        $timestamp = $request->header('x-partner-timestamp');
        $signature = $request->header('x-partner-signature');

        if (!$clientKey || !$timestamp || !$signature) {
            return response()->json([
                'success' => false,
                'error' => 'Missing authentication headers (x-partner-key, x-partner-timestamp, x-partner-signature required)',
            ], 401);
        }

        // Prevent replay attacks (5 minutes tolerance window)
        $currentTimestamp = now()->timestamp;
        if (abs($currentTimestamp - (int)$timestamp) > 300) {
            return response()->json([
                'success' => false,
                'error' => 'Request timestamp expired or skewed beyond 300 seconds',
            ], 401);
        }

        /** @var PartnerClient|null $client */
        $client = PartnerClient::where('client_key', $clientKey)
            ->where('is_active', true)
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or inactive partner key',
            ], 403);
        }

        // For safe HTTP methods (GET, HEAD), the body payload is canonically empty
        $payload = $request->isMethodSafe() ? '' : (string) $request->getContent();
        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $client->client_secret);

        if (!hash_equals($expectedSignature, (string)$signature)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid HMAC signature',
            ], 403);
        }

        $request->attributes->set('partner_client', $client);

        return $next($request);
    }
}
