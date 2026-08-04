import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { ArrowRight, Edit3, Copy, Check, Megaphone, DollarSign, ListChecks, Wrench, HeartHandshake, Info } from 'lucide-react';
import { toast } from 'sonner';

interface Playbook {
    id: number;
    title: string;
    marketing_message?: string;
    pricing_info?: string;
    client_requirements?: string;
    execution_workflow?: string;
    thank_you_message?: string;
    notes?: string;
    created_at: string;
    service?: {
        id: number;
        title: string;
        thumbnail?: string;
    };
    creator?: {
        id: number;
        name: string;
    };
}

interface ShowProps {
    playbook: Playbook;
}

export default function Show({ playbook }: ShowProps) {
    const [copiedField, setCopiedField] = useState<string | null>(null);

    const handleCopy = (text?: string, fieldName?: string) => {
        if (!text) {
            toast.error('لا يوجد نص لنسخه في هذا القسم');
            return;
        }
        navigator.clipboard.writeText(text);
        setCopiedField(fieldName || 'النص');
        toast.success(`تم نسخ (${fieldName}) إلى الحافظة بنجاح!`);
        setTimeout(() => setCopiedField(null), 2000);
    };

    return (
        <AdminSidebarLayout header={`دليل الخدمة: ${playbook.title}`}>
            <Head title={`${playbook.title} - Admin`} />

            <div className="max-w-5xl mx-auto space-y-6 pb-12">
                {/* Header Actions */}
                <div className="flex items-center justify-between gap-4">
                    <Link
                        href="/admin/marketplace/service-playbooks"
                        className="inline-flex items-center text-sm text-slate-600 hover:text-slate-900 font-medium gap-1.5"
                    >
                        <ArrowRight className="w-4 h-4" />
                        العودة للقائمة
                    </Link>
                    <Link href={`/admin/marketplace/service-playbooks/${playbook.id}/edit`}>
                        <Button className="bg-sky-600 hover:bg-sky-700 text-white gap-2 font-medium">
                            <Edit3 className="w-4 h-4" />
                            تعديل هذا الدليل
                        </Button>
                    </Link>
                </div>

                {/* Banner */}
                <Card className="border-slate-200 bg-slate-900 text-white shadow-sm">
                    <CardHeader className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="space-y-2">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <h1 className="text-xl font-bold text-white">{playbook.title}</h1>
                                    {playbook.service ? (
                                        <Badge className="bg-sky-500/20 text-sky-300 border-sky-400/30">
                                            🔗 {playbook.service.title}
                                        </Badge>
                                    ) : (
                                        <Badge className="bg-slate-800 text-slate-300 border-slate-700">
                                            خدمة مخصصة
                                        </Badge>
                                    )}
                                </div>
                                {playbook.notes && (
                                    <p className="text-xs text-slate-300 bg-slate-800/70 p-2.5 rounded-md border border-slate-700/50">
                                        💡 <span className="font-semibold">ملاحظة داخلية:</span> {playbook.notes}
                                    </p>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Section 1: Marketing Message */}
                <Card className="border-slate-200 shadow-sm overflow-hidden" data-color-mode="light">
                    <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4">
                        <div className="flex items-center gap-2">
                            <Megaphone className="w-5 h-5 text-amber-500" />
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">1. الرسالة التسويقية (Marketing Message)</CardTitle>
                                <CardDescription className="text-xs text-slate-500">جاهزة لإرسالها للعميل المهتم بالخدمة.</CardDescription>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleCopy(playbook.marketing_message, 'الرسالة التسويقية')}
                            className="text-xs gap-1.5 bg-white text-slate-800 border-slate-300 hover:bg-slate-100 shadow-xs"
                        >
                            {copiedField === 'الرسالة التسويقية' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                            {copiedField === 'الرسالة التسويقية' ? 'تم النسخ!' : 'نسخ الرسالة التسويقية'}
                        </Button>
                    </CardHeader>
                    <CardContent className="p-6 bg-white">
                        {playbook.marketing_message ? (
                            <MDEditor.Markdown source={playbook.marketing_message} className="prose max-w-none text-slate-800 text-sm" />
                        ) : (
                            <p className="text-xs text-slate-400 italic">لم يتم إضافة رسالة تسويقية بعد.</p>
                        )}
                    </CardContent>
                </Card>

                {/* Section 2: Pricing Info */}
                <Card className="border-slate-200 shadow-sm overflow-hidden" data-color-mode="light">
                    <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4">
                        <div className="flex items-center gap-2">
                            <DollarSign className="w-5 h-5 text-emerald-500" />
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">2. الأسعار والباقات (Pricing & Packages)</CardTitle>
                                <CardDescription className="text-xs text-slate-500">العروض والأسعار المتاحة للخدمة.</CardDescription>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleCopy(playbook.pricing_info, 'تفاصيل الأسعار')}
                            className="text-xs gap-1.5 bg-white text-slate-800 border-slate-300 hover:bg-slate-100 shadow-xs"
                        >
                            {copiedField === 'تفاصيل الأسعار' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                            {copiedField === 'تفاصيل الأسعار' ? 'تم النسخ!' : 'نسخ الأسعار'}
                        </Button>
                    </CardHeader>
                    <CardContent className="p-6 bg-white">
                        {playbook.pricing_info ? (
                            <MDEditor.Markdown source={playbook.pricing_info} className="prose max-w-none text-slate-800 text-sm" />
                        ) : (
                            <p className="text-xs text-slate-400 italic">لم يتم إدخال بيانات الأسعار بعد.</p>
                        )}
                    </CardContent>
                </Card>

                {/* Section 3: Client Requirements */}
                <Card className="border-slate-200 shadow-sm overflow-hidden" data-color-mode="light">
                    <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4">
                        <div className="flex items-center gap-2">
                            <ListChecks className="w-5 h-5 text-indigo-500" />
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">3. المطلوب من العميل (Client Requirements)</CardTitle>
                                <CardDescription className="text-xs text-slate-500">بيانات وملفات يطلب من العميل تزويدنا بها.</CardDescription>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleCopy(playbook.client_requirements, 'المطلوب من العميل')}
                            className="text-xs gap-1.5 bg-white text-slate-800 border-slate-300 hover:bg-slate-100 shadow-xs"
                        >
                            {copiedField === 'المطلوب من العميل' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                            {copiedField === 'المطلوب من العميل' ? 'تم النسخ!' : 'نسخ المطلوب من العميل'}
                        </Button>
                    </CardHeader>
                    <CardContent className="p-6 bg-white">
                        {playbook.client_requirements ? (
                            <MDEditor.Markdown source={playbook.client_requirements} className="prose max-w-none text-slate-800 text-sm" />
                        ) : (
                            <p className="text-xs text-slate-400 italic">لم يتم تحديد المطلوبات من العميل بعد.</p>
                        )}
                    </CardContent>
                </Card>

                {/* Section 4: Internal Execution Workflow */}
                <Card className="border-slate-200 shadow-sm overflow-hidden border-r-4 border-r-purple-500" data-color-mode="light">
                    <CardHeader className="border-b border-slate-100 bg-purple-50/30 flex flex-row items-center justify-between p-4">
                        <div className="flex items-center gap-2">
                            <Wrench className="w-5 h-5 text-purple-600" />
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">4. خطوات العمل الكيفية والتنفيذية (Internal Execution SOP)</CardTitle>
                                <CardDescription className="text-xs text-purple-700 font-medium">🔒 مرجع داخلي خاص بك وبفريق العمل لتطابق خطوات التنفيذ.</CardDescription>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleCopy(playbook.execution_workflow, 'خطوات العمل التنفيذية')}
                            className="text-xs gap-1.5 bg-white text-slate-800 border-slate-300 hover:bg-slate-100 shadow-xs"
                        >
                            {copiedField === 'خطوات العمل التنفيذية' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                            {copiedField === 'خطوات العمل التنفيذية' ? 'تم النسخ!' : 'نسخ خطوات العمل'}
                        </Button>
                    </CardHeader>
                    <CardContent className="p-6 bg-white">
                        {playbook.execution_workflow ? (
                            <MDEditor.Markdown source={playbook.execution_workflow} className="prose max-w-none text-slate-800 text-sm" />
                        ) : (
                            <p className="text-xs text-slate-400 italic">لم يتم إدخال خطوات العمل التنفيذية بعد.</p>
                        )}
                    </CardContent>
                </Card>

                {/* Section 5: Thank You Message */}
                <Card className="border-slate-200 shadow-sm overflow-hidden" data-color-mode="light">
                    <CardHeader className="border-b border-slate-100 bg-slate-50/50 flex flex-row items-center justify-between p-4">
                        <div className="flex items-center gap-2">
                            <HeartHandshake className="w-5 h-5 text-rose-500" />
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">5. رسالة الشكر والتسليم (Thank You & Delivery Message)</CardTitle>
                                <CardDescription className="text-xs text-slate-500">جاهزة لإرسالها للعميل عند التسليم النهائي للمشروع.</CardDescription>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleCopy(playbook.thank_you_message, 'رسالة الشكر والتسليم')}
                            className="text-xs gap-1.5 bg-white text-slate-800 border-slate-300 hover:bg-slate-100 shadow-xs"
                        >
                            {copiedField === 'رسالة الشكر والتسليم' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                            {copiedField === 'رسالة الشكر والتسليم' ? 'تم النسخ!' : 'نسخ رسالة الشكر'}
                        </Button>
                    </CardHeader>
                    <CardContent className="p-6 bg-white">
                        {playbook.thank_you_message ? (
                            <MDEditor.Markdown source={playbook.thank_you_message} className="prose max-w-none text-slate-800 text-sm" />
                        ) : (
                            <p className="text-xs text-slate-400 italic">لم يتم إدخال رسالة الشكر والتسليم بعد.</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
