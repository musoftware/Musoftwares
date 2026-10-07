import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { PageHeroHeader } from '@/Components/ui/PageHeroHeader';
import { BentoStatCard } from '@/Components/ui/BentoStatCard';
import { ContentCard } from '@/Components/ui/ContentCard';
import { 
    Folder, Wallet, FileText, ArrowRight, ArrowUpRight, 
    Server, Zap, Shield, MessageSquare, Laptop, Coins, CreditCard, Wrench, Key, LifeBuoy, Sparkles 
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatCurrencyAmount } from '@/lib/utils';
import axios from 'axios';
import { LoyaltyTierHeader } from './Components/LoyaltyTierHeader';
import { ProjectProgressBar } from './Components/ProjectProgressBar';
import { NewProjectPlaceholder } from './Components/NewProjectPlaceholder';
import { DataLockinVault } from './Components/DataLockinVault';
import { RewardsCatalogModal } from './Components/RewardsCatalogModal';

interface DashboardProps {
    stats?: any;
    recentTransactions?: any[];
    chartData?: any[];
    activeToolLicenses?: any[];
    userProjects?: any[];
    realNotifications?: any[];
    authUser?: any;
    userTier?: string;
    userLoyaltyPoints?: number;
    pointsToMoneyRate?: number;
    profileCompletion?: number;
    vaultAssets?: any[];
    vaultStats?: any;
    loyaltyRewards?: any[];
    userBalanceVal?: number;
    currencySymbol?: string;
    userBalanceFormatted?: string;
    userPoints?: number;
    unpaidInvoices?: any[];
    unpaidCount?: number;
    unpaidAmount?: number;
    totalDueAmount?: number;
    totalDueFormatted?: string;
}

export default function Dashboard({
    stats = {},
    recentTransactions = [],
    activeToolLicenses = [],
    userProjects = [],
    realNotifications = [],
    authUser = {},
    userTier = 'bronze',
    userLoyaltyPoints = 0,
    pointsToMoneyRate = 1 / 30,
    profileCompletion = 25,
    vaultAssets = [],
    vaultStats,
    loyaltyRewards = [],
    userBalanceFormatted = '',
    userPoints = 0,
    unpaidCount = 0,
    unpaidAmount = 0,
    totalDueFormatted = ''
}: DashboardProps) {
    const user = authUser?.name ? authUser : {};
    const effectiveTier = (userTier && userTier !== 'standard' ? userTier : (authUser?.loyalty_tier?.slug || (authUser?.tier && authUser?.tier !== 'standard' ? authUser?.tier : 'bronze'))) || 'bronze';
    const walletBalance = stats?.walletBalance ?? 0;
    const currency = stats?.currency?.symbol || stats?.currency?.currency;

    const [isRewardsOpen, setIsRewardsOpen] = useState(false);
    const [currentLoyaltyPoints, setCurrentLoyaltyPoints] = useState<number>(userLoyaltyPoints || 0);
    const [currentProfileCompletion, setCurrentProfileCompletion] = useState<number>(profileCompletion || 25);

    const handleCompleteProfile = async () => {
        try {
            const res = await axios.post('/api/portal/profile/complete');
            setCurrentProfileCompletion(100);
            if (res.data?.data?.current_balance !== undefined) {
                setCurrentLoyaltyPoints(res.data.data.current_balance);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const allUserProjects = (userProjects && userProjects.length > 0) ? userProjects : [];

    const activeProjects = allUserProjects.filter((p: any) => {
        const isFinished = Boolean(
            p.is_delivered ||
            p.progress_stage === 'delivered' ||
            Number(p.progress_percentage ?? p.progress ?? 0) === 100 ||
            p.status === 'closed' ||
            p.delivered_at
        );
        return !isFinished;
    });

    const completedProjects = allUserProjects.filter((p: any) => {
        return Boolean(
            p.is_delivered ||
            p.progress_stage === 'delivered' ||
            Number(p.progress_percentage ?? p.progress ?? 0) === 100 ||
            p.status === 'closed' ||
            p.delivered_at
        );
    });

    const hasActiveProject = activeProjects.length > 0;
    const [activeProjectIdx, setActiveProjectIdx] = useState(0);
    const currentProject = hasActiveProject ? (activeProjects[activeProjectIdx] || activeProjects[0]) : null;

    return (
        <AuthenticatedLayout>
            <Head title={__('client.dash_page_title')} />

            <div className="w-full max-w-full min-w-0 overflow-x-clip">
                {/* 1. TOP ACTIVE PROJECT SHOWCASE (Apple Bento Hero Banner) */}
                <PageHeroHeader
                    badge={hasActiveProject ? __('client.dash_badge_active') : __('client.dash_badge_workspace')}
                    title={
                        hasActiveProject
                            ? (currentProject?.name || currentProject?.project_name || __('client.dash_default_project_name'))
                            : __('client.dash_start_next_project')
                    }
                    description={
                        hasActiveProject
                            ? (currentProject?.phase || __('client.dash_default_phase'))
                            : __('client.dash_all_delivered_desc')
                    }
                    actions={
                        <div className="flex flex-wrap sm:flex-nowrap items-center gap-2.5 w-full sm:w-auto">
                            {hasActiveProject && currentProject ? (
                                <>
                                    <Link
                                        href={currentProject?.id ? `/projects/${currentProject.id}` : '/projects'}
                                        className="w-full sm:w-auto px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center justify-center gap-2 shadow-md shadow-blue-500/20 active:scale-98 select-none cursor-pointer"
                                    >
                                        <span className="uppercase">{__('client.dash_launch_workspace')}</span>
                                        <ArrowRight className="w-4 h-4" />
                                    </Link>
                                    <Link
                                        href={currentProject?.id ? `/tickets/create?project_id=${currentProject.id}` : '/tickets/create'}
                                        className="flex-1 sm:flex-initial px-4 py-2.5 border border-blue-500/25 bg-blue-500/10 text-[#0071e3] hover:bg-blue-500/20 text-xs font-semibold rounded-[980px] transition-all flex items-center justify-center gap-1.5 shadow-2xs active:scale-98 select-none cursor-pointer text-center"
                                    >
                                        <LifeBuoy className="w-3.5 h-3.5 shrink-0" />
                                        <span className="uppercase">{__('client.dash_open_ticket')}</span>
                                        <span className="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded-full bg-blue-500/15 text-[#0071e3] dark:text-blue-400 ms-0.5 uppercase">{__('client.dash_ticket_points')}</span>
                                    </Link>
                                    <Link
                                        href="/micro-services"
                                        className="flex-1 sm:flex-initial px-4 py-2.5 border border-purple-500/25 bg-purple-500/10 text-purple-600 dark:text-purple-400 hover:bg-purple-500/20 text-xs font-semibold rounded-[980px] transition-all flex items-center justify-center gap-1.5 shadow-2xs active:scale-98 select-none cursor-pointer text-center"
                                    >
                                        <Sparkles className="w-3.5 h-3.5 shrink-0" />
                                        <span className="uppercase">{__('client.dash_micro_services')}</span>
                                    </Link>
                                    <Link
                                        href="/estimator"
                                        className="flex-1 sm:flex-initial px-4 py-2.5 border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-900 text-[#1d1d1f] dark:text-[#f8fafc] hover:bg-[#f5f5f7] dark:hover:bg-zinc-800 text-xs font-semibold rounded-[980px] transition-all shadow-sm active:scale-98 select-none cursor-pointer text-center flex items-center justify-center"
                                    >
                                        <span className="uppercase">{__('client.dash_new_scope')}</span>
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link
                                        href="/projects/create-new"
                                        className="w-full sm:w-auto px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center justify-center gap-2 shadow-md shadow-blue-500/20 active:scale-98 select-none cursor-pointer"
                                    >
                                        <span className="uppercase">{__('client.dash_start_new_project')}</span>
                                        <ArrowRight className="w-4 h-4" />
                                    </Link>
                                    <Link
                                        href="/tickets/create"
                                        className="flex-1 sm:flex-initial px-4 py-2.5 border border-blue-500/25 bg-blue-500/10 text-[#0071e3] hover:bg-blue-500/20 text-xs font-semibold rounded-[980px] transition-all flex items-center justify-center gap-1.5 shadow-2xs active:scale-98 select-none cursor-pointer text-center"
                                    >
                                        <LifeBuoy className="w-3.5 h-3.5 shrink-0" />
                                        <span className="uppercase">{__('client.dash_open_ticket')}</span>
                                        <span className="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded-full bg-blue-500/15 text-[#0071e3] dark:text-blue-400 ms-0.5 uppercase">{__('client.dash_ticket_points')}</span>
                                    </Link>
                                    <Link
                                        href="/micro-services"
                                        className="flex-1 sm:flex-initial px-4 py-2.5 border border-purple-500/25 bg-purple-500/10 text-purple-600 dark:text-purple-400 hover:bg-purple-500/20 text-xs font-semibold rounded-[980px] transition-all flex items-center justify-center gap-1.5 shadow-2xs active:scale-98 select-none cursor-pointer text-center"
                                    >
                                        <Sparkles className="w-3.5 h-3.5 shrink-0" />
                                        <span className="uppercase">{__('client.dash_micro_services')}</span>
                                    </Link>
                                    <Link
                                        href="/projects"
                                        className="flex-1 sm:flex-initial px-4 py-2.5 border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-900 text-[#1d1d1f] dark:text-[#f8fafc] hover:bg-[#f5f5f7] dark:hover:bg-zinc-800 text-xs font-semibold rounded-[980px] transition-all shadow-sm active:scale-98 select-none cursor-pointer text-center flex items-center justify-center"
                                    >
                                        <span className="uppercase">{__('client.dash_view_archive')}</span>
                                    </Link>
                                </>
                            )}
                        </div>
                    }
                />

                {/* 2. CLIENT PORTAL ENGINE: LOYALTY, TIERS & PROJECT TRACKING */}
                <PageShell maxWidth="7xl" className="space-y-12">
                    {/* VIP Tier & Gamified Loyalty Header */}
                    <LoyaltyTierHeader
                        clientName={user?.name || ''}
                        tier={effectiveTier}
                        loyaltyPoints={currentLoyaltyPoints}
                        profileCompletion={currentProfileCompletion}
                        currency={stats?.currency}
                        pointsToMoneyRate={pointsToMoneyRate}
                        onOpenRewardsModal={() => setIsRewardsOpen(true)}
                        onCompleteProfile={handleCompleteProfile}
                    />

                    {/* Interactive 4-Stage Project Progress Tracker OR New Project Placeholder */}
                    {hasActiveProject && currentProject ? (
                        <ProjectProgressBar
                            project={currentProject}
                            onBriefSubmitted={() => window.location.reload()}
                            onProjectCompleted={() => window.location.reload()}
                        />
                    ) : (
                        <NewProjectPlaceholder completedProjects={completedProjects} />
                    )}

                    {/* Support & Tickets Loyalty Incentive Guide */}
                    <div className="rounded-2xl border border-blue-200/70 dark:border-blue-900/40 bg-linear-to-r from-blue-50/60 via-white to-indigo-50/40 dark:from-zinc-900 dark:via-zinc-900/80 dark:to-zinc-900 p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div className="space-y-2 max-w-2xl">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100/80 dark:bg-blue-950/60 text-[#0071e3] dark:text-blue-400 text-[11px] font-bold tracking-wide">
                                <LifeBuoy className="w-3.5 h-3.5" />
                                <span className="uppercase">{__('client.dash_support_badge')}</span>
                            </div>
                            <h3 className="text-base font-extrabold text-slate-900 dark:text-white tracking-tight">
                                {__('client.dash_support_title')}
                            </h3>
                            <p className="text-xs text-slate-600 dark:text-zinc-400 leading-relaxed">
                                {__('client.dash_support_desc')}
                            </p>
                        </div>
                        <div className="flex flex-wrap sm:flex-nowrap items-center gap-2.5 w-full sm:w-auto shrink-0">
                            <Link
                                href={currentProject?.id ? `/tickets/create?project_id=${currentProject.id}` : '/tickets/create'}
                                className="w-full sm:w-auto px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer active:scale-98 select-none"
                            >
                                <MessageSquare className="w-4 h-4" />
                                <span>{__('client.dash_open_support_ticket')}</span>
                            </Link>
                            <Link
                                href="/tickets"
                                className="w-full sm:w-auto px-4 py-2.5 border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-700 text-xs font-semibold rounded-xl transition-all cursor-pointer active:scale-98 select-none text-center flex items-center justify-center"
                            >
                                {__('client.dash_view_tickets')}
                            </Link>
                        </div>
                    </div>

                    {/* 3-PILLAR OPERATIONAL METRICS SUMMARY */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Pillar 1: Projects & Sprints */}
                        <BentoStatCard
                            label={__('client.dash_projects_tasks')}
                            value={
                                <span>
                                    {activeProjects.length} <span className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-normal">{__('general.active')}</span>
                                    {completedProjects.length > 0 && (
                                        <span className="text-xs text-zinc-400 font-normal ms-2">
                                            {__('client.dash_delivered_count', { count: completedProjects.length })}
                                        </span>
                                    )}
                                </span>
                            }
                            description={
                                hasActiveProject
                                    ? __('client.dash_projects_desc_active')
                                    : __('client.dash_projects_desc_done')
                            }
                            icon={Folder}
                            accentColor="blue"
                            action={
                                <Link href="/projects" className="text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:underline flex items-center gap-1">
                                    <span className="uppercase">{__('client.dash_view_all_projects')}</span>
                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                </Link>
                            }
                        />

                        {/* Pillar 2: Financial Ledger & Wallet */}
                        <BentoStatCard
                            label={__('client.dash_financial_ledger')}
                            value={
                                <span>
                                    {formatCurrencyAmount(walletBalance)} <span className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-normal">{currency}</span>
                                </span>
                            }
                            description={
                                unpaidCount > 0 ? (
                                    <span className="text-amber-600 dark:text-amber-400 font-semibold">{__('client.dash_pending_invoices', { count: unpaidCount, total: totalDueFormatted })}</span>
                                ) : (
                                    <span className="text-emerald-600 dark:text-emerald-400 font-medium">{__('client.dash_all_settled')}</span>
                                )
                            }
                            icon={Wallet}
                            accentColor="emerald"
                            action={
                                <div className="flex items-center space-x-4 rtl:space-x-reverse text-xs font-semibold">
                                    <Link href="/billing/invoices" className="text-[#1d1d1f] dark:text-[#f8fafc] hover:text-[#0071e3] dark:hover:text-[#2997ff] flex items-center gap-1 transition-colors">
                                        <span className="uppercase">{__('client.dash_invoices')}</span>
                                        <ArrowUpRight className="h-3.5 w-3.5" />
                                    </Link>
                                    <Link href="/financial/add-balance" className="text-[#0071e3] dark:text-[#2997ff] hover:underline flex items-center gap-1">
                                        <span className="uppercase">{__('client.dash_add_funds')}</span>
                                    </Link>
                                </div>
                            }
                        />

                        {/* Pillar 3: Deployed Systems & Tools */}
                        <BentoStatCard
                            label={__('client.dash_deployed_systems')}
                            value={
                                <span>
                                    {activeToolLicenses.length > 0 ? activeToolLicenses.length : 3} <span className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-normal">{__('client.dash_active_instances')}</span>
                                </span>
                            }
                            description={__('client.dash_deployed_desc')}
                            icon={Server}
                            accentColor="cyan"
                            action={
                                <Link href="/marketplace" className="text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:underline flex items-center gap-1">
                                    <span className="uppercase">{__('client.dash_marketplace_apps')}</span>
                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                </Link>
                            }
                        />
                    </div>

                    {/* 3. BENTO GRID OF CORE CLIENT CAPABILITIES */}
                    <div className="space-y-6">
                        <div>
                            <h2 className="text-xl sm:text-2xl font-bold text-[#1d1d1f] dark:text-[#f8fafc] tracking-tight font-sans">
                                {__('client.dash_integrated_systems')}
                            </h2>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 mt-1 font-sans">
                                {__('client.dash_integrated_systems_desc')}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            {/* Gold Saver Card */}
                            <a href="/sso/goldsaversys" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-amber-500/40 dark:hover:border-amber-400/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                        <Coins className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60 rounded-full">
                                        {__('client.dash_live')}
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-amber-600 dark:group-hover:text-amber-400 tracking-tight transition-colors">
                                    {__('client.dash_gold_saver_title')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    {__('client.dash_gold_saver_desc')}
                                </p>
                            </a>

                            {/* Payment Gateway Card */}
                            <a href="/sms-payment-gateway" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-rose-500/40 dark:hover:border-rose-400/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-rose-500/10 dark:bg-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                        <CreditCard className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60 rounded-full">
                                        {__('general.active')}
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-rose-600 dark:group-hover:text-rose-400 tracking-tight transition-colors">
                                    {__('client.dash_payment_gateway_title')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    {__('client.dash_payment_gateway_desc')}
                                </p>
                            </a>

                            {/* Software Store Card */}
                            <Link href="/store/tools" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-[#0071e3]/40 dark:hover:border-[#2997ff]/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 dark:bg-[#0071e3]/20 flex items-center justify-center text-[#0071e3] dark:text-[#2997ff]">
                                        <Wrench className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 rounded-full">
                                        {__('client.dash_store')}
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-[#0071e3] dark:group-hover:text-[#2997ff] tracking-tight transition-colors">
                                    {__('client.dash_tools_store_title')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    {__('client.dash_tools_store_desc')}
                                </p>
                            </Link>

                            {/* My Licenses & Devices Card */}
                            <Link href="/my-licenses" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-emerald-500/40 dark:hover:border-emerald-400/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                        <Key className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 rounded-full">
                                        {__('client.dash_licenses')}
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-emerald-600 dark:group-hover:text-emerald-400 tracking-tight transition-colors">
                                    {__('client.dash_licenses_title')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    {__('client.dash_licenses_desc')}
                                </p>
                            </Link>

                            {/* Reseller Portal Card (if reseller or admin) */}
                            {(authUser?.is_reseller || authUser?.roles?.includes('software_reseller') || authUser?.role === 'software_reseller' || authUser?.is_admin || authUser?.role === 'admin') && (
                                <Link href="/portal/devices" className="bg-white dark:bg-zinc-900/80 border border-[#0071e3]/30 dark:border-[#0071e3]/40 rounded-[24px] p-6 hover:border-[#0071e3] dark:hover:border-[#3898ec] hover:shadow-md transition-all group block shadow-sm">
                                    <div className="flex items-center justify-between mb-5">
                                        <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 dark:bg-[#0071e3]/20 flex items-center justify-center text-[#0071e3] dark:text-[#3898ec]">
                                            <Laptop className="w-6 h-6" />
                                        </div>
                                        <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-[#0071e3] dark:text-[#3898ec] border border-[#0071e3]/20 rounded-full">
                                            {__('client.dash_reseller')}
                                        </span>
                                    </div>
                                    <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-[#0071e3] dark:group-hover:text-[#3898ec] tracking-tight transition-colors">
                                        {__('client.dash_reseller_title')}
                                    </h3>
                                    <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                        {__('client.dash_reseller_desc')}
                                    </p>
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* Recent Transactions / Invoices Table */}
                    {recentTransactions.length > 0 && (
                        <ContentCard
                            title={__('client.dash_recent_operations')}
                            action={
                                <Link href="/billing/invoices" className="text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:underline inline-flex items-center gap-1">
                                    {__('client.dash_view_full_archive')}
                                    <ArrowRight className="w-3.5 h-3.5 rtl:rotate-180" />
                                </Link>
                            }
                        >
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs font-sans text-left rtl:text-right">
                                    <thead>
                                        <tr className="border-b border-black/5 dark:border-white/10 text-[#1d1d1f]/50 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                                            <th className="py-3 px-2">{__('client.dash_operation_id')}</th>
                                            <th className="py-3 px-2">{__('general.date')}</th>
                                            <th className="py-3 px-2">{__('general.description')}</th>
                                            <th className="py-3 px-2 text-right rtl:text-left">{__('general.amount')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-black/5 dark:divide-white/10">
                                        {recentTransactions.slice(0, 5).map((txn) => (
                                            <tr key={txn.id} className="hover:bg-[#f5f5f7] dark:hover:bg-zinc-800/50 transition-colors">
                                                <td className="py-3 px-2 font-mono text-[#1d1d1f]/60 dark:text-zinc-400">#TXN-{txn.id}</td>
                                                <td className="py-3 px-2 text-[#1d1d1f]/70 dark:text-zinc-300">{txn.date}</td>
                                                <td className="py-3 px-2 text-[#1d1d1f] dark:text-[#f8fafc] font-medium">{txn.method}</td>
                                                <td className={`py-3 px-2 text-right rtl:text-left font-bold ${txn.type === 'deposit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-[#1d1d1f] dark:text-[#f8fafc]'}`}>
                                                    {txn.amount > 0 ? `+${formatCurrencyAmount(txn.amount)}` : formatCurrencyAmount(txn.amount)} {txn.currency?.symbol || txn.currency?.currency || currency}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </ContentCard>
                    )}

                    {/* 4. DATA LOCK-IN VAULT (Confidential Deliverables, Code & Contracts) */}
                    <DataLockinVault
                        assets={vaultAssets || []}
                        vaultStats={vaultStats}
                    />
                </PageShell>

                {/* Loyalty Rewards Catalog Modal */}
                <RewardsCatalogModal
                    isOpen={isRewardsOpen}
                    onClose={() => setIsRewardsOpen(false)}
                    userPoints={currentLoyaltyPoints}
                    initialRewards={loyaltyRewards}
                    onRewardRedeemed={(newBal) => setCurrentLoyaltyPoints(newBal)}
                />
            </div>
        </AuthenticatedLayout>
    );
}
