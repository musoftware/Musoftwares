import React from 'react';
import { cn, statusColor } from '@/lib/utils';

export interface StatusBadgeProps {
    status: string;
    label?: React.ReactNode;
    size?: 'sm' | 'md';
    className?: string;
}

export function StatusBadge({ status, label, size = 'md', className = '' }: StatusBadgeProps) {
    if (!status) return null;

    const color = statusColor(status);

    const colorClasses: Record<string, string> = {
        gray:   'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 border-slate-200 dark:border-zinc-700',
        blue:   'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-100 dark:border-blue-900/40',
        green:  'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/40',
        yellow: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-900/40',
        red:    'bg-red-50 dark:bg-rose-950/40 text-red-700 dark:text-rose-400 border-red-100 dark:border-rose-900/40',
        purple: 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-400 border-purple-100 dark:border-purple-900/40',
    };

    const dotClasses: Record<string, string> = {
        gray:   'bg-slate-400',
        blue:   'bg-blue-500',
        green:  'bg-emerald-500',
        yellow: 'bg-amber-500',
        red:    'bg-red-500',
        purple: 'bg-purple-500',
    };

    const sizeClasses = {
        sm: 'px-2 py-0.5 text-[11px] gap-1',
        md: 'px-2.5 py-1 text-[12px] gap-1.5',
    };

    const dotSizeClasses = {
        sm: 'h-1.5 w-1.5',
        md: 'h-1.5 w-1.5',
    };

    const resolvedSize = size ?? 'md';

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border font-sans font-medium capitalize',
                sizeClasses[resolvedSize],
                colorClasses[color] || colorClasses.gray,
                className,
            )}
        >
            <span
                className={cn(
                    'rounded-full shrink-0',
                    dotSizeClasses[resolvedSize],
                    dotClasses[color] || dotClasses.gray,
                )}
            />
            {label || status.replace(/[_-]/g, ' ')}
        </span>
    );
}
