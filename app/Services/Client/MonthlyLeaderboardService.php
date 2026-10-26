<?php

namespace App\Services\Client;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clients ranked by the loyalty points they earned this calendar month.
 *
 * Privacy: other clients are never identified. The output carries only a position, a tier badge and
 * a points total for them; no ids, names, e-mails or avatars leave this class. Staff accounts are left out.
 */
class MonthlyLeaderboardService
{
    private const ADMIN_ROLES = ['super_admin', 'admin', 'superadmin', 'Admin'];

    /** @var Collection<int, object{user_id: int, points: int}>|null */
    private ?Collection $standings = null;

    /**
     * @return array{month: string, participants: int, me: array{position: int|null, points: int, top_percent: int|null},
     *               top: array<int, array{position: int, points: int, tier: string, is_me: bool}>}
     */
    public function forUser(User $user, int $limit = 10): array
    {
        $standings = $this->standings();
        $index = $standings->search(fn ($row) => (int) $row->user_id === (int) $user->id);
        $position = $index === false ? null : $index + 1;
        $participants = $standings->count();

        $tiers = DB::table('users')->whereIn('id', $standings->take($limit)->pluck('user_id'))->pluck('tier', 'id');

        return [
            'month' => now()->startOfMonth()->toDateString(),
            'participants' => $participants,
            'me' => [
                'position' => $position,
                'points' => $position ? (int) $standings[$index]->points : 0,
                'top_percent' => $position ? max(1, (int) ceil($position / $participants * 100)) : null,
            ],
            'top' => $standings->take($limit)->values()->map(fn ($row, int $i) => [
                'position' => $i + 1,
                'points' => (int) $row->points,
                'tier' => strtolower((string) ($tiers[$row->user_id] ?? 'bronze')) ?: 'bronze',
                'is_me' => (int) $row->user_id === (int) $user->id,
            ])->all(),
        ];
    }

    /** Positive points earned since the start of the month, highest first; ties go to whoever got there first. */
    private function standings(): Collection
    {
        return $this->standings ??= DB::table('loyalty_point_transactions')
            ->where('points', '>', 0)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereNotIn('user_id', $this->staffIds())
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(points) as points, MAX(created_at) as last_earned_at')
            ->orderByDesc('points')
            ->orderBy('last_earned_at')
            ->get();
    }

    /** @return array<int, int> */
    private function staffIds(): array
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
            return [];
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('roles.name', self::ADMIN_ROLES)
            ->pluck('model_has_roles.model_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
