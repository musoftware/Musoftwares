import { Head, Link } from '@inertiajs/react';
import FreelanceLayout from './Layout';
import { useState } from 'react';
import {
    Briefcase,
    CheckCircle2,
    Clock,
    DollarSign,
    ChevronRight,
    Star,
    Coins,
    ArrowUpRight,
    ShieldCheck,
    Search
} from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';
import { ContractQuickView } from '@/Components/ContextualPanels';

export default function FreelanceDashboard({ auth, stats: initialStats, activeProposals: initialProposals, activeContracts: initialContracts, recentActivities: initialActivities }: any) {
    const [selectedContract, setSelectedContract] = useState<any>(null);

    // Dynamic state parameters from server
    const stats = initialStats || {
        pointsBalance: auth?.user?.points_balance || 0,
        activeProposals: 0,
        activeContracts: 0,
        totalEarnings: 0
    };

    const activeProposals = initialProposals || [];
    const activeContracts = initialContracts || [];

    // Operational recent activity items to make the dashboard feel "alive"
    const recentActivities = initialActivities && initialActivities.length > 0 ? initialActivities : [
        {
            id: 'mock_1',
            type: 'system',
            text: 'Welcome to your new fully operational workspace.',
            time: 'Just now',
            color: 'text-indigo-500 bg-indigo-50'
        }
    ];

    return (
        <FreelanceLayout auth={auth} clean={true}>
            <Head title="Freelance Dashboard" />

            <div className="space-y-8 font-sans pb-12">
                
                {/* ─────────────────────────────────────────
                    1. HEADER / GREETING (Quiet & Calm)
                    ───────────────────────────────────────── */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">
                            Welcome back, {auth?.user?.name?.split(' ')[0] || 'Partner'}
                        </h1>
                        <p className="text-xs text-slate-500">
                            Monitor your active contracts, pending bids, and operational stats in real-time.
                        </p>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    2. KEY OPERATIONAL STATS (Borderless, Minimal)
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-6 p-6 bg-white border border-slate-200/70 rounded-xl shadow-sm">
                    {/* Total Completed Earnings */}
                    <div className="space-y-1">
                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                            Total Earnings
                        </span>
                        <div className="flex items-baseline gap-1.5">
                            <span className="text-2xl font-bold tracking-tight text-slate-900 font-mono">
                                {formatMoney(stats.totalEarnings, 'USD')}
                            </span>
                        </div>
                        <span className="text-[10px] text-emerald-600 font-medium block">
                            Payout cleared safely
                        </span>
                    </div>

                    {/* Active Contracts */}
                    <div className="space-y-1 border-l border-slate-100 pl-6">
                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                            Active Contracts
                        </span>
                        <div className="flex items-baseline gap-1.5">
                            <span className="text-2xl font-bold tracking-tight text-slate-900 font-mono">
                                {stats.activeContracts}
                            </span>
                        </div>
                        <span className="text-[10px] text-slate-500 block">
                            Contracts in progress
                        </span>
                    </div>

                    {/* Pending Proposals */}
                    <div className="space-y-1 border-l border-slate-100 pl-6">
                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                            Pending Proposals
                        </span>
                        <div className="flex items-baseline gap-1.5">
                            <span className="text-2xl font-bold tracking-tight text-slate-900 font-mono">
                                {stats.activeProposals}
                            </span>
                        </div>
                        <span className="text-[10px] text-slate-500 block">
                            Submitted active bids
                        </span>
                    </div>

                    {/* Available Connects */}
                    <div className="space-y-1 border-l border-slate-100 pl-6">
                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider block flex items-center gap-1">
                            Available Connects
                        </span>
                        <div className="flex items-baseline gap-2">
                            <span className="text-2xl font-bold tracking-tight text-slate-900 font-mono">
                                {stats.pointsBalance}
                            </span>
                            <Link 
                                href="/freelance/points" 
                                className="text-[10px] text-indigo-600 font-semibold hover:text-indigo-800 transition-colors"
                            >
                                Buy Packages →
                            </Link>
                        </div>
                        <span className="text-[10px] text-slate-500 block">
                            Monthly quota refreshed
                        </span>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    3. MAIN OPERATIONAL WORKSPACE GRID
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    {/* Left Column (8/12 - Active Work & Bids) */}
                    <div className="lg:col-span-8 space-y-8">
                        
                        {/* Section 1: Active Contracts */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="w-1.5 h-1.5 bg-emerald-500 rounded-full" />
                                    <h3 className="text-sm font-semibold text-slate-900 tracking-tight">
                                        Active Contract Agreements
                                    </h3>
                                </div>
                                <span className="text-xs text-slate-400 font-mono">
                                    {activeContracts.length} Active
                                </span>
                            </div>

                            <div className="bg-white border border-slate-200/70 rounded-xl divide-y divide-slate-100 overflow-hidden shadow-sm">
                                {activeContracts.map((contract: any) => (
                                    <div 
                                        key={contract.id}
                                        onClick={() => setSelectedContract(contract)}
                                        className="p-4 sm:p-5 hover:bg-slate-50/60 cursor-pointer transition flex items-center justify-between group"
                                    >
                                        <div className="space-y-1 pr-4">
                                            <span className="font-semibold text-slate-900 text-sm group-hover:text-indigo-600 transition-colors block">
                                                {contract.title}
                                            </span>
                                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                                <span>Client: <span className="font-medium text-slate-700">{contract.clientName}</span></span>
                                                <span className="text-slate-300">•</span>
                                                <span>Started: {formatDate(contract.startDate)}</span>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-6 shrink-0">
                                            <div className="w-24 sm:w-28 space-y-1">
                                                <div className="flex justify-between text-[10px]">
                                                    <span className="text-slate-400">Milestone</span>
                                                    <span className="font-mono font-semibold text-slate-700">{contract.progress}%</span>
                                                </div>
                                                <div className="h-1 w-full rounded-full bg-slate-100 overflow-hidden">
                                                    <div className="h-full bg-emerald-500 rounded-full" style={{ width: `${contract.progress}%` }} />
                                                </div>
                                            </div>
                                            <ChevronRight className="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                        </div>
                                    </div>
                                ))}

                                {activeContracts.length === 0 && (
                                    <div className="py-10 px-4 text-center">
                                        <p className="text-xs text-slate-500">No active contracts at the moment.</p>
                                        <Link 
                                            href="/freelance/jobs/browse" 
                                            className="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
                                        >
                                            Explore open freelance contracts <ChevronRight className="h-3 w-3" />
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Section 2: Submitted Proposals */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="w-1.5 h-1.5 bg-indigo-500 rounded-full" />
                                    <h3 className="text-sm font-semibold text-slate-900 tracking-tight">
                                        Submitted Project Proposals
                                    </h3>
                                </div>
                                <Link 
                                    href="/freelance/jobs/browse"
                                    className="text-xs text-indigo-600 font-semibold hover:text-indigo-800 transition-colors flex items-center gap-0.5"
                                >
                                    Browse Jobs <ChevronRight className="h-3 w-3" />
                                </Link>
                            </div>

                            <div className="bg-white border border-slate-200/70 rounded-xl divide-y divide-slate-100 overflow-hidden shadow-sm">
                                {activeProposals.map((proposal: any) => (
                                    <div 
                                        key={proposal.id}
                                        className="p-4 sm:p-5 hover:bg-slate-50/40 transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1 pr-4">
                                            <span className="font-semibold text-slate-900 text-sm block">
                                                {proposal.title}
                                            </span>
                                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                                <span>Submitted {formatDate(proposal.submittedAt)}</span>
                                                <span className="text-slate-300">•</span>
                                                <span>{proposal.connectsUsed} Connects used</span>
                                            </div>
                                        </div>
                                        <div className="text-right shrink-0">
                                            <span className="font-semibold text-slate-900 text-sm block font-mono">
                                                {formatMoney(proposal.budget, 'USD')}
                                            </span>
                                            <span className="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-250/20 px-2 py-0.5 rounded-full inline-block mt-1.5 uppercase tracking-wide">
                                                {proposal.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}

                                {activeProposals.length === 0 && (
                                    <div className="py-10 px-4 text-center">
                                        <p className="text-xs text-slate-500 font-medium text-slate-600">No submitted proposals found.</p>
                                        <p className="text-[11px] text-slate-400 mt-0.5">Start submitting bids to see active proposals here.</p>
                                        <Link 
                                            href="/freelance/jobs/browse" 
                                            className="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
                                        >
                                            Browse Jobs <ChevronRight className="h-3 w-3" />
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                    </div>

                    {/* Right Column (4/12 - Compact operational sidebar context) */}
                    <div className="lg:col-span-4 space-y-8">
                        
                        {/* A. Profile Strength (Sleek Inline Widget) */}
                        <div className="bg-white border border-slate-200/70 rounded-xl p-5 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                    <Star className="h-3.5 w-3.5 text-indigo-500" /> Account Status
                                </h4>
                                <span className="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200/50 px-1.5 py-0.5 rounded font-semibold uppercase">
                                    Active
                                </span>
                            </div>
                            
                            <div className="space-y-3">
                                <div className="space-y-1.5">
                                    <div className="flex justify-between text-xs text-slate-700">
                                        <span className="font-medium">Profile Completeness</span>
                                        <span className="font-mono font-bold text-slate-900">85%</span>
                                    </div>
                                    <div className="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div className="h-full bg-slate-900 rounded-full" style={{ width: '85%' }} />
                                    </div>
                                </div>
                                <p className="text-[11px] text-slate-400 leading-normal">
                                    Upload verified KYC documentation or add direct Wise settlement credentials to unlock 100% premium ranking.
                                </p>
                            </div>
                        </div>

                        {/* B. Recommended Actions (Pending Actions) */}
                        <div className="space-y-3">
                            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 px-1">
                                Recommended Actions
                            </h4>
                            
                            <div className="space-y-2">
                                {/* Action 1 */}
                                <div className="flex items-start gap-3 p-3 bg-white border border-slate-200/50 rounded-xl shadow-xs hover:border-slate-300 transition-colors">
                                    <div className="w-1.5 h-1.5 bg-indigo-500 rounded-full mt-1.5 shrink-0" />
                                    <div className="space-y-0.5">
                                        <Link href="/kyc" className="text-xs font-semibold text-slate-800 hover:text-indigo-600 block transition-colors">
                                            Submit KYC documentation
                                        </Link>
                                        <p className="text-[10px] text-slate-400 leading-normal">
                                            Required to secure high-value smart contract payouts.
                                        </p>
                                    </div>
                                </div>

                                {/* Action 2 */}
                                <div className="flex items-start gap-3 p-3 bg-white border border-slate-200/50 rounded-xl shadow-xs hover:border-slate-300 transition-colors">
                                    <div className="w-1.5 h-1.5 bg-indigo-500 rounded-full mt-1.5 shrink-0" />
                                    <div className="space-y-0.5">
                                        <Link href="/financial/payout-methods" className="text-xs font-semibold text-slate-800 hover:text-indigo-600 block transition-colors">
                                            Link verified payout source
                                        </Link>
                                        <p className="text-[10px] text-slate-400 leading-normal">
                                            Set up Wise or Direct Debit for automated settlement clearance.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* C. Recent Activities (Operational Timeline) */}
                        <div className="space-y-3">
                            <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 px-1">
                                Recent Events
                            </h4>
                            
                            <div className="bg-white border border-slate-200/70 rounded-xl p-4 shadow-sm space-y-3">
                                {recentActivities.map((act: any) => (
                                    <div key={act.id} className="flex gap-3 text-xs">
                                        <div className="w-1 bg-slate-100 rounded-full self-stretch shrink-0 relative">
                                            <div className="absolute top-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-400 rounded-full" />
                                        </div>
                                        <div className="space-y-0.5 flex-1">
                                            <p className="text-slate-700 leading-tight">{act.text}</p>
                                            <span className="text-[10px] text-slate-400 font-mono block">
                                                {act.time}
                                            </span>
                                        </div>
                                    </div>
                                ))}
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
