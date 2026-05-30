import React, { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ChevronRight, ArrowLeft, LayoutDashboard, Users, Mail, PlayCircle, Settings,
    Search, Tag, MessageCircle, Smartphone, Bookmark, Zap, Clock, BarChart2,
    UsersRound, FileText, Bot, PieChart, Send, Activity
} from 'lucide-react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import { CrmCommandPalette } from '@/Components/CRM/CrmCommandPalette';
import { __ } from '@/lib/i18n';

interface CrmLayoutProps extends PropsWithChildren {
    title: string;
    activeMenu: string;
}

export default function CrmLayout({ title, activeMenu, children }: CrmLayoutProps) {
    const { crm_features } = usePage().props as any;
    useInertiaNotifications();
    const workspaceName = "CRM Workspace";
    const tenantId = 'DRAFT';
    const [cmdOpen, setCmdOpen] = React.useState(false);

    const hasFeature = (featureName: string) => {
        return crm_features?.includes(featureName) || crm_features?.[featureName] === true;
    };

    // Define grouped menu structure
    const menuGroups = [
        {
            title: __('Core Operations'),
            items: [
                { id: 'dashboard', label: __('Dashboard'), icon: LayoutDashboard, href: '/crm/dashboard', isActive: activeMenu === 'dashboard' },
                { id: 'workspaces', label: __('Workspaces'), icon: Activity, href: '/crm', isActive: activeMenu === 'workspaces' },
                { id: 'leads', label: __('Leads & Pipeline'), icon: Users, href: '/crm/leads', isActive: activeMenu === 'leads' },
                { id: 'tags', label: __('Tags & Attributes'), icon: Tag, href: '/crm/tags', isActive: activeMenu === 'tags' },
                { id: 'search', label: __('Universal Search'), icon: Search, href: '/crm/search', isActive: activeMenu === 'search' },
            ]
        }
    ];

    // Advanced Operations / Automations
    const advancedItems = [];
    if (hasFeature('crm.campaigns.whatsapp') || hasFeature('crm.campaigns.email') || hasFeature('crm-advanced-operations')) {
        advancedItems.push({ id: 'campaigns', label: __('Broadcast Campaigns'), icon: Mail, href: '/crm/campaigns', isActive: activeMenu === 'campaigns' });
    }
    if (hasFeature('crm.automations') || hasFeature('crm-advanced-operations')) {
        advancedItems.push({ id: 'sequences', label: __('Automated Sequences'), icon: PlayCircle, href: '/crm/sequences', isActive: activeMenu === 'sequences' });
    }
    if (advancedItems.length > 0) {
        menuGroups.push({
            title: __('Advanced Operations'),
            items: advancedItems
        });
    }

    // WhatsApp Inbox
    if (hasFeature('crm.whatsapp') || hasFeature('crm-advanced-operations')) {
        menuGroups.push({
            title: __('WhatsApp Inbox'),
            items: [
                { id: 'wa-inbox', label: __('Live Inbox'), icon: MessageCircle, href: '/crm/whatsapp', isActive: activeMenu === 'wa-inbox' },
                { id: 'wa-accounts', label: __('Connected Accounts'), icon: Smartphone, href: '/crm/whatsapp/accounts', isActive: activeMenu === 'wa-accounts' },
                { id: 'wa-labels', label: __('Labels'), icon: Bookmark, href: '/crm/whatsapp/labels', isActive: activeMenu === 'wa-labels' },
                { id: 'wa-quick-replies', label: __('Quick Replies'), icon: Zap, href: '/crm/whatsapp/quick-replies', isActive: activeMenu === 'wa-quick-replies' },
                { id: 'wa-sla-policies', label: __('SLA Policies'), icon: Clock, href: '/crm/whatsapp/sla-policies', isActive: activeMenu === 'wa-sla-policies' },
                { id: 'wa-inbox-analytics', label: __('Inbox Analytics'), icon: BarChart2, href: '/crm/whatsapp/analytics/overview', isActive: activeMenu === 'wa-inbox-analytics' },
            ]
        });
    }

    // WhatsApp Campaigns
    if (hasFeature('crm.whatsapp_campaigns') || hasFeature('crm-advanced-operations')) {
        menuGroups.push({
            title: __('WhatsApp Campaigns'),
            items: [
                { id: 'wac-campaigns', label: __('Campaigns'), icon: Send, href: '/crm/whatsapp-campaigns', isActive: activeMenu === 'wac-campaigns' },
                { id: 'wac-audiences', label: __('Audiences'), icon: UsersRound, href: '/crm/whatsapp-campaigns/audiences', isActive: activeMenu === 'wac-audiences' },
                { id: 'wac-templates', label: __('Templates'), icon: FileText, href: '/crm/whatsapp-campaigns/templates', isActive: activeMenu === 'wac-templates' },
                { id: 'wac-automations', label: __('Automations'), icon: Bot, href: '/crm/whatsapp-campaigns/automations', isActive: activeMenu === 'wac-automations' },
                { id: 'wac-analytics', label: __('Campaign Analytics'), icon: PieChart, href: '/crm/whatsapp-campaigns/analytics/overview', isActive: activeMenu === 'wac-analytics' },
            ]
        });
    }

    // System Settings
    menuGroups.push({
        title: __('System'),
        items: [
            { id: 'settings', label: __('CRM Settings'), icon: Settings, href: '#', isActive: activeMenu === 'settings' },
        ]
    });

    const activeMenuLabel = menuGroups.flatMap(g => g.items).find(item => item.isActive)?.label || __('Workspace');

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
            <Head title={`CRM — ${title}`} />

            {/* Standalone Minimalist Top Navigation */}
            <header className="sticky top-0 z-40 w-full bg-white border-b border-slate-200" style={{ height: '60px' }}>
                <div className="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-full w-full mx-auto">
                    <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100">
                            {workspaceName.charAt(0).toUpperCase()}
                        </div>
                        <span className="font-semibold text-slate-900 tracking-tight">{workspaceName}</span>
                    </div>
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('dashboard')}
                            className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            <span className="hidden sm:inline">{__('Exit to Main Hub')}</span>
                            <span className="sm:hidden">{__('Exit')}</span>
                        </Link>
                    </div>
                </div>
            </header>

            <div className="flex-1 overflow-auto">
                <div className="mx-auto w-full space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                    {/* Contextual Breadcrumb Navigator */}
                    <div className="mb-8 flex items-center gap-1.5 text-sm text-slate-500">
                        <span className="cursor-pointer transition-colors hover:text-slate-900">
                            {workspaceName}
                        </span>
                        <ChevronRight className="h-4 w-4 shrink-0 text-slate-400" />
                        <span className="font-medium text-slate-900">{activeMenuLabel}</span>
                    </div>

                    <div className="flex flex-col items-start gap-8 lg:flex-row">
                        {/* Left Sidebar */}
                        <aside className="w-full shrink-0 lg:w-64">
                            <div className="space-y-6 rounded-xl border border-slate-200 bg-white py-4 shadow-sm">

                                <div className="flex items-center gap-2 border-b border-slate-100 pb-3 px-4">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100">
                                        {workspaceName.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="min-w-0">
                                        <span className="block truncate text-sm font-semibold text-slate-900">
                                            {workspaceName}
                                        </span>
                                        <span className="block font-mono text-[11px] text-slate-400">
                                            ID: #{tenantId}
                                        </span>
                                    </div>
                                </div>

                                <div className="px-3 space-y-6">
                                    {menuGroups.map((group, groupIdx) => (
                                        <div key={groupIdx} className="space-y-1">
                                            <h3 className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                                                {group.title}
                                            </h3>
                                            <nav className="space-y-0.5">
                                                {group.items.map((item) => {
                                                    const Icon = item.icon;
                                                    const classes = `group flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all ${item.isActive
                                                            ? 'bg-slate-100 font-medium text-slate-900'
                                                            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                                                        }`;

                                                    const content = (
                                                        <div className="flex items-center gap-3">
                                                            <Icon
                                                                className={`h-4 w-4 shrink-0 transition-colors ${item.isActive
                                                                        ? 'text-slate-900'
                                                                        : 'text-slate-400 group-hover:text-slate-600'
                                                                    }`}
                                                            />
                                                            <span>{item.label}</span>
                                                        </div>
                                                    );

                                                    return item.href ? (
                                                        <Link key={item.id} href={item.href} className={classes}>
                                                            {content}
                                                        </Link>
                                                    ) : (
                                                        <button key={item.id} className={classes}>
                                                            {content}
                                                        </button>
                                                    );
                                                })}
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

            <CrmCommandPalette open={cmdOpen} setOpen={setCmdOpen} onOpenLead={() => { }} />
        </div>
    );
}
