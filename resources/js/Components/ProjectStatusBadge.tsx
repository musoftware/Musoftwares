import React from 'react';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const STATUS_STYLES: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-700',
    hold_on: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-200 text-slate-700',
};

export interface ProjectStatusBadgeProps {
    status?: string | null;
    archived?: boolean;
    className?: string;
}

export function ProjectStatusBadge({ status, archived, className }: ProjectStatusBadgeProps) {
    if (archived) {
        return (
            <span
                className={cn(
                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold bg-slate-200 text-slate-700',
                    className,
                )}
            >
                {__('general.archived')}
            </span>
        );
    }
    const s = status ?? 'open';
    return (
        <span
            className={cn(
                'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold capitalize',
                STATUS_STYLES[s] ?? 'bg-slate-100 text-slate-600',
                className,
            )}
        >
            {s.replace('_', ' ')}
        </span>
    );
}

export default ProjectStatusBadge;