import React, { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight, ArrowLeft, LayoutDashboard, Users, Mail, PlayCircle, Settings } from 'lucide-react';
import { Toaster } from '@/Components/ui/toaster';
import { CrmCommandPalette } from '@/Components/CRM/CrmCommandPalette';

interface CrmLayoutProps extends PropsWithChildren {
    title: string;
    activeMenu: string;
}

export default function CrmLayout({ title, activeMenu, children }: CrmLayoutProps) {
    const { crm_features } = usePage().props as any;
    const workspaceName = "My CRM Workspace";
    const tenantId = 'DRAFT';

    const hasFeature = (featureName: string) => {
        // If the features array includes the feature name or if the key is true
        return crm_features?.includes(featureName) || crm_features?.[featureName] === true;
    };

    const menuItems = [
        {
            id: 'dashboard',
            label: 'Dashboard',
            icon: LayoutDashboard,
            href: route('crm.dashboard'),
            isActive: activeMenu === 'dashboard',
        },
        {
            id: 'leads',
            label: 'Leads',
            icon: Users,
            href: route('crm.leads.index'),
            isActive: activeMenu === 'leads',
        },
    ];

    if (hasFeature('crm.campaigns.whatsapp') || hasFeature('crm.campaigns.email')) {
        menuItems.push({
            id: 'campaigns',
            label: 'Campaigns',
            icon: Mail,
            href: route('crm.campaigns.index'),
            isActive: activeMenu === 'campaigns',
        });
    }

    if (hasFeature('crm.automations')) {
        menuItems.push({
            id: 'sequences',
            label: 'Sequences',
            icon: PlayCircle,
            href: route('crm.sequences.index'),
            isActive: activeMenu === 'sequences',
        });
    }

    menuItems.push({
        id: 'settings',
        label: 'Settings',
        icon: Settings,
        href: '#', // TODO: CRM Settings route
        isActive: activeMenu === 'settings',
    });

    const [cmdOpen, setCmdOpen] = React.useState(false);

    const activeMenuItem = menuItems.find(item => item.isActive);
    const activeMenuLabel = activeMenuItem?.label || 'Workspace';

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
                        <span className="font-semibold text-slate-900 tracking-tight">{workspaceName} System</span>
                    </div>
                    <div className="flex items-center gap-4">
                        <Link 
                            href={route('dashboard')} 
                            className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4" /> 
                            <span className="hidden sm:inline">Exit to Main Hub</span>
                            <span className="sm:hidden">Exit</span>
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
                            <div className="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div className="flex items-center gap-2 border-b border-slate-100 pb-3 px-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100">
                                        {workspaceName.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="min-w-0">
                                        <span className="block truncate text-sm font-semibold text-slate-900">
                                            {workspaceName}
                                        </span>
                                        <span className="block font-mono text-[11px] text-slate-400">
                                            Active Tenant ID: #{tenantId}
                                        </span>
                                    </div>
                                </div>

                                <nav className="space-y-0.5">
                                    {menuItems.map((item) => {
                                        const Icon = item.icon;
                                        const classes = `group flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all ${
                                            item.isActive
                                                ? 'bg-slate-100 font-medium text-slate-900'
                                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                                        }`;
                                        
                                        const content = (
                                            <>
                                                <div className="flex items-center gap-3">
                                                    <Icon
                                                        className={`h-4 w-4 shrink-0 transition-colors ${
                                                            item.isActive
                                                                ? 'text-slate-900'
                                                                : 'text-slate-400 group-hover:text-slate-600'
                                                        }`}
                                                    />
                                                    <span>{item.label}</span>
                                                </div>
                                            </>
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
                        </aside>

                        {/* Right Dynamic Content Area */}
                        <div className="min-w-0 flex-1 w-full">
                            {children}
                        </div>
                    </div>
                </div>
            </div>
            
            <CrmCommandPalette open={cmdOpen} setOpen={setCmdOpen} onOpenLead={() => {}} />
            <Toaster />
        </div>
    );
}
