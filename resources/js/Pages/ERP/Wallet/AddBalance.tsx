import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    ArrowLeft, CreditCard, Zap, Building2, Banknote, ShieldCheck
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useToast } from '@/Components/ui/use-toast';

interface AddBalanceProps {
    wallet: { id: number; balance: number; currency: string };
    client: { id: number; name: string; email: string };
}

export default function AddBalance({ wallet, client }: AddBalanceProps) {
    const { toast } = useToast();
    const activeWallet = wallet || { id: 1, balance: 1250.45, currency: 'USD' };
    const activeClient = client || { id: 1, name: 'SaaS Customer', email: 'customer@example.com' };

    const [customAmount, setCustomAmount] = useState('');
    const [selectedPreset, setSelectedPreset] = useState<number | null>(100);
    const [selectedMethod, setSelectedMethod] = useState<'stripe' | 'paypal' | 'bank' | 'crypto'>('stripe');

    const form = useForm({
        amount: 100,
        payment_method: 'stripe',
    });

    const safeRoute = (name: string, params?: any, fallbackUrl?: string) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined' && route().has(name)) {
                // @ts-ignore
                return route(name, params);
            }
        } catch (e) {}
        return fallbackUrl || '#';
    };

    const handlePresetSelect = (amount: number) => {
        setSelectedPreset(amount);
        setCustomAmount('');
        form.setData('amount', amount);
    };

    const handleCustomChange = (val: string) => {
        setCustomAmount(val);
        setSelectedPreset(null);
        const num = parseFloat(val);
        if (!isNaN(num) && num > 0) {
            form.setData('amount', num);
        } else {
            form.setData('amount', 0);
        }
    };

    const handleMethodSelect = (method: 'stripe' | 'paypal' | 'bank' | 'crypto') => {
        setSelectedMethod(method);
        form.setData('payment_method', method);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (form.data.amount < 5) {
            toast({
                title: "Minimum Deposit",
                description: "The minimum deposit amount is $5.00",
                variant: "destructive"
            });
            return;
        }

        form.post(safeRoute('erp.wallet.deposit', undefined, '/wallet/deposit'), {
            onSuccess: () => {
                toast({
                    title: "Deposit Successful!",
                    description: `Successfully added $${form.data.amount.toFixed(2)} to your wallet balance.`,
                });
            },
            onError: (err: any) => {
                toast({
                    title: "Deposit Error",
                    description: Object.values(err)[0] as string || "An error occurred while processing your deposit.",
                    variant: "destructive"
                });
            }
        });
    };

    const presets = [50, 100, 250, 500, 1000];

    const paymentMethods = [
        { id: 'stripe', name: 'Card / Apple Pay', desc: 'Instant deposit via Stripe securely', icon: CreditCard },
        { id: 'paypal', name: 'PayPal', desc: 'Secure digital wallet checkout', icon: Zap },
        { id: 'bank', name: 'Wire / ACH', desc: '1-2 business days for clearing', icon: Building2 },
        { id: 'crypto', name: 'USDC Stablecoin', desc: 'Ethereum & Solana chains', icon: Banknote },
    ];
    const { menuItems, workspaceName, tenantId } = useERPMenu('clients');

    return (
        <ERPLayout title="Deposit Funds" workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems}>

            <div className="max-w-2xl mx-auto px-4 py-8 space-y-6">
                {/* Header */}
                <div className="space-y-2">
                    <Link href={safeRoute('erp.wallet.show', activeClient.id, `/clients/${activeClient.id}/wallet`)} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                        <ArrowLeft className="mr-2 h-4 w-4" /> Back to Wallet
                    </Link>
                    <h1 className="text-2xl font-semibold tracking-tight">Deposit Funds</h1>
                    <p className="text-sm text-muted-foreground">Add balance to your wallet for seamless platform transactions.</p>
                </div>

                {/* Compact Wallet Summary Card */}
                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-slate-500">Wallet Balance</p>
                            <div className="text-3xl font-bold tracking-tight text-slate-900">
                                ${activeWallet.balance.toLocaleString(undefined, {minimumFractionDigits: 2})} <span className="text-lg font-normal text-slate-400">{activeWallet.currency}</span>
                            </div>
                        </div>
                        <div className="text-sm text-slate-500 max-w-[200px]">
                            Available for invoices, subscriptions, and services.
                        </div>
                    </div>
                </div>

                {/* Deposit Flow Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    
                    {/* Amount Selection */}
                    <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">1. Deposit Amount</h2>
                            <p className="text-sm text-slate-500 mt-1">Select a preset or enter a custom amount to deposit.</p>
                        </div>
                        <div className="grid grid-cols-3 sm:grid-cols-5 gap-3">
                            {presets.map((amt) => (
                                <Button
                                    type="button"
                                    key={amt}
                                    variant={selectedPreset === amt ? "default" : "outline"}
                                    onClick={() => handlePresetSelect(amt)}
                                    className={`h-12 text-base font-medium transition-colors shadow-sm ${selectedPreset === amt ? 'bg-slate-900 text-white hover:bg-slate-800' : 'border-slate-200 text-slate-700 hover:bg-slate-50'}`}
                                >
                                    ${amt}
                                </Button>
                            ))}
                        </div>

                        <div className="space-y-2 max-w-xs">
                            <Label htmlFor="custom-amount" className="text-slate-700 font-medium">Custom Amount</Label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span className="text-slate-500 font-medium">$</span>
                                </div>
                                <Input
                                    id="custom-amount"
                                    type="number"
                                    step="0.01"
                                    min="5.00"
                                    placeholder="0.00"
                                    value={customAmount}
                                    onChange={(e) => handleCustomChange(e.target.value)}
                                    className={`pl-8 shadow-none border-slate-200 ${customAmount && !selectedPreset ? "border-indigo-500 ring-1 ring-indigo-500 focus:border-indigo-500 focus:ring-indigo-500" : ""}`}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Payment Method */}
                    <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">2. Payment Method</h2>
                            <p className="text-sm text-slate-500 mt-1">Choose how you would like to fund your wallet.</p>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {paymentMethods.map((method) => {
                                const IconComponent = method.icon;
                                const isSelected = selectedMethod === method.id;

                                return (
                                    <div
                                        key={method.id}
                                        onClick={() => handleMethodSelect(method.id as any)}
                                        className={`relative flex cursor-pointer items-start gap-4 rounded-xl border p-5 transition-all duration-200 shadow-sm ${
                                            isSelected 
                                            ? 'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-50/50' 
                                            : 'border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300'
                                        }`}
                                    >
                                        <IconComponent className={`mt-0.5 h-6 w-6 shrink-0 ${isSelected ? 'text-indigo-600' : 'text-slate-400'}`} />
                                        <div className="flex-1 space-y-1">
                                            <p className={`text-sm font-semibold leading-none ${isSelected ? 'text-indigo-900' : 'text-slate-900'}`}>{method.name}</p>
                                            <p className={`text-sm ${isSelected ? 'text-indigo-700/80' : 'text-slate-500'}`}>{method.desc}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Confirmation & Action */}
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                        <Button
                            type="submit"
                            size="lg"
                            disabled={form.processing || form.data.amount < 5}
                            className="w-full sm:w-auto h-12 px-8 text-base shadow-none"
                        >
                            {form.processing ? 'Processing...' : `Deposit $${(form.data.amount || 0).toFixed(2)}`}
                        </Button>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ShieldCheck className="h-4 w-4" />
                            <span>Secure encrypted payment.</span>
                        </div>
                    </div>
                </form>
            </div>
        </ERPLayout>
    );
}

