import React from 'react';
import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';
import { safeRoute } from '@/lib/utils';
import type { DashboardStats } from '../types';

interface PendingInvoicesBannerProps {
    stats: DashboardStats;
}

export default function PendingInvoicesBanner({ stats }: PendingInvoicesBannerProps) {
    if (stats.unpaidInvoices > 0) {
        const needsCriticalPayment = stats.walletBalance < stats.unpaidAmount;
        const amount = needsCriticalPayment ? stats.outstandingBalance : stats.unpaidAmount;
        const invoicesHref = safeRoute('billing.invoices.index', undefined, '/billing/invoices');

        if (needsCriticalPayment) {
            return (
                <div
                    role="alert"
                    aria-live="assertive"
                    className="relative w-full overflow-hidden rounded-xl border border-rose-700 bg-gradient-to-br from-rose-600 to-red-700 text-white shadow-lg"
                >
                    <div className="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl" aria-hidden="true" />
                    <div className="pointer-events-none absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" aria-hidden="true" />

                    <div className="relative flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/20">
                                <AlertTriangle className="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide">
                                        Action Required
                                    </span>
                                    <h2 className="text-lg font-bold sm:text-xl">
                                        {__('general.critical_payment_required')}
                                    </h2>
                                </div>
                                <p className="mt-1 text-sm leading-relaxed text-white/90 sm:text-base">
                                    {__('general.you_have_an_outstanding_balance_of')}
                                    <span className="mx-1.5 inline-block rounded-md bg-white/15 px-2 py-0.5 text-base font-extrabold text-white sm:text-lg">
                                        <CurrencyDisplay amount={amount} currency={stats.currency} />
                                    </span>
                                    {__('general.that_needs_to_be_settled')}
                                </p>
                            </div>
                        </div>

                        <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                            <Link
                                href={invoicesHref}
                                className="inline-flex h-11 items-center justify-center rounded-lg bg-white px-6 text-sm font-bold text-rose-700 shadow-sm transition-colors hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-rose-700 sm:text-base"
                            >
                                {__('general.pay_now')}
                                <ArrowRight className="ms-2 h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                            </Link>
                            <Link
                                href={invoicesHref}
                                className="inline-flex h-11 items-center justify-center rounded-lg border border-white/40 px-5 text-sm font-medium text-white transition-colors hover:bg-white/10 sm:text-base"
                            >
                                {__('general.view_all')}
                            </Link>
                        </div>
                    </div>
                </div>
            );
        }

        return (
            <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm sm:p-5">
                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                            <AlertTriangle className="h-5 w-5 text-amber-700" aria-hidden="true" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="h-2 w-2 rounded-full bg-amber-500" />
                                <h2 className="text-base font-bold text-slate-900">
                                    {__('general.pending_invoices')}
                                </h2>
                            </div>
                            <p className="mt-1 text-sm leading-relaxed text-slate-600">
                                {__('general.you_have_an_outstanding_balance_of')}
                                <span className="mx-1 font-bold text-slate-900">
                                    <CurrencyDisplay amount={amount} currency={stats.currency} />
                                </span>
                                {__('general.that_needs_to_be_settled')}
                            </p>
                        </div>
                    </div>
                    <Link
                        href={invoicesHref}
                        className="inline-flex h-10 w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-medium text-slate-900 transition-colors hover:bg-slate-50 sm:w-auto"
                    >
                        {__('general.view_all')}
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col items-start justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:p-5">
            <p className="text-sm font-medium text-emerald-800">
                {__('general.all_caught_up_you_have_no_pending_invoices_at_the_moment')}
            </p>
            <Link
                href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')}
                className="inline-flex items-center text-sm font-medium text-emerald-700 hover:underline"
            >
                {__('general.view_all')}
                <ArrowRight className="ms-1 h-4 w-4 rtl:rotate-180" aria-hidden="true" />
            </Link>
        </div>
    );
}
