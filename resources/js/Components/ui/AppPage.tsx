import React from 'react';
import { cn } from '@/lib/utils';

interface AppPageProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

export function AppPage({ children, className, ...props }: AppPageProps) {
    return (
        <div
            className={cn(
                'mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8',
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
}
