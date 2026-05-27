import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import confetti from 'canvas-confetti';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function Onboarding({ currencies = [] }) {
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        businessName: '',
        baseCurrency: 'USD',
        timezone: 'UTC',
        clientName: '',
        clientEmail: '',
        clientCurrency: 'USD',
        invoiceDesc: '',
        invoiceAmount: '',
    });

    const handleComplete = (e) => {
        e?.preventDefault();
        
        confetti({
            particleCount: 150,
            spread: 80,
            origin: { y: 0.6 }
        });

        post(route('erp.onboarding.complete'));
    };

    return (
        <AuthenticatedLayout header="ERP Setup Wizard">
            <Head title="Configure ERP" />

            <div className="py-12 px-4 max-w-2xl mx-auto">
                <Card className="shadow-none border bg-card text-card-foreground">
                    <CardContent className="p-8">
                        {/* Progress Bar */}
                        <div className="mb-8 text-center">
                            <p className="text-sm text-muted-foreground mb-3 font-medium">Step {step} of 4</p>
                            <div className="flex justify-center space-x-2">
                                {[1, 2, 3, 4].map((s) => (
                                    <div
                                        key={s}
                                        className={`h-2 w-10 rounded-full transition-colors ${s <= step ? 'bg-primary' : 'bg-muted'}`}
                                    />
                                ))}
                            </div>
                        </div>

                        {errors.error && (
                            <div className="mb-6 p-4 text-sm bg-destructive/10 border border-destructive/20 text-destructive rounded-lg font-medium">
                                {errors.error}
                            </div>
                        )}

                        {/* Step 1: Business Setup */}
                        {step === 1 && (
                            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2">
                                <div className="space-y-1">
                                    <h3 className="text-2xl font-bold tracking-tight">Business Setup</h3>
                                    <p className="text-sm text-muted-foreground">Configure your foundational workspace settings.</p>
                                </div>
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="businessName">Business Name</Label>
                                        <Input
                                            id="businessName"
                                            value={data.businessName}
                                            onChange={e => setData('businessName', e.target.value)}
                                            placeholder="Acme Inc"
                                            required
                                        />
                                        {errors.businessName && <p className="text-xs text-destructive">{errors.businessName}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="baseCurrency">Base Currency</Label>
                                        <select
                                            id="baseCurrency"
                                            value={data.baseCurrency}
                                            onChange={e => {
                                                setData(data => ({
                                                    ...data,
                                                    baseCurrency: e.target.value,
                                                    clientCurrency: e.target.value
                                                }));
                                            }}
                                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        >
                                            {currencies.map(c => (
                                                <option key={c.id} value={c.currency}>{c.currency} ({c.symbol})</option>
                                            ))}
                                            {currencies.length === 0 && (
                                                <>
                                                    <option value="USD">USD ($)</option>
                                                    <option value="EUR">EUR (€)</option>
                                                    <option value="GBP">GBP (£)</option>
                                                    <option value="EGP">EGP (E£)</option>
                                                </>
                                            )}
                                        </select>
                                        {errors.baseCurrency && <p className="text-xs text-destructive">{errors.baseCurrency}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="timezone">Timezone</Label>
                                        <select
                                            id="timezone"
                                            value={data.timezone}
                                            onChange={e => setData('timezone', e.target.value)}
                                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        >
                                            <option value="UTC">UTC</option>
                                            <option value="Europe/London">Europe/London</option>
                                            <option value="America/New_York">America/New_York</option>
                                            <option value="Africa/Cairo">Africa/Cairo</option>
                                        </select>
                                        {errors.timezone && <p className="text-xs text-destructive">{errors.timezone}</p>}
                                    </div>
                                </div>
                                <Button
                                    onClick={() => data.businessName ? setStep(2) : alert('Please enter a business name')}
                                    className="w-full h-11 shadow-none"
                                >
                                    Continue →
                                </Button>
                            </div>
                        )}

                        {/* Step 2: Add First Client */}
                        {step === 2 && (
                            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2">
                                <div className="space-y-1">
                                    <h3 className="text-2xl font-bold tracking-tight">Add First Client (Optional)</h3>
                                    <p className="text-sm text-muted-foreground">Create a profile for your first tenant client.</p>
                                </div>
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="clientName">Client Name</Label>
                                        <Input
                                            id="clientName"
                                            value={data.clientName}
                                            onChange={e => setData('clientName', e.target.value)}
                                            placeholder="John Doe"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="clientEmail">Email</Label>
                                        <Input
                                            id="clientEmail"
                                            type="email"
                                            value={data.clientEmail}
                                            onChange={e => setData('clientEmail', e.target.value)}
                                            placeholder="john@example.com"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="clientCurrency">Currency</Label>
                                        <select
                                            id="clientCurrency"
                                            value={data.clientCurrency}
                                            onChange={e => setData('clientCurrency', e.target.value)}
                                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                        >
                                            {currencies.map(c => (
                                                <option key={c.id} value={c.currency}>{c.currency} ({c.symbol})</option>
                                            ))}
                                            {currencies.length === 0 && (
                                                <>
                                                    <option value="USD">USD ($)</option>
                                                    <option value="EUR">EUR (€)</option>
                                                    <option value="GBP">GBP (£)</option>
                                                    <option value="EGP">EGP (E£)</option>
                                                </>
                                            )}
                                        </select>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setData(data => ({
                                                ...data,
                                                clientName: '',
                                                clientEmail: '',
                                            }));
                                            setStep(4);
                                        }}
                                        className="w-1/3"
                                    >
                                        Skip
                                    </Button>
                                    <Button
                                        onClick={() => data.clientName ? setStep(3) : alert('Please enter client name or click Skip')}
                                        className="w-2/3 shadow-none"
                                    >
                                        Continue
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Step 3: Create First Invoice */}
                        {step === 3 && (
                            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2">
                                <div className="space-y-1">
                                    <h3 className="text-2xl font-bold tracking-tight">Create First Invoice (Optional)</h3>
                                    <p className="text-sm text-muted-foreground">Draft your first billable invoice for {data.clientName}.</p>
                                </div>
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="invoiceDesc">Title / Description</Label>
                                        <Input
                                            id="invoiceDesc"
                                            value={data.invoiceDesc}
                                            onChange={e => setData('invoiceDesc', e.target.value)}
                                            placeholder="Web Design Services"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="invoiceAmount">Amount ({data.clientCurrency})</Label>
                                        <Input
                                            id="invoiceAmount"
                                            type="number"
                                            value={data.invoiceAmount}
                                            onChange={e => setData('invoiceAmount', e.target.value)}
                                            placeholder="1000.00"
                                        />
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setData(data => ({
                                                ...data,
                                                invoiceDesc: '',
                                                invoiceAmount: '',
                                            }));
                                            setStep(4);
                                        }}
                                        className="w-1/3"
                                    >
                                        Skip
                                    </Button>
                                    <Button
                                        onClick={() => data.invoiceDesc && data.invoiceAmount ? setStep(4) : alert('Fill in the fields or click Skip')}
                                        className="w-2/3 shadow-none"
                                    >
                                        Continue
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Step 4: Summary & Complete */}
                        {step === 4 && (
                            <div className="text-center space-y-6 py-8 animate-in zoom-in-95">
                                <div className="space-y-2">
                                    <h3 className="text-3xl font-bold tracking-tight text-primary">Ready to launch! 🎉</h3>
                                    <p className="text-muted-foreground">Review your configuration summary before entering your Workspace.</p>
                                </div>
                                <div className="bg-muted p-5 rounded-xl text-left inline-block w-full max-w-sm mx-auto border border-border">
                                    <h4 className="font-semibold text-foreground mb-3 text-sm">Summary</h4>
                                    <ul className="space-y-2 text-sm text-muted-foreground font-medium">
                                        <li className="flex items-center gap-2">
                                            <div className="w-1.5 h-1.5 rounded-full bg-primary" />
                                            Business: {data.businessName}
                                        </li>
                                        <li className="flex items-center gap-2">
                                            <div className="w-1.5 h-1.5 rounded-full bg-primary" />
                                            Currency: {data.baseCurrency}
                                        </li>
                                        {data.clientName && (
                                            <li className="flex items-center gap-2">
                                                <div className="w-1.5 h-1.5 rounded-full bg-primary" />
                                                Client: {data.clientName} ({data.clientCurrency})
                                            </li>
                                        )}
                                        {data.invoiceDesc && (
                                            <li className="flex items-center gap-2">
                                                <div className="w-1.5 h-1.5 rounded-full bg-primary" />
                                                First Invoice: {data.invoiceDesc} (${data.invoiceAmount})
                                            </li>
                                        )}
                                    </ul>
                                </div>
                                <div className="pt-4">
                                    <Button
                                        onClick={handleComplete}
                                        disabled={processing}
                                        size="lg"
                                        className="px-8 shadow-none"
                                    >
                                        {processing ? 'Creating Workspace...' : 'Initialize Workspace & Enter →'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
