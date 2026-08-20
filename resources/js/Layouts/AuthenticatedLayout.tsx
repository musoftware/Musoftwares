import React, { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import SafeLink from '@/Components/SafeLink';
import { useInertiaNotifications } from '@/hooks/useInertiaNotifications';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import { Button, buttonVariants } from '@/Components/ui/button';
import { cn, formatMoney } from '@/lib/utils';
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
    ListTodo,
    Calculator
} from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import ProductTourModal from '@/Components/ProductTourModal';
import BackgroundTaskStatus from '@/Components/Tools/BackgroundTaskStatus';

import axios from 'axios';
import MarketplaceModeToggle from '@/Components/Marketplace/MarketplaceModeToggle';
import { __ } from '@/lib/i18n';
import { useFCM } from '@/hooks/useFCM';
import { PageTransition } from '@/Components/ui/PageTransition';

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
    const [notifBannerDismissed, setNotifBannerDismissed] = useState(false);

    useEffect(() => {
        setIsIOS(
            typeof window !== 'undefined' &&
            /iPad|iPhone|iPod/.test(navigator.userAgent) &&
            !(window as any).MSStream
        );
        // Restore dismissed state from localStorage
        try {
            if (localStorage.getItem('notif_banner_dismissed') === '1') {
                setNotifBannerDismissed(true);
            }
        } catch { /* ignore */ }
    }, []);

    const dismissNotifBanner = () => {
        setNotifBannerDismissed(true);
        try { localStorage.setItem('notif_banner_dismissed', '1'); } catch { /* ignore */ }
    };

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
    const isAllTasksMenuActive = isRouteActive('client.projects.all-tasks');
    const isProjectsMenuActive = isRouteActive('client.projects') && !isAllTasksMenuActive;
    const isMessagesMenuActive = isRouteActive('messages');
    const isInvoicesMenuActive = isRouteActive('billing.invoices');
    const isTransactionsMenuActive = isRouteActive('financial.transactions');
    const isWithdrawalsMenuActive = isRouteActive('financial.withdrawals');
    const isPayoutMethodsMenuActive = isRouteActive('financial.payout-methods');
    const isReferralsMenuActive = isRouteActive('referrals');
    const isTicketsMenuActive = isRouteActive('tickets');
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
                'inline-flex items-center gap-1.5 h-8 px-3.5 rounded-none text-xs font-mono tracking-wider uppercase leading-none transition-colors duration-150',
                active
                    ? 'bg-white text-black font-bold'
                    : 'text-zinc-400 hover:bg-[#222222] hover:text-white'
            )}
        >
            {children}
        </Link>
    );

    return (
        <div className="min-h-screen bg-[#111111] font-sans text-[#E5E5E5] flex flex-col selection:bg-[#748660] selection:text-white">
            {isImpersonating && (
                <div
                    className="bg-gradient-to-r from-amber-600 via-orange-600 to-rose-700 text-white text-xs font-semibold px-4 shadow-md flex items-center justify-between z-[50] sticky top-0"
                    style={{
                        paddingTop: 'env(safe-area-inset-top, 0px)',
                        height: 'calc(36px + env(safe-area-inset-top, 0px))'
                    }}
                >
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
                        className="bg-white/20 hover:bg-white/30 text-white font-bold py-1 px-3 rounded-none border border-white/20 hover:border-white/40 transition-all text-[11px] shrink-0 ms-2"
                    >{__('general.stop_impersonation')}</Link>
                </div>
            )}

            {/* Top Navigation */}
            <header
                className={cn("sticky z-40 w-full bg-[#111111] border-b border-[#222222]", isImpersonating ? "top-[36px]" : "top-0")}
                style={{
                    paddingTop: 'env(safe-area-inset-top, 0px)',
                    height: 'calc(68px + env(safe-area-inset-top, 0px))'
                }}
            >
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 h-full">
                    <div className="flex h-full items-center justify-between">
                        {/* LEFT: Logo & Nav */}
                        <div className="flex items-center gap-8">
                            {/* Mobile Menu Trigger */}
                            <Sheet open={isMobileOpen} onOpenChange={setIsMobileOpen}>
                                <SheetTrigger asChild>
                                    <Button variant="ghost" size="icon" className="md:hidden -ms-2 text-zinc-400 hover:text-white">
                                        <Menu className="h-5 w-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent side="left" className="w-[300px] sm:w-[350px] p-0 bg-[#161616] border-[#2B2B2B] text-white">
                                    <div className="flex flex-col h-full">
                                        <div className="p-5 border-b border-[#222222]">
                                            <SafeLink href="/" className="flex items-center gap-2" onClick={() => setIsMobileOpen(false)}>
                                                <span className="text-xl font-black tracking-tighter text-white font-sans">
                                                    MUSOFT
                                                </span>
                                                <span className="text-[10px] font-mono tracking-widest uppercase text-zinc-400">
                                                    STUDIO
                                                </span>
                                            </SafeLink>
                                        </div>
                                        <div className="flex-1 overflow-y-auto p-4 space-y-6">
                                            {/* Mobile Nav Links */}
                                            <div className="space-y-1">
                                                {auth?.team_member ? (
                                                    <SafeLink href={safeRoute('sso.redirect', { system: 'erp' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                        <LayoutDashboard className="w-5 h-5 text-slate-400" /> {__('general.dashboard')}</SafeLink>
                                                ) : (
                                                    <>
                                                        <SafeLink href={safeRoute('dashboard')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                                                            <LayoutDashboard className="w-5 h-5 text-slate-400" /> {__('general.dashboard')}</SafeLink>
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
                                                                                <LifeBuoy className="w-4 h-4 text-slate-400" />{__('general.support_tickets')}
                                                                            </Link>
                                                                        </div>
                                                                    </AccordionContent>
                                                                </AccordionItem>

                                                                <Link
                                                                    href={safeRoute('marketplace.services.index')}
                                                                    onClick={() => setIsMobileOpen(false)}
                                                                    className={cn(
                                                                        "flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors",
                                                                        isRouteActive('marketplace.services')
                                                                            ? "bg-emerald-50 text-emerald-700"
                                                                            : "text-emerald-700 hover:bg-slate-50"
                                                                    )}
                                                                >
                                                                    <Briefcase className="w-5 h-5 text-emerald-600" /> {__('general.services')}
                                                                </Link>

                                                                <Link
                                                                    href={safeRoute('estimator', undefined, '/estimator')}
                                                                    onClick={() => setIsMobileOpen(false)}
                                                                    className={cn(
                                                                        "flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors",
                                                                        isRouteActive('estimator')
                                                                            ? "bg-blue-50 text-blue-700"
                                                                            : "text-slate-700 hover:bg-slate-50"
                                                                    )}
                                                                >
                                                                    <Calculator className="w-5 h-5 text-blue-600" /> {__('general.estimator') || 'Estimator'}
                                                                </Link>

                                                                <AccordionItem value="isaas" className="border-b-0">
                                                                    <AccordionTrigger className="px-3 py-2 hover:bg-slate-50 rounded-lg text-indigo-700 hover:no-underline">
                                                                        <div className="flex items-center gap-3 font-medium">
                                                                            <Sparkles className="w-5 h-5 text-indigo-600" /> iSAAS
                                                                        </div>
                                                                    </AccordionTrigger>
                                                                    <AccordionContent className="pb-1 px-2">
                                                                        <div className="flex flex-col space-y-1 mt-1 border-s-2 border-slate-100 ms-5 ps-4">
                                                                            <SafeLink href={activeModules.erp ? safeRoute('sso.redirect', { system: 'erp' }) : safeRoute('subscriptions.plans', { module: 'erp' })} onClick={() => setIsMobileOpen(false)} className="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-indigo-50/50 text-slate-600 font-medium">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Building2 className="w-4 h-4 text-indigo-500" /> ERP
                                                                                </div>
                                                                                {!activeModules.erp && <Lock className="w-3 h-3 text-slate-400" />}
                                                                            </SafeLink>
                                                                            <SafeLink href={activeModules.crm ? safeRoute('sso.redirect', { system: 'crm' }) : safeRoute('subscriptions.plans', { module: 'crm' })} onClick={() => setIsMobileOpen(false)} className="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-indigo-50/50 text-slate-600 font-medium">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Megaphone className="w-4 h-4 text-indigo-500" /> {__('general.lead_gen_crm')}
                                                                                </div>
                                                                                {!activeModules.crm && <Lock className="w-3 h-3 text-slate-400" />}
                                                                            </SafeLink>
                                                                            <SafeLink href={activeModules.booking ? safeRoute('sso.redirect', { system: 'bookingsys' }) : safeRoute('subscriptions.plans', { module: 'booking' })} onClick={() => setIsMobileOpen(false)} className="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-amber-50/50 text-slate-600 font-medium">
                                                                                <div className="flex items-center gap-3">
                                                                                    <Calendar className="w-4 h-4 text-amber-500" /> {__('general.booking')}</div>
                                                                                {!activeModules.booking && <Lock className="w-3 h-3 text-slate-400" />}
                                                                            </SafeLink>
                                                                            <Link href={safeRoute('fbmb.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-teal-50/50 text-slate-600 font-medium">
                                                                                <Activity className="w-4 h-4 text-teal-500" /> {__('general.isaas_fb_lookup')}
                                                                            </Link>
                                                                            <Link href={safeRoute('whatsapp.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50/50 text-slate-600 font-medium">
                                                                                <MessageSquare className="w-4 h-4 text-emerald-500" /> WhatsApp Sender API
                                                                            </Link>
                                                                            <Link href={safeRoute('sms-payment-gateway.index')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-rose-50/50 text-slate-600 font-medium">
                                                                                <MessageSquare className="w-4 h-4 text-rose-500" /> {__('general.payment_gateway')}
                                                                            </Link>
                                                                            <SafeLink href={safeRoute('sso.redirect', { system: 'goldsaversys' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-50/50 text-slate-600 font-medium">
                                                                                <Coins className="w-4 h-4 text-yellow-500" /> {__('general.gold_savers')}
                                                                            </SafeLink>
                                                                            <SafeLink href={safeRoute('sso.redirect', { system: 'toolsys' })} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-fuchsia-50/50 text-slate-600 font-medium">
                                                                                <Wrench className="w-4 h-4 text-fuchsia-500" /> {__('general.tools_amp_plugins')}
                                                                            </SafeLink>
                                                                        </div>
                                                                    </AccordionContent>
                                                                </AccordionItem>

                                                            </Accordion>
                                                        </div>

                                                        <SafeLink href={safeRoute('subscriptions.plans')} onClick={() => setIsMobileOpen(false)} className="flex items-center gap-3 px-3 py-2 mt-2 rounded-lg hover:bg-amber-50 text-amber-700 font-medium">
                                                            <CreditCard className="w-5 h-5 text-amber-500" /> {__('general.subscription')}</SafeLink>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </SheetContent>
                            </Sheet>

                            <SafeLink href="/" className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                                    <ApplicationLogo className="w-5 h-5 text-white fill-current" />
                                </div>
                                <span className="font-medium text-lg hidden sm:block tracking-tight">musoftware</span>
                            </SafeLink>

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
                                                    "inline-flex items-center gap-1.5 h-8 px-3 rounded-none text-xs font-mono uppercase tracking-wider leading-none transition-colors duration-150 outline-none select-none",
                                                    isWorkspaceActive 
                                                        ? "bg-[#1E1E1E] text-white border border-[#2B2B2B]" 
                                                        : "text-zinc-400 hover:bg-[#1E1E1E] hover:text-white"
                                                )}>
                                                    {__('general.workspace')}<ChevronDown className="ms-1 h-3 w-3 opacity-60" />
                                                </DropdownMenuTrigger>
                                                {isTourOpen && (tourStep === 2 || tourStep === 5) && (
                                                    <span className="absolute top-1 end-1 flex h-2.5 w-2.5">
                                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#748660] opacity-75" />
                                                        <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#748660]" />
                                                    </span>
                                                )}
                                            </div>
                                            <DropdownMenuContent align="start" className="w-[820px] p-5 grid grid-cols-3 gap-6 rounded-none border border-[#2B2B2B] bg-[#141414] text-zinc-300 isolate z-50 shadow-2xl">
                                                {/* Column 1: Projects & Collaboration */}
                                                <div className="flex flex-col gap-1.5">
                                                    <div className="px-2 py-2 mb-1 border-b border-[#222222]">
                                                        <p className="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">{__('general.projects_and_collaboration')}</p>
                                                    </div>
                                                    
                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isProjectsMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('client.projects.index')} aria-current={isProjectsMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <FolderKanban className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.projects')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.projects_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isAllTasksMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('client.projects.all-tasks')} aria-current={isAllTasksMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <ListTodo className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.all_tasks')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.all_tasks_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isMessagesMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('messages.index')} aria-current={isMessagesMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <MessageSquare className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.messages')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.messages_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>
                                                </div>

                                                {/* Column 2: Financials */}
                                                <div className="flex flex-col gap-1.5">
                                                    <div className="px-2 py-2 mb-1 border-b border-[#222222]">
                                                        <p className="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">{__('general.financials')}</p>
                                                    </div>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isInvoicesMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('billing.invoices.index')} aria-current={isInvoicesMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <FileText className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.my_invoices')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.my_invoices_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isTransactionsMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('financial.transactions')} aria-current={isTransactionsMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <ArrowRightLeft className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.transactions')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.transactions_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isWithdrawalsMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('financial.withdrawals')} aria-current={isWithdrawalsMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <ArrowUpRight className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.request_withdrawal')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.request_withdrawal_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isPayoutMethodsMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('financial.payout-methods.index')} aria-current={isPayoutMethodsMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <CreditCard className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.payout_methods')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.payout_methods_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isReferralsMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('referrals.index')} aria-current={isReferralsMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <Users className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.referrals')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.referrals_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>
                                                </div>

                                                {/* Column 3: Support */}
                                                <div className="flex flex-col gap-1.5">
                                                    <div className="px-2 py-2 mb-1 border-b border-[#222222]">
                                                        <p className="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">{__('general.support')}</p>
                                                    </div>

                                                    <DropdownMenuItem 
                                                        className={cn(
                                                            "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                            isTicketsMenuActive
                                                                ? "bg-[#1E1E1E] border-[#333333]" 
                                                                : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                        )}
                                                        render={<SafeLink href={safeRoute('tickets.index')} aria-current={isTicketsMenuActive ? 'page' : undefined} className="flex items-start gap-3 p-2.5 w-full" />}
                                                    >
                                                        <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                            <LifeBuoy className="w-4 h-4" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-xs font-bold text-white font-sans">{__('general.support_tickets')}</p>
                                                            <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5">{__('general.support_tickets_desc')}</p>
                                                        </div>
                                                    </DropdownMenuItem>
                                                </div>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                {/* SERVICES LINK */}
                                <SafeLink
                                    href={safeRoute('marketplace.services.index')}
                                    className={cn(
                                        "inline-flex items-center gap-1.5 h-8 px-3 rounded-none text-xs font-mono uppercase tracking-wider leading-none transition-colors duration-150 outline-none select-none",
                                        isRouteActive('marketplace.services')
                                            ? "bg-[#1E1E1E] text-[#748660] font-bold border border-[#2B2B2B]"
                                            : "text-zinc-400 hover:bg-[#1E1E1E] hover:text-white"
                                    )}
                                >
                                    <Briefcase className="me-1.5 h-3.5 w-3.5 text-[#748660]" /> {__('general.services')}
                                </SafeLink>

                                {/* iSAAS MEGA MENU */}
                                <DropdownMenu>
                                    <div className="relative inline-block">
                                        <DropdownMenuTrigger className="inline-flex items-center gap-1.5 h-8 px-3 rounded-none text-xs font-mono uppercase tracking-wider leading-none text-zinc-400 hover:bg-[#1E1E1E] hover:text-white transition-colors duration-150 outline-none select-none">
                                            <Sparkles className="me-1.5 h-3.5 w-3.5 text-[#748660]" /> iSAAS <ChevronDown className="ms-1 h-3 w-3 opacity-60" />
                                        </DropdownMenuTrigger>
                                        {isTourOpen && tourStep === 4 && (
                                            <span className="absolute top-1 end-1 flex h-2.5 w-2.5">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#748660] opacity-75" />
                                                <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#748660]" />
                                            </span>
                                        )}
                                    </div>
                                    <DropdownMenuContent align="start" className="w-[640px] p-5 grid grid-cols-2 gap-6 rounded-none border border-[#2B2B2B] bg-[#141414] text-zinc-300 isolate z-50 shadow-2xl">
                                        {/* Column 1: Core Systems */}
                                        <div className="flex flex-col gap-1.5">
                                            <div className="px-2 py-2 mb-1 border-b border-[#222222]">
                                                <p className="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">{__('general.core_systems')}</p>
                                            </div>
                                            
                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isErpActive ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={activeModules.erp ? safeRoute('sso.redirect', { system: 'erp' }) : safeRoute('subscriptions.plans', { module: 'erp' })} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <Building2 className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">ERP</p>
                                                        {isErpActive && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                        {!activeModules.erp && <Lock className="w-3.5 h-3.5 text-zinc-500" />}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">
                                                        {!activeModules.erp ? 'Subscribe to access' : 'Clients, invoices, timers'}
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isCrmActive ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={activeModules.crm ? safeRoute('sso.redirect', { system: 'crm' }) : safeRoute('subscriptions.plans', { module: 'crm' })} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <Megaphone className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">{__('general.lead_gen_crm')}</p>
                                                        {isCrmActive && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                        {!activeModules.crm && <Lock className="w-3.5 h-3.5 text-zinc-500" />}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">
                                                        {!activeModules.crm ? 'Subscribe to access' : 'Capture leads & campaigns'}
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isBookingActive ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={activeModules.booking ? safeRoute('sso.redirect', { system: 'bookingsys' }) : safeRoute('subscriptions.plans', { module: 'booking' })} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <Calendar className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">{__('general.booking')}</p>
                                                        {isBookingActive && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                        {!activeModules.booking && <Lock className="w-3.5 h-3.5 text-zinc-500" />}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">
                                                        {!activeModules.booking ? 'Subscribe to access' : 'Appointments & Availability'}
                                                    </p>
                                                </div>
                                            </DropdownMenuItem>
                                        </div>

                                        {/* Column 2: Tools */}
                                        <div className="flex flex-col gap-1.5">
                                            <div className="px-2 py-2 mb-1 border-b border-[#222222]">
                                                <p className="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">{__('general.tools')}</p>
                                            </div>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isRouteActive('fbmb.index') ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={safeRoute('fbmb.index')} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <Activity className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">{__('general.isaas_fb_lookup')}</p>
                                                        {isRouteActive('fbmb.index') && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">{__('general.search_mobile_by_fbid')}</p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isRouteActive('whatsapp.index') ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={safeRoute('whatsapp.index')} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <MessageSquare className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">WhatsApp API</p>
                                                        {isRouteActive('whatsapp.index') && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">Meta Cloud API &amp; Webhooks</p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isRouteActive('sms-payment-gateway.index') ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={safeRoute('sms-payment-gateway.index')} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <MessageSquare className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">{__('general.payment_gateway')}</p>
                                                        {isRouteActive('sms-payment-gateway.index') && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">{__('general.android_automated_sms')}</p>
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem 
                                                className={cn(
                                                    "p-0 outline-none border transition-colors duration-150 cursor-pointer rounded-none group",
                                                    isToolsActive ? "bg-[#1E1E1E] border-[#333333]" : "hover:bg-[#1C1C1C] border-transparent hover:border-[#2B2B2B]"
                                                )}
                                                render={<SafeLink href={safeRoute('sso.redirect', { system: 'toolsys' })} className="flex items-start gap-3 p-2.5 w-full" />}
                                            >
                                                <div className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] flex items-center justify-center shrink-0 text-zinc-400 group-hover:text-[#748660] group-hover:border-[#748660]/40 transition-colors">
                                                    <Wrench className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs font-bold text-white font-sans">{__('general.tools_amp_plugins')}</p>
                                                        {isToolsActive && <span className="text-[10px] font-mono px-1.5 py-0.5 bg-[#1E2619] border border-[#748660]/40 text-[#748660]">{__('general.active')}</span>}
                                                    </div>
                                                    <p className="text-[11px] text-zinc-400 font-sans leading-tight mt-0.5 truncate">{__('general.extensions_amp_licensing')}</p>
                                                </div>
                                            </DropdownMenuItem>
                                        </div>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <NavLink href={safeRoute('estimator', undefined, '/estimator')} active={isRouteActive('estimator')} className="text-xs font-mono uppercase tracking-wider text-zinc-400 hover:text-white">
                                    <Calculator className="h-3.5 w-3.5 text-[#748660]" /> {__('general.estimator') || 'Estimator'}
                                </NavLink>

                                <NavLink href={safeRoute('subscriptions.plans')} active={isRouteActive('subscriptions')} className="text-xs font-mono uppercase tracking-wider text-zinc-400 hover:text-white">
                                    <CreditCard className="h-3.5 w-3.5 text-[#748660]" /> {__('general.subscription')}</NavLink>
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
                                <div className="hidden md:flex items-center gap-2 me-2 font-mono text-xs">
                                    {/* Add Balance pill — unified studio geometry */}
                                    <SafeLink
                                        href={safeRoute('financial.add-balance')}
                                        className="inline-flex items-center gap-1 h-8 min-w-[105px] justify-center px-3 text-xs font-bold border border-[#2B2B2B] bg-black text-zinc-300 hover:border-white hover:text-white rounded-none uppercase tracking-wider transition-colors duration-150"
                                    >
                                        <Plus className="w-3.5 h-3.5 shrink-0 text-[#748660]" />
                                        <span>{__('general.add_balance')}</span>
                                    </SafeLink>

                                    {/* Wallet pill — unified studio geometry */}
                                    <div className="relative">
                                        <SafeLink
                                            href={safeRoute('financial.transactions')}
                                            className="inline-flex items-center gap-1.5 h-8 min-w-[90px] justify-center px-3 bg-[#1C1C1C] hover:bg-[#252525] border border-[#2B2B2B] rounded-none transition-colors duration-150 text-xs font-bold text-white tracking-wider"
                                            title={__('general.wallet_balance')}
                                        >
                                            <Wallet className="w-3.5 h-3.5 text-[#748660] shrink-0" />
                                            <span>{wallet ? formatMoney(wallet.balance, wallet.currency) : formatMoney(0, (auth?.user as any)?.currency)}</span>
                                        </SafeLink>
                                        {isTourOpen && tourStep === 3 && (
                                            <span className="absolute -top-1 -end-1 flex h-2.5 w-2.5">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#748660] opacity-75" />
                                                <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#748660]" />
                                            </span>
                                        )}
                                    </div>

                                    {/* Points pill — unified studio geometry */}
                                    <SafeLink
                                        href={safeRoute('points.index', undefined, '/points')}
                                        className="inline-flex items-center gap-1.5 h-8 min-w-[60px] justify-center px-3 bg-[#1E2619] border border-[#748660]/40 rounded-none transition-colors duration-150 text-xs font-bold text-[#748660] tracking-wider"
                                        title={__('general.points_connects_balance')}
                                    >
                                        <Coins className="w-3.5 h-3.5 text-[#748660] shrink-0" />
                                        <span>{user?.points_balance !== undefined ? Number(user.points_balance).toLocaleString() : '0'}</span>
                                    </SafeLink>
                                </div>
                            )}

                            {/* Notifications */}
                            <DropdownMenu>
                                <div className="relative inline-block">
                                    <DropdownMenuTrigger className="w-8 h-8 rounded-none bg-black border border-[#2B2B2B] inline-flex items-center justify-center text-zinc-400 hover:text-white hover:border-[#748660] transition-colors duration-150 relative outline-none shrink-0">
                                        <Bell className="w-4 h-4" />
                                        {notifications?.unread_count > 0 && (
                                            <span className="absolute top-1 end-1 w-2 h-2 bg-[#748660] rounded-full" />
                                        )}
                                    </DropdownMenuTrigger>
                                    {isTourOpen && tourStep === 6 && (
                                        <span className="absolute top-0 end-0 flex h-2.5 w-2.5">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#748660] opacity-75" />
                                            <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#748660]" />
                                        </span>
                                    )}
                                </div>
                                <DropdownMenuContent align="end" className="w-80 p-0 rounded-none border border-[#2B2B2B] bg-[#141414] text-zinc-300 isolate z-50 shadow-2xl">
                                    <div className="px-4 py-3 border-b border-[#222222] flex justify-between items-center">
                                        <span className="font-mono font-bold text-white text-xs uppercase tracking-wider">{__('general.notifications')}</span>
                                        {notifications?.unread_count > 0 && (
                                            <SafeLink 
                                                href={safeRoute('notifications.mark-all-read')} 
                                                method="post" 
                                                as="button" 
                                                className="text-[11px] font-mono text-[#748660] hover:text-white font-bold bg-transparent border-0 cursor-pointer p-0"
                                            >{__('general.mark_all_read')}</SafeLink>
                                        )}
                                    </div>
                                    <div className="max-h-[300px] overflow-y-auto p-2 space-y-1">
                                        {notifications?.recent && notifications.recent.length > 0 ? (
                                            notifications.recent.map((n: any) => (
                                                <div key={n.id} className="p-2 hover:bg-[#1E1E1E] border border-transparent hover:border-[#2B2B2B] text-xs flex justify-between items-start gap-1 relative group transition-colors">
                                                    <SafeLink 
                                                        href={safeRoute('notifications.mark-read', { id: n.id })} 
                                                        method="post" 
                                                        as="button" 
                                                        className="flex-1 text-start p-0 outline-none"
                                                    >
                                                        <p className="text-white font-bold leading-tight font-sans text-xs">{n.data?.title || n.data?.message || __('general.new_notification')}</p>
                                                        {(n.data?.body || (n.data?.title && n.data?.message)) && (
                                                            <p className="text-zinc-400 text-[11px] mt-0.5 line-clamp-2 font-sans">{n.data?.body || n.data?.message}</p>
                                                        )}
                                                        <span className="text-[10px] font-mono text-zinc-500 block mt-1">{n.created_at ? new Date(n.created_at).toLocaleDateString() : ''}</span>
                                                    </SafeLink>
                                                    <SafeLink 
                                                        href={safeRoute('notifications.mark-read', { id: n.id, no_redirect: 1 })} 
                                                        method="post" 
                                                        as="button" 
                                                        className="text-[10px] font-mono text-[#748660] hover:text-white shrink-0 bg-transparent border-0 cursor-pointer p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                                    >{__('general.mark_read')}</SafeLink>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="px-2 py-6 text-center text-xs font-mono text-zinc-500 uppercase tracking-wider">{__('general.no_new_notifications')}</div>
                                        )}
                                    </div>
                                    <div className="p-2 border-t border-[#222222]">
                                        <SafeLink 
                                            href={safeRoute('notifications.index')} 
                                            className={cn(buttonVariants({ variant: 'ghost', size: 'sm' }), "w-full text-xs font-mono uppercase tracking-wider text-zinc-400 hover:text-white justify-center rounded-none hover:bg-[#1E1E1E]")}
                                        >{__('general.view_all')}</SafeLink>
                                    </div>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {/* Profile */}
                            <DropdownMenu>
                                <DropdownMenuTrigger className="outline-none relative shrink-0">
                                    <Avatar className="h-8 w-8 rounded-none border border-[#2B2B2B] cursor-pointer hover:border-white transition-colors duration-150">
                                        <AvatarFallback className="bg-black text-white font-mono font-bold text-xs rounded-none">
                                            {displayName.substring(0, 2).toUpperCase() || 'US'}
                                        </AvatarFallback>
                                    </Avatar>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56 p-2 rounded-none border border-[#2B2B2B] bg-[#141414] text-zinc-300 isolate z-50 shadow-2xl">
                                    <div className="px-2 py-2 mb-2 border-b border-[#222222]">
                                        <p className="text-xs font-bold text-white font-mono uppercase tracking-wider truncate">{displayName}</p>
                                        <p className="text-[11px] text-zinc-400 truncate mt-0.5">{displayEmail}</p>
                                    </div>
                                    
                                    {!auth?.team_member ? (
                                        <>
                                            <DropdownMenuGroup className="space-y-0.5">
                                                {user?.role === 'admin' && (
                                                    <DropdownMenuItem 
                                                        className="cursor-pointer rounded-none text-xs font-mono uppercase tracking-wider bg-[#1E2619] border border-[#748660]/40 text-[#748660] hover:bg-[#25321F] mb-1 p-2"
                                                        render={<SafeLink href={safeRoute('admin.dashboard')} className="flex items-center w-full font-bold" />}
                                                    >
                                                        <Shield className="me-2 h-3.5 w-3.5 text-[#748660]" />{__('general.admin_dashboard')}</DropdownMenuItem>
                                                )}
                                                {user?.roles?.includes('seller') && (
                                                    <DropdownMenuItem 
                                                        className="cursor-pointer rounded-none text-xs font-mono uppercase tracking-wider bg-[#1E2619] border border-[#748660]/40 text-[#748660] hover:bg-[#25321F] mb-1 p-2"
                                                        render={<SafeLink href={safeRoute('seller.dashboard')} className="flex items-center w-full font-bold" />}
                                                    >
                                                        <Building2 className="me-2 h-3.5 w-3.5 text-[#748660]" />Seller Portal</DropdownMenuItem>
                                                )}
                                                {(user?.roles?.includes('moderator') || user?.roles?.includes('support_agent')) && (
                                                    <DropdownMenuItem 
                                                        className="cursor-pointer rounded-none text-xs font-mono uppercase tracking-wider bg-[#1E2619] border border-[#748660]/40 text-[#748660] hover:bg-[#25321F] mb-1 p-2"
                                                        render={<SafeLink href={safeRoute('admin.tickets.index')} className="flex items-center w-full font-bold" />}
                                                    >
                                                        <LifeBuoy className="me-2 h-3.5 w-3.5 text-[#748660]" />{__('general.manage_tickets')}</DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('profile.edit')} className="flex items-center w-full" />}
                                                >
                                                    <User className="me-2 h-3.5 w-3.5 text-zinc-500" />{__('general.my_profile')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('kyc.index')} className="flex items-center w-full" />}
                                                >
                                                    <Shield className="me-2 h-3.5 w-3.5 text-zinc-500" />{__('general.identity_verification')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('financial.transactions')} className="flex items-center w-full" />}
                                                >
                                                    <History className="me-2 h-3.5 w-3.5 text-zinc-500" />{__('general.balance_history')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('profile.edit')} className="flex items-center w-full" />}
                                                >
                                                    <Shield className="me-2 h-3.5 w-3.5 text-zinc-500" />{__('general.security_settings')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('settings.backup.index')} className="flex items-center w-full" />}
                                                >
                                                    <Download className="me-2 h-3.5 w-3.5 text-zinc-500" />{__('general.backup_restore')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('settings.automations.index')} className="flex items-center w-full" />}
                                                >
                                                    <Settings className="me-2 h-3.5 w-3.5 text-zinc-500" />{__('general.automations')}</DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    className="cursor-pointer rounded-none text-xs text-zinc-300 hover:bg-[#1E1E1E] hover:text-white p-2 transition-colors"
                                                    render={<SafeLink href={safeRoute('subscriptions.manage')} className="flex items-center w-full" />}
                                                >
                                                    <Box className="me-2 h-3.5 w-3.5 text-zinc-500" /> {__('general.subscriptions')}</DropdownMenuItem>
                                            </DropdownMenuGroup>
                                            
                                            <DropdownMenuSeparator className="my-1.5 bg-[#222222]" />
                                            
                                            <DropdownMenuItem 
                                                className="cursor-pointer rounded-none text-xs font-mono uppercase tracking-wider text-rose-400 hover:text-rose-300 hover:bg-[#201111] p-2 transition-colors"
                                                render={<SafeLink href={safeRoute('logout')} method="post" as="button" className="flex items-center w-full font-bold" />}
                                            >
                                                <LogOut className="me-2 h-3.5 w-3.5" /> {__('general.logout')}</DropdownMenuItem>
                                        </>
                                    ) : (
                                        <DropdownMenuItem 
                                            className="cursor-pointer rounded-none text-xs font-mono uppercase tracking-wider text-rose-400 hover:text-rose-300 hover:bg-[#201111] p-2 transition-colors"
                                            render={<SafeLink href={route('erp.team.logout')} method="post" as="button" className="flex items-center w-full font-bold" />}
                                        >
                                            <LogOut className="me-2 h-3.5 w-3.5" />{__('general.logout_team_member')}</DropdownMenuItem>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </header>

            {/* Notification Permission Banner — hidden once dismissed or permission already granted */}
            {permission !== 'granted' && !auth?.has_ios_shortcut_active && !notifBannerDismissed && (
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
                                    className="flex-none rounded-full bg-indigo-900 px-3.5 py-1 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
                                >
                                    {__('general.enable_notifications')} <span aria-hidden="true">&rarr;</span>
                                </Link>
                            ) : (
                                <button
                                    onClick={requestPermission}
                                    className="flex-none rounded-full bg-indigo-900 px-3.5 py-1 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
                                >
                                    {__('general.enable_notifications')} <span aria-hidden="true">&rarr;</span>
                                </button>
                            )}
                            {/* Dismiss: hides the banner permanently via localStorage */}
                            <button
                                onClick={dismissNotifBanner}
                                aria-label="Dismiss notification banner"
                                className="rounded-full p-1 hover:bg-indigo-500 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
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
                <PageTransition>{children}</PageTransition>
            </main>

            <BackgroundTaskStatus />
            <ProductTourModal 
                user={user}
                isOpen={isTourOpen}
                onClose={() => setIsTourOpen(false)}
                currentStep={tourStep}
                onStepChange={setTourStep}
            />

            <Toaster />
            <FloatingWhatsAppButton />
        </div>
    );
}
