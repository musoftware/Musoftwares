import React from 'react';
import { cn } from '@/lib/utils';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { SkeletonStatCard } from './SkeletonLoaders';

export interface StatCardProps {
    label: string;
    value: string | number | React.ReactNode;
    change?: string | number;
    changeType?: 'up' | 'down' | 'neutral';
    icon?: any;
    iconColor?: 'primary' | 'success' | 'warning' | 'danger' | 'info';
    loading?: boolean;
    isMoney?: boolean;
}

export function StatCard({
    label,
    value,
    change,
    changeType = 'neutral',
    icon: Icon,
    iconColor = 'primary',
    loading = false,
    isMoney = false,
}: StatCardProps) {
    if (loading) {
        return <SkeletonStatCard className="" />;
    }

    const iconBgClasses = {
        primary: 'bg-primary-light text-primary',
        success: 'bg-success-light text-success',
        warning: 'bg-warning-light text-warning',
        danger: 'bg-danger-light text-danger',
        info: 'bg-blue-50 text-blue-600',
    };

    const badgeClasses = {
        up: 'bg-success-light text-success',
        down: 'bg-danger-light text-danger',
        neutral: 'bg-slate-100 text-slate-600',
    };

    return (
        <div className="bg-surface border-border flex flex-col rounded-xl border p-6 shadow-sm">
            <div className="mb-4 flex items-start justify-between">
                {Icon && (
                    <div
                        className={cn(
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                            iconBgClasses[iconColor] || iconBgClasses.primary,
                        )}
                    >
                        <Icon className="h-5 w-5" />
                    </div>
                )}
                {change && (
                    <div
                        className={cn(
                            'flex items-center space-x-1 rounded-full px-2.5 py-1 font-sans text-[12px] font-medium',
                            badgeClasses[changeType] || badgeClasses.neutral,
                        )}
                    >
                        {changeType === 'up' && <ArrowUp className="h-3 w-3" />}
                        {changeType === 'down' && (
                            <ArrowDown className="h-3 w-3" />
                        )}
                        <span>{change}</span>
                    </div>
                )}
            </div>
            <div>
                <div
                    className={cn(
                        'text-text-primary mb-1 text-[32px] font-bold',
                        isMoney ? 'font-mono' : 'font-sora',
                    )}
                >
                    {value}
                </div>
                <div className="section-label">{label}</div>
            </div>
        </div>
    );
}
