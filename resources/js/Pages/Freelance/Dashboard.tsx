import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import FreelanceLayout from './Layout';
import { 
    Briefcase,
    Clock,
    DollarSign,
    ChevronRight,
    Search,
    Activity,
    UserCheck,
    CreditCard,
    Plus,
    Coins
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { ContractQuickView } from '@/Components/ContextualPanels';
import { formatDate } from '@/lib/utils';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { ActivityFeed } from '@/Components/ui/ActivityFeed';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { __ } from '@/lib/i18n';

export default function FreelanceDashboard({ 
    stats: initialStats, 
    activeProposals: initialProposals, 
    activeContracts: initialContracts, 
    recentActivities: initialActivities, 
    upcomingBookings: initialBookings,
    clientData: initialClientData
}: any) {
    const { auth } = usePage().props as any;
    const [selectedContract, setSelectedContract] = useState<any>(null);

    const freelanceModeContext = useFreelanceMode();
    const mode = freelanceModeContext?.mode || 'client';
    const isClient = mode === 'client';

    // 1. Freelancer Data Setup
    const stats = initialStats || {
        pointsBalance: auth?.user?.points_balance || 0,
        activeProposals: 0,
        activeContracts: 0,
        totalEarnings: 0,
        currency: auth?.user?.preferred_currency || 'USD'
    };

    const activeProposals = initialProposals || [];
    const activeContracts = initialContracts || [];
    const upcomingBookings = initialBookings || [];

    const recentActivities = initialActivities && initialActivities.length > 0 ? initialActivities : [
        {
            id: 'mock_1',
            description: 'Welcome to your new fully operational workspace.',
            created_at: new Date().toISOString(),
            color: 'indigo',
            icon: 'activity'
        }
    ];

    // 2. Client Data Setup
    const clientData = initialClientData || {
        activeJobs: [],
        activeContracts: [],
        receivedProposals: [],
        stats: {
            activeJobs: 0,
            activeContracts: 0,
            totalContractedValue: 0,
            pointsSpent: 0,
            receivedProposals: 0,
            currencySymbol: null,
            currencyCode: null,
        },
        recentActivities: []
    };

    const clientActivities = clientData.recentActivities && clientData.recentActivities.length > 0 
        ? clientData.recentActivities 
        : [
            {
                id: 'mock_c1',
                description: 'Welcome to your new client dashboard.',
                created_at: new Date().toISOString(),
                color: 'indigo',
                icon: 'activity'
            }
        ];

    // Convert string icon names/types for Activities dynamically
    const mapActivities = (list: any[]) => {
        return list.map((item: any) => ({
            ...item,
            icon: item.type === 'proposal' || item.type === 'proposal_received' ? 'clock' 
                : item.type === 'contract' ? 'check-square' 
                : item.type === 'job' ? 'file-text'
                : 'activity'
        }));
    };

    const freelancerActivitiesMapped = mapActivities(recentActivities);
    const clientActivitiesMapped = mapActivities(clientActivities);

    const freelancerSuggestedActions = [
        {
            id: 1,
            title: __('Submit KYC verification'),
            description: __('Required to secure high-value smart contract payouts.'),
            href: '/kyc',
            icon: UserCheck,
            color: 'text-amber-600 bg-amber-50/80'
        },
        {
            id: 2,
            title: __('Link verified payout source'),
            description: __('Set up Direct Debit for automated settlement clearance.'),
            href: '/financial/payout-methods',
            icon: CreditCard,
            color: 'text-emerald-600 bg-emerald-50/80'
        }
    ];

    const clientSuggestedActions = [
        {
            id: 1,
            title: __('Post a new job'),
            description: __('Publish your project to find and hire verified industry experts.'),
            href: '/freelance/jobs/create',
            icon: Plus,
            color: 'text-indigo-600 bg-indigo-50/80'
        },
        {
            id: 2,
            title: __('Manage payments'),
            description: __('Top up your account balance or view your billing statements.'),
            href: '/financial/add-balance',
            icon: CreditCard,
            color: 'text-emerald-600 bg-emerald-50/80'
        }
    ];

    return (
        <FreelanceLayout>
            <div className="space-y-8">
                <ModulePageHeader 
                    title={`${__('Welcome back')}, ${auth?.user?.name?.split(' ')[0] || __('Partner')}`}
                    description={
                        isClient 
                            ? __("Manage your job listings, hire elite talent, and track contract milestones in real-time.")
                            : __("Monitor your active contracts, pending bids, and operational stats in real-time.")
                    }
                    actions={
                        isClient ? (
                            <Link 
                                href="/freelance/jobs/create" 
                                className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                            >
                                <Plus className="w-4 h-4 mr-2 stroke-[1.5]" /> {__('Post a Job')}
                            </Link>
                        ) : (
                            <Link 
                                href="/freelance/jobs/browse" 
                                className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                            >
                                <Search className="w-4 h-4 mr-2 stroke-[1.5]" /> {__('Browse Jobs')}
                            </Link>
                        )
                    }
                />

                {/* Metrics Grid */}
                {isClient ? (
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <MetricCard 
                            label={__('Contracted Value')}
                            value={clientData.stats.totalContractedValue}
                            icon={DollarSign}
                        />
                        <MetricCard 
                            label={__('Points Spent')}
                            value={clientData.stats.pointsSpent}
                            icon={Coins}
                        />
                        <MetricCard 
                            label={__('Active Contracts')}
                            value={clientData.stats.activeContracts}
                            icon={Briefcase}
                        />
                        <MetricCard 
                            label={__('Open Job Posts')}
                            value={clientData.stats.activeJobs}
                            icon={Briefcase}
                        />
                        <MetricCard 
                            label={__('Received Proposals')}
                            value={clientData.stats.receivedProposals}
                            icon={Clock}
                        />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <MetricCard 
                            label={__('Total Earnings')}
                            value={stats.totalEarnings}
                            icon={DollarSign}
                        />
                        <MetricCard 
                            label={__('Active Contracts')}
                            value={stats.activeContracts}
                            icon={Briefcase}
                        />
                        <MetricCard 
                            label={__('Pending Proposals')}
                            value={stats.activeProposals}
                            icon={Clock}
                        />
                        <MetricCard 
                            label={__('Available Connects')}
                            value={stats.pointsBalance}
                            icon={Activity}
                        />
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left 2 Columns: Lists */}
                    <div className="lg:col-span-2 space-y-8">
                        {isClient ? (
                            <>
                                <OperationalCard title={__('Open Job Posts')} description={__('Your recently published project briefs currently open for bids.')}>
                                    {clientData.activeJobs.length === 0 ? (
                                        <EmptyState 
                                            icon={Briefcase}
                                            title={__('No active job posts')}
                                            description={__('Post a job to start receiving bids from verified industry experts.')}
                                            action="/freelance/jobs/create"
                                            actionLabel={__('Post a Job')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {clientData.activeJobs.map((job: any) => (
                                                <div key={job.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <Link href={`/freelance/jobs/${job.id}`} className="text-sm font-semibold text-slate-900 block truncate hover:text-indigo-600 transition-colors">
                                                            {job.title}
                                                        </Link>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('Budget')}: <FinancialAmount amount={job.budget} currency={job.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; {__('Posted')} {formatDate(job.createdAt)} &bull; {job.proposalsCount} {__('proposals')}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-4 shrink-0">
                                                        <StatusBadge status={job.status} size="sm" />
                                                        <Link 
                                                            href={`/freelance/jobs/${job.id}`} 
                                                            className="text-slate-400 hover:text-slate-900 hover:bg-slate-100 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                        >
                                                            <ChevronRight className="h-4 w-4" />
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </OperationalCard>

                                <OperationalCard title={__('Active Contracts')} description={__('Your active freelancers and ongoing milestones.')}>
                                    {clientData.activeContracts.length === 0 ? (
                                        <EmptyState 
                                            icon={Briefcase}
                                            title={__('No active contracts')}
                                            description={__('Review proposals on your posted jobs to hire your next specialist.')}
                                            action="/freelance/jobs/my-jobs"
                                            actionLabel={__('View My Job Posts')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {clientData.activeContracts.map((contract: any) => (
                                                <div key={contract.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{contract.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('Freelancer')}: {contract.freelancerName} &bull; {__('Started')} {formatDate(contract.startDate)}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-4 shrink-0">
                                                        <StatusBadge status={contract.status} size="sm" />
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            onClick={() => setSelectedContract(contract)} 
                                                            className="text-slate-400 hover:text-slate-900 hover:bg-slate-100 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                        >
                                                            <ChevronRight className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </OperationalCard>

                                <OperationalCard title={__('Received Proposals')} description={__('Proposals awaiting your review from interested freelance professionals.')}>
                                    {clientData.receivedProposals.length === 0 ? (
                                        <EmptyState 
                                            icon={Clock}
                                            title={__('No pending proposals')}
                                            description={__('Bids from interested experts will appear here once you post an open job.')}
                                            action="/freelance/jobs/my-jobs"
                                            actionLabel={__('Manage Job Posts')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {clientData.receivedProposals.map((proposal: any) => (
                                                <div key={proposal.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{proposal.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('Freelancer')}: {proposal.freelancerName} &bull; {__('Bid')}: <FinancialAmount amount={proposal.budget} currency={clientData.stats.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; {__('Submitted')} {formatDate(proposal.submittedAt)}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-4 shrink-0">
                                                        <StatusBadge status={proposal.status} size="sm" />
                                                        <Link 
                                                            href={`/freelance/jobs/my-jobs`} 
                                                            className="text-slate-400 hover:text-slate-900 hover:bg-slate-100 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                        >
                                                            <ChevronRight className="h-4 w-4" />
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </OperationalCard>
                            </>
                        ) : (
                            <>
                                <OperationalCard title={__('Upcoming Appointments')} description={__('Your scheduled consultations and client meetings.')}>
                                    {upcomingBookings.length === 0 ? (
                                        <EmptyState 
                                            icon={Clock}
                                            title={__('No upcoming appointments')}
                                            description={__('Share your booking link to start scheduling consultations.')}
                                            action="/booking"
                                            actionLabel={__('Manage Availability')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {upcomingBookings.map((booking: any) => (
                                                <div key={booking.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0 flex items-center">
                                                        <div className="mr-4 bg-slate-100 rounded-lg p-2 text-center min-w-[50px]">
                                                            <span className="block text-xs font-bold text-slate-500 uppercase">{formatDate(booking.starts_at, 'MMM')}</span>
                                                            <span className="block text-lg font-bold text-slate-900">{formatDate(booking.starts_at, 'd')}</span>
                                                        </div>
                                                        <div>
                                                            <span className="text-sm font-semibold text-slate-900 block truncate">{booking.eventType.title}</span>
                                                            <span className="text-xs text-slate-500 mt-1 block">
                                                                {formatDate(booking.starts_at, 'h:mm a')} &bull; {booking.guest_name}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-4 shrink-0">
                                                        <StatusBadge status={booking.status} size="sm" />
                                                        <Link 
                                                            href={`/booking/appointments`} 
                                                            className="text-slate-400 hover:text-slate-900 hover:bg-slate-100 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                        >
                                                            <ChevronRight className="h-4 w-4" />
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </OperationalCard>

                                <OperationalCard title={__('Active Contracts')} description={__('Your currently running client engagements.')}>
                                    {activeContracts.length === 0 ? (
                                        <EmptyState 
                                            icon={Briefcase}
                                            title={__('No active contracts yet')}
                                            description={__('Start submitting proposals to begin working with clients.')}
                                            action="/freelance/jobs/browse"
                                            actionLabel={__('Browse Jobs')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {activeContracts.map((contract: any) => (
                                                <div key={contract.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{contract.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {contract.clientName} &bull; {__('Started')} {formatDate(contract.startDate)}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-4 shrink-0">
                                                        <StatusBadge status={contract.status} size="sm" />
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            onClick={() => setSelectedContract(contract)} 
                                                            className="text-slate-400 hover:text-slate-900 hover:bg-slate-100 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                        >
                                                            <ChevronRight className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </OperationalCard>

                                <OperationalCard title={__('Submitted Proposals')} description={__('Bids awaiting client review or response.')}>
                                    {activeProposals.length === 0 ? (
                                        <EmptyState 
                                            icon={Clock}
                                            title={__('No pending proposals')}
                                            description={__('Start submitting bids to see active proposals here.')}
                                            action="/freelance/jobs/browse"
                                            actionLabel={__('Browse Jobs')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {activeProposals.map((proposal: any) => (
                                                <div key={proposal.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{proposal.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('Bid')}: <FinancialAmount amount={proposal.budget} currency={stats.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; {__('Submitted')} {formatDate(proposal.submittedAt)}
                                                        </span>
                                                    </div>
                                                    <div className="shrink-0">
                                                        <StatusBadge status={proposal.status} size="sm" />
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </OperationalCard>
                            </>
                        )}
                    </div>

                    {/* Right 1 Column: Activity & Actions */}
                    <div className="space-y-8">
                        <div className="space-y-4">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">{__('Recent Events')}</h4>
                            <ActivityFeed items={isClient ? clientActivitiesMapped : freelancerActivitiesMapped} />
                        </div>

                        <div className="space-y-4 pt-2">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">{__('Suggested Actions')}</h4>
                            <div className="space-y-3">
                                {(isClient ? clientSuggestedActions : freelancerSuggestedActions).map(action => (
                                    <div key={action.id} className="group flex items-start gap-3.5 rounded-xl p-4 border border-slate-100 bg-white hover:border-slate-200 hover:shadow-sm transition-all duration-200">
                                        <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${action.color}`}>
                                            <action.icon className="w-4 h-4 stroke-[1.5]" />
                                        </div>
                                        <div className="flex-1 space-y-0.5">
                                            <Link href={action.href} className="text-xs font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors block">
                                                {action.title}
                                            </Link>
                                            <p className="text-[11px] text-slate-500 leading-relaxed font-medium">
                                                {action.description}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ContractQuickView
                isOpen={selectedContract !== null}
                onClose={() => setSelectedContract(null)}
                data={selectedContract}
            />
        </FreelanceLayout>
    );
}
