<?php

namespace Modules\Booking\app\Features\Reminders\Services;

class WaReminderLimitsService
{
    /**
     * Check if a tenant has remaining WhatsApp reminder credits.
     * This queries the core SaaS modules, or we define a simple local limit config for now.
     */
    public function canUse(int $tenantId): bool
    {
        // 1. Check feature flag first.
        if (!feature('booking-wa-reminders')) {
            return false;
        }

        // 2. Check limits via TenantFeature or a core limits system.
        // Assuming there is a limits column or we track usage in an activity table.
        // For demonstration, let's assume limit is 500.
        $limit = $this->getMonthlyLimit($tenantId);
        $used = $this->getCurrentMonthUsage($tenantId);

        return $used < $limit;
    }

    /**
     * Increase the usage counter for WhatsApp reminders for a tenant.
     */
    public function increaseUsage(int $tenantId, int $amount = 1): void
    {
        // Typically logged in a tenant_usages table or similar.
        // \App\Models\TenantUsage::updateOrCreate(...)
        // Example implementation depends on the exact core SaaS engine structure.
        
        // For now, fire an event or log it to the billing engine.
        \Illuminate\Support\Facades\Log::info("WA Reminder limit increased for Tenant {$tenantId} by {$amount}");
    }

    /**
     * Get the remaining limit for UI.
     */
    public function getRemainingUsage(int $tenantId): int
    {
        $limit = $this->getMonthlyLimit($tenantId);
        $used = $this->getCurrentMonthUsage($tenantId);
        
        return max(0, $limit - $used);
    }

    protected function getMonthlyLimit(int $tenantId): int
    {
        // We could fetch this from the current plan or hardcode the default for the addon.
        // Add-ons might increase this limit.
        return 500;
    }

    protected function getCurrentMonthUsage(int $tenantId): int
    {
        // Query the reminders table to see how many were sent this month
        return \Modules\Booking\app\Features\Reminders\Models\BookingWaReminder::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->count();
    }
}
