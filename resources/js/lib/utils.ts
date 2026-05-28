import { clsx, type ClassValue } from 'clsx';
import { format, formatDistanceToNow } from 'date-fns';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const CURRENCY_FORMATS: Record<string, string> = {
    'USD': '$%v',
    'EGP': 'e£%v',
    'EUR': '€%v',
    'GBP': '£%v',
    'AED': '%v د.إ',
    'MAD': '%v MAD',
    'SAR': '%v SAR',
    'IQD': '%v IQD'
};

export function formatMoney(amount: number | string, currency = 'USD') {
    const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(numericAmount)) return `${currency} 0.00`;
    
    const curCode = (typeof currency === 'string' ? currency : 'USD').trim().toUpperCase();
    
    const isNegative = numericAmount < 0;
    const absoluteAmount = Math.abs(numericAmount);
    
    const numberPart = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(absoluteAmount);

    // Dynamic look up from window.currencies shared from database
    const dynamicCurrencies = (window as any).currencies;
    if (Array.isArray(dynamicCurrencies)) {
        const found = dynamicCurrencies.find(c => c.currency && c.currency.toUpperCase() === curCode);
        if (found) {
            const symbol = found.symbol || curCode;
            const fmt = found.string_format;
            if (fmt) {
                const specifiers = ['%01.2f', '%s', '%.2f'];
                for (const spec of specifiers) {
                    if (fmt.includes(spec)) {
                        const formatted = fmt.replace(spec, numberPart);
                        return isNegative ? `-${formatted}` : formatted;
                    }
                }
                if (fmt.includes('{amount}') || fmt.includes('{symbol}') || fmt.includes('{code}')) {
                    const formatted = fmt
                        .replace('{symbol}', symbol)
                        .replace('{amount}', numberPart)
                        .replace('{code}', curCode);
                    return isNegative ? `-${formatted}` : formatted;
                }
            }
            return isNegative ? `-${symbol}${numberPart}` : `${symbol}${numberPart}`;
        }
    }

    if (CURRENCY_FORMATS[curCode]) {
        const formatted = CURRENCY_FORMATS[curCode].replace('%v', numberPart);
        return isNegative ? `-${formatted}` : formatted;
    }
    
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: curCode,
        }).format(numericAmount);
    } catch (e) {
        return `${curCode} ${numberPart}`;
    }
}

export function formatCompactCurrency(amount: number | string, currency = 'USD') {
    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(numericAmount)) return `${currency} 0`;
    
    if (Math.abs(numericAmount) >= 1_000_000) {
        try {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: typeof currency === 'string' && currency.trim() !== '' ? currency : 'USD',
                notation: 'compact',
                maximumFractionDigits: 1,
            }).format(numericAmount);
        } catch (e) {
            return `${currency} ${(numericAmount / 1_000_000).toFixed(1)}M`;
        }
    }
    return formatMoney(numericAmount, currency);
}

export function formatNumber(amount: number | string) {
    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('en-US').format(numericAmount);
}

/** Formats date as "May 17, 2026" */
export function formatDate(
    dateString: string | Date | null | undefined,
    formatStr = 'MMM d, yyyy',
): string {
    if (!dateString) return '—';
    try {
        const date =
            typeof dateString === 'string' ? new Date(dateString) : dateString;
        if (isNaN(date.getTime())) return '—';
        return format(date, formatStr);
    } catch {
        return '—';
    }
}

/** Alias for formatDate with "MMM d, yyyy" — human-readable */
export function formatDateHuman(dateString: string | Date | null | undefined): string {
    return formatDate(dateString, 'MMM d, yyyy');
}

/** Returns relative date like "2 hours ago" */
export function formatDateRelative(dateString: string | Date | null | undefined): string {
    if (!dateString) return '—';
    try {
        const date =
            typeof dateString === 'string' ? new Date(dateString) : dateString;
        if (isNaN(date.getTime())) return '—';
        return formatDistanceToNow(date, { addSuffix: true });
    } catch {
        return '—';
    }
}

/** Alias for formatDateRelative */
export function timeAgo(dateString: string | Date | null | undefined): string {
    return formatDateRelative(dateString);
}

export function statusColor(status: string): string {
    const s = status?.toLowerCase().replace(/[-\s]/g, '_') || '';

    // Green — success / paid / active states
    if ([
        'paid', 'success', 'credit', 'approved', 'positive', 'active',
        'completed', 'retained', 'paying', 'resolved', 'won', 'verified',
        'enabled', 'open',
    ].includes(s))
        return 'green';

    // Yellow — pending / warning / draft states
    if ([
        'draft', 'pending', 'locked', 'warning', 'in_review', 'on_hold',
        'awaiting', 'processing', 'review', 'flagged', 'trial',
    ].includes(s))
        return 'yellow';

    // Red — errors / overdue / cancelled / destructive states
    if ([
        'error', 'danger', 'debit', 'negative', 'delete', 'rejected',
        'canceled', 'cancelled', 'overdue', 'expired', 'lost', 'failed',
        'blocked', 'revoked',
    ].includes(s))
        return 'red';

    // Blue — informational / sent / in-progress states
    if ([
        'info', 'sent', 'in_progress', 'blue', 'submitted', 'new', 'lead',
    ].includes(s))
        return 'blue';

    // Purple — special / premium states
    if (['purple', 'vip', 'premium', 'featured', 'special'].includes(s))
        return 'purple';

    // Gray — archived / neutral states (default fallback)
    return 'gray';
}
