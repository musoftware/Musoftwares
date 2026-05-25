<?php

namespace App\Services\Tools;

use Modules\Tools\Models\ToolReseller;
use Modules\Tools\Models\ToolResellerUser;

class AdminResellerService
{
    public function createReseller(array $data): void
    {
        if (ToolReseller::where('user_id', $data['user_id'])->exists()) {
            throw new \Exception('This user already has a reseller account.');
        }

        ToolReseller::create([
            ...$data,
            'token'   => ToolReseller::generateToken(),
            'balance' => 0,
            'status'  => 'active',
        ]);
    }

    public function updateReseller(ToolReseller $reseller, array $data): void
    {
        if ($data['status'] === 'suspended' && $reseller->status !== 'suspended') {
            $reseller->suspend(auto: false);
        } elseif ($data['status'] === 'active' && $reseller->status !== 'active') {
            $reseller->activate();
        }

        $reseller->update($data);
    }

    public function deleteReseller(ToolReseller $reseller): void
    {
        $reseller->update(['status' => 'inactive']);
    }

    public function adjustBalance(ToolReseller $reseller, array $data): void
    {
        if ($data['type'] === 'manual_debit') {
            $reseller->deductBalance(
                amount:      $data['amount'],
                description: $data['description'] ?? 'Manual debit by admin',
                type:        'manual_debit',
            );
        } else {
            $reseller->creditBalance(
                amount:      $data['amount'],
                description: $data['description'] ?? 'Admin top-up',
                type:        $data['type'],
            );
        }
    }

    public function suspendUser(int $resellerId, int $userId): void
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->update(['status' => 'suspended']);
    }

    public function activateUser(int $resellerId, int $userId): void
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->update(['status' => 'active']);
    }

    public function clearSharingFlag(int $resellerId, int $userId): void
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->clearSharingFlag();
    }

    public function toggleSharingCheck(int $resellerId, int $userId): string
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->update(['sharing_check_enabled' => !$ru->sharing_check_enabled]);

        return $ru->sharing_check_enabled ? 'disabled' : 'enabled';
    }
}
