import React, { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import { Button } from '@/Components/ui/button';
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
    MessageSquare, LifeBuoy, Bookmark, Activity, Sparkles, Building2, Briefcase, Megaphone, Play
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
                                    <DropdownMenuContent align="start" className="w-[300px] p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                            <p className="text-xs font-medium text-slate-500">Connected Workspaces</p>
                                        </div>
                                        <Link href={safeRoute('erp.clients.index')} className="flex items-start gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors group">
                                            <div className="w-8 h-8 rounded-md bg-indigo-50 flex items-center justify-center shrink-0 group-hover:bg-indigo-100 transition-colors">
                                                <Building2 className="w-4 h-4 text-indigo-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-slate-900">ERP / Business OS</p>
                                                <p className="text-xs text-slate-500">Manage clients, timers, and ledger</p>
                                            </div>
                                        </Link>
                                        <Link href={safeRoute('freelance.dashboard')} className="flex items-start gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors group mt-1">
                                            <div className="w-8 h-8 rounded-md bg-emerald-50 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 transition-colors">
                                                <Briefcase className="w-4 h-4 text-emerald-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-slate-900">Freelance Hub</p>
                                                <p className="text-xs text-slate-500">Contracts, jobs, and deliverables</p>
                                            </div>
                                        </Link>
                                        <Link href={safeRoute('marketplace.dashboard')} className="flex items-start gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors group mt-1">
                                            <div className="w-8 h-8 rounded-md bg-rose-50 flex items-center justify-center shrink-0 group-hover:bg-rose-100 transition-colors">
                                                <Megaphone className="w-4 h-4 text-rose-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-slate-900">Marketing & Sales</p>
                                                <p className="text-xs text-slate-500">Campaigns and CRM pipelines</p>
                                            </div>
                                        </Link>
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
                                
                                <Link href={safeRoute('financial.transactions')} className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-sm font-medium text-slate-900">
                                    <Wallet className="w-4 h-4 text-slate-500" /> {wallet ? `${Number(wallet.balance).toFixed(2)} ${wallet.currency}` : '$0.00'}
                                </Link>
                                
                                <div className="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-100 rounded-full transition-colors text-sm font-medium text-amber-700">
                                    <Coins className="w-4 h-4 text-amber-500" /> 1,450
                                </div>
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
                                        <Link href="#" className="text-xs text-indigo-600 hover:text-indigo-700 font-medium">Mark all read</Link>
                                    </div>
                                    <div className="max-h-[300px] overflow-y-auto p-2">
                                        <div className="px-2 py-6 text-center text-sm text-slate-500 font-light">
                                            No new notifications
                                        </div>
                                    </div>
                                    <div className="p-2 border-t border-slate-100">
                                        <Button variant="ghost" className="w-full text-xs text-slate-600">View All</Button>
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
            <main className="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
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
