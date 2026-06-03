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
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center px-4 py-16 text-center border border-dashed border-slate-200 bg-slate-50/50 rounded-2xl',
                className,
            )}
        >
            {Icon && (
                <div className="bg-white border border-slate-100 shadow-sm mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                    <Icon className="text-slate-400 h-7 w-7" />
                </div>
            )}

            <h3 className="font-sans text-slate-900 mt-4 text-lg font-semibold">
                {title}
            </h3>

            {description && (
                <p className="text-slate-500 mt-2 max-w-sm font-sans text-sm">
                    {description}
                </p>
            )}

            {actionLabel && (action || onClick) && (
                <div className="mt-6">
                    {onClick ? (
                        <button
                            onClick={onClick}
                            className="bg-slate-900 hover:bg-slate-800 border border-slate-900 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors"
                        >
                            {ActionIcon && <ActionIcon className="mr-2 h-4 w-4" />}
                            {actionLabel}
                        </button>
                    ) : (
                        <Link
                            href={action}
                            className="bg-slate-900 hover:bg-slate-800 border border-slate-900 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors"
                        >
                            {ActionIcon && <ActionIcon className="mr-2 h-4 w-4" />}
                            {actionLabel}
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}
