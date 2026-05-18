import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    ChevronRight, ChevronLeft, Check, Plus, Trash2,
    Layers, DollarSign, FileText, Send, ArrowLeft
} from 'lucide-react';

interface Category { id: number; name: string; slug: string; }
interface Props { categories: Category[]; }

const STEPS = [
    { id: 1, label: 'Overview',    icon: Layers,     desc: 'Title & category' },
    { id: 2, label: 'Pricing',     icon: DollarSign, desc: 'Packages & rates' },
    { id: 3, label: 'Description', icon: FileText,   desc: 'Describe your service' },
    { id: 4, label: 'Publish',     icon: Send,        desc: 'Review & submit' },
];

const CURRENCIES = ['USD', 'EUR', 'GBP', 'EGP', 'SAR', 'AED'];

const emptyPackage = () => ({
    name: '', description: '', price: '', currency_code: 'USD', delivery_days: 3,
});

export default function CreateService({ categories }: Props) {
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        category_id: '',
        packages: [emptyPackage()],
    });

    const setPackageField = (idx: number, field: string, value: any) => {
        const pkgs = [...data.packages] as any[];
        pkgs[idx] = { ...pkgs[idx], [field]: value };
        setData('packages', pkgs);
    };

    const addPackage = () => {
        if (data.packages.length < 3) setData('packages', [...data.packages, emptyPackage()]);
    };

    const removePackage = (idx: number) => {
        if (data.packages.length > 1) setData('packages', data.packages.filter((_, i) => i !== idx));
    };

    const canNext = () => {
        if (step === 1) return data.title.trim().length >= 10 && data.category_id;
        if (step === 2) return data.packages.every(p => p.name && p.price && Number(p.price) >= 1 && p.delivery_days >= 1);
        if (step === 3) return data.description.trim().length >= 100;
        return true;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('marketplace.services.store'));
    };

    const PKG_LABELS = ['Basic', 'Standard', 'Premium'];

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Publish a Service" />

            <div className="min-h-screen bg-slate-50">
                {/* Top bar */}
                <div className="bg-white border-b border-slate-200 sticky top-16 z-30">
                    <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                        <Link href="/marketplace/dashboard" className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-4 h-4" /> Back
                        </Link>
                        <h1 className="text-base font-semibold text-slate-900">Publish a Service</h1>
                        <div className="text-xs text-slate-400">Step {step} of {STEPS.length}</div>
                    </div>

                    {/* Step tabs */}
                    <div className="max-w-5xl mx-auto px-4 sm:px-6">
                        <div className="flex">
                            {STEPS.map((s, i) => {
                                const done = step > s.id;
                                const active = step === s.id;
                                return (
                                    <button
                                        key={s.id}
                                        onClick={() => done && setStep(s.id)}
                                        disabled={!done}
                                        className={cn(
                                            'flex-1 flex flex-col sm:flex-row items-center gap-1.5 px-3 py-3 text-xs font-semibold border-b-2 transition-all',
                                            active ? 'border-indigo-600 text-indigo-700 bg-indigo-50/40' :
                                            done  ? 'border-emerald-400 text-emerald-700 cursor-pointer hover:bg-slate-50' :
                                                    'border-transparent text-slate-400'
                                        )}
                                    >
                                        <span className={cn(
                                            'w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
                                            active ? 'bg-indigo-600 text-white' :
                                            done  ? 'bg-emerald-500 text-white' :
                                                    'bg-slate-200 text-slate-500'
                                        )}>
                                            {done ? <Check className="w-3.5 h-3.5" /> : s.id}
                                        </span>
                                        <span className="hidden sm:block">{s.label}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-10">
                    <form onSubmit={handleSubmit}>

                        {/* ── STEP 1: Overview ── */}
                        {step === 1 && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <div className="lg:col-span-2 space-y-6">
                                    <div>
                                        <h2 className="text-xl font-bold text-slate-900 mb-1">What are you offering?</h2>
                                        <p className="text-sm text-slate-500">A great title attracts more buyers. Be specific about your expertise.</p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-sm font-semibold text-slate-700">
                                            Service Title <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            value={data.title}
                                            onChange={e => setData('title', e.target.value)}
                                            placeholder="e.g. I will design a professional logo for your brand"
                                            maxLength={80}
                                            className={cn('h-12 text-base', errors.title && 'border-red-400')}
                                        />
                                        <div className="flex justify-between text-xs text-slate-400">
                                            <span className={data.title.length < 10 ? 'text-amber-500' : 'text-emerald-600'}>
                                                {data.title.length < 10 ? `${10 - data.title.length} more characters needed` : '✓ Good title'}
                                            </span>
                                            <span>{data.title.length}/80</span>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-sm font-semibold text-slate-700">
                                            Category <span className="text-red-500">*</span>
                                        </Label>
                                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            {categories.map(cat => (
                                                <button
                                                    type="button"
                                                    key={cat.id}
                                                    onClick={() => setData('category_id', String(cat.id))}
                                                    className={cn(
                                                        'px-3 py-2.5 rounded-xl border text-sm font-medium text-left transition-all',
                                                        String(data.category_id) === String(cat.id)
                                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-400'
                                                            : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/30'
                                                    )}
                                                >
                                                    {cat.name}
                                                </button>
                                            ))}
                                        </div>
                                        {errors.category_id && <p className="text-xs text-red-500">{errors.category_id}</p>}
                                    </div>
                                </div>

                                {/* Tips sidebar */}
                                <div className="lg:col-span-1">
                                    <div className="bg-amber-50 border border-amber-100 rounded-2xl p-5 space-y-3">
                                        <h4 className="text-sm font-bold text-amber-800">💡 Tips for a great listing</h4>
                                        <ul className="space-y-2 text-xs text-amber-700 leading-relaxed">
                                            <li>✓ Start with "I will…" to describe your offer</li>
                                            <li>✓ Be specific — avoid vague titles like "I will do design"</li>
                                            <li>✓ Include your specialization (e.g. "minimalist", "3D", "Arabic")</li>
                                            <li>✓ Choose the most relevant category</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* ── STEP 2: Pricing ── */}
                        {step === 2 && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="text-xl font-bold text-slate-900 mb-1">Set your pricing</h2>
                                    <p className="text-sm text-slate-500">Create up to 3 packages (Basic, Standard, Premium) to offer different tiers.</p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {data.packages.map((pkg, idx) => (
                                        <div key={idx} className={cn(
                                            'bg-white rounded-2xl border shadow-sm overflow-hidden',
                                            idx === 0 ? 'border-slate-200' :
                                            idx === 1 ? 'border-indigo-200' : 'border-violet-200'
                                        )}>
                                            {/* Package header */}
                                            <div className={cn(
                                                'px-4 py-3 flex items-center justify-between',
                                                idx === 0 ? 'bg-slate-50' :
                                                idx === 1 ? 'bg-indigo-50' : 'bg-violet-50'
                                            )}>
                                                <span className={cn(
                                                    'text-xs font-bold uppercase tracking-wider',
                                                    idx === 0 ? 'text-slate-600' :
                                                    idx === 1 ? 'text-indigo-700' : 'text-violet-700'
                                                )}>
                                                    {PKG_LABELS[idx]}
                                                </span>
                                                {idx > 0 && (
                                                    <button type="button" onClick={() => removePackage(idx)} className="text-slate-400 hover:text-red-500 transition-colors">
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                )}
                                            </div>

                                            <div className="p-4 space-y-3">
                                                <div>
                                                    <Label className="text-xs font-semibold text-slate-600 mb-1 block">Package Name</Label>
                                                    <Input
                                                        value={pkg.name}
                                                        onChange={e => setPackageField(idx, 'name', e.target.value)}
                                                        placeholder={`${PKG_LABELS[idx]} Package`}
                                                        maxLength={80}
                                                        className="h-9 text-sm"
                                                    />
                                                </div>

                                                <div>
                                                    <Label className="text-xs font-semibold text-slate-600 mb-1 block">What's included</Label>
                                                    <textarea
                                                        value={pkg.description}
                                                        onChange={e => setPackageField(idx, 'description', e.target.value)}
                                                        placeholder="Briefly describe what's included..."
                                                        rows={3}
                                                        maxLength={500}
                                                        className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-ring"
                                                    />
                                                </div>

                                                <div className="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <Label className="text-xs font-semibold text-slate-600 mb-1 block">Price</Label>
                                                        <div className="relative">
                                                            <select
                                                                value={pkg.currency_code}
                                                                onChange={e => setPackageField(idx, 'currency_code', e.target.value)}
                                                                className="absolute left-2 top-1/2 -translate-y-1/2 text-[11px] bg-transparent font-semibold text-slate-500 border-0 outline-none"
                                                            >
                                                                {CURRENCIES.map(c => <option key={c}>{c}</option>)}
                                                            </select>
                                                            <Input
                                                                type="number"
                                                                min="1"
                                                                value={pkg.price}
                                                                onChange={e => setPackageField(idx, 'price', e.target.value)}
                                                                placeholder="0"
                                                                className="h-9 text-sm pl-14"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <Label className="text-xs font-semibold text-slate-600 mb-1 block">Delivery (days)</Label>
                                                        <Input
                                                            type="number"
                                                            min="1"
                                                            max="365"
                                                            value={pkg.delivery_days}
                                                            onChange={e => setPackageField(idx, 'delivery_days', Number(e.target.value))}
                                                            className="h-9 text-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}

                                    {data.packages.length < 3 && (
                                        <button
                                            type="button"
                                            onClick={addPackage}
                                            className="border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center gap-2 py-10 text-slate-400 hover:border-indigo-300 hover:text-indigo-500 transition-all"
                                        >
                                            <Plus className="w-6 h-6" />
                                            <span className="text-sm font-medium">Add {PKG_LABELS[data.packages.length]} Package</span>
                                        </button>
                                    )}
                                </div>

                                {(errors as any).packages && (
                                    <p className="text-sm text-red-500">{(errors as any).packages}</p>
                                )}
                            </div>
                        )}

                        {/* ── STEP 3: Description ── */}
                        {step === 3 && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <div className="lg:col-span-2 space-y-4">
                                    <div>
                                        <h2 className="text-xl font-bold text-slate-900 mb-1">Describe your service</h2>
                                        <p className="text-sm text-slate-500">Explain what you offer, your experience, and what makes you stand out. Minimum 100 characters.</p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-sm font-semibold text-slate-700">
                                            Service Description <span className="text-red-500">*</span>
                                        </Label>
                                        <textarea
                                            value={data.description}
                                            onChange={e => setData('description', e.target.value)}
                                            rows={14}
                                            placeholder={`Tell buyers about your service in detail.\n\nFor example:\n• What exactly will you deliver?\n• What's your process?\n• Why should they choose you?\n• What do you need from the buyer to get started?`}
                                            className={cn(
                                                'w-full rounded-xl border bg-white px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all leading-relaxed',
                                                errors.description ? 'border-red-400' : 'border-slate-200'
                                            )}
                                        />
                                        <div className="flex justify-between text-xs">
                                            <span className={data.description.length < 100 ? 'text-amber-500' : 'text-emerald-600'}>
                                                {data.description.length < 100
                                                    ? `${100 - data.description.length} more characters needed`
                                                    : '✓ Looking great!'}
                                            </span>
                                            <span className="text-slate-400">{data.description.length} characters</span>
                                        </div>
                                        {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 space-y-3">
                                        <h4 className="text-sm font-bold text-indigo-800">✍️ Description tips</h4>
                                        <ul className="space-y-1.5 text-xs text-indigo-700 leading-relaxed">
                                            <li>• Use bullet points for clarity</li>
                                            <li>• Mention your years of experience</li>
                                            <li>• List tools/software you use</li>
                                            <li>• Set clear expectations on revisions</li>
                                            <li>• Mention turnaround time</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* ── STEP 4: Review & Publish ── */}
                        {step === 4 && (
                            <div className="max-w-2xl mx-auto space-y-6">
                                <div className="text-center space-y-2">
                                    <div className="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <Send className="w-8 h-8 text-indigo-600" />
                                    </div>
                                    <h2 className="text-2xl font-bold text-slate-900">Ready to publish?</h2>
                                    <p className="text-sm text-slate-500">Review your service details below. Once submitted, it will be reviewed before going live.</p>
                                </div>

                                {/* Summary card */}
                                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                    <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                        <h3 className="font-bold text-slate-900">{data.title}</h3>
                                        <button type="button" onClick={() => setStep(1)} className="text-xs text-indigo-600 hover:underline">Edit</button>
                                    </div>
                                    <div className="px-6 py-4 border-b border-slate-100 space-y-1">
                                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Category</p>
                                        <p className="text-sm text-slate-700">{categories.find(c => String(c.id) === String(data.category_id))?.name || '—'}</p>
                                    </div>
                                    <div className="px-6 py-4 border-b border-slate-100">
                                        <div className="flex items-center justify-between mb-2">
                                            <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Packages ({data.packages.length})</p>
                                            <button type="button" onClick={() => setStep(2)} className="text-xs text-indigo-600 hover:underline">Edit</button>
                                        </div>
                                        <div className="space-y-2">
                                            {data.packages.map((pkg, i) => (
                                                <div key={i} className="flex items-center justify-between text-sm">
                                                    <span className="font-medium text-slate-700">{PKG_LABELS[i]}: {pkg.name || '(unnamed)'}</span>
                                                    <span className="font-bold text-slate-900">{pkg.currency_code} {pkg.price} · {pkg.delivery_days}d</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="px-6 py-4">
                                        <div className="flex items-center justify-between mb-2">
                                            <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Description preview</p>
                                            <button type="button" onClick={() => setStep(3)} className="text-xs text-indigo-600 hover:underline">Edit</button>
                                        </div>
                                        <p className="text-sm text-slate-600 line-clamp-3 leading-relaxed">{data.description}</p>
                                    </div>
                                </div>

                                {/* Info banner */}
                                <div className="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-xl p-4">
                                    <span className="text-lg">📋</span>
                                    <div className="text-xs text-amber-700 leading-relaxed">
                                        <strong className="block mb-0.5">Review process</strong>
                                        Your service will be reviewed by our team within 24 hours. You'll be notified once it goes live on the marketplace.
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    size="lg"
                                    disabled={processing}
                                    className="w-full h-12 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base gap-2"
                                >
                                    <Send className="w-5 h-5" />
                                    {processing ? 'Submitting…' : 'Submit for Review'}
                                </Button>
                            </div>
                        )}

                        {/* Navigation */}
                        {step < 4 && (
                            <div className="flex items-center justify-between mt-10 pt-6 border-t border-slate-200">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setStep(s => s - 1)}
                                    disabled={step === 1}
                                    className="gap-2"
                                >
                                    <ChevronLeft className="w-4 h-4" /> Back
                                </Button>

                                <div className="flex items-center gap-2">
                                    {STEPS.map(s => (
                                        <div key={s.id} className={cn(
                                            'w-2 h-2 rounded-full transition-all',
                                            step === s.id ? 'bg-indigo-600 w-6' :
                                            step > s.id  ? 'bg-emerald-400' : 'bg-slate-200'
                                        )} />
                                    ))}
                                </div>

                                <Button
                                    type="button"
                                    onClick={() => setStep(s => s + 1)}
                                    disabled={!canNext()}
                                    className="gap-2 bg-indigo-600 hover:bg-indigo-700 text-white"
                                >
                                    Continue <ChevronRight className="w-4 h-4" />
                                </Button>
                            </div>
                        )}
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
