import React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { cn } from '@/lib/utils';

export interface DataCardProps {
    title: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
}

export function DataCard({ title, description, children, className }: DataCardProps) {
    return (
        <Card className={cn('rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/80 shadow-sm transition-colors', className)}>
            <CardHeader className="p-6 pb-4">
                <CardTitle className="text-base font-semibold tracking-tight text-slate-900 dark:text-white">{title}</CardTitle>
                {description && <CardDescription className="text-sm text-slate-500 dark:text-zinc-400">{description}</CardDescription>}
            </CardHeader>
            <CardContent className="p-6 pt-0">
                {children}
            </CardContent>
        </Card>
    );
}
