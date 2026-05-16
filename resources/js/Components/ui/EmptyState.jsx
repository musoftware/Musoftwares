import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    actionLabel,
    actionIcon: ActionIcon,
    className,
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center px-4 py-16 text-center',
                className,
            )}
        >
            {Icon && (
                <div className="bg-primary-light mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                    <Icon className="text-primary h-7 w-7" />
                </div>
            )}

            <h3 className="font-sora text-text-primary mt-4 text-[16px] font-semibold">
                {title}
            </h3>

            {description && (
                <p className="text-text-muted mt-2 max-w-sm font-sans text-[14px]">
                    {description}
                </p>
            )}

            {action && actionLabel && (
                <div className="mt-6">
                    <Link
                        href={action}
                        className="bg-primary hover:bg-primary-hover inline-flex items-center justify-center rounded-lg px-4 py-2 text-[14px] font-medium text-white shadow-sm transition-colors"
                    >
                        {ActionIcon && <ActionIcon className="mr-2 h-4 w-4" />}
                        {actionLabel}
                    </Link>
                </div>
            )}
        </div>
    );
}
