import React, { PropsWithChildren, ReactNode } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

interface WorkspaceLayoutProps extends PropsWithChildren {
    title: string;
    workspaceName: string;
    tenantId?: string;
    menuItems: Array<{
        id: string;
        label: string;
        icon: any;
        badge?: number;
        href?: string;
        onClick?: (e?: any) => void;
        isActive: boolean;
    }>;
}

export default function WorkspaceLayout({
    title,
    workspaceName,
    tenantId = 'DRAFT',
    menuItems,
    children,
}: WorkspaceLayoutProps) {
    const activeMenuItem = menuItems.find(item => item.isActive);
    const activeMenuLabel = activeMenuItem?.label || 'Workspace';

    return (
        <AuthenticatedLayout header="Workspace">
            <Head title={`Workspace — ${title}`} />

            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
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
                        </div>
                    </aside>

                    {/* Right Dynamic Content Area */}
                    <div className="min-w-0 flex-1 w-full">
                        {children}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
