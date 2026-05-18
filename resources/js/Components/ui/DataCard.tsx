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
        <Card className={cn('rounded-xl border border-slate-200 bg-white shadow-sm', className)}>
            <CardHeader className="p-6 pb-4">
                <CardTitle className="text-base font-semibold tracking-tight text-slate-900">{title}</CardTitle>
                {description && <CardDescription className="text-sm text-slate-500">{description}</CardDescription>}
            </CardHeader>
            <CardContent className="p-6 pt-0">
                {children}
            </CardContent>
        </Card>
    );
}
