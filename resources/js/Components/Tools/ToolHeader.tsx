import React from 'react';
import { router } from '@inertiajs/react';
import { Download, Star, Cpu, CheckCircle2, ChevronRight } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { PlatformBadges } from './PlatformBadge';
import { RuntimeBadge } from './RuntimeBadge';

interface ToolHeaderProps {
    tool: {
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
    };
    isSubscribed: boolean;
}

export function ToolHeader({ tool, isSubscribed }: ToolHeaderProps) {
    const safePlatforms = Array.isArray(tool.supported_os)
        ? tool.supported_os
        : typeof tool.supported_os === 'string'
            ? (() => { try { return JSON.parse(tool.supported_os); } catch { return []; } })()
            : [];

    return (
        <div className="space-y-4">
            {/* Breadcrumb */}
            <nav className="flex items-center gap-1.5 text-xs text-slate-400">
                <button
                    onClick={() => router.visit(route('tools.explore'))}
                    className="hover:text-slate-600 transition-colors"
                >
                    Marketplace
                </button>
                <ChevronRight className="h-3 w-3" />
                <span className="capitalize text-slate-500">{tool.category_label}</span>
                <ChevronRight className="h-3 w-3" />
                <span className="text-slate-700 font-medium truncate">{tool.title}</span>
            </nav>

            {/* Hero row */}
            <div className="flex items-start gap-5">
                {/* App icon */}
                <div className="w-16 h-16 rounded-2xl bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 shadow-sm">
                    {tool.icon_url ? (
                        <img src={tool.icon_url} alt={tool.title} className="w-11 h-11 object-contain" />
                    ) : (
                        <Cpu className="h-8 w-8 text-slate-300" />
                    )}
                </div>

                <div className="flex-1 min-w-0">
                    {/* Title row */}
                    <div className="flex items-center gap-2 flex-wrap mb-1.5">
                        <h1 className="text-xl font-bold text-slate-900 tracking-tight">{tool.title}</h1>
                        {isSubscribed && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <CheckCircle2 className="h-3 w-3" />
                                Subscribed
                            </span>
                        )}
                        {tool.is_featured && (
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <Star className="h-3 w-3" />
                                Featured
                            </span>
                        )}
                    </div>

                    {/* Badges row */}
                    <div className="flex items-center flex-wrap gap-2 mb-2.5">
                        <RuntimeBadge category={tool.category} label={tool.category_label} />
                        <Badge variant="outline" className="text-slate-500 font-mono text-[11px] px-2 py-0">
                            v{tool.current_version}
                        </Badge>
                        <PlatformBadges platforms={safePlatforms} size="sm" />
                    </div>

                    {/* Description */}
                    <p className="text-sm text-slate-600 leading-relaxed max-w-2xl">{tool.short_description}</p>

                    {/* Stats */}
                    <div className="flex items-center gap-4 mt-3 text-xs text-slate-400">
                        <span className="flex items-center gap-1">
                            <Download className="h-3 w-3" />
                            {tool.download_count.toLocaleString()} downloads
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}
