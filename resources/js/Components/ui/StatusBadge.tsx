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
        gray: 'bg-slate-100 text-slate-600 border-slate-200',
        blue: 'bg-blue-50 text-blue-700 border-blue-100',
        green: 'bg-success-light text-success border-green-100',
        yellow: 'bg-warning-light text-warning border-yellow-100',
        red: 'bg-danger-light text-danger border-red-100',
        purple: 'bg-purple-50 text-purple-700 border-purple-100',
    };

    const dotClasses: Record<string, string> = {
        gray: 'bg-slate-500',
        blue: 'bg-blue-600',
        green: 'bg-success',
        yellow: 'bg-warning',
        red: 'bg-danger',
        purple: 'bg-purple-600',
    };

    const sizeClasses = {
        sm: 'px-2 py-0.5 text-[11px]',
        md: 'px-2.5 py-1 text-[12px]',
    };

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border font-sans font-medium capitalize',
                sizeClasses[size] || sizeClasses.md,
                colorClasses[color] || colorClasses.gray,
                className,
            )}
        >
            <span
                className={cn(
                    'mr-1.5 h-1.5 w-1.5 rounded-full',
                    dotClasses[color] || dotClasses.gray,
                )}
            />
            {label || status.replace(/_/g, ' ')}
        </span>
    );
}
