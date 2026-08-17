import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { 
    ArrowRight, Lock, ShieldCheck, CheckCircle2, 
    CreditCard, Sparkles, User, Mail, Phone, Building2, FileText
} from 'lucide-react';
import { toast } from 'sonner';

interface Quotation {
    id: number;
    uuid: string;
    quotation_number: string;
    title: string;
    currency: string;
    deposit_percentage: number;
    development_total: number;
    deposit_amount: number;
    remaining_amount: number;
}

interface QuotationCheckoutProps {
    quotation: Quotation;
    payUrl: string;
    backUrl: string;
}

export default function QuotationCheckout({ quotation, payUrl, backUrl }: QuotationCheckoutProps) {
    const { data, setData, post, processing, errors } = useForm({
        client_name: '',
        client_email: '',
        client_phone: '',
        client_whatsapp: '',
        company_name: '',
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.client_name.trim() || !data.client_email.trim()) {
            toast.error('يرجى إدخال الاسم والبريد الإلكتروني بشكل صحيح.');
            return;
        }

        post(payUrl, {
            onError: (errs) => {
                console.error(errs);
                toast.error('يرجى مراجعة البيانات والتأكد من صحتها.');
            },
        });
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white pb-24">
            <Head title={`استكمال البيانات وسداد الدفعة - ${quotation.title}`} />

            {/* Header */}
            <header className="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="w-8 h-8 fill-current text-slate-900" />
                        <div>
                            <span className="font-extrabold text-base tracking-tight text-slate-900 block leading-none">
                                MUSOFTWARE
                            </span>
                            <span className="text-[10px] text-slate-400 font-medium tracking-wider uppercase">
                                Secure Checkout
                            </span>
                        </div>
                    </div>

                    <Link href={backUrl} className="inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-slate-900 font-medium">
                        <ArrowRight className="w-4 h-4" />
                        العودة لتفاصيل العرض
                    </Link>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-4 sm:px-6 pt-8 sm:pt-12">
                {/* Step Indicator */}
                <div className="mb-8 flex items-center justify-center gap-2 sm:gap-4 text-xs font-semibold">
                    <div className="flex items-center gap-1.5 text-slate-400">
                        <span className="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-600">1</span>
                        <span>مراجعة العرض</span>
                    </div>
                    <div className="w-8 h-0.5 bg-slate-200" />
                    <div className="flex items-center gap-1.5 text-indigo-600">
                        <span className="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center">2</span>
                        <span className="font-bold">بيانات العميل والسداد</span>
                    </div>
                    <div className="w-8 h-0.5 bg-slate-200" />
                    <div className="flex items-center gap-1.5 text-slate-400">
                        <span className="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-600">3</span>
                        <span>تأكيد البدء</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    {/* Left 7 Cols: Customer Information Form */}
                    <div className="lg:col-span-7 space-y-6">
                        <Card className="border-slate-200/80 shadow-sm bg-white">
                            <CardHeader className="border-b border-slate-100 pb-4">
                                <CardTitle className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <User className="w-5 h-5 text-indigo-600" />
                                    بيانات التواصل والحساب
                                </CardTitle>
                                <CardDescription>
                                    سيتم إنشاء حسابك التلقائي وإصدار الفاتورة الرسمية باسم هذه البيانات فور إتمام السداد.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <form onSubmit={submit} id="checkout-form" className="space-y-4">
                                    {/* Full Name */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="client_name" className="text-xs font-bold text-slate-700">
                                            الاسم الكامل <span className="text-red-500">*</span>
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="client_name"
                                                type="text"
                                                placeholder="مثال: أحمد محمود"
                                                value={data.client_name}
                                                onChange={(e) => setData('client_name', e.target.value)}
                                                className="bg-slate-50/50"
                                                required
                                            />
                                        </div>
                                        {errors.client_name && <p className="text-xs text-red-500">{errors.client_name}</p>}
                                    </div>

                                    {/* Email */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="client_email" className="text-xs font-bold text-slate-700">
                                            البريد الإلكتروني <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="client_email"
                                            type="email"
                                            placeholder="name@example.com"
                                            value={data.client_email}
                                            onChange={(e) => setData('client_email', e.target.value)}
                                            className="bg-slate-50/50 font-mono text-sm"
                                            required
                                        />
                                        {errors.client_email && <p className="text-xs text-red-500">{errors.client_email}</p>}
                                        <span className="text-[11px] text-slate-400">ستصلك بيانات الدخول والفاتورة وإشعارات المشروع على هذا البريد.</span>
                                    </div>

                                    {/* Phone & WhatsApp */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="client_phone" className="text-xs font-bold text-slate-700">
                                                رقم الهاتف / الموبايل
                                            </Label>
                                            <Input
                                                id="client_phone"
                                                type="tel"
                                                placeholder="010XXXXXXXX"
                                                value={data.client_phone}
                                                onChange={(e) => {
                                                    const val = e.target.value;
                                                    setData(prev => ({
                                                        ...prev,
                                                        client_phone: val,
                                                        client_whatsapp: prev.client_whatsapp || val,
                                                    }));
                                                }}
                                                className="bg-slate-50/50 font-mono text-sm"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="client_whatsapp" className="text-xs font-bold text-slate-700">
                                                رقم الواتساب (WhatsApp)
                                            </Label>
                                            <Input
                                                id="client_whatsapp"
                                                type="tel"
                                                placeholder="010XXXXXXXX"
                                                value={data.client_whatsapp}
                                                onChange={(e) => setData('client_whatsapp', e.target.value)}
                                                className="bg-slate-50/50 font-mono text-sm"
                                            />
                                        </div>
                                    </div>

                                    {/* Company Name */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="company_name" className="text-xs font-bold text-slate-700">
                                            اسم الشركة / المؤسسة (اختياري)
                                        </Label>
                                        <Input
                                            id="company_name"
                                            type="text"
                                            placeholder="اسم شركتك أو علامتك التجارية"
                                            value={data.company_name}
                                            onChange={(e) => setData('company_name', e.target.value)}
                                            className="bg-slate-50/50"
                                        />
                                    </div>

                                    {/* Notes */}
                                    <div className="space-y-1.5">
                                        <Label htmlFor="notes" className="text-xs font-bold text-slate-700">
                                            ملاحظات أو متطلبات إضافية (اختياري)
                                        </Label>
                                        <Textarea
                                            id="notes"
                                            placeholder="أي تفاصيل أو ملاحظات تود إضافتها لفريق العمل..."
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            rows={3}
                                            className="bg-slate-50/50 text-sm"
                                        />
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right 5 Cols: Order Summary & Checkout Action */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="border-slate-200/80 shadow-md bg-white overflow-hidden">
                            <CardHeader className="bg-slate-900 text-white p-5">
                                <span className="font-mono text-xs text-indigo-300 font-semibold">{quotation.quotation_number}</span>
                                <CardTitle className="text-base font-bold text-white mt-1 line-clamp-2">
                                    {quotation.title}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-6 space-y-4">
                                <div className="space-y-2.5 pb-4 border-b border-slate-100 text-sm">
                                    <div className="flex items-center justify-between text-slate-600">
                                        <span>إجمالي أعمال المشروع:</span>
                                        <span className="font-mono font-bold text-slate-900">{quotation.development_total} {quotation.currency}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-slate-600">
                                        <span>نسبة الدفعة المقدمة:</span>
                                        <span className="font-mono font-semibold text-emerald-600">{quotation.deposit_percentage}%</span>
                                    </div>
                                    <div className="flex items-center justify-between text-slate-600">
                                        <span>المتبقي عند التسليم:</span>
                                        <span className="font-mono text-slate-500">{quotation.remaining_amount} {quotation.currency}</span>
                                    </div>
                                </div>

                                <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 space-y-1 text-center">
                                    <span className="text-xs font-bold text-emerald-800 uppercase tracking-wider block">
                                        المبلغ المستحق للدفع الآن (50%)
                                    </span>
                                    <div className="text-3xl font-extrabold font-mono text-emerald-700">
                                        {quotation.deposit_amount} <span className="text-sm font-normal text-emerald-600">{quotation.currency}</span>
                                    </div>
                                </div>

                                <div className="space-y-2 pt-2">
                                    <Button
                                        type="submit"
                                        form="checkout-form"
                                        disabled={processing}
                                        className="w-full bg-slate-900 text-white hover:bg-slate-800 text-base font-bold py-6 rounded-2xl shadow-lg hover:shadow-xl transition-all gap-2"
                                    >
                                        <Lock className="w-4 h-4 text-emerald-400" />
                                        {processing ? 'جاري التوجيه لبوابة الدفع...' : 'الانتقال للدفع الآمن الآن'}
                                    </Button>
                                    <p className="text-[11px] text-center text-slate-400">
                                        سيتم نقلك بأمان إلى بوابة الدفع لإتمام العملية بواسطة البطاقة البنكية أو المحافظ.
                                    </p>
                                </div>

                                <div className="pt-4 border-t border-slate-100 flex items-center justify-center gap-3 text-xs text-slate-400 font-medium">
                                    <span className="flex items-center gap-1">
                                        <ShieldCheck className="w-3.5 h-3.5 text-emerald-500" />
                                        تشفير 256-bit SSL
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-indigo-500" />
                                        فاتورة رسمية فورية
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </main>
        </div>
    );
}
