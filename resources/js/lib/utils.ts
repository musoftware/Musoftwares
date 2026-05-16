import { clsx, type ClassValue } from 'clsx';
import { format, formatDistanceToNow } from 'date-fns';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatMoney(amount: number | string, currency = 'USD') {
    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(numericAmount);
}

export function formatNumber(amount: number | string) {
    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('en-US').format(numericAmount);
}

export function formatDate(
    dateString: string | Date,
    formatStr = 'MMM d, yyyy',
) {
    if (!dateString) return '';
    const date =
        typeof dateString === 'string' ? new Date(dateString) : dateString;
    return format(date, formatStr);
}

export function timeAgo(dateString: string | Date) {
    if (!dateString) return '';
    const date =
        typeof dateString === 'string' ? new Date(dateString) : dateString;
    return formatDistanceToNow(date, { addSuffix: true });
}

export function statusColor(status: string): string {
    const s = status?.toLowerCase() || '';

    if (['draft', 'pending', 'locked', 'warning'].includes(s)) return 'yellow';
    if (['paid', 'success', 'credit', 'approved', 'positive'].includes(s))
        return 'green';
    if (
        [
            'error',
            'danger',
            'debit',
            'negative',
            'delete',
            'rejected',
            'canceled',
        ].includes(s)
    )
        return 'red';
    if (['info', 'sent', 'in progress', 'blue'].includes(s)) return 'blue';
    if (['purple'].includes(s)) return 'purple';

    return 'gray';
}
