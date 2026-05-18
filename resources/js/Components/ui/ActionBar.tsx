import React from 'react';
import { cn } from '@/lib/utils';

interface ActionBarProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

export function ActionBar({ children, className, ...props }: ActionBarProps) {
    return (
        <div
            className={cn(
                'flex flex-col sm:flex-row items-center justify-between gap-4 py-4',
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
}
