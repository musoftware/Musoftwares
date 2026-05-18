import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, CreditCard, Wallet, AlertCircle, ArrowLeft, Shield } from 'lucide-react';

interface Props {
    tool: { slug: string; title: string; icon_url: string | null };
    plan: { id: number; name: string; price_monthly: number; price_yearly: number; max_devices: number; features: string[] };
    walletBalance: number;
    hasExisting: boolean;
}

export default function Subscribe({ tool, plan, walletBalance, hasExisting }: Props) {
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>('monthly');
    const { data, setData, post, processing, errors } = useForm({
        billing_cycle: 'monthly' as 'monthly' | 'yearly',
        payment_method: 'wallet' as 'wallet' | 'kashier',
    });

    const price = billingCycle === 'monthly' ? plan.price_monthly : plan.price_yearly;
    const canPayByWallet = walletBalance >= price;

    const handleCycleChange = (cycle: 'monthly' | 'yearly') => {
        setBillingCycle(cycle);
        setData('billing_cycle', cycle);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tools.subscribe', { slug: tool.slug, planId: plan.id }));
    };

    if (hasExisting) {
        return (
            <ToolsPublicLayout title="Already Subscribed" activeNav="downloads">
                <Head title="Already Subscribed" />
                <div className="max-w-md mx-auto py-20 px-4 text-center">
                    <div className="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center mx-auto mb-5">
                        <CheckCircle2 className="h-8 w-8 text-emerald-500" />
                    </div>
                    <h1 className="text-xl font-bold text-slate-900 mb-2">Already subscribed</h1>
                    <p className="text-slate-500 mb-6 text-sm">You can download the tool from your Downloads page.</p>
                    <div className="flex gap-3 justify-center">
                        <Button onClick={() => router.visit(route('tools.downloads'))}>Go to Downloads</Button>
                        <Button variant="outline" onClick={() => router.visit(route('tools.show', tool.slug))}>View Tool</Button>
                    </div>
                </div>
            </ToolsPublicLayout>
        );
    }

    return (
        <ToolsPublicLayout title={`Subscribe to ${tool.title}`} activeNav="explore">
            <Head title={`Subscribe to ${tool.title}`} />
            <div className="max-w-lg mx-auto py-10 px-4 sm:px-6 space-y-6">

                {/* Back */}
                <button
                    onClick={() => router.visit(route('tools.show', tool.slug))}
                    className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" /> Back to {tool.title}
                </button>

                {/* Header */}
                <div>
                    <h1 className="text-xl font-bold text-slate-900">Subscribe to {tool.title}</h1>
                    <p className="text-sm text-slate-500 mt-1">Get access to {plan.name} plan and download on your devices.</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-5">
                    {/* Plan card */}
                    <div className="bg-slate-900 rounded-xl overflow-hidden">
                        <div className="px-5 py-4 flex items-center justify-between border-b border-slate-700">
                            <div>
                                <p className="font-semibold text-white">{plan.name} Plan</p>
                                <p className="text-xs text-slate-400 mt-0.5">Up to {plan.max_devices} device{plan.max_devices > 1 ? 's' : ''}</p>
                            </div>
                            {/* Billing toggle */}
                            <div className="flex items-center bg-slate-800 rounded-lg p-0.5 gap-0.5">
                                {(['monthly', 'yearly'] as const).map(cycle => (
                                    <button
                                        type="button"
                                        key={cycle}
                                        onClick={() => handleCycleChange(cycle)}
                                        className={`px-3 py-1.5 rounded-md text-xs font-medium capitalize transition-all ${
                                            billingCycle === cycle
                                                ? 'bg-white text-slate-900 shadow-sm'
                                                : 'text-slate-400 hover:text-slate-200'
                                        }`}
                                    >
                                        {cycle}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="px-5 py-4">
                            <div className="flex items-end gap-1 mb-4">
                                <span className="text-3xl font-bold text-white">${price.toFixed(2)}</span>
                                <span className="text-slate-400 text-sm mb-1">/{billingCycle === 'monthly' ? 'month' : 'year'}</span>
                            </div>
                            <ul className="space-y-2">
                                {plan.features.map((f, i) => (
                                    <li key={i} className="flex items-start gap-2 text-xs text-slate-300">
                                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-400 flex-shrink-0 mt-0.5" />
                                        {f}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>

                    {/* Payment method */}
                    <div className="space-y-2.5">
                        <p className="text-sm font-semibold text-slate-700">Payment Method</p>
                        <div className="grid grid-cols-2 gap-3">
                            {[
                                {
                                    method: 'wallet' as const,
                                    icon: Wallet,
                                    label: 'Wallet',
                                    sub: `Balance: $${walletBalance.toFixed(2)}`,
                                },
                                {
                                    method: 'kashier' as const,
                                    icon: CreditCard,
                                    label: 'Card',
                                    sub: 'via Kashier',
                                },
                            ].map(opt => {
                                const Icon = opt.icon;
                                const isSelected = data.payment_method === opt.method;
                                return (
                                    <button
                                        type="button"
                                        key={opt.method}
                                        onClick={() => setData('payment_method', opt.method)}
                                        className={`flex items-center gap-3 p-4 rounded-xl border-2 text-left transition-all ${
                                            isSelected
                                                ? 'border-slate-900 bg-slate-50'
                                                : 'border-slate-200 bg-white hover:border-slate-300'
                                        }`}
                                    >
                                        <Icon className="h-5 w-5 text-slate-500 flex-shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium text-slate-900">{opt.label}</p>
                                            <p className="text-xs text-slate-500">{opt.sub}</p>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {data.payment_method === 'wallet' && !canPayByWallet && (
                            <div className="flex items-start gap-2 p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-800">
                                <AlertCircle className="h-4 w-4 flex-shrink-0 mt-0.5" />
                                Insufficient wallet balance. Please add funds or pay by card.
                            </div>
                        )}
                    </div>

                    {/* Submit */}
                    <Button
                        type="submit"
                        size="lg"
                        className="w-full bg-slate-900 hover:bg-slate-800 text-white h-11"
                        disabled={processing || (data.payment_method === 'wallet' && !canPayByWallet)}
                    >
                        {processing ? 'Processing...' : `Subscribe — $${price.toFixed(2)}`}
                    </Button>

                    <p className="text-xs text-slate-400 text-center flex items-center justify-center gap-1">
                        <Shield className="h-3 w-3" />
                        Cancel anytime. Access continues until end of billing period.
                    </p>
                </form>
            </div>
        </ToolsPublicLayout>
    );
}
