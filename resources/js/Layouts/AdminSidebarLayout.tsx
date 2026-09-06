import React, { PropsWithChildren, ReactNode } from 'react';
import { SidebarProvider, SidebarTrigger } from '@/Components/ui/sidebar';
import { AppSidebar } from '@/Components/Admin/AppSidebar';
import { Head, Link, usePage } from '@inertiajs/react';
import SafeLink from '@/Components/SafeLink';
import ThemeToggle from '@/Components/ThemeToggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

import { TooltipProvider } from '@/Components/ui/tooltip';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import { __ } from '@/lib/i18n';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

interface AdminSidebarLayoutProps extends PropsWithChildren {
    title?: string;
    header?: ReactNode;
    user?: any;
    actions?: ReactNode;
}

export default function AdminSidebarLayout({ title, header, user, children, actions }: AdminSidebarLayoutProps) {
    useInertiaNotifications();

    const { auth } = usePage<any>().props;
    const currentUser = user || auth?.user;
    const displayName = currentUser?.name || '';
    const displayEmail = currentUser?.email || '';

    return (
        <TooltipProvider>
        <SidebarProvider>
            {title && <Head title={title} />}
            <AppSidebar />
            <main className="flex-1 flex flex-col min-h-screen bg-slate-50 dark:bg-[#090d16] text-slate-900 dark:text-slate-100 w-full overflow-hidden transition-colors duration-200">
                <header
                    className="sticky top-0 z-10 flex shrink-0 items-center gap-4 border-b border-slate-200 dark:border-white/10 bg-white dark:bg-[#0f172a] px-4 shadow-sm transition-colors"
                    style={{
                        paddingTop: 'calc(12px + env(safe-area-inset-top, 0px))',
                        paddingBottom: '12px',
                        minHeight: 'calc(64px + env(safe-area-inset-top, 0px))'
                    }}
                >
                    <SidebarTrigger className="-ms-1" />
                    <div className="flex-1">
                        {header && typeof header === 'string' ? (
                            <h1 className="text-lg font-semibold text-slate-800 dark:text-slate-100">{header}</h1>
                        ) : (
                            header
                        )}
                    </div>
                    {actions && (
                        <div className="hidden sm:flex items-center gap-2">
                            {actions}
                        </div>
                    )}
                    
                    <ThemeToggle className="h-9 w-9" />

                    {/* User Profile Dropdown */}
                    <DropdownMenu>
                        <DropdownMenuTrigger className="outline-none relative shrink-0">
                            <Avatar className="h-9 w-9 border border-slate-200 dark:border-white/10 cursor-pointer hover:opacity-80 transition-opacity duration-150">
                                <AvatarFallback className="bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-medium text-xs">
                                    {displayName ? displayName.substring(0, 2).toUpperCase() : 'U'}
                                </AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56 p-2 rounded-xl shadow-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900 isolate z-50">
                            <div className="px-2 py-2 mb-2 border-b border-slate-50 dark:border-white/5">
                                <p className="text-sm font-medium text-slate-900 dark:text-white truncate">{displayName}</p>
                                <p className="text-xs text-slate-500 dark:text-zinc-400 truncate">{displayEmail}</p>
                            </div>
                            <DropdownMenuItem className="cursor-pointer rounded-lg text-sm mb-1 dark:text-zinc-200 dark:hover:text-white" asChild>
                                <SafeLink href={route().has('dashboard') ? route('dashboard') : '#'}>{__('general.dashboard')}</SafeLink>
                            </DropdownMenuItem>
                            <DropdownMenuItem className="cursor-pointer rounded-lg text-sm mb-1 dark:text-zinc-200 dark:hover:text-white" asChild>
                                <Link href={route().has('profile.edit') ? route('profile.edit') : '#'}>{__('general.profile')}</Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator className="dark:bg-white/10" />
                            <DropdownMenuItem className="cursor-pointer rounded-lg text-sm text-slate-900 dark:text-zinc-200 focus:bg-red-50 dark:focus:bg-red-950/30 focus:text-slate-900 dark:focus:text-red-400" asChild>
                                <Link href={route().has('logout') ? route('logout') : '#'} method="post" as="button" className="w-full text-start">{__('general.log_out')}</Link>
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
            <FloatingWhatsAppButton />
        </SidebarProvider>
        </TooltipProvider>
    );
}
