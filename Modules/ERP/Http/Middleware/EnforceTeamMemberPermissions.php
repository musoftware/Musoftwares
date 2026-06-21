<?php

namespace Modules\ERP\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\TeamMember;

class EnforceTeamMemberPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('erp_team')->check()) {
            $member = Auth::guard('erp_team')->user();
            $route = $request->route();
            $routeName = $route ? $route->getName() : '';

            // Admin has full access
            if ($member->role === TeamMember::ROLE_ADMIN || $member->role === TeamMember::ROLE_LEGACY_MANAGER) {
                return $next($request);
            }

            // Always allow basic features for all logged-in team members
            $alwaysAllowed = [
                'erp.dashboard',
                'erp.team.portal',
                'erp.tasks',
                'erp.notes',
                'erp.calendar',
                'erp.files',
                'erp.onboarding',
                'erp.settings.update' // Might be needed for basic profile updates, but wait, settings are admin.
            ];

            foreach ($alwaysAllowed as $allowed) {
                if (str_starts_with($routeName, $allowed)) {
                    // Do not allow settings for non-admin/managers unless it's their own profile (which might be handled elsewhere)
                    if (str_starts_with($routeName, 'erp.settings')) {
                        continue; 
                    }
                    return $next($request);
                }
            }

            $role = $member->role;
            
            // Payroll & Settings -> Only Admin and Account Manager
            if (str_starts_with($routeName, 'erp.payroll') || str_starts_with($routeName, 'erp.settings')) {
                if ($role !== TeamMember::ROLE_ACCOUNT_MANAGER) {
                    abort(403, 'Unauthorized. Your role does not permit access to this module.');
                }
            }

            // Financials (Invoices, Withdrawals, Expenses, Debts, Recurring, Payment Methods)
            $isFinancial = str_starts_with($routeName, 'erp.invoices') || 
                           str_starts_with($routeName, 'erp.withdrawals') || 
                           str_starts_with($routeName, 'erp.expenses') || 
                           str_starts_with($routeName, 'erp.debts') || 
                           str_starts_with($routeName, 'erp.recurring') ||
                           str_starts_with($routeName, 'erp.payment-methods');
            if ($isFinancial) {
                if (!in_array($role, [TeamMember::ROLE_ACCOUNT_MANAGER, TeamMember::ROLE_SALES_MANAGER, TeamMember::ROLE_BRANCH_MANAGER])) {
                    if ($role === TeamMember::ROLE_SALES_AGENT && str_starts_with($routeName, 'erp.invoices')) {
                        // Sales agents can access invoices
                    } else {
                        abort(403, 'Unauthorized. Your role does not permit access to financial modules.');
                    }
                }
            }

            // Inventory & POS
            $isInventory = str_starts_with($routeName, 'erp.inventory') || str_starts_with($routeName, 'erp.pos');
            if ($isInventory) {
                if (!in_array($role, [TeamMember::ROLE_BRANCH_MANAGER, TeamMember::ROLE_SALES_MANAGER, TeamMember::ROLE_SALES_AGENT, TeamMember::ROLE_ACCOUNT_MANAGER])) {
                    abort(403, 'Unauthorized. Your role does not permit access to inventory and POS.');
                }
            }
            
            // Team Members Management
            if (str_starts_with($routeName, 'erp.team-members')) {
                if (!in_array($role, [TeamMember::ROLE_BRANCH_MANAGER, TeamMember::ROLE_ACCOUNT_MANAGER])) {
                    abort(403, 'Unauthorized. Your role does not permit managing team members.');
                }
            }

            // Support Tickets
            if (str_starts_with($routeName, 'erp.tickets')) {
                if (!in_array($role, [TeamMember::ROLE_SUPPORT_AGENT, TeamMember::ROLE_SUPPORT_MANAGER, TeamMember::ROLE_ACCOUNT_MANAGER, TeamMember::ROLE_BRANCH_MANAGER])) {
                    abort(403, 'Unauthorized. Your role does not permit access to support tickets.');
                }
            }
        }

        return $next($request);
    }
}
