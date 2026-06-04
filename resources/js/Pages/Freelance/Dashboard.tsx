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

    const recentActivities = initialActivities && initialActivities.length > 0 ? initialActivities : [];

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
        : [];

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
            title: __('general.submit_kyc_verification'),
            description: __('freelance.required_to_secure_highvalue_smart'),
            href: '/kyc',
            icon: UserCheck,
            color: 'text-amber-600 bg-amber-50/80'
        },
        {
            id: 2,
            title: __('general.top_up_your_balance'),
            description: __('erp.purchase_points_to_continue_bidding'),
            href: '/financial/add-balance',
            icon: Coins,
            color: 'text-indigo-600 bg-indigo-50/80'
        }
    ];

    const clientSuggestedActions = [
        {
            id: 1,
            title: __('freelance.post_a_new_job_2'),
            description: __('erp.publish_your_project_to_find'),
            href: '/freelance/jobs/create',
            icon: Plus,
            color: 'text-indigo-600 bg-indigo-50/80'
        },
        {
            id: 2,
            title: __('general.manage_payments'),
            description: __('billing.top_up_your_account_balance'),
            href: '/financial/add-balance',
            icon: CreditCard,
            color: 'text-emerald-600 bg-emerald-50/80'
        }
    ];

    return (
        <FreelanceLayout>
            <div className="space-y-8">
                <ModulePageHeader 
                    title={`${__('general.welcome_back')}, ${auth?.user?.name?.split(' ')[0] || __('general.partner')}`}
                    description={
                        isClient 
                            ? __("freelance.manage_your_job_listings_hire")
                            : __("freelance.monitor_your_active_contracts_pending")
                    }
                    actions={
                        isClient ? (
                            <Link 
                                href="/freelance/jobs/create" 
                                className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                            >
                                <Plus className="w-4 h-4 mr-2 stroke-[1.5]" /> {__('freelance.post_a_job')}
                            </Link>
                        ) : (
                            <Link 
                                href="/freelance/jobs/browse" 
                                className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                            >
                                <Search className="w-4 h-4 mr-2 stroke-[1.5]" /> {__('freelance.browse_jobs')}
                            </Link>
                        )
                    }
                />

                {/* Metrics Grid */}
                {isClient ? (
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <MetricCard 
                            label={__('general.contracted_value')}
                            value={clientData.stats.totalContractedValue}
                            icon={DollarSign}
                        />
                        <MetricCard 
                            label={__('general.points_spent')}
                            value={clientData.stats.pointsSpent}
                            icon={Coins}
                        />
                        <MetricCard 
                            label={__('freelance.active_contracts')}
                            value={clientData.stats.activeContracts}
                            icon={Briefcase}
                        />
                        <MetricCard 
                            label={__('freelance.open_job_posts')}
                            value={clientData.stats.activeJobs}
                            icon={Briefcase}
                        />
                        <MetricCard 
                            label={__('freelance.received_proposals')}
                            value={clientData.stats.receivedProposals}
                            icon={Clock}
                        />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <MetricCard 
                            label={__('general.total_earnings')}
                            value={stats.totalEarnings}
                            icon={DollarSign}
                        />
                        <MetricCard 
                            label={__('freelance.active_contracts')}
                            value={stats.activeContracts}
                            icon={Briefcase}
                        />
                        <MetricCard 
                            label={__('freelance.pending_proposals')}
                            value={stats.activeProposals}
                            icon={Clock}
                        />
                        <MetricCard 
                            label={__('freelance.available_connects')}
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
                                <OperationalCard title={__('freelance.open_job_posts')} description={__('erp.your_recently_published_project_briefs')}>
                                    {clientData.activeJobs.length === 0 ? (
                                        <EmptyState 
                                            icon={Briefcase}
                                            title={__('freelance.no_active_job_posts')}
                                            description={__('freelance.post_a_job_to_start')}
                                            action="/freelance/jobs/create"
                                            actionLabel={__('freelance.post_a_job')}
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
                                                            {__('erp.budget')}: <FinancialAmount amount={job.budget} currency={job.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; {__('general.posted')} {formatDate(job.createdAt)} &bull; {job.proposalsCount} {__('freelance.proposals')}
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

                                <OperationalCard title={__('freelance.active_contracts')} description={__('general.your_active_freelancers_and_ongoing')}>
                                    {clientData.activeContracts.length === 0 ? (
                                        <EmptyState 
                                            icon={Briefcase}
                                            title={__('freelance.no_active_contracts')}
                                            description={__('freelance.review_proposals_on_your_posted')}
                                            action="/freelance/jobs/my-jobs"
                                            actionLabel={__('freelance.view_my_job_posts')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {clientData.activeContracts.map((contract: any) => (
                                                <div key={contract.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{contract.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('general.freelancer')}: {contract.freelancerName} &bull; {__('general.started')} {formatDate(contract.startDate)}
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

                                <OperationalCard title={__('freelance.received_proposals')} description={__('freelance.proposals_awaiting_your_review_from')}>
                                    {clientData.receivedProposals.length === 0 ? (
                                        <EmptyState 
                                            icon={Clock}
                                            title={__('freelance.no_pending_proposals')}
                                            description={__('freelance.bids_from_interested_experts_will')}
                                            action="/freelance/jobs/my-jobs"
                                            actionLabel={__('freelance.manage_job_posts')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {clientData.receivedProposals.map((proposal: any) => (
                                                <div key={proposal.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{proposal.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('general.freelancer')}: {proposal.freelancerName} &bull; {__('freelance.bid')}: <FinancialAmount amount={proposal.budget} currency={clientData.stats.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; {__('general.submitted')} {formatDate(proposal.submittedAt)}
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
                                <OperationalCard title={__('general.upcoming_appointments')} description={__('erp.your_scheduled_consultations_and_client')}>
                                    {upcomingBookings.length === 0 ? (
                                        <EmptyState 
                                            icon={Clock}
                                            title={__('general.no_upcoming_appointments')}
                                            description={__('general.share_your_booking_link_to')}
                                            action="/booking"
                                            actionLabel={__('general.manage_availability')}
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

                                <OperationalCard title={__('freelance.active_contracts')} description={__('erp.your_currently_running_client_engagements')}>
                                    {activeContracts.length === 0 ? (
                                        <EmptyState 
                                            icon={Briefcase}
                                            title={__('freelance.no_active_contracts_yet')}
                                            description={__('erp.start_submitting_proposals_to_begin')}
                                            action="/freelance/jobs/browse"
                                            actionLabel={__('freelance.browse_jobs')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {activeContracts.map((contract: any) => (
                                                <div key={contract.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{contract.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {contract.clientName} &bull; {__('general.started')} {formatDate(contract.startDate)}
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

                                <OperationalCard title={__('freelance.submitted_proposals')} description={__('erp.bids_awaiting_client_review_or')}>
                                    {activeProposals.length === 0 ? (
                                        <EmptyState 
                                            icon={Clock}
                                            title={__('freelance.no_pending_proposals')}
                                            description={__('freelance.start_submitting_bids_to_see')}
                                            action="/freelance/jobs/browse"
                                            actionLabel={__('freelance.browse_jobs')}
                                        />
                                    ) : (
                                        <div className="divide-y divide-slate-100">
                                            {activeProposals.map((proposal: any) => (
                                                <div key={proposal.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                                    <div className="min-w-0">
                                                        <span className="text-sm font-semibold text-slate-900 block truncate">{proposal.title}</span>
                                                        <span className="text-xs text-slate-500 mt-1 block">
                                                            {__('freelance.bid')}: <FinancialAmount amount={proposal.budget} currency={stats.currency} className="text-xs font-mono font-medium text-slate-600" /> &bull; {__('general.submitted')} {formatDate(proposal.submittedAt)}
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
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">{__('general.recent_events')}</h4>
                            <ActivityFeed items={isClient ? clientActivitiesMapped : freelancerActivitiesMapped} />
                        </div>

                        <div className="space-y-4 pt-2">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">{__('general.suggested_actions')}</h4>
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
