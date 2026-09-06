import React from 'react';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Search } from 'lucide-react';

export interface PageHeroHeaderProps {
    title: React.ReactNode;
    description?: React.ReactNode;
    badge?: React.ReactNode;
    backLink?: {
        href: string;
        label: string;
    };
    backHref?: string;
    backLabel?: string;
    actions?: React.ReactNode;
    search?: {
        value: string;
        onChange: (value: string) => void;
        placeholder?: string;
    };
    searchValue?: string;
    onSearchChange?: (value: string) => void;
    searchPlaceholder?: string;
    maxWidth?: 'default' | 'full' | '7xl';
    className?: string;
}

const maxWidthMap = {
    default: 'max-w-[1400px]',
    '7xl': 'max-w-7xl',
    full: 'w-full',
};

/**
 * PageHeroHeader — Standardized Apple-style banner for page headers with full dark mode support.
 */
export function PageHeroHeader({
    title,
    description,
    badge,
    backLink,
    backHref,
    backLabel,
    actions,
    search,
    searchValue,
    onSearchChange,
    searchPlaceholder,
    maxWidth = 'default',
    className,
}: PageHeroHeaderProps) {
    const resolvedBackLink = backLink || (backHref && backLabel ? { href: backHref, label: backLabel } : undefined);
    const resolvedSearch = search || (searchValue !== undefined && onSearchChange ? {
        value: searchValue,
        onChange: onSearchChange,
        placeholder: searchPlaceholder,
    } : undefined);
    return (
        <div
            data-slot="page-hero-header"
            className={cn(
                'w-full py-8 px-6 sm:px-10 transition-colors duration-200',
                'bg-white dark:bg-[#0f172a] border-b border-black/5 dark:border-white/10',
                className
            )}
        >
            <div
                className={cn(
                    'mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6',
                    maxWidthMap[maxWidth]
                )}
            >
                <div className="space-y-2 min-w-0">
                    {resolvedBackLink && (
                        <div>
                            <Link
                                href={resolvedBackLink.href}
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5 rtl:rotate-180" />
                                {resolvedBackLink.label}
                            </Link>
                        </div>
                    )}

                    {badge && <div className="flex items-center gap-3">{badge}</div>}

                    <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] dark:text-white font-sans">
                        {title}
                    </h1>

                    {description && (
                        <p className="text-xs sm:text-sm text-[#1d1d1f]/70 dark:text-zinc-400 font-sans max-w-2xl">
                            {description}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3 shrink-0">
                    {resolvedSearch && (
                        <div className="w-full sm:w-72 relative">
                            <Search className="w-4 h-4 text-[#1d1d1f]/40 dark:text-zinc-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                type="text"
                                placeholder={resolvedSearch.placeholder || 'Search...'}
                                value={resolvedSearch.value}
                                onChange={(e) => resolvedSearch.onChange(e.target.value)}
                                className={cn(
                                    'w-full h-10 ps-10 pe-4 rounded-[980px] text-xs sm:text-sm transition-all shadow-inner outline-none',
                                    'bg-[#f5f5f7] dark:bg-zinc-900 border border-black/5 dark:border-white/10',
                                    'text-[#1d1d1f] dark:text-zinc-100 placeholder:text-[#1d1d1f]/40 dark:placeholder:text-zinc-500',
                                    'focus:ring-2 focus:ring-[#0071e3] focus:bg-white dark:focus:bg-zinc-950'
                                )}
                            />
                        </div>
                    )}

                    {actions}
                </div>
            </div>
        </div>
    );
}

export default PageHeroHeader;
