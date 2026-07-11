import React, { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import { Button, buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
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
    Settings, User, Users, History, Shield, CreditCard, Box, 
    LayoutDashboard, FileText, ArrowRightLeft, ArrowUpRight,
    MessageSquare, LifeBuoy, Bookmark, Activity, Sparkles, Building2, Briefcase, Megaphone, Play, Lock, Calendar, Radar, Wrench, Download,
    FolderKanban,
    ListTodo
} from 'lucide-react';
import CommandPalette from '@/Components/CommandPalette';
import ProductTourModal from '@/Components/ProductTourModal';
import BackgroundTaskStatus from '@/Components/Tools/BackgroundTaskStatus';

import axios from 'axios';
import MarketplaceModeToggle from '@/Components/Marketplace/MarketplaceModeToggle';
import { __ } from '@/lib/i18n';
import { useFCM } from '@/hooks/useFCM';

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
    useInertiaNotifications();
    const { permission, requestPermission } = useFCM();
    const user = auth.user;
    const { toast } = useToast();

    const isImpersonating = auth?.is_impersonating;
    const activeUser = auth?.team_member || user;
    const displayName = activeUser?.name || 'SaaS User';
    const displayEmail = activeUser?.email || 'user@example.com';
    const [isMobileOpen, setIsMobileOpen] = useState(false);
    const [isIOS, setIsIOS] = useState(false);

    useEffect(() => {
        setIsIOS(
            typeof window !== 'undefined' &&
            /iPad|iPhone|iPod/.test(navigator.userAgent) &&
            !(window as any).MSStream
        );
    }, []);

    // Safety checks for route existence
    const safeRoute = (name: string, params?: any, fallbackUrl?: string) => {
        try {
            if (typeof route !== 'undefined' && route().has(name)) {
                return route(name, params);
            }
        } catch (e) { /* empty */ }
        return fallbackUrl || '#';
    };

    const isRouteActive = (name: string) => {
        try {
            if (typeof route !== 'undefined') {
                return route().current(name) || route().current(`${name}.*`);
            }
        } catch (e) { /* empty */ }
        return false;
    };

    const isErpActive = isRouteActive('erp');
    const isCrmActive = isRouteActive('crm');
    const isMarketplaceActive = isRouteActive('marketplace');
    const isBookingActive = isRouteActive('booking');
    const isIntelligenceActive = isRouteActive('intelligence');
    const isToolsActive = isRouteActive('tools');
    const isWorkspaceActive = 
        isRouteActive('client.projects') || 
        isRouteActive('financial') || 
        isRouteActive('billing') || 
        isRouteActive('referrals') || 
        isRouteActive('tickets') || 
        isRouteActive('messages');
    const activeModules = auth?.active_modules || { erp: true, marketplace: true, booking: true, tools: true, fbmb: true };

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
                    <div className="flex items-center gap-2 min-w-0">
                        <Shield className="h-4 w-4 text-amber-100 animate-pulse shrink-0" />
                        <span className="truncate">
                            <span className="hidden sm:inline">{__('general.you_are_currently_impersonating')}</span>
                            <span className="sm:hidden">{__('general.impersonating_user')}</span>
                            <strong className="underline mx-1">{user?.name}</strong> 
                            <span className="hidden md:inline">({user?.email}). {__('general.impersonation_scope_notice')}</span>
                        </span>
                    </div>
                    <Link
                        href={route('admin.stop-impersonate')}
                        className="bg-white/20 hover:bg-white/30 text-white font-bold py-1 px-3 rounded-full border border-white/20 hover:border-white/40 transition-all text-[11px] shrink-0 ms-2"
                    >{__('general.stop_impersonation')}</Link>
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
                                    <Button variant="ghost" size="icon" className="md:hidden -ms-2 text-slate-500">
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
                                                    <Link href={safeRoute('sso.redirect', { system: 'erp' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                        <LayoutDashboard className="w-5 h-5 text-slate-400" /> {__('general.dashboard')}</Link>
                                                ) : (
                                                    <>
                                                        <Link href={safeRoute('dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                            <LayoutDashboard className="w-5 h-5 text-slate-400" /> {__('general.dashboard')}</Link>
                                                        <Link href={activeModules.erp ? safeRoute('sso.redirect', { system: 'erp' }) : safeRoute('subscriptions.plans', { module: 'erp' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                            <Building2 className="w-5 h-5 text-slate-400" /> {__('general.erp')}</Link>
                                                        <Link href={safeRoute('financial.add-balance')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50 text-emerald-700 font-medium">
                                                            <Plus className="w-5 h-5 text-emerald-500" />{__('general.add_balance')}
                                                        </Link>
 
                                                        <div className="mt-4 pt-2 border-t border-slate-100">
                                                            <Accordion className="w-full">
                                                                <AccordionItem value="workspace" className="border-b-0">
                                                                    <AccordionTrigger className="px-3 py-2 hover:bg-slate-50 rounded-lg text-slate-700 hover:no-underline">
                                                                        <div className="flex items-center gap-3 font-medium">
                                                                            <FolderKanban className="w-5 h-5 text-slate-400" /> {__('general.workspace')}</div>
                                                                    </AccordionTrigger>
                                                                    <AccordionContent className="pb-1 px-2">
                                                                        <div className="flex flex-col space-y-1 mt-1 border-s-2 border-slate-100 ms-5 ps-4">
                                                                            <Link href={safeRoute('client.projects.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <FolderKanban className="w-4 h-4 text-slate-400" /> {__('general.projects')}</Link>
                                                                            <Link href={safeRoute('client.projects.all-tasks')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <ListTodo className="w-4 h-4 text-slate-400" /> {__('general.all_tasks')}</Link>
                                                                            <Link href={safeRoute('messages.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <MessageSquare className="w-4 h-4 text-slate-400" /> {__('general.messages')}</Link>
                                                                            <Link href={safeRoute('billing.invoices.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <FileText className="w-4 h-4 text-slate-400" />{__('general.my_invoices')}</Link>
                                                                            <Link href={safeRoute('financial.transactions')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <ArrowRightLeft className="w-4 h-4 text-slate-400" /> {__('general.transactions')}</Link>
                                                                            <Link href={safeRoute('financial.withdrawals')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <ArrowUpRight className="w-4 h-4 text-slate-400" />{__('general.request_withdrawal')}</Link>
                                                                            <Link href={safeRoute('financial.payout-methods.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <CreditCard className="w-4 h-4 text-slate-400" />{__('general.payout_methods')}</Link>
                                                                            <Link href={safeRoute('tickets.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-600 font-medium">
                                                                                <LifeBuoy className="w-4 h-4 text-slate-400" />{__('general.support_tickets')}</Link>
                                                                        </div>
                                                                    </AccordionContent>
                                                                </AccordionItem>

                                                                <AccordionItem value="services" className="border-b-0">
                                                                    <AccordionTrigger className="px-3 py-2 hover:bg-slate-50 rounded-lg text-emerald-700 hover:no-underline">
                                                                        <div className="flex items-center gap-3 font-medium">
                                                                            <Briefcase className="w-5 h-5 text-emerald-600" /> {__('general.services')}</div>
                                                                    </AccordionTrigger>
                                                                    <AccordionContent className="pb-1 px-2">
                                                                        <div className="flex flex-col space-y-1 mt-1 border-s-2 border-slate-100 ms-5 ps-4">
                                                                            <Link href={safeRoute('marketplace.dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-violet-50/50 text-slate-600 font-medium">
                                                                                <Megaphone className="w-4 h-4 text-violet-500" /> {__('general.marketing_suite')}
                                                                            </Link>
                                                                            {user?.roles?.includes('seller') && (
                                                                                <Link href={safeRoute('seller.dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-amber-50/50 text-slate-600 font-medium">
                                                                                    <Building2 className="w-4 h-4 text-amber-500" /> Seller Portal
                                                                                </Link>
                                                                            )}
                                                                        </div>
                                                                    </AccordionContent>
                                                                </AccordionItem>

                                                                <AccordionItem value="isaas" className="border-b-0">
                                                                    <AccordionTrigger className="px-3 py-2 hover:bg-slate-50 rounded-lg text-indigo-700 hover:no-underline">
                                                                        <div className="flex items-center gap-3 font-medium">
                                                                            <Sparkles className="w-5 h-5 text-indigo-600" /> iSAAS
                                                                        </div>
                                                                    </AccordionTrigger>
                                                                    <AccordionContent className="pb-1 px-2">
                                                                        <div className="flex flex-col space-y-1 mt-1 border-s-2 border-slate-100 ms-5 ps-4">
                                                                            <Link href={activeModules.crm ? safeRoute('sso.redirect', { system: 'crm' }) : safeRoute('subscriptions.plans', { module: 'crm' })} onClick={() => setIsMobileOpen(false)} className="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-indigo-50/50 text-slate-600 font-medium">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Megaphone className="w-4 h-4 text-indigo-500" /> {__('general.lead_gen_crm')}
                                                                                </div>
                                                                                {!activeModules.crm && <Lock className="w-3 h-3 text-slate-400" />}
                                                                            </Link>
                                                                            <Link href={activeModules.booking ? safeRoute('sso.redirect', { system: 'bookingsys' }) : safeRoute('subscriptions.plans', { module: 'booking' })} onClick={() => setIsMobileOpen(false)} className="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-amber-50/50 text-slate-600 font-medium">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Calendar className="w-4 h-4 text-amber-500" /> {__('general.booking')}</div>
                                                                                {!activeModules.booking && <Lock className="w-3 h-3 text-slate-400" />}
                                                                            </Link>
                                                                            <Link href={safeRoute('fbmb.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-teal-50/50 text-slate-600 font-medium">
                                                                                <Activity className="w-4 h-4 text-teal-500" /> {__('general.isaas_fb_lookup')}
                                                                            </Link>
                                                                            <Link href={safeRoute('sms-payment-gateway.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-rose-50/50 text-slate-600 font-medium">
                                                                                <MessageSquare className="w-4 h-4 text-rose-500" /> {__('general.payment_gateway')}
                                                                            </Link>
                                                                            <Link href={safeRoute('sso.redirect', { system: 'goldsaversys' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-50/50 text-slate-600 font-medium">
                                                                                <Coins className="w-4 h-4 text-yellow-500" /> {__('general.gold_savers')}
                                                                            </Link>
                                                                            <Link href={safeRoute('sso.redirect', { system: 'toolsys' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-fuchsia-50/50 text-slate-600 font-medium">
                                                                                <Wrench className="w-4 h-4 text-fuchsia-500" /> {__('general.tools_amp_plugins')}
                                                                            </Link>
                                                                        </div>
                                                                    </AccordionContent>
                                                                </AccordionItem>

                                                            </Accordion>
                                                        </div>

                                                        <Link href={safeRoute('subscriptions.plans')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 mt-2 rounded-lg hover:bg-amber-50 text-amber-700 font-medium">
                                                            <CreditCard className="w-5 h-5 text-amber-500" /> {__('general.subscription')}</Link>
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
                                        href={auth?.team_member ? safeRoute('sso.redirect', { system: 'erp' }) : safeRoute('dashboard')} 
                                        active={auth?.team_member ? isRouteActive('erp') : isRouteActive('dashboard')}
                                    >
                                        {__('general.dashboard')}</NavLink>
                                    {(!auth?.team_member && isTourOpen && tourStep === 2) && (
                                        <span className="absolute -top-1 end-0 flex h-3 w-3">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75" />
                                            <span className="relative inline-flex rounded-full h-3 w-3 bg-indigo-500" />
                                        </span>
                                    )}
                                </div>
                                
                                {!auth?.team_member && (
                                    <>
                                        <DropdownMenu>
                                            <div className="relative inline-block">
                                                <DropdownMenuTrigger className={cn(
                                                    "inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none transition-colors duration-150 outline-none select-none",
                                                    isWorkspaceActive 
                                                        ? "bg-slate-100/80 text-slate-900" 
                                                        : "text-slate-500 hover:bg-slate-100/60 hover:text-slate-800"
                                                )}>
                                                    {__('general.workspace')}<ChevronDown className="ms-1 h-3.5 w-3.5 opacity-50" />
                                                </DropdownMenuTrigger>
                                                {isTourOpen && (tourStep === 2 || tourStep === 5) && (
                                                    <span className="absolute top-1 end-1 flex h-3 w-3">
                                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75" />
                                                        <span className="relative inline-flex rounded-full h-3 w-3 bg-purple-500" />
                                                    </span>
                                                )}
                                            </div>
                                            <DropdownMenuContent align="start" className="w-[820px] p-4 grid grid-cols-3 gap-6 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                                {/* Column 1: Projects & Collaboration */}
                                                <div className="flex flex-col gap-1">
                                                    <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.projects_and_collaboration')}</p>
                                                    </div>
                                                    
                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg",
                                                            isRouteActive('client.projects') && !route().current('client.projects.all-tasks')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('client.projects.index')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <FolderKanban className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.projects')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.projects_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg mt-1",
                                                            route().current('client.projects.all-tasks')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('client.projects.all-tasks')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <ListTodo className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.all_tasks')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.all_tasks_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg mt-1",
                                                            isRouteActive('messages')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('messages.index')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <MessageSquare className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.messages')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.messages_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>
                                                </div>

                                                {/* Column 2: Financials */}
                                                <div className="flex flex-col gap-1">
                                                    <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.financials')}</p>
                                                    </div>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg",
                                                            isRouteActive('billing.invoices')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('billing.invoices.index')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <FileText className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.my_invoices')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.my_invoices_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg mt-1",
                                                            isRouteActive('financial.transactions')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('financial.transactions')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <ArrowRightLeft className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.transactions')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.transactions_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg mt-1",
                                                            isRouteActive('financial.withdrawals')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('financial.withdrawals')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <ArrowUpRight className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.request_withdrawal')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.request_withdrawal_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg mt-1",
                                                            isRouteActive('financial.payout-methods')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('financial.payout-methods.index')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <CreditCard className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.payout_methods')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.payout_methods_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg mt-1",
                                                            isRouteActive('referrals')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('referrals.index')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <Users className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.referrals')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.referrals_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>
                                                </div>

                                                {/* Column 3: Support */}
                                                <div className="flex flex-col gap-1">
                                                    <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.support')}</p>
                                                    </div>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-lg",
                                                            isRouteActive('tickets')
                                                                ? "bg-slate-50 border-slate-100" 
                                                                : "hover:bg-slate-50 border-transparent"
                                                        )}
                                                        render={<Link href={safeRoute('tickets.index')} className="flex items-start gap-3 p-2 rounded-lg w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-md bg-slate-100 flex items-center justify-center shrink-0 text-slate-600">
                                                            <LifeBuoy className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-slate-900">{__('general.support_tickets')}</p>
                                                            <p className="text-xs text-slate-500 leading-normal">{__('general.support_tickets_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>
                                                </div>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                {/* SERVICES MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors duration-150 outline-none select-none">
                                            <Briefcase className="me-1.5 h-3.5 w-3.5" /> {__('general.services')}<ChevronDown className="ms-1 h-3.5 w-3.5 opacity-50" />
                                        </DropdownMenuTrigger>
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[320px] p-2 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                            <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.service_hubs')}</p>
                                        </div>
                                        
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
                                                    <p className={cn("text-sm font-medium", isMarketplaceActive ? "text-violet-900" : "text-slate-900")}>{__('general.marketing_suite')}</p>
                                                    <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-violet-100 text-violet-700">{__('general.free')}</span>
                                                </div>
                                                <p className={cn("text-xs truncate", isMarketplaceActive ? "text-violet-700/70" : "text-slate-500")}>{__('general.services_clients_amp_campaigns')}</p>
                                            </div>
                                        </DropdownMenuItem>

                                        {user?.roles?.includes('seller') && (
                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer mt-1",
                                                    isRouteActive('seller.dashboard') ? "bg-blue-50/80 border-blue-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('seller.dashboard')} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isRouteActive('seller.dashboard') ? "bg-blue-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-blue-50"
                                                )}>
                                                    <Building2 className={cn("w-4 h-4", isRouteActive('seller.dashboard') ? "text-blue-700" : "text-slate-500 group-hover/dropdown-menu-item:text-blue-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isRouteActive('seller.dashboard') ? "text-blue-900" : "text-slate-900")}>Seller Portal</p>
                                                        <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700">Seller</span>
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('seller.dashboard') ? "text-blue-700/70" : "text-slate-500")}>Manage products and payouts</p>
                                                </div>
                                            </DropdownMenuItem>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                {/* iSAAS MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-sm font-medium leading-none text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 transition-colors duration-150 outline-none select-none">
                                            <Sparkles className="me-1.5 h-3.5 w-3.5" /> iSAAS <ChevronDown className="ms-1 h-3.5 w-3.5 opacity-50" />
                                        </DropdownMenuTrigger>
                                        {isTourOpen && tourStep === 4 && (
                                            <span className="absolute top-1 end-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75" />
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-blue-500" />
                                            </span>
                                        )}
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[640px] p-4 grid grid-cols-2 gap-4 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                        {/* Column 1: Core Systems */}
                                        <div className="flex flex-col gap-1">
                                            <div className="px-2 py-2 mb-1 border-b border-slate-50">
                                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.core_systems')}</p>
                                            </div>
                                            
                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isErpActive ? "bg-indigo-50/80 border-indigo-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={activeModules.erp ? safeRoute('sso.redirect', { system: 'erp' }) : safeRoute('subscriptions.plans', { module: 'erp' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
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
                                                        {isErpActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{__('general.active')}</span>}
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
                                                render={<Link href={activeModules.crm ? safeRoute('sso.redirect', { system: 'crm' }) : safeRoute('subscriptions.plans', { module: 'crm' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isCrmActive ? "bg-indigo-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-indigo-50"
                                                )}>
                                                    <Megaphone className={cn("w-4 h-4", isCrmActive ? "text-indigo-700" : "text-slate-500 group-hover/dropdown-menu-item:text-indigo-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isCrmActive ? "text-indigo-900" : "text-slate-900")}>{__('general.lead_gen_crm')}</p>
                                                        {isCrmActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{__('general.active')}</span>}
                                                        {!activeModules.crm && <Lock className="w-3.5 h-3.5 text-slate-400" />}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isCrmActive ? "text-indigo-700/70" : "text-slate-500")}>
                                                        {!activeModules.crm ? 'Subscribe to access' : 'Capture leads and manage campaigns'}
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isBookingActive ? "bg-amber-50/80 border-amber-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={activeModules.booking ? safeRoute('sso.redirect', { system: 'bookingsys' }) : safeRoute('subscriptions.plans', { module: 'booking' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isBookingActive ? "bg-amber-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-amber-50"
                                                )}>
                                                    <Calendar className={cn("w-4 h-4", isBookingActive ? "text-amber-700" : "text-slate-500 group-hover/dropdown-menu-item:text-amber-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isBookingActive ? "text-amber-900" : "text-slate-900")}>{__('general.booking')}</p>
                                                        {isBookingActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">{__('general.active')}</span>}
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
                                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.tools')}</p>
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
                                                        <p className={cn("text-sm font-medium", isRouteActive('fbmb.index') ? "text-teal-900" : "text-slate-900")}>{__('general.isaas_fb_lookup')}</p>
                                                        {isRouteActive('fbmb.index') && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-teal-100 text-teal-700">{__('general.active')}</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('fbmb.index') ? "text-teal-700/70" : "text-slate-500")}>{__('general.search_mobile_by_fbid')}</p>
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
                                                        <p className={cn("text-sm font-medium", isRouteActive('sms-payment-gateway.index') ? "text-rose-900" : "text-slate-900")}>{__('general.payment_gateway')}</p>
                                                        {isRouteActive('sms-payment-gateway.index') && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700">{__('general.active')}</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('sms-payment-gateway.index') ? "text-rose-700/70" : "text-slate-500")}>{__('general.android_automated_sms')}</p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isRouteActive('isaas.gold-savers.index') ? "bg-yellow-50/80 border-yellow-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('sso.redirect', { system: 'goldsaversys' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isRouteActive('isaas.gold-savers.index') ? "bg-yellow-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-yellow-50"
                                                )}>
                                                    <Coins className={cn("w-4 h-4", isRouteActive('isaas.gold-savers.index') ? "text-yellow-700" : "text-slate-500 group-hover/dropdown-menu-item:text-yellow-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isRouteActive('isaas.gold-savers.index') ? "text-yellow-900" : "text-slate-900")}>{__('general.gold_savers')}</p>
                                                        {isRouteActive('isaas.gold-savers.index') && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-yellow-100 text-yellow-700">{__('general.active')}</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isRouteActive('isaas.gold-savers.index') ? "text-yellow-700/70" : "text-slate-500")}>{__('general.track_your_gold_value')}</p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer",
                                                    isToolsActive ? "bg-fuchsia-50/80 border-fuchsia-100" : "hover:bg-slate-50 border-transparent"
                                                )}
                                                render={<Link href={safeRoute('sso.redirect', { system: 'toolsys' })} className="flex items-start gap-3 p-2.5 rounded-lg w-full" />}
                                            >
                                                <div className={cn(
                                                    "w-8 h-8 rounded-md flex items-center justify-center shrink-0 transition-colors",
                                                    isToolsActive ? "bg-fuchsia-100" : "bg-slate-100 group-hover/dropdown-menu-item:bg-fuchsia-50"
                                                )}>
                                                    <Wrench className={cn("w-4 h-4", isToolsActive ? "text-fuchsia-700" : "text-slate-500 group-hover/dropdown-menu-item:text-fuchsia-600")} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={cn("text-sm font-medium", isToolsActive ? "text-fuchsia-900" : "text-slate-900")}>{__('general.tools_amp_plugins')}</p>
                                                        {isToolsActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-fuchsia-100 text-fuchsia-700">{__('general.active')}</span>}
                                                        {!isToolsActive && <span className="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">{__('general.free_browse')}</span>}
                                                    </div>
                                                    <p className={cn("text-xs truncate", isToolsActive ? "text-fuchsia-700/70" : "text-slate-500")}>{__('general.extensions_amp_licensing')}</p>
                                                </div>
                                            </DropdownMenuItem>
                                        </div>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <NavLink href={safeRoute('subscriptions.plans')} active={isRouteActive('subscriptions')}>
                                    <CreditCard className="h-3.5 w-3.5 text-amber-500" /> {__('general.subscription')}</NavLink>
                                    </>
                                )}
                            </nav>
                        </div>

                        {/* RIGHT: Financials, Tour Button & Profile */}
                        <div className="flex items-center gap-3">
                            {isMarketplaceActive && (
                                <div className="me-1 sm:me-2">
                                    <MarketplaceModeToggle />
                                </div>
                            )}
                            {!auth?.team_member && (
                                <div className="hidden md:flex items-center gap-2 me-2">
                                    {/* Add Balance pill — locked geometry */}
                                    <Link
                                        href={safeRoute('financial.add-balance')}
                                        className="inline-flex items-center gap-1 h-8 min-w-[105px] justify-center px-3 text-xs font-medium border border-slate-200 rounded-full text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors duration-150"
                                    >
                                        <Plus className="w-3.5 h-3.5 shrink-0" />
                                        <span>{__('general.add_balance')}</span>
                                    </Link>

                                    {/* Wallet pill — locked geometry */}
                                    <div className="relative">
                                        <Link
                                            href={safeRoute('financial.transactions')}
                                            className="inline-flex items-center gap-1.5 h-8 min-w-[90px] justify-center px-3 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors duration-150 text-xs font-medium text-slate-900"
                                            title={__('general.wallet_balance')}
                                        >
                                            <Wallet className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                            <span>{wallet ? `${Number(wallet.balance).toFixed(2)} ${wallet.currency}` : '$0.00'}</span>
                                        </Link>
                                        {isTourOpen && tourStep === 3 && (
                                            <span className="absolute -top-1 -end-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-emerald-500" />
                                            </span>
                                        )}
                                    </div>

                                    {/* Points pill — locked geometry */}
                                    <Link
                                        href={safeRoute('points.index', undefined, '/points')}
                                        className="inline-flex items-center gap-1.5 h-8 min-w-[60px] justify-center px-3 bg-amber-50 hover:bg-amber-100 border border-amber-100/80 rounded-full transition-colors duration-150 text-xs font-medium text-amber-700"
                                        title={__('general.points_connects_balance')}
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
                                            <span className="absolute top-2 end-2 w-2 h-2 bg-rose-500 rounded-full border-2 border-white" />
                                        )}
                                    </DropdownMenuTrigger>
                                    {isTourOpen && tourStep === 6 && (
                                        <span className="absolute top-0 end-0 flex h-3 w-3">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75" />
                                            <span className="relative inline-flex rounded-full h-3 w-3 bg-rose-500" />
                                        </span>
                                    )}
                                </div>
                                <DropdownMenuContent align="end" className="w-80 p-0 rounded-xl shadow-xl border border-slate-200 bg-white isolate z-50">
                                    <div className="px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                                        <span className="font-semibold text-slate-900 text-sm">{__('general.notifications')}</span>
                                        {notifications?.unread_count > 0 && (
                                            <Link 
                                                href={safeRoute('notifications.mark-all-read')} 
                                                method="post" 
                                                as="button" 
                                                className="text-xs text-indigo-600 hover:text-indigo-700 font-medium bg-transparent border-0 cursor-pointer p-0"
                                            >{__('general.mark_all_read')}</Link>
                                        )}
                                    </div>
                                    <div className="max-h-[300px] overflow-y-auto p-2">
                                        {notifications?.recent && notifications.recent.length > 0 ? (
                                            notifications.recent.map((n: any) => (
                                                <div key={n.id} className="p-1 hover:bg-slate-50 rounded-lg text-xs flex justify-between items-start gap-1 border-b border-slate-50 last:border-0 relative group">
                                                    <Link 
                                                        href={safeRoute('notifications.mark-read', { id: n.id })} 
                                                        method="post" 
                                                        as="button" 
                                                        className="flex-1 text-start p-1.5 rounded-md outline-none hover:bg-slate-100/50 transition-colors"
                                                    >
                                                        <p className="text-slate-800 font-medium leading-tight">{n.data?.title || n.data?.message || __('general.new_notification')}</p>
                                                        {(n.data?.body || (n.data?.title && n.data?.message)) && (
                                                            <p className="text-slate-500 text-[10px] mt-0.5 line-clamp-2">{n.data?.body || n.data?.message}</p>
                                                        )}
                                                        <span className="text-[10px] text-slate-400 block mt-1">{n.created_at ? new Date(n.created_at).toLocaleDateString() : ''}</span>
                                                    </Link>
                                                    <Link 
                                                        href={safeRoute('notifications.mark-read', { id: n.id, no_redirect: 1 })} 
                                                        method="post" 
                                                        as="button" 
                                                        className="text-[10px] text-indigo-600 hover:underline shrink-0 bg-transparent border-0 cursor-pointer p-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                                    >{__('general.mark_read')}</Link>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="px-2 py-6 text-center text-sm text-slate-500 font-light">{__('general.no_new_notifications')}</div>
                                        )}
                                    </div>
                                    <div className="p-2 border-t border-slate-100">
                                        <Link 
                                            href={safeRoute('notifications.index')} 
                                            className={cn(buttonVariants({ variant: 'ghost', size: 'sm' }), "w-full text-xs text-slate-600 justify-center")}
                                        >{__('general.view_all')}</Link>
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
                                                        <Shield className="me-2 h-4 w-4 text-indigo-600" />{__('general.admin_dashboard')}</DropdownMenuItem>
                                                )}
                                                {user?.roles?.includes('seller') && (
                                                    <DropdownMenuItem 
                                                        className="cursor-pointer rounded-lg text-sm bg-amber-50 text-amber-700 focus:bg-amber-100 focus:text-amber-800 mb-1"
                                                        render={<Link href={safeRoute('seller.dashboard')} className="flex items-center w-full font-medium" />}
                                                    >
                                                        <Building2 className="me-2 h-4 w-4 text-amber-600" />Seller Portal</DropdownMenuItem>
                                                )}
                                                {(user?.roles?.includes('moderator') || user?.roles?.includes('support_agent')) && (
                                                    <DropdownMenuItem 
                                                        className="cursor-pointer rounded-lg text-sm bg-blue-50 text-blue-700 focus:bg-blue-100 focus:text-blue-800 mb-1"
                                                        render={<Link href={safeRoute('admin.tickets.index')} className="flex items-center w-full font-medium" />}
                                                    >
                                                        <LifeBuoy className="me-2 h-4 w-4 text-blue-600" />{__('general.manage_tickets')}</DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('profile.edit')} className="flex items-center w-full" />}
                                                >
                                                    <User className="me-2 h-4 w-4 text-slate-400" />{__('general.my_profile')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('kyc.index')} className="flex items-center w-full" />}
                                                >
                                                    <Shield className="me-2 h-4 w-4 text-slate-400" />{__('general.identity_verification')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('financial.transactions')} className="flex items-center w-full" />}
                                                >
                                                    <History className="me-2 h-4 w-4 text-slate-400" />{__('general.balance_history')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('profile.edit')} className="flex items-center w-full" />}
                                                >
                                                    <Shield className="me-2 h-4 w-4 text-slate-400" />{__('general.security_settings')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('settings.backup.index')} className="flex items-center w-full" />}
                                                >
                                                    <Download className="me-2 h-4 w-4 text-slate-400" />{__('general.backup_restore')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('settings.automations.index')} className="flex items-center w-full" />}
                                                >
                                                    <Settings className="me-2 h-4 w-4 text-slate-400" />{__('general.automations')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-lg text-sm"
                                                    render={<Link href={safeRoute('subscriptions.manage')} className="flex items-center w-full" />}
                                                >
                                                    <Box className="me-2 h-4 w-4 text-slate-400" /> {__('general.subscriptions')}</DropdownMenuItem>
                                            </DropdownMenuGroup>
                                            
                                            <DropdownMenuSeparator className="my-1 bg-slate-100" />
                                            
                                            <DropdownMenuItem 
                                                className="cursor-pointer rounded-lg text-sm text-rose-600 focus:text-rose-600 focus:bg-rose-50"
                                                render={<Link href={safeRoute('logout')} method="post" as="button" className="flex items-center w-full font-medium" />}
                                            >
                                                <LogOut className="me-2 h-4 w-4" /> {__('general.logout')}</DropdownMenuItem>
                                        </>
                                    ) : (
                                        <DropdownMenuItem 
                                            className="cursor-pointer rounded-lg text-sm text-rose-600 focus:text-rose-600 focus:bg-rose-50"
                                            render={<Link href={route('erp.team.logout')} method="post" as="button" className="flex items-center w-full font-medium" />}
                                        >
                                            <LogOut className="me-2 h-4 w-4" />{__('general.logout_team_member')}</DropdownMenuItem>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </header>

            {/* Notification Permission Banner */}
            {permission !== 'granted' && !auth?.has_ios_shortcut_active && (
                <div className="bg-indigo-600 px-4 py-3 text-white sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                        <p className="text-sm leading-6">
                            <strong className="font-semibold">{__('general.notifications')}</strong>
                            <svg viewBox="0 0 2 2" className="mx-2 inline h-0.5 w-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                            {__('general.notifications_disabled_message')}
                        </p>
                        <div className="flex items-center gap-2">
                            {isIOS ? (
                                <Link
                                    href={safeRoute('freelance.settings.notifications') || '#'}
                                    className="flex-none rounded-full bg-indigo-900 px-3.5 py-1 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900 transition-colors"
                                >
                                    {__('general.enable_notifications')} <span aria-hidden="true">&rarr;</span>
                                </Link>
                            ) : (
                                <button
                                    onClick={requestPermission}
                                    className="flex-none rounded-full bg-indigo-900 px-3.5 py-1 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900 transition-colors"
                                >
                                    {__('general.enable_notifications')} <span aria-hidden="true">&rarr;</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}

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

            <BackgroundTaskStatus />
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
