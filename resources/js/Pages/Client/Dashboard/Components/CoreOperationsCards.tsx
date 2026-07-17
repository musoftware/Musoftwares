import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, FileText, History, Sparkles, Wallet } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { safeRoute } from '@/lib/utils';
import { IsoCurrencyAmount } from '@/lib/currencyDisplay';
import type { DashboardStats } from '../types';

interface CoreOperationsCardsProps {
    stats: DashboardStats;
}

interface DashboardStatsWithRenewal extends DashboardStats {
    nextRenewalAt?: string | null;
}

function getMaskedAccountId(userId: number | undefined, currencyId: number | undefined) {
    if (!userId || !currencyId) {
        return '•••• ****';
    }

    const digits = ((userId * 31 + currencyId * 7) % 10000).toString().padStart(4, '0');

    return __('general.account_id_masked', { digits });
}

function daysUntil(target: string | null | undefined): number | null {
    if (!target) return null;
    const parsed = new Date(target);
    if (Number.isNaN(parsed.getTime())) return null;
    const diff = parsed.getTime() - Date.now();
    if (diff <= 0) return 0;
    return Math.ceil(diff / (24 * 60 * 60 * 1000));
}

function RenewalHint({ nextRenewalAt }: { nextRenewalAt?: string | null }) {
    const days = daysUntil(nextRenewalAt);
    if (days === null) return null;
    let label: string;
    let className = 'text-xs font-medium text-slate-500';

    if (days <= 0) {
        label = __('general.renews_today');
        className = 'text-xs font-semibold text-amber-700';
    } else if (days === 1) {
        label = __('general.renews_in_one_day');
        className = 'text-xs font-semibold text-amber-700';
    } else if (days <= 3) {
        label = __('general.renews_in_days', { count: days });
        className = 'text-xs font-semibold text-amber-700';
    } else {
        label = __('general.renews_in_days', { count: days });
        className = 'text-xs font-medium text-slate-500';
    }

    return (
        <span className={`mt-1 inline-flex items-center gap-1 border-s-2 border-slate-200 ps-2 ${className}`}>
            {label}
        </span>
    );
}

function DynamicUnpaidBadge({ count }: { count: number }) {
    const isUnpaid = count > 0;

    return (
        <span
            className={`rounded-full px-2 py-0.5 text-xs font-bold transition-colors duration-300 ${
                isUnpaid
                    ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'
                    : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
            }`}
        >
            {count}
        </span>
    );
}

export default function CoreOperationsCards({ stats }: CoreOperationsCardsProps) {
    const { auth } = usePage<{ auth: { user: { id: number; name: string; email: string } } }>().props;
    const maskedAccountId = getMaskedAccountId(auth.user.id, stats.currency.id);

    return (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div className="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                <div className="mb-4">
                    <Sparkles className="icon-md text-slate-900" aria-hidden="true" />
                </div>
                <div>
                    <h3 className="mb-1 text-lg font-bold text-slate-900">{__('general.active_subscriptions')}</h3>
                    <p className="text-4xl font-bold tracking-tight text-slate-900">{stats.activeSubscriptions}</p>
                    {(stats as DashboardStatsWithRenewal).nextRenewalAt !== undefined && (
                        <RenewalHint nextRenewalAt={(stats as DashboardStatsWithRenewal).nextRenewalAt} />
                    )}
                </div>
                <div className="mt-auto pt-4">
                    <div className="flex flex-col gap-2">
                        <Link
                            href="/subscriptions/plans"
                            className="group flex items-center justify-between rounded-lg border border-slate-100 p-2.5 transition-colors hover:border-primary hover:bg-slate-50"
                        >
                            <span className="text-sm font-medium text-slate-700 group-hover:text-primary">{__('general.view_tool_plans')}</span>
                            <ArrowRight className="h-4 w-4 text-slate-400 group-hover:text-primary" aria-hidden="true" />
                        </Link>
                        <Link
                            href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')}
                            className="group flex items-center justify-between rounded-lg border border-slate-100 p-2.5 transition-colors hover:border-primary hover:bg-slate-50"
                        >
                            <span className="text-sm font-medium text-slate-700 group-hover:text-primary">{__('general.subscription_history')}</span>
                            <History className="h-4 w-4 text-slate-400 group-hover:text-primary" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </div>

            <div className="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                <div className="relative flex min-h-[190px] flex-col justify-between bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 p-6 text-white">
                    <div className="flex items-center gap-2">
                        <Wallet className="icon-md text-indigo-200" aria-hidden="true" />
                        <h3 className="text-lg font-bold">{__('general.charge_balance')}</h3>
                    </div>
                    <div>
                        <p className="mb-2 text-xs font-medium uppercase tracking-wider text-slate-300">{__('general.account_balance')}</p>
                        <IsoCurrencyAmount
                            amount={stats.walletBalance}
                            currency={stats.currency}
                            size="lg"
                            className="text-white [&_.currency-code]:text-slate-300 [&_.currency-flag]:bg-white/10"
                        />
                    </div>
                    <p className="text-sm font-medium tracking-[0.18em] text-slate-300">{maskedAccountId}</p>
                </div>
                <div className="flex flex-1 flex-col justify-end bg-white p-6">
                    <Link
                        href={safeRoute('financial.add-balance', undefined, '/financial/add-balance')}
                        className="group flex items-center justify-center rounded-lg bg-primary px-4 py-3 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                    >
                        {__('general.add_funds')}
                        <ArrowRight className="ms-2 h-4 w-4 transition-transform group-hover:translate-x-1 rtl:rotate-180" aria-hidden="true" />
                    </Link>
                </div>
            </div>

            <div className="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                <div className="mb-4">
                    <FileText className="icon-md text-slate-900" aria-hidden="true" />
                </div>
                <div>
                    <h3 className="mb-1 text-lg font-bold text-slate-900">{__('general.billing_invoices')}</h3>
                </div>
                <div className="mt-auto pt-4">
                    <p className="mb-2 text-xs font-medium text-slate-500">{__('general.billing_quick_access')}</p>
                    <div className="flex flex-col gap-2">
                        <Link
                            href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')}
                            className="group flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-2.5 transition-colors hover:border-slate-300"
                        >
                            <span className="text-sm font-medium text-slate-700 group-hover:text-slate-900">{__('general.unpaid_invoices')}</span>
                            <DynamicUnpaidBadge count={stats.unpaidInvoices} />
                        </Link>
                        <Link
                            href={safeRoute('financial.transactions', undefined, '/financial/transactions')}
                            className="group flex items-center justify-between rounded-lg border border-slate-200 p-2.5 transition-colors hover:bg-slate-50"
                        >
                            <span className="text-sm font-medium text-slate-700 group-hover:text-slate-900">{__('general.transactions')}</span>
                            <ArrowRight className="h-4 w-4 text-slate-400 group-hover:text-slate-900" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
