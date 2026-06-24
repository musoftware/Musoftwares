<?php

namespace App\Http\Controllers;

use App\Models\SsoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Redirect the user to the target system with an SSO token.
     */
    public function redirect(Request $request, $system)
    {
        // Must be logged in
        if (!Auth::check()) {
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
            $targetUrl = config('services.erp.url') . '/sso/callback';
        } elseif ($system === 'crm') {
            $targetUrl = config('services.crm.url') . '/sso/callback';
        } elseif ($system === 'affsys') {
            $targetUrl = config('services.affsys.url') . '/sso/callback';
        } elseif ($system === 'bookingsys') {
            $targetUrl = config('services.bookingsys.url') . '/sso/callback';
        } elseif ($system === 'freelancesys') {
            $targetUrl = config('services.freelancesys.url') . '/sso/callback';
        } elseif ($system === 'goldsaversys') {
            $targetUrl = config('services.goldsaversys.url') . '/sso/callback';
        } else {
            abort(404, 'System not found');
        }

        return redirect()->away($targetUrl . '?token=' . $token);
    }

    /**
     * Verify the token via server-to-server API call.
     */
    public function verify(Request $request)
    {
        $tokenString = $request->input('token');

        if (!$tokenString) {
            return response()->json(['error' => 'Token is missing'], 400);
        }

        $ssoToken = SsoToken::where('token', $tokenString)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$ssoToken) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Mark as used
        $ssoToken->update(['used_at' => now()]);

        $user = $ssoToken->user;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'system' => $ssoToken->target_system
        ]);
    }
}
