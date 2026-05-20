import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/utils';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { PageHeader } from '@/Components/ui/PageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { cn } from '@/lib/utils';
import {
    FileText, Clock, CheckCircle2, XCircle, ChevronRight,
    Search, Briefcase, Loader2, AlertCircle, Trash2,
} from 'lucide-react';

const AppLayout   = AuthenticatedLayout;
const AppPage     = ({ children }: { children: React.ReactNode }) =>
    <div className="max-w-[1100px] mx-auto px-4 py-8 space-y-6">{children}</div>;
const SectionCard = ({ children, className, ...props }: { children: React.ReactNode; className?: string; [x: string]: any }) =>
    <Card className={cn('shadow-sm border-gray-200 overflow-hidden', className)} {...props}>{children}</Card>;

const STATUS_CONFIG: Record<string, { label: string; icon: React.ElementType; className: string }> = {
    pending:  { label: 'Pending',  icon: Clock,         className: 'bg-amber-50  text-amber-700  border-amber-200'  },
    accepted: { label: 'Accepted', icon: CheckCircle2,  className: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    rejected: { label: 'Rejected', icon: XCircle,       className: 'bg-red-50    text-red-700    border-red-200'    },
};

function StatusPill({ status }: { status: string }) {
    const cfg = STATUS_CONFIG[status] ?? { label: status, icon: AlertCircle, className: 'bg-slate-50 text-slate-600 border-slate-200' };
    const Icon = cfg.icon;
    return (
        <span className={cn('inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border', cfg.className)}>
            <Icon className="h-3 w-3" />
            {cfg.label}
        </span>
    );
}

const FILTERS = ['all', 'pending', 'accepted', 'rejected'] as const;
type Filter = typeof FILTERS[number];

export default function ProposalsIndex({ proposals, stats }: any) {
    const { auth } = usePage().props as any;
    const globalCurrency = auth?.user?.preferred_currency || 'USD';

    const [filter, setFilter]       = useState<Filter>('all');
    const [withdrawing, setWithd]   = useState<number | null>(null);

    const allProposals: any[] = proposals?.data ?? [];
    const displayed = filter === 'all'
        ? allProposals
        : allProposals.filter((p: any) => p.status === filter);

    const handleWithdraw = (proposalId: number) => {
        if (!confirm('Withdraw this proposal? This cannot be undone.')) return;
        setWithd(proposalId);
        router.delete(`/freelance/proposals/${proposalId}/withdraw`, {
            preserveScroll: true,
            onFinish: () => setWithd(null),
        });
    };

    const statCards = [
        { label: 'Total Submitted', value: stats?.total    ?? 0, icon: FileText,     color: 'text-indigo-600 bg-indigo-50' },
        { label: 'Pending Review',  value: stats?.pending  ?? 0, icon: Clock,        color: 'text-amber-600  bg-amber-50'  },
        { label: 'Accepted',        value: stats?.accepted ?? 0, icon: CheckCircle2, color: 'text-emerald-600 bg-emerald-50' },
        { label: 'Rejected',        value: stats?.rejected ?? 0, icon: XCircle,      color: 'text-red-600    bg-red-50'    },
    ];

    return (
        <AppLayout header="My Proposals">
            <Head title="My Proposals – Freelance" />
            <AppPage>
                <PageHeader
                    title="My Proposals"
                    subtitle="Track the status of every bid you've submitted across all jobs."
                    icon={FileText}
                    actions={
                        <Link
                            href="/freelance/jobs/browse"
                            className="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm"
                        >
                            <Search className="h-4 w-4" /> Browse Jobs
                        </Link>
                    }
                />

                {/* Stat row */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {statCards.map((s) => {
                        const Icon = s.icon;
                        return (
                            <SectionCard key={s.label} className="p-4 flex items-center gap-3">
                                <div className={cn('w-9 h-9 rounded-lg flex items-center justify-center shrink-0', s.color)}>
                                    <Icon className="h-4 w-4" />
                                </div>
                                <div>
                                    <p className="text-2xl font-black text-slate-900 leading-none">{s.value}</p>
                                    <p className="text-[11px] text-slate-500 mt-0.5">{s.label}</p>
                                </div>
                            </SectionCard>
                        );
                    })}
                </div>

                {/* Filter tabs */}
                <div className="flex gap-1.5 flex-wrap">
                    {FILTERS.map((f) => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={cn(
                                'px-3.5 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider border transition-colors',
                                filter === f
                                    ? 'bg-slate-900 text-white border-slate-900'
                                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                            )}
                        >
                            {f === 'all' ? `All (${stats?.total ?? 0})` : `${STATUS_CONFIG[f]?.label} (${stats?.[f] ?? 0})`}
                        </button>
                    ))}
                </div>

                {/* Proposals list */}
                {displayed.length === 0 ? (
                    <SectionCard>
                        <EmptyState
                            icon={Briefcase}
                            title="No proposals yet"
                            description={filter === 'all'
                                ? "You haven't submitted any proposals. Browse open jobs to get started."
                                : `No ${filter} proposals to display.`}
                            action="/freelance/jobs/browse"
                            actionLabel="Browse Jobs"
                        />
                    </SectionCard>
                ) : (
                    <div className="space-y-3">
                        {displayed.map((proposal: any) => (
                            <SectionCard
                                key={proposal.id}
                                className="group hover:border-indigo-200 transition-colors"
                            >
                                <div className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div className="flex-1 min-w-0 space-y-1">
                                        <Link
                                            href={`/freelance/jobs/${proposal.job?.id}`}
                                            className="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors truncate block"
                                        >
                                            {proposal.job?.title ?? 'Unknown Job'}
                                        </Link>
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                            <span className="flex items-center gap-1">
                                                <Clock className="h-3 w-3 text-slate-400" />
                                                Submitted {formatDate(proposal.created_at)}
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <Briefcase className="h-3 w-3 text-slate-400" />
                                                {proposal.job?.type === 'hourly' ? 'Hourly' : 'Fixed Price'}
                                            </span>
                                        </div>
                                        <p className="text-xs text-slate-600 line-clamp-1 mt-1">
                                            {proposal.cover_letter}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-4 shrink-0">
                                        <div className="text-right">
                                            <FinancialAmount
                                                amount={proposal.bid_amount}
                                                currency={proposal.currency_code || globalCurrency}
                                                className="text-base font-black text-slate-900"
                                            />
                                            <p className="text-[10px] text-slate-400">Your Bid</p>
                                        </div>

                                        <StatusPill status={proposal.status} />

                                        {proposal.status === 'pending' && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleWithdraw(proposal.id)}
                                                disabled={withdrawing === proposal.id}
                                                className="text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 h-auto rounded-md shadow-none transition-colors"
                                                title="Withdraw proposal"
                                            >
                                                {withdrawing === proposal.id
                                                    ? <Loader2 className="h-4 w-4 animate-spin" />
                                                    : <Trash2 className="h-4 w-4" />
                                                }
                                            </Button>
                                        )}

                                        {proposal.status === 'accepted' && (
                                            <Link
                                                href="/freelance/contracts"
                                                className="text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 p-1.5 rounded-md transition-colors"
                                                title="View Contract"
                                            >
                                                <ChevronRight className="h-4 w-4" />
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </SectionCard>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {proposals?.links && proposals.data?.length > 0 && (
                    <div className="flex justify-center gap-2 pt-4">
                        {proposals.links.map((link: any, i: number) => (
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    className={cn(
                                        'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors',
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'
                                    )}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-100 bg-slate-50 text-slate-400 cursor-not-allowed"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            )
                        ))}
                    </div>
                )}
            </AppPage>
        </AppLayout>
    );
}
