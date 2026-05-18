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
        up: 'text-emerald-600',
        down: 'text-rose-600',
        neutral: 'text-slate-500',
    };

    return (
        <div className={cn("bg-white border border-slate-100 flex flex-col rounded-2xl p-6 shadow-sm transition-all hover:border-slate-200", className)}>
            <div className="flex items-center justify-between gap-2 mb-3">
                <span className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{label}</span>
                {Icon && <Icon className="w-4 h-4 text-slate-400 stroke-[1.5]" />}
            </div>
            
            <div className="flex items-baseline gap-2">
                <div className="text-2xl font-bold tracking-tight text-slate-900 font-sans">
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
                <p className="mt-1 text-[11px] text-slate-500">{description}</p>
            )}
        </div>
    );
}
