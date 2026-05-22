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
    starter:        { accent: 'emerald',  gradient: 'from-emerald-500 to-teal-600',   badge: 'bg-emerald-100 text-emerald-700', icon: MessageSquare },
    professional:   { accent: 'indigo',   gradient: 'from-indigo-500 to-violet-600',  badge: 'bg-indigo-100 text-indigo-700',   icon: Wrench },
    business_suite: { accent: 'amber',    gradient: 'from-amber-500 to-orange-600',   badge: 'bg-amber-100 text-amber-700',     icon: Building2 },
    custom:         { accent: 'fuchsia',  gradient: 'from-fuchsia-500 to-purple-600', badge: 'bg-fuchsia-100 text-fuchsia-700', icon: Sparkles },
};

export default function Plans({ plans, serviceItems, activeSubscription, walletBalance, currency }: PlansProps) {
    const [billing, setBilling] = useState<'monthly' | 'yearly'>('monthly');
    const [selectedCustomItems, setSelectedCustomItems] = useState<string[]>([]);
    const [isCustomExpanded, setIsCustomExpanded] = useState(false);

    const fixedPlans = plans.filter(p => !p.is_custom);
    const customPlan = plans.find(p => p.is_custom);

    const hasYearly = plans.some(p => p.yearly_price > 0);
    const hasMonthly = plans.some(p => p.monthly_price > 0);

    const customTotal = useMemo(() => {
        return serviceItems
            .filter(item => selectedCustomItems.includes(item.slug))
            .reduce((sum, item) => sum + (billing === 'yearly' ? item.yearly_price : item.monthly_price), 0);
    }, [selectedCustomItems, billing, serviceItems]);

    const toggleCustomItem = (slug: string) => {
        setSelectedCustomItems(prev =>
            prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]
        );
    };

    const handleSubscribeWallet = (planId: number) => {
        if (confirm('Subscribe using your wallet balance?')) {
            router.post(route('subscriptions.subscribe'), { plan_id: planId, billing_cycle: billing });
        }
    };

    const handleSubscribeKashier = (planId: number) => {
        router.post(route('subscriptions.kashier.checkout'), { plan_id: planId, billing_cycle: billing });
    };

    const handleSubscribeCustomWallet = () => {
        if (selectedCustomItems.length === 0) return;
        if (confirm(`Subscribe to a custom plan for ${currency} ${customTotal.toFixed(2)}/${billing === 'yearly' ? 'year' : 'month'}?`)) {
            router.post(route('subscriptions.subscribe-custom'), { items: selectedCustomItems, billing_cycle: billing });
        }
    };

    const getPrice = (plan: Plan) => billing === 'yearly' ? plan.yearly_price : plan.monthly_price;

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
                {hasYearly && hasMonthly && (
                    <div className="mt-8 inline-flex items-center gap-3 text-sm text-slate-500">
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
                {fixedPlans.length === 0 && !customPlan ? (
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
                            const isPopular = plan.slug === 'professional';

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
                                                    {price % 1 === 0 ? Math.round(price) : price.toFixed(2)}
                                                </span>
                                                <span className="text-sm text-slate-400 ml-0.5">{currency}</span>
                                            </div>
                                            <p className="text-sm text-slate-400 mt-0.5">
                                                per {billing === 'yearly' ? 'year' : 'month'}
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

            {/* ── Custom Plan Builder ── */}
            {customPlan && serviceItems.length > 0 && (
                <div className="max-w-6xl mx-auto px-4 pb-20">
                    <div className="mt-8 border border-dashed border-slate-300 rounded-3xl bg-gradient-to-br from-white to-slate-50/80 overflow-hidden">
                        {/* Header */}
                        <button
                            onClick={() => setIsCustomExpanded(!isCustomExpanded)}
                            className="w-full flex items-center justify-between p-8 text-left hover:bg-slate-50/50 transition-colors"
                        >
                            <div className="flex items-center gap-4">
                                <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-purple-600 flex items-center justify-center shadow-sm">
                                    <Sparkles className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900">Build Your Own Plan</h3>
                                    <p className="text-sm text-slate-500 mt-0.5">
                                        Pick only the modules and tools you need. Price updates in real-time.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                {selectedCustomItems.length > 0 && (
                                    <div className="text-right mr-2">
                                        <p className="text-2xl font-bold text-slate-900">
                                            {customTotal.toFixed(2)} <span className="text-sm font-normal text-slate-400">{currency}</span>
                                        </p>
                                        <p className="text-xs text-slate-400">/{billing === 'yearly' ? 'year' : 'month'}</p>
                                    </div>
                                )}
                                <div className={cn(
                                    'w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center transition-transform',
                                    isCustomExpanded ? 'rotate-45 bg-slate-100' : 'bg-white'
                                )}>
                                    <Plus className="w-4 h-4 text-slate-500" />
                                </div>
                            </div>
                        </button>

                        {/* Expandable items */}
                        {isCustomExpanded && (
                            <div className="px-8 pb-8 border-t border-slate-100">
                                {/* Modules */}
                                {serviceItems.filter(i => i.type === 'module').length > 0 && (
                                    <div className="mt-6">
                                        <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                                            <Building2 className="h-3.5 w-3.5" /> Platform Modules
                                        </h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            {serviceItems.filter(i => i.type === 'module').map(item => {
                                                const isSelected = selectedCustomItems.includes(item.slug);
                                                const itemPrice = billing === 'yearly' ? item.yearly_price : item.monthly_price;
                                                return (
                                                    <button
                                                        key={item.slug}
                                                        onClick={() => toggleCustomItem(item.slug)}
                                                        className={cn(
                                                            'flex items-center justify-between p-4 rounded-xl border text-left transition-all',
                                                            isSelected
                                                                ? 'border-indigo-200 bg-indigo-50/50 ring-1 ring-indigo-100'
                                                                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'
                                                        )}
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className={cn(
                                                                'w-8 h-8 rounded-lg flex items-center justify-center transition-colors',
                                                                isSelected ? 'bg-indigo-100' : 'bg-slate-100'
                                                            )}>
                                                                {isSelected
                                                                    ? <Check className="w-4 h-4 text-indigo-600" />
                                                                    : <Plus className="w-4 h-4 text-slate-400" />
                                                                }
                                                            </div>
                                                            <div>
                                                                <p className={cn('text-sm font-medium', isSelected ? 'text-indigo-900' : 'text-slate-900')}>
                                                                    {item.name}
                                                                </p>
                                                                {item.description && (
                                                                    <p className="text-xs text-slate-500 mt-0.5 line-clamp-1">{item.description}</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <span className={cn(
                                                            'text-sm font-semibold shrink-0 ml-3',
                                                            isSelected ? 'text-indigo-600' : 'text-slate-500'
                                                        )}>
                                                            {itemPrice.toFixed(2)} {currency}
                                                        </span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* Tools */}
                                {serviceItems.filter(i => i.type === 'tool').length > 0 && (
                                    <div className="mt-6">
                                        <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                                            <Wrench className="h-3.5 w-3.5" /> Tools & Plugins
                                        </h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            {serviceItems.filter(i => i.type === 'tool').map(item => {
                                                const isSelected = selectedCustomItems.includes(item.slug);
                                                const itemPrice = billing === 'yearly' ? item.yearly_price : item.monthly_price;
                                                return (
                                                    <button
                                                        key={item.slug}
                                                        onClick={() => toggleCustomItem(item.slug)}
                                                        className={cn(
                                                            'flex items-center justify-between p-4 rounded-xl border text-left transition-all',
                                                            isSelected
                                                                ? 'border-fuchsia-200 bg-fuchsia-50/50 ring-1 ring-fuchsia-100'
                                                                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'
                                                        )}
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className={cn(
                                                                'w-8 h-8 rounded-lg flex items-center justify-center transition-colors',
                                                                isSelected ? 'bg-fuchsia-100' : 'bg-slate-100'
                                                            )}>
                                                                {isSelected
                                                                    ? <Check className="w-4 h-4 text-fuchsia-600" />
                                                                    : <Plus className="w-4 h-4 text-slate-400" />
                                                                }
                                                            </div>
                                                            <div>
                                                                <p className={cn('text-sm font-medium', isSelected ? 'text-fuchsia-900' : 'text-slate-900')}>
                                                                    {item.name}
                                                                </p>
                                                                {item.description && (
                                                                    <p className="text-xs text-slate-500 mt-0.5 line-clamp-1">{item.description}</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <span className={cn(
                                                            'text-sm font-semibold shrink-0 ml-3',
                                                            isSelected ? 'text-fuchsia-600' : 'text-slate-500'
                                                        )}>
                                                            {itemPrice.toFixed(2)} {currency}
                                                        </span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* Custom Summary + CTA */}
                                {selectedCustomItems.length > 0 && (
                                    <div className="mt-8 flex items-center justify-between bg-white border border-slate-200 rounded-2xl p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-100 to-purple-100 border border-fuchsia-200 flex items-center justify-center">
                                                <Calculator className="h-5 w-5 text-fuchsia-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-slate-900">
                                                    {selectedCustomItems.length} item{selectedCustomItems.length > 1 ? 's' : ''} selected
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    Total: <strong className="text-slate-900">{customTotal.toFixed(2)} {currency}</strong>
                                                    <span className="text-slate-400"> /{billing === 'yearly' ? 'year' : 'month'}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                onClick={handleSubscribeCustomWallet}
                                                disabled={walletBalance < customTotal}
                                                className={cn(
                                                    'h-10 rounded-xl text-sm font-medium gap-2',
                                                    walletBalance >= customTotal
                                                        ? 'bg-gradient-to-r from-fuchsia-600 to-purple-600 hover:from-fuchsia-700 hover:to-purple-700 text-white'
                                                        : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                                )}
                                            >
                                                <Wallet className="h-4 w-4" />
                                                {walletBalance >= customTotal ? 'Subscribe with Wallet' : `Need ${currency} ${customTotal.toFixed(2)}`}
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}

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
