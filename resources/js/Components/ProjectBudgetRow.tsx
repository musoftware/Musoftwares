import React from 'react';
import { Wallet, CheckCircle2 } from 'lucide-react';
import { IsoCurrencyAmount } from '@/lib/currencyDisplay';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export interface ProjectBudgetProject {
    budget: string | number;
    total_paid: string | number;
    currency?: { currency: string; symbol?: string; string_format?: string } | null;
}

export interface ProjectBudgetRowProps {
    project: ProjectBudgetProject;
    className?: string;
}

export function ProjectBudgetRow({ project, className }: ProjectBudgetRowProps) {
    return (
        <div
            className={cn(
                'mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-slate-100 pb-3',
                className,
            )}
        >
            <div className="flex min-w-0 items-center gap-2">
                <Wallet className="icon-md shrink-0 text-slate-400" aria-hidden="true" />
                <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    {__('general.budget')}
                </span>
                <IsoCurrencyAmount amount={project.budget} currency={project.currency} size="sm" />
            </div>
            <div className="flex min-w-0 items-center gap-2">
                <CheckCircle2 className="icon-md shrink-0 text-emerald-400" aria-hidden="true" />
                <span className="text-[11px] font-semibold uppercase tracking-wide text-emerald-500/70">
                    {__('general.paid')}
                </span>
                <IsoCurrencyAmount
                    amount={project.total_paid}
                    currency={project.currency}
                    size="sm"
                    className="text-emerald-700"
                />
            </div>
        </div>
    );
}

export default ProjectBudgetRow;
