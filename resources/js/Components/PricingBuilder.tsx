import React, { useState, useMemo, useEffect } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import {
    Check, Layers, Crown, Sparkles, Building2, MessageSquare, Zap, Store, Wrench
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
    isNewSystem = true, // default for guests is new system
    onSystemTypeChange,
    renderActions,
    proratedRefund = 0
}: PricingBuilderProps) {
    const [billing, setBilling] = useState<'1_month' | '6_months' | '1_year'>('1_month');
    
    // Default selected items (e.g., ERP and CRM) to show a populated cart by default
    const activeItems = useMemo(() => {
        if (isNewSystem) return serviceItems.filter(i => i.id === 'erp' || i.id === 'crm').map(i => i.id);
        return [];
    }, [isNewSystem, serviceItems]);
    useEffect(() => {
        setSelectedItems(activeItems);
    }, [isNewSystem, activeItems]);

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
    const originalTotal = subtotal * months;
    const discount = originalTotal - total;

    const renderItemCard = (item: ServiceItem, isAddon: boolean = false) => {
        const isSelected = selectedItems.includes(item.id);
        const Icon = item.icon && ICON_MAP[item.icon] ? ICON_MAP[item.icon] : Layers;
        const ownedFeature = isNewSystem ? undefined : activeSubscription?.owned_features?.find(f => f.id === item.id);

        return (
            <div
                key={item.id}
                onClick={() => toggleItem(item.id)}
                className={cn(
                    'relative flex items-start gap-4 rounded-2xl border transition-all duration-300 cursor-pointer group text-left',
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
                        </div>
                        <div className="text-right shrink-0 ml-2">
                            {item.type === 'module' && (
                                <div className="text-[10px] text-slate-400 font-medium uppercase tracking-wider mb-0.5 text-right">Starts from</div>
                            )}
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
        <div className="flex flex-col lg:flex-row gap-8 items-start w-full">
            {/* ── Products List (Left) ── */}
            <div className="flex-1 w-full space-y-10 text-left">
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
                                    {moduleAddons.length > 0 && (isModuleSelected || (isNewSystem ? false : activeSubscription?.owned_features?.find(f => f.id === module.id)?.status === 'active')) && (
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
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-900">Automation Tools</h2>
                            <p className="text-sm text-slate-500">Standalone tools to boost your productivity.</p>
                        </div>
                        {tools.length > 0 && (
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={handleSelectAllTools}
                            >
                                {tools.every(t => selectedItems.includes(t.id)) ? 'Deselect All' : 'Select All Tools'}
                            </Button>
                        )}
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {tools.map(renderItemCard)}
                    </div>
                </section>
            </div>

            {/* ── Sticky Summary Cart (Right) ── */}
            <div className="w-full lg:w-[380px] shrink-0 sticky top-24 text-left">
                <div className="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                    <div className="p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col gap-3">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">Workspace Summary</h3>
                            <p className="text-sm text-slate-500">Select your preferred billing cycle</p>
                        </div>
                        
                        <div className="flex bg-slate-200/50 p-1 rounded-xl text-sm text-slate-500">
                            {[
                                { id: '1_month', label: '1M' },
                                { id: '6_months', label: '6M' },
                                { id: '1_year', label: '1Y (Save 16%)' },
                            ].map(option => (
                                <button
                                    key={option.id}
                                    onClick={() => setBilling(option.id as any)}
                                    className={cn(
                                        'flex-1 py-2 px-1 text-center rounded-lg font-medium transition-all duration-200 text-xs sm:text-sm',
                                        billing === option.id
                                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                            : 'hover:text-slate-700 hover:bg-slate-200'
                                    )}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>

                        {activeSubscription?.owned_features && activeSubscription.owned_features.length > 0 && (
                            <div className="mt-4 flex flex-col gap-2 p-3 bg-white border border-slate-200 rounded-xl">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        className="text-indigo-600 focus:ring-indigo-500" 
                                        checked={!isNewSystem} 
                                        onChange={() => onSystemTypeChange?.(false)}
                                    />
                                    <span className="text-sm font-medium text-slate-700">Upgrade Current System</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        className="text-indigo-600 focus:ring-indigo-500" 
                                        checked={isNewSystem} 
                                        onChange={() => onSystemTypeChange?.(true)}
                                    />
                                    <span className="text-sm font-medium text-slate-700">Create New System</span>
                                </label>
                            </div>
                        )}
                    </div>
                    
                    <div className="p-6">
                        {selectedItems.length === 0 ? (
                            <div className="text-center py-8">
                                <div className="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <Layers className="w-6 h-6 text-slate-300" />
                                </div>
                                <p className="text-slate-500 text-sm">Select modules to build your workspace.</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {selectedItems.map(id => {
                                    const item = serviceItems.find(i => i.id === id);
                                    if (!item) return null;
                                    
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

                                {!isNewSystem && proratedRefund > 0 && (
                                    <div className="flex justify-between text-sm text-amber-600 font-medium">
                                        <span>Prorated Refund (Current Plan)</span>
                                        <span>-{proratedRefund.toFixed(2)}</span>
                                    </div>
                                )}

                                <div className="flex justify-between items-end">
                                    <span className="text-base font-medium text-slate-900">Total To Pay</span>
                                    <div className="text-right">
                                        <span className="text-3xl font-bold tracking-tight text-indigo-600">
                                            {Math.max(0, total - (!isNewSystem ? proratedRefund : 0)).toFixed(2)}
                                        </span>
                                        <span className="text-sm text-slate-400 ml-1">{currency}</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="p-6 pt-0 space-y-3">
                        {renderActions ? renderActions({ selectedItems, billing, total }) : (
                            <Link href="/register?trial=true" className="block w-full">
                                <Button
                                    disabled={selectedItems.length === 0}
                                    className={cn(
                                        'w-full h-12 rounded-xl text-sm font-medium gap-2 transition-all',
                                        selectedItems.length > 0
                                            ? 'bg-slate-900 hover:bg-slate-800 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                    )}
                                >
                                    Start Building Your Workspace
                                </Button>
                            </Link>
                        )}
                        {!renderActions && (
                            <p className="text-xs text-center text-slate-500 pt-2">
                                No credit card required for 14-day trial.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
