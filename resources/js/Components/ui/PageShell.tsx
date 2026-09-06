import React from 'react';
import { cn } from '@/lib/utils';

export interface PageShellProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    maxWidth?: 'default' | 'full' | 'sm' | 'md' | 'lg' | 'xl' | '7xl';
    contentClassName?: string;
    noPadding?: boolean;
}

const maxWidthMap = {
    sm: 'max-w-screen-sm',
    md: 'max-w-screen-md',
    lg: 'max-w-5xl',
    xl: 'max-w-6xl',
    '7xl': 'max-w-7xl',
    default: 'max-w-[1400px]',
    full: 'w-full',
};

/**
 * PageShell — Unified high-fidelity background container for all portal, billing, and dashboard pages.
 * Handles automatic dark mode transition with Apple-grade neutral tones.
 */
export function PageShell({
    children,
    className,
    contentClassName,
    maxWidth = 'default',
    noPadding = false,
    ...props
}: PageShellProps) {
    return (
        <div
            data-slot="page-shell"
            className={cn(
                'w-full min-h-[calc(100vh-68px)] font-sans antialiased transition-colors duration-200',
                'bg-[#f5f5f7] dark:bg-[#090d16] text-[#1d1d1f] dark:text-[#f8fafc]',
                'selection:bg-[#0071e3]/20 selection:text-[#0071e3]',
                className
            )}
            {...props}
        >
            <div
                className={cn(
                    'mx-auto w-full',
                    maxWidthMap[maxWidth],
                    !noPadding && 'px-6 sm:px-10 py-8 space-y-8',
                    contentClassName
                )}
            >
                {children}
            </div>
        </div>
    );
}

export default PageShell;
