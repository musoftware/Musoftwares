<?php

namespace App\Services;

use App\Models\User;

class KycService extends BaseService
{

    public function approveKyc(User $user, int $adminId): void
    {
        $user->update([
            'kyc_verified'    => true,
            'kyc_verified_at' => now(),
            'kyc_verified_by' => $adminId,
            'kyc_notes'       => 'KYC approved by Admin on ' . now()->format('Y-m-d H:i:s'),
        ]);

        // Mark all pending docs as approved
        $user->kycDocuments()->where('status', 'pending')->update(['status' => 'approved']);
    }

    public function rejectKyc(User $user, string $reason): void
    {
        $user->update([
            'kyc_verified'    => false,
            'kyc_verified_at' => null,
            'kyc_notes'       => 'KYC rejected: ' . $reason,
        ]);

        // Mark all pending docs as rejected
        $user->kycDocuments()->where('status', 'pending')->update([
            'status'           => 'rejected',
            'admin_notes'      => $reason
        ]);
    }
}
