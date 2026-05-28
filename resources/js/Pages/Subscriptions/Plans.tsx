import React, { useState, useMemo, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import {
    Check, Wallet, CreditCard, ArrowRight, ShieldCheck,
    Sparkles, Building2, Wrench, MessageSquare, Zap,
    Crown, Layers, ArrowLeft,
    Globe, Link as LinkIcon, Calendar, Users, Repeat,
    BarChart, List, Star, Code, Settings, Headset,
    Send, GitMerge, Database, Mail, Filter, Webhook,
    Download, Brain, Inbox, History, Shield,
    Package, MonitorSmartphone, Calculator, Banknote,
    ShoppingCart, Truck, Warehouse, CheckSquare, Coins,
    Receipt, Files, ScanLine, Clock, Bell, UserCircle,
    Laptop, Smartphone, Lightbulb,
    TrendingUp, Activity, Target, FileText, Trophy, 
    UserPlus, Store, LineChart, Rss, WifiOff, Umbrella, 
    RefreshCw, PieChart
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface ServiceItem {
    id: string;
    slug: string;
    tool_id?: number;
    parent_id?: string;
    name: string;
    type: 'module' | 'tool' | 'addon';
    description: string | null;
    monthly_price: number;
    yearly_price: number;
    icon: string | null;
}

interface ActiveSub {
    id: number;
    plan_id: number;
    plan_slug: string | null;
    plan_name: string;
    status: string;
    billing_cycle: string;
    amount: number;
    expires_at: string;
    auto_renew: boolean;
    owned_features: {
        id: string;
        status: 'active' | 'expired';
        expires_at: string;
    }[] | null;
}

interface PlansProps {
    serviceItems: ServiceItem[];
    activeSubscription: ActiveSub | null;
    walletBalance: number;
    currency: string;
}

const ICON_MAP: Record<string, React.ElementType> = {
    Building2, MessageSquare, Zap, Sparkles, Check, Wrench,
    Globe, Link: LinkIcon, Calendar, Users, Repeat,
    BarChart, List, Star, Code, Settings, Headset,
    Send, GitMerge, Database, Mail, Filter, Webhook,
    Download, Brain, Inbox, History, Shield,
    Package, MonitorSmartphone, Calculator, Banknote,
    ShoppingCart, Truck, Warehouse, CheckSquare, Coins,
    Receipt, Files, ScanLine, Clock, Bell, UserCircle,
    Laptop, Smartphone, Lightbulb,
    TrendingUp, Activity, Target, FileText, Trophy, 
    UserPlus, Store, LineChart, Rss, WifiOff, Umbrella, 
    RefreshCw, PieChart
};

export default function Plans({ serviceItems, activeSubscription, walletBalance, currency }: PlansProps) {
    const [billing, setBilling] = useState<'1_month' | '6_months' | '1_year'>('1_month');
    const [isNewSystem, setIsNewSystem] = useState<boolean>(false);
    
    const activeItems = useMemo(() => {
        if (isNewSystem) return [];
        return activeSubscription?.owned_features
            ?.filter(f => f.status === 'active')
            .map(f => f.id) || [];
    }, [activeSubscription, isNewSystem]);
    
    // Update selected items automatically when switching modes
    useEffect(() => {
        setSelectedItems(activeItems);
    }, [isNewSystem]);

    const [selectedItems, setSelectedItems] = useState<string[]>(activeItems);

    const modules = serviceItems.filter(item => item.type === 'module');
    const tools = serviceItems.filter(item => item.type === 'tool');
    const addons = serviceItems.filter(item => item.type === 'addon');

    const toggleItem = (id: string) => {
        const item = serviceItems.find(i => i.id === id);
        
        if (item?.type === 'module') {
            setSelectedItems(prev => {
                if (prev.includes(id)) {
                    // Deselect module AND all its nested addons
                    return prev.filter(i => i !== id && serviceItems.find(si => si.id === i)?.parent_id !== id);
                }
                return [...prev, id];
            });
        } else {
            setSelectedItems(prev =>
                prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
            );
        }
    };

    const handleSelectAllTools = () => {
        const toolIds = tools.map(t => t.id);
        if (toolIds.length === 0) return;
        
        const allSelected = toolIds.every(id => selectedItems.includes(id));
        
        if (allSelected) {
            // Deselect all tools
            setSelectedItems(prev => prev.filter(id => !toolIds.includes(id)));
        } else {
            // Select all tools
            setSelectedItems(prev => {
                const nonTools = prev.filter(id => !toolIds.includes(id));
                return [...nonTools, ...toolIds];
            });
        }
    };

    const multiplier = useMemo(() => {
        if (billing === '1_month') return 1;
        if (billing === '6_months') return 6;
        if (billing === '1_year') return 10; // 2 months free
        return 1;
    }, [billing]);

    const months = useMemo(() => {
        if (billing === '1_month') return 1;
        if (billing === '6_months') return 6;
        if (billing === '1_year') return 12;
        return 1;
    }, [billing]);

    const calculateItemPrice = (item: ServiceItem) => {
        return item.monthly_price * months;
    };

    const subtotal = useMemo(() => {
        let baseSubtotal = 0;
        let toolsCount = 0;

        selectedItems.forEach((id) => {
            const item = serviceItems.find((i) => i.id === id);
            if (item) {
                if (item.type === 'tool') {
                    toolsCount++;
                } else {
                    baseSubtotal += item.monthly_price;
                }
            }
        });

        let toolsSubtotal = 0;
        if (toolsCount > 0) {
            const firstToolId = selectedItems.find(id => serviceItems.find(i => i.id === id)?.type === 'tool');
            const toolBaseMonthly = serviceItems.find(i => i.id === firstToolId)?.monthly_price || 0;
            const discountPercent = Math.min(50, (toolsCount - 1) * 10);
            toolsSubtotal = (toolBaseMonthly * toolsCount) * (1 - (discountPercent / 100));
        }

        return baseSubtotal + toolsSubtotal;
    }, [selectedItems, serviceItems]);

    // Calculate how much discount they got from tools volume
    const toolsDiscount = useMemo(() => {
        let toolsCount = 0;
        let firstToolId: string | null = null;
        
        selectedItems.forEach((id) => {
            const item = serviceItems.find((i) => i.id === id);
            if (item && item.type === 'tool') {
                toolsCount++;
                if (!firstToolId) firstToolId = id;
            }
        });

        if (toolsCount <= 1) return 0;
        
        const toolBaseMonthly = serviceItems.find(i => i.id === firstToolId)?.monthly_price || 0;
        const discountPercent = Math.min(50, (toolsCount - 1) * 10);
        const originalToolsPrice = toolBaseMonthly * toolsCount;
        const discountedToolsPrice = originalToolsPrice * (1 - (discountPercent / 100));
        
        return (originalToolsPrice - discountedToolsPrice) * months;
    }, [selectedItems, serviceItems, months]);

    const total = subtotal * multiplier;
    
    // Original total without discount
    const originalTotal = subtotal * months;
    const discount = originalTotal - total;

    const handleSubscribeWallet = () => {
        if (selectedItems.length === 0) return;
        const msg = isNewSystem 
            ? `Create a NEW workspace with these ${selectedItems.length} items?`
            : `Subscribe to these ${selectedItems.length} items using your wallet balance?`;
        if (confirm(msg)) {
            router.post(route('subscriptions.subscribe'), { items: selectedItems, billing_cycle: billing, is_new_system: isNewSystem });
        }
    };

    const handleSubscribeKashier = () => {
        if (selectedItems.length === 0) return;
        router.post(route('subscriptions.kashier.checkout'), { items: selectedItems, billing_cycle: billing, is_new_system: isNewSystem });
    };

    const canAfford = walletBalance >= total;

    const renderItemCard = (item: ServiceItem, isAddon: boolean = false) => {
        const isSelected = selectedItems.includes(item.id);
        const Icon = item.icon && ICON_MAP[item.icon] ? ICON_MAP[item.icon] : Layers;
        const ownedFeature = isNewSystem ? undefined : activeSubscription?.owned_features?.find(f => f.id === item.id);

        return (
            <div
                key={item.id}
                onClick={() => toggleItem(item.id)}
                className={cn(
                    'relative flex items-start gap-4 rounded-2xl border transition-all duration-300 cursor-pointer group',
                    isAddon ? 'p-4 bg-white/50 hover:bg-white' : 'p-5 bg-white hover:shadow-sm',
                    isSelected
                        ? (isAddon ? 'border-indigo-400 bg-indigo-50/50' : 'border-indigo-600 bg-indigo-50/50 shadow-md shadow-indigo-100/50')
                        : 'border-slate-200 hover:border-indigo-300'
                )}
            >
                <div className={cn(
                    'flex items-center justify-center rounded-md border mt-0.5 shrink-0 transition-colors',
                    isAddon ? 'w-5 h-5' : 'w-6 h-6',
                    isSelected ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-50 border-slate-300 text-transparent group-hover:border-indigo-400'
                )}>
                    <Check className={isAddon ? "w-3 h-3" : "w-4 h-4"} />
                </div>
                
                <div className="flex-1">
                    <div className="flex justify-between items-start">
                        <div>
                            <div className="flex items-center gap-2">
                                <Icon className={cn("w-4 h-4", isSelected ? 'text-indigo-600' : 'text-slate-400')} />
                                <h3 className={cn("font-semibold", isSelected ? 'text-indigo-900' : 'text-slate-900')}>
                                    {item.name}
                                </h3>
                                {ownedFeature && (
                                    <span className={cn(
                                        "ml-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full",
                                        ownedFeature.status === 'active' 
                                            ? "bg-emerald-100 text-emerald-700" 
                                            : "bg-red-100 text-red-700"
                                    )}>
                                        {ownedFeature.status === 'active' ? 'Active' : 'Expired'}
                                    </span>
                                )}
                            </div>
                            {item.description && (
                                <p className="text-sm text-slate-500 mt-1 leading-relaxed">
                                    {item.description}
                                </p>
                            )}
                            {ownedFeature && (
                                <p className={cn(
                                    "text-xs mt-1.5 font-medium",
                                    ownedFeature.status === 'active' ? "text-emerald-600" : "text-red-500"
                                )}>
                                    {ownedFeature.status === 'active' ? 'Renews / Expires on' : 'Expired on'} {ownedFeature.expires_at}
                                    {ownedFeature.status === 'expired' && " - Select to Renew"}
                                </p>
                            )}
                            {item.type === 'tool' && (
                                <a 
                                    href={route('tools.show', item.slug)}
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    className="text-xs font-medium text-indigo-600 hover:text-indigo-700 hover:underline mt-1.5 inline-block"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    View Details
                                </a>
                            )}
                        </div>
                        <div className="text-right">
                            <span className="text-lg font-bold text-slate-900">
                                {calculateItemPrice(item).toFixed(2)}
                            </span>
                            <span className="text-xs text-slate-500 ml-1">{currency}</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Build Your Plan" />

            {/* ── Hero ── */}
            <div className="max-w-7xl mx-auto px-4 pt-10 pb-6">
                <Link href={route('subscriptions.manage')} className="inline-flex items-center text-sm text-slate-500 hover:text-slate-800 mb-6 transition-colors">
                    <ArrowLeft className="w-4 h-4 mr-1.5" /> Back to My Subscriptions
                </Link>
                <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <div className="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
                            <Crown className="h-3.5 w-3.5" /> Fully Genius System
                        </div>
                        <h1 className="text-3xl md:text-4xl font-bold tracking-tight text-slate-900">
                            Build Your Perfect Plan
                        </h1>
                        <p className="mt-2 text-lg text-slate-500 font-light">
                            Select exactly what you need. No more, no less.
                        </p>
                    </div>
                </div>
            </div>

            {/* ── Active Subscription Banner ── */}
            {activeSubscription && (
                <div className="max-w-7xl mx-auto px-4 mb-6">
                    <div className="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-2xl px-6 py-3.5">
                        <div className="flex items-center gap-2.5 text-sm text-emerald-800">
                            <ShieldCheck className="h-4 w-4 text-emerald-500" />
                            <span>
                                You have an active subscription: <strong>{activeSubscription.plan_name}</strong>
                                {activeSubscription.expires_at && ` · renews ${activeSubscription.expires_at}`}
                            </span>
                        </div>
                    </div>
                </div>
            )}

            <div className="max-w-7xl mx-auto px-4 pb-20">
                <PricingBuilder 
                    serviceItems={serviceItems} 
                    currency={currency} 
                    activeSubscription={activeSubscription}
                    isNewSystem={isNewSystem}
                    onSystemTypeChange={setIsNewSystem}
                    renderActions={({ selectedItems, billing, total }) => {
                        const canAfford = walletBalance >= total;
                        
                        const handleSubscribeWallet = () => {
                            if (selectedItems.length === 0) return;
                            const msg = isNewSystem 
                                ? `Create a NEW workspace with these ${selectedItems.length} items?`
                                : `Subscribe to these ${selectedItems.length} items using your wallet balance?`;
                            if (confirm(msg)) {
                                router.post(route('subscriptions.subscribe'), { items: selectedItems, billing_cycle: billing, is_new_system: isNewSystem });
                            }
                        };
                    
                        const handleSubscribeKashier = () => {
                            if (selectedItems.length === 0) return;
                            router.post(route('subscriptions.kashier.checkout'), { items: selectedItems, billing_cycle: billing, is_new_system: isNewSystem });
                        };

                        return (
                            <>
                                <Button
                                    onClick={handleSubscribeWallet}
                                    disabled={selectedItems.length === 0 || !canAfford}
                                    className={cn(
                                        'w-full h-12 rounded-xl text-sm font-medium gap-2 transition-all',
                                        canAfford && selectedItems.length > 0
                                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                    )}
                                >
                                    <Wallet className="h-4 w-4" />
                                    {canAfford || selectedItems.length === 0 ? 'Subscribe with Wallet' : `Need ${currency} ${total.toFixed(2)}`}
                                </Button>

                                <Button
                                    onClick={handleSubscribeKashier}
                                    disabled={selectedItems.length === 0}
                                    variant="outline"
                                    className="w-full h-12 rounded-xl text-sm font-medium gap-2 border-slate-200 hover:bg-slate-50 text-slate-700"
                                >
                                    <CreditCard className="h-4 w-4 text-slate-400" />
                                    Pay by Card
                                </Button>

                                {!canAfford && selectedItems.length > 0 && (
                                    <Link
                                        href={route('financial.add-balance')}
                                        className="flex items-center justify-center gap-1 text-xs text-slate-500 hover:text-slate-700 pt-2"
                                    >
                                        Add funds to wallet <ArrowRight className="h-3 w-3" />
                                    </Link>
                                )}
                            </>
                        );
                    }}
                />
                
                <div className="mt-6 flex items-center justify-center gap-2 text-sm text-slate-400">
                    <Wallet className="h-3.5 w-3.5" />
                    <span>Wallet balance: <strong className="text-slate-600">{walletBalance} {currency}</strong></span>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
