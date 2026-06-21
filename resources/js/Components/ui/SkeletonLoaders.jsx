import { cn } from '@/lib/utils';

export function SkeletonText({ width = '100%', height = '16px', className }) {
    return (
        <div
            className={cn('skeleton rounded', className)}
            style={{ width, height }}
        />
    );
}

export function SkeletonAvatar({ size = '40px', className }) {
    return (
        <div
            className={cn('skeleton shrink-0 rounded-full', className)}
            style={{ width: size, height: size }}
        />
    );
}

export function SkeletonCard({ className }) {
    return (
        <div
            className={cn(
                'bg-surface border-border rounded-xl border p-6',
                className,
            )}
        >
            <SkeletonText width="60%" height="24px" className="mb-4" />
            <SkeletonText width="100%" height="14px" className="mb-2" />
            <SkeletonText width="80%" height="14px" className="mb-2" />
            <SkeletonText width="40%" height="14px" />
        </div>
    );
}

export function SkeletonStatCard({ className }) {
    return (
        <div
            className={cn(
                'bg-surface border-border flex flex-col rounded-xl border p-6 shadow-sm',
                className,
            )}
        >
            <div className="mb-4 flex items-start justify-between">
                <SkeletonAvatar size="40px" />
            </div>
            <div>
                <SkeletonText
                    width="80px"
                    height="32px"
                    className="mb-2 rounded-md"
                />
                <SkeletonText
                    width="60px"
                    height="12px"
                    className="rounded-sm"
                />
            </div>
        </div>
    );
}

export function SkeletonTableRow({ cols = 4, className }) {
    return (
        <tr className={cn('border-border border-b', className)}>
            {Array.from({ length: cols }).map((_, i) => (
                <td key={i} className="px-4 py-3">
                    <SkeletonText
                        width={i === 0 ? '60%' : '100%'}
                        height="14px"
                    />
                </td>
            ))}
        </tr>
    );
}

export function SkeletonTable({ rows = 5, cols = 4, className }) {
    return (
        <div
            className={cn(
                'border-border bg-surface w-full overflow-hidden rounded-lg border',
                className,
            )}
        >
            <table className="w-full border-collapse text-start">
                <thead>
                    <tr className="bg-surface-raised border-border border-b">
                        {Array.from({ length: cols }).map((_, i) => (
                            <th key={i} className="px-4 py-3">
                                <SkeletonText width="40%" height="12px" />
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {Array.from({ length: rows }).map((_, i) => (
                        <SkeletonTableRow key={i} cols={cols} />
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function SkeletonForm({ fields = 4, className }) {
    return (
        <div className={cn('space-y-4', className)}>
            {Array.from({ length: fields }).map((_, i) => (
                <div key={i} className="space-y-1.5">
                    <SkeletonText width="30%" height="14px" />
                    <SkeletonText
                        width="100%"
                        height="38px"
                        className="rounded-md"
                    />
                </div>
            ))}
        </div>
    );
}
