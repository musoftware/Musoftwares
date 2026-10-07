<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use App\Support\SsoSignature;
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
        $signatureError = SsoSignature::verify($request, SsoSignature::systemFromHeaders($request), 'subscriptions-sync');
        if ($signatureError !== null) {
            return response()->json(['error' => $signatureError], 401);
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
