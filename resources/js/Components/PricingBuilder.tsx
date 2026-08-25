import React, { useState, useMemo, useEffect } from 'react';
import axios from 'axios';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import {
    Check, Layers, Crown, Sparkles, Building2, MessageSquare, Zap, Store, Wrench,
    Globe, Link as LinkIcon, Calendar, Users, Repeat,
    BarChart, List, Star, Code, Settings, Headset,
    Send, GitMerge, Database, Mail, Filter, Webhook,
    Download, Brain, Inbox, History, Shield,
    Package, MonitorSmartphone, Calculator, Banknote,
    ShoppingCart, Truck, Warehouse, CheckSquare, Coins,
    Receipt, Files, ScanLine, Clock, Bell, UserCircle,
    Laptop, Smartphone, Lightbulb,
    TrendingUp, Activity, Target, FileText, Trophy,
    UserPlus, LineChart, Rss, WifiOff, Umbrella,
    RefreshCw, PieChart, ChevronDown, ChevronUp, Info
} from 'lucide-react';

const ICON_MAP: Record<string, React.ElementType> = {
    Building2, MessageSquare, Zap, Sparkles, Check, Store, Layers, Wrench,
    Globe, Link: LinkIcon, Calendar, Users, Repeat,
    BarChart, List, Star, Code, Settings, Headset,
    Send, GitMerge, Database, Mail, Filter, Webhook,
    Download, Brain, Inbox, History, Shield,
    Package, MonitorSmartphone, Calculator, Banknote,
    ShoppingCart, Truck, Warehouse, CheckSquare, Coins,
    Receipt, Files, ScanLine, Clock, Bell, UserCircle,
    Laptop, Smartphone, Lightbulb,
    TrendingUp, Activity, Target, FileText, Trophy,
    UserPlus, LineChart, Rss, WifiOff, Umbrella,
    RefreshCw, PieChart
};

export interface ServiceItem {
    id: string;
    slug: string;
    name: string;
    type: 'module' | 'tool' | 'addon';
    parent_id?: string;
    description: string | null;
    monthly_price: number;
    yearly_price: number;
    icon: string | null;
}

export interface ActiveSub {
    id: number;
    plan_id?: number;
    plan_slug?: string | null;
    plan_name?: string;
    status: string;
    billing_cycle?: string;
    amount?: number;
    expires_at: string;
    auto_renew?: boolean;
    owned_features: {
        id: string;
        status: 'active' | 'expired';
        expires_at: string;
    }[] | null;
}

export interface PricingBuilderProps {
    serviceItems: ServiceItem[];
    currency: string;
    activeSubscription?: ActiveSub | null;
    isNewSystem?: boolean;
    onSystemTypeChange?: (isNew: boolean) => void;
    renderActions?: (state: {
        selectedItems: string[];
        billing: '1_month' | '6_months' | '1_year';
        total: number;
    }) => React.ReactNode;
    proratedRefund?: number;
    targetModule?: string | null;
    targetTool?: string | null;
    targetPlan?: string | null;
}

export default function PricingBuilder({
    serviceItems = [],
    currency,
    activeSubscription,
    isNewSystem = true,
    onSystemTypeChange,
    renderActions,
    proratedRefund = 0,
    targetModule = null,
    targetTool = null,
    targetPlan = null
}: PricingBuilderProps) {
    const [billing, setBilling] = useState<'1_month' | '6_months' | '1_year'>('1_month');
    const [isCartExpanded, setIsCartExpanded] = useState(false);

    // Default selected items (ERP & CRM) plus any module/tool from params
    const activeItems = useMemo(() => {
        const items: string[] = [];
        if (isNewSystem) {
            items.push(...serviceItems.filter(i => i.id === 'erp' || i.id === 'crm').map(i => i.id));
        }

        let mod = targetModule;
        let tool = targetTool;

        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            if (!mod) mod = params.get('module');
            if (!tool) tool = params.get('tool');
        }

        if (mod && !items.includes(mod)) {
            const ownsMod = !isNewSystem && activeSubscription?.owned_features?.find(f => f.id === mod && f.status === 'active');
            if (!ownsMod) {
                items.push(mod);
            }
        }

        if (tool) {
            const matchingItem = serviceItems.find(i => i.id === tool || i.slug === tool || i.id === 'tool-' + tool);
            if (matchingItem && !items.includes(matchingItem.id)) {
                items.push(matchingItem.id);
            }
        }
        return items;
    }, [isNewSystem, serviceItems, activeSubscription, targetModule, targetTool]);

    const [selectedItems, setSelectedItems] = useState<string[]>(activeItems);

    useEffect(() => {
        setSelectedItems(activeItems);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isNewSystem]);

    const modules = useMemo(() => serviceItems.filter(item => item.type === 'module'), [serviceItems]);
    const tools = useMemo(() => serviceItems.filter(item => item.type === 'tool'), [serviceItems]);
    const addons = useMemo(() => serviceItems.filter(item => item.type === 'addon'), [serviceItems]);

    const toggleItem = (id: string) => {
        const item = serviceItems.find(i => i.id === id);

        if (item?.type === 'module') {
            setSelectedItems(prev => {
                if (prev.includes(id)) {
                    // Deselect module AND all its child addons
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
            setSelectedItems(prev => prev.filter(id => !toolIds.includes(id)));
        } else {
            setSelectedItems(prev => {
                const nonTools = prev.filter(id => !toolIds.includes(id));
                return [...nonTools, ...toolIds];
            });
        }
    };

    const handleSelectAllAddons = (moduleId: string) => {
        const moduleAddonIds = addons.filter(a => a.parent_id === moduleId).map(a => a.id);
        if (moduleAddonIds.length === 0) return;

        const allSelected = moduleAddonIds.every(id => selectedItems.includes(id));
        if (allSelected) {
            setSelectedItems(prev => prev.filter(id => !moduleAddonIds.includes(id)));
        } else {
            setSelectedItems(prev => {
                const nonAddons = prev.filter(id => !moduleAddonIds.includes(id));
                return [...nonAddons, ...moduleAddonIds];
            });
        }
    };

    const months = useMemo(() => {
        if (billing === '1_month') return 1;
        if (billing === '6_months') return 6;
        if (billing === '1_year') return 12;
        return 1;
    }, [billing]);

    const calculateItemPrice = (item: ServiceItem) => {
        return item.monthly_price * months;
    };

    const [calcResult, setCalcResult] = useState<{ toolsDiscount: number; annualDiscount: number; total: number } | null>(null);
    const [isCalculating, setIsCalculating] = useState(false);

    useEffect(() => {
        let isMounted = true;
        const fetchCalc = async () => {
            if (selectedItems.length === 0) {
                if (isMounted) setCalcResult(null);
                return;
            }
            setIsCalculating(true);
            try {
                const res = await axios.post('/subscriptions/calculate-custom', {
                    items: selectedItems,
                    billing_cycle: billing
                });
                if (isMounted) {
                    setCalcResult(res.data);
                }
            } catch (e) {
                console.error("Pricing calculation error:", e);
            } finally {
                if (isMounted) setIsCalculating(false);
            }
        };
        const timer = setTimeout(fetchCalc, 250);
        return () => {
            isMounted = false;
            clearTimeout(timer);
        };
    }, [selectedItems, billing]);

    const toolsDiscount = calcResult?.toolsDiscount || 0;
    const discount = calcResult?.annualDiscount || 0;
    const total = calcResult?.total || 0;

    const renderItemCard = (item: ServiceItem, isAddon: boolean = false) => {
        const isSelected = selectedItems.includes(item.id);
        const Icon = item.icon && ICON_MAP[item.icon] ? ICON_MAP[item.icon] : Layers;
        const ownedFeature = isNewSystem ? undefined : activeSubscription?.owned_features?.find(f => f.id === item.id);

        if (isAddon) {
            return (
                <div
                    key={item.id}
                    onClick={() => toggleItem(item.id)}
                    className={cn(
                        'relative flex items-start gap-3 p-3.5 rounded-xl border transition-all duration-200 cursor-pointer text-start select-none',
                        isSelected
                            ? 'bg-indigo-50/70 border-indigo-400/80 shadow-xs ring-1 ring-indigo-500/20 dark:bg-indigo-950/40 dark:border-indigo-500/60'
                            : 'bg-white border-slate-200/80 hover:border-slate-300 hover:bg-slate-50/50 shadow-2xs dark:bg-zinc-900 dark:border-zinc-800 dark:hover:border-zinc-700'
                    )}
                >
                    <div className={cn(
                        'w-4 h-4 rounded-md border flex items-center justify-center shrink-0 mt-0.5 transition-colors',
                        isSelected
                            ? 'bg-indigo-600 border-indigo-600 text-white dark:bg-indigo-500 dark:border-indigo-500'
                            : 'bg-white border-slate-300 dark:bg-zinc-800 dark:border-zinc-700 text-transparent'
                    )}>
                        <Check className="w-2.5 h-2.5" strokeWidth={3} />
                    </div>

                    <div className="flex-1 min-w-0">
                        <div className="flex justify-between items-start gap-2">
                            <div className="min-w-0">
                                <h4 className={cn('text-xs font-semibold truncate', isSelected ? 'text-indigo-950 dark:text-indigo-200' : 'text-slate-800 dark:text-zinc-200')}>
                                    {item.name}
                                </h4>
                                {item.description && (
                                    <p className="text-[11px] text-slate-500 dark:text-zinc-400 line-clamp-1 mt-0.5">
                                        {item.description}
                                    </p>
                                )}
                            </div>
                            <div className="text-end shrink-0">
                                <span className={cn('text-xs font-bold', isSelected ? 'text-indigo-700 dark:text-indigo-300' : 'text-slate-900 dark:text-zinc-100')}>
                                    +{calculateItemPrice(item).toFixed(2)}
                                </span>
                                <span className="text-[10px] text-slate-400 ms-0.5">{currency}</span>
                            </div>
                        </div>
                    </div>
                </div>
            );
        }

        return (
            <div
                key={item.id}
                onClick={() => toggleItem(item.id)}
                className={cn(
                    'relative flex items-start gap-4 p-5 rounded-2xl border transition-all duration-200 cursor-pointer group text-start select-none',
                    isSelected
                        ? 'bg-indigo-50/50 border-indigo-500 shadow-sm ring-1 ring-indigo-500/20 dark:bg-indigo-950/30 dark:border-indigo-500/60'
                        : 'bg-white border-slate-200/80 hover:border-slate-300 hover:shadow-xs dark:bg-zinc-900 dark:border-zinc-800 dark:hover:border-zinc-700'
                )}
            >
                <div className={cn(
                    'w-5 h-5 rounded-lg border flex items-center justify-center shrink-0 mt-0.5 transition-colors',
                    isSelected
                        ? 'bg-indigo-600 border-indigo-600 text-white dark:bg-indigo-500 dark:border-indigo-500'
                        : 'bg-white border-slate-300 text-transparent group-hover:border-slate-400 dark:bg-zinc-800 dark:border-zinc-700'
                )}>
                    <Check className="w-3.5 h-3.5" strokeWidth={3} />
                </div>

                <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-start gap-4">
                        <div className="min-w-0">
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <div className={cn(
                                    'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors',
                                    isSelected
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200/80 dark:bg-zinc-800 dark:text-zinc-300'
                                )}>
                                    <Icon className="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 className={cn('text-sm font-semibold tracking-tight', isSelected ? 'text-slate-900 dark:text-white' : 'text-slate-900 dark:text-zinc-100')}>
                                        {item.name}
                                    </h3>
                                    {item.type === 'module' && (
                                        <span className="text-[10px] font-medium uppercase tracking-wider text-slate-400">
                                            Platform Module
                                        </span>
                                    )}
                                </div>

                                {ownedFeature && (
                                    <span className={cn(
                                        'px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded-full',
                                        ownedFeature.status === 'active'
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                                            : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800'
                                    )}>
                                        {ownedFeature.status === 'active' ? 'Active' : 'Expired'}
                                    </span>
                                )}
                            </div>

                            {item.description && (
                                <p className="text-xs text-slate-500 dark:text-zinc-400 mt-2 leading-relaxed">
                                    {item.description}
                                </p>
                            )}

                            {ownedFeature && (
                                <p className={cn(
                                    'text-xs mt-2 font-medium flex items-center gap-1',
                                    ownedFeature.status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                                )}>
                                    <Info className="w-3.5 h-3.5" />
                                    {ownedFeature.status === 'active' ? 'Renews / Expires on' : 'Expired on'} {ownedFeature.expires_at}
                                    {ownedFeature.status === 'expired' && ' — Select to Renew'}
                                </p>
                            )}
                        </div>

                        <div className="text-end shrink-0">
                            {item.type === 'module' && (
                                <div className="text-[10px] text-slate-400 uppercase tracking-wider font-medium mb-0.5">
                                    {__('general.starts_from')}
                                </div>
                            )}
                            <div className="flex items-baseline justify-end gap-1">
                                <span className="text-lg font-bold text-slate-900 dark:text-white">
                                    {calculateItemPrice(item).toFixed(2)}
                                </span>
                                <span className="text-xs font-medium text-slate-500 dark:text-zinc-400">
                                    {currency}
                                </span>
                            </div>
                            <span className="text-[10px] text-slate-400">
                                /{months === 1 ? 'mo' : `${months}mo`}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="flex flex-col lg:flex-row gap-8 items-start w-full">
            {/* ── Products List (Left) ── */}
            <div className="flex-1 w-full space-y-10 text-start">
                {/* 1. Core Modules */}
                <section className="space-y-4">
                    <div>
                        <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/80 mb-1.5 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800">
                            <Building2 className="w-3 h-3" />
                            <span>Architecture Core</span>
                        </div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {__('general.core_modules')}
                        </h2>
                        <p className="text-xs text-slate-500 dark:text-zinc-400">
                            {__('general.the_foundation_for_your_business_operations')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-4">
                        {modules.map(module => {
                            const moduleAddons = addons.filter(a => a.parent_id === module.id);
                            const isModuleSelected = selectedItems.includes(module.id);
                            const ownsModule = !isNewSystem && activeSubscription?.owned_features?.find(f => f.id === module.id)?.status === 'active';

                            return (
                                <div key={module.id} className="flex flex-col">
                                    {renderItemCard(module)}

                                    {/* Add-ons Sub-Section */}
                                    {moduleAddons.length > 0 && (isModuleSelected || ownsModule) && (
                                        <div className="mt-3 ps-4 md:ps-6 border-s-2 border-indigo-200 dark:border-indigo-800 ms-4 md:ms-6 pb-2 animate-in slide-in-from-top-3 fade-in duration-200">
                                            <div className="flex items-center justify-between mb-2.5">
                                                <h4 className="text-xs font-bold text-slate-700 dark:text-zinc-300 flex items-center gap-1.5">
                                                    <Sparkles className="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                                    <span>Enhance {module.name}</span>
                                                </h4>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-6 text-[11px] px-2 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/60 rounded-md font-medium"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleSelectAllAddons(module.id);
                                                    }}
                                                >
                                                    {moduleAddons.every(a => selectedItems.includes(a.id))
                                                        ? __('general.deselect_all')
                                                        : __('general.select_all_addons')}
                                                </Button>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                                {moduleAddons.map(addon => renderItemCard(addon, true))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </section>

                <div className="border-t border-slate-200/80 dark:border-zinc-800" />

                {/* 2. Automation Tools */}
                <section className="space-y-4">
                    <div className="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100/80 mb-1.5 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                <Zap className="w-3 h-3" />
                                <span>Automation Layer</span>
                            </div>
                            <h2 className="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {__('general.automation_tools')}
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-zinc-400">
                                {__('general.standalone_tools_to_boost_your_productivity')}
                            </p>
                        </div>
                        {tools.length > 0 && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleSelectAllTools}
                                className="h-8 text-xs font-medium border-slate-200 hover:bg-slate-50 dark:border-zinc-800 dark:hover:bg-zinc-800 rounded-xl"
                            >
                                {tools.every(t => selectedItems.includes(t.id)) ? 'Deselect All' : 'Select All Tools'}
                            </Button>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        {tools.map(tool => renderItemCard(tool))}
                    </div>
                </section>
            </div>

            {/* ── Sticky Summary Cart (Right) ── */}
            <div className="w-full lg:w-[380px] shrink-0 sticky top-24 text-start">
                <div className="bg-white dark:bg-zinc-900 border border-slate-200/80 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                    {/* Cart Header */}
                    <div className="p-5 border-b border-slate-100 dark:border-zinc-800/80 space-y-3.5 bg-slate-50/50 dark:bg-zinc-900/50">
                        <div>
                            <h3 className="text-sm font-bold tracking-tight text-slate-900 dark:text-white">
                                {__('general.workspace_summary')}
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-zinc-400">
                                {__('general.select_your_preferred_billing_cycle')}
                            </p>
                        </div>

                        {/* Billing Cycle Pill Selector */}
                        <div className="flex bg-slate-200/60 dark:bg-zinc-800/80 p-1 rounded-xl gap-1">
                            {[
                                { id: '1_month', label: '1 Month', badge: null },
                                { id: '6_months', label: '6 Months', badge: null },
                                { id: '1_year', label: '1 Year', badge: '-16%' },
                            ].map(option => (
                                <button
                                    key={option.id}
                                    type="button"
                                    onClick={() => setBilling(option.id as any)}
                                    className={cn(
                                        'flex-1 py-1.5 px-2 rounded-lg text-center font-semibold text-xs transition-all flex items-center justify-center gap-1',
                                        billing === option.id
                                            ? 'bg-white text-slate-900 shadow-2xs dark:bg-zinc-700 dark:text-white'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white'
                                    )}
                                >
                                    <span>{option.label}</span>
                                    {option.badge && (
                                        <span className="text-[10px] px-1 py-0.2 rounded font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                            {option.badge}
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Cart Items List */}
                    <div className="p-5 space-y-4">
                        {selectedItems.length === 0 ? (
                            <div className="text-center py-8">
                                <div className="w-10 h-10 bg-slate-100 dark:bg-zinc-800 rounded-2xl flex items-center justify-center mx-auto mb-3 text-slate-400 dark:text-zinc-500">
                                    <Layers className="w-5 h-5" />
                                </div>
                                <p className="text-slate-500 dark:text-zinc-400 text-xs">
                                    {__('general.select_modules_to_build_your_workspace')}
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-2.5">
                                <div className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                    Selected Items ({selectedItems.length})
                                </div>

                                <div className="space-y-2">
                                    {selectedItems.slice(0, isCartExpanded ? undefined : 5).map(id => {
                                        const item = serviceItems.find(i => i.id === id);
                                        if (!item) return null;
                                        const isAddon = item.type === 'addon';

                                        return (
                                            <div
                                                key={id}
                                                className={cn(
                                                    'flex justify-between items-center text-xs py-1',
                                                    isAddon ? 'ps-3 text-slate-500 dark:text-zinc-400' : 'text-slate-800 dark:text-zinc-200'
                                                )}
                                            >
                                                <span className="flex items-center gap-1.5 truncate pe-2">
                                                    {isAddon && <span className="text-slate-400 text-[10px]">↳</span>}
                                                    <span className="truncate">{item.name}</span>
                                                </span>
                                                <span className={cn('shrink-0', isAddon ? 'text-slate-500' : 'font-semibold text-slate-900 dark:text-white')}>
                                                    {calculateItemPrice(item).toFixed(2)} {currency}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>

                                {selectedItems.length > 5 && (
                                    <button
                                        type="button"
                                        onClick={() => setIsCartExpanded(!isCartExpanded)}
                                        className="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center gap-1 pt-1"
                                    >
                                        {isCartExpanded ? (
                                            <>
                                                <span>{__('general.show_less')}</span>
                                                <ChevronUp className="w-3.5 h-3.5" />
                                            </>
                                        ) : (
                                            <>
                                                <span>+{selectedItems.length - 5} {__('general.more_items')}</span>
                                                <ChevronDown className="w-3.5 h-3.5" />
                                            </>
                                        )}
                                    </button>
                                )}

                                <div className="border-t border-slate-100 dark:border-zinc-800 pt-3 space-y-1.5">
                                    {toolsDiscount > 0 && (
                                        <div className="flex justify-between text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                            <span>{__('general.tools_volume_discount')}</span>
                                            <span>-{toolsDiscount.toFixed(2)} {currency}</span>
                                        </div>
                                    )}

                                    {discount > 0 && (
                                        <div className="flex justify-between text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                            <span>Annual Savings</span>
                                            <span>-{discount.toFixed(2)} {currency}</span>
                                        </div>
                                    )}

                                    {!isNewSystem && proratedRefund > 0 && (
                                        <div className="flex justify-between text-xs text-amber-600 dark:text-amber-400 font-semibold">
                                            <span>Prorated Credit</span>
                                            <span>-{proratedRefund.toFixed(2)} {currency}</span>
                                        </div>
                                    )}
                                </div>

                                <div className="border-t border-slate-200/80 dark:border-zinc-800 pt-3 flex justify-between items-baseline">
                                    <span className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                        {__('general.total_to_pay')}
                                    </span>
                                    <div className={cn('text-end transition-opacity', isCalculating && 'opacity-50')}>
                                        <span className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                            {Math.max(0, total - (!isNewSystem ? proratedRefund : 0)).toFixed(2)}
                                        </span>
                                        <span className="text-xs font-semibold text-slate-500 dark:text-zinc-400 ms-1">
                                            {currency}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Cart Footer Actions */}
                    <div className="p-5 pt-0 space-y-3">
                        {renderActions ? (
                            renderActions({ selectedItems, billing, total })
                        ) : (
                            <Link href="/register?trial=true" className="block w-full">
                                <Button
                                    disabled={selectedItems.length === 0}
                                    className={cn(
                                        'w-full h-11 rounded-xl text-xs font-bold uppercase tracking-wider transition-all',
                                        selectedItems.length > 0
                                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-400 dark:bg-zinc-800 dark:text-zinc-600 cursor-not-allowed'
                                    )}
                                >
                                    {__('general.start_building_your_workspace')}
                                </Button>
                            </Link>
                        )}
                        {!renderActions && (
                            <p className="text-[11px] text-center text-slate-400 dark:text-zinc-500 pt-1 leading-relaxed">
                                {__('general.no_credit_card_required_for_14_day_trial_on_erp_modules_not_applicable_for_tools')}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
