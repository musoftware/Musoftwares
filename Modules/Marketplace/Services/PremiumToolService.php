<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PremiumToolService
{
    /**
     * Execute AI tool and record daily quota usage.
     */
    public function executeTool(User $user, string $toolSlug, array $inputParams): array
    {
        $today = now('Africa/Cairo')->toDateString();

        // Check daily usage quota (default 20 requests per day)
        $todayUsageCount = DB::table('premium_tool_usages')
            ->where('user_id', $user->id)
            ->where('tool_slug', $toolSlug)
            ->whereDate('created_at', $today)
            ->count();

        $dailyLimit = 20;
        if ($todayUsageCount >= $dailyLimit) {
            throw new Exception("Daily usage quota limit reached for tool '{$toolSlug}' ({$dailyLimit}/day).");
        }

        // Record usage
        DB::table('premium_tool_usages')->insert([
            'user_id' => $user->id,
            'tool_slug' => $toolSlug,
            'input_data' => json_encode($inputParams),
            'created_at' => now('Africa/Cairo'),
        ]);

        return [
            'success' => true,
            'tool_slug' => $toolSlug,
            'result' => 'Generated output for ' . ($inputParams['prompt'] ?? 'task'),
            'usage_remaining' => $dailyLimit - ($todayUsageCount + 1),
        ];
    }
}
