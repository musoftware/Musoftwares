import React from 'react';
import { Link } from '@inertiajs/react';
import { FileText, CheckCircle2, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';
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
        
        return (
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div className="flex items-start gap-4">
                    <div className="ps-2 sm:ps-0">
                        <div className="flex items-center gap-2 mb-1">
                            <span className={cn(
                                "w-2 h-2 rounded-full",
                                needsCriticalPayment ? "bg-rose-600" : "bg-amber-500"
                            )} />
                            <h2 className="text-base font-bold text-slate-900">
                                {needsCriticalPayment ? __('general.critical_payment_required') : __('general.pending_invoices')}
                            </h2>
                        </div>
                        <p className="text-sm leading-relaxed text-slate-600">
                            {__('general.you_have_an_outstanding_balance_of')}
                            <span className="font-bold mx-1 text-slate-900">
                                <CurrencyDisplay amount={needsCriticalPayment ? stats.outstandingBalance : stats.unpaidAmount} currency={stats.currency} />
                            </span>
                            {__('general.that_needs_to_be_settled')}
                        </p>
                    </div>
                </div>
                <Link 
                    href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')}
                    className="inline-flex items-center justify-center h-10 px-6 rounded-lg text-slate-900 border border-slate-200 font-medium text-sm transition-colors hover:bg-slate-50 shrink-0 whitespace-nowrap w-full sm:w-auto"
                >
                    {__('general.view_all')}
                </Link>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
                <p className="text-slate-600 font-medium text-sm">{__('general.all_caught_up_you_have_no_pending_invoices_at_the_moment')}</p>
            </div>
            <Link href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')} className="text-slate-500 font-medium text-sm hover:underline flex items-center shrink-0">
                {__('general.view_all')}<ArrowRight className="w-4 h-4 ms-1 rtl:rotate-180" />
            </Link>
        </div>
    );
}
