import { Head, Link } from '@inertiajs/react';
import FreelanceLayout from './Layout';
import { useState } from 'react';
import {
    Briefcase,
    CheckCircle2,
    Clock,
    DollarSign,
    Lock,
    Search,
    TrendingUp,
    ShieldAlert,
    ChevronRight,
    Star
} from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';
import { ContractQuickView } from '@/Components/ContextualPanels';

export default function FreelanceDashboard({ auth }: any) {
    const [selectedContract, setSelectedContract] = useState<any>(null);

    // Dynamic state parameters
    const stats = {
        pointsBalance: auth?.user?.points_balance || 150,
        activeProposals: 1,
        activeContracts: 1,
        totalEarnings: 3200.00
    };

    // Active bidding proposals list
    const activeProposals = [
        {
            id: 1,
            title: 'Freelance Proposal: Vercel Edge Cache Optimization Audit',
            status: 'Under Review',
            budget: 3200.00,
            submittedAt: '2026-05-14',
            connectsUsed: 6
        }
    ];

    // Active contracts milestones progression
    const activeContracts = [
        {
            id: 1,
            title: 'SaaS Platform Development Architecture & Audit',
            clientName: 'Vercel Labs',
            startDate: '2026-05-12',
            value: 3200.00,
            progress: 75,
            status: 'in-progress'
        }
    ];

    return (
        <FreelanceLayout auth={auth}>
            <Head title="Freelance Dashboard" />

            <div className="space-y-6 font-sans text-sm pb-8">
                
                {/* ─────────────────────────────────────────
                    FREELANCE KPI SUMMARY DECK
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Points balance */}
                    <div className="bg-slate-50/50 border border-slate-100 rounded-xl p-4 space-y-1">
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Available Connects</span>
                            <DollarSign className="h-4 w-4 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-xl font-bold text-text-primary block">
                                {stats.pointsBalance} Connects
                            </span>
                            <Link 
                                href="/freelance/points" 
                                className="text-[10px] text-indigo-600 font-semibold hover:underline block mt-0.5"
                            >
                                Buy point packages →
                            </Link>
                        </div>
                    </div>

                    {/* Active Bid Proposals */}
                    <div className="bg-slate-50/50 border border-slate-100 rounded-xl p-4 space-y-1">
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Active Bid Proposals</span>
                            <Clock className="h-4 w-4 text-amber-500" />
                        </div>
                        <div>
                            <span className="font-mono text-xl font-bold text-text-primary block">
                                {stats.activeProposals} Active Bids
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-0.5">
                                1 under active review
                            </span>
                        </div>
                    </div>

                    {/* Active Contracts */}
                    <div className="bg-slate-50/50 border border-slate-100 rounded-xl p-4 space-y-1">
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Active Contracts</span>
                            <Briefcase className="h-4 w-4 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-xl font-bold text-text-primary block">
                                {stats.activeContracts} Active
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-0.5">
                                Milestone progress 75%
                            </span>
                        </div>
                    </div>

                    {/* Lifetime Completed Earnings */}
                    <div className="bg-slate-50/50 border border-slate-100 rounded-xl p-4 space-y-1">
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Completed Revenue</span>
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        </div>
                        <div>
                            <span className="font-mono text-xl font-bold text-text-primary block">
                                {formatMoney(stats.totalEarnings, 'USD')}
                            </span>
                            <span className="text-[10px] text-emerald-600 font-semibold block mt-0.5">
                                Payout cleared safely
                            </span>
                        </div>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    MAIN FREELANCE PANELS
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                    
                    {/* Left Column (70%) */}
                    <div className="lg:col-span-7 space-y-6">
                        
                        {/* Active Proposals Panel */}
                        <div className="border border-border/60 rounded-xl overflow-hidden bg-white shadow-sm">
                            <div className="px-5 py-3.5 border-b border-border/40 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
                                    <Clock className="h-4 w-4 text-indigo-500" /> Current Submitted Proposals
                                </h3>
                                <Link 
                                    href="/freelance/jobs/browse"
                                    className="text-[11px] text-indigo-600 font-semibold hover:underline flex items-center gap-0.5"
                                >
                                    Browse Jobs <ChevronRight className="h-3 w-3" />
                                </Link>
                            </div>
                            
                            <div className="divide-y divide-border/40 text-xs">
                                {activeProposals.map(proposal => (
                                    <div 
                                        key={proposal.id}
                                        className="p-4 hover:bg-slate-50/40 transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1">
                                            <span className="font-semibold text-text-primary text-[13px] block">
                                                {proposal.title}
                                            </span>
                                            <div className="flex items-center gap-3 text-text-secondary text-[11px]">
                                                <span>Submitted: {formatDate(proposal.submittedAt)}</span>
                                                <span>•</span>
                                                <span>Connects: {proposal.connectsUsed}</span>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="font-mono font-bold text-text-primary block">
                                                {formatMoney(proposal.budget, 'USD')}
                                            </span>
                                            <span className="text-[10px] text-amber-600 font-bold uppercase bg-amber-50 px-1.5 py-0.2 rounded mt-1 border border-amber-200 inline-block">
                                                {proposal.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Active Contracts Milestone progression */}
                        <div className="border border-border/60 rounded-xl overflow-hidden bg-white shadow-sm">
                            <div className="px-5 py-3.5 border-b border-border/40 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
                                    <Briefcase className="h-4 w-4 text-indigo-500" /> Active Project Contracts
                                </h3>
                            </div>
                            
                            <div className="divide-y divide-border/40 text-xs">
                                {activeContracts.map(contract => (
                                    <div 
                                        key={contract.id}
                                        onClick={() => setSelectedContract(contract)}
                                        className="p-4 hover:bg-slate-50/70 cursor-pointer transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1 flex-1">
                                            <span className="font-semibold text-text-primary text-[13px] block">
                                                {contract.title}
                                            </span>
                                            <span className="text-[11px] text-text-secondary block">
                                                Client: {contract.clientName} • Started: {formatDate(contract.startDate)}
                                            </span>
                                        </div>
                                        
                                        <div className="w-32 shrink-0 ml-4 space-y-1">
                                            <div className="flex justify-between text-[10px]">
                                                <span className="text-text-secondary">Progress</span>
                                                <span className="font-mono font-bold text-text-primary">{contract.progress}%</span>
                                            </div>
                                            <div className="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                                <div className="h-full bg-indigo-600 rounded-full" style={{ width: `${contract.progress}%` }} />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </div>

                    {/* Right Column (30%) */}
                    <div className="lg:col-span-3 space-y-6">
                        
                        {/* Profile Strength Meter */}
                        <div className="bg-white border border-slate-100 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-text-muted border-b border-slate-100 pb-2 flex items-center gap-1.5">
                                <Star className="h-3.5 w-3.5 text-indigo-500" /> Profile Strength
                            </h4>
                            <div className="space-y-2">
                                <div className="flex justify-between text-xs font-semibold">
                                    <span>Completeness</span>
                                    <span className="font-mono">85%</span>
                                </div>
                                <div className="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div className="h-full bg-indigo-600" style={{ width: '85%' }} />
                                </div>
                                <p className="text-[10px] text-text-secondary leading-snug">Link your GitHub or verified Wise payout method to reach 100% and earn high-tier job invitations.</p>
                            </div>
                        </div>

                        {/* Secure Payments Shield */}
                        <div className="bg-indigo-50/40 border border-indigo-150/40 rounded-xl p-4 text-[11px] leading-relaxed text-indigo-900 flex gap-2.5">
                            <Lock className="h-4.5 w-4.5 text-indigo-600 shrink-0 mt-0.5" />
                            <div>
                                <span className="font-bold block text-indigo-950 mb-0.5">Escrow Safe Protections</span>
                                Pay out securely under smart contract locks. Funds are audited, verified, and settled immediately upon approval.
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {/* Contextual Side Panel */}
            <ContractQuickView
                isOpen={selectedContract !== null}
                onClose={() => setSelectedContract(null)}
                data={selectedContract}
            />
        </FreelanceLayout>
    );
}
