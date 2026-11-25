<?php

namespace Modules\AppBuilder\Services;

use App\Models\AdminSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Which builder chats have customer messages the admin has not opened yet. Opening a chat marks it seen
 * for that admin only. Chats whose last customer message is older than builder_new_since are never "new".
 */
final class AdminChatViews
{
    public function markSeen(int $userId, int $projectId): void
    {
        DB::table('builder_admin_views')->upsert(
            [['user_id' => $userId, 'project_id' => $projectId, 'seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]],
            ['user_id', 'project_id'],
            ['seen_at', 'updated_at'],
        );
    }

    /** @return array<int, Carbon> seen time per project id */
    public function seenTimes(int $userId, array $projectIds): array
    {
        return DB::table('builder_admin_views')
            ->where('user_id', $userId)
            ->whereIn('project_id', $projectIds)
            ->pluck('seen_at', 'project_id')
            ->map(fn ($at) => Carbon::parse($at))
            ->all();
    }

    public function isNew(?CarbonInterface $lastCustomerAt, ?CarbonInterface $seenAt): bool
    {
        if ($lastCustomerAt === null || $lastCustomerAt->lt($this->since())) {
            return false;
        }

        return $seenAt === null || $lastCustomerAt->gt($seenAt);
    }

    private function since(): Carbon
    {
        return once(fn () => Carbon::parse((string) AdminSettings::GetValue('builder_new_since', '2000-01-01 00:00:00')));
    }
}
