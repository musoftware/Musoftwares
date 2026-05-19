import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Input } from '@/Components/ui/input';
import { ToolCard } from '@/Components/Tools/ToolCard';
import { PlatformBadges } from '@/Components/Tools/PlatformBadge';
import {
    Search, ShoppingBag, Zap,
    Globe, Eye, Database, Bot, Monitor, Activity, Package
} from 'lucide-react';

const CATEGORY_ICONS: Record<string, React.ElementType> = {
    scraper:    Globe,
    automation: Zap,
    ocr:        Eye,
    ai:         Bot,
    data:       Database,
    browser:    Monitor,
    monitoring: Activity,
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

interface Props {
    tools: { data: Tool[]; links: any[] };
    categories: Record<string, string>;
    subscribedSlugs: string[];
    filters: { search?: string; category?: string };
}

export default function Explore({ tools, categories, subscribedSlugs, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [activeCategory, setActiveCategory] = useState(filters.category || '');

    const applyFilter = (params: { search?: string; category?: string }) => {
        router.get(route('tools.explore'), { ...filters, ...params }, {
            preserveState: true, replace: true
        });
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        clearTimeout((window as any).__toolsSearchDebounce);
        (window as any).__toolsSearchDebounce = setTimeout(() => {
            applyFilter({ search: value, category: activeCategory });
        }, 320);
    };

    const handleCategory = (cat: string) => {
        const next = activeCategory === cat ? '' : cat;
        setActiveCategory(next);
        applyFilter({ search, category: next });
    };

    const featuredTools = tools.data.filter(t => t.is_featured);
    const regularTools  = tools.data.filter(t => !t.is_featured);

    return (
        <ToolsPublicLayout title="Tools Marketplace" activeNav="explore">
            <Head title="Tools Marketplace" />

            {/* Hero section */}
            <div className="bg-white border-b border-slate-200/80">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
                    <div className="max-w-2xl">
                        <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium mb-4">
                            <Package className="h-3 w-3" />
                            {tools.data.length} tools available
                        </div>
                        <h1 className="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight leading-tight mb-3">
                        Professional Automation Tools
                        <br />
                        <span className="text-slate-400 font-normal">for power users.</span>
                    </h1>
                        <p className="text-slate-500 text-base leading-relaxed max-w-xl">
                            Scraping, automation, AI-powered workflows — run directly in your browser with a single subscription.
                        </p>
                    </div>

                    {/* Search */}
                    <div className="mt-6 relative max-w-sm">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input
                            placeholder="Search tools..."
                            value={search}
                            onChange={e => handleSearch(e.target.value)}
                            className="pl-9 h-10 bg-white border-slate-200 text-sm placeholder:text-slate-400 focus-visible:ring-slate-900 focus-visible:ring-1"
                        />
                    </div>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

                {/* Category filter strip */}
                <div className="flex flex-wrap gap-1.5">
                    <button
                        onClick={() => handleCategory('')}
                        className={`px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all border ${
                            activeCategory === ''
                                ? 'bg-slate-900 text-white border-slate-900'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                        }`}
                    >
                        All Tools
                    </button>
                    {Object.entries(categories).map(([key, label]) => {
                        const Icon = CATEGORY_ICONS[key] ?? Zap;
                        return (
                            <button
                                key={key}
                                onClick={() => handleCategory(key)}
                                className={`flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all border ${
                                    activeCategory === key
                                        ? 'bg-slate-900 text-white border-slate-900'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                }`}
                            >
                                <Icon className="h-3 w-3" />
                                {label}
                            </button>
                        );
                    })}
                </div>

                {/* Tool grid */}
                {tools.data.length === 0 ? (
                    <div className="text-center py-20">
                        <div className="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                            <ShoppingBag className="h-7 w-7 text-slate-300" />
                        </div>
                        <p className="text-base font-semibold text-slate-700 mb-1">No tools found</p>
                        <p className="text-sm text-slate-400">Try adjusting your search or category.</p>
                    </div>
                ) : (
                    <>
                        {/* Featured section */}
                        {featuredTools.length > 0 && activeCategory === '' && !filters.search && (
                            <div className="space-y-3">
                                <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Featured</h2>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    {featuredTools.map(tool => (
                                        <ToolCard
                                            key={tool.id}
                                            tool={tool}
                                            isSubscribed={subscribedSlugs.includes(tool.slug)}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* All tools */}
                        {(activeCategory !== '' || !!filters.search || featuredTools.length === 0) ? (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {tools.data.map(tool => (
                                    <ToolCard
                                        key={tool.id}
                                        tool={tool}
                                        isSubscribed={subscribedSlugs.includes(tool.slug)}
                                    />
                                ))}
                            </div>
                        ) : regularTools.length > 0 ? (
                            <div className="space-y-3">
                                <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">All Tools</h2>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    {regularTools.map(tool => (
                                        <ToolCard
                                            key={tool.id}
                                            tool={tool}
                                            isSubscribed={subscribedSlugs.includes(tool.slug)}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}
                    </>
                )}

                {/* Pagination */}
                {tools.links.length > 3 && (
                    <div className="flex justify-center gap-1 pt-4">
                        {tools.links.map((link: any, i: number) => (
                            <button
                                key={i}
                                disabled={!link.url || link.active}
                                onClick={() => link.url && router.visit(link.url)}
                                className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
                                    link.active
                                        ? 'bg-slate-900 text-white'
                                        : link.url
                                        ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
                                        : 'bg-white border border-slate-100 text-slate-300 cursor-not-allowed'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </ToolsPublicLayout>
    );
}
