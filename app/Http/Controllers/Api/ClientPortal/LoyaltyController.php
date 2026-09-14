<?php

namespace App\Http\Controllers\Api\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyReward;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * Get loyalty dashboard overview (points balance, tier, recent transactions).
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $summary = $this->loyaltyService->getUserSummary($user);

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Get rewards catalog.
     */
    public function rewardsCatalog(): JsonResponse
    {
        $rewards = $this->loyaltyService->getActiveRewards();

        return response()->json([
            'status' => 'success',
            'data' => $rewards,
        ]);
    }

    /**
     * Redeem points for a specific reward.
     */
    public function redeem(Request $request, LoyaltyReward $reward): JsonResponse
    {
        $user = $request->user();

        try {
            $redemption = $this->loyaltyService->redeemReward($user, $reward);

            return response()->json([
                'status' => 'success',
                'message' => 'Reward redeemed successfully.',
                'data' => [
                    'redemption' => $redemption,
                    'new_points_balance' => $user->fresh()->loyalty_points_balance,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete a profile step and award 50 points if reached 100%.
     */
    public function completeProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->profile_completion_percentage = 100;
        $user->save();

        $transaction = $this->loyaltyService->awardPoints($user, 'profile_completed');

        return response()->json([
            'status' => 'success',
            'message' => $transaction ? 'Profile 100% completed! 50 points awarded.' : 'Profile updated.',
            'data' => [
                'points_awarded' => $transaction ? 50 : 0,
                'current_balance' => $user->fresh()->loyalty_points_balance,
            ],
        ]);
    }
}
