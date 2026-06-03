import React, { PropsWithChildren, ReactNode } from 'react';
import { SidebarProvider, SidebarTrigger } from '@/Components/ui/sidebar';
import { AppSidebar } from '@/Components/Admin/AppSidebar';
import { Head, Link } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

import { TooltipProvider } from '@/Components/ui/tooltip';
import { __ } from '@/lib/i18n';

interface AdminSidebarLayoutProps extends PropsWithChildren {
    title?: string;
    header?: ReactNode;
    user?: any;
    actions?: ReactNode;
}

export default function AdminSidebarLayout({ title, header, user, children, actions }: AdminSidebarLayoutProps) {
    const displayName = user?.name || 'Admin User';
    const displayEmail = user?.email || 'admin@example.com';

    return (
        <TooltipProvider>
        <SidebarProvider>
            {title && <Head title={title} />}
            <AppSidebar />
            <main className="flex-1 flex flex-col min-h-screen bg-slate-50 w-full overflow-hidden">
                <header className="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-4 border-b bg-white px-4 shadow-sm">
                    <SidebarTrigger className="-ml-1" />
                    <div className="flex-1">
                        {header && typeof header === 'string' ? (
                            <h1 className="text-lg font-semibold text-slate-800">{header}</h1>
                        ) : (
                            header
                        )}
                    </div>
                    {actions && (
                        <div className="flex items-center gap-2">
                            {actions}
                        </div>
                    )}
                    
                    {/* User Profile Dropdown */}
                    <DropdownMenu>
                        <DropdownMenuTrigger className="outline-none relative shrink-0">
                            <Avatar className="h-9 w-9 border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity duration-150">
                                <AvatarFallback className="bg-slate-900 text-white font-medium text-xs">
                                    {displayName.substring(0, 2).toUpperCase() || 'AD'}
                                </AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56 p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                            <div className="px-2 py-2 mb-2 border-b border-slate-50">
                                <p className="text-sm font-medium text-slate-900 truncate">{displayName}</p>
                                <p className="text-xs text-slate-500 truncate">{displayEmail}</p>
                            </div>
                            <DropdownMenuItem className="cursor-pointer rounded-lg text-sm mb-1" asChild>
                                <Link href={route().has('profile.edit') ? route('profile.edit') : '#'}>Profile</Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem className="cursor-pointer rounded-lg text-sm text-rose-600 focus:bg-rose-50 focus:text-rose-700" asChild>
                                <Link href={route().has('logout') ? route('logout') : '#'} method="post" as="button" className="w-full text-left">{__('general.log_out')}</Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </header>
                <div className="flex-1 overflow-auto p-4 sm:p-6 lg:p-8">
                    <div className="mx-auto w-full max-w-7xl">
                        {children}
                    </div>
                </div>
            </main>
        </SidebarProvider>
        </TooltipProvider>
    );
}
