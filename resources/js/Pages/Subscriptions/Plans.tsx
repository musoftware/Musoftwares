import React, { useState, useMemo, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import PricingBuilder from '@/Components/PricingBuilder';
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
    proratedRefund?: number;
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

export default function Plans({ serviceItems, activeSubscription, walletBalance, currency, proratedRefund = 0 }: PlansProps) {
    const [billing, setBilling] = useState<'1_month' | '6_months' | '1_year'>('1_month');
    const [isNewSystem, setIsNewSystem] = useState<boolean>(false);
    
    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Build Your Workspace" />

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
                            Build Your Workspace
                        </h1>
                        <p className="mt-2 text-lg text-slate-500 font-light">
                            Select exactly what you need. No more, no less.
                        </p>
                    </div>
                </div>
            </div>

            {/* ── Active Subscription Banner ── */}
            {activeSubscription && activeSubscription.owned_features && activeSubscription.owned_features.length > 0 && (
                <div className="max-w-7xl mx-auto px-4 mb-6">
                    <div className="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-2xl px-6 py-3.5">
                        <div className="flex items-center gap-2.5 text-sm text-emerald-800">
                            <ShieldCheck className="h-4 w-4 text-emerald-500" />
                            <span>
                                Your workspace has active modules
                                {activeSubscription.expires_at && ` · next renewal on ${activeSubscription.expires_at}`}
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
                    proratedRefund={proratedRefund}
                    renderActions={({ selectedItems, billing, total }) => {
                        const finalTotal = Math.max(0, total - (!isNewSystem ? proratedRefund : 0));
                        const canAfford = walletBalance >= finalTotal;
                        
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
                                    {canAfford || selectedItems.length === 0 ? 'Subscribe with Wallet' : `Need ${currency} ${finalTotal.toFixed(2)}`}
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
