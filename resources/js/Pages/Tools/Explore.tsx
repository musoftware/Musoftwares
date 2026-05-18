import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { 
    Search, Download, ShoppingBag, Zap, 
    Globe, Eye, Database, Bot, Monitor, Activity
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

    return (
        <ToolsPublicLayout title="Tools Marketplace" activeNav="explore">
            <Head title="Tools Marketplace" />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Tools Marketplace</h1>
                        <p className="text-sm text-slate-500 mt-1">Download powerful desktop tools. Subscribe to unlock.</p>
                    </div>
                    <div className="relative w-full sm:w-72">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input
                            placeholder="Search tools..."
                            value={search}
                            onChange={e => handleSearch(e.target.value)}
                            className="pl-9 bg-white"
                        />
                    </div>
                </div>

                {/* Category Pills */}
                <div className="flex flex-wrap gap-2">
                    <button
                        onClick={() => handleCategory('')}
                        className={`px-4 py-1.5 rounded-full text-sm font-medium transition-all border ${
                            activeCategory === ''
                                ? 'bg-slate-900 text-white border-slate-900'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
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
                                className={`flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition-all border ${
                                    activeCategory === key
                                        ? 'bg-slate-900 text-white border-slate-900'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                                }`}
                            >
                                <Icon className="h-3.5 w-3.5" />
                                {label}
                            </button>
                        );
                    })}
                </div>

                {/* Tool Grid */}
                {tools.data.length === 0 ? (
                    <div className="text-center py-20 text-slate-400">
                        <ShoppingBag className="h-12 w-12 mx-auto mb-4 opacity-30" />
                        <p className="text-lg font-medium text-slate-600">No tools found</p>
                        <p className="text-sm mt-1">Try adjusting your search or category filter.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {tools.data.map(tool => {
                            const Icon = CATEGORY_ICONS[tool.category] ?? Zap;
                            const isSubscribed = subscribedSlugs.includes(tool.slug);
                            return (
                                <div
                                    key={tool.id}
                                    onClick={() => router.visit(route('tools.show', tool.slug))}
                                    className="group relative bg-white border border-slate-200/80 rounded-2xl p-6 cursor-pointer hover:shadow-lg hover:border-slate-300 hover:-translate-y-0.5 transition-all duration-200"
                                >
                                    {/* Featured ribbon */}
                                    {tool.is_featured && (
                                        <div className="absolute top-3 right-3">
                                            <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100 text-xs">Featured</Badge>
                                        </div>
                                    )}
                                    {isSubscribed && (
                                        <div className="absolute top-3 left-3">
                                            <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100 text-xs">Active</Badge>
                                        </div>
                                    )}

                                    {/* Icon */}
                                    <div className={`w-12 h-12 rounded-xl flex items-center justify-center mb-4 bg-slate-100 group-hover:bg-slate-900 transition-colors duration-200`}>
                                        {tool.icon_url
                                            ? <img src={tool.icon_url} alt={tool.title} className="w-8 h-8 object-contain" />
                                            : <Icon className="h-6 w-6 text-slate-500 group-hover:text-white transition-colors" />
                                        }
                                    </div>

                                    <h3 className="font-semibold text-slate-900 mb-1 text-sm leading-tight">{tool.title}</h3>
                                    <p className="text-xs text-slate-500 mb-4 line-clamp-2 leading-relaxed">{tool.short_description}</p>

                                    {/* Footer */}
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs text-slate-400 font-medium">
                                            {tool.is_free
                                                ? 'Free'
                                                : `From $${tool.starting_price}/mo`
                                            }
                                        </span>
                                        <div className="flex items-center gap-1 text-xs text-slate-400">
                                            <Download className="h-3 w-3" />
                                            {tool.download_count.toLocaleString()}
                                        </div>
                                    </div>

                                    {/* OS badges */}
                                    <div className="flex gap-1 mt-3">
                                        {(Array.isArray(tool.supported_os)
                                            ? tool.supported_os
                                            : typeof tool.supported_os === 'string'
                                                ? JSON.parse(tool.supported_os)
                                                : []
                                        ).map((os: string) => (
                                            <span key={os} className="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded capitalize font-medium">
                                                {os}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Pagination */}
                {tools.links.length > 3 && (
                    <div className="flex justify-center gap-1 pt-4">
                        {tools.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url || link.active}
                                onClick={() => link.url && router.visit(link.url)}
                                className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
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
