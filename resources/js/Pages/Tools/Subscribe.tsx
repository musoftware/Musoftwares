import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { CheckCircle2, CreditCard, Wallet, AlertCircle, ShoppingBag, Download, Star, Activity, ArrowLeft } from 'lucide-react';

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
            <WorkspaceLayout title="Subscribe" workspaceName="Tools" tenantId="SYS-TOOLS"
                menuItems={[
                    { id: 'explore', label: 'Explore', icon: ShoppingBag, href: route('tools.explore'), isActive: false },
                ]}>
                <Head title="Already Subscribed" />
                <div className="max-w-xl mx-auto py-20 px-4 text-center">
                    <CheckCircle2 className="h-12 w-12 text-emerald-500 mx-auto mb-4" />
                    <h1 className="text-xl font-bold text-slate-900 mb-2">You already have an active subscription</h1>
                    <p className="text-slate-500 mb-6 text-sm">You can download the tool from your Downloads page.</p>
                    <div className="flex gap-3 justify-center">
                        <Button onClick={() => router.visit(route('tools.downloads'))}>Go to Downloads</Button>
                        <Button variant="outline" onClick={() => router.visit(route('tools.show', tool.slug))}>View Tool</Button>
                    </div>
                </div>
            </WorkspaceLayout>
        );
    }

    return (
        <WorkspaceLayout title="Subscribe" workspaceName="Tools" tenantId="SYS-TOOLS"
            menuItems={[
                { id: 'explore', label: 'Explore', icon: ShoppingBag, href: route('tools.explore'), isActive: false },
                { id: 'downloads', label: 'Downloads', icon: Download, href: route('tools.downloads'), isActive: false },
                { id: 'licenses', label: 'My Licenses', icon: Star, href: route('tools.my-licenses'), isActive: false },
                { id: 'billing', label: 'Billing', icon: Activity, href: route('tools.billing'), isActive: false },
            ]}>
            <Head title={`Subscribe to ${tool.title}`} />
            <div className="max-w-2xl mx-auto py-10 px-4 sm:px-6 space-y-8">
                <button onClick={() => router.visit(route('tools.show', tool.slug))}
                    className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-800 transition-colors">
                    <ArrowLeft className="h-4 w-4" /> Back to {tool.title}
                </button>

                <ModulePageHeader title={`Subscribe to ${tool.title}`} description={`Get access to ${plan.name} plan and download to your devices.`} />

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Plan summary */}
                    <Card>
                        <CardContent className="p-5 space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="font-semibold text-slate-900">{plan.name} Plan</p>
                                    <p className="text-sm text-slate-500 mt-0.5">Up to {plan.max_devices} device{plan.max_devices > 1 ? 's' : ''}</p>
                                </div>
                                {/* Billing toggle */}
                                <div className="flex items-center bg-slate-100 rounded-lg p-0.5 text-xs font-medium">
                                    {(['monthly', 'yearly'] as const).map(cycle => (
                                        <button type="button" key={cycle} onClick={() => handleCycleChange(cycle)}
                                            className={`px-3 py-1.5 rounded-md transition-all capitalize ${billingCycle === cycle ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'}`}>
                                            {cycle}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-end gap-1 pt-1">
                                <span className="text-3xl font-bold text-slate-900">${price.toFixed(2)}</span>
                                <span className="text-slate-400 text-sm mb-1">/ {billingCycle === 'monthly' ? 'month' : 'year'}</span>
                            </div>

                            <ul className="space-y-1.5 pt-2 border-t border-slate-100">
                                {plan.features.map((f, i) => (
                                    <li key={i} className="flex items-start gap-2 text-sm text-slate-600">
                                        <CheckCircle2 className="h-4 w-4 text-emerald-500 flex-shrink-0 mt-0.5" />{f}
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Payment method */}
                    <div className="space-y-3">
                        <p className="text-sm font-medium text-slate-700">Payment Method</p>
                        <div className="grid grid-cols-2 gap-3">
                            <button type="button" onClick={() => setData('payment_method', 'wallet')}
                                className={`flex items-center gap-3 p-4 rounded-xl border-2 transition-all text-left ${data.payment_method === 'wallet' ? 'border-slate-900 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-300'}`}>
                                <Wallet className="h-5 w-5 text-slate-600 flex-shrink-0" />
                                <div>
                                    <p className="text-sm font-medium text-slate-900">Wallet</p>
                                    <p className="text-xs text-slate-500">Balance: ${walletBalance.toFixed(2)}</p>
                                </div>
                            </button>
                            <button type="button" onClick={() => setData('payment_method', 'kashier')}
                                className={`flex items-center gap-3 p-4 rounded-xl border-2 transition-all text-left ${data.payment_method === 'kashier' ? 'border-slate-900 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-300'}`}>
                                <CreditCard className="h-5 w-5 text-slate-600 flex-shrink-0" />
                                <div>
                                    <p className="text-sm font-medium text-slate-900">Card</p>
                                    <p className="text-xs text-slate-500">via Kashier</p>
                                </div>
                            </button>
                        </div>
                        {data.payment_method === 'wallet' && !canPayByWallet && (
                            <div className="flex items-start gap-2 p-3 bg-amber-50 rounded-lg border border-amber-200 text-sm text-amber-800">
                                <AlertCircle className="h-4 w-4 flex-shrink-0 mt-0.5" />
                                Insufficient wallet balance. Please add funds or pay by card.
                            </div>
                        )}
                    </div>

                    <Button type="submit" className="w-full" size="lg"
                        disabled={processing || (data.payment_method === 'wallet' && !canPayByWallet)}>
                        {processing ? 'Processing...' : `Subscribe — $${price.toFixed(2)}`}
                    </Button>
                    <p className="text-xs text-slate-400 text-center">
                        You can cancel anytime. Access continues until the end of your billing period.
                    </p>
                </form>
            </div>
        </WorkspaceLayout>
    );
}
