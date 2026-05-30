<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CRMWhatsAppCampaignLimitsService
{
    protected array $defaultLimits = [
        'monthly_whatsapp_campaign_messages' => 5000,
        'max_active_campaigns'               => 5,
        'max_audience_segments'              => 10,
        'max_campaign_sequences'             => 3,
        'max_connected_whatsapp_accounts'    => 2,
    ];

    public function canUse(int $workspaceId, string $limitKey, int $amount = 1): bool
    {
        $limit = $this->getLimit($workspaceId, $limitKey);

        if ($limit === null) {
            $limit = $this->defaultLimits[$limitKey] ?? 0;
        }

        if ($limit === -1) return true;
        if ($limit === 0) return false;

        $currentUsage = $this->getCurrentUsage($workspaceId, $limitKey);
        return ($currentUsage + $amount) <= $limit;
    }

    public function increaseUsage(int $workspaceId, string $limitKey, int $amount = 1): void
    {
        DB::table('tenant_usages')->updateOrInsert(
            ['tenant_id' => $workspaceId, 'usage_key' => $limitKey],
            [
                'used_amount'     => DB::raw("used_amount + {$amount}"),
                'reset_frequency' => $this->getResetFrequency($limitKey),
                'updated_at'      => now(),
            ]
        );

        Cache::forget("crm_wa_campaign_usage_{$workspaceId}_{$limitKey}");
    }

    public function getRemainingUsage(int $workspaceId, string $limitKey): int
    {
        $limit = $this->getLimit($workspaceId, $limitKey) ?? ($this->defaultLimits[$limitKey] ?? 0);

        if ($limit === -1) return PHP_INT_MAX;

        $used = $this->getCurrentUsage($workspaceId, $limitKey);
        return max(0, $limit - $used);
    }

    public function getUsageSummary(int $workspaceId): array
    {
        $summary = [];

        foreach ($this->defaultLimits as $key => $defaultLimit) {
            $limit = $this->getLimit($workspaceId, $key) ?? $defaultLimit;
            $used = $this->getCurrentUsage($workspaceId, $key);

            $summary[$key] = [
                'limit'      => $limit,
                'used'       => $used,
                'remaining'  => $limit === -1 ? 'unlimited' : max(0, $limit - $used),
                'percentage' => $limit > 0 ? round(($used / $limit) * 100, 1) : 0,
            ];
        }

        return $summary;
    }

    protected function getLimit(int $workspaceId, string $limitKey): ?int
    {
        return DB::table('tenant_usages')
            ->where('tenant_id', $workspaceId)
            ->where('usage_key', $limitKey)
            ->value('limit_amount');
    }

    protected function getCurrentUsage(int $workspaceId, string $limitKey): int
    {
        return Cache::remember("crm_wa_campaign_usage_{$workspaceId}_{$limitKey}", 60, function () use ($workspaceId, $limitKey) {
            $tracked = DB::table('tenant_usages')
                ->where('tenant_id', $workspaceId)
                ->where('usage_key', $limitKey)
                ->value('used_amount');

            if ($tracked !== null) return (int) $tracked;

            return match ($limitKey) {
                'max_active_campaigns' => \Modules\CRM\Models\WhatsAppCampaign::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)->whereIn('status', ['running', 'paused', 'scheduled'])->count(),
                'max_audience_segments' => \Modules\CRM\Models\WhatsAppCampaignAudience::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)->count(),
                'max_campaign_sequences' => \Modules\CRM\Models\WhatsAppCampaignSequence::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)->where('is_active', true)->count(),
                default => 0,
            };
        });
    }

    protected function getResetFrequency(string $limitKey): string
    {
        return match ($limitKey) {
            'monthly_whatsapp_campaign_messages' => 'monthly',
            default                              => 'never',
        };
    }

    public function resetMonthlyUsage(): int
    {
        return DB::table('tenant_usages')
            ->where('reset_frequency', 'monthly')
            ->where('usage_key', 'like', '%campaign%')
            ->where('last_reset_at', '<', now()->startOfMonth())
            ->update([
                'used_amount'   => 0,
                'last_reset_at' => now(),
                'updated_at'    => now(),
            ]);
    }
}
