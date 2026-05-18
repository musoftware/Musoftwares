import React from 'react';
import { Globe, Zap, Eye, Bot, Database, Monitor, Activity } from 'lucide-react';

const CATEGORY_CONFIG: Record<string, { icon: React.ElementType; color: string }> = {
    scraper:    { icon: Globe,    color: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
    automation: { icon: Zap,      color: 'bg-amber-50 text-amber-700 border-amber-200' },
    ocr:        { icon: Eye,      color: 'bg-teal-50 text-teal-700 border-teal-200' },
    ai:         { icon: Bot,      color: 'bg-violet-50 text-violet-700 border-violet-200' },
    data:       { icon: Database, color: 'bg-cyan-50 text-cyan-700 border-cyan-200' },
    browser:    { icon: Monitor,  color: 'bg-blue-50 text-blue-700 border-blue-200' },
    monitoring: { icon: Activity, color: 'bg-rose-50 text-rose-700 border-rose-200' },
};

interface RuntimeBadgeProps {
    category: string;
    label: string;
}

export function RuntimeBadge({ category, label }: RuntimeBadgeProps) {
    const config = CATEGORY_CONFIG[category] ?? { icon: Zap, color: 'bg-slate-50 text-slate-700 border-slate-200' };
    const Icon = config.icon;

    return (
        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs font-medium ${config.color}`}>
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}
