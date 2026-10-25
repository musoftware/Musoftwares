import React from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { ArrowDown, ArrowUp, ArrowUpRight } from 'lucide-react';

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
    /** When set, the whole card opens this page (the list behind the number). */
    href?: string;
}

export function MetricCard({ label, value, icon: Icon, trend, className, href }: MetricCardProps) {
    const card = (
        <Card
            className={cn(
                'rounded-xl border border-slate-200 bg-white shadow-sm h-full',
                href && 'group transition-all hover:border-slate-300 hover:shadow-md',
                className
            )}
        >
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
                            <ArrowUp className="me-1 h-3 w-3 text-emerald-500" />
                        ) : (
                            <ArrowDown className="me-1 h-3 w-3 text-rose-500" />
                        )}
                        <span
                            className={cn(
                                'font-medium',
                                trend.isPositive ? 'text-emerald-600' : 'text-rose-600'
                            )}
                        >
                            {trend.value}%
                        </span>
                        <span className="ms-1 text-slate-500">{trend.label}</span>
                    </div>
                )}
                {/* Always visible so touch users (no hover) know the card opens details. */}
                {href && (
                    <p className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-slate-600 group-hover:text-slate-900">
                        {__('general.view_details', { default: 'View details' })}
                        <ArrowUpRight className="h-3 w-3 rtl:-scale-x-100" />
                    </p>
                )}
            </CardContent>
        </Card>
    );

    if (!href) {
        return card;
    }

    return (
        <Link href={href} className="block h-full rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2">
            {card}
        </Link>
    );
}
