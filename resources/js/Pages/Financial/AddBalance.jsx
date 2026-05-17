import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { Wallet, ShieldCheck, ArrowRight, Sparkles, CheckCircle2, AlertCircle, CreditCard, Lock } from 'lucide-react';

export default function AddBalance({ wallet }) {
    const { flash } = usePage().props;
    const [selectedPreset, setSelectedPreset] = useState(50);

    const { data, setData, post, processing, errors } = useForm({
        amount: 50,
    });

    const handlePresetClick = (val) => {
        setSelectedPreset(val);
        setData('amount', val);
    };

    const handleCustomChange = (e) => {
        const val = parseFloat(e.target.value) || '';
        setSelectedPreset(null);
        setData('amount', val);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('financial.add-balance.kashier'));
    };

    const presets = [10, 50, 100, 250, 500];

    return (
        <AuthenticatedLayout header="Add Balance to Wallet">
            <Head title="Add Balance" />

            <div className="max-w-4xl mx-auto space-y-8 pb-12">
                {/* Flash Alerts */}
                {flash?.success && (
                    <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                        <p className="text-sm font-medium">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                        <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
                        <p className="text-sm font-medium">{flash.error}</p>
                    </div>
                )}

                {/* Balance Overview Card */}
                <div className="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div className="absolute -right-12 -bottom-12 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl"></div>
                    <div className="flex items-center justify-between mb-6 relative z-10">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center backdrop-blur-md">
                                <Wallet className="w-6 h-6 text-indigo-400" />
                            </div>
                            <div>
                                <span className="text-xs font-medium text-indigo-200 uppercase tracking-wider">Current Available Balance</span>
                                <div className="text-3xl font-bold tracking-tight text-white mt-0.5">
                                    {Number(wallet?.balance || 0).toFixed(2)} <span className="text-lg font-normal text-slate-400">{wallet?.currency || 'USD'}</span>
                                </div>
                            </div>
                        </div>
                        <div className="hidden sm:flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-xs text-indigo-200 backdrop-blur-md">
                            <Sparkles className="w-4 h-4 text-amber-400" /> Instant Recharge Available
                        </div>
                    </div>
                    <p className="text-xs text-slate-400 relative z-10 max-w-md">
                        Funds deposited to your wallet can be instantly used across all platform services, freelancer hires, and software purchases.
                    </p>
                </div>

                {/* Deposit Form Card */}
                <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="p-8 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-bold text-slate-900">Select Deposit Amount</h2>
                            <p className="text-xs text-slate-500 mt-1">Choose a quick preset or enter a custom recharge amount</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full flex items-center gap-1.5 border border-indigo-100">
                                <ShieldCheck className="w-4 h-4 text-indigo-600" /> Secured by Kashier
                            </span>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="p-8 space-y-8">
                        {/* Preset Buttons */}
                        <div>
                            <label className="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-3">Quick Presets</label>
                            <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                {presets.map((amount) => (
                                    <button
                                        type="button"
                                        key={amount}
                                        onClick={() => handlePresetClick(amount)}
                                        className={`py-4 px-6 rounded-2xl font-bold text-base transition-all border ${
                                            selectedPreset === amount
                                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20 scale-[1.02]'
                                                : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 hover:border-slate-300'
                                        }`}
                                    >
                                        ${amount}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Custom Input */}
                        <div>
                            <label className="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-3">Custom Recharge Amount ({wallet?.currency || 'USD'})</label>
                            <div className="relative max-w-md">
                                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold text-xl">
                                    $
                                </div>
                                <input
                                    type="number"
                                    min="5"
                                    step="0.01"
                                    value={data.amount}
                                    onChange={handleCustomChange}
                                    placeholder="Enter custom amount (min $5)"
                                    className="block w-full pl-9 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-lg font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all placeholder:text-slate-400 font-mono"
                                />
                            </div>
                            {errors.amount && (
                                <p className="text-xs font-medium text-rose-600 mt-2">{errors.amount}</p>
                            )}
                            <p className="text-xs text-slate-400 mt-2">Minimum deposit amount is $5.00 {wallet?.currency || 'USD'}.</p>
                        </div>

                        <div className="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div className="flex items-center gap-4 text-xs text-slate-500">
                                <div className="flex items-center gap-1.5 font-medium">
                                    <Lock className="w-4 h-4 text-emerald-600 shrink-0" /> 256-bit SSL Encrypted
                                </div>
                                <div className="flex items-center gap-1.5 font-medium">
                                    <CreditCard className="w-4 h-4 text-indigo-600 shrink-0" /> Accepts all major cards & wallets
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processing || !data.amount || data.amount < 5}
                                className="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-base font-semibold rounded-2xl transition-all shadow-lg shadow-indigo-500/25 group"
                            >
                                {processing ? (
                                    <span className="flex items-center gap-2">
                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Connecting to Kashier...
                                    </span>
                                ) : (
                                    <span className="flex items-center gap-2">
                                        Proceed to Secure Payment (${Number(data.amount || 0).toFixed(2)})
                                        <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                                    </span>
                                )}
                            </button>
                        </div>
                    </form>
                </div>

                {/* FAQ / Security Banner */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                        <div className="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 text-indigo-600 font-bold text-lg">
                            1
                        </div>
                        <h3 className="text-sm font-bold text-slate-900 mb-1">Instant Crediting</h3>
                        <p className="text-xs text-slate-500 leading-relaxed">
                            Once your payment is approved by Kashier, our automated webhook instantly credits your exact recharge amount to your workspace wallet.
                        </p>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                        <div className="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-4 text-emerald-600 font-bold text-lg">
                            <ShieldCheck className="w-5 h-5" />
                        </div>
                        <h3 className="text-sm font-bold text-slate-900 mb-1">PCI-DSS Level 1 Compliance</h3>
                        <p className="text-xs text-slate-500 leading-relaxed">
                            All sensitive payment data and credit card details are handled directly by Kashier's bank-grade secure gateway. We never store raw card numbers.
                        </p>
                    </div>

                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                        <div className="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-4 text-amber-600 font-bold text-lg">
                            $
                        </div>
                        <h3 className="text-sm font-bold text-slate-900 mb-1">Zero Hidden Fees</h3>
                        <p className="text-xs text-slate-500 leading-relaxed">
                            The amount you choose to deposit is precisely what gets added to your available balance. Standard processing fees are covered transparently.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
