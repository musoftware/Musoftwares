import React from 'react';
import { cn } from '@/lib/utils';

export interface ContentCardProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'title'> {
    title?: React.ReactNode;
    subtitle?: React.ReactNode;
    action?: React.ReactNode;
    badge?: React.ReactNode;
    noPadding?: boolean;
    headerClassName?: string;
    bodyClassName?: string;
}

/**
 * ContentCard — High-end Apple-style container panel for tables, lists, and form blocks.
 * Provides unified borders, dark mode backgrounds, and typography.
 */
export function ContentCard({
    title,
    subtitle,
    action,
    badge,
    children,
    noPadding = false,
    className,
    headerClassName,
    bodyClassName,
    ...props
}: ContentCardProps) {
    const hasHeader = Boolean(title || subtitle || action || badge);

    return (
        <div
            data-slot="content-card"
            className={cn(
                'rounded-[24px] shadow-sm transition-colors duration-200 overflow-hidden',
                'bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10',
                className
            )}
            {...props}
        >
            {hasHeader && (
                <div
                    className={cn(
                        'flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-black/5 dark:border-white/10 px-6 sm:px-8 py-5',
                        headerClassName
                    )}
                >
                    <div className="space-y-0.5">
                        <div className="flex items-center gap-2">
                            {title && (
                                <h2 className="text-base font-bold text-[#1d1d1f] dark:text-white font-sans">
                                    {title}
                                </h2>
                            )}
                            {badge}
                        </div>
                        {subtitle && (
                            <p className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                {subtitle}
                            </p>
                        )}
                    </div>

                    {action && <div className="flex items-center gap-2 shrink-0">{action}</div>}
                </div>
            )}

            <div
                className={cn(
                    !noPadding && 'p-6 sm:p-8 space-y-6',
                    bodyClassName
                )}
            >
                {children}
            </div>
        </div>
    );
}

export default ContentCard;
