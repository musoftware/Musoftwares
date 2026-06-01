<?php

namespace Modules\ERP\app\Features\MultiBranch\Services;

use App\Models\TenantUsage;
use Illuminate\Support\Facades\DB;

class ERPMultiBranchLimitsService
{
    protected function getLimit(int $tenantId, string $feature, int $default = 1): ?int
    {
        $usage = TenantUsage::firstOrCreate(
            ['tenant_id' => $tenantId, 'usage_key' => $feature],
            ['used_amount' => 0, 'limit_amount' => $default, 'reset_frequency' => 'monthly']
        );
        return $usage->limit_amount;
    }

    public function getLimits(int $tenantId): array
    {
        return [
            'max_branches' => $this->getLimit($tenantId, 'max_branches', 1),
            'max_branch_employees' => $this->getLimit($tenantId, 'max_branch_employees', 5),
            'max_branch_managers' => $this->getLimit($tenantId, 'max_branch_managers', 1),
            'monthly_branch_transfers' => $this->getLimit($tenantId, 'monthly_branch_transfers', 10),
            'max_branch_integrations' => $this->getLimit($tenantId, 'max_branch_integrations', 0),
        ];
    }
    
    public function checkUsage(int $tenantId, string $feature): bool
    {
        $usage = TenantUsage::firstOrCreate(
            ['tenant_id' => $tenantId, 'usage_key' => $feature],
            ['used_amount' => 0, 'limit_amount' => 1, 'reset_frequency' => 'monthly']
        );
        return $usage->hasAvailable(1);
    }

    public function increaseUsage(int $tenantId, string $feature): void
    {
        DB::table('tenant_usages')
            ->where('tenant_id', $tenantId)
            ->where('usage_key', $feature)
            ->increment('used_amount', 1);
    }
}
