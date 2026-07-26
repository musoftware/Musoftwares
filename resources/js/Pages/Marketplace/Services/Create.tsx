import React, { useState } from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import {
    ChevronRight, ChevronLeft, Check, Send, ArrowLeft, Star, Sparkles
} from 'lucide-react';
import OverviewStep from './Steps/OverviewStep';
import PricingStep from './Steps/PricingStep';
import DescriptionStep from './Steps/DescriptionStep';
import GalleryStep from './Steps/GalleryStep';
import PublishStep from './Steps/PublishStep';
import { __ } from '@/lib/i18n';

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
    const { auth } = usePage().props as any;
    const isAdmin = auth?.user && (auth.user.role === 'admin' || auth.user.roles?.includes('admin') || auth.user.is_admin);

    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        category_id: '',
        tags: [] as string[],
        description: '',
        faq: [] as {question: string, answer: string}[],
        requirements: [] as string[],
        gallery: [] as File[],
        kept_gallery: [] as string[],
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
        if (step < STEPS.length) {
            if (canNext()) {
                setStep(s => s + 1);
            }
            return;
        }
        post(route('marketplace.services.store'));
    };


    const lowestPrice = data.packages.reduce((min, p) => p.price && Number(p.price) < min ? Number(p.price) : min, Infinity);
    const displayPrice = lowestPrice === Infinity ? 0 : lowestPrice;
    const currencyMap: Record<number, string> = { 1: 'USD', 2: 'EGP', 3: 'EUR', 4: 'GBP', 5: 'AED', 6: 'SAR' };
    const currency = currencyMap[data.packages[0]?.currency_id];
    const selectedCategory = categories.find(c => String(c.id) === String(data.category_id));

    return (
        <MarketplaceLayout>
            <Head title={__('general.publish_a_service')} />

            <div className="min-h-screen bg-slate-50">
                {/* Top bar */}
                <div className="bg-white border-b border-slate-200 sticky top-0 z-30">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                        <Link href="/marketplace/dashboard" className="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-4 h-4" /> {__('general.back')}</Link>
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

                {/* Admin AI Banner */}
                {isAdmin && (
                    <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white border-b border-indigo-500/30 py-3.5 px-4 sm:px-6 shadow-inner">
                        <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <div className="p-2 bg-indigo-500/20 rounded-lg border border-indigo-400/30 shrink-0">
                                    <Sparkles className="w-5 h-5 text-amber-400 animate-pulse" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold flex items-center gap-2 text-white">
                                        إنشاء خدمة فورية بالذكاء الاصطناعي (أدمن فقط)
                                        <span className="text-[10px] bg-amber-400/20 text-amber-300 font-semibold px-2 py-0.5 rounded-full border border-amber-400/30">Admin Only</span>
                                    </h3>
                                    <p className="text-xs text-slate-300">
                                        اكتب عنوان الخدمة فقط والذكاء الاصطناعي (ChatGPT / Gemini) سينشئ التفاصيل، الباقات، والصورة تلقائياً!
                                    </p>
                                </div>
                            </div>
                            <Link
                                href="/marketplace/services/create-ai"
                                className="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg shadow-md hover:shadow-indigo-500/25 transition-all shrink-0 cursor-pointer"
                            >
                                <Sparkles className="w-4 h-4 text-amber-300" />
                                استخدام مولد AI
                            </Link>
                        </div>
                    </div>
                )}

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
                                <div className="flex items-center justify-end gap-4 pt-6 border-t border-slate-200">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setStep(s => s - 1)}
                                        disabled={step === 1}
                                        className="gap-2"
                                    >
                                        <ChevronLeft className="w-4 h-4" /> {__('general.back')}</Button>
                                    <Button
                                        type="button"
                                        onClick={() => setStep(s => s + 1)}
                                        disabled={!canNext()}
                                        className="gap-2 bg-indigo-600 hover:bg-indigo-700 text-white"
                                    >
                                        {__('general.continue')}<ChevronRight className="w-4 h-4" />
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
                                    ) : data.kept_gallery?.length > 0 ? (
                                        <img src={data.kept_gallery[0].startsWith('http') ? data.kept_gallery[0] : (data.kept_gallery[0].startsWith('/') ? data.kept_gallery[0] : `/uploads/${data.kept_gallery[0].replace(/^storage\//, '').replace(/^uploads\//, '')}`)} alt="Thumbnail" className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="text-slate-400 flex flex-col items-center">
                                            <span className="text-4xl mb-2">📸</span>
                                            <span className="text-xs font-medium">{__('general.image_preview')}</span>
                                        </div>
                                    )}

                                    {selectedCategory && (
                                        <div className="absolute top-3 start-3 bg-white/90 backdrop-blur text-slate-800 text-[10px] font-bold px-2 py-1 rounded-md shadow-sm">
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
                                        <div className="ms-auto flex items-center text-amber-500 text-[11px] font-bold">
                                            <Star className="w-3 h-3 fill-amber-500 me-1" /> 5.0 (0)
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
        </MarketplaceLayout>
    );
}

