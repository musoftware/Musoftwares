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
                id: 1,
                name: 'Enterprise Cloud ERP Deployment',
                status: 'In Progress',
                progress: 68,
                phase: 'Sprint 3: Ledger Reconciliation & VAT Automation',
                link: '/client/projects'
            }
        ];

    const [activeProjectIdx, setActiveProjectIdx] = useState(0);
    const currentProject = projectsList[activeProjectIdx] || projectsList[0];

    return (
        <AuthenticatedLayout>
            <Head title="Client Console — Musoftwares Studio" />

            <div className="w-full bg-[#111111] text-[#E5E5E5] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#748660] selection:text-white">
                
                {/* 1. TOP ACTIVE PROJECT SHOWCASE (SAGE GREEN REFERENCE BAR #748660) */}
                <div className="w-full bg-[#748660] text-[#111111] py-8 px-6 sm:px-10 border-b border-[#5E6D4E]">
                    <div className="max-w-[1400px] mx-auto flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        <div className="space-y-2">
                            <div className="flex items-center gap-3">
                                <span className="px-2.5 py-0.5 bg-[#0F140A] text-[#748660] text-[10px] font-mono uppercase tracking-widest font-bold">
                                    Active Studio Delivery
                                </span>
                                <span className="text-xs font-mono text-[#242E1B] font-semibold">
                                    Status: {currentProject.status || 'Active'}
                                </span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#0F140A] font-sans">
                                {currentProject.name || currentProject.project_name || 'Enterprise Architecture Workspace'}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#202816] font-mono">
                                {currentProject.phase || 'Real-time sprint progress tracked with zero-loss audit logging.'}
                            </p>
                        </div>

                        <div className="flex items-center space-x-4 shrink-0">
                            <Link href="/client/projects">
                                <button className="px-6 py-2.5 bg-[#0F140A] text-[#748660] hover:bg-black hover:text-white text-xs font-bold font-mono tracking-widest uppercase transition-all flex items-center gap-2">
                                    <span>LAUNCH WORKSPACE</span>
                                    <ArrowRight className="w-4 h-4" />
                                </button>
                            </Link>
                            <Link href="/estimator">
                                <button className="px-5 py-2.5 border border-[#0F140A] text-[#0F140A] hover:bg-[#0F140A] hover:text-[#748660] text-xs font-bold font-mono tracking-widest uppercase transition-all">
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
                        <div className="bg-[#161616] border border-[#2B2B2B] p-6 flex flex-col justify-between group hover:border-zinc-500 transition-colors">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-xs font-mono uppercase tracking-widest text-zinc-400">Projects &amp; Tasks</span>
                                <Folder className="h-4 w-4 text-zinc-400" />
                            </div>
                            <div className="space-y-1 mb-6">
                                <div className="text-3xl font-bold font-mono text-white">
                                    {projectsList.length} <span className="text-xs text-zinc-400 font-normal">Active</span>
                                </div>
                                <p className="text-xs text-zinc-400">Sprint deliveries, kanban boards &amp; files</p>
                            </div>
                            <Link href="/client/projects" className="text-xs font-mono font-bold tracking-wider text-white hover:text-zinc-300 flex items-center gap-1">
                                <span>VIEW PROJECT BOARD</span>
                                <ArrowUpRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>

                        {/* Pillar 2: Financial Ledger & Wallet */}
                        <div className="bg-[#161616] border border-[#2B2B2B] p-6 flex flex-col justify-between group hover:border-zinc-500 transition-colors">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-xs font-mono uppercase tracking-widest text-zinc-400">Financial Ledger</span>
                                <Wallet className="h-4 w-4 text-[#748660]" />
                            </div>
                            <div className="space-y-1 mb-6">
                                <div className="text-3xl font-bold font-mono text-white">
                                    {Number(walletBalance).toLocaleString()} <span className="text-xs text-zinc-400 font-normal">{currency}</span>
                                </div>
                                <p className="text-xs text-zinc-400">
                                    {unpaidCount > 0 ? (
                                        <span className="text-amber-400 font-bold">{unpaidCount} Pending Invoices ({totalDueFormatted})</span>
                                    ) : (
                                        <span className="text-emerald-400">All accounts settled</span>
                                    )}
                                </p>
                            </div>
                            <div className="flex items-center space-x-4 text-xs font-mono font-bold">
                                <Link href="/billing/invoices" className="text-white hover:text-zinc-300 flex items-center gap-1">
                                    <span>INVOICES</span>
                                    <ArrowUpRight className="h-3.5 w-3.5" />
                                </Link>
                                <Link href="/financial/add-balance" className="text-[#748660] hover:underline flex items-center gap-1">
                                    <span>+ ADD FUNDS</span>
                                </Link>
                            </div>
                        </div>

                        {/* Pillar 3: Deployed Systems & Tools */}
                        <div className="bg-[#161616] border border-[#2B2B2B] p-6 flex flex-col justify-between group hover:border-zinc-500 transition-colors">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-xs font-mono uppercase tracking-widest text-zinc-400">Deployed Systems</span>
                                <Server className="h-4 w-4 text-cyan-400" />
                            </div>
                            <div className="space-y-1 mb-6">
                                <div className="text-3xl font-bold font-mono text-white">
                                    {activeToolLicenses.length > 0 ? activeToolLicenses.length : 3} <span className="text-xs text-zinc-400 font-normal">Active Instances</span>
                                </div>
                                <p className="text-xs text-zinc-400">ERP, Meta Cloud API, and Background Runtime</p>
                            </div>
                            <Link href="/tools/explore" className="text-xs font-mono font-bold tracking-wider text-white hover:text-zinc-300 flex items-center gap-1">
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
                            <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
                                Integrated Enterprise Systems
                            </h2>
                            <p className="text-xs font-mono text-zinc-400 mt-1">
                                Direct Single Sign-On (SSO) links to your provisioned infrastructure
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        
                        {/* ERP Card */}
                        <a href="/sso/erp" className="bg-[#161616] border border-[#2B2B2B] p-6 hover:border-white transition-all group block">
                            <div className="flex items-center justify-between mb-6">
                                <div className="w-10 h-10 bg-black border border-[#2B2B2B] flex items-center justify-center text-white">
                                    <Server className="w-5 h-5" />
                                </div>
                                <span className="text-[10px] font-mono uppercase tracking-widest px-2 py-0.5 bg-[#748660] text-black font-bold">
                                    Live
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-white group-hover:text-white tracking-tight">
                                Enterprise ERP Console
                            </h3>
                            <p className="text-xs text-zinc-400 font-mono mt-1.5 leading-relaxed">
                                Financial operations, double-entry journal ledger, and VAT reports.
                            </p>
                        </a>

                        {/* CRM Card */}
                        <a href="/sso/crm" className="bg-[#161616] border border-[#2B2B2B] p-6 hover:border-white transition-all group block">
                            <div className="flex items-center justify-between mb-6">
                                <div className="w-10 h-10 bg-black border border-[#2B2B2B] flex items-center justify-center text-white">
                                    <Zap className="w-5 h-5" />
                                </div>
                                <span className="text-[10px] font-mono uppercase tracking-widest px-2 py-0.5 bg-zinc-800 text-zinc-300 font-mono">
                                    Active
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-white group-hover:text-white tracking-tight">
                                CRM Customer Pipeline
                            </h3>
                            <p className="text-xs text-zinc-400 font-mono mt-1.5 leading-relaxed">
                                Lead tracking, interaction history, and client contracts sync.
                            </p>
                        </a>

                        {/* Meta API & WhatsApp */}
                        <a href="/services/whatsapp-business-verification" className="bg-[#161616] border border-[#2B2B2B] p-6 hover:border-white transition-all group block">
                            <div className="flex items-center justify-between mb-6">
                                <div className="w-10 h-10 bg-black border border-[#2B2B2B] flex items-center justify-center text-white">
                                    <MessageSquare className="w-5 h-5 text-emerald-400" />
                                </div>
                                <span className="text-[10px] font-mono uppercase tracking-widest px-2 py-0.5 bg-[#25D366]/20 text-[#25D366] font-mono">
                                    Connected
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-white group-hover:text-white tracking-tight">
                                WhatsApp Cloud API
                            </h3>
                            <p className="text-xs text-zinc-400 font-mono mt-1.5 leading-relaxed">
                                Meta Graph webhooks, automated reply flows, and high-speed delivery.
                            </p>
                        </a>

                        {/* Direct Architect Support */}
                        <Link href="/company/contact" className="bg-[#1C1A14] border border-[#B2831B]/40 p-6 hover:border-[#B2831B] transition-all group block">
                            <div className="flex items-center justify-between mb-6">
                                <div className="w-10 h-10 bg-[#B2831B] flex items-center justify-center text-black font-bold">
                                    <Shield className="w-5 h-5" />
                                </div>
                                <span className="text-[10px] font-mono uppercase tracking-widest px-2 py-0.5 bg-[#B2831B] text-black font-bold">
                                    Priority
                                </span>
                            </div>
                            <h3 className="text-base font-bold text-white tracking-tight">
                                Direct Studio Support
                            </h3>
                            <p className="text-xs text-zinc-400 font-mono mt-1.5 leading-relaxed">
                                Dedicated architect contact with 24-hour turnaround on technical requests.
                            </p>
                        </Link>

                    </div>

                    {/* Recent Transactions / Invoices Table */}
                    {recentTransactions.length > 0 && (
                        <div className="mt-12 bg-[#161616] border border-[#2B2B2B] p-6 sm:p-8">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-base font-bold text-white font-mono uppercase tracking-wider">
                                    Recent Financial Operations
                                </h3>
                                <Link href="/billing/invoices" className="text-xs font-mono text-zinc-400 hover:text-white underline">
                                    View Full Archive ➔
                                </Link>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-xs font-mono text-left">
                                    <thead>
                                        <tr className="border-b border-[#2B2B2B] text-zinc-400 uppercase tracking-wider">
                                            <th className="py-3 px-2">Operation ID</th>
                                            <th className="py-3 px-2">Date</th>
                                            <th className="py-3 px-2">Description</th>
                                            <th className="py-3 px-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#222222]">
                                        {recentTransactions.slice(0, 5).map((txn) => (
                                            <tr key={txn.id} className="hover:bg-[#1C1C1C] transition-colors">
                                                <td className="py-3 px-2 text-zinc-400">#TXN-{txn.id}</td>
                                                <td className="py-3 px-2 text-zinc-300">{txn.date}</td>
                                                <td className="py-3 px-2 text-white font-medium">{txn.method}</td>
                                                <td className={`py-3 px-2 text-right font-bold ${txn.type === 'deposit' ? 'text-emerald-400' : 'text-zinc-300'}`}>
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
