import React, { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import { Button, buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuGroup
} from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { 
    Bell, ChevronDown, Wallet, Menu, Plus, Coins, LogOut, 
    Settings, User, History, Shield, CreditCard, Box, 
    LayoutDashboard, FileText, ArrowRightLeft, ArrowUpRight,
    MessageSquare, LifeBuoy, Bookmark, Activity, Sparkles, Building2, Briefcase, Megaphone, Play, Lock, Calendar, Radar, Wrench, Download
} from 'lucide-react';
import CommandPalette from '@/Components/CommandPalette';
import ProductTourModal from '@/Components/ProductTourModal';
import { FloatingQuickAdd } from '@/Components/CRM/FloatingQuickAdd';
import axios from 'axios';
import FreelanceModeToggle from '@/Components/Freelance/FreelanceModeToggle';
import MarketplaceModeToggle from '@/Components/Marketplace/MarketplaceModeToggle';
import { FlashHandler } from '@/Components/FlashHandler';

export default function Authenticated(props: PropsWithChildren<{ header?: ReactNode }>) {
    return (
        <AuthenticatedContent {...props} />
    );
}

function AuthenticatedContent({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, notifications, flash, wallet } = usePage().props as any;
    const user = auth.user;
    const { toast } = useToast();

    const isImpersonating = auth?.is_impersonating;
    const activeUser = auth?.team_member || user;
    const displayName = activeUser?.name || 'SaaS User';
    const displayEmail = activeUser?.email || 'user@example.com';
    const [isMobileOpen, setIsMobileOpen] = useState(false);

    // Safety checks for route existence
    const safeRoute = (name: string, params?: any, fallbackUrl?: string) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined' && route().has(name)) {
                // @ts-ignore
                return route(name, params);
            }
        } catch (e) {}
        return fallbackUrl || '#';
    };

    const isRouteActive = (name: string) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined') {
                // @ts-ignore
                return route().current(name) || route().current(`${name}.*`);
            }
        } catch (e) {}
        return false;
    };

    const isErpActive = isRouteActive('erp');
    const isCrmActive = isRouteActive('crm');
    const isFreelanceActive = isRouteActive('freelance');
    const isMarketplaceActive = isRouteActive('marketplace');
    const isBookingActive = isRouteActive('booking');
    const isIntelligenceActive = isRouteActive('intelligence');
    const isToolsActive = isRouteActive('tools');
    const activeModules = auth?.active_modules || { erp: true, freelance: true, marketplace: true, booking: true, tools: true, fbmb: true };

    const [isTourOpen, setIsTourOpen] = useState(false);
    const [tourStep, setTourStep] = useState(1);



    // Check if product tour should auto-open for new users
    useEffect(() => {
        if (user && user.tour_completed === false && user.tour_skipped === false) {
            setIsTourOpen(true);
            setTourStep(user.current_tour_step || 1);
        }
    }, [user]);

    const handleStartTour = () => {
        setIsTourOpen(true);
        setTourStep(1);
        axios.post('/product-tour/status', { reset: true });
    };


    // Stable NavItem: identical padding/height/font-weight in ALL states — only color changes
    const NavLink = ({ href, active, children }: any) => (
        <Link
            href={href}
            className={cn(
                // Fixed geometry — never changes on hover/active
                'inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none',
                // Color-only transition
                'transition-colors duration-150',
                active
                    ? 'bg-slate-100 text-slate-900'
                    : 'text-slate-500 hover:bg-slate-100/60 hover:text-slate-800'
            )}
        >
            {children}
        </Link>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
            {isImpersonating && (
                <div className="bg-gradient-to-r from-amber-500 via-orange-600 to-rose-600 text-white text-xs font-semibold px-4 shadow-md flex items-center justify-between z-[50] sticky top-0" style={{ height: '36px' }}>
                    <div className="flex items-center gap-2">
                        <Shield className="h-4 w-4 text-amber-100 animate-pulse shrink-0" />
                        <span className="truncate">
                            You are currently impersonating <strong className="underline">{user?.name}</strong> ({user?.email}). All actions performed will be under this account's scope.
                        </span>
                    </div>
                    <Link
                        href={route('admin.stop-impersonate')}
                        className="bg-white/20 hover:bg-white/30 text-white font-bold py-1 px-3 rounded-full border border-white/20 hover:border-white/40 transition-all text-[11px] shrink-0"
                    >
                        Stop Impersonation
                    </Link>
                </div>
            )}

            {/* Top Navigation */}
            <header className={cn("sticky z-40 w-full bg-white border-b border-slate-200", isImpersonating ? "top-[36px]" : "top-0")} style={{ height: '68px' }}>
                <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-full">
                    <div className="flex h-full items-center justify-between">
                        {/* LEFT: Logo & Nav */}
                        <div className="flex items-center gap-6">
                            {/* Mobile Menu Trigger */}
                            <Sheet open={isMobileOpen} onOpenChange={setIsMobileOpen}>
                                <SheetTrigger asChild>
                                    <Button variant="ghost" size="icon" className="md:hidden -ml-2 text-slate-500">
                                        <Menu className="h-5 w-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent side="left" className="w-[300px] sm:w-[350px] p-0 bg-white">
                                    <div className="flex flex-col h-full">
                                        <div className="p-4 border-b border-slate-100">
                                            <Link href="/" className="flex items-center gap-2" onClick={() => setIsMobileOpen(false)}>
                                                <div className="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                                                    <ApplicationLogo className="w-5 h-5 text-white" />
                                                </div>
                                                <span className="font-semibold text-lg">musoftware</span>
                                            </Link>
                                        </div>
                                        <div className="flex-1 overflow-y-auto p-4 space-y-6">
                                            {/* Mobile Nav Links */}
                                            <div className="space-y-1">
                                                {auth?.team_member ? (
                                                    <Link href={safeRoute('erp.dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                        <LayoutDashboard className="w-5 h-5 text-slate-400" /> Dashboard
                                                    </Link>
                                                ) : (
                                                    <>
                                                        <Link href={safeRoute('dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                            <LayoutDashboard className="w-5 h-5 text-slate-400" /> Dashboard
                                                        </Link>
                                                        <Link href={activeModules.erp ? safeRoute('erp.dashboard') : safeRoute('subscriptions.plans', { module: 'erp' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                            <Building2 className="w-5 h-5 text-slate-400" /> ERP
                                                        </Link>
                                                        <Link href={safeRoute('erp.client-invoices.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                            <FileText className="w-5 h-5 text-slate-400" /> My Invoices
                                                        </Link>
                                                        <Link href={safeRoute('financial.add-balance')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50 text-emerald-700 font-medium">
                                                            <Plus className="w-5 h-5 text-emerald-500" /> Add Balance
                                                        </Link>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </SheetContent>
                            </Sheet>

                            <Link href="/" className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                                    <ApplicationLogo className="w-5 h-5 text-white fill-current" />
                                </div>
                                <span className="font-medium text-lg hidden sm:block tracking-tight">musoftware</span>
                            </Link>

                            <nav className="hidden md:flex items-center gap-1">
                                <div className="relative">
                                    <NavLink 
                                        href={auth?.team_member ? safeRoute('erp.dashboard') : safeRoute('dashboard')} 
                                        active={auth?.team_member ? isRouteActive('erp') : isRouteActive('dashboard')}
                                    >
                                        Dashboard
                                    </NavLink>
                                    {(!auth?.team_member && isTourOpen && tourStep === 2) && (
                                        <span className="absolute -top-1 right-0 flex h-3 w-3">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75" />
                                            <span className="relative inline-flex rounded-full h-3 w-3 bg-indigo-500" />
                                        </span>
                                    )}
                                </div>
                                
                                {!auth?.team_member && (
                                    <>
                                        {/* MORE MEGA MENU */}
                                        <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none text-slate-500 hover:bg-slate-100/60 hover:text-slate-800 transition-colors duration-150 outline-none select-none">
                                            More <ChevronDown className="ml-1 h-3.5 w-3.5 opacity-50" />
                                        </DropdownMenuTrigger>
                                        {isTourOpen && tourStep === 5 && (
                                            <span className="absolute top-1 right-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75" />
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-purple-500" />
                                            </span>
                                        )}
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[450px] p-4 grid grid-cols-2 gap-4 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        <div>
                                            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 px-2">Financial</h4>
                                            <div className="space-y-0.5">
                                                <Link href={safeRoute('financial.transactions')} className="flex items-center gap-3 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm font-medium text-slate-700">
                                                    <ArrowRightLeft className="w-4 h-4 text-slate-400" /> Transactions
                                                </Link>
                                                <Link href={safeRoute('financial.withdrawals')} className="flex items-center gap-3 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm font-medium text-slate-700">
                                                    <ArrowUpRight className="w-4 h-4 text-slate-400" /> Request Withdrawal
                                                </Link>
                                                <Link href={safeRoute('financial.payout-methods.index')} className="flex items-center gap-3 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm font-medium text-slate-700">
                                                    <CreditCard className="w-4 h-4 text-slate-400" /> Payout Methods
                                                </Link>
                                                <Link href={safeRoute('erp.client-invoices.index')} className="flex items-center gap-3 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm font-medium text-slate-700">
                                                    <FileText className="w-4 h-4 text-slate-400" /> My Invoices
                                                </Link>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2 px-2">Support & Workspace</h4>
                                            <div className="space-y-0.5">
                                                <Link href={safeRoute('tickets.index')} className="flex items-center gap-3 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm font-medium text-slate-700">
                                                    <LifeBuoy className="w-4 h-4 text-slate-400" /> Support Tickets
                                                </Link>
                                                <Link href={safeRoute('messages.index')} className="flex items-center gap-3 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm font-medium text-slate-700">
                                                    <MessageSquare className="w-4 h-4 text-slate-400" /> Messages
                                                </Link>
                                            </div>
                                        </div>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                {/* SERVICES MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors duration-150 outline-none select-none">
                                            <Briefcase className="mr-1.5 h-3.5 w-3.5" /> Services <ChevronDown className="ml-1 h-3.5 w-3.5 opacity-50" />
                                        </DropdownMenuTrigger>
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[320px] p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Service Hubs</p>
                                        </div>
                                        
                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 mb-1 outline-none border transition-colors duration-150 cursor-pointer",
                                                isFreelanceActive ? "bg-emerald-50/80 border-emerald-100" : "hover:bg-slate-50 border-transparent"
                                            )}
                                            render={<Link href={safeRoute('freelance.dashboard')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                        >
                                            <div className={cn(
                                                "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                isFreelanceActive ? "bg-emerald-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-emerald-50"
                                            )}>
                                                <Briefcase className={cn("w-4 h-4", isFreelanceActive ? "text-emerald-700" : "text-slate-500 group-hover/dropdown-menu-item:text-emerald-600")} />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between">
                                                    <p className={cn("text-sm font-medium", isFreelanceActive ? "text-emerald-900" : "text-slate-900")}>Freelance Hub</p>
                                                    <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Points-Based</span>
                                                </div>
                                                <p className={cn("text-xs truncate", isFreelanceActive ? "text-emerald-700/70" : "text-slate-500")}>
                                                    Jobs, proposals &amp; earnings
                                                </p>
                                            </div>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                isMarketplaceActive ? "bg-violet-50/80 border-violet-100" : "hover:bg-slate-50 border-transparent"
                                            )}
                                            render={<Link href={safeRoute('marketplace.dashboard')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                        >
                                            <div className={cn(
                                                "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                isMarketplaceActive ? "bg-violet-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-violet-50"
                                            )}>
                                                <Megaphone className={cn("w-4 h-4", isMarketplaceActive ? "text-violet-700" : "text-slate-500 group-hover/dropdown-menu-item:text-violet-600")} />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between">
                                                    <p className={cn("text-sm font-medium", isMarketplaceActive ? "text-violet-900" : "text-slate-900")}>Marketing Suite</p>
                                                    <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-violet-100 text-violet-700">Free</span>
                                                </div>
                                                <p className={cn("text-xs truncate", isMarketplaceActive ? "text-violet-700/70" : "text-slate-500")}>
                                                    Services, clients &amp; campaigns
                                                </p>
                                            </div>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                {/* iSAAS MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 transition-colors duration-150 outline-none select-none">
                                            <Sparkles className="mr-1.5 h-3.5 w-3.5" /> iSAAS <ChevronDown className="ml-1 h-3.5 w-3.5 opacity-50" />
                                        </DropdownMenuTrigger>
                                        {isTourOpen && tourStep === 4 && (
                                            <span className="absolute top-1 right-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75" />
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-blue-500" />
                                            </span>
                                        )}
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[640px] p-4 grid grid-cols-2 gap-4 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        {/* Column 1: Core Systems */}
                                        <div className="flex flex-col gap-1">
                                            <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Core Systems</p>
                                            </div>
                                            
                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isErpActive ? "bg-indigo-50/80 border-indigo-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={activeModules.erp ? safeRoute('erp.dashboard') : safeRoute('subscriptions.plans', { module: 'erp' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isErpActive ? "bg-indigo-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-indigo-50"
                                                )}>
                                                    <Building2 className={cn("w-4 h-4", isErpActive ? "text-indigo-700" : "text-slate-500 group-hover/dropdown-menu-item:text-indigo-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isErpActive ? "text-indigo-900" : "text-slate-900")}>ERP</p>
                                                        {isErpActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Active</span>}
                                                        {!activeModules.erp && <Lock className="w-3.5 h-3.5 text-slate-400" />}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isErpActive ? "text-indigo-700/70" : "text-slate-500")}>
                                                        {!activeModules.erp ? 'Subscribe to access' : 'Clients, invoices, timers'}
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isCrmActive ? "bg-indigo-50/80 border-indigo-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('crm.dashboard')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isCrmActive ? "bg-indigo-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-indigo-50"
                                                )}>
                                                    <Megaphone className={cn("w-4 h-4", isCrmActive ? "text-indigo-700" : "text-slate-500 group-hover/dropdown-menu-item:text-indigo-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isCrmActive ? "text-indigo-900" : "text-slate-900")}>Lead Gen CRM</p>
                                                        {isCrmActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Active</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isCrmActive ? "text-indigo-700/70" : "text-slate-500")}>
                                                        Capture leads and manage campaigns
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isBookingActive ? "bg-amber-50/80 border-amber-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={activeModules.booking ? safeRoute('booking.index') : safeRoute('subscriptions.plans', { module: 'booking' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isBookingActive ? "bg-amber-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-amber-50"
                                                )}>
                                                    <Calendar className={cn("w-4 h-4", isBookingActive ? "text-amber-700" : "text-slate-500 group-hover/dropdown-menu-item:text-amber-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isBookingActive ? "text-amber-900" : "text-slate-900")}>Booking</p>
                                                        {isBookingActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">Active</span>}
                                                        {!activeModules.booking && <Lock className="w-3.5 h-3.5 text-slate-400" />}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isBookingActive ? "text-amber-700/70" : "text-slate-500")}>
                                                        {!activeModules.booking ? 'Subscribe to access' : 'Appointments & Availability'}
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>
                                        </div>

                                        {/* Column 2: Tools */}
                                        <div className="flex flex-col gap-1">
                                            <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Tools</p>
                                            </div>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isRouteActive('fbmb.index') ? "bg-teal-50/80 border-teal-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('fbmb.index')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isRouteActive('fbmb.index') ? "bg-teal-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-teal-50"
                                                )}>
                                                    <Activity className={cn("w-4 h-4", isRouteActive('fbmb.index') ? "text-teal-700" : "text-slate-500 group-hover/dropdown-menu-item:text-teal-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isRouteActive('fbmb.index') ? "text-teal-900" : "text-slate-900")}>iSAAS FB Lookup</p>
                                                        {isRouteActive('fbmb.index') && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-teal-100 text-teal-700">Active</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('fbmb.index') ? "text-teal-700/70" : "text-slate-500")}>
                                                        Search Mobile by FBID
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isRouteActive('sms-payment-gateway.index') ? "bg-rose-50/80 border-rose-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('sms-payment-gateway.index')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isRouteActive('sms-payment-gateway.index') ? "bg-rose-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-rose-50"
                                                )}>
                                                    <MessageSquare className={cn("w-4 h-4", isRouteActive('sms-payment-gateway.index') ? "text-rose-700" : "text-slate-500 group-hover/dropdown-menu-item:text-rose-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isRouteActive('sms-payment-gateway.index') ? "text-rose-900" : "text-slate-900")}>Payment Gateway</p>
                                                        {isRouteActive('sms-payment-gateway.index') && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700">Active</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('sms-payment-gateway.index') ? "text-rose-700/70" : "text-slate-500")}>
                                                        Android automated SMS
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isRouteActive('isaas.gold-savers.index') ? "bg-yellow-50/80 border-yellow-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('isaas.gold-savers.index')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isRouteActive('isaas.gold-savers.index') ? "bg-yellow-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-yellow-50"
                                                )}>
                                                    <Coins className={cn("w-4 h-4", isRouteActive('isaas.gold-savers.index') ? "text-yellow-700" : "text-slate-500 group-hover/dropdown-menu-item:text-yellow-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isRouteActive('isaas.gold-savers.index') ? "text-yellow-900" : "text-slate-900")}>Gold Savers</p>
                                                        {isRouteActive('isaas.gold-savers.index') && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-yellow-100 text-yellow-700">Active</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('isaas.gold-savers.index') ? "text-yellow-700/70" : "text-slate-500")}>
                                                        Track your gold value
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isToolsActive ? "bg-fuchsia-50/80 border-fuchsia-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('tools.explore')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isToolsActive ? "bg-fuchsia-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-fuchsia-50"
                                                )}>
                                                    <Wrench className={cn("w-4 h-4", isToolsActive ? "text-fuchsia-700" : "text-slate-500 group-hover/dropdown-menu-item:text-fuchsia-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isToolsActive ? "text-fuchsia-900" : "text-slate-900")}>Tools &amp; Plugins</p>
                                                        {isToolsActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-fuchsia-100 text-fuchsia-700">Active</span>}
                                                        {!isToolsActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">Free Browse</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isToolsActive ? "text-fuchsia-700/70" : "text-slate-500")}>
                                                        Extensions &amp; Licensing
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>
                                        </div>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <NavLink href={safeRoute('subscriptions.plans')} active={isRouteActive('subscriptions')}>
                                    <CreditCard className="h-3.5 w-3.5 text-amber-500" /> Subscription
                                </NavLink>
                                    </>
                                )}
                            </nav>
                        </div>

                        {/* RIGHT: Financials, Tour Button & Profile */}
                        <div className="flex items-center gap-3">
                            {isFreelanceActive && (
                                <div className="mr-1 sm:mr-2">
                                    <FreelanceModeToggle />
                                </div>
                            )}
                            {isMarketplaceActive && (
                                <div className="mr-1 sm:mr-2">
                                    <MarketplaceModeToggle />
                                </div>
                            )}
                            {!auth?.team_member && (
                                <div className="hidden md:flex items-center gap-2 mr-2">
                                    {/* Add Balance pill — locked geometry */}
                                    <Link
                                        href={safeRoute('financial.add-balance')}
                                        className="inline-flex items-center gap-1 h-8 min-w-[105px] justify-center px-3 text-xs font-medium border border-slate-200 rounded-full text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors duration-150"
                                    >
                                        <Plus className="w-3.5 h-3.5 shrink-0" />
                                        <span>Add Balance</span>
                                    </Link>

                                    {/* Wallet pill — locked geometry */}
                                    <div className="relative">
                                        <Link
                                            href={safeRoute('financial.transactions')}
                                            className="inline-flex items-center gap-1.5 h-8 min-w-[90px] justify-center px-3 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors duration-150 text-xs font-medium text-slate-900"
                                            title="Wallet Balance"
                                        >
                                            <Wallet className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                            <span>{wallet ? `${Number(wallet.balance).toFixed(2)} ${wallet.currency}` : '$0.00'}</span>
                                        </Link>
                                        {isTourOpen && tourStep === 3 && (
                                            <span className="absolute -top-1 -right-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-emerald-500" />
                                            </span>
                                        )}
                                    </div>

                                    {/* Points pill — locked geometry */}
                                    <Link
                                        href={safeRoute('points.index', undefined, '/points')}
                                        className="inline-flex items-center gap-1.5 h-8 min-w-[60px] justify-center px-3 bg-amber-50 hover:bg-amber-100 border border-amber-100/80 rounded-full transition-colors duration-150 text-xs font-medium text-amber-700"
                                        title="Points/Connects Balance"
                                    >
                                        <Coins className="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                        <span>{user?.points_balance !== undefined ? Number(user.points_balance).toLocaleString() : '0'}</span>
                                    </Link>
                                </div>
                            )}

                            {/* Notifications */}
                            <DropdownMenu>
                                <div className="relative inline-block">
                                    <DropdownMenuTrigger className="w-9 h-9 rounded-full inline-flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors duration-150 relative outline-none shrink-0">
                                        <Bell className="w-5 h-5" />
                                        {notifications?.unread_count > 0 && (
                                            <span className="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border-2 border-white" />
                                        )}
                                    </DropdownMenuTrigger>
                                    {isTourOpen && tourStep === 6 && (
                                        <span className="absolute top-0 right-0 flex h-3 w-3">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75" />
                                            <span className="relative inline-flex rounded-full h-3 w-3 bg-rose-500" />
                                        </span>
                                    )}
                                </div>
                                <DropdownMenuContent align="end" className="w-80 p-0 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                    <div className="px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                                        <span className="font-semibold text-slate-900 text-sm">Notifications</span>
                                        {notifications?.unread_count > 0 && (
                                            <Link 
                                                href={safeRoute('notifications.mark-all-read')} 
                                                method="post" 
                                                as="button" 
                                                className="text-xs text-indigo-600 hover:text-indigo-700 font-medium bg-transparent border-0 cursor-pointer p-0"
                                            >
                                                Mark all read
                                            </Link>
                                        )}
                                    </div>
                                    <div className="max-h-[300px] overflow-y-auto p-2">
                                        {notifications?.recent && notifications.recent.length > 0 ? (
                                            notifications.recent.map((n: any) => (
                                                <div key={n.id} className="p-2 hover:bg-slate-50 rounded-lg text-xs flex justify-between items-start gap-2 border-b border-slate-50 last:border-0">
                                                    <div className="flex-1">
                                                        <p className="text-slate-800 font-medium">{n.data?.message || n.data?.title || 'New Notification'}</p>
                                                        <span className="text-[10px] text-slate-400">{n.created_at ? new Date(n.created_at).toLocaleDateString() : ''}</span>
                                                    </div>
                                                    <Link 
                                                        href={safeRoute('notifications.mark-read', { id: n.id })} 
                                                        method="post" 
                                                        as="button" 
                                                        className="text-[10px] text-indigo-600 hover:underline shrink-0 bg-transparent border-0 cursor-pointer"
                                                    >
                                                        Mark read
                                                    </Link>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="px-2 py-6 text-center text-sm text-slate-500 font-light">
                                                No new notifications
                                            </div>
                                        )}
                                    </div>
                                    <div className="p-2 border-t border-slate-100">
                                        <Link 
                                            href={safeRoute('notifications.index')} 
                                            className={cn(buttonVariants({ variant: 'ghost', size: 'sm' }), "w-full text-xs text-slate-600 justify-center")}
                                        >
                                            View All
                                        </Link>
                                    </div>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {/* Profile */}
                            <DropdownMenu>
                                <DropdownMenuTrigger className="outline-none relative shrink-0">
                                    <Avatar className="h-9 w-9 border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity duration-150">
                                        <AvatarFallback className="bg-slate-900 text-white font-medium text-xs">
                                            {displayName.substring(0, 2).toUpperCase() || 'US'}
                                        </AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56 p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                    <div className="px-2 py-2 mb-2 border-b border-slate-50">
                                        <p className="text-sm font-medium text-slate-900 truncate">{displayName}</p>
                                        <p className="text-xs text-slate-500 truncate">{displayEmail}</p>
                                    </div>
                                    
                                    {!auth?.team_member ? (
                                        <>
                                            <DropdownMenuGroup>
                                                {user?.role === 'admin' && (
                                                    <DropdownMenuItem 
                                                        className="cursor-pointer rounded-lg text-sm bg-indigo-50 text-indigo-700 focus:bg-indigo-100 focus:text-indigo-800 mb-1"
                                                        render={<Link href={safeRoute('admin.dashboard')} className="flex items-center w-full font-medium" />}
                                                    >
                                                        <Shield className="mr-2 h-4 w-4 text-indigo-600" /> Admin Dashboard
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('profile.edit')} className="flex items-center w-full" />}
                                                >
                                                    <User className="mr-2 h-4 w-4 text-slate-400" /> My Profile
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('kyc.index')} className="flex items-center w-full" />}
                                                >
                                                    <Shield className="mr-2 h-4 w-4 text-slate-400" /> Identity Verification
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('financial.transactions')} className="flex items-center w-full" />}
                                                >
                                                    <History className="mr-2 h-4 w-4 text-slate-400" /> Balance History
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('profile.edit')} className="flex items-center w-full" />}
                                                >
                                                    <Shield className="mr-2 h-4 w-4 text-slate-400" /> Security Settings
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('settings.backup.index')} className="flex items-center w-full" />}
                                                >
                                                    <Download className="mr-2 h-4 w-4 text-slate-400" /> Backup & Restore
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('subscriptions.manage')} className="flex items-center w-full" />}
                                                >
                                                    <Box className="mr-2 h-4 w-4 text-slate-400" /> Subscriptions
                                                </DropdownMenuItem>
                                            </DropdownMenuGroup>
                                            
                                            <DropdownMenuSeparator className="my-1 bg-slate-100" />
                                            
                                            <DropdownMenuItem 
                                                className="cursor-pointer rounded-lg text-sm text-rose-600 focus:text-rose-600 focus:bg-rose-50"
                                                render={<Link href={safeRoute('logout')} method="post" as="button" className="flex items-center w-full font-medium" />}
                                            >
                                                <LogOut className="mr-2 h-4 w-4" /> Logout
                                            </DropdownMenuItem>
                                        </>
                                    ) : (
                                        <DropdownMenuItem 
                                            className="cursor-pointer rounded-lg text-sm text-rose-600 focus:text-rose-600 focus:bg-rose-50"
                                            render={<Link href={route('erp.team.logout')} method="post" as="button" className="flex items-center w-full font-medium" />}
                                        >
                                            <LogOut className="mr-2 h-4 w-4" /> Logout Team Member
                                        </DropdownMenuItem>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </header>

            {/* Optional Header for context */}
            {header && (
                <div className="bg-white border-b border-slate-200 py-4 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-full mx-auto">
                        <div className="text-xl font-semibold tracking-tight text-slate-900">{header}</div>
                    </div>
                </div>
            )}

            {/* Main Content */}
            <main className="flex-1 w-full relative">
                {children}
            </main>

            <ProductTourModal 
                user={user}
                isOpen={isTourOpen}
                onClose={() => setIsTourOpen(false)}
                currentStep={tourStep}
                onStepChange={setTourStep}
            />

            <CommandPalette />
            <FloatingQuickAdd />
            <FlashHandler />
            <Toaster />
        </div>
    );
}
