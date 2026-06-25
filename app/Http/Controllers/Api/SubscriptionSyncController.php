<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class SubscriptionSyncController extends Controller
{
    /**
     * Get the subscription status for a list of users and a specific module.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer',
        ]);

        $module = $request->input('module');
        $userIds = $request->input('user_ids');

        $subscriptions = UserSubscription::whereIn('user_id', $userIds)
            ->where('object', 'like', $module . '%')
            ->get(['user_id', 'object', 'status', 'expires_at'])
            ->groupBy('user_id');

        $results = [];
        foreach ($userIds as $userId) {
            if ($subscriptions->has($userId)) {
                $userSubs = $subscriptions->get($userId);
                $subsArray = [];
                foreach ($userSubs as $sub) {
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
                    ]
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
