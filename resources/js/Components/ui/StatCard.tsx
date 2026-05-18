import React from 'react';
import { cn } from '@/lib/utils';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { SkeletonStatCard } from './SkeletonLoaders';

export interface StatCardProps {
    label: string;
    value: React.ReactNode;
    description?: React.ReactNode;
    change?: string | number;
    changeType?: 'up' | 'down' | 'neutral';
    icon?: any;
    loading?: boolean;
    className?: string;
}

export function StatCard({
    label,
    value,
    description,
    change,
    changeType = 'neutral',
    icon: Icon,
    loading = false,
    className
}: StatCardProps) {
    if (loading) {
        return <SkeletonStatCard className="" />;
    }

    const badgeClasses = {
        up: 'text-success',
        down: 'text-danger',
        neutral: 'text-slate-500',
    };

    return (
        <div className={cn("bg-surface border border-border/60 flex flex-col rounded-xl p-5 shadow-sm transition-all hover:border-border hover:shadow", className)}>
            <div className="flex items-center justify-between gap-2 mb-3">
                <span className="text-[11px] font-semibold text-text-muted uppercase tracking-wider">{label}</span>
                {Icon && <Icon className="w-4 h-4 text-text-muted stroke-[1.5]" />}
            </div>
            
            <div className="flex items-baseline gap-2">
                <div className="text-2xl font-bold tracking-tight text-text-primary font-sans">
                    {value}
                </div>
                {change && (
                    <div className={cn('flex items-center text-xs font-medium', badgeClasses[changeType])}>
                        {changeType === 'up' && <ArrowUp className="h-3 w-3 mr-0.5" />}
                        {changeType === 'down' && <ArrowDown className="h-3 w-3 mr-0.5" />}
                        {change}
                    </div>
                )}
            </div>

            {description && (
                <p className="mt-1 text-[11px] text-text-muted">{description}</p>
            )}
        </div>
    );
}
