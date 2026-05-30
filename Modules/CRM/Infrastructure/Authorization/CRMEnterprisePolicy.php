<?php

namespace Modules\CRM\Infrastructure\Authorization;

use App\Models\User;

class CRMEnterprisePolicy
{
    /**
     * Check if the user has access to basic sales staff operations (Lead Collector / Telesales).
     */
    public function viewSalesStaffOperations(User $user): bool
    {
        return $user->hasModuleSubscription('crm-sales-staff') || 
               $user->hasModuleSubscription('crm-sales-management');
    }

    /**
     * Check if the user has access to management dashboards (Sales Manager / Team Leader).
     */
    public function viewSalesManagement(User $user): bool
    {
        return $user->hasModuleSubscription('crm-sales-management');
    }

    /**
     * Check if the user has access to the enterprise call center features.
     */
    public function viewCallCenter(User $user): bool
    {
        return $user->hasModuleSubscription('crm-call-center');
    }

    /**
     * Check if the user has access to advanced workflows and automations.
     */
    public function manageAdvancedOperations(User $user): bool
    {
        return $user->hasModuleSubscription('crm-advanced-operations');
    }
}
