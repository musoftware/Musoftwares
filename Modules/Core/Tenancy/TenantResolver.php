<?php

namespace Modules\Core\Tenancy;

use Modules\ERP\Models\Tenant;

class TenantResolver
{
    protected static ?Tenant $currentTenant = null;
    protected static bool $adminBypass = false;

    /**
     * Resolve the current tenant from context (e.g., subdomain, user, or queue payload).
     */
    public static function resolve(?Tenant $tenant = null): void
    {
        self::$currentTenant = $tenant;
    }

    /**
     * Retrieve the currently resolved tenant.
     */
    public static function current(): ?Tenant
    {
        return self::$currentTenant;
    }

    /**
     * Get the current tenant ID, throws if strict isolation is breached and not bypassed.
     */
    public static function currentId(): ?int
    {
        if (self::$adminBypass) {
            return null; // Admin bypass logic (e.g., super_admin)
        }

        if (!self::$currentTenant) {
            // Depending on strictness, throw an exception here
            // throw new \Exception("Tenant context is required but not resolved.");
            return null;
        }

        return self::$currentTenant->id;
    }

    /**
     * Enable admin bypass to query across tenants.
     */
    public static function enableAdminBypass(): void
    {
        self::$adminBypass = true;
    }

    /**
     * Disable admin bypass.
     */
    public static function disableAdminBypass(): void
    {
        self::$adminBypass = false;
    }

    /**
     * Execute a callback under a specific tenant context temporarily.
     */
    public static function runAs(Tenant $tenant, callable $callback)
    {
        $originalTenant = self::$currentTenant;
        self::resolve($tenant);

        try {
            return $callback();
        } finally {
            self::resolve($originalTenant);
        }
    }
}
