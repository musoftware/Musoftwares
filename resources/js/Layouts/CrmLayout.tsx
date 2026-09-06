import React, { PropsWithChildren, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import SafeLink from '@/Components/SafeLink';
import ThemeToggle from '@/Components/ThemeToggle';
import {
    ChevronRight, ArrowLeft, LayoutDashboard, Users, Mail, PlayCircle, Settings,
    Search, Tag, MessageCircle, Smartphone, Bookmark, Zap, Clock, BarChart2,
    UsersRound, FileText, Bot, PieChart, Send, Activity, UserPlus, Menu, X
} from 'lucide-react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import { __ } from '@/lib/i18n';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

interface CrmLayoutProps extends PropsWithChildren {
    title: string;
    activeMenu: string;
}

export default function CrmLayout({ title, activeMenu, children }: CrmLayoutProps) {
    const { crm_features, auth } = usePage().props as any;
    useInertiaNotifications();
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const workspaceName = "CRM Workspace";
    const tenantId = 'DRAFT';

    const teamMember = auth?.crm_team_member;
    const memberRole = teamMember?.role;
    const isTeamMember = !!teamMember;

    const hasFeature = (featureName: string) => {
        return crm_features?.includes(featureName) || crm_features?.[featureName] === true;
    };

    /**
     * Role-based menu access map.
     * If user is NOT a team member (owner), they see everything.
     * If user IS a team member, they see only items matching their role.
     */
    const roleMenuAccess: Record<string, string[]> = {
        'member': ['dashboard', 'leads'],
        'social_media': ['dashboard', 'leads'],
        'sales_agent': ['dashboard', 'leads', 'tags', 'search'],
        'call_center': ['dashboard', 'leads', 'tags', 'search'],
        'support_agent': ['dashboard', 'leads', 'search'],
        'support_manager': ['dashboard', 'leads', 'search', 'settings'],
        'sales_manager': ['dashboard', 'workspaces', 'leads', 'tags', 'search', 'widgets', 'campaigns', 'sequences'],
        'marketing': ['dashboard', 'leads', 'campaigns', 'widgets'],
        'manager': ['dashboard', 'workspaces', 'leads', 'tags', 'search', 'widgets', 'team', 'campaigns', 'sequences', 'settings'],
        'admin': ['dashboard', 'workspaces', 'leads', 'tags', 'search', 'widgets', 'team', 'campaigns', 'sequences', 'settings'],
    };

    const canSeeMenu = (menuId: string) => {
        if (!isTeamMember) return true; // Owner sees everything
        const allowed = roleMenuAccess[memberRole] || roleMenuAccess['member'];
        return allowed.includes(menuId);
    };

    // Define grouped menu structure
    const allCoreItems = [
        { id: 'dashboard', label: __('general.dashboard'), icon: LayoutDashboard, href: route('crm.dashboard'), isActive: activeMenu === 'dashboard' },
        { id: 'workspaces', label: __('general.workspaces'), icon: Activity, href: route('crm.workspaces.index'), isActive: activeMenu === 'workspaces' },
        { id: 'leads', label: __('general.leads_pipeline'), icon: Users, href: route('crm.leads.index'), isActive: activeMenu === 'leads' },
        { id: 'tags', label: __('general.tags_attributes'), icon: Tag, href: route('crm.tags.index'), isActive: activeMenu === 'tags' },
        { id: 'search', label: __('general.universal_search'), icon: Search, href: route('crm.search.page'), isActive: activeMenu === 'search' },
        { id: 'widgets', label: __('general.web_forms'), icon: FileText, href: route('crm.widgets.index'), isActive: activeMenu === 'widgets' },
    ].filter(item => canSeeMenu(item.id));

    const menuGroups: { title: string; items: typeof allCoreItems }[] = [];

    if (allCoreItems.length > 0) {
        menuGroups.push({
            title: __('general.core_operations'),
            items: allCoreItems
        });
    }

    // Advanced Operations / Automations
    const advancedItems: typeof allCoreItems = [];
    if ((hasFeature('crm.campaigns.whatsapp') || hasFeature('crm.campaigns.email') || hasFeature('crm-advanced-operations')) && canSeeMenu('campaigns')) {
        advancedItems.push({ id: 'campaigns', label: __('general.broadcast_campaigns'), icon: Mail, href: route('crm.campaigns.index'), isActive: activeMenu === 'campaigns' });
    }
    if ((hasFeature('crm.automations') || hasFeature('crm-advanced-operations')) && canSeeMenu('sequences')) {
        advancedItems.push({ id: 'sequences', label: __('general.automated_sequences'), icon: PlayCircle, href: route('crm.sequences.index'), isActive: activeMenu === 'sequences' });
    }
    if (advancedItems.length > 0) {
        menuGroups.push({
            title: __('general.advanced_operations'),
            items: advancedItems
        });
    }

    // System Settings & Team
    const systemItems: typeof allCoreItems = [];

    // Team Members — only visible to workspace owner (not team members)
    if (!isTeamMember) {
        systemItems.push({ id: 'team', label: __('crm.team_members'), icon: UserPlus, href: route('crm.team-members.index'), isActive: activeMenu === 'team' });
    }

    if (canSeeMenu('settings')) {
        systemItems.push({ id: 'settings', label: __('general.crm_settings'), icon: Settings, href: route('crm.settings.index'), isActive: activeMenu === 'settings' });
    }

    if (systemItems.length > 0) {
        menuGroups.push({
            title: __('general.system'),
            items: systemItems
        });
    }

    const activeMenuLabel = menuGroups.flatMap(g => g.items).find(item => item.isActive)?.label || __('general.workspace');

    const renderMenuItem = (item: (typeof allCoreItems)[0], onNavigate?: () => void) => {
        const Icon = item.icon;
        const classes = `group flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all ${
            item.isActive
                ? 'bg-slate-100 dark:bg-zinc-800 font-medium text-slate-900 dark:text-white'
                : 'text-slate-500 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800/60 hover:text-slate-900 dark:hover:text-white'
        }`;

        const content = (
            <div className="flex items-center gap-3">
                <Icon
                    className={`h-4 w-4 shrink-0 transition-colors ${
                        item.isActive
                            ? 'text-slate-900 dark:text-white'
                            : 'text-slate-400 dark:text-zinc-500 group-hover:text-slate-600 dark:group-hover:text-zinc-300'
                    }`}
                />
                <span>{item.label}</span>
            </div>
        );

        return item.href ? (
            <Link 
                key={item.id} 
                href={item.href} 
                onClick={onNavigate}
                className={classes}
            >
                {content}
            </Link>
        ) : (
            <button 
                key={item.id} 
                onClick={onNavigate}
                className={classes}
            >
                {content}
            </button>
        );
    };

    return (
        <div className="min-h-screen bg-[#f5f5f7] dark:bg-[#090d16] font-sans text-slate-900 dark:text-slate-100 flex flex-col transition-colors duration-200">
            <Head title={`CRM — ${title}`} />

            {/* Mobile Menu Drawer Overlay */}
            <div 
                className={`fixed inset-0 z-50 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm lg:hidden transition-opacity duration-300 ${
                    isMobileMenuOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
                }`}
                onClick={() => setIsMobileMenuOpen(false)}
            />

            {/* Mobile Menu Drawer Content */}
            <div 
                className={`fixed top-0 bottom-0 start-0 z-50 w-72 bg-white dark:bg-[#0f172a] border-e border-slate-200 dark:border-white/10 shadow-xl lg:hidden transform transition-transform duration-300 ease-in-out flex flex-col ${
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Drawer Header */}
                <div className="flex items-center justify-between p-4 border-b border-slate-100 dark:border-white/10">
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100 dark:shadow-none">
                            {workspaceName.charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0">
                            <span className="block truncate text-sm font-semibold text-slate-900 dark:text-white">
                                {workspaceName}
                            </span>
                            <span className="block font-mono text-[10px] text-slate-400 dark:text-zinc-500">
                                Tenant ID: #{tenantId}
                            </span>
                        </div>
                    </div>
                    <button 
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="p-1.5 rounded-lg text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors focus:outline-none"
                        aria-label="Close menu"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Drawer Menu Items */}
                <div className="flex-1 overflow-y-auto p-4 space-y-6">
                    {menuGroups.map((group, groupIdx) => (
                        <div key={groupIdx} className="space-y-1">
                            <h3 className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500 mb-2">
                                {group.title}
                            </h3>
                            <nav className="space-y-0.5">
                                {group.items.map((item) => renderMenuItem(item, () => setIsMobileMenuOpen(false)))}
                            </nav>
                        </div>
                    ))}

                    {/* Drawer Footer Actions */}
                    <div className="mt-4 pt-3 border-t border-slate-100 dark:border-white/10">
                        {isTeamMember ? (
                            <Link
                                href={route('crm.team.logout')}
                                method="post"
                                as="button"
                                onClick={() => setIsMobileMenuOpen(false)}
                                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 w-full transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" />
                                <span>{__('general.logout')}</span>
                            </Link>
                        ) : (
                            <SafeLink
                                href={route('dashboard')}
                                onClick={() => setIsMobileMenuOpen(false)}
                                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800 hover:text-slate-900 dark:hover:text-white w-full transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" />
                                <span>{__('general.exit_to_main_hub')}</span>
                            </SafeLink>
                        )}
                    </div>
                </div>
            </div>

            {/* Standalone Minimalist Top Navigation */}
            <header className="sticky top-0 z-40 w-full bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-slate-200 dark:border-white/10 transition-colors" style={{ height: '60px' }}>
                <div className="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-full w-full mx-auto">
                    <div className="flex items-center gap-3">
                        {/* Hamburger Menu Toggle Button */}
                        <button
                            onClick={() => setIsMobileMenuOpen(true)}
                            className="lg:hidden -ms-2 p-1.5 rounded-lg text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors focus:outline-none"
                            aria-label="Open Navigation"
                        >
                            <Menu className="w-5 h-5" />
                        </button>

                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100 dark:shadow-none">
                            {workspaceName.charAt(0).toUpperCase()}
                        </div>
                        <span className="font-semibold text-slate-900 dark:text-white tracking-tight">{workspaceName}</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <ThemeToggle className="h-8 w-8" />
                        {isTeamMember ? (
                            <Link
                                href={route('crm.team.logout')}
                                method="post"
                                as="button"
                                className="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" />
                                <span>{__('general.logout')}</span>
                            </Link>
                        ) : (
                            <SafeLink
                                href={route('dashboard')}
                                className="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-lg transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" />
                                <span>{__('general.exit_to_main_hub')}</span>
                            </SafeLink>
                        )}
                    </div>
                </div>
            </header>

            <div className="flex-1 overflow-auto">
                <div className="mx-auto w-full space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                    {/* Contextual Breadcrumb Navigator */}
                    <div className="mb-8 flex items-center gap-1.5 text-sm text-slate-500 dark:text-zinc-400">
                        <span className="cursor-pointer transition-colors hover:text-slate-900 dark:hover:text-white">
                            {workspaceName}
                        </span>
                        <ChevronRight className="h-4 w-4 shrink-0 text-slate-400 dark:text-zinc-600" />
                        <span className="font-medium text-slate-900 dark:text-white">{activeMenuLabel}</span>
                    </div>

                    <div className="flex flex-col items-start gap-8 lg:flex-row">
                        {/* Left Sidebar (Desktop Only) */}
                        <aside className="hidden lg:block w-full shrink-0 lg:w-64">
                            <div className="space-y-6 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0f172a] py-4 shadow-sm">

                                <div className="flex items-center gap-2 border-b border-slate-100 dark:border-white/10 pb-3 px-4">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100 dark:shadow-none">
                                        {workspaceName.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="min-w-0">
                                        <span className="block truncate text-sm font-semibold text-slate-900 dark:text-white">
                                            {workspaceName}
                                        </span>
                                        <span className="block font-mono text-[11px] text-slate-400 dark:text-zinc-500">
                                            ID: #{tenantId}
                                        </span>
                                    </div>
                                </div>

                                <div className="px-3 space-y-6">
                                    {menuGroups.map((group, groupIdx) => (
                                        <div key={groupIdx} className="space-y-1">
                                            <h3 className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500 mb-2">
                                                {group.title}
                                            </h3>
                                            <nav className="space-y-0.5">
                                                {group.items.map((item) => renderMenuItem(item))}
                                            </nav>
                                        </div>
                                    ))}
                                </div>

                            </div>
                        </aside>

                        {/* Right Dynamic Content Area */}
                        <div className="min-w-0 flex-1 w-full">
                            {children}
                        </div>
                    </div>
                </div>
            </div>

            <FloatingWhatsAppButton />
        </div>
    );
}
