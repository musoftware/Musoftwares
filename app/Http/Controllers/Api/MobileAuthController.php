<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class MobileAuthController extends Controller
{
    /**
     * Send OTP to phone number.
     * In production, integrate with an SMS gateway (e.g. Vonage, Twilio, Infobip).
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:7|max:20',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $request->phone);
        $otp   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        Cache::put("otp:{$phone}", $otp, now()->addMinutes(10));

        // TODO: Send OTP via SMS gateway
        // In development, log to laravel.log for testing
        \Illuminate\Support\Facades\Log::info("OTP for {$phone}: {$otp}");

        return response()->json([
            'message' => __('freelance.otp_sent'),
            'expires_in' => 600,
            // In development only — remove in production:
            'otp_debug' => app()->isLocal() ? $otp : null,
        ]);
    }

    /**
     * Verify OTP and issue Sanctum token.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp'   => 'required|string|size:6',
        ]);

        $phone      = preg_replace('/[^0-9+]/', '', $request->phone);
        $cachedOtp  = Cache::get("otp:{$phone}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'message' => __('freelance.otp_invalid'),
            ], 422);
        }

        // OTP valid — clear it
        Cache::forget("otp:{$phone}");

        // Find or create user by phone
        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name'     => 'User',
                'password' => bcrypt(Str::random(32)),
                'email'    => null,
            ]
        );

        // Load currency relation
        $user->load('currency');

        // Issue Sanctum token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'             => $user->id,
                'name'           => $user->name,
                'phone'          => $user->phone,
                'email'          => $user->email,
                'role'           => $user->freelance_role ?? null,
                'currency'       => $user->currency ? [
                    'id'       => $user->currency->id,
                    'currency' => $user->currency->currency,
                    'symbol'   => $user->currency->symbol,
                ] : null,
                'points_balance' => $user->points_balance ?? 0,
                'created_at'     => $user->created_at?->toDateString(),
            ],
        ]);
    }

    /**
     * Update profile (name, email, role).
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'role'  => 'sometimes|in:client,freelancer',
        ]);

        $user = $request->user();

        if (isset($validated['name']))  { $user->name  = $validated['name']; }
        if (isset($validated['email'])) { $user->email = $validated['email']; }
        if (isset($validated['role']))  { $user->freelance_role = $validated['role']; }

        $user->save();
        $user->load('currency');

        return response()->json($this->formatUser($user));
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('currency');
        return response()->json($this->formatUser($user));
    }

    private function formatUser(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'phone'          => $user->phone,
            'email'          => $user->email,
            'role'           => $user->freelance_role ?? null,
            'currency'       => $user->currency ? [
                'id'       => $user->currency->id,
                'currency' => $user->currency->currency,
                'symbol'   => $user->currency->symbol,
            ] : null,
            'points_balance' => $user->points_balance ?? 0,
            'created_at'     => $user->created_at?->toDateString(),
        ];
    }
}
