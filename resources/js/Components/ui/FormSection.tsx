import React from 'react';
import { cn } from '@/lib/utils';

interface FormSectionProps extends React.HTMLAttributes<HTMLDivElement> {
    title: string;
    description?: string;
    children: React.ReactNode;
}

export function FormSection({ title, description, children, className, ...props }: FormSectionProps) {
    return (
        <div className={cn("grid grid-cols-1 md:grid-cols-3 gap-6 py-6 border-b border-border/40 last:border-0", className)} {...props}>
            <div className="md:col-span-1 space-y-1">
                <h3 className="text-base font-semibold">{title}</h3>
                {description && <p className="text-sm text-text-muted">{description}</p>}
            </div>
            <div className="md:col-span-2 space-y-6">
                {children}
            </div>
        </div>
    );
}
