import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import FreelanceLayout from '../Layout';
import { Card } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/utils';
import { CurrencyDisplay as FinancialAmount } from '@/Components/ui/CurrencyDisplay';
import { PageHeader } from '@/Components/ui/PageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    FileText, Clock, CheckCircle2, XCircle, ChevronRight,
    Briefcase, AlertCircle, DollarSign, ShieldAlert, Activity,
} from 'lucide-react';

import { FreelanceCard } from '@/Components/Freelance/ui/FreelanceCard';
import { FreelanceStatusPill } from '@/Components/Freelance/ui/FreelanceStatusPill';

const AppLayout   = FreelanceLayout;
const AppPage     = ({ children }: { children: React.ReactNode }) =>
    <div className="w-full space-y-6">{children}</div>;

function ProgressBar({ value }: { value: number }) {
    const pct = Math.min(100, Math.max(0, value));
    return (
        <div className="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
            <div
                className={cn('h-full rounded-full transition-all', pct >= 100 ? 'bg-emerald-500' : 'bg-indigo-500')}
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

const FILTERS = ['all', 'active', 'completed', 'disputed'] as const;
type Filter = typeof FILTERS[number];

export default function ContractsIndex({ contracts, stats }: any) {
    const { auth } = usePage().props as any;
    const globalCurrency = auth?.user?.preferred_currency || 'USD';
    const userId = auth?.user?.id;

    const [filter, setFilter] = useState<Filter>('all');

    const allContracts: any[] = contracts?.data ?? [];
    const displayed = filter === 'all'
        ? allContracts
        : allContracts.filter((c: any) => c.status === filter);

    const statCards = [
        { label: __('freelance.total_contracts'), value: stats?.total       ?? 0, icon: FileText,     color: 'text-indigo-600 bg-indigo-50'  },
        { label: __('general.active'),          value: stats?.active      ?? 0, icon: Activity,     color: 'text-emerald-600 bg-emerald-50' },
        { label: __('general.completed'),       value: stats?.completed   ?? 0, icon: CheckCircle2, color: 'text-blue-600    bg-blue-50'   },
        { label: __('general.total_earned'),    value: stats?.total_value ?? 0, icon: DollarSign,   color: 'text-amber-600  bg-amber-50',  isCurrency: true },
    ];

    return (
        <AppLayout>
            <Head title={`${__('freelance.my_contracts')} - ${__('freelance.freelance')}`} />
            <AppPage>
                <PageHeader
                    title={__('freelance.my_contracts')}
                    subtitle={__('general.monitor_your_active_engagements_track')}
                    icon={Briefcase}
                    actions={
                        <Link
                            href="/freelance/jobs/browse"
                            className="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm"
                        >
                            <Briefcase className="h-4 w-4" /> {__('general.find_more_work')}
                        </Link>
                    }
                />

                {/* Stat row */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {statCards.map((s) => {
                        const Icon = s.icon;
                        return (
                            <FreelanceCard key={s.label} className="p-4 flex items-center gap-3">
                                <div className={cn('w-9 h-9 rounded-lg flex items-center justify-center shrink-0', s.color)}>
                                    <Icon className="h-4 w-4" />
                                </div>
                                <div>
                                    {s.isCurrency ? (
                                        <FinancialAmount
                                            amount={s.value}
                                            currency={globalCurrency}
                                            className="text-xl font-black text-slate-900 leading-none"
                                        />
                                    ) : (
                                        <p className="text-2xl font-black text-slate-900 leading-none">{s.value}</p>
                                    )}
                                    <p className="text-[11px] text-slate-500 mt-0.5">{s.label}</p>
                                </div>
                            </FreelanceCard>
                        );
                    })}
                </div>

                {/* Filter tabs */}
                <div className="flex gap-1.5 flex-wrap">
                    {FILTERS.map((f) => {
                        const count = f === 'all' ? (stats?.total ?? 0) : (stats?.[f] ?? 0);
                        return (
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
                                {f === 'all' ? `${__('general.all')} (${count})` : `${__(f)} (${count})`}
                            </button>
                        );
                    })}
                </div>

                {/* Contracts list */}
                {displayed.length === 0 ? (
                    <FreelanceCard>
                        <EmptyState
                            icon={Briefcase}
                            title={__('freelance.no_contracts_yet')}
                            description={filter === 'all'
                                ? __('You don\'t have any contracts. Submit proposals on open jobs to start working.')
                                : `${__('general.no')} ${filter} ${__('freelance.contracts_to_display')}`}
                            action="/freelance/jobs/browse"
                            actionLabel={__('freelance.browse_jobs')}
                        />
                    </FreelanceCard>
                ) : (
                    <div className="space-y-3">
                        {displayed.map((contract: any) => {
                            const isClient     = contract.client_id === userId;
                            const counterparty = isClient ? contract.freelancer : contract.client;
                            const roleLabel    = isClient ? __('freelance.hired') : __('general.working_for');
                            const progress     = contract.status === 'completed' ? 100
                                              : contract.status === 'active'    ? 45
                                              : 0;

                            return (
                                <FreelanceCard
                                    key={contract.id}
                                    interactive
                                >
                                    <div className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div className="flex-1 min-w-0 space-y-1.5">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <Link
                                                    href={`/freelance/contracts/${contract.id}`}
                                                    className="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors truncate"
                                                >
                                                    {contract.job?.title ?? __('freelance.unknown_job')}
                                                </Link>
                                                <Badge
                                                    variant="outline"
                                                    className="text-[10px] font-bold uppercase tracking-wider bg-slate-50 border-slate-200 text-slate-500"
                                                >
                                                    {__(contract.job?.type ?? 'Fixed')}
                                                </Badge>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                                <span className="flex items-center gap-1">
                                                    <Clock className="h-3 w-3 text-slate-400" />
                                                    {__('general.started')} {formatDate(contract.started_at)}
                                                </span>
                                                {counterparty && (
                                                    <span className="flex items-center gap-1">
                                                        <Briefcase className="h-3 w-3 text-slate-400" />
                                                        {roleLabel} <span className="font-medium text-slate-700">{counterparty.name}</span>
                                                    </span>
                                                )}
                                            </div>

                                            {contract.status === 'active' && (
                                                <div className="flex items-center gap-2 pt-0.5">
                                                    <ProgressBar value={progress} />
                                                    <span className="text-[10px] text-slate-400">{progress}% {__('general.complete')}</span>
                                                </div>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-4 shrink-0">
                                            <div className="text-right">
                                                <FinancialAmount
                                                    amount={contract.amount}
                                                    currency={contract.currency_id}
                                                    className="text-base font-black text-slate-900"
                                                />
                                                <p className="text-[10px] text-slate-400">{__('freelance.contract_value')}</p>
                                            </div>

                                            <FreelanceStatusPill status={contract.status} />

                                            <Link
                                                href={`/freelance/contracts/${contract.id}`}
                                                className="text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 p-1.5 rounded-md transition-colors opacity-0 group-hover:opacity-100"
                                                title={__('freelance.view_contract_2')}
                                            >
                                                <ChevronRight className="h-4 w-4" />
                                            </Link>
                                        </div>
                                    </div>
                                </FreelanceCard>
                            );
                        })}
                    </div>
                )}

                {/* Pagination */}
                {contracts?.links && contracts.data?.length > 0 && (
                    <div className="flex justify-center gap-2 pt-4">
                        {contracts.links.map((link: any, i: number) => (
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
