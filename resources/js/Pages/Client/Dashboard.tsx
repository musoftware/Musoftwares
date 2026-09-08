import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { PageHeroHeader } from '@/Components/ui/PageHeroHeader';
import { BentoStatCard } from '@/Components/ui/BentoStatCard';
import { ContentCard } from '@/Components/ui/ContentCard';
import { 
    Folder, Wallet, FileText, ArrowRight, ArrowUpRight, 
    Server, Zap, Shield, MessageSquare, Laptop, Coins, CreditCard, Wrench, Key 
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface DashboardProps {
    stats?: any;
    recentTransactions?: any[];
    chartData?: any[];
    activeToolLicenses?: any[];
    userProjects?: any[];
    realNotifications?: any[];
    authUser?: any;
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
    userBalanceFormatted = '',
    userPoints = 0,
    unpaidCount = 0,
    unpaidAmount = 0,
    totalDueFormatted = ''
}: DashboardProps) {
    const user = authUser?.name ? authUser : {};
    const walletBalance = stats?.walletBalance ?? 0;
    const currency = stats?.currency?.symbol || stats?.currency?.currency;

    const projectsList = (userProjects && userProjects.length > 0) 
        ? userProjects 
        : [
            {
                id: 0,
                name: 'Enterprise Cloud ERP Deployment',
                status: 'In Progress',
                progress: 68,
                phase: 'Sprint 3: Ledger Reconciliation & VAT Automation',
                link: '/projects'
            }
        ];

    const [activeProjectIdx, setActiveProjectIdx] = useState(0);
    const currentProject = projectsList[activeProjectIdx] || projectsList[0];

    return (
        <AuthenticatedLayout>
            <Head title="Client Console — Musoftwares Studio" />

            <div className="w-full">
                {/* 1. TOP ACTIVE PROJECT SHOWCASE (Apple Bento Hero Banner) */}
                <PageHeroHeader
                    badge="Active Studio Delivery"
                    title={currentProject.name || currentProject.project_name || 'Enterprise Architecture Workspace'}
                    description={currentProject.phase || 'Real-time sprint progress tracked with zero-loss audit logging.'}
                    actions={
                        <div className="flex items-center space-x-4 rtl:space-x-reverse shrink-0">
                            <Link href={currentProject?.id ? `/projects/${currentProject.id}` : '/projects'}>
                                <button className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer">
                                    <span>LAUNCH WORKSPACE</span>
                                    <ArrowRight className="w-4 h-4" />
                                </button>
                            </Link>
                            <Link href="/estimator">
                                <button className="px-5 py-2.5 border border-black/10 dark:border-white/10 bg-white dark:bg-zinc-900 text-[#1d1d1f] dark:text-[#f8fafc] hover:bg-[#f5f5f7] dark:hover:bg-zinc-800 text-xs font-semibold rounded-[980px] transition-all shadow-sm cursor-pointer">
                                    NEW SCOPE +
                                </button>
                            </Link>
                        </div>
                    }
                />

                {/* 2. 3-PILLAR OPERATIONAL METRICS SUMMARY */}
                <PageShell maxWidth="7xl" className="space-y-12">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Pillar 1: Projects & Sprints */}
                        <BentoStatCard
                            label="Projects & Tasks"
                            value={
                                <span>
                                    {projectsList.length} <span className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-normal">Active</span>
                                </span>
                            }
                            description="Sprint deliveries, kanban boards & files"
                            icon={Folder}
                            accentColor="blue"
                            action={
                                <Link href="/projects" className="text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:underline flex items-center gap-1">
                                    <span>VIEW PROJECT BOARD</span>
                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                </Link>
                            }
                        />

                        {/* Pillar 2: Financial Ledger & Wallet */}
                        <BentoStatCard
                            label="Financial Ledger"
                            value={
                                <span>
                                    {Number(walletBalance).toLocaleString()} <span className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-normal">{currency}</span>
                                </span>
                            }
                            description={
                                unpaidCount > 0 ? (
                                    <span className="text-amber-600 dark:text-amber-400 font-semibold">{unpaidCount} Pending Invoices ({totalDueFormatted})</span>
                                ) : (
                                    <span className="text-emerald-600 dark:text-emerald-400 font-medium">All accounts settled</span>
                                )
                            }
                            icon={Wallet}
                            accentColor="emerald"
                            action={
                                <div className="flex items-center space-x-4 rtl:space-x-reverse text-xs font-semibold">
                                    <Link href="/billing/invoices" className="text-[#1d1d1f] dark:text-[#f8fafc] hover:text-[#0071e3] dark:hover:text-[#2997ff] flex items-center gap-1 transition-colors">
                                        <span>INVOICES</span>
                                        <ArrowUpRight className="h-3.5 w-3.5" />
                                    </Link>
                                    <Link href="/financial/add-balance" className="text-[#0071e3] dark:text-[#2997ff] hover:underline flex items-center gap-1">
                                        <span>+ ADD FUNDS</span>
                                    </Link>
                                </div>
                            }
                        />

                        {/* Pillar 3: Deployed Systems & Tools */}
                        <BentoStatCard
                            label="Deployed Systems"
                            value={
                                <span>
                                    {activeToolLicenses.length > 0 ? activeToolLicenses.length : 3} <span className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-normal">Active Instances</span>
                                </span>
                            }
                            description="ERP, Meta Cloud API, and Background Runtime"
                            icon={Server}
                            accentColor="cyan"
                            action={
                                <Link href="/marketplace" className="text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:underline flex items-center gap-1">
                                    <span>MARKETPLACE APPS</span>
                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                </Link>
                            }
                        />
                    </div>

                    {/* 3. BENTO GRID OF CORE CLIENT CAPABILITIES */}
                    <div className="space-y-6">
                        <div>
                            <h2 className="text-xl sm:text-2xl font-bold text-[#1d1d1f] dark:text-[#f8fafc] tracking-tight font-sans">
                                Integrated Enterprise Systems
                            </h2>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 mt-1 font-sans">
                                Direct Single Sign-On (SSO) links to your provisioned infrastructure
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
                                        Live
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-amber-600 dark:group-hover:text-amber-400 tracking-tight transition-colors">
                                    Gold Saver & Assets
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    Real-time gold vault, hedging, gram rates & personal asset tracking.
                                </p>
                            </a>

                            {/* Payment Gateway Card */}
                            <a href="/sms-payment-gateway" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-rose-500/40 dark:hover:border-rose-400/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-rose-500/10 dark:bg-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                        <CreditCard className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60 rounded-full">
                                        Active
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-rose-600 dark:group-hover:text-rose-400 tracking-tight transition-colors">
                                    Automated Payment Gateway
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    Automated SMS verification, webhook notifications & mobile wallet settlements.
                                </p>
                            </a>

                            {/* Software Store Card */}
                            <Link href="/store/tools" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-[#0071e3]/40 dark:hover:border-[#2997ff]/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 dark:bg-[#0071e3]/20 flex items-center justify-center text-[#0071e3] dark:text-[#2997ff]">
                                        <Wrench className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 rounded-full">
                                        Store
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-[#0071e3] dark:group-hover:text-[#2997ff] tracking-tight transition-colors">
                                    Software & Tools Store
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    Desktop utilities with instant automated activation linked to your email.
                                </p>
                            </Link>

                            {/* My Licenses & Devices Card */}
                            <Link href="/my-licenses" className="bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-emerald-500/40 dark:hover:border-emerald-400/40 hover:shadow-md transition-all group block shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                        <Key className="w-6 h-6" />
                                    </div>
                                    <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 rounded-full">
                                        Licenses
                                    </span>
                                </div>
                                <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-emerald-600 dark:group-hover:text-emerald-400 tracking-tight transition-colors">
                                    My Licenses & Devices
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                    Manage your purchased software tools, linked computers, and hardware keys.
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
                                            Reseller
                                        </span>
                                    </div>
                                    <h3 className="text-base font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-[#0071e3] dark:group-hover:text-[#3898ec] tracking-tight transition-colors">
                                        Software Reseller Portal
                                    </h3>
                                    <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-1.5 leading-relaxed">
                                        Manage client device activations, quotas, and software licenses.
                                    </p>
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* Recent Transactions / Invoices Table */}
                    {recentTransactions.length > 0 && (
                        <ContentCard
                            title="Recent Financial Operations"
                            action={
                                <Link href="/billing/invoices" className="text-xs font-semibold text-[#0071e3] dark:text-[#2997ff] hover:underline">
                                    View Full Archive ➔
                                </Link>
                            }
                        >
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs font-sans text-left rtl:text-right">
                                    <thead>
                                        <tr className="border-b border-black/5 dark:border-white/10 text-[#1d1d1f]/50 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                                            <th className="py-3 px-2">Operation ID</th>
                                            <th className="py-3 px-2">Date</th>
                                            <th className="py-3 px-2">Description</th>
                                            <th className="py-3 px-2 text-right rtl:text-left">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-black/5 dark:divide-white/10">
                                        {recentTransactions.slice(0, 5).map((txn) => (
                                            <tr key={txn.id} className="hover:bg-[#f5f5f7] dark:hover:bg-zinc-800/50 transition-colors">
                                                <td className="py-3 px-2 font-mono text-[#1d1d1f]/60 dark:text-zinc-400">#TXN-{txn.id}</td>
                                                <td className="py-3 px-2 text-[#1d1d1f]/70 dark:text-zinc-300">{txn.date}</td>
                                                <td className="py-3 px-2 text-[#1d1d1f] dark:text-[#f8fafc] font-medium">{txn.method}</td>
                                                <td className={`py-3 px-2 text-right rtl:text-left font-bold ${txn.type === 'deposit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-[#1d1d1f] dark:text-[#f8fafc]'}`}>
                                                    {txn.amount > 0 ? `+${Number(txn.amount).toLocaleString()}` : Number(txn.amount).toLocaleString()} {txn.currency?.symbol || txn.currency?.currency || currency}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </ContentCard>
                    )}
                </PageShell>
            </div>
        </AuthenticatedLayout>
    );
}
