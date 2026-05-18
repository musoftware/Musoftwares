import React from 'react';
import { Card, CardContent } from '@/Components/ui/card';
import { cn } from '@/lib/utils';

export interface EmptyStateCardProps {
    title: string;
    description: string;
    icon: React.ElementType;
    action?: React.ReactNode;
    className?: string;
}

export function EmptyStateCard({ title, description, icon: Icon, action, className }: EmptyStateCardProps) {
    return (
        <Card className={cn('rounded-xl border border-dashed border-slate-300 bg-slate-50/50', className)}>
            <CardContent className="flex flex-col items-center justify-center p-12 text-center">
                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Icon className="h-8 w-8 text-slate-400" />
                </div>
                <h3 className="text-lg font-semibold tracking-tight text-slate-900">{title}</h3>
                <p className="mt-2 max-w-sm text-sm text-slate-500">{description}</p>
                {action && <div className="mt-6">{action}</div>}
            </CardContent>
        </Card>
    );
}
