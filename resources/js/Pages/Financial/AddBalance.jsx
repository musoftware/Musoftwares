import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    ArrowLeft, ShieldCheck
} from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function AddBalance({ wallet }: { wallet: any }) {
    const [selectedPreset, setSelectedPreset] = useState<number | null>(50);
    const [customAmount, setCustomAmount] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        amount: 50,
    });

    const handlePresetClick = (val: number) => {
        setSelectedPreset(val);
        setCustomAmount('');
        setData('amount', val);
    };

    const handleCustomChange = (e: React.ChangeEvent<HTMLInputElement>) => {
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

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('financial.add-balance.kashier'));
    };

    const presets = [10, 50, 100, 250, 500];

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

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Deposit Funds" />

            <div className="max-w-2xl mx-auto px-4 py-8 space-y-6">
                {/* Header */}
                <div className="space-y-2">
                    <Link href={safeRoute('client.wallet.index', undefined, '/wallet')} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                        <ArrowLeft className="mr-2 h-4 w-4" /> Back to Wallet
                    </Link>
                    <h1 className="text-2xl font-semibold tracking-tight">Deposit Funds</h1>
                    <p className="text-sm text-muted-foreground">Add balance to your wallet for seamless platform transactions.</p>
                </div>

                {/* Compact Wallet Summary Card */}
                <Card className="shadow-none">
                    <CardContent className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">Wallet Balance</p>
                                <div className="text-3xl font-bold tracking-tight text-foreground">
                                    ${Number(wallet?.balance || 0).toLocaleString(undefined, {minimumFractionDigits: 2})} <span className="text-lg font-normal text-muted-foreground">{wallet?.currency || 'USD'}</span>
                                </div>
                            </div>
                            <div className="text-sm text-muted-foreground max-w-[200px]">
                                Available for invoices, subscriptions, and platform services.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Deposit Flow Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    
                    {/* Amount Selection */}
                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle className="text-lg">1. Deposit Amount</CardTitle>
                            <CardDescription>Select a preset or enter a custom amount to deposit.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid grid-cols-3 sm:grid-cols-5 gap-3">
                                {presets.map((amount) => (
                                    <Button
                                        type="button"
                                        key={amount}
                                        variant={selectedPreset === amount ? "default" : "outline"}
                                        onClick={() => handlePresetClick(amount)}
                                        className="h-12 text-base font-medium"
                                    >
                                        ${amount}
                                    </Button>
                                ))}
                            </div>

                            <div className="space-y-2 max-w-xs">
                                <Label htmlFor="custom-amount">Custom Amount</Label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span className="text-muted-foreground font-medium">$</span>
                                    </div>
                                    <Input
                                        id="custom-amount"
                                        type="number"
                                        step="0.01"
                                        min="5.00"
                                        placeholder="0.00"
                                        value={customAmount}
                                        onChange={handleCustomChange}
                                        className={`pl-8 shadow-none ${customAmount && !selectedPreset ? "border-primary ring-1 ring-primary" : ""}`}
                                    />
                                </div>
                                {errors.amount && (
                                    <p className="text-sm font-medium text-destructive mt-2">{errors.amount}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Confirmation & Action */}
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                        <Button
                            type="submit"
                            size="lg"
                            disabled={processing || !data.amount || data.amount < 5}
                            className="w-full sm:w-auto h-12 px-8 text-base shadow-none"
                        >
                            {processing ? 'Processing...' : `Deposit $${Number(data.amount || 0).toFixed(2)} via Kashier`}
                        </Button>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ShieldCheck className="h-4 w-4" />
                            <span>Secure encrypted payment.</span>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
