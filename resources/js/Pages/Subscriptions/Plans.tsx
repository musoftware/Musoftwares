import React, { useState, useMemo } from 'react';
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
    custom_items: string[] | null;
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
    const [selectedItems, setSelectedItems] = useState<string[]>([]);

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

    const multiplier = useMemo(() => {
        if (billing === '1_month') return 1;
        if (billing === '6_months') return 6;
        if (billing === '1_year') return 10; // 2 months free
        return 1;
    }, [billing]);

    const calculateItemPrice = (item: ServiceItem) => {
        return item.monthly_price * multiplier;
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
        
        return (originalToolsPrice - discountedToolsPrice) * multiplier;
    }, [selectedItems, serviceItems, multiplier]);

    const total = subtotal * multiplier;
    
    // Original total without discount (if 1 year selected, it would be * 12)
    const originalTotal = subtotal * (billing === '1_year' ? 12 : (billing === '6_months' ? 6 : 1));
    const discount = originalTotal - total;

    const handleSubscribeWallet = () => {
        if (selectedItems.length === 0) return;
        if (confirm(`Subscribe to these ${selectedItems.length} items using your wallet balance?`)) {
            router.post(route('subscriptions.subscribe'), { items: selectedItems, billing_cycle: billing });
        }
    };

    const handleSubscribeKashier = () => {
        if (selectedItems.length === 0) return;
        router.post(route('subscriptions.kashier.checkout'), { items: selectedItems, billing_cycle: billing });
    };

    const canAfford = walletBalance >= total;

    const renderItemCard = (item: ServiceItem, isAddon: boolean = false) => {
        const isSelected = selectedItems.includes(item.id);
        const Icon = item.icon && ICON_MAP[item.icon] ? ICON_MAP[item.icon] : Layers;

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
                            </div>
                            {item.description && (
                                <p className="text-sm text-slate-500 mt-1 leading-relaxed">
                                    {item.description}
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

                    {/* ── Billing Toggle ── */}
                    <div className="inline-flex items-center bg-slate-100 p-1 rounded-xl text-sm text-slate-500 shrink-0">
                        {[
                            { id: '1_month', label: '1 Month' },
                            { id: '6_months', label: '6 Months' },
                            { id: '1_year', label: '1 Year (Save 16%)' },
                        ].map(option => (
                            <button
                                key={option.id}
                                onClick={() => setBilling(option.id as any)}
                                className={cn(
                                    'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                                    billing === option.id
                                        ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                        : 'hover:text-slate-700 hover:bg-slate-200/50'
                                )}
                            >
                                {option.label}
                            </button>
                        ))}
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
                <div className="flex flex-col lg:flex-row gap-8 items-start">
                    
                    {/* ── Products List (Left) ── */}
                    <div className="flex-1 w-full space-y-10">
                        
                        <section>
                            <div className="mb-4">
                                <h2 className="text-xl font-semibold text-slate-900">Core Modules</h2>
                                <p className="text-sm text-slate-500">The foundation for your business operations.</p>
                            </div>
                            <div className="grid grid-cols-1 gap-6">
                                {modules.map(module => {
                                    const moduleAddons = addons.filter(a => a.parent_id === module.id);
                                    const isModuleSelected = selectedItems.includes(module.id);
                                    return (
                                        <div key={module.id} className="flex flex-col">
                                            {renderItemCard(module)}
                                            
                                            {/* Add-ons Section */}
                                            {moduleAddons.length > 0 && isModuleSelected && (
                                                <div className="mt-4 pl-4 md:pl-8 border-l-[3px] border-indigo-100 ml-4 md:ml-6 pb-2 animate-in slide-in-from-top-4 fade-in duration-300">
                                                    <h4 className="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2 tracking-tight">
                                                        <Sparkles className="w-4 h-4 text-indigo-500" /> 
                                                        Power up {module.name}
                                                    </h4>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        {moduleAddons.map(addon => renderItemCard(addon, true))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </section>

                        <div className="border-t border-slate-100" />

                        <section>
                            <div className="mb-4">
                                <h2 className="text-xl font-semibold text-slate-900">Automation Tools</h2>
                                <p className="text-sm text-slate-500">Standalone tools to boost your productivity.</p>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {tools.map(renderItemCard)}
                            </div>
                        </section>

                    </div>

                    {/* ── Sticky Summary Cart (Right) ── */}
                    <div className="w-full lg:w-[380px] shrink-0 sticky top-24">
                        <div className="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                            <div className="p-6 bg-slate-50/50 border-b border-slate-100">
                                <h3 className="text-lg font-semibold text-slate-900">Your Plan Summary</h3>
                                <p className="text-sm text-slate-500">
                                    Billed {billing.replace('_', ' ')}
                                </p>
                            </div>
                            
                            <div className="p-6">
                                {selectedItems.length === 0 ? (
                                    <div className="text-center py-8">
                                        <div className="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <Layers className="w-6 h-6 text-slate-300" />
                                        </div>
                                        <p className="text-slate-500 text-sm">Select products to build your plan.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {selectedItems.map(id => {
                                            const item = serviceItems.find(i => i.id === id);
                                            if (!item) return null;
                                            
                                            // Group Addons visually under their parents in the cart
                                            const isAddon = item.type === 'addon';
                                            
                                            return (
                                                <div key={id} className={cn("flex justify-between text-sm", isAddon ? "pl-4 text-slate-500" : "text-slate-700 font-medium")}>
                                                    <span className="flex items-center gap-1.5">
                                                        {isAddon && <span className="text-slate-300">↳</span>}
                                                        {item.name}
                                                    </span>
                                                    <span className={cn(isAddon ? "text-slate-500" : "text-slate-900")}>
                                                        {calculateItemPrice(item).toFixed(2)}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                        
                                        <div className="border-t border-slate-100 pt-4 mt-4" />
                                        
                                        {toolsDiscount > 0 && (
                                            <div className="flex justify-between text-sm text-indigo-600 font-medium">
                                                <span>Tools Volume Discount</span>
                                                <span>-{toolsDiscount.toFixed(2)}</span>
                                            </div>
                                        )}

                                        {discount > 0 && (
                                            <div className="flex justify-between text-sm text-emerald-600 font-medium">
                                                <span>Discount (Annual)</span>
                                                <span>-{discount.toFixed(2)}</span>
                                            </div>
                                        )}

                                        <div className="flex justify-between items-end">
                                            <span className="text-base font-medium text-slate-900">Total</span>
                                            <div className="text-right">
                                                <span className="text-3xl font-bold tracking-tight text-indigo-600">
                                                    {total.toFixed(2)}
                                                </span>
                                                <span className="text-sm text-slate-400 ml-1">{currency}</span>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="p-6 pt-0 space-y-3">
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
                            </div>
                        </div>

                        <div className="mt-6 flex items-center justify-center gap-2 text-sm text-slate-400">
                            <Wallet className="h-3.5 w-3.5" />
                            <span>Wallet balance: <strong className="text-slate-600">{walletBalance} {currency}</strong></span>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
