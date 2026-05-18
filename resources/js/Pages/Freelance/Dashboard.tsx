import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
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
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { ActivityFeed } from '@/Components/ui/ActivityFeed';

export default function FreelanceDashboard({ stats: initialStats, activeProposals: initialProposals, activeContracts: initialContracts, recentActivities: initialActivities, upcomingBookings: initialBookings }: any) {
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
    const upcomingBookings = initialBookings || [];

    const recentActivities = initialActivities && initialActivities.length > 0 ? initialActivities : [
        {
            id: 'mock_1',
            text: 'Welcome to your new fully operational workspace.',
            time: 'Just now',
            color: 'bg-indigo-100',
            icon: Activity
        }
    ];

    const suggestedActions = [
        {
            id: 1,
            title: 'Submit KYC verification',
            description: 'Required to secure high-value smart contract payouts.',
            href: '/kyc',
            icon: UserCheck,
            color: 'text-amber-600 bg-amber-50/80'
        },
        {
            id: 2,
            title: 'Link verified payout source',
            description: 'Set up Direct Debit for automated settlement clearance.',
            href: '/financial/payout-methods',
            icon: CreditCard,
            color: 'text-emerald-600 bg-emerald-50/80'
        }
    ];

    const menuItems = [
        { id: 'dashboard', label: 'Dashboard', icon: Briefcase, href: '/freelance', isActive: true },
        { id: 'jobs', label: 'Find Work', icon: Search, href: '/freelance/jobs/browse', isActive: false },
        { id: 'contracts', label: 'My Contracts', icon: Clock, href: '/freelance/contracts', isActive: false },
    ];

    return (
        <WorkspaceLayout 
            title="Freelance Hub"
            workspaceName="Freelance Hub"
            tenantId="FR-DRAFT"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title={`Welcome back, ${auth?.user?.name?.split(' ')[0] || 'Partner'}`}
                    description="Monitor your active contracts, pending bids, and operational stats in real-time."
                    actions={
                        <Link 
                            href="/freelance/jobs/browse" 
                            className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-sm"
                        >
                            <Search className="w-4 h-4 mr-2 stroke-[1.5]" /> Browse Jobs
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <MetricCard 
                        label="Total Earnings"
                        value={stats.totalEarnings}
                        icon={DollarSign}
                    />
                    <MetricCard 
                        label="Active Contracts"
                        value={stats.activeContracts}
                        icon={Briefcase}
                    />
                    <MetricCard 
                        label="Pending Proposals"
                        value={stats.activeProposals}
                        icon={Clock}
                    />
                    <MetricCard 
                        label="Available Connects"
                        value={stats.pointsBalance}
                        icon={Activity}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-8">
                        <OperationalCard title="Upcoming Appointments" description="Your scheduled consultations and client meetings.">
                            {upcomingBookings.length === 0 ? (
                                <EmptyState 
                                    icon={Clock}
                                    title="No upcoming appointments"
                                    description="Share your booking link to start scheduling consultations."
                                    action="/booking"
                                    actionLabel="Manage Availability"
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

                        <OperationalCard title="Active Contracts" description="Your currently running client engagements.">
                            {activeContracts.length === 0 ? (
                                <EmptyState 
                                    icon={Briefcase}
                                    title="No active contracts yet"
                                    description="Start submitting proposals to begin working with clients."
                                    action="/freelance/jobs/browse"
                                    actionLabel="Browse Jobs"
                                />
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {activeContracts.map((contract: any) => (
                                        <div key={contract.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                            <div className="min-w-0">
                                                <span className="text-sm font-semibold text-slate-900 block truncate">{contract.title}</span>
                                                <span className="text-xs text-slate-500 mt-1 block">
                                                    {contract.clientName} &bull; Started {formatDate(contract.startDate)}
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

                        <OperationalCard title="Submitted Proposals" description="Bids awaiting client review or response.">
                            {activeProposals.length === 0 ? (
                                <EmptyState 
                                    icon={Clock}
                                    title="No pending proposals"
                                    description="Start submitting bids to see active proposals here."
                                    action="/freelance/jobs/browse"
                                    actionLabel="Browse Jobs"
                                />
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {activeProposals.map((proposal: any) => (
                                        <div key={proposal.id} className="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 px-2 rounded-lg transition-colors -mx-2">
                                            <div className="min-w-0">
                                                <span className="text-sm font-semibold text-slate-900 block truncate">{proposal.title}</span>
                                                <span className="text-xs text-slate-500 mt-1 block">
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
                        </OperationalCard>
                    </div>

                    <div className="space-y-8">
                        <div className="space-y-4">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Recent Events</h4>
                            <ActivityFeed items={recentActivities} />
                        </div>

                        <div className="space-y-4 pt-2">
                            <h4 className="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Suggested Actions</h4>
                            <div className="space-y-3">
                                {suggestedActions.map(action => (
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
        </WorkspaceLayout>
    );
}
