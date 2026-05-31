<?php

namespace Modules\SmsPaymentGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\SmsPaymentGateway\Models\SmsGatewayApiKey;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request authenticated via SMS Gateway API keys.
     *
     * Sets on the request:
     *  - smsGatewayApiKey: The SmsGatewayApiKey model
     *  - smsGatewayKeyType: 'publishable' | 'secret'
     *  - smsGatewayIsTest: bool
     *  - smsGatewayUser: The user who owns the key
     *
     * @param string|null $requiredKeyType  'secret' to require sk_ keys, null to allow both
     */
    public function handle(Request $request, Closure $next, ?string $requiredKeyType = null): Response
    {
        $rawKey = $this->extractKey($request);

        if (!$rawKey) {
            return response()->json([
                'error' => [
                    'type' => 'authentication_error',
                    'message' => __('sms_gateway.api_key_missing'),
                ],
            ], 401);
        }

        $keyType = SmsGatewayApiKey::determineKeyType($rawKey);

        if (!$keyType) {
            return response()->json([
                'error' => [
                    'type' => 'authentication_error',
                    'message' => __('sms_gateway.api_key_invalid_format'),
                ],
            ], 401);
        }

        // Enforce required key type if specified
        if ($requiredKeyType && $keyType !== $requiredKeyType) {
            return response()->json([
                'error' => [
                    'type' => 'authentication_error',
                    'message' => __('sms_gateway.api_key_type_required', ['type' => $requiredKeyType]),
                ],
            ], 403);
        }

        // Look up the key
        $apiKey = $keyType === 'publishable'
            ? SmsGatewayApiKey::findByPublishableKey($rawKey)
            : SmsGatewayApiKey::findBySecretKey($rawKey);

        if (!$apiKey) {
            return response()->json([
                'error' => [
                    'type' => 'authentication_error',
                    'message' => __('sms_gateway.api_key_invalid'),
                ],
            ], 401);
        }

        // Load user
        $user = $apiKey->user;
        if (!$user) {
            return response()->json([
                'error' => [
                    'type' => 'authentication_error',
                    'message' => __('sms_gateway.api_key_user_not_found'),
                ],
            ], 401);
        }

        // Touch last used (fire-and-forget, don't block request)
        $apiKey->touchLastUsed();

        // Set request attributes for downstream controllers
        $request->merge([
            'smsGatewayApiKey' => $apiKey,
            'smsGatewayKeyType' => $keyType,
            'smsGatewayIsTest' => $apiKey->is_test,
            'smsGatewayUser' => $user,
        ]);

        // Also set the authenticated user so Auth::user() works in controllers
        auth()->setUser($user);

        return $next($request);
    }

    /**
     * Extract the API key from the Authorization header or query parameter.
     */
    protected function extractKey(Request $request): ?string
    {
        // Try Authorization: Bearer xxx
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Fallback: query parameter (for widget polling)
        $queryKey = $request->query('key');
        if ($queryKey) {
            return $queryKey;
        }

        return null;
    }
}
