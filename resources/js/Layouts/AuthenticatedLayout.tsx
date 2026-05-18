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
    MessageSquare, LifeBuoy, Bookmark, Activity, Sparkles, Building2, Briefcase, Megaphone, Play, Lock, Calendar, Radar, Wrench
} from 'lucide-react';
import CommandPalette from '@/Components/CommandPalette';
import ProductTourModal from '@/Components/ProductTourModal';
import axios from 'axios';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, notifications, flash, wallet } = usePage().props as any;
    const user = auth.user;
    const { toast } = useToast();
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
    const isFreelanceActive = isRouteActive('freelance');
    const isMarketplaceActive = isRouteActive('marketplace');
    const isBookingActive = isRouteActive('booking');
    const isIntelligenceActive = isRouteActive('intelligence');
    const isToolsActive = isRouteActive('tools');
    const activeModules = auth?.active_modules || { erp: true, freelance: true, marketplace: true, booking: true, intelligence: true, tools: true };

    const [isTourOpen, setIsTourOpen] = useState(false);
    const [tourStep, setTourStep] = useState(1);

    useEffect(() => {
        if (flash?.message) {
            toast({ description: flash.message });
        }
    }, [flash]);

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


    const NavLink = ({ href, active, children }: any) => (
        <Link
            href={href}
            className={`inline-flex items-center px-3 py-2 text-sm font-medium transition-colors rounded-md ${
                active 
                ? 'bg-slate-100 text-slate-900' 
                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
            }`}
        >
            {children}
        </Link>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
            {/* Top Navigation */}
            <header className="sticky top-0 z-40 w-full bg-white border-b border-slate-200">
                <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
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
                                                <Link href={safeRoute('dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                    <LayoutDashboard className="w-5 h-5 text-slate-400" /> Dashboard
                                                </Link>
                                                <Link href={safeRoute('erp.invoices.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                    <FileText className="w-5 h-5 text-slate-400" /> Invoices
                                                </Link>
                                                <Link href={safeRoute('financial.add-balance')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50 text-emerald-700 font-medium">
                                                    <Plus className="w-5 h-5 text-emerald-500" /> Add Balance
                                                </Link>
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
                                    <NavLink href={safeRoute('dashboard')} active={isRouteActive('dashboard')}>
                                        Dashboard
                                    </NavLink>
                                    {isTourOpen && tourStep === 2 && (
                                        <span className="absolute -top-1 right-0 flex h-3 w-3">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75" />
                                            <span className="relative inline-flex rounded-full h-3 w-3 bg-indigo-500" />
                                        </span>
                                    )}
                                </div>

                                <div className="relative">
                                    <NavLink href={safeRoute('erp.invoices.index')} active={isRouteActive('erp.invoices.index')}>
                                        Invoices
                                    </NavLink>
                                    {isTourOpen && tourStep === 3 && (
                                        <span className="absolute -top-1 right-0 flex h-3 w-3">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                            <span className="relative inline-flex rounded-full h-3 w-3 bg-emerald-500" />
                                        </span>
                                    )}
                                </div>
                                
                                {/* MORE MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 rounded-md outline-none">
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

                                {/* iSAAS MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-md outline-none transition-colors">
                                            <Sparkles className="mr-1.5 h-3.5 w-3.5" /> iSAAS <ChevronDown className="ml-1 h-3.5 w-3.5 opacity-50" />
                                        </DropdownMenuTrigger>
                                        {isTourOpen && tourStep === 4 && (
                                            <span className="absolute top-1 right-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75" />
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-blue-500" />
                                            </span>
                                        )}
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[320px] p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Connected Workspaces</p>
                                        </div>
                                        
                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 mb-1 outline-none border transition-all cursor-pointer",
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
                                                    <p className={cn("text-sm font-medium", isErpActive ? "text-indigo-900" : "text-slate-900")}>Business OS</p>
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
                                                "p-0 mb-1 outline-none border transition-all cursor-pointer",
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
                                                    <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Free</span>
                                                </div>
                                                <p className={cn("text-xs truncate", isFreelanceActive ? "text-emerald-700/70" : "text-slate-500")}>
                                                    Jobs, proposals &amp; earnings
                                                </p>
                                            </div>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 mb-1 outline-none border transition-all cursor-pointer",
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

                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 mb-1 outline-none border transition-all cursor-pointer",
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
                                                    {activeModules.booking && !isBookingActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">Add-on</span>}
                                                </div>
                                                <p className={cn("text-xs truncate", isBookingActive ? "text-amber-700/70" : "text-slate-500")}>
                                                    {!activeModules.booking ? 'Subscribe to access' : 'Appointments & Availability'}
                                                </p>
                                            </div>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 mb-1 outline-none border transition-all cursor-pointer",
                                                isIntelligenceActive ? "bg-cyan-50/80 border-cyan-100" : "hover:bg-slate-50 border-transparent"
                                            )}
                                            render={<Link href={activeModules.intelligence ? safeRoute('intelligence.index') : safeRoute('subscriptions.plans', { module: 'intelligence' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                        >
                                            <div className={cn(
                                                "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                isIntelligenceActive ? "bg-cyan-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-cyan-50"
                                            )}>
                                                <Radar className={cn("w-4 h-4", isIntelligenceActive ? "text-cyan-700" : "text-slate-500 group-hover/dropdown-menu-item:text-cyan-600")} />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between">
                                                    <p className={cn("text-sm font-medium", isIntelligenceActive ? "text-cyan-900" : "text-slate-900")}>Intelligence</p>
                                                    {isIntelligenceActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-cyan-100 text-cyan-700">Active</span>}
                                                    {!activeModules.intelligence && <Lock className="w-3.5 h-3.5 text-slate-400" />}
                                                    {activeModules.intelligence && !isIntelligenceActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-cyan-100 text-cyan-700">Pro</span>}
                                                </div>
                                                <p className={cn("text-xs truncate", isIntelligenceActive ? "text-cyan-700/70" : "text-slate-500")}>
                                                    {!activeModules.intelligence ? 'Subscribe to access' : 'Market & Ad Tracking'}
                                                </p>
                                            </div>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem 
                                            className={cn(
                                                "p-0 outline-none border transition-all cursor-pointer",
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
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </nav>
                        </div>

                        {/* RIGHT: Financials, Tour Button & Profile */}
                        <div className="flex items-center gap-3">
                            <div className="hidden md:flex items-center gap-3 mr-2">
                                <Link href={safeRoute('financial.add-balance')} className="inline-flex items-center justify-center px-3 h-8 text-xs font-medium border border-slate-200 rounded-full hover:bg-slate-100 transition-colors text-slate-700">
                                    <Plus className="w-3.5 h-3.5 mr-1" /> Add Balance
                                </Link>
                                
                                <Link href={safeRoute('financial.transactions')} className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-sm font-medium text-slate-900" title="Wallet Balance">
                                    <Wallet className="w-4 h-4 text-slate-500" /> {wallet ? `${Number(wallet.balance).toFixed(2)} ${wallet.currency}` : '$0.00'}
                                </Link>
                                
                                <Link href={safeRoute('freelance.points.index')} className="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-100 rounded-full transition-colors text-sm font-medium text-amber-700" title="Points/Connects Balance">
                                    <Coins className="w-4 h-4 text-amber-500" /> {user?.points_balance !== undefined ? Number(user.points_balance).toLocaleString() : '0'}
                                </Link>
                            </div>

                            {/* Notifications */}
                            <DropdownMenu>
                                <div className="relative inline-block">
                                    <DropdownMenuTrigger className="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors relative outline-none">
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
                                <DropdownMenuTrigger className="outline-none relative">
                                    <Avatar className="h-9 w-9 border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity">
                                        <AvatarFallback className="bg-slate-900 text-white font-medium text-xs">
                                            {user?.name?.substring(0, 2).toUpperCase() || 'US'}
                                        </AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56 p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                    <div className="px-2 py-2 mb-2 border-b border-slate-50">
                                        <p className="text-sm font-medium text-slate-900 truncate">{user?.name || 'SaaS User'}</p>
                                        <p className="text-xs text-slate-500 truncate">{user?.email || 'user@example.com'}</p>
                                    </div>
                                    
                                    <DropdownMenuGroup>
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
                                            render={<Link href={safeRoute('profile.edit')} className="flex items-center w-full" />}
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
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </header>

            {/* Optional Header for context */}
            {header && (
                <div className="bg-white border-b border-slate-200 py-4 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-[1600px] mx-auto">
                        <h1 className="text-xl font-semibold tracking-tight text-slate-900">{header}</h1>
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
            <Toaster />
        </div>
    );
}
