import React, { PropsWithChildren, ReactNode } from 'react';
import { SidebarProvider, SidebarTrigger } from '@/Components/ui/sidebar';
import { AppSidebar } from '@/Components/Admin/AppSidebar';
import { Head, Link, usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { TooltipProvider } from '@/Components/ui/tooltip';
import { Breadcrumbs } from '@/Components/ui/Breadcrumbs';
import { __ } from '@/lib/i18n';

interface AdminBoardLayoutProps extends PropsWithChildren {
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

export default function AdminBoardLayout({ title, breadcrumbs, children }: AdminBoardLayoutProps) {
    const { auth } = usePage<any>().props;
    const currentUser = auth?.user;
    const displayName = currentUser?.name || '';
    const displayEmail = currentUser?.email || '';

    return (
        <TooltipProvider>
            <SidebarProvider>
                {title && <Head title={title} />}
                <AppSidebar />
                <main className="flex min-h-screen w-full flex-1 flex-col bg-slate-50">
                    <header className="sticky top-0 z-40 flex h-12 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 shadow-sm sm:px-4">
                        <SidebarTrigger className="-ms-1 h-8 w-8 text-slate-600" />
                        <div className="h-5 w-px bg-slate-200" />
                        <nav aria-label={__('general.board_layout_aria')} className="min-w-0 flex-1">
                            {breadcrumbs && breadcrumbs.length > 0 && (
                                <Breadcrumbs items={breadcrumbs} />
                            )}
                        </nav>

                        <DropdownMenu>
                            <DropdownMenuTrigger className="outline-none relative shrink-0">
                                <Avatar className="h-8 w-8 border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity duration-150">
                                    <AvatarFallback className="bg-slate-900 text-white font-medium text-[10px]">
                                        {displayName ? displayName.substring(0, 2).toUpperCase() : 'U'}
                                    </AvatarFallback>
                                </Avatar>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56 p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                <div className="px-2 py-2 mb-2 border-b border-slate-50">
                                    <p className="text-sm font-medium text-slate-900 truncate">{displayName}</p>
                                    <p className="text-xs text-slate-500 truncate">{displayEmail}</p>
                                </div>
                                <DropdownMenuItem className="cursor-pointer rounded-lg text-sm mb-1" asChild>
                                    <Link href={route().has('dashboard') ? route('dashboard') : '#'}>{__('general.dashboard')}</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem className="cursor-pointer rounded-lg text-sm mb-1" asChild>
                                    <Link href={route().has('profile.edit') ? route('profile.edit') : '#'}>{__('general.profile')}</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="cursor-pointer rounded-lg text-sm text-slate-900 focus:bg-red-50 focus:text-slate-900" asChild>
                                    <Link href={route().has('logout') ? route('logout') : '#'} method="post" as="button" className="w-full text-start">{__('general.log_out')}</Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </header>

                    {children}
                </main>
            </SidebarProvider>
        </TooltipProvider>
    );
}