<?php

namespace App\Services;

use Modules\Marketplace\Models\Service;
use Illuminate\Support\Facades\Auth;

class MarketplaceService
{
    public function updateServiceStatus(Service $service, array $data): void
    {
        $status = $data['status'];
        $rejectionReason = $data['rejection_reason'] ?? null;

        $updateData = [
            'status' => $status,
        ];

        if ($status === 'active') {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = Auth::id();
        } elseif ($status === 'rejected') {
            $updateData['rejection_reason'] = $rejectionReason;
            $updateData['rejected_at'] = now();
        } elseif ($status === 'suspended') {
            $updateData['suspended_at'] = now();
            $updateData['suspended_by'] = Auth::id();
        }

        $service->update($updateData);

        // TODO: Fire events for email notifications (e.g. ServiceApprovedEvent, ServiceRejectedEvent)
    }

    public function bulkUpdateStatus(array $ids, string $action): int
    {
        $statusMap = [
            'approve' => 'active',
            'decline' => 'declined',
            'suspend' => 'suspended',
        ];

        $status = $statusMap[$action] ?? 'pending';

        $updateData = ['status' => $status];
        if ($status === 'active') {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = Auth::id();
        } elseif ($status === 'declined') {
            $updateData['rejected_at'] = now();
            // Rejection reason left empty for bulk decline unless passed
        } elseif ($status === 'suspended') {
            $updateData['suspended_at'] = now();
            $updateData['suspended_by'] = Auth::id();
        }

        $updatedCount = Service::whereIn('id', $ids)->update($updateData);

        // TODO: Fire bulk events if needed

        return $updatedCount;
    }
}
