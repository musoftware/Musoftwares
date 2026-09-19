<?php

namespace App\Http\Controllers;

use App\Models\SsoToken;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SsoController extends Controller
{
    private const SYSTEM_ALIASES = [
        'gold-saver' => 'goldsaversys',
        'gold_saver' => 'goldsaversys',
        'gold' => 'goldsaversys',
    ];

    private const SUPPORTED_SYSTEMS = [
        'erp',
        'crm',
        'affsys',
        'bookingsys',
        'goldsaversys',
        'investorsys',
        'toolsys',
    ];

    /**
     * Redirect the user to the target system with an SSO token.
     */
    public function redirect(Request $request, string $system): Response
    {
        if ($system === 'redirect') {
            $system = (string) ($request->segment(3) ?: 'toolsys');
        }

        $canonicalSystem = $this->resolveSystem($system);
        if (! $canonicalSystem) {
            abort(404, 'System not found');
        }

        // Guests attempting to access toolsys are routed to the public tools page
        if (! Auth::check()) {
            if ($canonicalSystem === 'toolsys') {
                $toolsUrl = (string) config('services.toolsys.url', 'https://tools.musoftwares.com');

                return redirect()->away(rtrim($toolsUrl, '/').'/tools');
            }

            return redirect()->route('login');
        }

        $targetCallbackUrl = $this->getTargetCallbackUrl($canonicalSystem);
        if (! $targetCallbackUrl) {
            abort(404, 'Target system URL is not configured.');
        }

        $token = $this->generateToken(Auth::id(), $canonicalSystem);

        return Inertia::location($targetCallbackUrl.'?token='.$token);
    }

    /**
     * Verify the token via server-to-server API call.
     */
    public function verify(Request $request): JsonResponse
    {
        $tokenString = $request->input('token');
        if (! $tokenString) {
            return response()->json(['error' => 'Token is missing'], 400);
        }

        $ssoToken = SsoToken::where('token', (string) $tokenString)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $ssoToken) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        $system = $this->resolveSystem((string) $ssoToken->target_system) ?? (string) $ssoToken->target_system;

        // Verify HMAC signature if configured for the target system BEFORE consuming token
        if (! $this->verifySignature($request, $system, (string) $tokenString)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        // Atomically consume token now that authenticity is verified
        $consumed = SsoToken::where('id', $ssoToken->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($consumed === 0) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        $user = $ssoToken->user;
        if (! $user) {
            return response()->json(['error' => 'User not found for token'], 404);
        }

        $subscriptionPrefix = (string) config("saas.system_to_module.{$system}", $system);
        $subscription = $this->fetchActiveSubscription($user->id, $subscriptionPrefix, $system);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'currency_id' => $user->currency_id,
            ],
            'system' => $system,
            'module' => $subscriptionPrefix,
            'subscription' => $subscription ? [
                'object' => $subscription->object,
                'status' => $subscription->status,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Resolve and normalize system identifier.
     */
    protected function resolveSystem(string $system): ?string
    {
        $normalized = strtolower(trim($system));
        $canonical = self::SYSTEM_ALIASES[$normalized] ?? $normalized;

        return in_array($canonical, self::SUPPORTED_SYSTEMS, true) ? $canonical : null;
    }

    /**
     * Get callback URL for a given system.
     */
    protected function getTargetCallbackUrl(string $system): ?string
    {
        $baseUrl = config("services.{$system}.url");
        if (empty($baseUrl)) {
            return null;
        }

        return rtrim((string) $baseUrl, '/').'/sso/callback';
    }

    /**
     * Generate single-use token.
     */
    protected function generateToken(int $userId, string $system): string
    {
        $token = Str::random(64);

        SsoToken::create([
            'user_id' => $userId,
            'token' => $token,
            'target_system' => $system,
            'expires_at' => now()->addSeconds(60),
        ]);

        return $token;
    }

    /**
     * Verify HMAC signature from incoming server-to-server request.
     */
    protected function verifySignature(Request $request, string $system, string $token): bool
    {
        $signature = $request->header('X-GoldSaver-Signature')
            ?? $request->header('X-ToolSys-Signature')
            ?? $request->header('X-Investor-Signature')
            ?? $request->header('X-Sso-Signature');

        $timestamp = $request->header('X-GoldSaver-Timestamp')
            ?? $request->header('X-ToolSys-Timestamp')
            ?? $request->header('X-Investor-Timestamp')
            ?? $request->header('X-Sso-Timestamp');

        $secret = (string) config("services.{$system}.shared_secret", '');

        // If secret is configured and signature headers are sent, verify strictly
        if ($secret !== '' && $signature) {
            if (! $timestamp || abs(now()->timestamp - (int) $timestamp) > 300) {
                Log::warning('[SSO] Verification timestamp expired or missing for system: '.$system);

                return false;
            }

            $expected = hash_hmac('sha256', $timestamp.'.'.$token, $secret);
            if (! hash_equals($expected, $signature)) {
                Log::warning('[SSO] Signature mismatch for system: '.$system, [
                    'received' => $signature,
                    'expected' => $expected,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Query active subscription strictly for the target system.
     */
    protected function fetchActiveSubscription(int $userId, string $subscriptionPrefix, string $system): ?UserSubscription
    {
        return UserSubscription::where('user_id', $userId)
            ->where(function ($query) use ($subscriptionPrefix, $system) {
                $query->where('object', 'like', $subscriptionPrefix.'%');
                if ($system === 'goldsaversys') {
                    $query->orWhere('object', 'like', 'gold%');
                }
            })
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('expires_at', 'desc')
            ->first(['object', 'status', 'expires_at']);
    }
}
