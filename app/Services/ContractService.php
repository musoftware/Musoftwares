<?php

namespace App\Services;

use App\Models\Contract;

class ContractService
{    public function updateStatus(Contract $contract, string $status): void
    {
        $contract->status = $status;
        
        // If marking as signed, automatically stamp the time if not already
        if ($status === 'signed' && is_null($contract->signed_at)) {
            $contract->signed_at = now();
        }

        $contract->save();
    }
    public function deleteContract(Contract $contract): void
    {
        $contract->delete();
    }
}
