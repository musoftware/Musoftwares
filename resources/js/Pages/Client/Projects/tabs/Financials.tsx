import React from 'react';
import { PiggyBank, Wallet, Clock, Receipt } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { IsoCurrencyAmount } from '@/lib/currencyDisplay';
import { __ } from '@/lib/i18n';

export interface FinancialsTabProps {
    budget: string | number;
    paid: string | number;
    pending: string | number;
    percentage: number;
    currency: { currency: string; symbol?: string; string_format?: string } | null;
}

export default function FinancialsTab({ budget, paid, pending, percentage, currency }: FinancialsTabProps) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <PiggyBank className="icon-md text-slate-400" />
                            {__('general.budget')}
                        </div>
                        <div className="mt-2">
                            <IsoCurrencyAmount amount={budget} currency={currency} size="lg" />
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-xl border border-emerald-100 bg-emerald-50/40">
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-emerald-600">
                            <Wallet className="icon-md text-emerald-500" />
                            {__('general.paid_invoices')}
                        </div>
                        <div className="mt-2 text-emerald-700">
                            <IsoCurrencyAmount amount={paid} currency={currency} size="lg" />
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-xl border border-amber-100 bg-amber-50/40">
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-amber-600">
                            <Receipt className="icon-md text-amber-500" />
                            {__('general.pending_invoices')}
                        </div>
                        <div className="mt-2 text-amber-700">
                            <IsoCurrencyAmount amount={pending} currency={currency} size="lg" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card className="rounded-xl border border-slate-200">
                <CardContent className="p-5">
                    <div className="mb-2 flex items-center justify-between text-sm">
                        <span className="flex items-center gap-2 font-medium text-slate-700">
                            <Clock className="icon-md text-slate-400" /> {__('general.progress')}
                        </span>
                        <span className="font-semibold tabular-nums text-slate-900">{Math.round(percentage)}%</span>
                    </div>
                    <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full rounded-full bg-emerald-500 transition-all"
                            style={{ width: `${Math.min(100, Math.max(0, percentage))}%` }}
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
