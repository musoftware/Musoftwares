import React from 'react';
import { router } from '@inertiajs/react';
import { Users, Zap } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { PlatformBadges } from './PlatformBadge';

const CATEGORY_ICON_MAP: Record<string, string> = {
    scraper:    '🌐',
    automation: '⚡',
    ocr:        '👁',
    ai:         '🤖',
    data:       '🗄️',
    browser:    '💻',
    monitoring: '📊',
};

interface Tool {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    icon_url: string | null;
    category: string;
    category_label: string;
    supported_os: string[];
    current_version: string;
    download_count: number;
    is_featured: boolean;
    starting_price: number;
    is_free: boolean;
}

interface ToolCardProps {
    tool: Tool;
    isSubscribed?: boolean;
}

export function ToolCard({ tool, isSubscribed = false }: ToolCardProps) {
    const emoji = CATEGORY_ICON_MAP[tool.category] ?? '📦';

    const safePlatforms = Array.isArray(tool.supported_os)
        ? tool.supported_os
        : typeof tool.supported_os === 'string'
            ? (() => { try { return JSON.parse(tool.supported_os); } catch { return []; } })()
            : [];

    return (
        <div
            onClick={() => router.visit(route('tools.show', tool.slug))}
            className="group relative bg-white border border-slate-200/80 rounded-xl p-5 cursor-pointer
                       hover:border-slate-300 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5
                       flex flex-col gap-3"
        >
            {/* Badges (top-right) */}
            <div className="absolute top-3 right-3 flex flex-col items-end gap-1">
                {tool.is_featured && (
                    <Badge className="bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-50 text-[10px] px-1.5 py-0 font-medium">
                        Featured
                    </Badge>
                )}
                {isSubscribed && (
                    <Badge className="bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-50 text-[10px] px-1.5 py-0 font-medium">
                        ✓ Owned
                    </Badge>
                )}
            </div>

            {/* Icon */}
            <div className="flex items-start gap-3">
                <div className="w-11 h-11 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0 group-hover:border-slate-200 transition-colors">
                    {tool.icon_url ? (
                        <img src={tool.icon_url} alt={tool.title} className="w-7 h-7 object-contain" />
                    ) : (
                        <span className="text-xl">{emoji}</span>
                    )}
                </div>
                <div className="flex-1 min-w-0 pr-10">
                    <h3 className="font-semibold text-slate-900 text-sm leading-tight truncate">{tool.title}</h3>
                    <p className="text-[11px] text-slate-400 font-medium mt-0.5 capitalize">{tool.category_label}</p>
                </div>
            </div>

            {/* Description */}
            <p className="text-xs text-slate-500 leading-relaxed line-clamp-2">{tool.short_description}</p>

            {/* Footer */}
            <div className="mt-auto space-y-2.5">
                <PlatformBadges platforms={safePlatforms} size="sm" />

                <div className="flex items-center justify-between">
                    <span className={`text-xs font-semibold ${tool.is_free ? 'text-emerald-600' : 'text-slate-800'}`}>
                        {tool.is_free ? 'Free' : `From $${tool.starting_price}/mo`}
                    </span>
                    <span className="flex items-center gap-1 text-[11px] text-slate-400">
                        <Users className="h-3 w-3" />
                        {tool.download_count.toLocaleString()}
                    </span>
                </div>
            </div>
        </div>
    );
}
