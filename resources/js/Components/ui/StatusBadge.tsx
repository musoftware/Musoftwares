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
        gray:   'bg-slate-100 text-slate-600 border-slate-200',
        blue:   'bg-blue-50 text-blue-700 border-blue-100',
        green:  'bg-emerald-50 text-emerald-700 border-emerald-100',
        yellow: 'bg-amber-50 text-amber-700 border-amber-100',
        red:    'bg-red-50 text-red-700 border-red-100',
        purple: 'bg-purple-50 text-purple-700 border-purple-100',
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
