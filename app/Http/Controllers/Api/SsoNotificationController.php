<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\GoldSystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SsoNotificationController extends Controller
{
    /**
     * Send a notification to a user via SSO integration.
     */
    public function notify(Request $request): JsonResponse
    {
        $secret = (string) config('services.goldsaversys.shared_secret', '');

        $signature = $request->header('X-GoldSaver-Signature');
        $timestamp = $request->header('X-GoldSaver-Timestamp');
        $system = $request->header('X-GoldSaver-System');

        if (! $signature || ! $timestamp || ! $system) {
            return response()->json(['error' => 'missing_signature_headers'], 401);
        }

        // Prevent replay attacks (allow 5 minute clock drift)
        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return response()->json(['error' => 'signature_expired'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp.'.sso-notify', $secret);
        if (! hash_equals($expected, $signature)) {
            Log::warning('SSO Notification signature mismatch', [
                'ip' => $request->ip(),
                'received' => $signature,
                'expected' => $expected,
            ]);

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $request->validate([
            'email' => 'required_without:monolith_user_id|string|email',
            'monolith_user_id' => 'required_without:email|integer',
            'title' => 'required|string|max:200',
            'message' => 'required|string',
            'channels' => 'required|array',
            'channels.*' => 'string|in:mail,sms,whatsapp,fcm',
        ]);

        $user = null;
        if ($request->has('monolith_user_id')) {
            $user = User::find($request->input('monolith_user_id'));
        }
        if (! $user && $request->has('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        if (! $user) {
            return response()->json(['error' => 'user_not_found'], 404);
        }

        $user->notify(new GoldSystemNotification(
            title: $request->input('title'),
            messageContent: $request->input('message'),
            channels: $request->input('channels')
        ));

        return response()->json([
            'success' => true,
            'message' => 'Notification queued successfully.',
        ]);
    }
}
