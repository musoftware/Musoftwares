import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    ArrowLeft, ShieldCheck, CreditCard, AlertCircle, Wallet, Sparkles, Check
} from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function AddBalance({ wallet, presets = [] }) {
    const defaultAmount = presets.length > 0 ? presets[0] : 50;
    const [selectedPreset, setSelectedPreset] = useState(defaultAmount);
    const [customAmount, setCustomAmount] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        amount: defaultAmount,
    });

    const safeRoute = (name, params, fallbackUrl) => {
        try {
            if (typeof route !== 'undefined' && route().has(name)) {
                return route(name, params);
            }
        } catch (e) { /* empty */ }
        return fallbackUrl || '#';
    };

    const handlePresetClick = (val) => {
        setSelectedPreset(val);
        setCustomAmount('');
        setData('amount', val);
    };

    const handleCustomChange = (e) => {
        const val = e.target.value;
        setCustomAmount(val);
        setSelectedPreset(null);
        const num = parseFloat(val);
        if (!isNaN(num) && num > 0) {
            setData('amount', num);
        } else {
            setData('amount', 0);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(safeRoute('financial.add-balance.kashier', undefined, '/financial/add-balance/kashier'));
    };

    const walletBalance = Number(wallet?.balance || 0);
    const walletCurrency = wallet?.currency || 'EGP';

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.add_balance')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Header Banner */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href={safeRoute('dashboard', undefined, '/dashboard')}
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_dashboard')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.add_balance')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('general.top_up_your_wallet_to_pay_for_subscriptions_and_platform_services')}
                            </p>
                        </div>

                        {/* Current Wallet Balance Pill */}
                        <div className="bg-[#f5f5f7] border border-black/5 rounded-[20px] p-4 flex items-center gap-4 shrink-0">
                            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600">
                                <Wallet className="w-5 h-5" />
                            </div>
                            <div>
                                <p className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50">
                                    {__('general.current_wallet_balance')}
                                </p>
                                <div className="text-xl font-bold tracking-tight text-[#1d1d1f]">
                                    {formatMoney(walletBalance, walletCurrency)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Form Body */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-6">

                    {/* Global error display */}
                    {errors && Object.keys(errors).length > 0 && (
                        <div className="flex items-start gap-3 p-4 rounded-[18px] bg-rose-50 border border-rose-200 text-rose-700 text-xs sm:text-sm">
                            <AlertCircle className="w-5 h-5 shrink-0 mt-0.5 text-rose-500" />
                            <div>
                                <strong className="font-semibold block mb-1">{__('general.payment_error')}</strong>
                                {Object.values(errors).map((err, i) => (
                                    <p key={i}>{err}</p>
                                ))}
                            </div>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6 max-w-3xl">

                        {/* Amount Selection Bento Card */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm space-y-6">
                            <div>
                                <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                    {__('general.select_amount')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 mt-0.5">
                                    {__('general.choose_a_preset_or_enter_a_custom_amount_to_deposit')}
                                </p>
                            </div>

                            {/* Presets Grid */}
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                                {presets.map((amount) => {
                                    const isSelected = selectedPreset === amount;
                                    return (
                                        <button
                                            type="button"
                                            key={amount}
                                            onClick={() => handlePresetClick(amount)}
                                            className={`h-12 rounded-[980px] text-xs sm:text-sm font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                                                isSelected
                                                    ? 'bg-[#1d1d1f] text-white shadow-md'
                                                    : 'bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] hover:bg-black/5'
                                            }`}
                                        >
                                            {isSelected && <Check className="w-3.5 h-3.5 text-emerald-400" />}
                                            {formatMoney(amount, walletCurrency)}
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Custom Amount Input */}
                            <div className="space-y-2 pt-2 border-t border-black/5 max-w-sm">
                                <Label htmlFor="custom-amount" className="text-xs font-semibold text-[#1d1d1f]">
                                    {__('general.custom_amount')}
                                </Label>
                                <div className="relative">
                                    <span className="absolute start-4 top-1/2 -translate-y-1/2 text-[#1d1d1f]/40 font-semibold text-xs pointer-events-none">
                                        {walletCurrency}
                                    </span>
                                    <Input
                                        id="custom-amount"
                                        type="number"
                                        step="0.01"
                                        min="5.00"
                                        placeholder="0.00"
                                        value={customAmount}
                                        onChange={handleCustomChange}
                                        className="h-11 ps-14 pe-4 bg-white border border-black/10 rounded-xl text-xs sm:text-sm font-semibold text-[#1d1d1f] focus:ring-2 focus:ring-[#0071e3] focus:border-[#0071e3]"
                                    />
                                </div>
                                {errors.amount && (
                                    <p className="text-xs font-medium text-rose-600">{errors.amount}</p>
                                )}
                            </div>

                            {/* Amount Summary Preview */}
                            {data.amount >= 5 && (
                                <div className="bg-[#f5f5f7] border border-black/5 rounded-[16px] p-4 flex items-center justify-between">
                                    <span className="text-xs font-medium text-[#1d1d1f]/70">You will deposit:</span>
                                    <span className="font-bold text-[#1d1d1f] font-mono text-base">
                                        {formatMoney(data.amount, walletCurrency)}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Payment Method Card */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 shadow-sm space-y-4">
                            <div>
                                <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                    {__('general.payment_method')}
                                </h3>
                                <p className="text-xs text-[#1d1d1f]/60 mt-0.5">
                                    {__('general.payments_are_processed_securely_via_kashier')}
                                </p>
                            </div>

                            <div className="flex items-center gap-4 p-4 rounded-[18px] border-2 border-[#0071e3] bg-[#0071e3]/5">
                                <div className="w-11 h-11 rounded-xl bg-[#0071e3] flex items-center justify-center text-white shrink-0 shadow-sm shadow-blue-500/20">
                                    <CreditCard className="w-5 h-5" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs sm:text-sm font-bold text-[#1d1d1f]">
                                        {__('general.kashier_card_amp_wallet')}
                                    </p>
                                    <p className="text-[11px] text-[#1d1d1f]/60 mt-0.5">
                                        {__('general.visa_mastercard_and_digital_wallets_accepted')}
                                    </p>
                                </div>
                                <div className="w-3 h-3 rounded-full bg-emerald-500 shrink-0" />
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                            <button
                                type="submit"
                                disabled={processing || !data.amount || data.amount < 5}
                                className="w-full sm:w-auto h-12 px-8 bg-[#0071e3] hover:bg-[#0077ed] disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-semibold rounded-[980px] shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                            >
                                {processing
                                    ? 'Redirecting to payment...'
                                    : `Pay ${formatMoney(data.amount || 0, walletCurrency)} via Kashier`
                                }
                            </button>

                            <div className="flex items-center gap-2 text-xs text-[#1d1d1f]/60">
                                <ShieldCheck className="h-4 w-4 text-emerald-500" />
                                <span>256-bit SSL secured. Instant balance credit.</span>
                            </div>
                        </div>

                        <p className="text-[11px] text-[#1d1d1f]/50 text-center">
                            Minimum deposit: {formatMoney(5, walletCurrency)}. You will be redirected to Kashier's secure portal.
                        </p>
                    </form>

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
