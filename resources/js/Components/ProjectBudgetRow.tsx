import React from 'react';
import { CheckCircle2, Wallet } from 'lucide-react';
import { IsoCurrencyAmount } from '@/lib/currencyDisplay';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export interface ProjectBudgetRowProps {
    budget: string | number;
    totalPaid: string | number;
    currency: { currency: string; symbol: string; string_format?: string } | null;
    className?: string;
    orientation?: 'row' | 'stack';
}

export function ProjectBudgetRow({
    budget,
    totalPaid,
    currency,
    className,
    orientation = 'row',
}: ProjectBudgetRowProps) {
    const layoutClass = orientation === 'stack'
        ? 'flex flex-col gap-3'
        : 'flex flex-wrap items-center justify-between gap-x-4 gap-y-2';

    return (
        <div className={cn('border-b border-slate-100 pb-3', className)}>
            <div className={layoutClass}>
                <div className="flex min-w-0 items-center gap-2">
                    <Wallet className="icon-md shrink-0 text-slate-400" aria-hidden="true" />
                    <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        {__('general.budget')}
                    </span>
                    <IsoCurrencyAmount amount={budget} currency={currency} size="sm" />
                </div>
                <div className="flex min-w-0 items-center gap-2">
                    <CheckCircle2 className="icon-md shrink-0 text-emerald-500" aria-hidden="true" />
                    <span className="text-[11px] font-semibold uppercase tracking-wide text-emerald-500/70">
                        {__('general.paid')}
                    </span>
                    <IsoCurrencyAmount
                        amount={totalPaid}
                        currency={currency}
                        size="sm"
                        className="text-emerald-700"
                    />
                </div>
            </div>
        </div>
    );
}

export default ProjectBudgetRow;
