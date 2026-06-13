import React from 'react';
import { Link } from '@inertiajs/react';
import { FileText, CheckCircle2, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';
import type { DashboardStats } from '../types';

interface PendingInvoicesBannerProps {
    stats: DashboardStats;
    outstandingBalance: number;
    safeRoute: (name: string, params?: any, fallbackUrl?: string) => string;
}

export default function PendingInvoicesBanner({ stats, outstandingBalance, safeRoute }: PendingInvoicesBannerProps) {
    if (stats.unpaidInvoices > 0) {
        const needsCriticalPayment = stats.walletBalance < stats.unpaidAmount;
        
        return (
            <div className={cn(
                "border rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden",
                needsCriticalPayment ? "bg-rose-50 border-rose-200" : "bg-amber-50 border-amber-200"
            )}>
                <div className={cn(
                    "absolute left-0 top-0 bottom-0 w-1",
                    needsCriticalPayment ? "bg-rose-500" : "bg-amber-500"
                )}></div>
                <div className="flex items-start gap-4">
                    <div className={cn(
                        "p-3 rounded-full shrink-0 hidden sm:flex",
                        needsCriticalPayment ? "bg-rose-100 text-rose-600" : "bg-amber-100 text-amber-600"
                    )}>
                        <FileText className="w-6 h-6" />
                    </div>
                    <div className="ps-2 sm:ps-0">
                        {needsCriticalPayment && (
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-900 text-white mb-2">
                                {__('general.action_needed')}
                            </span>
                        )}
                        <h2 className={cn(
                            "text-base font-bold mb-1",
                            needsCriticalPayment ? "text-rose-900" : "text-amber-900"
                        )}>
                            {needsCriticalPayment ? __('general.critical_payment_required') : __('general.pending_invoices')}
                        </h2>
                        <p className={cn(
                            "text-sm leading-relaxed",
                            needsCriticalPayment ? "text-rose-700" : "text-amber-700"
                        )}>
                            {__('general.you_have_an_outstanding_balance_of')}
                            <span className="font-bold mx-1">
                                <CurrencyDisplay amount={needsCriticalPayment ? outstandingBalance : stats.unpaidAmount} currency={stats.currency} />
                            </span>
                            {__('general.that_needs_to_be_settled')}
                        </p>
                    </div>
                </div>
                <Link 
                    href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')}
                    className={cn(
                        "inline-flex items-center justify-center h-10 px-6 rounded-lg text-white font-medium text-sm transition-colors shadow-sm shrink-0 whitespace-nowrap w-full sm:w-auto",
                        needsCriticalPayment ? "bg-rose-600 hover:bg-rose-700" : "bg-amber-600 hover:bg-amber-700"
                    )}
                >
                    {__('general.view_all')}
                </Link>
            </div>
        );
    }

    return (
        <div className="bg-slate-50 rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
                <div className="p-2 bg-emerald-100 rounded-full shrink-0">
                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                </div>
                <p className="text-slate-700 font-medium text-sm">{__('general.all_caught_up_you_have_no_pending_invoices_at_the_moment')}</p>
            </div>
            <Link href={safeRoute('billing.invoices.index', undefined, '/billing/invoices')} className="text-slate-500 font-medium text-sm hover:underline flex items-center shrink-0">
                {__('general.view_all')}<ArrowRight className="w-4 h-4 ms-1 rtl:rotate-180" />
            </Link>
        </div>
    );
}
