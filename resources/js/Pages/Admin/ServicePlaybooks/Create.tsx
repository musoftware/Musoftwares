import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import MDEditor from '@uiw/react-md-editor';
import { ArrowRight, Save, Sparkles, Copy, Check, Info, FileText, Megaphone, DollarSign, ListChecks, Wrench, HeartHandshake } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface Service {
    id: number;
    title: string;
    tagline?: string;
    thumbnail?: string;
    packages?: any[];
    extras?: any[];
}

interface CreateProps {
    services: Service[];
}

export default function Create({ services }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        service_id: '',
        marketing_message: '',
        pricing_info: '',
        client_requirements: '',
        execution_workflow: '',
        thank_you_message: '',
        notes: '',
    });

    const [isLoadingPricing, setIsLoadingPricing] = useState(false);
    const [copiedField, setCopiedField] = useState<string | null>(null);

    const handleServiceChange = async (serviceId: string) => {
        setData('service_id', serviceId);

        if (!serviceId) return;

        // Auto-fill title if empty
        const selected = services.find(s => s.id.toString() === serviceId);
        if (selected && !data.title) {
            setData('title', `دليل تنفيذ خدمة: ${selected.title}`);
        }

        // Fetch pricing template
        try {
            setIsLoadingPricing(true);
            const res = await axios.get(`/admin/marketplace/service-playbooks/service-details/${serviceId}`);
            if (res.data && res.data.pricing_template) {
                setData(prev => ({
                    ...prev,
                    service_id: serviceId,
                    title: prev.title || `دليل تنفيذ خدمة: ${selected?.title || ''}`,
                    pricing_info: res.data.pricing_template
                }));
                toast.success('تم توليد قالب الأسعار تلقائياً من بيانات الخدمة!');
            }
        } catch (e) {
            console.error(e);
        } finally {
            setIsLoadingPricing(false);
        }
    };

    const handleCopy = (text: string, fieldName: string) => {
        if (!text) {
            toast.error('لا يوجد نص لنسخه في هذا القسم');
            return;
        }
        navigator.clipboard.writeText(text);
        setCopiedField(fieldName);
        toast.success(`تم نسخ نص (${fieldName}) إلى المحافظة!`);
        setTimeout(() => setCopiedField(null), 2000);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/marketplace/service-playbooks', {
            onSuccess: () => toast.success('تم إنشاء دليل الخدمة بنجاح'),
        });
    };

    return (
        <AdminSidebarLayout header="إضافة دليل خدمة جديد (Service Playbook)">
            <Head title="إضافة دليل خدمة - Admin" />

            <div className="max-w-5xl mx-auto space-y-6 pb-12">
                {/* Navigation Back */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/admin/marketplace/service-playbooks"
                        className="inline-flex items-center text-sm text-slate-600 hover:text-slate-900 font-medium gap-1.5"
                    >
                        <ArrowRight className="w-4 h-4" />
                        العودة لقائمة أدلة الخدمات
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Card 1: Main Info */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <Info className="w-5 h-5 text-sky-500" />
                                البيانات الأساسية وربط الخدمة
                            </CardTitle>
                            <CardDescription className="text-xs text-slate-500">
                                اختر الخدمة المربوطة من خدمات الماركت بليس وحدد اسم الدليل الداخلي.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-6 space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-slate-700">عنوان دليل الخدمة <span className="text-red-500">*</span></Label>
                                    <Input
                                        value={data.title}
                                        onChange={e => setData('title', e.target.value)}
                                        placeholder="مثال: دليل تقديم وإنشاء تطبيق متجر إلكتروني"
                                        required
                                        className="bg-white"
                                    />
                                    {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-sm font-medium text-slate-700">ربط بخدمة من الماركت بليس (اختياري)</Label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                        value={data.service_id}
                                        onChange={e => handleServiceChange(e.target.value)}
                                    >
                                        <option value="">-- خدمة مخصصة غير مربوطة بالماركت بليس --</option>
                                        {services.map(s => (
                                            <option key={s.id} value={s.id}>{s.title}</option>
                                        ))}
                                    </select>
                                    {errors.service_id && <p className="text-xs text-red-500">{errors.service_id}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label className="text-sm font-medium text-slate-700">ملاحظات داخلية سريعة</Label>
                                <Textarea
                                    value={data.notes}
                                    onChange={e => setData('notes', e.target.value)}
                                    placeholder="أي ملاحظات إضافية للفريق أو للتذكر..."
                                    rows={2}
                                    className="bg-white"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 2: Marketing Message */}
                    <Card className="border-slate-200 shadow-sm" data-color-mode="light">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <Megaphone className="w-5 h-5 text-amber-500" />
                                    1. الرسالة التسويقية (Marketing Message)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500">
                                    النص التسويقي الترويجي للخدمة والمميزات لنسخها وإرسالها للعميل المحتمل.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.marketing_message, 'الرسالة التسويقية')}
                                className="text-xs gap-1.5 border-slate-200 text-slate-700 hover:bg-slate-100"
                            >
                                {copiedField === 'الرسالة التسويقية' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'الرسالة التسويقية' ? 'تم النسخ!' : 'نسخ النص'}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6">
                            <MDEditor
                                value={data.marketing_message}
                                onChange={val => setData('marketing_message', val || '')}
                                height={220}
                            />
                        </CardContent>
                    </Card>

                    {/* Card 3: Pricing Info */}
                    <Card className="border-slate-200 shadow-sm" data-color-mode="light">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <DollarSign className="w-5 h-5 text-emerald-500" />
                                    2. الأسعار والباقات (Pricing & Offers)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500">
                                    يتم سحب وتوليد هذا الجزء تلقائياً عند اختيار الخدمة، ويمكنك التعديل عليه بمرونة.
                                </CardDescription>
                            </div>
                            <div className="flex items-center gap-2">
                                {data.service_id && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={isLoadingPricing}
                                        onClick={() => handleServiceChange(data.service_id)}
                                        className="text-xs gap-1 text-sky-700 border-sky-200 bg-sky-50 hover:bg-sky-100"
                                    >
                                        <Sparkles className="w-3.5 h-3.5" />
                                        توليد الأسعار تلقائياً
                                    </Button>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCopy(data.pricing_info, 'تفاصيل الأسعار')}
                                    className="text-xs gap-1.5 border-slate-200 text-slate-700 hover:bg-slate-100"
                                >
                                    {copiedField === 'تفاصيل الأسعار' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                    {copiedField === 'تفاصيل الأسعار' ? 'تم النسخ!' : 'نسخ النص'}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-6">
                            <MDEditor
                                value={data.pricing_info}
                                onChange={val => setData('pricing_info', val || '')}
                                height={260}
                            />
                        </CardContent>
                    </Card>

                    {/* Card 4: Client Requirements */}
                    <Card className="border-slate-200 shadow-sm" data-color-mode="light">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <ListChecks className="w-5 h-5 text-indigo-500" />
                                    3. المطلوب من العميل (Client Requirements)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500">
                                    قائمة الملفات والمعلومات المطلوبة من العميل للبدء في التنفيذ.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.client_requirements, 'المطلوب من العميل')}
                                className="text-xs gap-1.5 border-slate-200 text-slate-700 hover:bg-slate-100"
                            >
                                {copiedField === 'المطلوب من العميل' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'المطلوب من العميل' ? 'تم النسخ!' : 'نسخ النص'}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6">
                            <MDEditor
                                value={data.client_requirements}
                                onChange={val => setData('client_requirements', val || '')}
                                height={220}
                            />
                        </CardContent>
                    </Card>

                    {/* Card 5: Internal Execution Workflow */}
                    <Card className="border-slate-200 shadow-sm" data-color-mode="light">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <Wrench className="w-5 h-5 text-purple-500" />
                                    4. خطوات العمل الكيفية والتنفيذية (Internal Execution SOP)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500">
                                    دليل خطوة بخطوة لما ستقوم بتنفيذه بنفسك أو الفريق الفني خطوة بخطوة.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.execution_workflow, 'خطوات العمل التنفيذية')}
                                className="text-xs gap-1.5 border-slate-200 text-slate-700 hover:bg-slate-100"
                            >
                                {copiedField === 'خطوات العمل التنفيذية' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'خطوات العمل التنفيذية' ? 'تم النسخ!' : 'نسخ النص'}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6">
                            <MDEditor
                                value={data.execution_workflow}
                                onChange={val => setData('execution_workflow', val || '')}
                                height={260}
                            />
                        </CardContent>
                    </Card>

                    {/* Card 6: Thank You Message */}
                    <Card className="border-slate-200 shadow-sm" data-color-mode="light">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <HeartHandshake className="w-5 h-5 text-rose-500" />
                                    5. رسالة الشكر والتسليم (Thank You & Delivery Message)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500">
                                    الرسالة النهائية بعد تسليم العمل وإتمام المشروع.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleCopy(data.thank_you_message, 'رسالة الشكر والتسليم')}
                                className="text-xs gap-1.5 border-slate-200 text-slate-700 hover:bg-slate-100"
                            >
                                {copiedField === 'رسالة الشكر والتسليم' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                {copiedField === 'رسالة الشكر والتسليم' ? 'تم النسخ!' : 'نسخ النص'}
                            </Button>
                        </CardHeader>
                        <CardContent className="p-6">
                            <MDEditor
                                value={data.thank_you_message}
                                onChange={val => setData('thank_you_message', val || '')}
                                height={220}
                            />
                        </CardContent>
                    </Card>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                        <Link href="/admin/marketplace/service-playbooks">
                            <Button variant="outline" type="button">إلغاء</Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="bg-sky-600 hover:bg-sky-700 text-white gap-2 font-medium px-6">
                            <Save className="w-4 h-4" />
                            حفظ دليل الخدمة
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
