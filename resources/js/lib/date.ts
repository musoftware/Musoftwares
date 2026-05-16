import { format, formatDistanceToNow } from 'date-fns';

export function formatDate(date: string | Date, formatStr: string = 'PP') {
    return format(new Date(date), formatStr);
}

export function formatRelative(date: string | Date) {
    return formatDistanceToNow(new Date(date), { addSuffix: true });
}
