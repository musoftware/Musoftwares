<?php

namespace Modules\ERP\app\Features\MultiBranch\Services;

use App\Helpers\UsageHelper;

class ERPMultiBranchLimitsService
{
    public function getLimits(int $tenantId): array
    {
        return [
            'max_branches' => UsageHelper::getLimit($tenantId, 'max_branches'),
            'max_branch_employees' => UsageHelper::getLimit($tenantId, 'max_branch_employees'),
            'max_branch_managers' => UsageHelper::getLimit($tenantId, 'max_branch_managers'),
            'monthly_branch_transfers' => UsageHelper::getLimit($tenantId, 'monthly_branch_transfers'),
            'max_branch_integrations' => UsageHelper::getLimit($tenantId, 'max_branch_integrations'),
        ];
    }
    
    public function checkUsage(int $tenantId, string $feature): bool
    {
        return UsageHelper::canUse($tenantId, $feature);
    }
}
