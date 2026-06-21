import { cn, formatDate, timeAgo } from '@/lib/utils';
import { format } from 'date-fns';

export function DateDisplay({
    date,
    formatStr = 'MMM d, yyyy',
    showRelative = false,
    showTooltip = false,
    className,
}) {
    if (!date) return null;

    const parsedDate = typeof date === 'string' ? new Date(date) : date;

    const primaryText = formatDate(parsedDate, formatStr);
    const relativeText = showRelative ? timeAgo(parsedDate) : null;
    const fullDateTime = showTooltip
        ? format(parsedDate, 'MMM d, yyyy h:mm a')
        : null;

    return (
        <div
            className={cn(
                'text-text-primary inline-flex items-center font-sans text-[13px]',
                className,
            )}
            title={fullDateTime}
        >
            <span>{primaryText}</span>
            {relativeText && (
                <span className="text-text-muted ms-1.5">({relativeText})</span>
            )}
        </div>
    );
}
