import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    ArrowLeft, ShieldCheck, CreditCard, AlertCircle, Wallet
} from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { formatMoney } from '@/lib/utils';

export default function AddBalance({ wallet }) {
    const [selectedPreset, setSelectedPreset] = useState(50);
    const [customAmount, setCustomAmount] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        amount: 50,
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

    const presets = [10, 25, 50, 100, 250, 500];
    const walletBalance = Number(wallet?.balance || 0);
    const walletCurrency = wallet?.currency;

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title={__('general.add_balance')} />

            <div className="max-w-2xl mx-auto px-4 py-8 space-y-6">
                {/* Header */}
                <div className="space-y-1">
                    <Link
                        href={safeRoute('dashboard', undefined, '/dashboard')}
                        className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <ArrowLeft className="mr-2 h-4 w-4" />{__('general.back_to_dashboard')}</Link>
                    <h1 className="text-2xl font-semibold tracking-tight">{__('general.add_balance')}</h1>
                    <p className="text-sm text-muted-foreground">{__('general.top_up_your_wallet_to_pay_for_subscriptions_and_platform_services')}</p>
                </div>

                {/* Current Wallet Balance */}
                <Card className="shadow-none border-slate-200 bg-gradient-to-r from-slate-900 to-slate-800 text-white">
                    <CardContent className="p-6 flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-slate-400 mb-1">{__('general.current_wallet_balance')}</p>
                            <div className="text-3xl font-bold tracking-tight">
                                {formatMoney(walletBalance, walletCurrency)}
                            </div>
                        </div>
                        <div className="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center">
                            <Wallet className="w-6 h-6 text-white" />
                        </div>
                    </CardContent>
                </Card>

                {/* Global error display */}
                {errors && Object.keys(errors).length > 0 && (
                    <div className="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        <AlertCircle className="w-5 h-5 shrink-0 mt-0.5 text-red-500" />
                        <div>
                            <strong className="font-semibold block mb-1">{__('general.payment_error')}</strong>
                            {Object.values(errors).map((err, i) => (
                                <p key={i}>{err}</p>
                            ))}
                        </div>
                    </div>
                )}

                {/* Deposit Form */}
                <form onSubmit={handleSubmit} className="space-y-6">

                    {/* Amount Selection */}
                    <Card className="shadow-none border-slate-200">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold">{__('general.select_amount')}</CardTitle>
                            <CardDescription>{__('general.choose_a_preset_or_enter_a_custom_amount_to_deposit')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                {presets.map((amount) => (
                                    <Button
                                        type="button"
                                        key={amount}
                                        variant={selectedPreset === amount ? 'default' : 'outline'}
                                        onClick={() => handlePresetClick(amount)}
                                        className={`h-11 text-sm font-semibold transition-all ${
                                            selectedPreset === amount
                                                ? 'bg-slate-900 text-white hover:bg-slate-800 border-slate-900'
                                                : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                                        }`}
                                    >
                                        {formatMoney(amount, walletCurrency)}
                                    </Button>
                                ))}
                            </div>

                            <div className="space-y-1.5 max-w-xs">
                                <Label htmlFor="custom-amount" className="text-sm font-medium">{__('general.custom_amount')}</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-sm pointer-events-none">
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
                                        className={`pl-12 shadow-none ${
                                            customAmount && !selectedPreset
                                                ? 'border-primary ring-1 ring-primary'
                                                : 'border-slate-200'
                                        }`}
                                    />
                                </div>
                                {errors.amount && (
                                    <p className="text-sm font-medium text-destructive">{errors.amount}</p>
                                )}
                            </div>

                            {/* Amount summary */}
                            {data.amount >= 5 && (
                                <div className="bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-center justify-between">
                                    <span className="text-sm text-slate-600">You will deposit:</span>
                                    <span className="font-bold text-slate-900 font-mono">
                                        {formatMoney(data.amount, walletCurrency)}
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Payment Method — Kashier only */}
                    <Card className="shadow-none border-slate-200">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold">{__('general.payment_method')}</CardTitle>
                            <CardDescription>{__('general.payments_are_processed_securely_via_kashier')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-4 p-4 rounded-xl border-2 border-primary bg-primary/5">
                                <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center shrink-0">
                                    <CreditCard className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-slate-900">{__('general.kashier_card_amp_wallet')}</p>
                                    <p className="text-xs text-slate-500">{__('general.visa_mastercard_and_digital_wallets_accepted')}</p>
                                </div>
                                <div className="ml-auto w-2 h-2 rounded-full bg-emerald-500" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-1">
                        <Button
                            type="submit"
                            size="lg"
                            disabled={processing || !data.amount || data.amount < 5}
                            className="w-full sm:w-auto h-12 px-8 text-base bg-slate-900 hover:bg-slate-800 text-white font-semibold shadow-sm"
                        >
                            {processing
                                ? 'Redirecting to payment...'
                                : `Pay ${formatMoney(data.amount || 0, walletCurrency)} via Kashier`
                            }
                        </Button>
                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                            <ShieldCheck className="h-4 w-4 text-emerald-500" />
                            <span>256-bit SSL secured. Funds credited instantly after payment.</span>
                        </div>
                    </div>

                    <p className="text-xs text-slate-400 text-center">
                        Minimum deposit: {formatMoney(5, walletCurrency)}. You will be redirected to Kashier's secure payment page to complete the transaction.
                    </p>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

