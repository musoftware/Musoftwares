import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Calculator, FileText, Share2, Copy, Check, Sparkles, UserPlus, ArrowRight, ShieldCheck, DollarSign, Clock } from 'lucide-react';
import axios from 'axios';

export default function QuickCreate({ clients = [], currencies = [] }) {
    const { flash } = usePage().props;
    const [calculating, setCalculating] = useState(false);
    const [valuation, setValuation] = useState(null);
    const [copied, setCopied] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        description: '',
        client_id: '',
        currency_id: 1,
    });

    const handleCalculate = async (customCurrencyId = null) => {
        if (!data.description || data.description.trim().length < 5) {
            return;
        }
        setCalculating(true);
        const targetCurrency = customCurrencyId || data.currency_id;
        try {
            const res = await axios.post(route('admin.contracts.quick-calculate'), {
                description: data.description,
                currency_id: targetCurrency,
            });
            if (res.data.ok) {
                setValuation(res.data.valuation);
            }
        } catch (err) {
            console.error('Failed to calculate valuation:', err);
        } finally {
            setCalculating(false);
        }
    };

    const handleCurrencyChange = (newCurrencyId) => {
        setData('currency_id', newCurrencyId);
        if (valuation && data.description && data.description.trim().length >= 5) {
            handleCalculate(newCurrencyId);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.contracts.quick-store'));
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 3000);
    };

    return (
        <AdminSidebarLayout>
            <Head title="مولّد العقود والتسعير السريع" />

            <div className="space-y-8 max-w-6xl mx-auto py-6 px-4">
                {/* Header Title */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-5 border-slate-200">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <Sparkles className="w-5 h-5 text-amber-500" />
                            <span className="text-xs font-black uppercase tracking-widest text-amber-600">Quick Pricing Engine</span>
                        </div>
                        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">مولّد العقود والتسعير السريع</h1>
                        <p className="text-sm text-slate-500 mt-1">
                            أدخل وصف المشروع نصياً، وسيقوم المحرك بحساب التسعير التلقائي ثنائي المستوى وتوليد العقد ورابط المشاركة للعميل فوراً.
                        </p>
                    </div>
                </div>

                {/* Flash Shareable URL Notice Modal/Banner */}
                {flash?.shareable_url && (
                    <Card className="border-2 border-emerald-500 bg-emerald-50/50 shadow-md">
                        <CardHeader className="pb-3">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold">
                                    ✓
                                </div>
                                <div>
                                    <CardTitle className="text-lg text-emerald-900 font-extrabold">تم إنشاء العقد وتوليد الرابط القابل للمشاركة!</CardTitle>
                                    <CardDescription className="text-emerald-700 text-xs">
                                        مرجع العقد: <span className="font-mono font-bold text-emerald-950">{flash.contract_ref}</span>
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-2 bg-white p-3 rounded-lg border border-emerald-200">
                                <Input
                                    readOnly
                                    value={flash.shareable_url}
                                    className="font-mono text-xs text-slate-800 bg-slate-50 border-none focus-visible:ring-0"
                                />
                                <Button
                                    type="button"
                                    onClick={() => copyToClipboard(flash.shareable_url)}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white shrink-0 text-xs font-bold gap-2"
                                >
                                    {copied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                                    {copied ? 'تم النسخ!' : 'نسخ الرابط'}
                                </Button>
                            </div>

                            <div className="flex gap-3">
                                <Button
                                    type="button"
                                    onClick={() => window.open(`https://wa.me/?text=${encodeURIComponent('أهلاً بك! يمكنك معاينة العقد وتوقيعه مباشرة عبر هذا الرابط:\n' + flash.shareable_url)}`, '_blank')}
                                    className="bg-[#25D366] hover:bg-[#1da851] text-white font-bold text-xs gap-2 rounded-full px-6"
                                >
                                    <Share2 className="w-4 h-4" /> مشاركة عبر الواتساب
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Left Section — Input Description & Client Selection */}
                    <div className="lg:col-span-7 space-y-6">
                        <Card className="border border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-bold flex items-center gap-2">
                                    <FileText className="w-5 h-5 text-slate-700" />
                                    1. تفاصيل ووصف المشروع
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    اكتب متطلبات واحتياجات المشروع نصياً، وسيقوم المحرك بتوليد المكونات وتحديد الساعات.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-slate-700">نص وصف المشروع والخصائص المطلوبة *</Label>
                                    <Textarea
                                        rows={7}
                                        placeholder="مثال: موقع متجر إلكتروني لبيع المستلزمات الطبية أونلاين، يحتوي على سلة مشتريات، بوابة دفع سترايب، نظام فواتير، لوحة تحكم للطلبات، وإشعارات للعميل بالواتساب..."
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="text-sm font-sans leading-relaxed border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                    />
                                    {errors.description && <p className="text-xs text-red-500 font-bold">{errors.description}</p>}
                                </div>

                                <div className="flex justify-end">
                                    <Button
                                        type="button"
                                        onClick={() => handleCalculate()}
                                        disabled={calculating || !data.description || data.description.trim().length < 5}
                                        className="bg-amber-500 hover:bg-amber-600 text-black font-extrabold text-xs gap-2 rounded-full px-6"
                                    >
                                        <Calculator className="w-4 h-4" />
                                        {calculating ? 'جاري حساب التسعير بالذكاء الاصطناعي...' : 'حساب وتحليل التسعير الآن'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Client & Currency Selection */}
                        <Card className="border border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-bold flex items-center gap-2">
                                    <UserPlus className="w-5 h-5 text-slate-700" />
                                    2. ربط العميل والعملة
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    يمكنك تحديد العميل الآن، أو ترك العميل فارغاً ليقوم النظام بربط العقد تلقائياً بالعميل عند تسجيل دخوله.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-slate-700">اختيار العميل (اختياري)</Label>
                                    <select
                                        value={data.client_id}
                                        onChange={(e) => setData('client_id', e.target.value)}
                                        className="w-full h-10 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="">-- يربط تلقائياً عند تسجيل دخول العميل --</option>
                                        {clients.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} ({c.email})
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-slate-700">عملة العقد</Label>
                                    <select
                                        value={data.currency_id}
                                        onChange={(e) => handleCurrencyChange(e.target.value)}
                                        className="w-full h-10 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none font-bold text-slate-900"
                                    >
                                        {currencies.map((curr) => (
                                            <option key={curr.id} value={curr.id}>
                                                {curr.currency} ({curr.symbol})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Section — Live Valuation Output & Contract Actions */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="border-2 border-slate-900 bg-white shadow-xl sticky top-24">
                            <CardHeader className="bg-slate-900 text-white rounded-t-lg py-5">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-lg font-bold flex items-center gap-2">
                                        <ShieldCheck className="w-5 h-5 text-amber-400" />
                                        نتائج التسعير والتفكيك الفني
                                    </CardTitle>
                                    <span className="text-[10px] font-extrabold uppercase tracking-widest bg-amber-400 text-slate-950 px-2.5 py-1 rounded-full">
                                        Two-Level Engine
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-6">
                                {valuation ? (
                                    <>
                                        {/* Main Summary Numbers */}
                                        <div className="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-center">
                                            <div>
                                                <p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">إجمالي التكلفة</p>
                                                <p className="text-2xl font-black text-slate-900 mt-1">
                                                    {valuation.converted_amount} <span className="text-xs font-bold text-amber-600">{valuation.currency_symbol}</span>
                                                </p>
                                                <p className="text-xs font-bold text-slate-400 mt-0.5">
                                                    ~ ${valuation.recommended_usd} USD
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">الدفعة الأولى (50%)</p>
                                                <p className="text-2xl font-black text-emerald-600 mt-1">
                                                    {valuation.deposit_converted || (valuation.converted_amount * 0.5).toFixed(2)} <span className="text-xs font-bold text-emerald-700">{valuation.currency_symbol}</span>
                                                </p>
                                                <p className="text-[10px] font-bold text-emerald-700 mt-0.5">مطلوبة لتفعيل العقد</p>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between text-xs text-slate-600 border-b pb-3">
                                            <span className="flex items-center gap-1.5 font-bold"><Clock className="w-4 h-4 text-slate-400" /> مدة التنفيذ المتوقعة:</span>
                                            <span className="font-extrabold text-slate-900">{valuation.estimated_days} أيام عمل ({valuation.total_hours} ساعة)</span>
                                        </div>

                                        {/* AI Summary & Tech Stack Card */}
                                        {valuation.ai_summary && (
                                            <div className="bg-amber-50/70 border border-amber-200/80 rounded-xl p-4 space-y-3 text-xs">
                                                <div className="flex items-center gap-1.5 font-extrabold text-amber-900">
                                                    <Sparkles className="w-4 h-4 text-amber-600 shrink-0" />
                                                    <span>تحليل الذكاء الاصطناعي للمشروع (AI Scope Analysis):</span>
                                                </div>
                                                <p className="text-slate-700 leading-relaxed font-sans">
                                                    {valuation.ai_summary}
                                                </p>
                                                {valuation.tech_stack && (
                                                    <div className="pt-1 flex items-center gap-2 flex-wrap">
                                                        <span className="text-[10px] font-bold text-slate-500">البيئة البرمجية المقترحة:</span>
                                                        <span className="bg-white text-slate-800 border border-amber-300 font-mono text-[10px] px-2 py-0.5 rounded-md font-bold">
                                                            {valuation.tech_stack}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {/* Key Deliverables List */}
                                        {valuation.key_features && valuation.key_features.length > 0 && (
                                            <div className="space-y-2">
                                                <p className="text-xs font-black uppercase tracking-wider text-slate-500">المخرجات الرئيسية للمشروع (Deliverables):</p>
                                                <div className="grid grid-cols-1 gap-1.5">
                                                    {valuation.key_features.map((feat, fIdx) => (
                                                        <div key={fIdx} className="flex items-center gap-2 text-xs text-slate-800 bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100">
                                                            <span className="text-emerald-600 font-bold text-xs">✓</span>
                                                            <span className="font-bold">{feat}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {/* Itemized Micro-Components */}
                                        <div className="space-y-3">
                                            <p className="text-xs font-black uppercase tracking-wider text-slate-500">التفكيك الفني والمالي المفصّل (Micro-Components):</p>
                                            <div className="max-h-72 overflow-y-auto space-y-2.5 pr-1">
                                                {valuation.micro_components?.map((comp, idx) => (
                                                    <div key={idx} className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <div>
                                                                <div className="flex items-center gap-2 flex-wrap">
                                                                    <p className="font-extrabold text-slate-900 text-xs">{comp.name_ar}</p>
                                                                    {comp.name_en && (
                                                                        <span className="text-[10px] text-slate-400 font-mono">({comp.name_en})</span>
                                                                    )}
                                                                </div>
                                                                {comp.description_ar && (
                                                                    <p className="text-[11px] text-slate-600 mt-1 leading-relaxed">{comp.description_ar}</p>
                                                                )}
                                                            </div>
                                                            <div className="text-left shrink-0">
                                                                <p className="font-mono font-black text-slate-900 text-sm">
                                                                    {comp.converted_cost} <span className="text-[11px] font-bold text-amber-600">{comp.currency_symbol}</span>
                                                                </p>
                                                                <p className="text-[10px] font-bold text-slate-400 mt-0.5">
                                                                    ~ ${comp.cost_usd} USD
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center justify-between pt-1 border-t border-slate-200/60 text-[10px]">
                                                            <span className="text-slate-500 font-bold">⏱ الساعات المقدرة: <strong className="text-slate-800">{comp.estimated_hours} ساعة</strong></span>
                                                            {comp.complexity && (
                                                                <span className="bg-slate-200/70 text-slate-700 font-bold px-2 py-0.5 rounded uppercase">
                                                                    مستوى التعقيد: {comp.complexity}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Action Button */}
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold py-6 rounded-xl text-xs uppercase tracking-wider gap-2 shadow-lg"
                                        >
                                            <ArrowRight className="w-4 h-4" />
                                            {processing ? 'جاري إنشاء العقد وتوليد الرابط...' : 'إنشاء العقد وتوليد الرابط للعميل'}
                                        </Button>
                                    </>
                                ) : (
                                    <div className="text-center py-10 space-y-3">
                                        <Calculator className="w-12 h-12 text-slate-300 mx-auto" />
                                        <p className="text-sm font-bold text-slate-600">في انتظار أدخال وصف المشروع</p>
                                        <p className="text-xs text-slate-400 max-w-xs mx-auto">
                                            اكتب المتطلبات على اليسار واضغط على "حساب وتحليل التسعير الآن" لعرض النتائج فوراً.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
