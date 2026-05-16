import { Card, CardContent } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import React from 'react';

interface StatCardProps {
    title: string;
    value: React.ReactNode;
    subtitle?: React.ReactNode;
    icon: React.ElementType;
    trend?: 'up' | 'down' | 'neutral';
    className?: string;
}

export function StatCard({
    title,
    value,
    subtitle,
    icon: Icon,
    trend,
    className,
}: StatCardProps) {
    return (
        <Card className={className}>
            <CardContent className="p-6">
                <div className="flex items-center justify-between space-y-0 pb-2">
                    <p className="text-muted-foreground text-sm font-medium">
                        {title}
                    </p>
                    <Icon className="text-muted-foreground h-4 w-4" />
                </div>
                <div>
                    <div className="text-2xl font-bold">{value}</div>
                    {subtitle && (
                        <p
                            className={cn(
                                'mt-1 text-xs',
                                trend === 'up' && 'text-green-600',
                                trend === 'down' && 'text-red-600',
                                trend === 'neutral' && 'text-muted-foreground',
                                !trend && 'text-muted-foreground',
                            )}
                        >
                            {subtitle}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
