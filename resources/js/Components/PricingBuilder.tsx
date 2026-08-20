import React, { useState, useMemo, useEffect } from 'react';
import axios from 'axios';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
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
    targetModule?: string | null;
    targetTool?: string | null;
    targetPlan?: string | null;
}

export default function PricingBuilder({ 
    serviceItems, 
    currency, 
    activeSubscription,
    isNewSystem = true, // default for guests is new system
    onSystemTypeChange,
    renderActions,
    proratedRefund = 0,
    targetModule = null,
    targetTool = null,
    targetPlan = null
}: PricingBuilderProps) {
    const [billing, setBilling] = useState<'1_month' | '6_months' | '1_year'>('1_month');
    const [isCartExpanded, setIsCartExpanded] = useState(false);
    
    // Determine default selected items (e.g., ERP and CRM) plus any module/tool passed via URL or props
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

    useEffect(() => {
        setSelectedItems(activeItems);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isNewSystem]); // Only reset when switching modes, not when activeItems changes (to allow URL param to be set once)

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

    const handleSelectAllAddons = (moduleId: string) => {
        const moduleAddonsIds = addons.filter(a => a.parent_id === moduleId).map(a => a.id);
        if (moduleAddonsIds.length === 0) return;
        
        const allSelected = moduleAddonsIds.every(id => selectedItems.includes(id));
        
        if (allSelected) {
            setSelectedItems(prev => prev.filter(id => !moduleAddonsIds.includes(id)));
        } else {
            setSelectedItems(prev => {
                const nonAddons = prev.filter(id => !moduleAddonsIds.includes(id));
                return [...nonAddons, ...moduleAddonsIds];
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

    const [calcResult, setCalcResult] = useState<{toolsDiscount: number, annualDiscount: number, total: number} | null>(null);
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
        const timer = setTimeout(fetchCalc, 300);
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

        return (
            <div
                key={item.id}
                onClick={() => toggleItem(item.id)}
                className={cn(
                    'relative flex items-start gap-4 border transition-all duration-200 cursor-pointer group text-start font-mono',
                    isAddon ? 'p-3.5 bg-black hover:border-zinc-500' : 'p-5 bg-[#161616] hover:border-zinc-500',
                    isSelected
                        ? (isAddon ? 'border-[#748660] bg-[#1A2215]' : 'border-[#748660] bg-[#1A2215] shadow-sm')
                        : 'border-[#2B2B2B]'
                )}
            >
                <div className={cn(
                    'flex items-center justify-center border mt-0.5 shrink-0 transition-colors',
                    isAddon ? 'w-5 h-5' : 'w-5 h-5',
                    isSelected ? 'bg-[#748660] border-[#748660] text-black' : 'bg-black border-[#333333] text-transparent group-hover:border-zinc-500'
                )}>
                    <Check className={isAddon ? "w-3 h-3" : "w-3.5 h-3.5"} strokeWidth={3} />
                </div>
                
                <div className="flex-1">
                    <div className="flex justify-between items-start">
                        <div>
                            <div className="flex items-center gap-2">
                                <Icon className={cn("w-4 h-4", isSelected ? 'text-[#748660]' : 'text-zinc-400')} />
                                <h3 className={cn("font-bold text-sm", isSelected ? 'text-white' : 'text-zinc-200')}>
                                    {item.name}
                                </h3>
                                {ownedFeature && (
                                    <span className={cn(
                                        "ms-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider",
                                        ownedFeature.status === 'active' 
                                            ? "bg-[#1E2619] text-[#748660] border border-[#748660]/40" 
                                            : "bg-red-950 text-red-400 border border-red-800"
                                    )}>
                                        {ownedFeature.status === 'active' ? 'Active' : 'Expired'}
                                    </span>
                                )}
                            </div>
                            {item.description && (
                                <p className="text-xs text-zinc-400 mt-1 leading-relaxed font-mono">
                                    {item.description}
                                </p>
                            )}
                            {ownedFeature && (
                                <p className={cn(
                                    "text-xs mt-1.5 font-medium",
                                    ownedFeature.status === 'active' ? "text-[#748660]" : "text-red-400"
                                    )}>
                                    {ownedFeature.status === 'active' ? 'Renews / Expires on' : 'Expired on'} {ownedFeature.expires_at}
                                    {ownedFeature.status === 'expired' && " - Select to Renew"}
                                </p>
                            )}
                        </div>
                        <div className="text-end shrink-0 ms-2 font-mono">
                            {item.type === 'module' && (
                                <div className="text-[10px] text-zinc-500 font-medium uppercase tracking-wider mb-0.5 text-end">{__('general.starts_from')}</div>
                            )}
                            <span className="text-base font-bold text-white">
                                {calculateItemPrice(item).toFixed(2)}
                            </span>
                            <span className="text-xs text-zinc-400 ms-1">{currency}</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="flex flex-col lg:flex-row gap-8 items-start w-full font-mono">
            {/* ── Products List (Left) ── */}
            <div className="flex-1 w-full space-y-10 text-start">
                <section>
                    <div className="mb-4">
                        <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                            Architecture Core
                        </span>
                        <h2 className="text-xl font-bold text-white mt-1">{__('general.core_modules')}</h2>
                        <p className="text-xs text-zinc-400 font-mono">{__('general.the_foundation_for_your_business_operations')}</p>
                    </div>
                    <div className="grid grid-cols-1 gap-4">
                        {modules.map(module => {
                            const moduleAddons = addons.filter(a => a.parent_id === module.id);
                            const isModuleSelected = selectedItems.includes(module.id);
                            return (
                                <div key={module.id} className="flex flex-col">
                                    {renderItemCard(module)}
                                    
                                    {/* Add-ons Section */}
                                    {moduleAddons.length > 0 && (isModuleSelected || (isNewSystem ? false : activeSubscription?.owned_features?.find(f => f.id === module.id)?.status === 'active')) && (
                                        <div className="mt-3 ps-4 md:ps-6 border-s-2 border-[#748660]/40 ms-4 md:ms-6 pb-2 animate-in slide-in-from-top-4 fade-in duration-200">
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="text-xs font-bold text-zinc-300 flex items-center gap-2 tracking-tight">
                                                    <Sparkles className="w-3.5 h-3.5 text-[#748660]" /> 
                                                    Power up {module.name}
                                                </h4>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    className="h-6 text-[11px] px-2 text-[#748660] hover:text-white hover:bg-black rounded-none"
                                                    onClick={(e) => { e.stopPropagation(); handleSelectAllAddons(module.id); }}
                                                >
                                                    {moduleAddons.every(a => selectedItems.includes(a.id)) ? __('general.deselect_all') : __('general.select_all_addons')}
                                                </Button>
                                            </div>
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

                <div className="border-t border-[#222222]" />

                <section>
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#748660] font-bold">
                                Automation Layer
                            </span>
                            <h2 className="text-xl font-bold text-white mt-1">{__('general.automation_tools')}</h2>
                            <p className="text-xs text-zinc-400 font-mono">{__('general.standalone_tools_to_boost_your_productivity')}</p>
                        </div>
                        {tools.length > 0 && (
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={handleSelectAllTools}
                                className="border-[#333333] bg-black text-xs text-zinc-300 hover:bg-[#222222] hover:text-white rounded-none"
                            >
                                {tools.every(t => selectedItems.includes(t.id)) ? 'Deselect All' : 'Select All Tools'}
                            </Button>
                        )}
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {tools.map(tool => renderItemCard(tool))}
                    </div>
                </section>
            </div>

            {/* ── Sticky Summary Cart (Right) ── */}
            <div className="w-full lg:w-[380px] shrink-0 sticky top-24 text-start font-mono">
                <div className="bg-[#161616] border border-[#2B2B2B] overflow-hidden">
                    <div className="p-6 bg-black border-b border-[#2B2B2B] flex flex-col gap-4">
                        <div>
                            <h3 className="text-sm font-bold uppercase tracking-wider text-white">{__('general.workspace_summary')}</h3>
                            <p className="text-xs text-zinc-400">{__('general.select_your_preferred_billing_cycle')}</p>
                        </div>
                        
                        <div className="flex border border-[#333333] bg-black p-0.5 text-xs">
                            {[
                                { id: '1_month', label: '1M' },
                                { id: '6_months', label: '6M' },
                                { id: '1_year', label: '1Y (-16%)' },
                            ].map(option => (
                                <button
                                    key={option.id}
                                    onClick={() => setBilling(option.id as any)}
                                    className={cn(
                                        'flex-1 py-1.5 px-1 text-center font-bold transition-all text-xs',
                                        billing === option.id
                                            ? 'bg-white text-black'
                                            : 'text-zinc-400 hover:text-white'
                                    )}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>

                    </div>
                    
                    <div className="p-6">
                        {selectedItems.length === 0 ? (
                            <div className="text-center py-8">
                                <div className="w-10 h-10 bg-black border border-[#2B2B2B] flex items-center justify-center mx-auto mb-3 text-zinc-500">
                                    <Layers className="w-5 h-5" />
                                </div>
                                <p className="text-zinc-500 text-xs">{__('general.select_modules_to_build_your_workspace')}</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {selectedItems.slice(0, isCartExpanded ? undefined : 5).map(id => {
                                    const item = serviceItems.find(i => i.id === id);
                                    if (!item) return null;
                                    
                                    const isAddon = item.type === 'addon';
                                    
                                    return (
                                        <div key={id} className={cn("flex justify-between text-xs", isAddon ? "ps-3 text-zinc-400" : "text-zinc-200")}>
                                            <span className="flex items-center gap-1.5">
                                                {isAddon && <span className="text-zinc-600">↳</span>}
                                                {item.name}
                                            </span>
                                            <span className={cn(isAddon ? "text-zinc-400" : "text-white font-bold")}>
                                                {calculateItemPrice(item).toFixed(2)}
                                            </span>
                                        </div>
                                    );
                                })}

                                {selectedItems.length > 5 && (
                                    <button
                                        onClick={() => setIsCartExpanded(!isCartExpanded)}
                                        className="text-xs text-[#748660] hover:underline font-bold w-full text-start py-1"
                                    >
                                        {isCartExpanded ? __('general.show_less') : `+${selectedItems.length - 5} ${__('general.more_items')}`}
                                    </button>
                                )}
                                
                                <div className="border-t border-[#2B2B2B] pt-3 mt-3" />
                                
                                {toolsDiscount > 0 && (
                                    <div className="flex justify-between text-xs text-[#748660] font-bold">
                                        <span>{__('general.tools_volume_discount')}</span>
                                        <span>-{toolsDiscount.toFixed(2)}</span>
                                    </div>
                                )}

                                {discount > 0 && (
                                    <div className="flex justify-between text-xs text-[#748660] font-bold">
                                        <span>Discount (Annual)</span>
                                        <span>-{discount.toFixed(2)}</span>
                                    </div>
                                )}

                                {!isNewSystem && proratedRefund > 0 && (
                                    <div className="flex justify-between text-xs text-amber-400 font-bold">
                                        <span>Prorated Refund</span>
                                        <span>-{proratedRefund.toFixed(2)}</span>
                                    </div>
                                )}

                                <div className="flex justify-between items-end pt-2">
                                    <span className="text-xs uppercase font-bold text-zinc-400">{__('general.total_to_pay')}</span>
                                    <div className={cn("text-end", isCalculating && "opacity-50 transition-opacity")}>
                                        <span className="text-2xl sm:text-3xl font-bold font-mono text-white">
                                            {Math.max(0, total - (!isNewSystem ? proratedRefund : 0)).toFixed(2)}
                                        </span>
                                        <span className="text-xs text-zinc-400 ms-1">{currency}</span>
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
                                        'w-full h-11 rounded-none text-xs font-bold uppercase tracking-wider transition-all',
                                        selectedItems.length > 0
                                            ? 'bg-white hover:bg-zinc-200 text-black'
                                            : 'bg-[#2B2B2B] text-zinc-600 cursor-not-allowed'
                                    )}
                                >{__('general.start_building_your_workspace')}</Button>
                            </Link>
                        )}
                        {!renderActions && (
                            <p className="text-[11px] text-center text-zinc-500 pt-1 font-mono">{__('general.no_credit_card_required_for_14_day_trial_on_erp_modules_not_applicable_for_tools')}</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
