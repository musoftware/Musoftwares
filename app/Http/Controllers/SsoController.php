<?php

namespace App\Http\Controllers;

use App\Models\SsoToken;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SsoController extends Controller
{
    /**
     * Redirect the user to the target system with an SSO token.
     */
    public function redirect(Request $request, $system)
    {
        // Handle /sso/redirect/toolsys matching by checking if system segment is 'redirect'
        if ($system === 'redirect') {
            // Fetch next segment or default to toolsys
            $system = $request->segment(3) ?: 'toolsys';
        }

        // Must be logged in, except for toolsys which supports guest/free tools
        if (! Auth::check()) {
            if ($system === 'toolsys') {
                $targetUrl = config('services.toolsys.url', 'https://tools.musoftwares.com');
                return redirect()->away(rtrim($targetUrl, '/') . '/tools');
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Optional: Check if user has an active subscription for the system
        // We will assume basic validation is handled by middleware or let them through and ERP will block if needed.

        // Generate a secure one-time token
        $token = Str::random(64);

        SsoToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'target_system' => $system,
            'expires_at' => now()->addSeconds(60), // Valid for 60 seconds
        ]);

        // Redirect based on the target system
        $targetUrl = '';
        if ($system === 'erp') {
            $targetUrl = config('services.erp.url').'/sso/callback';
        } elseif ($system === 'crm') {
            $targetUrl = config('services.crm.url').'/sso/callback';
        } elseif ($system === 'affsys') {
            $targetUrl = config('services.affsys.url').'/sso/callback';
        } elseif ($system === 'bookingsys') {
            $targetUrl = config('services.bookingsys.url').'/sso/callback';
        } elseif ($system === 'goldsaversys') {
            $targetUrl = config('services.goldsaversys.url').'/sso/callback';
        } elseif ($system === 'investorsys') {
            $targetUrl = config('services.investorsys.url').'/sso/callback';
        } elseif ($system === 'toolsys') {
            $targetUrl = config('services.toolsys.url').'/sso/callback';
        } else {
            abort(404, 'System not found');
        }

        return Inertia::location($targetUrl.'?token='.$token);
    }

    /**
     * Verify the token via server-to-server API call.
     *
     * Returns the user, the target system, and the user's active subscription
     * for that system so the satellite app can route correctly without a second API call.
     */
    public function verify(Request $request)
    {
        $tokenString = $request->input('token');

        if (! $tokenString) {
            return response()->json(['error' => 'Token is missing'], 400);
        }

        $ssoToken = SsoToken::where('token', $tokenString)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $ssoToken) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Mark as used
        $ssoToken->update(['used_at' => now()]);

        $user = $ssoToken->user;
        $system = $ssoToken->target_system;

        // Fetch the user's subscription for the target system (e.g. 'erp', 'goldsaversys')
        $subscriptionPrefix = config("saas.system_to_module.{$system}", $system);

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('object', 'like', $subscriptionPrefix.'%')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('expires_at', 'desc')
            ->first(['object', 'status', 'expires_at']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'currency_id' => $user->currency_id,
            ],
            'system' => $system,
            'subscription' => $subscription ? [
                'object' => $subscription->object,
                'status' => $subscription->status,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
