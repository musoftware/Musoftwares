import { ReactNode } from 'react';

interface PageHeaderProps {
    title: string;
    subtitle?: string;
    children?: ReactNode; // For actions/buttons on the right
}

export default function PageHeader({
    title,
    subtitle,
    children,
}: PageHeaderProps) {
    return (
        <div className="mb-6 flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
            <div>
                <h1 className="text-2xl font-bold tracking-tight text-gray-900">
                    {title}
                </h1>
                {subtitle && (
                    <p className="mt-1 text-sm text-gray-500">{subtitle}</p>
                )}
            </div>
            {children && (
                <div className="flex items-center space-x-2">{children}</div>
            )}
        </div>
    );
}
