import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Check, Wallet, CreditCard, ArrowRight, ShieldCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Plan {
    id: number;
    module: string;
    name: string;
    price: number;
    billing: string;
    features: Record<string, string> | string[];
    is_active: boolean;
}

interface ActiveSub {
    id: number;
    plan_id: number;
    plan_name: string;
    status: string;
    expires_at: string;
    auto_renew: boolean;
}

interface PlansProps {
    plans: Plan[];
    activeSubscription: ActiveSub | null;
    walletBalance: number;
    currency: string;
    module: string;
}

// Only paid modules that have real plans in the DB
const PAID_MODULES = [
    { id: 'erp', label: 'Business OS' },
];

// Free modules — always accessible, no subscription needed
const FREE_MODULES = [
    { id: 'freelance', label: 'Freelance Hub' },
    { id: 'marketing', label: 'Marketing Suite' },
];

export default function Plans({ plans, activeSubscription, walletBalance, currency, module }: PlansProps) {
    const [billing, setBilling] = useState<'monthly' | 'yearly'>('monthly');

    const handleSubscribeWallet = (planId: number) => {
        if (confirm('Subscribe using your wallet balance?')) {
            router.post(route('subscriptions.subscribe'), { plan_id: planId });
        }
    };

    const handleSubscribeKashier = (planId: number) => {
        router.post(route('subscriptions.kashier.checkout'), { plan_id: planId });
    };

    const switchModule = (newModule: string) => {
        router.get(route('subscriptions.plans'), { module: newModule });
    };

    // Filter plans by selected billing cycle
    const visiblePlans = plans.filter(p => p.billing === billing);

    const featuresList = (plan: Plan) =>
        Array.isArray(plan.features)
            ? plan.features
            : Object.values(plan.features);

    // Determine if there are yearly plans available
    const hasYearly  = plans.some(p => p.billing === 'yearly');
    const hasMonthly = plans.some(p => p.billing === 'monthly');

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Pricing" />

            {/* ── Hero ── */}
            <div className="max-w-5xl mx-auto px-4 pt-16 pb-6 text-center">
                <h1 className="text-[2.6rem] font-semibold tracking-tight text-slate-900 leading-tight">
                    Simple, honest pricing.
                </h1>
                <p className="mt-3 text-lg text-slate-500 font-light">
                    One subscription. Full access. Cancel anytime.
                </p>

                {/* Paid Module Switcher — only show modules with real plans */}
                {PAID_MODULES.length > 1 && (
                    <div className="mt-8 inline-flex bg-slate-100 rounded-full p-1 gap-1">
                        {PAID_MODULES.map(m => (
                            <button
                                key={m.id}
                                onClick={() => switchModule(m.id)}
                                className={cn(
                                    'px-5 py-2 rounded-full text-sm font-medium transition-all',
                                    module === m.id
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-700'
                                )}
                            >
                                {m.label}
                            </button>
                        ))}
                    </div>
                )}

                {/* Free Modules — always included, no subscription needed */}
                <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                    {FREE_MODULES.map(m => (
                        <span
                            key={m.id}
                            className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full"
                        >
                            <Check className="h-3 w-3" />
                            {m.label} — Included Free
                        </span>
                    ))}
                </div>

                {/* Billing Toggle — only show if both exist */}
                {hasYearly && hasMonthly && (
                    <div className="mt-5 inline-flex items-center gap-3 text-sm text-slate-500">
                        <button
                            onClick={() => setBilling('monthly')}
                            className={cn('font-medium transition-colors', billing === 'monthly' ? 'text-slate-900' : 'hover:text-slate-700')}
                        >
                            Monthly
                        </button>
                        <button
                            onClick={() => setBilling(billing === 'monthly' ? 'yearly' : 'monthly')}
                            className={cn(
                                'relative w-10 h-5 rounded-full transition-colors',
                                billing === 'yearly' ? 'bg-slate-900' : 'bg-slate-300'
                            )}
                        >
                            <span className={cn(
                                'absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform',
                                billing === 'yearly' ? 'translate-x-5' : ''
                            )} />
                        </button>
                        <button
                            onClick={() => setBilling('yearly')}
                            className={cn('font-medium transition-colors', billing === 'yearly' ? 'text-slate-900' : 'hover:text-slate-700')}
                        >
                            Annual
                            <span className="ml-1.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-600 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-full">
                                Save 2 mo
                            </span>
                        </button>
                    </div>
                )}
            </div>

            {/* ── Active Subscription Banner ── */}
            {activeSubscription && (
                <div className="max-w-5xl mx-auto px-4 mb-4">
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

            {/* ── Plan Cards ── */}
            <div className="max-w-5xl mx-auto px-4 pb-20">
                {visiblePlans.length === 0 ? (
                    <div className="text-center py-24 text-slate-400">
                        <p className="text-lg font-light">No {billing} plans available for this module.</p>
                        {hasMonthly && billing === 'yearly' && (
                            <button onClick={() => setBilling('monthly')} className="mt-3 text-sm text-slate-600 underline underline-offset-2">
                                View monthly plans
                            </button>
                        )}
                    </div>
                ) : (
                    <div className={cn(
                        'grid gap-5',
                        visiblePlans.length === 1 ? 'grid-cols-1 max-w-sm mx-auto' :
                        visiblePlans.length === 2 ? 'grid-cols-1 md:grid-cols-2 max-w-2xl mx-auto' :
                        'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
                    )}>
                        {visiblePlans.map((plan) => {
                            const isCurrentPlan = activeSubscription?.plan_id === plan.id;
                            const canAfford = walletBalance >= plan.price;
                            const features = featuresList(plan);

                            return (
                                <div
                                    key={plan.id}
                                    className={cn(
                                        'relative flex flex-col rounded-3xl border bg-white overflow-hidden transition-shadow',
                                        isCurrentPlan
                                            ? 'border-slate-900 shadow-lg shadow-slate-900/10'
                                            : 'border-slate-200 hover:shadow-md hover:shadow-slate-100'
                                    )}
                                >
                                    {/* Current Plan label */}
                                    {isCurrentPlan && (
                                        <div className="absolute top-0 inset-x-0 h-1 bg-slate-900 rounded-t-3xl" />
                                    )}

                                    <div className="p-8 flex-1">
                                        {/* Plan name + badge */}
                                        <div className="flex items-start justify-between gap-2">
                                            <h2 className="text-base font-semibold text-slate-900">{plan.name}</h2>
                                            {isCurrentPlan && (
                                                <span className="shrink-0 text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-1 rounded-full">
                                                    Current
                                                </span>
                                            )}
                                        </div>

                                        {/* Price */}
                                        <div className="mt-5 mb-6">
                                            <div className="flex items-baseline gap-1">
                                                <span className="text-5xl font-semibold tracking-tight text-slate-900">
                                                    {plan.price % 1 === 0
                                                        ? `${Math.round(plan.price)}`
                                                        : plan.price.toFixed(2)
                                                    }
                                                </span>
                                                <span className="text-sm text-slate-400 ml-0.5">{currency}</span>
                                            </div>
                                            <p className="text-sm text-slate-400 mt-0.5">
                                                per {plan.billing === 'yearly' ? 'year' : 'month'}
                                            </p>
                                        </div>

                                        {/* Divider */}
                                        <div className="border-t border-slate-100 mb-6" />

                                        {/* Features */}
                                        <ul className="space-y-3">
                                            {features.map((f, i) => (
                                                <li key={i} className="flex items-start gap-3 text-sm text-slate-600">
                                                    <Check className="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
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
                                        ) : (
                                            <>
                                                <Button
                                                    onClick={() => handleSubscribeWallet(plan.id)}
                                                    disabled={!canAfford}
                                                    className={cn(
                                                        'w-full h-11 rounded-xl text-sm font-medium gap-2 transition-all',
                                                        canAfford
                                                            ? 'bg-slate-900 hover:bg-slate-800 text-white'
                                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                                    )}
                                                >
                                                    <Wallet className="h-4 w-4" />
                                                    {canAfford ? 'Subscribe with Wallet' : `Need ${currency} ${plan.price}`}
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

                {/* Wallet balance chip */}
                <div className="mt-10 flex items-center justify-center gap-2 text-sm text-slate-400">
                    <Wallet className="h-3.5 w-3.5" />
                    <span>Wallet balance: <strong className="text-slate-600">{walletBalance} {currency}</strong></span>
                    <span className="text-slate-200">·</span>
                    <Link href={route('financial.add-balance')} className="text-slate-500 hover:text-slate-800 underline underline-offset-2">
                        Add funds
                    </Link>
                </div>

                {/* Trust line */}
                <p className="mt-4 text-center text-xs text-slate-400 flex items-center justify-center gap-1.5">
                    <ShieldCheck className="h-3.5 w-3.5" />
                    Cancel anytime · Instant activation · 7-day money-back guarantee
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
