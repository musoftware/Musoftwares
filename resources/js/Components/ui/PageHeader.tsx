import { Breadcrumbs } from './Breadcrumbs';

// Force Vite recompile

export interface PageHeaderProps {
    title: string;
    subtitle?: string;
    icon?: any;
    actions?: any;
    breadcrumbs?: any[];
}

export function PageHeader({
    title,
    subtitle,
    icon: Icon,
    actions,
    breadcrumbs,
}: PageHeaderProps) {
    return (
        <div className="mb-6 flex flex-col space-y-4">
            {breadcrumbs && breadcrumbs.length > 0 && (
                <Breadcrumbs items={breadcrumbs} />
            )}
            <div className="flex items-start justify-between border-b border-slate-100 dark:border-zinc-800 pb-6">
                <div className="flex items-center space-x-4">
                    {Icon && (
                        <div className="bg-indigo-50 dark:bg-indigo-950/40 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-indigo-100 dark:border-indigo-900/50">
                            <Icon className="text-indigo-600 dark:text-indigo-400 h-5 w-5" />
                        </div>
                    )}
                    <div>
                        <h1 className="font-sans text-slate-900 dark:text-white text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {subtitle && (
                            <p className="text-slate-500 dark:text-zinc-400 mt-1 font-sans text-sm">
                                {subtitle}
                            </p>
                        )}
                    </div>
                </div>
                {actions && (
                    <div className="flex shrink-0 items-center space-x-3">
                        {actions}
                    </div>
                )}
            </div>
        </div>
    );
}
