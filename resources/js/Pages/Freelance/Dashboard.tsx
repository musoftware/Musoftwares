import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Briefcase,
    Clock,
    DollarSign,
    ChevronRight,
    Search,
    Activity,
    UserCheck,
    CreditCard
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { ContractQuickView } from '@/Components/ContextualPanels';
import { formatDate } from '@/lib/utils';

const SleekStatCard = ({ label, value, icon: Icon, description }: any) => {
    return (
        <div className="group rounded-xl border border-gray-100 bg-white p-5 shadow-[0_1px_3px_rgba(0,0,0,0.01)] hover:border-gray-200/80 transition-all duration-200">
            <div className="flex items-center justify-between gap-2">
                <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">{label}</span>
                {Icon && <Icon className="w-4 h-4 text-slate-400 stroke-[1.5] group-hover:text-slate-600 transition-colors" />}
            </div>
            <div className="mt-3">
                <div className="text-2xl font-bold tracking-tight text-slate-900 font-sans">
                    {value}
                </div>
            </div>
            {description && (
                <p className="mt-1 text-[11px] text-slate-400 font-normal">{description}</p>
            )}
        </div>
    );
};

const MinimalEmptyState = ({ title, description, actionHref, actionLabel, icon: Icon }: any) => (
    <div className="flex flex-col items-center justify-center py-8 px-4 text-center rounded-xl border border-dashed border-gray-200 bg-gray-50/20">
        {Icon && <Icon className="w-5 h-5 text-gray-400 mb-2 stroke-[1.5]" />}
        <h4 className="text-xs font-semibold text-gray-900 uppercase tracking-wider">{title}</h4>
        <p className="text-xs text-gray-400 mt-1 max-w-[280px] leading-normal">{description}</p>
        {actionHref && (
            <Link 
                href={actionHref} 
                className="mt-4 inline-flex items-center justify-center px-3.5 py-1.5 text-xs font-medium text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors shadow-sm"
            >
                {actionLabel}
            </Link>
        )}
    </div>
);

const SleekActivityTimeline = ({ activities }: { activities: any[] }) => (
    <div className="space-y-5 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[1px] before:bg-slate-100">
        {activities.map((act) => (
            <div key={act.id} className="relative flex gap-4 pl-1">
                <div className="w-[15px] h-[15px] rounded-full border-2 border-white bg-slate-200 mt-1 shrink-0 flex items-center justify-center shadow-sm z-10">
                    <div className="w-[5px] h-[5px] rounded-full bg-slate-500" />
                </div>
                <div className="flex-1 min-w-0">
                    <p className="text-xs text-slate-600 leading-relaxed font-normal">{act.text}</p>
                    <span className="text-[10px] text-slate-400 mt-0.5 block">{act.time}</span>
                </div>
            </div>
        ))}
    </div>
);

const SleekSuggestedActions = () => (
    <div className="space-y-3">
        <div className="group flex items-start gap-3.5 rounded-xl p-3 border border-slate-100/60 bg-white hover:border-slate-200 hover:bg-slate-50/30 transition-all duration-200 shadow-[0_1px_2px_rgba(0,0,0,0.01)]">
            <div className="w-8 h-8 rounded-lg bg-amber-50/80 flex items-center justify-center shrink-0">
                <UserCheck className="w-4 h-4 text-amber-600 stroke-[1.5]" />
            </div>
            <div className="flex-1 space-y-0.5">
                <Link href="/kyc" className="text-xs font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors block">
                    Submit KYC verification
                </Link>
                <p className="text-[11px] text-slate-400 leading-normal font-normal">
                    Required to secure high-value smart contract payouts.
                </p>
            </div>
        </div>
        
        <div className="group flex items-start gap-3.5 rounded-xl p-3 border border-slate-100/60 bg-white hover:border-slate-200 hover:bg-slate-50/30 transition-all duration-200 shadow-[0_1px_2px_rgba(0,0,0,0.01)]">
            <div className="w-8 h-8 rounded-lg bg-emerald-50/80 flex items-center justify-center shrink-0">
                <CreditCard className="w-4 h-4 text-emerald-600 stroke-[1.5]" />
            </div>
            <div className="flex-1 space-y-0.5">
                <Link href="/financial/payout-methods" className="text-xs font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors block">
                    Link verified payout source
                </Link>
                <p className="text-[11px] text-slate-400 leading-normal font-normal">
                    Set up Direct Debit for automated settlement clearance.
                </p>
            </div>
        </div>
    </div>
);

export default function FreelanceDashboard({ stats: initialStats, activeProposals: initialProposals, activeContracts: initialContracts, recentActivities: initialActivities }: any) {
    const { auth } = usePage().props as any;
    const [selectedContract, setSelectedContract] = useState<any>(null);

    const stats = initialStats || {
        pointsBalance: auth?.user?.points_balance || 0,
        activeProposals: 0,
        activeContracts: 0,
        totalEarnings: 0,
        currency: auth?.user?.preferred_currency || 'USD'
    };

    const activeProposals = initialProposals || [];
    const activeContracts = initialContracts || [];

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
        <AuthenticatedLayout header={undefined}>
            <Head title="Freelance Hub" />

            <div className="max-w-[1100px] mx-auto px-4 sm:px-6 py-10 space-y-10">
                {/* 1. Welcome Area */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                            Welcome back, {auth?.user?.name?.split(' ')[0] || 'Partner'}
                        </h1>
                        <p className="text-sm text-slate-400 mt-1 font-normal tracking-wide">
                            Monitor your active contracts, pending bids, and operational stats in real-time.
                        </p>
                    </div>
                    <div>
                        <Link 
                            href="/freelance/jobs/browse" 
                            className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                        >
                            <Search className="w-4 h-4 mr-2 stroke-[1.5]" /> Browse Jobs
                        </Link>
                    </div>
                </div>

                {/* 2. Compact Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <SleekStatCard 
                        label="Total Earnings"
                        value={<FinancialAmount amount={stats.totalEarnings} currency={stats.currency} className="text-2xl font-semibold font-sans text-slate-900" />}
                        icon={DollarSign}
                    />
                    <SleekStatCard 
                        label="Active Contracts"
                        value={stats.activeContracts}
                        icon={Briefcase}
                    />
                    <SleekStatCard 
                        label="Pending Proposals"
                        value={stats.activeProposals}
                        icon={Clock}
                    />
                    <SleekStatCard 
                        label="Available Connects"
                        value={stats.pointsBalance}
                        icon={Activity}
                        description="Connects balance"
                    />
                </div>

                {/* 3. Main Workspace Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    {/* Left Column (2/3) - Current Work */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* Active Contracts */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900 tracking-tight">Active Contracts</h3>
                                    <p className="text-xs text-slate-400 mt-0.5">Your currently running client engagements.</p>
                                </div>
                            </div>

                            {activeContracts.length === 0 ? (
                                <MinimalEmptyState 
                                    icon={Briefcase}
                                    title="No active contracts yet"
                                    description="Start submitting proposals to begin working with clients."
                                    actionHref="/freelance/jobs/browse"
                                    actionLabel="Browse Jobs"
                                />
                            ) : (
                                <div className="divide-y divide-gray-100">
                                    {activeContracts.map((contract: any) => (
                                        <div key={contract.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/30 px-2 rounded-lg transition-colors">
                                            <div className="min-w-0">
                                                <span className="text-sm font-semibold text-slate-900 block truncate">{contract.title}</span>
                                                <span className="text-xs text-slate-400 mt-1 block">
                                                    {contract.clientName} &bull; Started {formatDate(contract.startDate)}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-4 shrink-0">
                                                <StatusBadge status={contract.status} size="sm" />
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    onClick={() => setSelectedContract(contract)} 
                                                    className="text-slate-400 hover:text-slate-900 hover:bg-slate-50 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                >
                                                    <ChevronRight className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Submitted Proposals */}
                        <div className="space-y-4 pt-4">
                            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900 tracking-tight">Submitted Proposals</h3>
                                    <p className="text-xs text-slate-400 mt-0.5">Bids awaiting client review or response.</p>
                                </div>
                            </div>

                            {activeProposals.length === 0 ? (
                                <MinimalEmptyState 
                                    icon={Clock}
                                    title="No pending proposals"
                                    description="Start submitting bids to see active proposals here."
                                    actionHref="/freelance/jobs/browse"
                                    actionLabel="Browse Jobs"
                                />
                            ) : (
                                <div className="divide-y divide-gray-100">
                                    {activeProposals.map((proposal: any) => (
                                        <div key={proposal.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/30 px-2 rounded-lg transition-colors">
                                            <div className="min-w-0">
                                                <span className="text-sm font-semibold text-slate-900 block truncate">{proposal.title}</span>
                                                <span className="text-xs text-slate-400 mt-1 block">
                                                    Bid: <FinancialAmount amount={proposal.budget} currency={stats.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; Submitted {formatDate(proposal.submittedAt)}
                                                </span>
                                            </div>
                                            <div className="shrink-0">
                                                <StatusBadge status={proposal.status} size="sm" />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column (1/3) - Sidebar (Events & Actions) */}
                    <div className="space-y-8 lg:pl-4">
                        {/* Activity Feed */}
                        <div className="space-y-4">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Recent Events</h4>
                            <div className="relative pl-1">
                                <SleekActivityTimeline activities={recentActivities} />
                            </div>
                        </div>

                        {/* Suggested Actions */}
                        <div className="space-y-4 pt-2">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Suggested Actions</h4>
                            <SleekSuggestedActions />
                        </div>
                    </div>
                </div>
            </div>

            <ContractQuickView
                isOpen={selectedContract !== null}
                onClose={() => setSelectedContract(null)}
                data={selectedContract}
            />
        </AuthenticatedLayout>
    );
}
