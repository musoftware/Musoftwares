import React from 'react';
import { Card, CardContent } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import { ChevronRight } from 'lucide-react';

export interface ActionCardProps {
    title: string;
    description?: string;
    icon: React.ElementType;
    onClick?: () => void;
    className?: string;
}

export function ActionCard({ title, description, icon: Icon, onClick, className }: ActionCardProps) {
    return (
        <Card 
            className={cn(
                'group cursor-pointer rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/80 shadow-sm transition-all hover:border-slate-300 dark:hover:border-zinc-700 hover:shadow-md',
                className
            )}
            onClick={onClick}
        >
            <CardContent className="flex items-center justify-between p-4 sm:p-6">
                <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-50 dark:bg-zinc-800 transition-colors group-hover:bg-indigo-50 dark:group-hover:bg-indigo-950/40">
                        <Icon className="h-6 w-6 text-slate-600 dark:text-zinc-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" />
                    </div>
                    <div>
                        <h3 className="text-base font-medium text-slate-900 dark:text-white">{title}</h3>
                        {description && <p className="mt-0.5 text-sm text-slate-500 dark:text-zinc-400">{description}</p>}
                    </div>
                </div>
                <ChevronRight className="h-5 w-5 text-slate-400 dark:text-zinc-500 transition-transform group-hover:translate-x-1 group-hover:text-slate-600 dark:group-hover:text-zinc-300" />
            </CardContent>
        </Card>
    );
}
