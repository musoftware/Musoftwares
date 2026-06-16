import React, { useState, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { DollarSign, ShieldCheck, HelpCircle, Receipt, Percent, Settings, ArrowRight, Wallet } from 'lucide-react';

export default function PayoutUsd() {
    const [usdAmount, setUsdAmount] = useState(10);
    // Mock exchange rate for USD to EGP
    const exchangeRate = 48.50; 

    const results = useMemo(() => {
        if (!usdAmount || usdAmount <= 0) return null;

        const baseEgp = usdAmount * exchangeRate;
        
        let current = baseEgp;
        const steps: any[] = [];

        steps.push({
            label: 'Base Conversion',
            amount: current,
            fee: 0,
            percent: 0,
            icon: <DollarSign className="w-5 h-5 text-emerald-500" />
        });

        // Service Fee (1%)
        let prev = current;
        current = prev / (1 - 0.01);
        steps.push({
            label: 'Service Fee',
            amount: current,
            fee: current - prev,
            percent: 1.0,
            icon: <Settings className="w-5 h-5 text-slate-500" />
        });

        // Processing Fee (4.4%)
        prev = current;
        current = prev / (1 - 0.044);
        steps.push({
            label: 'Processing Fee',
            amount: current,
            fee: current - prev,
            percent: 4.4,
            icon: <Receipt className="w-5 h-5 text-indigo-500" />
        });

        // Transaction Fee (5%)
        prev = current;
        current = prev / (1 - 0.05);
        steps.push({
            label: 'Transaction Fee',
            amount: current,
            fee: current - prev,
            percent: 5.0,
            icon: <Wallet className="w-5 h-5 text-amber-500" />
        });

        // Regulatory Adjustment (4.75%)
        prev = current;
        current = prev / (1 - 0.0475);
        steps.push({
            label: 'Regulatory Adjustment',
            amount: current,
            fee: current - prev,
            percent: 4.75,
            icon: <ShieldCheck className="w-5 h-5 text-red-500" />
        });

        const totalFees = current - baseEgp;
        const effectiveRate = current / usdAmount;
        const purity = (baseEgp / current) * 100;
        const feePercent = ((current - baseEgp) / baseEgp) * 100;

        return {
            steps,
            total: current,
            effectiveRate,
            totalFees,
            purity,
            feePercent,
            baseEgp
        };

    }, [usdAmount]);

    const formatMoney = (val: number, currency: string = 'EGP') => 
        new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(val);

    return (
        <WebToolsLayout title="USD Payout Tool" activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="mb-8">
                    <span className="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium mb-3">
                        Global Service Calculator
                    </span>
                    <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <DollarSign className="w-8 h-8 text-emerald-500" />
                        USD Payout Calculator
                    </h1>
                    <p className="mt-2 text-lg text-slate-600">
                        Real-time EGP conversion for global software and hosting services.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Calculator Section */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="border-slate-200 shadow-sm border-t-4 border-t-emerald-500">
                            <CardHeader className="bg-slate-50 border-b border-slate-100">
                                <CardTitle className="text-lg">Amount to Pay (USD)</CardTitle>
                                <CardDescription>Enter the service invoice amount in USD</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="space-y-4">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span className="text-slate-500 font-bold text-xl">$</span>
                                        </div>
                                        <Input 
                                            type="number" 
                                            value={usdAmount || ''} 
                                            onChange={e => setUsdAmount(Number(e.target.value))} 
                                            className="pl-10 text-2xl h-14 font-bold"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div className="flex items-center justify-between text-sm text-slate-500 bg-slate-50 p-3 rounded-lg border border-slate-100">
                                        <span>Current Bank Rate:</span>
                                        <span className="font-bold text-slate-900">{formatMoney(exchangeRate, 'EGP')}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Info Cards */}
                        <Card className="border-none shadow-sm bg-blue-50/50">
                            <CardContent className="p-5">
                                <div className="flex items-center gap-3 mb-2">
                                    <HelpCircle className="w-5 h-5 text-blue-500" />
                                    <h5 className="font-bold text-slate-900">What is this tool?</h5>
                                </div>
                                <p className="text-sm text-slate-600 leading-relaxed">
                                    This tool calculates the total EGP amount required to purchase USD-denominated services when paying through local providers. It accounts for market exchange rates and all intermediary processing fees.
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm bg-amber-50/50">
                            <CardContent className="p-5">
                                <div className="flex items-center gap-3 mb-2">
                                    <ShieldCheck className="w-5 h-5 text-amber-500" />
                                    <h5 className="font-bold text-slate-900">Transparency & Fees</h5>
                                </div>
                                <p className="text-sm text-slate-600 leading-relaxed">
                                    Our calculation includes standard conversion fees (1%), processing (4.4%), and regulatory transaction fees typical for EGP-to-USD service flows.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Results Section */}
                    <div className="lg:col-span-7">
                        {results ? (
                            <div className="space-y-6">
                                <Card className="bg-slate-900 text-white border-none shadow-lg">
                                    <CardContent className="p-8">
                                        <div className="text-center mb-8">
                                            <p className="text-slate-400 font-medium mb-2 uppercase tracking-widest text-sm">Total Required (EGP)</p>
                                            <h2 className="text-5xl font-black text-emerald-400">
                                                {formatMoney(results.total, 'EGP').replace('EGP', '').trim()} <span className="text-2xl text-emerald-600">EGP</span>
                                            </h2>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4 border-t border-slate-800 pt-6">
                                            <div>
                                                <p className="text-slate-500 text-sm mb-1">Effective Rate (Per USD)</p>
                                                <p className="text-xl font-bold">{formatMoney(results.effectiveRate, 'EGP')}</p>
                                            </div>
                                            <div>
                                                <p className="text-slate-500 text-sm mb-1">Total Fees</p>
                                                <p className="text-xl font-bold text-amber-400">{formatMoney(results.totalFees, 'EGP')}</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="border-slate-200 shadow-sm">
                                    <CardHeader className="pb-4 border-b border-slate-100">
                                        <CardTitle className="text-lg">Fee Breakdown Step-by-Step</CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-0">
                                        <div className="divide-y divide-slate-100">
                                            {results.steps.map((step, idx) => (
                                                <div key={idx} className="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-slate-100 rounded-lg">
                                                            {step.icon}
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-slate-900">{step.label}</p>
                                                            {step.percent > 0 && (
                                                                <p className="text-xs text-slate-500 font-medium">{step.percent}% margin</p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="font-bold text-slate-900">{formatMoney(step.amount, 'EGP')}</p>
                                                        {step.fee > 0 && (
                                                            <p className="text-xs text-red-500 font-medium">+{formatMoney(step.fee, 'EGP')} fee</p>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        ) : (
                            <Card className="h-full flex flex-col items-center justify-center p-12 text-center text-slate-500 border-dashed border-2 border-slate-200 bg-slate-50/50">
                                <DollarSign className="w-16 h-16 text-slate-300 mb-4" />
                                <h3 className="text-xl font-medium text-slate-700">Enter USD Amount</h3>
                                <p className="mt-2 text-sm">Please provide a valid USD amount to calculate the final EGP payout requirement.</p>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </WebToolsLayout>
    );
}
