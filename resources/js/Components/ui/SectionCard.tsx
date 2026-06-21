import React from 'react';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';

export interface SectionCardProps extends React.ComponentProps<typeof Card> {
    title?: string;
    description?: string;
    action?: React.ReactNode;
    children: React.ReactNode;
    footer?: React.ReactNode;
    noPadding?: boolean;
}

export function SectionCard({
    title,
    description,
    action,
    children,
    footer,
    noPadding = false,
    className,
    ...props
}: SectionCardProps) {
    return (
        <div className={cn('bg-white shadow-sm border border-slate-100 rounded-2xl overflow-hidden', className)} {...props}>
            {(title || description || action) && (
                <div className={cn('flex flex-row items-center justify-between border-b border-slate-100 p-6', noPadding && 'pb-4')}>
                    <div className="space-y-1">
                        {title && <h3 className="text-lg font-semibold text-slate-900">{title}</h3>}
                        {description && <p className="text-sm text-slate-500">{description}</p>}
                    </div>
                    {action && <div className="shrink-0 ms-4">{action}</div>}
                </div>
            )}
            <div className={cn('p-6', noPadding && 'p-0')}>
                {children}
            </div>
            {footer && (
                <div className="border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                    {footer}
                </div>
            )}
        </div>
    );
}
