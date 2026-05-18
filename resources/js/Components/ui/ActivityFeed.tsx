import React from 'react';
import { cn } from '@/lib/utils';

export interface ActivityItem {
    id: string | number;
    text: string | React.ReactNode;
    time: string;
    icon?: any;
    color?: string;
}

export interface ActivityFeedProps {
    items: ActivityItem[];
    className?: string;
}

export function ActivityFeed({ items, className }: ActivityFeedProps) {
    if (!items || items.length === 0) {
        return <div className="text-sm text-text-muted">No recent activity.</div>;
    }

    return (
        <div className={cn("space-y-6 relative before:absolute before:inset-0 before:ml-2.5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-border/60 before:to-transparent", className)}>
            {items.map((item) => (
                <div key={item.id} className="relative flex items-start justify-between gap-4">
                    <div className={cn("w-5 h-5 rounded-full mt-0.5 shrink-0 flex items-center justify-center border-2 border-surface z-10", item.color || "bg-slate-200")}>
                        {item.icon ? <item.icon className="w-3 h-3 text-white" /> : <div className="w-1.5 h-1.5 rounded-full bg-slate-500" />}
                    </div>
                    <div className="flex-1 min-w-0 flex flex-col pt-0.5">
                        <div className="text-sm text-text-primary">{item.text}</div>
                        <div className="text-[11px] text-text-muted mt-0.5">{item.time}</div>
                    </div>
                </div>
            ))}
        </div>
    );
}
