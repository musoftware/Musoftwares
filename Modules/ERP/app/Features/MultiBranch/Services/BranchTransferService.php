<?php

namespace Modules\ERP\app\Features\MultiBranch\Services;

use Modules\ERP\Models\BranchTransfer;
use Illuminate\Support\Facades\DB;

class BranchTransferService
{
    public function createTransfer(int $tenantId, int $fromBranchId, int $toBranchId, string $type, int $userId): BranchTransfer
    {
        return BranchTransfer::create([
            'tenant_id' => $tenantId,
            'from_branch_id' => $fromBranchId,
            'to_branch_id' => $toBranchId,
            'type' => $type,
            'status' => 'pending',
            'requested_by' => $userId,
        ]);
    }

    public function approveTransfer(BranchTransfer $transfer, int $approverId): bool
    {
        DB::beginTransaction();
        try {
            $transfer->update([
                'status' => 'completed',
                'approved_by' => $approverId,
            ]);

            // Logic to move inventory would happen here depending on $transfer->type

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
