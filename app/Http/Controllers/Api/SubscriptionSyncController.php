<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionSyncController extends Controller
{
    /**
     * Get the subscription status for a list of users and a specific module.
     *
     * @return JsonResponse
     */
    public function sync(Request $request)
    {
        $system = $request->header('X-ToolSys-System')
            ?? $request->header('X-Sso-System')
            ?? $request->header('X-Investor-System')
            ?? $request->header('X-GoldSaver-System');

        $signature = $request->header('X-ToolSys-Signature')
            ?? $request->header('X-Sso-Signature')
            ?? $request->header('X-Investor-Signature')
            ?? $request->header('X-GoldSaver-Signature');

        $timestamp = $request->header('X-ToolSys-Timestamp')
            ?? $request->header('X-Sso-Timestamp')
            ?? $request->header('X-Investor-Timestamp')
            ?? $request->header('X-GoldSaver-Timestamp');

        $secret = $system ? (string) config("services.{$system}.shared_secret", '') : '';

        if ($secret !== '') {
            if (! $signature || ! $timestamp || ! $system) {
                return response()->json(['error' => 'missing_signature_headers'], 401);
            }

            // Prevent replay attacks (allow 5 minute clock drift)
            if (abs(now()->timestamp - (int) $timestamp) > 300) {
                return response()->json(['error' => 'signature_expired'], 401);
            }

            $expected = hash_hmac('sha256', $timestamp.'.subscriptions-sync', $secret);
            if (! hash_equals($expected, $signature)) {
                \Illuminate\Support\Facades\Log::warning('SSO Subscription sync signature mismatch', [
                    'ip' => $request->ip(),
                    'received' => $signature,
                    'expected' => $expected,
                ]);

                return response()->json(['error' => 'invalid_signature'], 401);
            }
        }

        $request->validate([
            'module' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer',
        ]);

        $module = $request->input('module');
        $userIds = $request->input('user_ids');

        $subscriptionPrefix = config("saas.system_to_module.{$module}", $module);

        $toolKeys = collect(config('tools', []))->flatMap(fn ($t, $g) => ['tool-'.$g, 'tool-'.($t['slug'] ?? ''), $g, $t['slug'] ?? ''])->filter()->toArray();
        $toolKeys[] = $subscriptionPrefix;

        $subscriptions = UserSubscription::whereIn('user_id', $userIds)
            ->where(function ($query) use ($subscriptionPrefix, $toolKeys) {
                $query->where('object', 'like', $subscriptionPrefix.'%')
                    ->orWhereIn('object', $toolKeys);
            })
            ->get(['user_id', 'object', 'status', 'expires_at'])
            ->groupBy('user_id');

        $results = [];
        foreach ($userIds as $userId) {
            if ($subscriptions->has($userId)) {
                $userSubs = $subscriptions->get($userId);
                $subsArray = [];
                foreach ($userSubs as $sub) {
                    if (isset($subsArray[$sub->object])) {
                        $existing = $subsArray[$sub->object];
                        // If new one is active and existing is not, overwrite
                        if ($sub->status === 'active' && $existing['status'] !== 'active') {
                            // Proceed to overwrite
                        } elseif ($sub->status === $existing['status']) {
                            // If same status, keep the one that expires later
                            if ($sub->expires_at > $existing['expires_at']) {
                                // Proceed to overwrite
                            } else {
                                continue;
                            }
                        } else {
                            // Existing is active, new is not, keep existing
                            continue;
                        }
                    }

                    $subsArray[$sub->object] = [
                        'status' => $sub->status,
                        'expires_at' => $sub->expires_at,
                    ];
                }
                $results[$userId] = $subsArray;
            } else {
                // If they don't have a record, they are effectively expired or cancelled
                $results[$userId] = [
                    $module => [
                        'status' => 'cancelled',
                        'expires_at' => null,
                    ],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
