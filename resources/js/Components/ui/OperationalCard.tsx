import React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { cn } from '@/lib/utils';

export interface OperationalCardProps {
    title?: string | React.ReactNode;
    description?: string;
    action?: React.ReactNode;
    children: React.ReactNode;
    footer?: React.ReactNode;
    noPadding?: boolean;
    className?: string;
    icon?: React.ReactNode;
}

export function OperationalCard({
    title,
    description,
    action,
    children,
    footer,
    noPadding = false,
    className,
    icon,
}: OperationalCardProps) {
    return (
        <Card className={cn('rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/80 shadow-sm overflow-hidden transition-colors', className)}>
            {(title || description || action || icon) && (
                <div className={cn('flex flex-row items-center justify-between border-b border-slate-100 dark:border-zinc-800 p-6', noPadding && 'pb-4')}>
                    <div className="space-y-1">
                        {title && (
                            <h3 className="text-base font-semibold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                                {icon && icon}
                                {title}
                            </h3>
                        )}
                        {description && <p className="text-sm text-slate-500 dark:text-zinc-400">{description}</p>}
                    </div>
                    {action && <div className="ms-4 shrink-0">{action}</div>}
                </div>
            )}
            <CardContent className={cn('p-6', noPadding && 'p-0 pb-0')}>
                {children}
            </CardContent>
            {footer && (
                <CardFooter className="border-t border-slate-100 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-950/40 px-6 py-4">
                    {footer}
                </CardFooter>
            )}
        </Card>
    );
}
