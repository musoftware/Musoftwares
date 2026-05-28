import { usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Users,
    Briefcase,
    CheckSquare,
    FileText,
    History,
    Folder,
    Pin,
    Calendar,
    UserCheck,
    HardDrive,
    Settings,
    LifeBuoy,
    BarChart,
    Shield,
    Banknote,
    Package,
    Coins,
    Receipt,
    Lock,
} from 'lucide-react';

interface MenuItem {
    id: string;
    label: string;
    icon: any;
    isActive: boolean;
    href: string;
    locked?: boolean;
    badge?: number;
    description?: string;
    features?: string[];
}

interface ERPMenuResult {
    menuItems: MenuItem[];
    lockedAddons: MenuItem[];
    workspaceName: string;
    tenantId: string;
}

/**
 * Shared hook that provides ERP sidebar menu items and workspace metadata.
 * Used by all ERP sub-pages to avoid duplicating menu configuration.
 *
 * @param activeSection - The sidebar section ID to highlight (e.g. 'clients', 'projects')
 * @param options - Optional overrides for workspaceName and tenantId
 */
export function useERPMenu(
    activeSection: string,
    options?: { workspaceName?: string; tenantId?: string }
): ERPMenuResult {
    const { auth, tenant } = (usePage().props as any) ?? {};

    const isTeamMember = !!auth?.team_member;
    // Addons the user has purchased (array of addon slugs)
    const activeAddons: string[] = auth?.erp_addons ?? [];

    const allItems = [
        { id: 'overview', label: 'Overview', icon: LayoutDashboard },
        { id: 'clients', label: 'Clients', icon: Users },
        { id: 'projects', label: 'Projects', icon: Briefcase },
        { id: 'tasks', label: 'Tasks', icon: CheckSquare },
        { id: 'invoices', label: 'Invoices', icon: FileText },
        { id: 'transactions', label: 'Transactions', icon: History },
        { id: 'referrals', label: 'Referrals', icon: Users },
        { id: 'documents', label: 'Files', icon: Folder },
        { id: 'notes', label: 'Notes', icon: Pin },
        { id: 'calendar', label: 'Calendar', icon: Calendar },
        { id: 'team', label: 'Team', icon: UserCheck },
        { id: 'backup', label: 'Backup', icon: HardDrive },
        { id: 'settings', label: 'Settings', icon: Settings },
    ];

    // Addon menu items — shown at the bottom of the sidebar
    // These map to saas.php addon slugs under 'erp' parent
    const addonItems = [
        { id: 'erp-team-members', label: 'Team Members', icon: Users, description: 'Invite managers and staff to your workspace. Assign roles, control access, and track activity.', features: ['Role-based access', 'Invite by email', 'Suspend anytime'] },
        { id: 'erp-tickets', label: 'Support Tickets', icon: LifeBuoy, description: 'Track client issues, manage priorities, and resolve tickets — all from your workspace.', features: ['Priority levels', 'Client-linked tickets', 'Resolution tracking'] },
        { id: 'erp-referrals', label: 'Referral Program', icon: Users, description: 'Track client referrals, links, trees, and referral earnings.', features: ['Referral links', 'Tree structure', 'Earnings tracking'] },
        { id: 'erp-document-storage', label: 'Cloud Storage', icon: Package, description: 'Upload and manage files with your own S3-compatible storage provider.', features: ['S3/MinIO support', 'File categorization', 'Secure downloads'] },
        { id: 'erp-analytics', label: 'Reports', icon: BarChart, description: 'Unlock detailed revenue analytics, expense breakdowns, and client insights.', features: ['Revenue reports', 'Expense breakdown', 'Client insights'] },
        { id: 'erp-multi-currency', label: 'Multi Currency', icon: Coins, description: 'Issue invoices and track payments in multiple currencies with auto conversion.', features: ['Auto conversion', 'Multi-currency invoices', 'Exchange rate sync'] },
        { id: 'erp-permissions', label: 'Permissions', icon: Shield, description: 'Fine-grained permission controls for team members across all modules.', features: ['Granular roles', 'Module-level access', 'Audit trail'] },
        { id: 'erp-payroll', label: 'Payroll', icon: Banknote, description: 'Automate salary calculations, deductions, and payment tracking for your team.', features: ['Salary management', 'Deduction rules', 'Payment history'] },
        { id: 'erp-inventory', label: 'Inventory', icon: Package, description: 'Track stock levels, manage products, and automate reorder alerts.', features: ['Stock tracking', 'Product catalog', 'Reorder alerts'] },
        { id: 'erp-tax-engine', label: 'Tax Engine', icon: Receipt, description: 'Automated tax calculation, VAT management, and tax-ready invoice generation.', features: ['Auto tax calc', 'VAT support', 'Tax reports'] },
    ];

    // Team members don't see team, settings, or backup
    const filtered = allItems.filter((item) => {
        if (isTeamMember && (item.id === 'team' || item.id === 'settings' || item.id === 'backup')) {
            return false;
        }
        if (item.id === 'referrals' && !activeAddons.includes('erp-referrals')) {
            return false;
        }
        return true;
    });

    const menuItems: MenuItem[] = filtered.map((m) => ({
        ...m,
        isActive: m.id === activeSection,
        href:
            m.id === 'team'
                ? route('erp.team-members.index')
                : m.id === 'backup'
                  ? route('erp.backup.index')
                  : m.id === 'referrals'
                    ? route('erp.referrals.index')
                    : route('erp.dashboard', { section: m.id }),
    }));

    // Build locked addon items — only show addons the user DOESN'T have
    const lockedAddons: MenuItem[] = isTeamMember ? [] : addonItems
        .filter((addon) => !activeAddons.includes(addon.id))
        .map((addon) => ({
            id: addon.id,
            label: addon.label,
            icon: addon.icon,
            isActive: activeSection === addon.id,
            href: route('erp.dashboard', { section: addon.id }),
            locked: true,
            description: addon.description,
            features: addon.features,
        }));

    const workspaceName =
        options?.workspaceName ||
        (tenant?.name ? tenant.name : auth?.user?.name ? `${auth.user.name}'s Workspace` : 'ERP Workspace');

    const tenantId = options?.tenantId || tenant?.id?.toString() || 'ACTIVE';

    return { menuItems, lockedAddons, workspaceName, tenantId };
}
