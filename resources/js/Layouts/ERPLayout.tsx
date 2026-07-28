import React, { PropsWithChildren, ReactNode, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import SafeLink from '@/Components/SafeLink';
import { ChevronRight, ArrowLeft, Lock, Menu, X } from 'lucide-react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import { __ } from '@/lib/i18n';

interface ERPLayoutProps extends PropsWithChildren {
    title: string;
    workspaceName?: string;
    tenantId?: string;
    menuItems?: Array<{
        id: string;
        label: string;
        icon: any;
        badge?: number;
        href?: string;
        onClick?: (e?: any) => void;
        isActive: boolean;
        locked?: boolean;
    }>;
    lockedAddons?: Array<{
        id: string;
        label: string;
        icon: any;
        href: string;
        locked?: boolean;
        isActive: boolean;
        onClick?: (e?: any) => void;
        description?: string;
        features?: string[];
    }>;
}

export default function ERPLayout({
    title,
    workspaceName = 'Workspace',
    tenantId = 'DRAFT',
    menuItems = [],
    lockedAddons = [],
    children,
}: ERPLayoutProps) {
    const { auth } = usePage().props as any;
    const teamMember = auth?.team_member;
    const isTeamMember = !!teamMember;

    useInertiaNotifications();
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const activeMenuItem = menuItems.find(item => item.isActive);
    const activeMenuLabel = activeMenuItem?.label || 'Workspace';

    const safeWorkspaceName = workspaceName || 'Workspace';

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
            <Head title={`ERP — ${title}`} />

            {/* Mobile Menu Drawer Overlay */}
            <div 
                className={`fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm lg:hidden transition-opacity duration-300 ${
                    isMobileMenuOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
                }`}
                onClick={() => setIsMobileMenuOpen(false)}
            />

            {/* Mobile Menu Drawer Content */}
            <div 
                className={`fixed top-0 bottom-0 start-0 z-50 w-72 bg-white shadow-xl lg:hidden transform transition-transform duration-300 ease-in-out flex flex-col ${
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Drawer Header */}
                <div className="flex items-center justify-between p-4 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100">
                            {safeWorkspaceName.charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0">
                            <span className="block truncate text-sm font-semibold text-slate-900">
                                {safeWorkspaceName}
                            </span>
                            <span className="block font-mono text-[10px] text-slate-400">
                                Tenant ID: #{tenantId}
                            </span>
                        </div>
                    </div>
                    <button 
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-none"
                        aria-label={__('general.close_navigation_menu')}
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Drawer Menu Items */}
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
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
                                    {item.badge !== undefined && item.badge > 0 && (
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${
                                                item.isActive
                                                    ? 'border border-slate-200 bg-white text-slate-900 shadow-sm'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            {item.badge}
                                        </span>
                                    )}
                                </>
                            );
                            
                            return item.href ? (
                                <Link 
                                    key={item.id} 
                                    href={item.href} 
                                    onClick={(e) => {
                                        setIsMobileMenuOpen(false);
                                        if (item.onClick) item.onClick(e);
                                    }} 
                                    className={classes}
                                >
                                    {content}
                                </Link>
                            ) : (
                                <button 
                                    key={item.id} 
                                    onClick={(e) => {
                                        setIsMobileMenuOpen(false);
                                        if (item.onClick) item.onClick(e);
                                    }} 
                                    className={classes}
                                >
                                    {content}
                                </button>
                            );
                        })}
                    </nav>

                    {/* Locked Addon Upsells in Drawer */}
                    {lockedAddons.length > 0 && (
                        <div className="mt-2 pt-3 border-t border-slate-100">
                            <div className="px-3 pb-2">
                                <span className="text-[10px] font-semibold uppercase tracking-widest text-slate-300">{__('general.available_add_ons')}</span>
                            </div>
                            <nav className="space-y-0.5">
                                {lockedAddons.map((addon) => {
                                    const AddonIcon = addon.icon;
                                    const addonClasses = `group flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all ${
                                        addon.isActive
                                            ? 'bg-slate-100/70 text-slate-500 opacity-75'
                                            : 'text-slate-400 hover:bg-slate-50 hover:text-slate-500 opacity-60 hover:opacity-80'
                                    }`;
                                    const addonContent = (
                                        <>
                                            <div className="flex items-center gap-3">
                                                <AddonIcon className="h-4 w-4 shrink-0 text-slate-300 group-hover:text-slate-400" />
                                                <span>{addon.label}</span>
                                            </div>
                                            <Lock className="h-3 w-3 text-slate-300 group-hover:text-indigo-400 transition-colors" />
                                        </>
                                    );
                                    return addon.onClick ? (
                                        <button 
                                            key={addon.id} 
                                            onClick={(e) => {
                                                setIsMobileMenuOpen(false);
                                                if (addon.onClick) addon.onClick(e);
                                            }} 
                                            className={addonClasses}
                                        >
                                            {addonContent}
                                        </button>
                                    ) : (
                                        <Link 
                                            key={addon.id} 
                                            href={addon.href} 
                                            onClick={() => setIsMobileMenuOpen(false)}
                                            className={addonClasses}
                                        >
                                            {addonContent}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>
                    )}
                </div>
            </div>

            {/* Standalone Minimalist Top Navigation */}
            <header className="sticky top-0 z-40 w-full bg-white border-b border-slate-200" style={{ height: '60px' }}>
                <div className="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-full w-full mx-auto">
                    <div className="flex items-center gap-3">
                        {/* Hamburger Menu Toggle Button */}
                        <button
                            onClick={() => setIsMobileMenuOpen(true)}
                            className="lg:hidden -ms-2 p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-none"
                            aria-label={__('general.open_navigation_menu')}
                        >
                            <Menu className="w-5 h-5" />
                        </button>

                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100">
                            {safeWorkspaceName.charAt(0).toUpperCase()}
                        </div>
                        <span className="font-semibold text-slate-900 tracking-tight">{safeWorkspaceName} System</span>
                    </div>
                    <div className="flex items-center gap-4">
                        {isTeamMember ? (
                            <Link 
                                href={route('erp.team.logout')} 
                                method="post"
                                as="button"
                                className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" /> 
                                <span>{__('general.logout')}</span>
                            </Link>
                        ) : (
                            <SafeLink 
                                href={route('dashboard')} 
                                className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" /> 
                                <span className="hidden sm:inline">{__('general.exit_to_main_hub')}</span>
                                <span className="sm:hidden">{__('general.exit')}</span>
                            </SafeLink>
                        )}
                    </div>
                </div>
            </header>

            <div className="flex-1 overflow-auto">
                <div className="mx-auto w-full space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                    {/* Contextual Breadcrumb Navigator */}
                    <div className="mb-8 flex items-center gap-1.5 text-sm text-slate-500">
                        <span className="cursor-pointer transition-colors hover:text-slate-900">
                            {safeWorkspaceName}
                        </span>
                        <ChevronRight className="h-4 w-4 shrink-0 text-slate-400" />
                        <span className="font-medium text-slate-900">{activeMenuLabel}</span>
                    </div>

                    <div className="flex flex-col items-start gap-8 lg:flex-row">
                        {/* Left Sidebar */}
                        <aside className="hidden lg:block w-full shrink-0 lg:w-64">
                            <div className="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div className="flex items-center gap-2 border-b border-slate-100 pb-3 px-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-100">
                                        {safeWorkspaceName.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="min-w-0">
                                        <span className="block truncate text-sm font-semibold text-slate-900">
                                            {safeWorkspaceName}
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
                                                {item.badge !== undefined && item.badge > 0 && (
                                                    <span
                                                        className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${
                                                            item.isActive
                                                                ? 'border border-slate-200 bg-white text-slate-900 shadow-sm'
                                                                : 'bg-slate-100 text-slate-500'
                                                        }`}
                                                    >
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </>
                                        );
                                        
                                        return item.href ? (
                                            <Link key={item.id} href={item.href} onClick={item.onClick} className={classes}>
                                                {content}
                                            </Link>
                                        ) : (
                                            <button key={item.id} onClick={item.onClick} className={classes}>
                                                {content}
                                            </button>
                                        );
                                    })}
                                </nav>

                                {/* Locked Addon Upsells */}
                                {lockedAddons.length > 0 && (
                                    <div className="mt-2 pt-3 border-t border-slate-100">
                                        <div className="px-3 pb-2">
                                            <span className="text-[10px] font-semibold uppercase tracking-widest text-slate-300">{__('general.available_add_ons')}</span>
                                        </div>
                                        <nav className="space-y-0.5">
                                            {lockedAddons.map((addon) => {
                                                const AddonIcon = addon.icon;
                                                const addonClasses = `group flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all ${
                                                    addon.isActive
                                                        ? 'bg-slate-100/70 text-slate-500 opacity-75'
                                                        : 'text-slate-400 hover:bg-slate-50 hover:text-slate-500 opacity-60 hover:opacity-80'
                                                }`;
                                                const addonContent = (
                                                    <>
                                                        <div className="flex items-center gap-3">
                                                            <AddonIcon className="h-4 w-4 shrink-0 text-slate-300 group-hover:text-slate-400" />
                                                            <span>{addon.label}</span>
                                                        </div>
                                                        <Lock className="h-3 w-3 text-slate-300 group-hover:text-indigo-400 transition-colors" />
                                                    </>
                                                );
                                                return addon.onClick ? (
                                                    <button key={addon.id} onClick={addon.onClick} className={addonClasses}>
                                                        {addonContent}
                                                    </button>
                                                ) : (
                                                    <Link key={addon.id} href={addon.href} className={addonClasses}>
                                                        {addonContent}
                                                    </Link>
                                                );
                                            })}
                                        </nav>
                                    </div>
                                )}
                            </div>
                        </aside>

                        {/* Right Dynamic Content Area */}
                        <div className="min-w-0 flex-1 w-full">
                            {children}
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    );
}
