<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Notifications\ServiceModerationNotification;
use Illuminate\Support\Facades\Log;
use Modules\Marketplace\Models\Service;

/** Approving and rejecting a service: records who did it and tells the seller. */
class ServiceModeration
{
    public function approve(Service $service, User $admin): void
    {
        $service->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $this->notifySeller($service, 'approved');
    }

    public function reject(Service $service, ?string $reason): void
    {
        $service->update(['status' => 'rejected', 'rejected_at' => now(), 'rejection_reason' => $reason]);

        $this->notifySeller($service, 'rejected', $reason);
    }

    /** The decision is already saved, so a mail problem is logged and does not undo it. */
    private function notifySeller(Service $service, string $decision, ?string $reason = null): void
    {
        try {
            $service->seller?->notify(new ServiceModerationNotification($service, $decision, $reason));
        } catch (\Throwable $e) {
            Log::error('marketplace.service.moderation_notice_failed', ['service_id' => $service->id, 'error' => $e->getMessage()]);
        }
    }
}
