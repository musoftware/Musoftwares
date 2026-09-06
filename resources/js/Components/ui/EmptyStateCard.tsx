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
        <Card className={cn('rounded-xl border border-dashed border-slate-300 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-900/30', className)}>
            <CardContent className="flex flex-col items-center justify-center p-12 text-center">
                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-zinc-800">
                    <Icon className="h-8 w-8 text-slate-400 dark:text-zinc-500" />
                </div>
                <h3 className="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{title}</h3>
                <p className="mt-2 max-w-sm text-sm text-slate-500 dark:text-zinc-400">{description}</p>
                {action && <div className="mt-6">{action}</div>}
            </CardContent>
        </Card>
    );
}
