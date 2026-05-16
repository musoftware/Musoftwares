import { FileQuestion } from 'lucide-react';
import { ReactNode } from 'react';

interface EmptyStateProps {
    title?: string;
    message: string;
    action?: ReactNode;
    icon?: ReactNode;
}

export default function EmptyState({
    title = 'No results found',
    message,
    action,
    icon,
}: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center">
            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm">
                {icon || <FileQuestion className="h-6 w-6" />}
            </div>
            <h3 className="mb-1 text-lg font-medium text-gray-900">{title}</h3>
            <p className="mx-auto mb-6 max-w-sm text-sm text-gray-500">
                {message}
            </p>
            {action && <div>{action}</div>}
        </div>
    );
}
