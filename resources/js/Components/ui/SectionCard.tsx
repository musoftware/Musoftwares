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
        <Card className={cn('bg-surface shadow-sm border-border rounded-xl', className)} {...props}>
            {(title || description || action) && (
                <CardHeader className="flex flex-row items-center justify-between border-b border-border/40 space-y-0 pb-4">
                    <div className="space-y-1">
                        {title && <CardTitle className="text-base font-semibold">{title}</CardTitle>}
                        {description && <CardDescription className="text-sm">{description}</CardDescription>}
                    </div>
                    {action && <div className="shrink-0 ml-4">{action}</div>}
                </CardHeader>
            )}
            <CardContent className={cn('pt-6', noPadding && 'p-0 pt-0')}>
                {children}
            </CardContent>
            {footer && (
                <CardFooter className="border-t border-border/40 bg-surface-raised/30 px-6 py-4">
                    {footer}
                </CardFooter>
            )}
        </Card>
    );
}
