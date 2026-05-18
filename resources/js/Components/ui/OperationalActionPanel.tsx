import React from 'react';
import { cn } from '@/lib/utils';
import { ChevronRight } from 'lucide-react';

export interface OperationalActionPanelProps {
    title?: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
}

export function OperationalActionPanel({
    title = 'Quick Actions',
    description,
    children,
    className
}: OperationalActionPanelProps) {
    return (
        <div className={cn("space-y-6", className)}>
            {(title || description) && (
                <div>
                    {title && <h3 className="text-base font-semibold tracking-tight text-slate-900">{title}</h3>}
                    {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
                </div>
            )}
            <div className="space-y-3">
                {children}
            </div>
        </div>
    );
}

export interface OperationalActionItemProps {
    icon: React.ElementType;
    label: string;
    onClick: () => void;
    colorClass?: string;
}

export function OperationalActionItem({
    icon: Icon,
    label,
    onClick,
    colorClass = "text-slate-600 group-hover:text-slate-900"
}: OperationalActionItemProps) {
    return (
        <button 
            onClick={onClick} 
            className="group flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white p-3 text-left shadow-sm transition-all hover:border-slate-300 hover:shadow-md"
        >
            <div className="flex items-center gap-3">
                <div className="rounded-lg bg-slate-50 p-2 transition-colors group-hover:bg-slate-100">
                    <Icon className={cn("h-4 w-4", colorClass)} />
                </div>
                <span className="text-sm font-medium text-slate-700">{label}</span>
            </div>
            <ChevronRight className="h-4 w-4 text-slate-400 transition-colors group-hover:text-slate-600" />
        </button>
    );
}
