import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Check, Wallet, CreditCard, ArrowRight, ShieldCheck, HelpCircle } from 'lucide-react';
import { AppPage } from '@/Components/ui/AppPage';
import { PageHeader } from '@/Components/ui/PageHeader';
import { SectionCard } from '@/Components/ui/SectionCard';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
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

export default function Plans({ plans, activeSubscription, walletBalance, currency, module }: PlansProps) {
    
    const handleSubscribeWallet = (planId: number) => {
        if (confirm("Are you sure you want to subscribe to this plan using your wallet balance? The amount will be deducted directly.")) {
            router.post(route('subscriptions.subscribe'), { plan_id: planId });
        }
    };

    const handleSubscribeKashier = (planId: number) => {
        router.post(route('subscriptions.kashier.checkout'), { plan_id: planId });
    };

    const switchModule = (newModule: string) => {
        router.get(route('subscriptions.plans'), { module: newModule });
    };

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Pricing Plans" />

            <AppPage>
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-text-primary font-sans">Choose Your Subscription</h2>
                        <p className="text-sm text-text-muted mt-1">Unlock production-grade modular workflows for your business.</p>
                    </div>

                    <div className="flex bg-surface-raised p-1 rounded-lg w-full md:w-auto shadow-sm border border-border">
                        <button
                            onClick={() => switchModule('erp')}
                            className={cn("flex-1 md:flex-none px-4 py-2 rounded-md text-xs font-semibold transition-all", module === 'erp' ? 'bg-surface shadow-sm text-text-primary' : 'text-text-muted hover:text-text-primary')}
                        >
                            Business OS (ERP)
                        </button>
                        <button
                            onClick={() => switchModule('freelance')}
                            className={cn("flex-1 md:flex-none px-4 py-2 rounded-md text-xs font-semibold transition-all", module === 'freelance' ? 'bg-surface shadow-sm text-text-primary' : 'text-text-muted hover:text-text-primary')}
                        >
                            Freelancer Hub
                        </button>
                        <button
                            onClick={() => switchModule('marketing')}
                            className={cn("flex-1 md:flex-none px-4 py-2 rounded-md text-xs font-semibold transition-all", module === 'marketing' ? 'bg-surface shadow-sm text-text-primary' : 'text-text-muted hover:text-text-primary')}
                        >
                            Marketing Suite
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <SectionCard className="lg:col-span-2 relative overflow-hidden bg-surface">
                        <div className="absolute right-0 top-0 translate-x-6 -translate-y-6 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none" />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
                            <div className="space-y-1">
                                <h3 className="font-bold text-sm flex items-center gap-2 text-text-primary uppercase tracking-wider">
                                    <ShieldCheck className="h-4 w-4 text-primary" />
                                    Current Module Status
                                </h3>
                                {activeSubscription ? (
                                    <div className="space-y-1 mt-2">
                                        <p className="text-sm font-medium text-text-secondary">
                                            You are currently subscribed to <span className="text-primary font-semibold">{activeSubscription.plan_name}</span>.
                                        </p>
                                        <p className="text-xs text-text-muted">
                                            Active • Renews on {activeSubscription.expires_at}
                                        </p>
                                    </div>
                                ) : (
                                    <p className="text-sm text-text-muted mt-2">
                                        No active subscription for {module.toUpperCase()} module. Subscribing will unlock full access.
                                    </p>
                                )}
                            </div>

                            {activeSubscription && (
                                <Link href={route('subscriptions.manage')}>
                                    <Button variant="outline" className="shadow-none gap-2 text-xs h-9 bg-surface hover:bg-surface-raised border-border">
                                        Manage Subscriptions <ArrowRight className="h-3.5 w-3.5" />
                                    </Button>
                                </Link>
                            )}
                        </div>
                    </SectionCard>

                    <SectionCard className="relative overflow-hidden bg-surface">
                        <div className="flex justify-between items-start mb-6">
                            <div className="space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-text-muted">Wallet Balance</span>
                                <h3 className="text-2xl font-bold tracking-tight text-primary font-sans">
                                    <CurrencyDisplay amount={walletBalance} currency={currency} />
                                </h3>
                            </div>
                            <div className="p-2.5 bg-surface-raised rounded-xl">
                                <Wallet className="h-5 w-5 text-primary" />
                            </div>
                        </div>
                        <Link href={route('financial.add-balance')}>
                            <Button size="sm" className="w-full shadow-none bg-primary hover:bg-primary-hover text-white font-medium h-9 text-xs">
                                + Add Funds
                            </Button>
                        </Link>
                    </SectionCard>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {plans.map((plan) => {
                        const isCurrentPlan = activeSubscription?.plan_id === plan.id;
                        const hasSufficientWallet = walletBalance >= plan.price;
                        const featuresList = Array.isArray(plan.features) 
                            ? plan.features 
                            : Object.entries(plan.features).map(([key, val]) => `${key}: ${val}`);

                        return (
                            <div 
                                key={plan.id} 
                                className={cn(
                                    "flex flex-col relative shadow-sm border bg-surface rounded-xl overflow-hidden transition-all hover:shadow",
                                    isCurrentPlan ? 'ring-2 ring-primary border-transparent' : 'border-border hover:border-border-strong'
                                )}
                            >
                                {isCurrentPlan && (
                                    <span className="absolute top-0 right-6 -translate-y-1/2 bg-primary text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow">
                                        Current Plan
                                    </span>
                                )}

                                <div className="p-6">
                                    <h3 className="text-xl font-bold tracking-tight text-text-primary font-sans">{plan.name}</h3>
                                    <p className="text-[11px] uppercase font-bold text-text-muted mt-1 tracking-wider">
                                        {plan.module} MODULE
                                    </p>
                                    <div className="mt-5 flex items-baseline gap-1">
                                        <span className="text-4xl font-extrabold tracking-tight text-text-primary font-sans">
                                            <CurrencyDisplay amount={plan.price} currency={currency} />
                                        </span>
                                        <span className="text-sm font-medium text-text-muted">
                                            /{plan.billing === 'yearly' ? 'yr' : 'mo'}
                                        </span>
                                    </div>
                                </div>

                                <div className="px-6 pb-6 flex-1">
                                    <div className="border-t border-border/40 pt-6">
                                        <h4 className="text-[11px] font-bold text-text-primary uppercase tracking-wider mb-4">Included Features</h4>
                                        <ul className="space-y-3">
                                            {featuresList.map((feature, i) => (
                                                <li key={i} className="flex items-start gap-3 text-sm text-text-secondary font-medium">
                                                    <Check className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                                                    <span>{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>

                                <div className="p-6 border-t border-border/40 bg-surface flex flex-col gap-3">
                                    {isCurrentPlan ? (
                                        <Button disabled className="w-full shadow-none bg-surface-raised text-text-muted cursor-not-allowed h-9 text-xs">
                                            Active Subscribed Plan
                                        </Button>
                                    ) : (
                                        <>
                                            <Button
                                                onClick={() => handleSubscribeWallet(plan.id)}
                                                disabled={!hasSufficientWallet}
                                                className={cn(
                                                    "w-full shadow-none gap-2 font-semibold h-9 text-xs transition-colors",
                                                    hasSufficientWallet ? 'bg-primary hover:bg-primary-hover text-white' : 'opacity-65 cursor-not-allowed bg-surface-raised text-text-muted'
                                                )}
                                            >
                                                <Wallet className="h-4 w-4" />
                                                Subscribe via Wallet
                                            </Button>

                                            <Button
                                                onClick={() => handleSubscribeKashier(plan.id)}
                                                variant="outline"
                                                className="w-full shadow-none gap-2 font-semibold hover:bg-surface-raised border-border h-9 text-xs bg-surface"
                                            >
                                                <CreditCard className="h-4 w-4 text-text-muted" />
                                                Checkout via Kashier Card
                                            </Button>
                                            
                                            {!hasSufficientWallet && (
                                                <p className="text-[10px] text-amber-600 text-center font-semibold mt-1">
                                                    * Low wallet balance. Use Kashier checkout or add funds.
                                                </p>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="border-t border-border/40 pt-10 mt-16 max-w-3xl mx-auto space-y-8">
                    <h3 className="text-lg font-bold text-center flex items-center justify-center gap-2 text-text-primary">
                        <HelpCircle className="h-5 w-5 text-primary" /> Frequently Asked Questions
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div className="space-y-2">
                            <h4 className="font-semibold text-sm text-text-primary">How does wallet billing work?</h4>
                            <p className="text-xs text-text-muted leading-relaxed">You can deposit funds securely via credit/debit card, and use your internal wallet balance to instantly pay or auto-renew any module subscription without leaving the app.</p>
                        </div>
                        <div className="space-y-2">
                            <h4 className="font-semibold text-sm text-text-primary">Can I switch plans or cancel anytime?</h4>
                            <p className="text-xs text-text-muted leading-relaxed">Yes! You can cancel or upgrade your subscription from the Subscription Management panel. Upgrades take effect immediately, while cancellations expire at the end of the billing cycle.</p>
                        </div>
                    </div>
                </div>
            </AppPage>
        </AuthenticatedLayout>
    );
}
