import React from 'react';
import { Breadcrumbs } from './Breadcrumbs';

export interface ModulePageHeaderProps {
    title: string;
    description?: string;
    icon?: React.ElementType;
    actions?: React.ReactNode;
    breadcrumbs?: any[];
    filters?: React.ReactNode;
}

export function ModulePageHeader({
    title,
    description,
    icon: Icon,
    actions,
    breadcrumbs,
    filters,
}: ModulePageHeaderProps) {
    return (
        <div className="mb-8 flex flex-col space-y-6">
            {breadcrumbs && breadcrumbs.length > 0 && (
                <div className="mb-2">
                    <Breadcrumbs items={breadcrumbs} />
                </div>
            )}
            
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-start space-x-4">
                    {Icon && (
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm">
                            <Icon className="h-5 w-5 text-slate-700" />
                        </div>
                    )}
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-1 text-sm text-slate-500">
                                {description}
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
            
            {filters && (
                <div className="border-t border-slate-100 pt-4">
                    {filters}
                </div>
            )}
        </div>
    );
}
