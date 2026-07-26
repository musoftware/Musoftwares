import React from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import {
    Sparkles, ArrowLeft, Bot, Zap, Image as ImageIcon, Layers, CheckCircle2, Loader2
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Category { id: number; name: string; slug: string; }
interface Props { categories: Category[]; }

export default function CreateAiService({ categories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        provider: 'chatgpt',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.title.trim() || processing) return;
        post(route('marketplace.services.store_ai'));
    };

    return (
        <MarketplaceLayout>
            <Head title="توليد خدمة بالذكاء الاصطناعي | AI Service Generator" />

            <div className="min-h-screen bg-slate-900 text-slate-100 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                {/* Background decorative glow */}
                <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-indigo-600/20 to-purple-600/20 rounded-full blur-3xl pointer-events-none" />

                <div className="max-w-3xl mx-auto relative z-10">
                    {/* Navigation back */}
                    <div className="mb-8 flex items-center justify-between">
                        <Link
                            href="/marketplace/services/create"
                            className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            العودة إلى صفحة الإنشاء العادية
                        </Link>
                        <span className="text-xs bg-amber-400/20 text-amber-300 border border-amber-400/30 px-3 py-1 rounded-full font-semibold">
                            Admin Only Feature
                        </span>
                    </div>

                    {/* Main Header */}
                    <div className="text-center mb-10">
                        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-4">
                            <Sparkles className="w-4 h-4 text-amber-400 animate-pulse" />
                            مولّد الخدمات التلقائي بالذكاء الاصطناعي
                        </div>
                        <h1 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            أنشئ خدمة متكاملة بضغطة زر واحدة ⚡
                        </h1>
                        <p className="mt-3 text-slate-400 text-sm sm:text-base max-w-xl mx-auto">
                            أدخل عنوان الخدمة فقط وسيقوم الذكاء الاصطناعي بتوليد العنوان الدقيق، الوصف الشامل، الباقات الثلاث، الكلمات المفتاحية وصورة الغلاف فوراً.
                        </p>
                    </div>

                    {/* Creation Form Card */}
                    <div className="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-2xl">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            
                            {/* Service Title Input */}
                            <div>
                                <label className="block text-sm font-semibold text-slate-200 mb-2">
                                    عنوان الخدمة أو فكرتها الأساسية <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    placeholder="مثال: تصميم وتطوير متجر إلكتروني متكامل بشرائح الدفع"
                                    disabled={processing}
                                    className="w-full px-4 py-3.5 bg-slate-900/90 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                                    required
                                />
                                {errors.title && (
                                    <p className="mt-1.5 text-xs text-rose-400">{errors.title}</p>
                                )}
                            </div>

                            {/* Provider Choice */}
                            <div>
                                <label className="block text-sm font-semibold text-slate-200 mb-3">
                                    اختر محرك الذكاء الاصطناعي (AI Provider)
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    
                                    {/* ChatGPT */}
                                    <label
                                        onClick={() => setData('provider', 'chatgpt')}
                                        className={`flex items-start gap-3.5 p-4 rounded-xl border cursor-pointer transition-all ${
                                            data.provider === 'chatgpt'
                                                ? 'bg-indigo-950/50 border-indigo-500 ring-2 ring-indigo-500/20'
                                                : 'bg-slate-900/50 border-slate-700/60 hover:border-slate-600'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="provider"
                                            value="chatgpt"
                                            checked={data.provider === 'chatgpt'}
                                            onChange={() => setData('provider', 'chatgpt')}
                                            className="mt-1 text-indigo-600 focus:ring-indigo-500 bg-slate-800 border-slate-600"
                                        />
                                        <div>
                                            <div className="flex items-center gap-2 font-bold text-white text-sm">
                                                <Bot className="w-4 h-4 text-emerald-400" />
                                                ChatGPT (OpenAI)
                                            </div>
                                            <p className="text-xs text-slate-400 mt-1">
                                                يستخدم GPT-4o لتوليد تفاصيل الخدمة و DALL-E لإنشاء صورة الغلاف.
                                            </p>
                                        </div>
                                    </label>

                                    {/* Gemini */}
                                    <label
                                        onClick={() => setData('provider', 'gemini')}
                                        className={`flex items-start gap-3.5 p-4 rounded-xl border cursor-pointer transition-all ${
                                            data.provider === 'gemini'
                                                ? 'bg-indigo-950/50 border-indigo-500 ring-2 ring-indigo-500/20'
                                                : 'bg-slate-900/50 border-slate-700/60 hover:border-slate-600'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="provider"
                                            value="gemini"
                                            checked={data.provider === 'gemini'}
                                            onChange={() => setData('provider', 'gemini')}
                                            className="mt-1 text-indigo-600 focus:ring-indigo-500 bg-slate-800 border-slate-600"
                                        />
                                        <div>
                                            <div className="flex items-center gap-2 font-bold text-white text-sm">
                                                <Zap className="w-4 h-4 text-blue-400" />
                                                Google Gemini
                                            </div>
                                            <p className="text-xs text-slate-400 mt-1">
                                                يستخدم Gemini 2.0 Flash السريع للتوليد الفائق مع DALL-E للصورة.
                                            </p>
                                        </div>
                                    </label>

                                </div>
                            </div>

                            {/* Features list bullet points */}
                            <div className="bg-slate-900/60 rounded-xl p-4 border border-slate-700/40 space-y-2 text-xs text-slate-300">
                                <div className="flex items-center gap-2 text-slate-200 font-semibold mb-1">
                                    <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                                    ما الذي سيتم إنشاؤه تلقائياً؟
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-slate-400">
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                                        عنوان جذاب وتسويقي احترافي
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                                        وصف كامل +150 كلمة بالتفاصيل
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                                        تحديد القسم الأنسب تلقائياً
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                                        3 باقات (Basic, Standard, Premium)
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                                        تأليف الأسئلة الشائعة والطلبات
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                                        صورة غلاف حصرية بواسطة AI
                                    </div>
                                </div>
                            </div>

                            {/* Submit Button */}
                            <Button
                                type="submit"
                                disabled={processing || !data.title.trim()}
                                className="w-full py-4 bg-gradient-to-r from-indigo-500 via-purple-600 to-pink-600 hover:from-indigo-600 hover:to-pink-700 text-white font-bold text-base rounded-xl shadow-lg hover:shadow-indigo-500/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="w-5 h-5 animate-spin" />
                                        جاري توليد التفاصيل والصورة بنجاح...
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="w-5 h-5 text-amber-300" />
                                        توليد ونشر الخدمة فكلياً
                                    </>
                                )}
                            </Button>

                        </form>
                    </div>
                </div>
            </div>
        </MarketplaceLayout>
    );
}
