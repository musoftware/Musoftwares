import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Check, Wallet, CreditCard, ArrowRight, ShieldCheck, HelpCircle } from 'lucide-react';

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
    
    const formatMoney = (amount: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    };

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
        <AuthenticatedLayout header="SaaS Modules & Subscriptions">
            <Head title="Pricing Plans" />

            <div className="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                {/* 1. Module Selector Tabs */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b pb-6">
                    <div>
                        <h2 className="text-3xl font-extrabold tracking-tight text-foreground">Choose Your Subscription</h2>
                        <p className="text-sm text-muted-foreground mt-1">Unlock production-grade modular workflows for your business.</p>
                    </div>

                    <div className="flex bg-muted p-1 rounded-xl w-full md:w-auto">
                        <button
                            onClick={() => switchModule('erp')}
                            className={`flex-1 md:flex-none px-5 py-2.5 rounded-lg text-sm font-semibold transition-all ${module === 'erp' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Business OS (ERP)
                        </button>
                        <button
                            onClick={() => switchModule('freelance')}
                            className={`flex-1 md:flex-none px-5 py-2.5 rounded-lg text-sm font-semibold transition-all ${module === 'freelance' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Freelancer Hub
                        </button>
                        <button
                            onClick={() => switchModule('marketing')}
                            className={`flex-1 md:flex-none px-5 py-2.5 rounded-lg text-sm font-semibold transition-all ${module === 'marketing' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Marketing Suite
                        </button>
                    </div>
                </div>

                {/* 2. Top Info Row: Wallet & Current Subscription status */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Active Subscription status */}
                    <div className="lg:col-span-2 p-6 rounded-2xl border bg-card text-card-foreground flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative overflow-hidden">
                        <div className="absolute right-0 top-0 translate-x-6 -translate-y-6 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none" />
                        
                        <div className="space-y-2">
                            <h3 className="font-bold text-lg flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5 text-indigo-500" />
                                Current Module Status
                            </h3>
                            {activeSubscription ? (
                                <div className="space-y-1">
                                    <p className="text-sm font-medium">
                                        You are currently subscribed to <span className="text-primary font-semibold">{activeSubscription.plan_name}</span>.
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Active • Renews on {activeSubscription.expires_at}
                                    </p>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No active subscription for {module.toUpperCase()} module. Subscribing will unlock full access.
                                </p>
                            )}
                        </div>

                        {activeSubscription && (
                            <Link href={route('subscriptions.manage')}>
                                <Button variant="outline" className="shadow-none gap-2">
                                    Manage Subscriptions <ArrowRight className="h-4 w-4" />
                                </Button>
                            </Link>
                        )}
                    </div>

                    {/* Dynamic Wallet Balance Box */}
                    <div className="p-6 rounded-2xl border bg-card text-card-foreground flex flex-col justify-between gap-4 relative overflow-hidden">
                        <div className="flex justify-between items-start">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Wallet Balance</span>
                                <h3 className="text-3xl font-extrabold tracking-tight text-indigo-600 dark:text-indigo-400">
                                    {formatMoney(walletBalance)}
                                </h3>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl">
                                <Wallet className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                        </div>
                        <Link href={route('financial.add-balance')}>
                            <Button size="sm" className="w-full shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                                + Add Funds
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* 3. Grid of Pricing Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {plans.map((plan) => {
                        const isCurrentPlan = activeSubscription?.plan_id === plan.id;
                        const hasSufficientWallet = walletBalance >= plan.price;
                        const featuresList = Array.isArray(plan.features) 
                            ? plan.features 
                            : Object.entries(plan.features).map(([key, val]) => `${key}: ${val}`);

                        return (
                            <Card 
                                key={plan.id} 
                                className={`flex flex-col relative shadow-none border bg-card hover:border-slate-400 dark:hover:border-slate-600 transition-all ${isCurrentPlan ? 'ring-2 ring-indigo-600 dark:ring-indigo-400 border-transparent' : ''}`}
                            >
                                {isCurrentPlan && (
                                    <span className="absolute top-0 right-6 -translate-y-1/2 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow">
                                        Current Plan
                                    </span>
                                )}

                                <CardHeader className="p-8">
                                    <CardTitle className="text-xl font-bold tracking-tight">{plan.name}</CardTitle>
                                    <CardDescription className="text-xs uppercase font-semibold text-muted-foreground mt-1">
                                        {plan.module.toUpperCase()} MODULE
                                    </CardDescription>
                                    <div className="mt-5 flex items-baseline gap-1">
                                        <span className="text-4xl font-extrabold tracking-tight">
                                            {formatMoney(plan.price)}
                                        </span>
                                        <span className="text-sm font-medium text-muted-foreground">
                                            /{plan.billing === 'yearly' ? 'yr' : 'mo'}
                                        </span>
                                    </div>
                                </CardHeader>

                                <CardContent className="px-8 pb-8 flex-1">
                                    <div className="border-t pt-6">
                                        <h4 className="text-xs font-semibold text-foreground uppercase tracking-wider mb-4">Included Features</h4>
                                        <ul className="space-y-3.5">
                                            {featuresList.map((feature, i) => (
                                                <li key={i} className="flex items-start gap-3 text-sm text-muted-foreground font-medium">
                                                    <Check className="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                                                    <span>{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </CardContent>

                                <CardFooter className="p-8 border-t flex flex-col gap-3">
                                    {isCurrentPlan ? (
                                        <Button disabled className="w-full shadow-none bg-muted text-muted-foreground cursor-not-allowed">
                                            Active Subscribed Plan
                                        </Button>
                                    ) : (
                                        <>
                                            {/* Pay via Wallet */}
                                            <Button
                                                onClick={() => handleSubscribeWallet(plan.id)}
                                                disabled={!hasSufficientWallet}
                                                variant={hasSufficientWallet ? 'default' : 'outline'}
                                                className={`w-full shadow-none gap-2 font-semibold ${hasSufficientWallet ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'opacity-65 cursor-not-allowed'}`}
                                            >
                                                <Wallet className="h-4 w-4" />
                                                Subscribe via Wallet
                                            </Button>

                                            {/* Pay via Kashier */}
                                            <Button
                                                onClick={() => handleSubscribeKashier(plan.id)}
                                                variant="outline"
                                                className="w-full shadow-none gap-2 font-semibold hover:bg-slate-50 dark:hover:bg-slate-900 border-slate-200 dark:border-slate-800"
                                            >
                                                <CreditCard className="h-4 w-4 text-slate-500" />
                                                Checkout via Kashier Card
                                            </Button>
                                            
                                            {!hasSufficientWallet && (
                                                <p className="text-[11px] text-amber-600 dark:text-amber-400 text-center font-medium">
                                                    * Low wallet balance. Use Kashier checkout or add funds.
                                                </p>
                                            )}
                                        </>
                                    )}
                                </CardFooter>
                            </Card>
                        );
                    })}
                </div>

                {/* FAQ section */}
                <div className="border-t pt-10 mt-16 max-w-3xl mx-auto space-y-8">
                    <h3 className="text-xl font-bold text-center flex items-center justify-center gap-2">
                        <HelpCircle className="h-5 w-5 text-indigo-500" /> Frequently Asked Questions
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-1.5">
                            <h4 className="font-semibold text-sm">How does wallet billing work?</h4>
                            <p className="text-xs text-muted-foreground">You can deposit funds securely via credit/debit card, and use your internal wallet balance to instantly pay or auto-renew any module subscription without leaving the app.</p>
                        </div>
                        <div className="space-y-1.5">
                            <h4 className="font-semibold text-sm">Can I switch plans or cancel anytime?</h4>
                            <p className="text-xs text-muted-foreground">Yes! You can cancel or upgrade your subscription from the Subscription Management panel. Upgrades take effect immediately, while cancellations expire at the end of the billing cycle.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
