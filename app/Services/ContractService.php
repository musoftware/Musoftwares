<?php

namespace App\Services;

use App\Models\Contract;

class ContractService
{    public function createContract(array $data): Contract
    {
        $data['content'] = [
            'lang' => $data['lang'] ?? 'ar'
        ];
        
        return Contract::create($data);
    }

    public function updateContract(Contract $contract, array $data): void
    {
        $data['content'] = array_merge(
            $contract->content ?? [],
            ['lang' => $data['lang'] ?? 'ar']
        );

        $contract->update($data);
    }

    public function updateStatus(Contract $contract, string $status): void
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
