import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';

export interface EmptyStateProps {
    icon?: any;
    title: string;
    description?: string;
    action?: any;
    actionLabel?: string;
    actionIcon?: any;
    onClick?: () => void;
    className?: string;
    tone?: 'neutral' | 'friendly';
}

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    actionLabel,
    actionIcon: ActionIcon,
    onClick,
    className,
    tone = 'neutral',
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-900/30 px-4 py-16 text-center transition-colors',
                className,
            )}
        >
            {Icon && (
                <div
                    className={cn(
                        'mb-4 flex h-16 w-16 items-center justify-center rounded-full border shadow-sm',
                        tone === 'friendly'
                            ? 'border-sky-100 dark:border-sky-900/40 bg-sky-50/70 dark:bg-sky-950/40'
                            : 'border-slate-100 dark:border-zinc-800 bg-white dark:bg-zinc-900',
                    )}
                >
                    <Icon className={cn('h-7 w-7', tone === 'friendly' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400 dark:text-zinc-500')} />
                </div>
            )}

            <h3 className="mt-4 font-sans text-lg font-semibold text-slate-900 dark:text-white">
                {title}
            </h3>

            {description && (
                <p className="mt-2 max-w-sm font-sans text-sm text-slate-500 dark:text-zinc-400">
                    {description}
                </p>
            )}

            {actionLabel && (action || onClick) && (
                <div className="mt-6">
                    {onClick ? (
                        <button
                            onClick={onClick}
                            className="inline-flex items-center justify-center rounded-lg border border-slate-900 dark:border-white bg-slate-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-slate-900 shadow-sm transition-colors hover:bg-slate-800 dark:hover:bg-zinc-100"
                        >
                            {ActionIcon && <ActionIcon className="me-2 h-4 w-4" />}
                            {actionLabel}
                        </button>
                    ) : (
                        <Link
                            href={action}
                            className="inline-flex items-center justify-center rounded-lg border border-slate-900 dark:border-white bg-slate-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-slate-900 shadow-sm transition-colors hover:bg-slate-800 dark:hover:bg-zinc-100"
                        >
                            {ActionIcon && <ActionIcon className="me-2 h-4 w-4" />}
                            {actionLabel}
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}
