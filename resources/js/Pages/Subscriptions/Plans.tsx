import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import {
    Check, Wallet, CreditCard, ArrowRight, ShieldCheck,
    Sparkles, Building2, Wrench, MessageSquare, Zap,
    Crown, Plus, Minus, Calculator,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface Plan {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    monthly_price: number;
    yearly_price: number;
    included_modules: string[];
    included_tools: string[];
    features: string[];
    is_custom: boolean;
    prices?: {
        '3_months': number;
        '6_months': number;
        '1_year': number;
        '3_years': number;
    };
}

interface ServiceItem {
    slug: string;
    name: string;
    type: 'module' | 'tool';
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
    plans: Plan[];
    serviceItems: ServiceItem[];
    activeSubscription: ActiveSub | null;
    walletBalance: number;
    currency: string;
}

const TIER_STYLES: Record<string, { accent: string; gradient: string; badge: string; icon: React.ElementType }> = {
    trial:  { accent: 'slate',    gradient: 'from-slate-500 to-gray-600',     badge: 'bg-slate-100 text-slate-700',     icon: Zap },
    go:     { accent: 'emerald',  gradient: 'from-emerald-500 to-teal-600',   badge: 'bg-emerald-100 text-emerald-700', icon: MessageSquare },
    plus:   { accent: 'indigo',   gradient: 'from-indigo-500 to-violet-600',  badge: 'bg-indigo-100 text-indigo-700',   icon: Wrench },
    pro:    { accent: 'amber',    gradient: 'from-amber-500 to-orange-600',   badge: 'bg-amber-100 text-amber-700',     icon: Building2 },
    custom: { accent: 'fuchsia',  gradient: 'from-fuchsia-500 to-purple-600', badge: 'bg-fuchsia-100 text-fuchsia-700', icon: Sparkles },
};

export default function Plans({ plans, serviceItems, activeSubscription, walletBalance, currency }: PlansProps) {
    const [billing, setBilling] = useState<'3_months' | '6_months' | '1_year' | '3_years'>('3_months');

    const fixedPlans = plans.filter(p => !p.is_custom);

    const handleSubscribeWallet = (planId: number) => {
        if (confirm('Subscribe using your wallet balance?')) {
            router.post(route('subscriptions.subscribe'), { plan_id: planId, billing_cycle: billing });
        }
    };

    const handleSubscribeKashier = (planId: number) => {
        router.post(route('subscriptions.kashier.checkout'), { plan_id: planId, billing_cycle: billing });
    };

    const getPrice = (plan: Plan) => {
        if (plan.prices && plan.prices[billing] !== undefined) {
            return Number(plan.prices[billing]) || 0;
        }
        const months = {
            '3_months': 3,
            '6_months': 6,
            '1_year': 12,
            '3_years': 36
        }[billing] || 3;
        return (Number(plan.monthly_price) || 0) * months;
    };

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Subscription Plans" />

            {/* ── Hero ── */}
            <div className="max-w-6xl mx-auto px-4 pt-16 pb-8 text-center">
                <div className="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-50 to-violet-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-4 py-1.5 rounded-full mb-6">
                    <Crown className="h-3.5 w-3.5" /> Unified Platform Plans
                </div>
                <h1 className="text-[2.6rem] font-semibold tracking-tight text-slate-900 leading-tight">
                    One plan. Everything you need.
                </h1>
                <p className="mt-3 text-lg text-slate-500 font-light max-w-xl mx-auto">
                    Choose a plan that fits your business. Upgrade, downgrade, or build your own — anytime.
                </p>

                {/* ── Billing Toggle ── */}
                <div className="mt-8 inline-flex items-center bg-slate-100 p-1.5 rounded-full text-sm text-slate-500">
                    {[
                        { id: '3_months', label: '3 Months' },
                        { id: '6_months', label: '6 Months' },
                        { id: '1_year', label: '1 Year' },
                        { id: '3_years', label: '3 Years' },
                    ].map(option => (
                        <button
                            key={option.id}
                            onClick={() => setBilling(option.id as any)}
                            className={cn(
                                'px-5 py-2 rounded-full font-medium transition-all duration-200',
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

            {/* ── Active Subscription Banner ── */}
            {activeSubscription && (
                <div className="max-w-6xl mx-auto px-4 mb-6">
                    <div className="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-2xl px-6 py-3.5">
                        <div className="flex items-center gap-2.5 text-sm text-emerald-800">
                            <ShieldCheck className="h-4 w-4 text-emerald-500" />
                            <span>
                                You're subscribed to <strong>{activeSubscription.plan_name}</strong>
                                {activeSubscription.expires_at && ` · renews ${activeSubscription.expires_at}`}
                            </span>
                        </div>
                        <Link href={route('subscriptions.manage')}>
                            <Button variant="ghost" size="sm" className="text-emerald-700 hover:bg-emerald-100 h-8 text-xs gap-1.5">
                                Manage <ArrowRight className="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                    </div>
                </div>
            )}

            {/* ── Plan Cards Grid ── */}
            <div className="max-w-6xl mx-auto px-4 pb-6">
                {fixedPlans.length === 0 ? (
                    <div className="text-center py-24 text-slate-400">
                        <p className="text-lg font-light">No plans available yet. Check back soon.</p>
                    </div>
                ) : (
                    <div className={cn(
                        'grid gap-5',
                        fixedPlans.length <= 2 ? 'grid-cols-1 md:grid-cols-2 max-w-3xl mx-auto' :
                        'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
                    )}>
                        {fixedPlans.map((plan) => {
                            const style = TIER_STYLES[plan.slug] || TIER_STYLES.starter;
                            const TierIcon = style.icon;
                            const price = getPrice(plan);
                            const isCurrentPlan = activeSubscription?.plan_slug === plan.slug;
                            const canAfford = walletBalance >= price;
                            const isPopular = plan.slug === 'plus';

                            return (
                                <div
                                    key={plan.id}
                                    className={cn(
                                        'relative flex flex-col rounded-3xl border bg-white overflow-hidden transition-all duration-300',
                                        isCurrentPlan
                                            ? 'border-slate-900 shadow-lg shadow-slate-900/10 ring-1 ring-slate-900/5'
                                            : isPopular
                                                ? 'border-indigo-200 shadow-md shadow-indigo-100/50 hover:shadow-lg'
                                                : 'border-slate-200 hover:shadow-md hover:shadow-slate-100'
                                    )}
                                >
                                    {/* Popular / Current badge */}
                                    {isPopular && !isCurrentPlan && (
                                        <div className={cn('absolute top-0 inset-x-0 h-1 bg-gradient-to-r', style.gradient)} />
                                    )}
                                    {isCurrentPlan && (
                                        <div className="absolute top-0 inset-x-0 h-1 bg-slate-900" />
                                    )}

                                    <div className="p-8 flex-1">
                                        {/* Header */}
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex items-center gap-2.5">
                                                <div className={cn('w-9 h-9 rounded-xl flex items-center justify-center bg-gradient-to-br', style.gradient)}>
                                                    <TierIcon className="w-4.5 h-4.5 text-white" />
                                                </div>
                                                <h2 className="text-base font-semibold text-slate-900">{plan.name}</h2>
                                            </div>
                                            {isCurrentPlan && (
                                                <span className="shrink-0 text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-1 rounded-full">
                                                    Current
                                                </span>
                                            )}
                                            {isPopular && !isCurrentPlan && (
                                                <span className={cn('shrink-0 text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-full', style.badge)}>
                                                    Popular
                                                </span>
                                            )}
                                        </div>

                                        {/* Description */}
                                        {plan.description && (
                                            <p className="mt-3 text-sm text-slate-500 leading-relaxed">{plan.description}</p>
                                        )}

                                        {/* Price */}
                                        <div className="mt-5 mb-6">
                                            <div className="flex items-baseline gap-1">
                                                <span className="text-4xl font-semibold tracking-tight text-slate-900">
                                                    {plan.slug === 'trial' ? '0' : (Number(price || 0) % 1 === 0 ? Math.round(Number(price || 0)) : Number(price || 0).toFixed(2))}
                                                </span>
                                                <span className="text-sm text-slate-400 ml-0.5">{currency}</span>
                                            </div>
                                            <p className="text-sm text-slate-400 mt-0.5 capitalize">
                                                {plan.slug === 'trial' ? 'Valid for 1 Day' : `billed every ${billing.replace('_', ' ')}`}
                                            </p>
                                        </div>

                                        {/* Divider */}
                                        <div className="border-t border-slate-100 mb-5" />

                                        {/* Features */}
                                        <ul className="space-y-2.5">
                                            {plan.features.map((f, i) => (
                                                <li key={i} className="flex items-start gap-2.5 text-sm text-slate-600">
                                                    <Check className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                                                    <span>{f}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    {/* CTA */}
                                    <div className="px-8 pb-8 space-y-2.5">
                                        {isCurrentPlan ? (
                                            <div className="w-full h-11 rounded-xl bg-slate-100 flex items-center justify-center text-sm font-medium text-slate-400">
                                                Active plan
                                            </div>
                                        ) : plan.slug === 'trial' ? (
                                            <Button
                                                onClick={() => handleSubscribeWallet(plan.id)}
                                                className="w-full h-11 rounded-xl text-sm font-medium gap-2 transition-all bg-slate-900 hover:bg-slate-800 text-white"
                                            >
                                                <Zap className="h-4 w-4" />
                                                Start 1-Day Free Trial
                                            </Button>
                                        ) : (
                                            <>
                                                <Button
                                                    onClick={() => handleSubscribeWallet(plan.id)}
                                                    disabled={!canAfford}
                                                    className={cn(
                                                        'w-full h-11 rounded-xl text-sm font-medium gap-2 transition-all',
                                                        canAfford
                                                            ? isPopular
                                                                ? 'bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white shadow-sm'
                                                                : 'bg-slate-900 hover:bg-slate-800 text-white'
                                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                                    )}
                                                >
                                                    <Wallet className="h-4 w-4" />
                                                    {canAfford ? 'Subscribe with Wallet' : `Need ${currency} ${price}`}
                                                </Button>

                                                <Button
                                                    onClick={() => handleSubscribeKashier(plan.id)}
                                                    variant="outline"
                                                    className="w-full h-11 rounded-xl text-sm font-medium gap-2 border-slate-200 hover:bg-slate-50 text-slate-700"
                                                >
                                                    <CreditCard className="h-4 w-4 text-slate-400" />
                                                    Pay by Card
                                                </Button>

                                                {!canAfford && (
                                                    <Link
                                                        href={route('financial.add-balance')}
                                                        className="flex items-center justify-center gap-1 text-xs text-slate-500 hover:text-slate-700 pt-0.5"
                                                    >
                                                        Add funds to wallet <ArrowRight className="h-3 w-3" />
                                                    </Link>
                                                )}
                                            </>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>



            {/* ── Wallet balance + Trust ── */}
            <div className="max-w-6xl mx-auto px-4 pb-16">
                <div className="flex items-center justify-center gap-2 text-sm text-slate-400">
                    <Wallet className="h-3.5 w-3.5" />
                    <span>Wallet balance: <strong className="text-slate-600">{walletBalance} {currency}</strong></span>
                    <span className="text-slate-200">·</span>
                    <Link href={route('financial.add-balance')} className="text-slate-500 hover:text-slate-800 underline underline-offset-2">
                        Add funds
                    </Link>
                </div>
                <p className="mt-3 text-center text-xs text-slate-400 flex items-center justify-center gap-1.5">
                    <ShieldCheck className="h-3.5 w-3.5" />
                    Cancel anytime · Instant activation · 7-day money-back guarantee
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
