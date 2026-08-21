import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Folder, Wallet, FileText, ArrowRight, ArrowUpRight, 
    CheckCircle2, Clock, Server, Zap, Globe2, Shield, 
    CreditCard, Plus, ExternalLink, RefreshCw, MessageSquare, AlertTriangle 
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
    userBalanceFormatted = '0.00 EGP',
    userPoints = 0,
    unpaidCount = 0,
    unpaidAmount = 0,
    totalDueFormatted = '0.00 EGP'
}: DashboardProps) {
    const user = authUser?.name ? authUser : {};
    const walletBalance = stats?.walletBalance ?? 0;
    const currency = stats?.currency?.symbol ?? 'EGP';

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

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* 1. TOP ACTIVE PROJECT SHOWCASE (Apple Bento Hero Banner) */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        <div className="space-y-2">
                            <div className="flex items-center gap-3">
                                <span className="px-3 py-1 bg-[#0071e3]/10 text-[#0071e3] text-xs font-semibold rounded-full">
                                    Active Studio Delivery
                                </span>
                                <span className="text-xs font-sans text-[#1d1d1f]/60 font-medium">
                                    Status: {currentProject.status || 'Active'}
                                </span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {currentProject.name || currentProject.project_name || 'Enterprise Architecture Workspace'}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/70 font-sans">
                                {currentProject.phase || 'Real-time sprint progress tracked with zero-loss audit logging.'}
                            </p>
                        </div>

                        <div className="flex items-center space-x-4 shrink-0">
                            <Link href={currentProject?.id ? `/projects/${currentProject.id}` : '/projects'}>
                                <button className="px-6 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer">
                                    <span>LAUNCH WORKSPACE</span>
                                    <ArrowRight className="w-4 h-4" />
                                </button>
                            </Link>
                            <Link href="/estimator">
                                <button className="px-5 py-2.5 border border-black/10 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] text-xs font-semibold rounded-[980px] transition-all shadow-sm cursor-pointer">
                                    NEW SCOPE +
                                </button>
                            </Link>
                        </div>

                    </div>
                </div>

                {/* 2. 3-PILLAR OPERATIONAL METRICS SUMMARY */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-10">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {/* Pillar 1: Projects & Sprints */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 flex flex-col justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all shadow-sm">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-xs font-mono uppercase tracking-widest text-[#1d1d1f]/50 font-bold">Projects &amp; Tasks</span>
                                <div className="w-8 h-8 rounded-full bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Folder className="h-4 w-4" />
                                </div>
                            </div>
                            <div className="space-y-1 mb-6">
                                <div className="text-3xl font-bold font-sans text-[#1d1d1f] tracking-tight">
                                    {projectsList.length} <span className="text-xs text-[#1d1d1f]/60 font-normal">Active</span>
                                </div>
                                <p className="text-xs text-[#1d1d1f]/60">Sprint deliveries, kanban boards &amp; files</p>
                            </div>
                            <Link href="/projects" className="text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1">
                                <span>VIEW PROJECT BOARD</span>
                                <ArrowUpRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>

                        {/* Pillar 2: Financial Ledger & Wallet */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 flex flex-col justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all shadow-sm">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-xs font-mono uppercase tracking-widest text-[#1d1d1f]/50 font-bold">Financial Ledger</span>
                                <div className="w-8 h-8 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                                    <Wallet className="h-4 w-4" />
                                </div>
                            </div>
                            <div className="space-y-1 mb-6">
                                <div className="text-3xl font-bold font-sans text-[#1d1d1f] tracking-tight">
                                    {Number(walletBalance).toLocaleString()} <span className="text-xs text-[#1d1d1f]/60 font-normal">{currency}</span>
                                </div>
                                <p className="text-xs text-[#1d1d1f]/60">
                                    {unpaidCount > 0 ? (
                                        <span className="text-amber-600 font-semibold">{unpaidCount} Pending Invoices ({totalDueFormatted})</span>
                                    ) : (
                                        <span className="text-emerald-600 font-medium">All accounts settled</span>
                                    )}
                                </p>
                            </div>
                            <div className="flex items-center space-x-4 text-xs font-semibold">
                                <Link href="/billing/invoices" className="text-[#1d1d1f] hover:text-[#0071e3] flex items-center gap-1 transition-colors">
                                    <span>INVOICES</span>
                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                </Link>
                                <Link href="/financial/add-balance" className="text-[#0071e3] hover:underline flex items-center gap-1">
                                    <span>+ ADD FUNDS</span>
                                </Link>
                            </div>
                        </div>

                        {/* Pillar 3: Deployed Systems & Tools */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 flex flex-col justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all shadow-sm">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-xs font-mono uppercase tracking-widest text-[#1d1d1f]/50 font-bold">Deployed Systems</span>
                                <div className="w-8 h-8 rounded-full bg-cyan-500/10 flex items-center justify-center text-cyan-600">
                                    <Server className="h-4 w-4" />
                                </div>
                            </div>
                            <div className="space-y-1 mb-6">
                                <div className="text-3xl font-bold font-sans text-[#1d1d1f] tracking-tight">
                                    {activeToolLicenses.length > 0 ? activeToolLicenses.length : 3} <span className="text-xs text-[#1d1d1f]/60 font-normal">Active Instances</span>
                                </div>
                                <p className="text-xs text-[#1d1d1f]/60">ERP, Meta Cloud API, and Background Runtime</p>
                            </div>
                            <Link href="/marketplace" className="text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1">
                                <span>MARKETPLACE APPS</span>
                                <ArrowUpRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>

                    </div>
                </div>

                {/* 3. BENTO GRID OF CORE CLIENT CAPABILITIES */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 pb-16">
                    <div className="mb-8 flex items-center justify-between">
                        <div>
                            <h2 className="text-xl sm:text-2xl font-bold text-[#1d1d1f] tracking-tight font-sans">
                                Integrated Enterprise Systems
                            </h2>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 mt-1 font-sans">
                                Direct Single Sign-On (SSO) links to your provisioned infrastructure
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        
                        {/* ERP Card */}
                        <a href="/sso/erp" className="bg-white border border-black/5 rounded-[24px] p-6 hover:border-[#0071e3]/40 hover:shadow-md transition-all group block shadow-sm">
                            <div className="flex items-center justify-between mb-5">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Server className="w-6 h-6" />
                                </div>
                                <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-full">
                                    Live
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] group-hover:text-[#0071e3] tracking-tight transition-colors">
                                Enterprise ERP Console
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 font-sans mt-1.5 leading-relaxed">
                                Financial operations, double-entry journal ledger, and VAT reports.
                            </p>
                        </a>

                        {/* CRM Card */}
                        <a href="/sso/crm" className="bg-white border border-black/5 rounded-[24px] p-6 hover:border-[#0071e3]/40 hover:shadow-md transition-all group block shadow-sm">
                            <div className="flex items-center justify-between mb-5">
                                <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-600">
                                    <Zap className="w-6 h-6" />
                                </div>
                                <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-[#f5f5f7] text-[#1d1d1f]/70 border border-black/5 rounded-full">
                                    Active
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] group-hover:text-[#0071e3] tracking-tight transition-colors">
                                CRM Customer Pipeline
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 font-sans mt-1.5 leading-relaxed">
                                Lead tracking, interaction history, and client contracts sync.
                            </p>
                        </a>

                        {/* Meta API & WhatsApp */}
                        <a href="/services/whatsapp-business-verification" className="bg-white border border-black/5 rounded-[24px] p-6 hover:border-[#0071e3]/40 hover:shadow-md transition-all group block shadow-sm">
                            <div className="flex items-center justify-between mb-5">
                                <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                                    <MessageSquare className="w-6 h-6" />
                                </div>
                                <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-full">
                                    Connected
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] group-hover:text-[#0071e3] tracking-tight transition-colors">
                                WhatsApp Cloud API
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 font-sans mt-1.5 leading-relaxed">
                                Meta Graph webhooks, automated reply flows, and high-speed delivery.
                            </p>
                        </a>

                        {/* Direct Architect Support */}
                        <Link href="/company/contact" className="bg-white border border-black/5 rounded-[24px] p-6 hover:border-[#0071e3]/40 hover:shadow-md transition-all group block shadow-sm">
                            <div className="flex items-center justify-between mb-5">
                                <div className="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-600">
                                    <Shield className="w-6 h-6" />
                                </div>
                                <span className="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200/60 rounded-full">
                                    Priority
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] group-hover:text-[#0071e3] tracking-tight transition-colors">
                                Direct Studio Support
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 font-sans mt-1.5 leading-relaxed">
                                Dedicated architect contact with 24-hour turnaround on technical requests.
                            </p>
                        </Link>

                    </div>

                    {/* Recent Transactions / Invoices Table */}
                    {recentTransactions.length > 0 && (
                        <div className="mt-12 bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-base font-bold text-[#1d1d1f] font-sans tracking-tight">
                                    Recent Financial Operations
                                </h3>
                                <Link href="/billing/invoices" className="text-xs font-semibold text-[#0071e3] hover:underline">
                                    View Full Archive ➔
                                </Link>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-xs font-sans text-left">
                                    <thead>
                                        <tr className="border-b border-black/5 text-[#1d1d1f]/50 font-semibold uppercase tracking-wider">
                                            <th className="py-3 px-2">Operation ID</th>
                                            <th className="py-3 px-2">Date</th>
                                            <th className="py-3 px-2">Description</th>
                                            <th className="py-3 px-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-black/5">
                                        {recentTransactions.slice(0, 5).map((txn) => (
                                            <tr key={txn.id} className="hover:bg-[#f5f5f7] transition-colors">
                                                <td className="py-3 px-2 font-mono text-[#1d1d1f]/60">#TXN-{txn.id}</td>
                                                <td className="py-3 px-2 text-[#1d1d1f]/70">{txn.date}</td>
                                                <td className="py-3 px-2 text-[#1d1d1f] font-medium">{txn.method}</td>
                                                <td className={`py-3 px-2 text-right font-bold ${txn.type === 'deposit' ? 'text-emerald-600' : 'text-[#1d1d1f]'}`}>
                                                    {txn.amount > 0 ? `+${Number(txn.amount).toLocaleString()}` : Number(txn.amount).toLocaleString()} {txn.currency?.symbol || 'EGP'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
