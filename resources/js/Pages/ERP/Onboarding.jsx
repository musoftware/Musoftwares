import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import confetti from 'canvas-confetti';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function Onboarding() {
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

        post(route('erp.onboarding.store'));
    };

    return (
        <AuthenticatedLayout header="ERP Setup Wizard">
            <Head title="Configure Business OS" />

            <div className="py-16 px-4 max-w-2xl mx-auto font-sans">
                <div className="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                    <div className="p-8 md:p-12">
                        {/* Progress Bar */}
                        <div className="mb-10 text-center">
                            <p className="text-xs text-slate-400 mb-3 font-semibold uppercase tracking-wider">Step {step} of 4</p>
                            <div className="flex justify-center space-x-2">
                                {[1, 2, 3, 4].map((s) => (
                                    <div
                                        key={s}
                                        className={`h-1.5 w-12 rounded-full transition-colors duration-300 ${s <= step ? 'bg-slate-900' : 'bg-slate-100'}`}
                                    />
                                ))}
                            </div>
                        </div>

                        {errors.error && (
                            <div className="mb-6 p-4 text-sm bg-rose-50 border border-rose-100 text-rose-600 rounded-xl font-medium">
                                {errors.error}
                            </div>
                        )}

                        {/* Step 1: Business Setup */}
                        {step === 1 && (
                            <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <div className="space-y-2 text-center">
                                    <h3 className="text-3xl font-bold tracking-tight text-slate-900">Business Setup</h3>
                                    <p className="text-sm text-slate-500">Configure your foundational workspace settings.</p>
                                </div>
                                <div className="space-y-5 bg-slate-50/50 p-6 md:p-8 rounded-2xl border border-slate-100">
                                    <div className="space-y-2">
                                        <Label htmlFor="businessName" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Business Name</Label>
                                        <Input
                                            id="businessName"
                                            value={data.businessName}
                                            onChange={e => setData('businessName', e.target.value)}
                                            placeholder="Acme Inc"
                                            className="h-11 border-slate-200 bg-white"
                                            required
                                        />
                                        {errors.businessName && <p className="text-xs text-rose-500">{errors.businessName}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="baseCurrency" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Base Currency</Label>
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
                                            className="flex h-11 w-full items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                        >
                                            <option value="USD">USD ($)</option>
                                            <option value="EUR">EUR (€)</option>
                                            <option value="GBP">GBP (£)</option>
                                            <option value="EGP">EGP (E£)</option>
                                        </select>
                                        {errors.baseCurrency && <p className="text-xs text-rose-500">{errors.baseCurrency}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="timezone" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Timezone</Label>
                                        <select
                                            id="timezone"
                                            value={data.timezone}
                                            onChange={e => setData('timezone', e.target.value)}
                                            className="flex h-11 w-full items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                        >
                                            <option value="UTC">UTC</option>
                                            <option value="Europe/London">Europe/London</option>
                                            <option value="America/New_York">America/New_York</option>
                                            <option value="Africa/Cairo">Africa/Cairo</option>
                                        </select>
                                        {errors.timezone && <p className="text-xs text-rose-500">{errors.timezone}</p>}
                                    </div>
                                </div>
                                <Button
                                    onClick={() => data.businessName ? setStep(2) : alert('Please enter a business name')}
                                    className="w-full h-12 bg-slate-900 text-white hover:bg-slate-800 text-base"
                                >
                                    Continue
                                </Button>
                            </div>
                        )}

                        {/* Step 2: Add First Client */}
                        {step === 2 && (
                            <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <div className="space-y-2 text-center">
                                    <h3 className="text-3xl font-bold tracking-tight text-slate-900">Add First Client <span className="text-slate-400 font-normal">(Optional)</span></h3>
                                    <p className="text-sm text-slate-500">Create a profile for your first tenant client.</p>
                                </div>
                                <div className="space-y-5 bg-slate-50/50 p-6 md:p-8 rounded-2xl border border-slate-100">
                                    <div className="space-y-2">
                                        <Label htmlFor="clientName" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Client Name</Label>
                                        <Input
                                            id="clientName"
                                            value={data.clientName}
                                            onChange={e => setData('clientName', e.target.value)}
                                            placeholder="John Doe"
                                            className="h-11 border-slate-200 bg-white"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="clientEmail" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</Label>
                                        <Input
                                            id="clientEmail"
                                            type="email"
                                            value={data.clientEmail}
                                            onChange={e => setData('clientEmail', e.target.value)}
                                            placeholder="john@example.com"
                                            className="h-11 border-slate-200 bg-white"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="clientCurrency" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Currency</Label>
                                        <select
                                            id="clientCurrency"
                                            value={data.clientCurrency}
                                            onChange={e => setData('clientCurrency', e.target.value)}
                                            className="flex h-11 w-full items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                        >
                                            <option value="USD">USD ($)</option>
                                            <option value="EUR">EUR (€)</option>
                                            <option value="GBP">GBP (£)</option>
                                            <option value="EGP">EGP (E£)</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="flex gap-4">
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
                                        className="w-1/3 h-12 border-slate-200 text-slate-600 hover:bg-slate-50 text-base"
                                    >
                                        Skip
                                    </Button>
                                    <Button
                                        onClick={() => data.clientName ? setStep(3) : alert('Please enter client name or click Skip')}
                                        className="w-2/3 h-12 bg-slate-900 text-white hover:bg-slate-800 text-base"
                                    >
                                        Continue
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Step 3: Create First Invoice */}
                        {step === 3 && (
                            <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <div className="space-y-2 text-center">
                                    <h3 className="text-3xl font-bold tracking-tight text-slate-900">Create First Invoice <span className="text-slate-400 font-normal">(Optional)</span></h3>
                                    <p className="text-sm text-slate-500">Draft your first billable invoice for <span className="font-medium text-slate-900">{data.clientName}</span>.</p>
                                </div>
                                <div className="space-y-5 bg-slate-50/50 p-6 md:p-8 rounded-2xl border border-slate-100">
                                    <div className="space-y-2">
                                        <Label htmlFor="invoiceDesc" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Title / Description</Label>
                                        <Input
                                            id="invoiceDesc"
                                            value={data.invoiceDesc}
                                            onChange={e => setData('invoiceDesc', e.target.value)}
                                            placeholder="Web Design Services"
                                            className="h-11 border-slate-200 bg-white"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="invoiceAmount" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount ({data.clientCurrency})</Label>
                                        <Input
                                            id="invoiceAmount"
                                            type="number"
                                            value={data.invoiceAmount}
                                            onChange={e => setData('invoiceAmount', e.target.value)}
                                            placeholder="1000.00"
                                            className="h-11 border-slate-200 bg-white font-mono"
                                        />
                                    </div>
                                </div>
                                <div className="flex gap-4">
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
                                        className="w-1/3 h-12 border-slate-200 text-slate-600 hover:bg-slate-50 text-base"
                                    >
                                        Skip
                                    </Button>
                                    <Button
                                        onClick={() => data.invoiceDesc && data.invoiceAmount ? setStep(4) : alert('Fill in the fields or click Skip')}
                                        className="w-2/3 h-12 bg-slate-900 text-white hover:bg-slate-800 text-base"
                                    >
                                        Continue
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Step 4: Summary & Complete */}
                        {step === 4 && (
                            <div className="text-center space-y-8 py-4 animate-in zoom-in-95 duration-500">
                                <div className="space-y-3">
                                    <h3 className="text-4xl font-bold tracking-tight text-slate-900">Ready to launch! 🎉</h3>
                                    <p className="text-slate-500">Review your configuration summary before entering your Workspace.</p>
                                </div>
                                <div className="bg-slate-50 p-6 md:p-8 rounded-2xl text-left inline-block w-full max-w-sm mx-auto border border-slate-100 shadow-sm">
                                    <h4 className="font-semibold text-slate-900 mb-4 text-sm uppercase tracking-wider">Summary</h4>
                                    <ul className="space-y-3 text-sm text-slate-600 font-medium">
                                        <li className="flex items-center gap-3">
                                            <div className="w-1.5 h-1.5 rounded-full bg-indigo-500" />
                                            Business: <span className="text-slate-900 ml-1">{data.businessName}</span>
                                        </li>
                                        <li className="flex items-center gap-3">
                                            <div className="w-1.5 h-1.5 rounded-full bg-indigo-500" />
                                            Currency: <span className="text-slate-900 ml-1">{data.baseCurrency}</span>
                                        </li>
                                        {data.clientName && (
                                            <li className="flex items-center gap-3">
                                                <div className="w-1.5 h-1.5 rounded-full bg-indigo-500" />
                                                Client: <span className="text-slate-900 ml-1">{data.clientName}</span> ({data.clientCurrency})
                                            </li>
                                        )}
                                        {data.invoiceDesc && (
                                            <li className="flex items-center gap-3">
                                                <div className="w-1.5 h-1.5 rounded-full bg-indigo-500" />
                                                First Invoice: <span className="text-slate-900 ml-1">{data.invoiceDesc}</span> (${data.invoiceAmount})
                                            </li>
                                        )}
                                    </ul>
                                </div>
                                <div className="pt-6">
                                    <Button
                                        onClick={handleComplete}
                                        disabled={processing}
                                        size="lg"
                                        className="h-14 px-10 bg-slate-900 text-white hover:bg-slate-800 text-base shadow-lg shadow-slate-900/10 transition-all hover:scale-[1.02]"
                                    >
                                        {processing ? 'Creating Workspace...' : 'Initialize Workspace & Enter'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
