import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import PricingBuilder from '@/Components/PricingBuilder';
import {
    Wallet, CreditCard, ArrowRight, ShieldCheck,
    Sparkles, Crown, ArrowLeft, Zap
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
    isEligibleForTrial?: boolean;
}

export default function Plans({ serviceItems, activeSubscription, walletBalance, currency, proratedRefund = 0, isEligibleForTrial = false }: PlansProps) {
    const [isNewSystem, setIsNewSystem] = useState<boolean>(false);
    
    const activeCount = activeSubscription?.owned_features?.filter(f => f.status === 'active')?.length || 0;

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Build Your Workspace" />

            {/* ── Gradient Hero ── */}
            <div className="relative overflow-hidden">
                {/* Subtle gradient background */}
                <div className="absolute inset-0 bg-gradient-to-br from-slate-50 via-indigo-50/40 to-violet-50/30" />
                <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-gradient-to-bl from-indigo-100/30 via-transparent to-transparent rounded-full blur-3xl" />
                <div className="absolute bottom-0 left-0 w-[300px] h-[300px] bg-gradient-to-tr from-violet-100/20 via-transparent to-transparent rounded-full blur-3xl" />
                
                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-8">
                    <Link
                        href={route('subscriptions.manage')}
                        className="inline-flex items-center text-[13px] text-slate-500 hover:text-slate-800 mb-8 transition-colors group"
                    >
                        <ArrowLeft className="w-3.5 h-3.5 mr-1.5 group-hover:-translate-x-0.5 transition-transform" />
                        Back to My Subscriptions
                    </Link>

                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div className="max-w-2xl">
                            <div className="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-[11px] font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-full mb-4 shadow-sm">
                                <Crown className="h-3 w-3" />
                                Modular Workspace
                            </div>
                            <h1 className="text-3xl sm:text-4xl lg:text-[42px] font-bold tracking-tight text-slate-900 leading-[1.15]">
                                Build your perfect
                                <span className="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent"> workspace</span>
                            </h1>
                            <p className="mt-3 text-base sm:text-lg text-slate-500 font-normal leading-relaxed">
                                Select exactly what you need. Pay only for what you use. Scale anytime.
                            </p>
                        </div>

                        {/* Wallet Balance Card */}
                        <div className="flex items-center gap-3 bg-white/80 backdrop-blur-sm border border-slate-200/80 rounded-2xl px-5 py-3 shadow-sm">
                            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-sm">
                                <Wallet className="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <p className="text-[11px] text-slate-400 font-medium uppercase tracking-wider">Balance</p>
                                <p className="text-lg font-bold text-slate-900 tracking-tight">
                                    {walletBalance.toLocaleString()} <span className="text-sm font-medium text-slate-400">{currency}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Active Subscription Banner ── */}
            {activeCount > 0 && (
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-1 mb-4">
                    <div className="flex items-center gap-3 bg-emerald-50/80 backdrop-blur-sm border border-emerald-200/60 rounded-xl px-5 py-3">
                        <div className="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <ShieldCheck className="h-4 w-4 text-emerald-600" />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-medium text-emerald-800">
                                {activeCount} active {activeCount === 1 ? 'subscription' : 'subscriptions'}
                                {activeSubscription?.expires_at && (
                                    <span className="text-emerald-600 font-normal"> · Next renewal {activeSubscription.expires_at}</span>
                                )}
                            </p>
                        </div>
                        <Link href={route('subscriptions.manage')} className="text-xs font-semibold text-emerald-700 hover:text-emerald-900 transition-colors">
                            Manage →
                        </Link>
                    </div>
                </div>
            )}

            {/* ── Main Content ── */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
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

                        const handleStartTrial = () => {
                            if (selectedItems.length === 0) return;
                            if (confirm(`Start your 14-day free trial for the selected ${selectedItems.length} items?`)) {
                                router.post(route('subscriptions.trial'), { items: selectedItems, is_new_system: isNewSystem });
                            }
                        };

                        const hasOnlyTools = selectedItems.length > 0 && selectedItems.every(id => id.startsWith('tool-'));

                        if (isEligibleForTrial && !hasOnlyTools) {
                            return (
                                <div className="space-y-2">
                                    <Button
                                        onClick={handleStartTrial}
                                        disabled={selectedItems.length === 0}
                                        className={cn(
                                            'w-full h-12 rounded-xl text-sm font-semibold gap-2.5 transition-all duration-200',
                                            selectedItems.length > 0
                                                ? 'bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white shadow-md shadow-indigo-200/50'
                                                : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                        )}
                                    >
                                        <Sparkles className="h-4 w-4" />
                                        Start 14-Day Free Trial
                                    </Button>
                                    <p className="text-[11px] text-center text-slate-400">
                                        No credit card required · Cancel anytime
                                    </p>
                                </div>
                            );
                        }

                        return (
                            <div className="space-y-2.5">
                                <Button
                                    onClick={handleSubscribeWallet}
                                    disabled={selectedItems.length === 0 || !canAfford}
                                    className={cn(
                                        'w-full h-12 rounded-xl text-sm font-semibold gap-2.5 transition-all duration-200',
                                        canAfford && selectedItems.length > 0
                                            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white shadow-md shadow-indigo-200/50'
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
                                    className="w-full h-11 rounded-xl text-sm font-medium gap-2.5 border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 transition-all duration-200"
                                >
                                    <CreditCard className="h-4 w-4 text-slate-400" />
                                    Pay by Card
                                </Button>

                                {!canAfford && selectedItems.length > 0 && (
                                    <Link
                                        href={route('financial.add-balance')}
                                        className="flex items-center justify-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-medium pt-1 transition-colors"
                                    >
                                        Add funds to wallet <ArrowRight className="h-3 w-3" />
                                    </Link>
                                )}
                            </div>
                        );
                    }}
                />
            </div>
        </AuthenticatedLayout>
    );
}
