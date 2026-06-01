import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, LogIn, Zap, Lock } from 'lucide-react';

interface PricingPlan {
    id: number;
    name: string;
    price_monthly: number;
    price_yearly: number;
    features: string[];
    is_popular: boolean;
    yearly_savings: number;
}

interface PricingPanelProps {
    plans: PricingPlan[];
    toolSlug: string;
    isAuthed: boolean;
}

export function PricingPanel({ plans, toolSlug, isAuthed }: PricingPanelProps) {
    const [cycle, setCycle] = useState<'monthly' | 'yearly'>('monthly');

    if (!plans || plans.length === 0) return null;

    const handleSubscribe = (planId: number) => {
        if (!isAuthed) {
            router.visit(route('login'));
            return;
        }
        router.visit(route('tools.checkout', { slug: toolSlug, planId }));
    };

    return (
        <div className="space-y-4">
            {/* Billing toggle */}
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold text-slate-900">{__('general.choose_a_plan')}</h3>
                <div className="flex items-center bg-slate-100 rounded-lg p-0.5 gap-0.5">
                    {(['monthly', 'yearly'] as const).map(c => (
                        <button
                            key={c}
                            onClick={() => setCycle(c)}
                            className={`px-3 py-1.5 rounded-md text-xs font-medium capitalize transition-all duration-150 ${
                                cycle === c
                                    ? 'bg-white shadow-sm text-slate-900'
                                    : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            {c}
                            {c === 'yearly' && (
                                <span className={`ml-1 ${cycle === 'yearly' ? 'text-emerald-600' : 'text-slate-400'}`}>
                                    −20%
                                </span>
                            )}
                        </button>
                    ))}
                </div>
            </div>

            {/* Plan cards */}
            <div className="space-y-3">
                {plans.map(plan => {
                    // Derive yearly per-month price: use stored value or fall back to 10× monthly (2 months free)
                    const effectiveYearlyTotal = plan.price_yearly > 0
                        ? plan.price_yearly
                        : plan.price_monthly * 10;
                    const price = cycle === 'monthly'
                        ? plan.price_monthly
                        : Math.round(effectiveYearlyTotal / 12);

                    // Compute savings % inline so it's always accurate
                    const computedSavings = plan.price_monthly > 0
                        ? Math.round((1 - effectiveYearlyTotal / (plan.price_monthly * 12)) * 100)
                        : (plan.yearly_savings ?? 0);
                    const isPopular = plan.is_popular;

                    return (
                        <div
                            key={plan.id}
                            className={`relative rounded-xl overflow-hidden transition-all duration-150 ${
                                isPopular
                                    ? 'bg-slate-900 text-white ring-1 ring-slate-700'
                                    : 'bg-white border border-slate-200 hover:border-slate-300'
                            }`}
                        >
                            {isPopular && (
                                <div className="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-pink-500" />
                            )}

                            <div className="p-4 space-y-3">
                                {/* Plan name + popular badge */}
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className={`font-semibold text-sm ${isPopular ? 'text-white' : 'text-slate-900'}`}>
                                            {plan.name}
                                        </p>
                                    </div>
                                    {isPopular && (
                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-400 text-amber-900">
                                            <Zap className="h-3 w-3" />
                                            Popular
                                        </span>
                                    )}
                                </div>

                                {/* Price */}
                                <div className="flex items-end gap-1">
                                    <span className={`text-2xl font-bold tracking-tight ${isPopular ? 'text-white' : 'text-slate-900'}`}>
                                        ${price}
                                    </span>
                                    <span className={`text-xs mb-1 ${isPopular ? 'text-slate-400' : 'text-slate-500'}`}>
                                        /mo
                                    </span>
                                    {cycle === 'yearly' && computedSavings > 0 && (
                                        <span className={`text-xs mb-1 ml-1 font-medium ${isPopular ? 'text-emerald-400' : 'text-emerald-600'}`}>
                                            Save {computedSavings}%
                                        </span>
                                    )}
                                </div>

                                {/* Features */}
                                <ul className="space-y-1.5">
                                    {(Array.isArray(plan.features) ? plan.features : []).slice(0, 4).map((f, i) => (
                                        <li key={i} className={`flex items-start gap-2 text-xs ${isPopular ? 'text-slate-300' : 'text-slate-600'}`}>
                                            <CheckCircle2 className={`h-3.5 w-3.5 flex-shrink-0 mt-0.5 ${isPopular ? 'text-emerald-400' : 'text-emerald-500'}`} />
                                            {f}
                                        </li>
                                    ))}
                                </ul>

                                {/* CTA */}
                                <Button
                                    className={`w-full text-sm h-9 ${
                                        isPopular
                                            ? 'bg-white text-slate-900 hover:bg-slate-100'
                                            : 'bg-slate-900 text-white hover:bg-slate-800'
                                    }`}
                                    onClick={() => handleSubscribe(plan.id)}
                                >
                                    {isAuthed ? (
                                        `Get ${plan.name}`
                                    ) : (
                                        <>
                                            <LogIn className="h-3.5 w-3.5 mr-1.5" />{__('general.sign_in_to_subscribe')}</>
                                    )}
                                </Button>
                            </div>
                        </div>
                    );
                })}
            </div>

            {!isAuthed && (
                <div className="flex items-center gap-2 p-3 rounded-lg bg-slate-50 border border-slate-200">
                    <Lock className="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                    <p className="text-xs text-slate-500">
                        <button className="underline text-slate-700 hover:text-slate-900" onClick={() => router.visit(route('register'))}>{__('general.create_a_free_account')}</button>{__('general.to_subscribe_and_download')}</p>
                </div>
            )}
        </div>
    );
}
