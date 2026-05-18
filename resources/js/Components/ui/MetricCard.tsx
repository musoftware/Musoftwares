import React from 'react';
import { Card, CardContent } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import { ArrowDown, ArrowUp } from 'lucide-react';

export interface MetricCardProps {
    label: string;
    value: string | number;
    icon?: React.ElementType;
    trend?: {
        value: number;
        isPositive: boolean;
        label: string;
    };
    className?: string;
}

export function MetricCard({ label, value, icon: Icon, trend, className }: MetricCardProps) {
    return (
        <Card className={cn('rounded-xl border border-slate-200 bg-white shadow-sm', className)}>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <p className="text-sm font-medium text-slate-500">{label}</p>
                    {Icon && <Icon className="h-4 w-4 text-slate-400" />}
                </div>
                <div className="mt-2 flex items-baseline gap-2">
                    <h2 className="text-2xl font-semibold tracking-tight text-slate-900 font-mono">
                        {value}
                    </h2>
                </div>
                {trend && (
                    <div className="mt-1 flex items-center text-xs">
                        {trend.isPositive ? (
                            <ArrowUp className="mr-1 h-3 w-3 text-emerald-500" />
                        ) : (
                            <ArrowDown className="mr-1 h-3 w-3 text-rose-500" />
                        )}
                        <span
                            className={cn(
                                'font-medium',
                                trend.isPositive ? 'text-emerald-600' : 'text-rose-600'
                            )}
                        >
                            {trend.value}%
                        </span>
                        <span className="ml-1 text-slate-500">{trend.label}</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
