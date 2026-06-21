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
            className="group relative bg-white border border-slate-200/80 rounded-xl p-4 cursor-pointer
                       hover:border-slate-300 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5
                       flex flex-col sm:flex-row gap-4 sm:items-center"
        >
            {/* Icon */}
            <div className="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0 group-hover:border-slate-200 transition-colors">
                {tool.icon_url ? (
                    <img src={tool.icon_url} alt={tool.title} className="w-7 h-7 object-contain" />
                ) : (
                    <span className="text-xl">{emoji}</span>
                )}
            </div>

            {/* Title & Description */}
            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    <h3 className="font-semibold text-slate-900 text-sm leading-tight truncate">{tool.title}</h3>
                    {tool.is_featured && (
                        <Badge className="bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-50 text-[10px] px-1.5 py-0 font-medium h-4">
                            Featured
                        </Badge>
                    )}
                    {isSubscribed && (
                        <Badge className="bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-50 text-[10px] px-1.5 py-0 font-medium h-4">
                            ✓ Owned
                        </Badge>
                    )}
                </div>
                <p className="text-xs text-slate-500 leading-relaxed line-clamp-1 sm:line-clamp-2 mb-1.5">{tool.short_description}</p>
                <div className="flex items-center gap-3 text-[11px] font-medium">
                    <span className="text-slate-400 capitalize">{tool.category_label}</span>
                </div>
            </div>

            {/* End / Footer */}
            <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3 sm:gap-2 sm:ps-4 sm:border-s border-slate-100 min-w-[140px]">
                <PlatformBadges platforms={safePlatforms} size="sm" />
                <span className={`text-sm font-semibold ${tool.is_free ? 'text-emerald-600' : 'text-slate-800'}`}>
                    {tool.is_free ? 'Free' : `From $${tool.starting_price}/mo`}
                </span>
            </div>
        </div>
    );
}
