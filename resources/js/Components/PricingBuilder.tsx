import React, { useState, useMemo, useEffect } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import {
    Check, Layers, Sparkles, Building2, MessageSquare, Zap, Store, Wrench,
    ChevronRight, ShoppingBag, CalendarDays
} from 'lucide-react';

const ICON_MAP: Record<string, React.ElementType> = {
    Building2, MessageSquare, Zap, Sparkles, Check, Store, Layers, Wrench
};

interface ServiceItem {
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

interface PricingBuilderProps {
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
}

export default function PricingBuilder({ 
    serviceItems, 
    currency, 
    activeSubscription,
    isNewSystem = true,
    onSystemTypeChange,
    renderActions,
    proratedRefund = 0
}: PricingBuilderProps) {
    const [billing, setBilling] = useState<'1_month' | '6_months' | '1_year'>('1_month');
    
    const ownedActiveIds = useMemo(() => {
        return new Set(
            activeSubscription?.owned_features
                ?.filter(f => f.status === 'active')
                ?.map(f => f.id) || []
        );
    }, [activeSubscription]);

    // Determine default selected items (e.g., ERP and CRM) plus any module passed via URL
    const activeItems = useMemo(() => {
        const items: string[] = [];

        if (isNewSystem) {
            serviceItems.filter(i => i.id === 'erp' || i.id === 'crm').forEach(i => {
                if (!ownedActiveIds.has(i.id)) {
                    items.push(i.id);
                }
            });
        }
        
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            const mod = params.get('module');
            if (mod && !items.includes(mod) && !ownedActiveIds.has(mod)) {
                items.push(mod);
            }
        }
        return items;
    }, [isNewSystem, serviceItems, activeSubscription, ownedActiveIds]);

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
        const toolIds = tools.map(t => t.id).filter(id => !ownedActiveIds.has(id));
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

    const multiplier = useMemo(() => {
        if (billing === '1_month') return 1;
        if (billing === '6_months') return 6;
        if (billing === '1_year') return 10;
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
        let paidToolsCount = 0;
        let toolsBaseTotal = 0;

        selectedItems.forEach((id) => {
            if (ownedActiveIds.has(id)) return;

            const item = serviceItems.find((i) => i.id === id);
            if (item) {
                if (item.type === 'tool') {
                    if (item.monthly_price > 0) {
                        paidToolsCount++;
                        toolsBaseTotal += item.monthly_price;
                    }
                } else {
                    baseSubtotal += item.monthly_price;
                }
            }
        });

        let toolsSubtotal = 0;
        if (paidToolsCount > 0) {
            const discountPercent = Math.min(50, (paidToolsCount - 1) * 10);
            toolsSubtotal = toolsBaseTotal * (1 - (discountPercent / 100));
        }

        return baseSubtotal + toolsSubtotal;
    }, [selectedItems, serviceItems, ownedActiveIds]);

    const toolsDiscount = useMemo(() => {
        let paidToolsCount = 0;
        let toolsBaseTotal = 0;
        
        selectedItems.forEach((id) => {
            if (ownedActiveIds.has(id)) return;
            const item = serviceItems.find((i) => i.id === id);
            if (item && item.type === 'tool' && item.monthly_price > 0) {
                paidToolsCount++;
                toolsBaseTotal += item.monthly_price;
            }
        });

        if (paidToolsCount <= 1) return 0;
        
        const discountPercent = Math.min(50, (paidToolsCount - 1) * 10);
        const discountedToolsPrice = toolsBaseTotal * (1 - (discountPercent / 100));
        
        return (toolsBaseTotal - discountedToolsPrice) * months;
    }, [selectedItems, serviceItems, months, ownedActiveIds]);

    const total = subtotal * multiplier;
    const originalTotal = subtotal * months;
    const discount = originalTotal - total;

    const newItemsCount = selectedItems.filter(id => !ownedActiveIds.has(id)).length;

    const renderItemCard = (item: ServiceItem, isAddon: boolean = false) => {
        const isSelected = selectedItems.includes(item.id);
        const Icon = item.icon && ICON_MAP[item.icon] ? ICON_MAP[item.icon] : Layers;
        const ownedFeature = activeSubscription?.owned_features?.find(f => f.id === item.id);
        const isActivelyOwned = ownedFeature?.status === 'active';

        return (
            <div
                key={item.id}
                onClick={() => {
                    if (isActivelyOwned) return;
                    toggleItem(item.id);
                }}
                className={cn(
                    'relative rounded-2xl border transition-all duration-200 text-left overflow-hidden',
                    isAddon ? 'p-4' : 'p-5',
                    isActivelyOwned
                        ? 'border-emerald-200 bg-gradient-to-br from-emerald-50/60 to-emerald-50/20 cursor-default'
                        : 'cursor-pointer group',
                    !isActivelyOwned && (isAddon ? 'bg-white hover:bg-slate-50/50' : 'bg-white hover:shadow-md hover:shadow-slate-100/80'),
                    !isActivelyOwned && isSelected
                        ? (isAddon
                            ? 'border-indigo-300 bg-indigo-50/40 ring-1 ring-indigo-200/50'
                            : 'border-indigo-500 bg-gradient-to-br from-indigo-50/50 to-white ring-1 ring-indigo-200/60 shadow-md shadow-indigo-100/40')
                        : !isActivelyOwned ? 'border-slate-200/80 hover:border-slate-300' : ''
                )}
            >
                {/* Active glow line for selected */}
                {!isActivelyOwned && isSelected && !isAddon && (
                    <div className="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-indigo-500 to-violet-500" />
                )}
                {isActivelyOwned && !isAddon && (
                    <div className="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-emerald-400 to-teal-400" />
                )}

                <div className="flex items-start gap-4">
                    {/* Checkbox */}
                    <div className={cn(
                        'flex items-center justify-center rounded-lg border mt-0.5 shrink-0 transition-all duration-200',
                        isAddon ? 'w-5 h-5 rounded-md' : 'w-6 h-6',
                        isActivelyOwned
                            ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm'
                            : isSelected
                                ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm shadow-indigo-200/50'
                                : 'bg-white border-slate-300 text-transparent group-hover:border-indigo-400 group-hover:bg-indigo-50/50'
                    )}>
                        <Check className={cn(isAddon ? "w-3 h-3" : "w-3.5 h-3.5", "stroke-[3]")} />
                    </div>
                    
                    {/* Content */}
                    <div className="flex-1 min-w-0">
                        <div className="flex justify-between items-start gap-3">
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <div className={cn(
                                        'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors',
                                        isActivelyOwned
                                            ? 'bg-emerald-100 text-emerald-600'
                                            : isSelected
                                                ? 'bg-indigo-100 text-indigo-600'
                                                : 'bg-slate-100 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500'
                                    )}>
                                        <Icon className="w-3.5 h-3.5" />
                                    </div>
                                    <h3 className={cn(
                                        "font-semibold text-[15px] tracking-tight",
                                        isActivelyOwned ? 'text-emerald-900' : isSelected ? 'text-slate-900' : 'text-slate-800'
                                    )}>
                                        {item.name}
                                    </h3>
                                    {isActivelyOwned && (
                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md bg-emerald-500 text-white shadow-sm">
                                            <Check className="w-2.5 h-2.5 stroke-[3]" />
                                            Subscribed
                                        </span>
                                    )}
                                    {ownedFeature && ownedFeature.status === 'expired' && (
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md bg-red-100 text-red-600 border border-red-200">
                                            Expired
                                        </span>
                                    )}
                                </div>
                                {item.description && (
                                    <p className={cn(
                                        "text-[13px] mt-1.5 leading-relaxed",
                                        isActivelyOwned ? 'text-emerald-700/70' : 'text-slate-500'
                                    )}>
                                        {item.description}
                                    </p>
                                )}
                                {isActivelyOwned && (
                                    <div className="flex items-center gap-1.5 mt-2">
                                        <CalendarDays className="w-3 h-3 text-emerald-500" />
                                        <p className="text-[11px] font-medium text-emerald-600">
                                            Expires {ownedFeature!.expires_at}
                                        </p>
                                    </div>
                                )}
                                {ownedFeature && ownedFeature.status === 'expired' && (
                                    <p className="text-[11px] mt-1.5 font-medium text-red-500">
                                        Expired on {ownedFeature.expires_at} — Select to renew
                                    </p>
                                )}
                            </div>

                            {/* Price */}
                            <div className="text-right shrink-0">
                                {isActivelyOwned ? (
                                    <div className="flex items-center gap-1.5 text-emerald-600">
                                        <span className="text-[13px] font-semibold">Active</span>
                                    </div>
                                ) : (
                                    <>
                                        {item.type === 'module' && (
                                            <div className="text-[10px] text-slate-400 font-medium uppercase tracking-wider mb-0.5">From</div>
                                        )}
                                        <div className="flex items-baseline gap-0.5">
                                            <span className={cn(
                                                "font-bold tracking-tight",
                                                isAddon ? "text-base" : "text-lg",
                                                isSelected ? 'text-indigo-700' : 'text-slate-900'
                                            )}>
                                                {calculateItemPrice(item).toFixed(2)}
                                            </span>
                                            <span className="text-[11px] text-slate-400 ml-0.5">{currency}</span>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="flex flex-col lg:flex-row gap-8 items-start w-full">
            {/* ── Products List (Left) ── */}
            <div className="flex-1 w-full space-y-10 text-left">
                {/* Core Modules */}
                <section>
                    <div className="mb-5 flex items-center gap-3">
                        <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-sm">
                            <Building2 className="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <h2 className="text-lg font-bold text-slate-900 tracking-tight">Core Modules</h2>
                            <p className="text-[13px] text-slate-500">The foundation for your business operations.</p>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4">
                        {modules.map(module => {
                            const moduleAddons = addons.filter(a => a.parent_id === module.id);
                            const isModuleSelected = selectedItems.includes(module.id);
                            const isModuleOwned = ownedActiveIds.has(module.id);
                            return (
                                <div key={module.id} className="flex flex-col">
                                    {renderItemCard(module)}
                                    
                                    {/* Add-ons Section */}
                                    {moduleAddons.length > 0 && (isModuleSelected || isModuleOwned) && (
                                        <div className="mt-3 pl-5 md:pl-10 border-l-2 border-indigo-200/60 ml-5 md:ml-7 pb-1">
                                            <h4 className="text-[13px] font-bold text-slate-700 mb-3 flex items-center gap-2">
                                                <Sparkles className="w-3.5 h-3.5 text-indigo-500" /> 
                                                Power-ups for {module.name}
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

                {/* Automation Tools */}
                <section>
                    <div className="mb-5 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-sm">
                                <Zap className="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h2 className="text-lg font-bold text-slate-900 tracking-tight">Automation Tools</h2>
                                <p className="text-[13px] text-slate-500">Standalone tools to boost your productivity.</p>
                            </div>
                        </div>
                        {tools.length > 0 && (
                            <Button 
                                variant="outline" 
                                size="sm"
                                onClick={handleSelectAllTools}
                                className="text-xs rounded-lg border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 hover:text-indigo-700 transition-colors"
                            >
                                {tools.filter(t => !ownedActiveIds.has(t.id)).every(t => selectedItems.includes(t.id)) ? 'Deselect All' : 'Select All'}
                            </Button>
                        )}
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {tools.map(tool => renderItemCard(tool))}
                    </div>
                </section>
            </div>

            {/* ── Sticky Summary Cart (Right) ── */}
            <div className="w-full lg:w-[380px] shrink-0 sticky top-20 text-left">
                <div className="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-lg shadow-slate-200/30">
                    {/* Cart Header */}
                    <div className="p-5 bg-gradient-to-br from-slate-50 to-slate-50/50 border-b border-slate-100">
                        <div className="flex items-center gap-2.5 mb-4">
                            <div className="w-7 h-7 rounded-lg bg-slate-900 flex items-center justify-center">
                                <ShoppingBag className="w-3.5 h-3.5 text-white" />
                            </div>
                            <div>
                                <h3 className="text-[15px] font-bold text-slate-900 tracking-tight">Order Summary</h3>
                                <p className="text-[11px] text-slate-400">Select billing cycle</p>
                            </div>
                        </div>
                        
                        {/* Billing Cycle Toggle */}
                        <div className="flex bg-slate-200/40 p-1 rounded-xl">
                            {[
                                { id: '1_month', label: 'Monthly', sub: null },
                                { id: '6_months', label: '6 Months', sub: null },
                                { id: '1_year', label: 'Yearly', sub: 'Save 16%' },
                            ].map(option => (
                                <button
                                    key={option.id}
                                    onClick={() => setBilling(option.id as any)}
                                    className={cn(
                                        'flex-1 py-2 px-2 text-center rounded-lg font-medium transition-all duration-200 relative',
                                        billing === option.id
                                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                            : 'text-slate-500 hover:text-slate-700'
                                    )}
                                >
                                    <span className="text-[12px]">{option.label}</span>
                                    {option.sub && billing === option.id && (
                                        <span className="absolute -top-2 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full whitespace-nowrap">
                                            {option.sub}
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>

                        {/* System Type Toggle */}
                        {activeSubscription?.owned_features && activeSubscription.owned_features.length > 0 && (
                            <div className="mt-3 flex gap-2">
                                {[
                                    { isNew: false, label: 'Upgrade Current', icon: ChevronRight },
                                    { isNew: true, label: 'Create New', icon: Building2 },
                                ].map(opt => (
                                    <button
                                        key={String(opt.isNew)}
                                        onClick={() => onSystemTypeChange?.(opt.isNew)}
                                        className={cn(
                                            'flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-[12px] font-medium transition-all border',
                                            (isNewSystem === opt.isNew)
                                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                                                : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'
                                        )}
                                    >
                                        <opt.icon className="w-3.5 h-3.5" />
                                        {opt.label}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                    
                    {/* Cart Items */}
                    <div className="p-5">
                        {newItemsCount === 0 ? (
                            <div className="text-center py-10">
                                <div className="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-slate-100">
                                    <Layers className="w-6 h-6 text-slate-300" />
                                </div>
                                <p className="text-slate-500 text-sm font-medium">No new items selected</p>
                                <p className="text-[12px] text-slate-400 mt-1">Click on modules above to add them.</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {selectedItems.filter(id => !ownedActiveIds.has(id)).map(id => {
                                    const item = serviceItems.find(i => i.id === id);
                                    if (!item) return null;
                                    
                                    const isAddon = item.type === 'addon';
                                    
                                    return (
                                        <div key={id} className={cn(
                                            "flex justify-between items-center text-sm py-1",
                                            isAddon ? "pl-3" : ""
                                        )}>
                                            <span className="flex items-center gap-2 text-slate-700">
                                                {isAddon && <span className="text-indigo-300 text-xs">↳</span>}
                                                <span className={cn(isAddon ? "text-[13px]" : "text-[13px] font-medium")}>{item.name}</span>
                                            </span>
                                            <span className={cn(
                                                "font-semibold tabular-nums",
                                                isAddon ? "text-slate-500 text-[13px]" : "text-slate-900 text-[13px]"
                                            )}>
                                                {calculateItemPrice(item).toFixed(2)}
                                            </span>
                                        </div>
                                    );
                                })}
                                
                                <div className="border-t border-dashed border-slate-200 my-3" />
                                
                                {toolsDiscount > 0 && (
                                    <div className="flex justify-between text-[13px] text-indigo-600 font-medium">
                                        <span>Tools Discount</span>
                                        <span>-{toolsDiscount.toFixed(2)}</span>
                                    </div>
                                )}

                                {discount > 0 && (
                                    <div className="flex justify-between text-[13px] text-emerald-600 font-medium">
                                        <span>Annual Savings</span>
                                        <span>-{discount.toFixed(2)}</span>
                                    </div>
                                )}

                                {!isNewSystem && proratedRefund > 0 && (
                                    <div className="flex justify-between text-[13px] text-amber-600 font-medium">
                                        <span>Prorated Refund</span>
                                        <span>-{proratedRefund.toFixed(2)}</span>
                                    </div>
                                )}

                                <div className="border-t border-slate-200 pt-3 mt-2" />

                                <div className="flex justify-between items-end">
                                    <div>
                                        <span className="text-[11px] text-slate-400 font-medium uppercase tracking-wider block">Total</span>
                                        <span className="text-[12px] text-slate-500">
                                            {billing === '1_month' ? '1 month' : billing === '6_months' ? '6 months' : '1 year'}
                                        </span>
                                    </div>
                                    <div className="text-right">
                                        <span className="text-2xl font-bold tracking-tight bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
                                            {Math.max(0, total - (!isNewSystem ? proratedRefund : 0)).toFixed(2)}
                                        </span>
                                        <span className="text-sm text-slate-400 ml-1">{currency}</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Cart Actions */}
                    <div className="p-5 pt-0 space-y-3">
                        {renderActions ? renderActions({ selectedItems: selectedItems.filter(id => !ownedActiveIds.has(id)), billing, total }) : (
                            <Link href="/register?trial=true" className="block w-full">
                                <Button
                                    disabled={newItemsCount === 0}
                                    className={cn(
                                        'w-full h-12 rounded-xl text-sm font-semibold gap-2.5 transition-all duration-200',
                                        newItemsCount > 0
                                            ? 'bg-gradient-to-r from-slate-900 to-slate-800 hover:from-slate-800 hover:to-slate-700 text-white shadow-md'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                    )}
                                >
                                    Start Building Your Workspace
                                </Button>
                            </Link>
                        )}
                        {!renderActions && (
                            <p className="text-[11px] text-center text-slate-400 leading-relaxed">
                                No credit card required for 14-day trial on ERP & modules.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
