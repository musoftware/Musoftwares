import React, { PropsWithChildren, useState, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuGroup,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import {
    Bell, ChevronDown, Menu, Plus, Coins, LogOut,
    User, History, Shield, Briefcase, Search, Clock, ArrowLeft
} from 'lucide-react';
import FreelanceModeToggle from '@/Components/Freelance/FreelanceModeToggle';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { __ } from '@/lib/i18n';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';

export default function FreelanceLayout({ children, clean = false }) {
    useInertiaNotifications();
    const { auth, notifications, is_lance_domain } = usePage().props;
    const user = auth.user;
    
    const freelanceModeContext = useFreelanceMode();
    const mode = freelanceModeContext?.mode || 'client';
    const isClient = mode === 'client';

    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    useEffect(() => {
        if (typeof window !== 'undefined') {
            const clientOnlyPrefixes = [
                '/freelance/jobs/create',
                '/freelance/jobs/my-jobs'
            ];
            const freelancerOnlyPrefixes = [
                '/freelance/jobs/browse',
                '/freelance/proposals',
            ];

            const isClientOnlyPath = 
                clientOnlyPrefixes.some(path => currentPath.startsWith(path)) ||
                (currentPath.startsWith('/freelance/jobs/') && currentPath.includes('/edit'));

            const isFreelancerOnlyPath = 
                freelancerOnlyPrefixes.some(path => currentPath.startsWith(path));

            if (!isClient && isClientOnlyPath) {
                router.visit('/freelance/dashboard');
            } else if (isClient && isFreelancerOnlyPath) {
                router.visit('/freelance/dashboard');
            }
        }
    }, [isClient, currentPath]);

    const freelancerMenuItems = [
        { id: 'dashboard',  label: __('general.dashboard'),    icon: Briefcase, href: '/freelance/dashboard',              isActive: currentPath === '/freelance/dashboard' || currentPath === '/freelance/dashboard/' },
        { id: 'jobs',       label: __('general.find_work'),     icon: Search,    href: '/freelance/jobs/browse',  isActive: currentPath.startsWith('/freelance/jobs/browse') || currentPath.startsWith('/freelance/jobs/') && !currentPath.includes('/my-jobs') && !currentPath.includes('/create') },
        { id: 'proposals',  label: __('freelance.my_proposals'),  icon: Clock,     href: '/freelance/proposals',    isActive: currentPath.startsWith('/freelance/proposals') },
        { id: 'contracts',  label: __('freelance.my_contracts'),  icon: Clock,     href: '/freelance/contracts',    isActive: currentPath.startsWith('/freelance/contracts') },
    ];

    const clientMenuItems = [
        { id: 'dashboard',  label: __('general.dashboard'),       icon: Briefcase, href: '/freelance/dashboard',              isActive: currentPath === '/freelance/dashboard' || currentPath === '/freelance/dashboard/' },
        { id: 'post-job',   label: __('freelance.post_a_job'),      icon: Plus,      href: '/freelance/jobs/create',  isActive: currentPath.startsWith('/freelance/jobs/create') },
        { id: 'my-jobs',    label: __('freelance.my_posted_jobs'),  icon: Briefcase, href: '/freelance/jobs/my-jobs', isActive: currentPath.startsWith('/freelance/jobs/my-jobs') },
        { id: 'contracts',  label: __('freelance.my_contracts'),    icon: Clock,     href: '/freelance/contracts',    isActive: currentPath.startsWith('/freelance/contracts') },
    ];

    const menuItems = isClient ? clientMenuItems : freelancerMenuItems;

    const NavLink = ({ href, active, children }) => (
        <Link
            href={href}
            className={cn(
                'inline-flex items-center gap-1.5 h-10 px-4 rounded-full text-sm font-semibold leading-none transition-colors duration-150',
                active
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            )}
        >
            {children}
        </Link>
    );

    const displayName = user?.name || 'User';
    const displayEmail = user?.email || '';

    return (
        <div className="min-h-screen bg-[#F9FAFB] font-sans text-slate-900 flex flex-col">
            <header className="sticky top-0 z-40 w-full bg-white border-b border-slate-200 shadow-sm" style={{ height: '72px' }}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
                    <div className="flex h-full items-center justify-between">
                        {/* LEFT: Logo & Nav */}
                        <div className="flex items-center gap-8">
                            <Link href="/freelance/dashboard" className="flex items-center gap-2">
                                <ApplicationLogo className="h-8 w-auto text-emerald-600" />
                                <span className="font-bold text-lg tracking-tight text-slate-900 hidden sm:block">ArabiJobs</span>
                            </Link>

                            <nav className="hidden md:flex items-center gap-2">
                                {menuItems.map(item => (
                                    <NavLink key={item.id} href={item.href} active={item.isActive}>
                                        {item.label}
                                    </NavLink>
                                ))}
                            </nav>
                        </div>

                        {/* RIGHT: Mode, Points, Notifications, Profile */}
                        <div className="flex items-center gap-3">
                            {/* Mode Toggle */}
                            <div className="mr-1 sm:mr-2">
                                <FreelanceModeToggle />
                            </div>

                            {/* Points pill */}
                            <Link
                                href="/points"
                                className="hidden sm:inline-flex items-center gap-1.5 h-9 px-3.5 bg-amber-50 hover:bg-amber-100 border border-amber-200/60 rounded-full transition-colors duration-150 text-sm font-semibold text-amber-700 shadow-sm"
                                title={__("freelance.available_connects")}
                            >
                                <Coins className="w-4 h-4 text-amber-500 shrink-0" />
                                <span>{user?.points_balance !== undefined ? Number(user.points_balance).toLocaleString() : '0'}</span>
                            </Link>

                            {/* Notifications */}
                            <DropdownMenu>
                                <div className="relative inline-block">
                                    <DropdownMenuTrigger className="w-10 h-10 rounded-full inline-flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors duration-150 relative outline-none shrink-0">
                                        <Bell className="w-5 h-5" />
                                        {notifications?.unread_count > 0 && (
                                            <span className="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border-2 border-white" />
                                        )}
                                    </DropdownMenuTrigger>
                                </div>
                                <DropdownMenuContent align="end" className="w-80 p-0 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                    <div className="px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                                        <span className="font-semibold text-slate-900 text-sm">{__('general.notifications')}</span>
                                    </div>
                                    <div className="max-h-[300px] overflow-y-auto p-2">
                                        {notifications?.recent && notifications.recent.length > 0 ? (
                                            notifications.recent.map((n) => (
                                                <div key={n.id} className="p-2 hover:bg-slate-50 rounded-lg text-xs flex justify-between items-start gap-2 border-b border-slate-50 last:border-0">
                                                    <div className="flex-1">
                                                        <p className="text-slate-800 font-medium">{n.data?.message || n.data?.title || 'New Notification'}</p>
                                                        <span className="text-[10px] text-slate-400">{n.created_at ? new Date(n.created_at).toLocaleDateString() : ''}</span>
                                                    </div>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="px-2 py-6 text-center text-sm text-slate-500 font-light">
                                                {__('general.no_new_notifications')}
                                            </div>
                                        )}
                                    </div>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {/* Profile Dropdown */}
                            <DropdownMenu>
                                <DropdownMenuTrigger className="outline-none relative shrink-0">
                                    <Avatar className="h-10 w-10 border-2 border-slate-200 cursor-pointer hover:border-emerald-500 transition-colors duration-150">
                                        <AvatarFallback className="bg-emerald-100 text-emerald-800 font-bold text-sm">
                                            {displayName.substring(0, 2).toUpperCase() || 'US'}
                                        </AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56 p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                    <div className="px-2 py-2 mb-2 border-b border-slate-50">
                                        <p className="text-sm font-semibold text-slate-900 truncate">{displayName}</p>
                                        <p className="text-xs text-slate-500 truncate">{displayEmail}</p>
                                    </div>
                                    
                                    <DropdownMenuGroup>
                                        {!is_lance_domain && (
                                            <DropdownMenuItem 
                                                className="cursor-pointer rounded-lg text-sm mb-1"
                                                render={<Link href="/dashboard" className="flex items-center w-full font-medium" />}
                                            >
                                                <ArrowLeft className="mr-2 h-4 w-4 text-slate-400" /> {__('general.exit_to_main_hub')}
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem 
                                            className="cursor-pointer rounded-lg text-sm"
                                            render={<Link href="/profile" className="flex items-center w-full" />}
                                        >
                                            <User className="mr-2 h-4 w-4 text-slate-400" /> {__('general.my_profile')}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem 
                                            className="cursor-pointer rounded-lg text-sm"
                                            render={<Link href="/financial/transactions" className="flex items-center w-full" />}
                                        >
                                            <History className="mr-2 h-4 w-4 text-slate-400" /> {__('general.balance_history')}
                                        </DropdownMenuItem>
                                    </DropdownMenuGroup>
                                    <div className="my-1 border-t border-slate-50" />
                                    <DropdownMenuItem 
                                        className="cursor-pointer rounded-lg text-sm text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                                        render={<Link href="/logout" method="post" as="button" className="flex items-center w-full font-medium" />}
                                    >
                                        <LogOut className="mr-2 h-4 w-4" /> {__('general.log_out')}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {children}
            </main>
            <Toaster />
        </div>
    );
}
