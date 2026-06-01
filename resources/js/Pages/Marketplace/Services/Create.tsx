import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import {
    ChevronRight, ChevronLeft, Check, Send, ArrowLeft, Star
} from 'lucide-react';
import OverviewStep from './Steps/OverviewStep';
import PricingStep from './Steps/PricingStep';
import DescriptionStep from './Steps/DescriptionStep';
import GalleryStep from './Steps/GalleryStep';
import PublishStep from './Steps/PublishStep';

interface Category { id: number; name: string; slug: string; }
interface Props { categories: Category[]; seller: any; }

const STEPS = [
    { id: 1, label: 'Overview' },
    { id: 2, label: 'Pricing' },
    { id: 3, label: 'Description & FAQ' },
    { id: 4, label: 'Gallery' },
    { id: 5, label: 'Publish' },
];

export const emptyPackage = () => ({
    name: '', description: '', price: '', currency_id: 1, delivery_days: 3, revisions: 2, features: []
});

export default function CreateService({ categories, seller }: Props) {
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        category_id: '',
        tags: [] as string[],
        description: '',
        faq: [] as {question: string, answer: string}[],
        requirements: [] as string[],
        gallery: [] as File[],
        video_url: '',
        packages: [emptyPackage()],
    });

    const canNext = () => {
        if (step === 1) return data.title.trim().length >= 10 && data.category_id;
        if (step === 2) return data.packages.every(p => p.name && p.price && Number(p.price) >= 1 && p.delivery_days >= 1);
        if (step === 3) return data.description.trim().length >= 100;
        if (step === 4) return data.gallery.length > 0;
        return true;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('marketplace.services.store'));
    };

    const lowestPrice = data.packages.reduce((min, p) => p.price && Number(p.price) < min ? Number(p.price) : min, Infinity);
    const displayPrice = lowestPrice === Infinity ? 0 : lowestPrice;
    const currencyMap: Record<number, string> = { 1: 'USD', 2: 'EGP', 3: 'EUR', 4: 'GBP', 5: 'AED', 6: 'SAR' };
    const currency = currencyMap[data.packages[0]?.currency_id] || 'USD';
    const selectedCategory = categories.find(c => String(c.id) === String(data.category_id));

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title={__('general.publish_a_service')} />

            <div className="min-h-screen bg-slate-50">
                {/* Top bar */}
                <div className="bg-white border-b border-slate-200 sticky top-0 z-30">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                        <Link href="/marketplace/dashboard" className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-4 h-4" /> Back
                        </Link>
                        <h1 className="text-base font-semibold text-slate-900">{__('general.publish_a_service')}</h1>
                        <div className="text-xs text-slate-400">Step {step} of {STEPS.length}</div>
                    </div>

                    {/* Step tabs */}
                    <div className="max-w-7xl mx-auto px-4 sm:px-6">
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
                                            'flex-1 flex flex-col sm:flex-row items-center justify-center gap-2 px-2 py-3 text-xs font-semibold border-b-2 transition-all',
                                            active ? 'border-indigo-600 text-indigo-700 bg-indigo-50/40' :
                                            done  ? 'border-emerald-400 text-emerald-700 cursor-pointer hover:bg-slate-50' :
                                                    'border-transparent text-slate-400'
                                        )}
                                    >
                                        <span className={cn(
                                            'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
                                            active ? 'bg-indigo-600 text-white' :
                                            done  ? 'bg-emerald-500 text-white' :
                                                    'bg-slate-200 text-slate-500'
                                        )}>
                                            {done ? <Check className="w-3 h-3" /> : s.id}
                                        </span>
                                        <span className="hidden sm:block">{s.label}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10">
                    <form onSubmit={handleSubmit} className="flex flex-col lg:flex-row gap-8 items-start">
                        
                        {/* Main Form Area */}
                        <div className="flex-1 w-full space-y-8">
                            {step === 1 && <OverviewStep data={data} setData={setData} errors={errors} categories={categories} />}
                            {step === 2 && <PricingStep data={data} setData={setData} errors={errors} />}
                            {step === 3 && <DescriptionStep data={data} setData={setData} errors={errors} />}
                            {step === 4 && <GalleryStep data={data} setData={setData} errors={errors} />}
                            {step === 5 && <PublishStep data={data} setStep={setStep} processing={processing} />}

                            {/* Navigation */}
                            {step < 5 && (
                                <div className="flex items-center justify-between pt-6 border-t border-slate-200">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setStep(s => s - 1)}
                                        disabled={step === 1}
                                        className="gap-2"
                                    >
                                        <ChevronLeft className="w-4 h-4" /> Back
                                    </Button>
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
                        </div>

                        {/* Live Preview Panel (Sticky on right) */}
                        <div className="hidden lg:block w-[350px] sticky top-32 shrink-0">
                            <h3 className="text-sm font-bold text-slate-400 uppercase tracking-wider mb-3">{__('general.live_preview')}</h3>
                            <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                {/* Thumbnail */}
                                <div className="aspect-[4/3] bg-slate-100 relative overflow-hidden flex items-center justify-center">
                                    {data.gallery.length > 0 ? (
                                        <img src={URL.createObjectURL(data.gallery[0])} alt="Thumbnail" className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="text-slate-400 flex flex-col items-center">
                                            <span className="text-4xl mb-2">📸</span>
                                            <span className="text-xs font-medium">{__('general.image_preview')}</span>
                                        </div>
                                    )}
                                    {selectedCategory && (
                                        <div className="absolute top-3 left-3 bg-white/90 backdrop-blur text-slate-800 text-[10px] font-bold px-2 py-1 rounded-md shadow-sm">
                                            {selectedCategory.name}
                                        </div>
                                    )}
                                </div>
                                
                                <div className="p-4">
                                    {/* Seller Info */}
                                    <div className="flex items-center gap-2 mb-3">
                                        <div className="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center overflow-hidden shrink-0">
                                            {seller?.avatar ? <img src={seller.avatar} alt={seller.name} className="w-full h-full object-cover" /> : <span className="text-[10px] font-bold text-indigo-600">{seller?.name?.charAt(0)}</span>}
                                        </div>
                                        <span className="text-xs font-medium text-slate-700 truncate">{seller?.name}</span>
                                        <div className="ml-auto flex items-center text-amber-500 text-[11px] font-bold">
                                            <Star className="w-3 h-3 fill-amber-500 mr-1" /> 5.0 (0)
                                        </div>
                                    </div>

                                    {/* Title */}
                                    <h4 className="text-sm font-semibold text-slate-900 line-clamp-2 leading-snug mb-4 min-h-[40px]">
                                        {data.title || 'I will...'}
                                    </h4>

                                    {/* Footer */}
                                    <div className="flex items-center justify-between pt-3 border-t border-slate-100">
                                        <div className="text-xs text-slate-500 font-medium">{__('general.starting_at_1')}</div>
                                        <div className="text-lg font-bold text-slate-900">{currency} {displayPrice}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Tags Preview */}
                            {data.tags.length > 0 && (
                                <div className="mt-4">
                                    <div className="flex flex-wrap gap-1.5">
                                        {data.tags.map((tag: string, i: number) => (
                                            <span key={i} className="px-2 py-1 bg-slate-200/50 text-slate-600 text-[10px] font-bold uppercase rounded-md">
                                                {tag}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
