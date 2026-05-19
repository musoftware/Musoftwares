import React from 'react';
import { formatDate, formatDateRelative, formatDateHuman } from '@/lib/utils';

export interface DateDisplayProps {
    date: string | Date | null | undefined;
    /** 'full' → "May 17, 2026" | 'relative' → "2 hours ago" | 'short' → "May 17" | 'datetime' → "May 17, 2026 at 12:09 PM" */
    format?: 'full' | 'relative' | 'short' | 'datetime' | 'both';
    className?: string;
}

/**
 * DateDisplay — renders a date in a human-readable format.
 * Never renders raw ISO strings like "2026-05-17T12:09:04.000000Z".
 *
 * Usage:
 *   <DateDisplay date={invoice.created_at} />              → "May 17, 2026"
 *   <DateDisplay date={invoice.created_at} format="relative" /> → "2 hours ago"
 *   <DateDisplay date={invoice.created_at} format="both" />    → "May 17, 2026 · 2 hours ago"
 */
export function DateDisplay({ date, format = 'full', className }: DateDisplayProps) {
    if (!date) return <span className={className}>—</span>;

    let display: string;

    switch (format) {
        case 'relative':
            display = formatDateRelative(date);
            break;
        case 'short':
            display = formatDate(date, 'MMM d');
            break;
        case 'datetime':
            display = formatDate(date, 'MMM d, yyyy · h:mm a');
            break;
        case 'both':
            return (
                <span className={className}>
                    <span>{formatDateHuman(date)}</span>
                    <span className="text-slate-400 ml-1.5">· {formatDateRelative(date)}</span>
                </span>
            );
        case 'full':
        default:
            display = formatDateHuman(date);
            break;
    }

    return <span className={className}>{display}</span>;
}
