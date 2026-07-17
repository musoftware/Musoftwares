import React from 'react';
import { Clock, PiggyBank, Wallet } from 'lucide-react';
import { IsoCurrencyAmount } from '@/lib/currencyDisplay';
import { __ } from '@/lib/i18n';

export interface TabFinancials {
    budget: string;
    paid: string;
    pending: string;
    percentage: number;
}

interface Props {
    financials: TabFinancials | null;
    currency: { currency: string; symbol: string; string_format?: string } | null;
}

export function ProjectFinancialsTab({ financials, currency }: Props) {
    if (!financials) {
        return (
            <p className="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 p-8 text-center text-sm text-slate-500">
                {__('general.no_financial_data_available') || 'Financial details will appear once the project records any invoices.'}
            </p>
        );
    }

    const items = [
        {
            label: __('general.budget'),
            value: financials.budget,
            icon: PiggyBank,
        },
        {
            label: __('general.paid_invoices'),
            value: financials.paid,
            icon: Wallet,
        },
        {
            label: __('general.pending_invoices'),
            value: financials.pending,
            icon: Clock,
        },
    ];

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                {items.map((item) => (
                    <div
                        key={item.label}
                        className="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4"
                    >
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <item.icon className="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                {item.label}
                            </p>
                            <div className="mt-1 text-lg font-semibold text-slate-900">
                                <IsoCurrencyAmount amount={item.value} currency={currency} size="sm" />
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="rounded-xl border border-slate-200 bg-white p-4">
                <div className="mb-1 flex items-center justify-between text-sm">
                    <span className="font-medium text-slate-700">{__('general.completion')}</span>
                    <span className="font-sans font-semibold tabular-nums text-slate-900">
                        {Math.round(financials.percentage)}%
                    </span>
                </div>
                <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        className="h-full rounded-full bg-emerald-500 transition-all"
                        style={{ width: `${Math.min(100, Math.max(0, financials.percentage))}%` }}
                    />
                </div>
            </div>
        </div>
    );
}

export default ProjectFinancialsTab;
