import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Wallet, Lock, CreditCard, ArrowLeft, CheckCircle2, ShieldCheck,
    Building2, DollarSign, Sparkles, Zap, ArrowRight, Banknote
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
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
        { id: 'stripe', name: 'Credit Card / Apple Pay', desc: 'Instant deposit via Stripe securely', icon: CreditCard, tag: 'Popular' },
        { id: 'paypal', name: 'PayPal Account', desc: 'Secure digital wallet checkout', icon: Zap, tag: 'Instant' },
        { id: 'bank', name: 'Wire Transfer / ACH', desc: '1-2 business days for clearing', icon: Building2, tag: 'Lowest Fee' },
        { id: 'crypto', name: 'USDC Crypto Stablecoin', desc: 'Ethereum & Solana chains', icon: Banknote, tag: 'Web3' },
    ];

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Add Funds to Wallet" />

            <div className="max-w-[1000px] mx-auto space-y-8 pb-16">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-xs text-slate-500 font-medium">
                    <Link href={safeRoute('dashboard')} className="hover:text-slate-900 transition flex items-center gap-1">
                        <ArrowLeft className="w-3.5 h-3.5" /> Dashboard
                    </Link>
                    <span>/</span>
                    <Link href={safeRoute('erp.wallet.show', activeClient.id, `/clients/${activeClient.id}/wallet`)} className="hover:text-slate-900 transition">
                        Wallet
                    </Link>
                    <span>/</span>
                    <span className="text-slate-900 font-semibold">Add Balance</span>
                </div>

                {/* Hero Card */}
                <div className="bg-slate-900 text-white p-8 rounded-3xl relative overflow-hidden shadow-xl border border-slate-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div className="absolute -top-24 -right-24 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none" />
                    <div className="absolute -bottom-24 -left-24 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none" />
                    
                    <div className="relative z-10 space-y-2 max-w-lg">
                        <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-400/20 text-indigo-300 text-xs font-semibold">
                            <Sparkles className="w-3.5 h-3.5 text-indigo-400" /> Secure Instant Deposit
                        </div>
                        <h1 className="text-3xl font-extrabold tracking-tight">Add Balance to Your Wallet</h1>
                        <p className="text-sm text-slate-400 leading-relaxed font-light">
                            Pre-fund your SaaS account to automatically clear recurring invoices, milestone escrow locks, and workspace subscriptions with zero payment friction.
                        </p>
                    </div>

                    <div className="relative z-10 bg-white/10 backdrop-blur-md px-6 py-5 rounded-2xl border border-white/10 shrink-0 text-center w-full md:w-auto">
                        <p className="text-xs uppercase font-bold text-slate-400 tracking-wider mb-1">Current Available Balance</p>
                        <p className="text-3xl font-mono font-bold text-white">${activeWallet.balance.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                    </div>
                </div>

                {/* Main Form Box */}
                <form onSubmit={handleSubmit} className="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-10 space-y-8">
                    
                    {/* Step 1: Amount */}
                    <div className="space-y-4">
                        <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                            <h2 className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                <span className="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold">1</span>
                                Select Deposit Amount
                            </h2>
                            <span className="text-xs text-slate-400 font-medium">Minimum $5.00</span>
                        </div>

                        {/* Presets */}
                        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            {presets.map((amt) => (
                                <button
                                    type="button"
                                    key={amt}
                                    onClick={() => handlePresetSelect(amt)}
                                    className={`py-4 rounded-2xl border text-center font-mono font-bold transition-all relative overflow-hidden ${
                                        selectedPreset === amt 
                                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-md shadow-indigo-100 scale-[1.02]' 
                                        : 'bg-slate-50 border-slate-200 text-slate-800 hover:bg-slate-100/80 hover:border-slate-300'
                                    }`}
                                >
                                    ${amt}
                                    {amt === 100 && selectedPreset !== amt && (
                                        <span className="absolute top-1 right-2 text-[9px] text-indigo-600 font-sans font-semibold">Popular</span>
                                    )}
                                </button>
                            ))}
                        </div>

                        {/* Custom Amount */}
                        <div className="pt-2">
                            <label className="text-xs uppercase font-bold text-slate-400 tracking-wider block mb-2">Or Enter Custom Amount ($ USD)</label>
                            <div className="relative max-w-md">
                                <span className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold font-mono">$</span>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="5.00"
                                    placeholder="Enter custom amount..."
                                    value={customAmount}
                                    onChange={(e) => handleCustomChange(e.target.value)}
                                    className="pl-9 h-12 rounded-xl border-slate-200 font-mono text-base font-semibold focus:border-indigo-600 shadow-none"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Step 2: Payment Method */}
                    <div className="space-y-4 pt-4 border-t border-slate-100">
                        <div className="border-b border-slate-100 pb-3">
                            <h2 className="text-base font-semibold text-slate-900 flex items-center gap-2">
                                <span className="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold">2</span>
                                Select Payment Method
                            </h2>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {paymentMethods.map((method) => {
                                const IconComponent = method.icon;
                                const isSelected = selectedMethod === method.id;
                                return (
                                    <div
                                        key={method.id}
                                        onClick={() => handleMethodSelect(method.id as any)}
                                        className={`p-5 rounded-2xl border cursor-pointer transition-all flex items-start gap-4 relative overflow-hidden ${
                                            isSelected 
                                            ? 'border-indigo-600 bg-indigo-50/20 shadow-md shadow-indigo-50/50' 
                                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'
                                        }`}
                                    >
                                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors ${
                                            isSelected ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                                        }`}>
                                            <IconComponent className="w-5 h-5" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <h3 className="text-sm font-semibold text-slate-900">{method.name}</h3>
                                                <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                                    method.tag === 'Popular' ? 'bg-indigo-100 text-indigo-700' :
                                                    method.tag === 'Instant' ? 'bg-emerald-100 text-emerald-700' :
                                                    method.tag === 'Lowest Fee' ? 'bg-amber-100 text-amber-700' : 'bg-purple-100 text-purple-700'
                                                }`}>
                                                    {method.tag}
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-500 leading-relaxed font-light">{method.desc}</p>
                                        </div>
                                        <div className={`absolute top-4 right-4 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors ${
                                            isSelected ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300'
                                        }`}>
                                            {isSelected && <span className="w-2 h-2 rounded-full bg-white" />}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Step 3: Confirmation Summary */}
                    <div className="p-6 rounded-2xl bg-slate-50 border border-slate-100 space-y-4">
                        <div className="flex justify-between items-center text-sm font-medium text-slate-600">
                            <span>Selected Deposit Amount</span>
                            <span className="font-mono font-bold text-slate-900">${form.data.amount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between items-center text-sm font-medium text-slate-600">
                            <span>Processing Fee</span>
                            <span className="text-emerald-600 font-semibold font-mono">Free ($0.00)</span>
                        </div>
                        <div className="pt-3 border-t border-slate-200/80 flex justify-between items-center text-base font-bold text-slate-900">
                            <span>Total to Charge</span>
                            <span className="font-mono text-xl text-indigo-600">${form.data.amount.toFixed(2)}</span>
                        </div>
                        <div className="pt-4 flex flex-col sm:flex-row gap-4">
                            <Button
                                type="submit"
                                disabled={form.processing || form.data.amount <= 0}
                                className="w-full h-14 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base shadow-lg shadow-indigo-100 transition-all"
                            >
                                {form.processing ? 'Processing Secure Deposit...' : `Confirm & Deposit $${form.data.amount.toFixed(2)}`}
                                <ArrowRight className="w-5 h-5 ml-2" />
                            </Button>
                        </div>
                    </div>
                </form>

                {/* Trust Footer */}
                <div className="flex flex-col sm:flex-row items-center justify-center gap-6 text-center text-xs text-slate-500 pt-4 font-light">
                    <span className="flex items-center gap-1.5"><ShieldCheck className="w-4 h-4 text-emerald-600 font-bold" /> Bank-grade 256-bit SSL Encryption</span>
                    <span className="hidden sm:inline">•</span>
                    <span>PCI-DSS Level 1 Secure Payment Gateway</span>
                    <span className="hidden sm:inline">•</span>
                    <span>Funds protected by Escrow Safeguard</span>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
