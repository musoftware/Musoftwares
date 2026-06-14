import React, { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Calculator as CalcIcon, CreditCard, Smartphone, CheckCircle2, Copy, AlertCircle, Info } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import axios from 'axios';

interface Props {
    input: number | null;
    result: number | null;
    amount_to_pay: number | null;
    calculation_type: 'visa_master' | 'mobile_wallet';
    ninety_percent: number | null;
    auth: {
        user: any;
    };
}

export default function Calculator({ input, result, amount_to_pay, calculation_type, ninety_percent, auth }: Props) {
    const { toast } = useToast();
    const [amount, setAmount] = useState<string>(input ? input.toString() : '');
    const [calcType, setCalcType] = useState<'visa_master' | 'mobile_wallet'>(calculation_type || 'visa_master');
    const [receiveAmount, setReceiveAmount] = useState<number | null>(result);
    const [amountToPay, setAmountToPay] = useState<number | null>(amount_to_pay);
    const [ninetyPercent, setNinetyPercent] = useState<number | null>(ninety_percent);
    const [signedPayUrl, setSignedPayUrl] = useState<string>('');

    const isLoggedIn = !!auth.user;

    useEffect(() => {
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount) || numAmount <= 0) {
            setReceiveAmount(null);
            setAmountToPay(null);
            setNinetyPercent(null);
            setSignedPayUrl('');
            return;
        }

        let receive = 0;
        let pay = 0;

        if (calcType === 'visa_master') {
            const fixedFee = 3;
            const serviceFee = numAmount * 0.03;
            const processingFee = Math.min(20, Math.max(0.5, numAmount * 0.005));
            receive = numAmount - fixedFee - serviceFee - processingFee;

            let pFee = Math.max(0.5, numAmount * 0.005);
            pFee = Math.min(20, pFee);
            pay = (numAmount + pFee + 3) / (1 - 0.03);
        } else {
            const fixedFee = 2;
            const serviceFee = numAmount * 0.025;
            const processingFee = Math.min(15, Math.max(0.5, numAmount * 0.003));
            receive = numAmount - fixedFee - serviceFee - processingFee;

            let pFee = Math.max(0.5, numAmount * 0.003);
            pFee = Math.min(15, pFee);
            pay = (numAmount + pFee + 2) / (1 - 0.025);
        }

        setReceiveAmount(receive);
        setAmountToPay(pay);
        setNinetyPercent(receive * 0.9);

        // Fetch signed url if logged in
        if (isLoggedIn) {
            const timeout = setTimeout(() => {
                axios.get(route('tools.calculator.signed-pay-url'), {
                    params: {
                        amount: pay,
                        balance_egp: numAmount,
                        you_will_receive: receive
                    }
                }).then(res => {
                    if (res.data.url) {
                        setSignedPayUrl(res.data.url);
                    }
                }).catch(err => {
                    console.error(err);
                    setSignedPayUrl('');
                });
            }, 350);
            return () => clearTimeout(timeout);
        }

    }, [amount, calcType, isLoggedIn]);

    const formatNumber = (val: number | null) => {
        if (val === null || isNaN(val)) return '0.00';
        return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const copyLink = async () => {
        if (!signedPayUrl) return;
        try {
            await navigator.clipboard.writeText(signedPayUrl);
            toast({ title: 'Copied', description: 'Payment link copied to clipboard!' });
        } catch (e) {
            toast({ title: 'Error', description: 'Failed to copy.', variant: 'destructive' });
        }
    };

    return (
        <ToolsPublicLayout title="Payment Calculator" activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8">
                    <span className="inline-block px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-sm font-medium mb-3">
                        Financial Tools
                    </span>
                    <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Payment Calculator</h1>
                    <p className="mt-2 text-lg text-slate-600">
                        Calculate transfer fees and process payments instantly.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Main Calculator Area */}
                    <div className="lg:col-span-8 space-y-6">
                        <Card className="bg-blue-50/50 border-blue-100 shadow-sm">
                            <CardContent className="p-4 flex gap-4">
                                <div className="mt-1">
                                    <Info className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="font-semibold text-blue-900 mb-1">Choose Your Payment Method</h3>
                                    <p className="text-sm text-blue-800 mb-2">Select how you want to pay and receive money. This tool handles 2 different scenarios:</p>
                                    <ul className="text-sm text-blue-800 list-disc pl-5 space-y-1">
                                        <li><strong>Visa/Master:</strong> Pay with credit card to receive funds</li>
                                        <li><strong>Mobile Wallet:</strong> Pay from mobile wallet to receive funds</li>
                                    </ul>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="space-y-4">
                            <Label className="text-base font-semibold">Calculation Type</Label>
                            <RadioGroup value={calcType} onValueChange={(val: any) => setCalcType(val)} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Label
                                    htmlFor="visa"
                                    className={`cursor-pointer border-2 rounded-xl p-4 flex items-start gap-4 transition-colors ${calcType === 'visa_master' ? 'border-indigo-600 bg-indigo-50/30' : 'border-slate-200 hover:border-indigo-300'}`}
                                >
                                    <RadioGroupItem value="visa_master" id="visa" className="mt-1" />
                                    <div>
                                        <div className="font-semibold text-slate-900 flex items-center gap-2">
                                            <CreditCard className="w-4 h-4 text-slate-500" /> Visa/Master
                                        </div>
                                        <p className="text-sm text-slate-500 mt-1">Pay with card → Get Funds</p>
                                    </div>
                                </Label>
                                <Label
                                    htmlFor="wallet"
                                    className={`cursor-pointer border-2 rounded-xl p-4 flex items-start gap-4 transition-colors ${calcType === 'mobile_wallet' ? 'border-indigo-600 bg-indigo-50/30' : 'border-slate-200 hover:border-indigo-300'}`}
                                >
                                    <RadioGroupItem value="mobile_wallet" id="wallet" className="mt-1" />
                                    <div>
                                        <div className="font-semibold text-slate-900 flex items-center gap-2">
                                            <Smartphone className="w-4 h-4 text-slate-500" /> Mobile Wallet
                                        </div>
                                        <p className="text-sm text-slate-500 mt-1">Pay from Mobile → Get Funds</p>
                                    </div>
                                </Label>
                            </RadioGroup>
                        </div>

                        <div className="space-y-2 pt-4">
                            <Label className="text-base font-semibold">Initial Amount (EGP)</Label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span className="text-slate-500 sm:text-sm">EGP</span>
                                </div>
                                <Input
                                    type="number"
                                    className="pl-12 h-14 text-lg"
                                    placeholder="Enter balance to process (e.g. 1000)"
                                    value={amount}
                                    onChange={e => setAmount(e.target.value)}
                                    min="0"
                                    step="0.01"
                                />
                            </div>
                            <p className="text-sm text-slate-500">Enter the amount you wish to withdraw or transfer</p>
                        </div>

                        {amountToPay !== null && amountToPay > 0 && (
                            <div className="mt-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-0 border rounded-xl overflow-hidden shadow-sm">
                                    <div className="bg-white p-6 border-b md:border-b-0 md:border-r border-slate-100">
                                        <p className="text-sm font-medium text-slate-500 mb-1">You Receive</p>
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-3xl font-bold text-emerald-600">{formatNumber(receiveAmount)}</span>
                                            <span className="text-sm font-medium text-emerald-600/70">EGP</span>
                                        </div>
                                    </div>
                                    <div className="bg-white p-6 border-b md:border-b-0 md:border-r border-slate-100">
                                        <p className="text-sm font-medium text-slate-500 mb-1">Amount to Pay</p>
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-3xl font-bold text-indigo-600">{formatNumber(amountToPay)}</span>
                                            <span className="text-sm font-medium text-indigo-600/70">EGP</span>
                                        </div>
                                    </div>
                                    <div className="bg-white p-6">
                                        <p className="text-sm font-medium text-slate-500 mb-1">90% Net</p>
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-3xl font-bold text-slate-700">{formatNumber(ninetyPercent)}</span>
                                            <span className="text-sm font-medium text-slate-500">EGP</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                                    <h4 className="font-medium text-slate-900 flex items-center gap-2 mb-1">
                                        {calcType === 'visa_master' ? <CreditCard className="w-4 h-4" /> : <Smartphone className="w-4 h-4" />}
                                        {calcType === 'visa_master' ? 'Visa/Master Payment' : 'Mobile Wallet Transfer'}
                                    </h4>
                                    <p className="text-sm text-slate-600">
                                        Pay <strong>{formatNumber(amountToPay)} EGP</strong> {calcType === 'visa_master' ? 'with your Visa/Master card' : 'from your mobile wallet'} to receive <strong>{formatNumber(receiveAmount)} EGP</strong> in your account.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="pt-8">
                            <h3 className="font-bold text-xl mb-4">How it works</h3>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                                <div className="p-4 bg-white rounded-xl border border-slate-100 shadow-sm">
                                    <CalcIcon className="w-8 h-8 mx-auto text-indigo-500 mb-3" />
                                    <h4 className="font-semibold text-sm mb-1">1. Calculate</h4>
                                    <p className="text-xs text-slate-500">Enter balance</p>
                                </div>
                                <div className="p-4 bg-white rounded-xl border border-slate-100 shadow-sm">
                                    <CreditCard className="w-8 h-8 mx-auto text-emerald-500 mb-3" />
                                    <h4 className="font-semibold text-sm mb-1">2. Pay</h4>
                                    <p className="text-xs text-slate-500">Secure checkout</p>
                                </div>
                                <div className="p-4 bg-white rounded-xl border border-slate-100 shadow-sm">
                                    <AlertCircle className="w-8 h-8 mx-auto text-amber-500 mb-3" />
                                    <h4 className="font-semibold text-sm mb-1">3. Transfer</h4>
                                    <p className="text-xs text-slate-500">We process it</p>
                                </div>
                                <div className="p-4 bg-white rounded-xl border border-slate-100 shadow-sm">
                                    <CheckCircle2 className="w-8 h-8 mx-auto text-blue-500 mb-3" />
                                    <h4 className="font-semibold text-sm mb-1">4. Complete</h4>
                                    <p className="text-xs text-slate-500">Get your funds</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Sidebar Area */}
                    <div className="lg:col-span-4 space-y-6">
                        {amountToPay !== null && amountToPay > 0 && (
                            <Card className="border-indigo-100 shadow-md">
                                <CardHeader className="bg-indigo-50/50 pb-4 border-b border-indigo-50">
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <CreditCard className="w-5 h-5 text-indigo-600" /> 
                                        {calcType === 'visa_master' ? 'Visa/Master Payment' : 'Mobile Wallet Transfer'}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    <div className="text-center mb-6">
                                        {isLoggedIn ? (
                                            <>
                                                <h4 className="font-semibold text-slate-900">Welcome back, {auth.user.name}!</h4>
                                                <p className="text-sm text-slate-500 mt-1">Your account is ready for instant processing.</p>
                                            </>
                                        ) : (
                                            <>
                                                <h4 className="font-semibold text-slate-900">Payment Calculator</h4>
                                                <p className="text-sm text-slate-500 mt-1">Calculate fees and explore payment options.</p>
                                            </>
                                        )}
                                    </div>

                                    {isLoggedIn && (
                                        <div className="mb-6 space-y-2">
                                            <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Quick Link</Label>
                                            <div className="flex gap-2">
                                                <Input readOnly value={signedPayUrl} className="bg-slate-50 text-xs" />
                                                <Button variant="secondary" size="icon" onClick={copyLink} disabled={!signedPayUrl}>
                                                    <Copy className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    )}

                                    <div className="space-y-3">
                                        {isLoggedIn ? (
                                            <>
                                                <Button className="w-full h-12 text-base font-semibold" disabled={!signedPayUrl} onClick={() => window.location.href = signedPayUrl}>
                                                    <CreditCard className="w-5 h-5 mr-2" /> Secure Checkout
                                                </Button>
                                                <Button variant="outline" className="w-full" onClick={() => router.get(route('dashboard'))}>
                                                    Back to Dashboard
                                                </Button>
                                            </>
                                        ) : (
                                            <>
                                                <Button className="w-full h-12 text-base font-semibold bg-indigo-600 hover:bg-indigo-700" onClick={() => router.get(route('tools.pay-guest'))}>
                                                    Pay as Guest
                                                </Button>
                                                <Button variant="ghost" className="w-full text-sm" onClick={() => router.get(route('login'))}>
                                                    Login for Full Features
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm flex items-center gap-2">
                                    <Info className="w-4 h-4" /> Fee Structure
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-2">
                                {calcType === 'visa_master' ? (
                                    <div className="space-y-3">
                                        <h5 className="text-xs font-bold text-slate-500 uppercase tracking-wider">Visa/Master Payment</h5>
                                        <ul className="text-sm text-slate-600 space-y-2">
                                            <li className="flex justify-between"><span>Fixed Fee:</span> <span className="font-semibold text-slate-900">3 EGP</span></li>
                                            <li className="flex justify-between"><span>Service Fee:</span> <span className="font-semibold text-slate-900">3%</span></li>
                                            <li className="flex justify-between"><span>Processing Min/Max:</span> <span className="font-semibold text-slate-900">0.5 - 20 EGP</span></li>
                                        </ul>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        <h5 className="text-xs font-bold text-slate-500 uppercase tracking-wider">Mobile Wallet Transfer</h5>
                                        <ul className="text-sm text-slate-600 space-y-2">
                                            <li className="flex justify-between"><span>Fixed Fee:</span> <span className="font-semibold text-slate-900">2 EGP</span></li>
                                            <li className="flex justify-between"><span>Service Fee:</span> <span className="font-semibold text-slate-900">2.5%</span></li>
                                            <li className="flex justify-between"><span>Processing Min/Max:</span> <span className="font-semibold text-slate-900">0.5 - 15 EGP</span></li>
                                        </ul>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
