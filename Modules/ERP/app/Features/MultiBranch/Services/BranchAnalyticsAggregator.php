<?php

namespace Modules\ERP\app\Features\MultiBranch\Services;

use Modules\ERP\Models\Branch;

class BranchAnalyticsAggregator
{
    public function getBranchMetrics(int $branchId): array
    {
        return [
            'revenue' => 0, // Mocked for architecture readiness
            'transfers_sent' => \Modules\ERP\Models\BranchTransfer::where('from_branch_id', $branchId)->count(),
            'transfers_received' => \Modules\ERP\Models\BranchTransfer::where('to_branch_id', $branchId)->count(),
        ];
    }

    public function getGlobalMetrics(int $tenantId): array
    {
        return [
            'total_branches' => Branch::where('tenant_id', $tenantId)->count(),
            'active_branches' => Branch::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'total_transfers' => \Modules\ERP\Models\BranchTransfer::where('tenant_id', $tenantId)->count(),
        ];
    }
}
