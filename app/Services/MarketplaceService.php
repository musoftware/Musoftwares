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
            // In a real system, you would record who approved it
            // 'approved_by' => Auth::id(),
        } elseif ($status === 'rejected') {
            $updateData['rejection_reason'] = $rejectionReason;
            $updateData['rejected_at'] = now();
        } elseif ($status === 'suspended') {
            $updateData['suspended_at'] = now();
            // 'suspended_by' => Auth::id(),
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

        $updatedCount = Service::whereIn('id', $ids)->update(['status' => $status]);

        // TODO: Fire bulk events if needed

        return $updatedCount;
    }
}
