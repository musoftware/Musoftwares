<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CRMWhatsAppLimitsService
{
    /**
     * Default limits for the WhatsApp inbox add-on.
     */
    protected array $defaultLimits = [
        'max_connected_whatsapp_accounts' => 1,
        'monthly_whatsapp_messages'       => 1000,
        'max_team_members'                => 3,
        'max_active_conversations'        => 100,
        'max_automation_rules'            => 5,
    ];

    /**
     * Check if a workspace can perform an action based on their limits.
     */
    public function canUse(int $workspaceId, string $limitKey, int $amount = 1): bool
    {
        $limit = $this->getLimit($workspaceId, $limitKey);

        // Null limit = not configured, use default
        if ($limit === null) {
            $limit = $this->defaultLimits[$limitKey] ?? 0;
        }

        // -1 = unlimited
        if ($limit === -1) {
            return true;
        }

        if ($limit === 0) {
            return false;
        }

        $currentUsage = $this->getCurrentUsage($workspaceId, $limitKey);

        return ($currentUsage + $amount) <= $limit;
    }

    /**
     * Increase usage counter for a workspace.
     */
    public function increaseUsage(int $workspaceId, string $limitKey, int $amount = 1): void
    {
        DB::table('tenant_usages')->updateOrInsert(
            [
                'tenant_id' => $workspaceId,
                'usage_key' => $limitKey,
            ],
            [
                'used_amount'     => DB::raw("used_amount + {$amount}"),
                'reset_frequency' => $this->getResetFrequency($limitKey),
                'updated_at'      => now(),
            ]
        );

        // Clear cache
        Cache::forget("crm_wa_usage_{$workspaceId}_{$limitKey}");
    }

    /**
     * Get remaining usage for a limit key.
     */
    public function getRemainingUsage(int $workspaceId, string $limitKey): int
    {
        $limit = $this->getLimit($workspaceId, $limitKey);

        if ($limit === null) {
            $limit = $this->defaultLimits[$limitKey] ?? 0;
        }

        if ($limit === -1) {
            return PHP_INT_MAX; // Unlimited
        }

        $used = $this->getCurrentUsage($workspaceId, $limitKey);

        return max(0, $limit - $used);
    }

    /**
     * Get a complete usage summary for a workspace.
     */
    public function getUsageSummary(int $workspaceId): array
    {
        $summary = [];

        foreach ($this->defaultLimits as $key => $defaultLimit) {
            $limit = $this->getLimit($workspaceId, $key) ?? $defaultLimit;
            $used = $this->getCurrentUsage($workspaceId, $key);

            $summary[$key] = [
                'limit'     => $limit,
                'used'      => $used,
                'remaining' => $limit === -1 ? 'unlimited' : max(0, $limit - $used),
                'percentage' => $limit > 0 ? round(($used / $limit) * 100, 1) : 0,
            ];
        }

        return $summary;
    }

    /**
     * Get the configured limit for a key.
     */
    protected function getLimit(int $workspaceId, string $limitKey): ?int
    {
        $usage = DB::table('tenant_usages')
            ->where('tenant_id', $workspaceId)
            ->where('usage_key', $limitKey)
            ->first();

        return $usage?->limit_amount;
    }

    /**
     * Get the current usage for a key (cached 60s).
     */
    protected function getCurrentUsage(int $workspaceId, string $limitKey): int
    {
        return Cache::remember("crm_wa_usage_{$workspaceId}_{$limitKey}", 60, function () use ($workspaceId, $limitKey) {
            // First check the tenant_usages table for tracked amounts
            $tracked = DB::table('tenant_usages')
                ->where('tenant_id', $workspaceId)
                ->where('usage_key', $limitKey)
                ->value('used_amount');

            if ($tracked !== null) {
                return (int) $tracked;
            }

            // Fall back to dynamic counting
            return match ($limitKey) {
                'max_connected_whatsapp_accounts' => \Modules\CRM\Models\WhatsAppAccount::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)->count(),
                'max_active_conversations' => \Modules\CRM\Models\WhatsAppConversation::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)->whereIn('status', ['open', 'pending'])->count(),
                'max_automation_rules' => \Modules\CRM\Models\WhatsAppAutomationRule::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)->where('is_active', true)->count(),
                'max_team_members' => DB::table('crm_workspace_users')
                    ->where('workspace_id', $workspaceId)->where('is_active', true)->count(),
                default => 0,
            };
        });
    }

    /**
     * Get the reset frequency for a limit key.
     */
    protected function getResetFrequency(string $limitKey): string
    {
        return match ($limitKey) {
            'monthly_whatsapp_messages' => 'monthly',
            default                     => 'never',
        };
    }

    /**
     * Reset monthly usage counters (called by scheduled command).
     */
    public function resetMonthlyUsage(): int
    {
        return DB::table('tenant_usages')
            ->where('reset_frequency', 'monthly')
            ->where('usage_key', 'like', '%whatsapp%')
            ->where('last_reset_at', '<', now()->startOfMonth())
            ->update([
                'used_amount'   => 0,
                'last_reset_at' => now(),
                'updated_at'    => now(),
            ]);
    }
}
