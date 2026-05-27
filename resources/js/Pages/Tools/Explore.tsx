import React, { useState, useEffect } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { DesktopIcon } from '@/Components/Tools/DesktopIcon';
import { WindowModal } from '@/Components/Tools/WindowModal';
import { CheckCircle2, Shield, Play } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';

const WALLPAPER_URL = 'https://images.unsplash.com/photo-1506744626753-143d63428987?q=80&w=2560&auto=format&fit=crop';

interface PricingPlan {
    id: string;
    name: string;
    price_monthly: number;
    price_yearly: number;
    features: string[];
    is_popular: boolean;
    yearly_savings: number;
}

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
    pricing_plans: PricingPlan[];
}

interface Props {
    tools: { data: Tool[]; links: any[] };
    categories: Record<string, string>;
    subscribedSlugs: string[];
    hasBrowserSubscription: boolean;
    filters: { search?: string; category?: string };
}

export default function Explore({ tools, categories, subscribedSlugs, hasBrowserSubscription, filters }: Props) {
    const [currentTime, setCurrentTime] = useState(new Date());
    const [selectedTool, setSelectedTool] = useState<Tool | null>(null);
    const [isSubscribeModalOpen, setSubscribeModalOpen] = useState(false);

    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    const handleToolClick = (tool: Tool) => {
        const isOwned = subscribedSlugs.includes(tool.slug);
        if (isOwned) {
            router.visit(route('tools.run', tool.slug));
        } else {
            setSelectedTool(tool);
            setSubscribeModalOpen(true);
        }
    };

    const handleSubscribeAction = () => {
        if (!selectedTool) return;
        const plan = selectedTool.pricing_plans[0];
        if (plan) {
            router.visit(route('tools.checkout', { slug: selectedTool.slug, planId: plan.id }));
        } else {
            router.visit(route('tools.show', selectedTool.slug));
        }
    };

    const formatTime = (date: Date) => {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    const formatDate = (date: Date) => {
        return date.toLocaleDateString([], { month: 'numeric', day: 'numeric', year: 'numeric' });
    };

    return (
        <div 
            className="h-screen w-screen overflow-hidden bg-slate-900 bg-cover bg-center flex flex-col font-['Inter',sans-serif]"
            style={{ backgroundImage: `url(${WALLPAPER_URL})` }}
        >
            <Head title="Tools Workspace" />

            {/* Desktop Area */}
            <div className="flex-1 p-4 md:p-6 overflow-hidden flex flex-col flex-wrap gap-4 content-start">
                {tools.data.map((tool) => (
                    <DesktopIcon
                        key={tool.id}
                        title={tool.title}
                        iconUrl={tool.icon_url}
                        isOwned={subscribedSlugs.includes(tool.slug)}
                        isFeatured={tool.is_featured}
                        onClick={() => handleToolClick(tool)}
                    />
                ))}
            </div>

            {/* Taskbar */}
            <div className="h-12 bg-[#1c1c1c]/90 backdrop-blur-md border-t border-white/10 flex items-center justify-between px-2 shrink-0 select-none z-40">
                <div className="flex items-center h-full">
                    {/* Start Button */}
                    <Link 
                        href="/" 
                        className="h-full px-3 flex items-center justify-center hover:bg-white/10 transition-colors group"
                    >
                        <div className="w-6 h-6 rounded bg-blue-500 flex items-center justify-center group-hover:bg-blue-400 transition-colors">
                            <ApplicationLogo className="w-3.5 h-3.5 text-white fill-current" />
                        </div>
                    </Link>
                    
                    {/* Running Apps (Mocked as shortcuts to Dashboard/Downloads) */}
                    <div className="flex items-center h-full ml-2 space-x-1">
                        <Link href={route('dashboard')} className="h-10 px-3 flex items-center justify-center text-slate-300 hover:bg-white/10 rounded transition-colors text-xs font-medium">
                            Dashboard
                        </Link>
                        <Link href={route('tools.downloads')} className="h-10 px-3 flex items-center justify-center text-slate-300 hover:bg-white/10 rounded transition-colors text-xs font-medium">
                            Downloads
                        </Link>
                    </div>
                </div>

                <div className="flex items-center h-full text-white text-xs">
                    <div className="flex flex-col items-end justify-center px-3 hover:bg-white/10 h-full transition-colors cursor-default">
                        <span>{formatTime(currentTime)}</span>
                        <span>{formatDate(currentTime)}</span>
                    </div>
                </div>
            </div>

            {/* Subscribe Modal / App Window */}
            <WindowModal
                isOpen={isSubscribeModalOpen}
                onClose={() => {
                    setSubscribeModalOpen(false);
                    setSelectedTool(null);
                }}
                title={selectedTool ? `Subscribe to ${selectedTool.title}` : 'Subscribe'}
                icon={
                    selectedTool?.icon_url ? 
                    <img src={selectedTool.icon_url} alt="" className="w-full h-full object-contain" /> : 
                    <span className="text-[10px]">📦</span>
                }
                width="w-[500px]"
            >
                {selectedTool && (
                    <div className="flex flex-col h-full bg-slate-50">
                        {/* Header Info */}
                        <div className="p-6 bg-white border-b border-slate-200 flex gap-4 items-start">
                            <div className="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0 border border-slate-200">
                                {selectedTool.icon_url ? (
                                    <img src={selectedTool.icon_url} alt={selectedTool.title} className="w-10 h-10 object-contain" />
                                ) : (
                                    <span className="text-3xl">📦</span>
                                )}
                            </div>
                            <div>
                                <h2 className="text-xl font-bold text-slate-900">{selectedTool.title}</h2>
                                <p className="text-sm text-slate-500 mt-1">{selectedTool.short_description}</p>
                                <div className="mt-2 flex items-center gap-2">
                                    <span className="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-xs font-medium capitalize">
                                        {selectedTool.category_label}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Plan Info */}
                        <div className="p-6 space-y-4 flex-1">
                            {selectedTool.pricing_plans && selectedTool.pricing_plans.length > 0 ? (
                                <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                    <div className="bg-slate-900 px-4 py-3 flex items-center justify-between">
                                        <span className="text-white font-semibold">{selectedTool.pricing_plans[0].name} Plan</span>
                                        <span className="text-white text-lg font-bold">
                                            {selectedTool.pricing_plans[0].price_monthly <= 0 
                                                ? 'Free' 
                                                : `$${selectedTool.pricing_plans[0].price_monthly}/mo`}
                                        </span>
                                    </div>
                                    <div className="p-4">
                                        <ul className="space-y-2">
                                            {selectedTool.pricing_plans[0].features.slice(0, 4).map((f, i) => (
                                                <li key={i} className="flex items-start gap-2 text-sm text-slate-600">
                                                    <CheckCircle2 className="h-4 w-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                                                    {f}
                                                </li>
                                            ))}
                                            {selectedTool.pricing_plans[0].features.length > 4 && (
                                                <li className="text-xs text-slate-400 pl-6">
                                                    + {selectedTool.pricing_plans[0].features.length - 4} more features
                                                </li>
                                            )}
                                        </ul>
                                    </div>
                                </div>
                            ) : (
                                <div className="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                                    <span className="font-semibold text-slate-900">Starting at</span>
                                    <span className="text-lg font-bold text-slate-900">
                                        {selectedTool.starting_price <= 0 ? 'Free' : `$${selectedTool.starting_price}/mo`}
                                    </span>
                                </div>
                            )}
                            
                            <div className="pt-2">
                                <Button 
                                    onClick={handleSubscribeAction}
                                    className="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white"
                                >
                                    Proceed to Subscribe
                                </Button>
                            </div>

                            <p className="text-xs text-slate-400 text-center flex items-center justify-center gap-1 mt-2">
                                <Shield className="h-3 w-3" />
                                Safe & Secure Checkout
                            </p>
                        </div>
                    </div>
                )}
            </WindowModal>
        </div>
    );
}
