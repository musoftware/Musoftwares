import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { 
    CheckCircle2, Server, Code, ShieldCheck, Printer, 
    ArrowLeft, ExternalLink, CreditCard, Sparkles, 
    Lock, Calendar, HelpCircle, FileText, Check, PhoneCall
} from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';

interface QuotationItem {
    id: number;
    type: 'our_work' | 'indicative_cost';
    title: string;
    description?: string;
    price: number;
    quantity: number;
    total: number;
    external_link?: string;
    link_label?: string;
}

interface Quotation {
    id: number;
    uuid: string;
    quotation_number: string;
    title: string;
    currency: string;
    deposit_percentage: number;
    development_total: number;
    indicative_total: number;
    grand_total: number;
    deposit_amount: number;
    remaining_amount: number;
    valid_until?: string;
    scope_markdown?: string;
    created_at: string;
    items: QuotationItem[];
}

interface QuotationShowProps {
    quotation: Quotation;
    checkoutUrl: string;
}

export default function QuotationShow({ quotation, checkoutUrl }: QuotationShowProps) {
    const ourWorkItems = quotation.items?.filter(it => it.type === 'our_work') || [];
    const indicativeItems = quotation.items?.filter(it => it.type === 'indicative_cost') || [];

    const handlePrint = () => {
        window.print();
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white pb-32">
            <Head title={`عرض سعر: ${quotation.title} - مسوفتوير`} />

            {/* Top Brand Header */}
            <header className="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 print:static print:border-none">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="w-9 h-9 fill-current text-slate-900" />
                        <div>
                            <span className="font-extrabold text-lg tracking-tight text-slate-900 block leading-none">
                                MUSOFTWARE
                            </span>
                            <span className="text-[10px] text-slate-400 font-medium tracking-wider uppercase">
                                Software & Digital Solutions
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handlePrint}
                            className="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-600 border-slate-300 print:hidden"
                        >
                            <Printer className="w-3.5 h-3.5" />
                            طباعة العرض (PDF)
                        </Button>

                        <Link href={checkoutUrl} className="print:hidden">
                            <Button className="bg-slate-900 text-white hover:bg-slate-800 text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all gap-1.5">
                                <Sparkles className="w-4 h-4 text-amber-400" />
                                قبول العرض ودفع {quotation.deposit_percentage}% مقدم
                            </Button>
                        </Link>
                    </div>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-4 sm:px-6 pt-8 sm:pt-12 space-y-8">
                {/* Hero / Proposal Title Banner */}
                <div className="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white p-6 sm:p-10 rounded-3xl shadow-xl relative overflow-hidden">
                    <div className="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
                    <div className="relative z-10 space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="font-mono text-xs uppercase tracking-widest text-indigo-300 font-bold bg-white/10 px-3 py-1 rounded-full border border-white/10">
                                عرض سعر رسمي • {quotation.quotation_number}
                            </span>
                            {quotation.valid_until && (
                                <span className="text-xs text-slate-300 flex items-center gap-1 font-medium">
                                    <Calendar className="w-3.5 h-3.5 text-indigo-400" />
                                    صالح حتى: {new Date(quotation.valid_until).toLocaleDateString('ar-EG')}
                                </span>
                            )}
                        </div>

                        <h1 className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            {quotation.title}
                        </h1>

                        <p className="text-sm sm:text-base text-slate-300 max-w-2xl leading-relaxed">
                            يسعدنا تقديم هذا المقترح الفني والمالي المتكامل لتنفيذ مشروعكم بأعلى معايير الجودة والأداء، مع تفصيل كامل لكافة مراحل التطوير والتكاليف.
                        </p>

                        <div className="pt-4 flex flex-wrap items-center gap-4 text-xs text-slate-300">
                            <span className="flex items-center gap-1.5">
                                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                                ضمان جودة برمجية معتمدة
                            </span>
                            <span className="flex items-center gap-1.5">
                                <Lock className="w-4 h-4 text-indigo-400" />
                                دفع إلكتروني مشفر وآمن 100%
                            </span>
                            <span className="flex items-center gap-1.5">
                                <CheckCircle2 className="w-4 h-4 text-amber-400" />
                                بدء التنفيذ فور سداد الدفعة المقدمة ({quotation.deposit_percentage}%)
                            </span>
                        </div>
                    </div>
                </div>

                {/* Financial Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {/* Dev Total */}
                    <Card className="border-slate-200/80 shadow-sm bg-white">
                        <CardHeader className="pb-2">
                            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                إجمالي أعمال التطوير (الأساس)
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl sm:text-3xl font-extrabold font-mono text-slate-900">
                                {quotation.development_total} <span className="text-sm font-normal text-slate-500">{quotation.currency}</span>
                            </div>
                            <p className="text-xs text-slate-500 mt-1">تكلفة البرمجة والتنفيذ الكاملة</p>
                        </CardContent>
                    </Card>

                    {/* Deposit Due Now (Highlight) */}
                    <Card className="border-emerald-300 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-md relative overflow-hidden">
                        <div className="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none" />
                        <CardHeader className="pb-2">
                            <span className="text-xs font-bold text-emerald-100 uppercase tracking-wider flex items-center gap-1">
                                <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                                الدفعة المقدمة للبدء ({quotation.deposit_percentage}%)
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl sm:text-4xl font-extrabold font-mono text-white">
                                {quotation.deposit_amount} <span className="text-sm font-normal text-emerald-100">{quotation.currency}</span>
                            </div>
                            <p className="text-xs text-emerald-100 mt-1 font-medium">المطلوب سداده الآن لبدء العمل فوراً</p>
                        </CardContent>
                    </Card>

                    {/* Remaining */}
                    <Card className="border-slate-200/80 shadow-sm bg-white">
                        <CardHeader className="pb-2">
                            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                الدفعة المتبقية ({100 - Number(quotation.deposit_percentage)}%)
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl sm:text-3xl font-extrabold font-mono text-slate-900">
                                {quotation.remaining_amount} <span className="text-sm font-normal text-slate-500">{quotation.currency}</span>
                            </div>
                            <p className="text-xs text-slate-500 mt-1">تستحق عند التسليم والاعتماد النهائي</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Scope of Work & Deliverables (Markdown) */}
                {quotation.scope_markdown && (
                    <Card className="border-slate-200/80 shadow-sm bg-white overflow-hidden">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 py-4">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <FileText className="w-4 h-4 text-indigo-600" />
                                تفاصيل ومواصفات المشروع (Scope & Deliverables)
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 sm:p-8" data-color-mode="light">
                            <div className="prose prose-slate max-w-none prose-headings:font-bold prose-h3:text-lg prose-h3:mt-6 prose-p:text-slate-600 prose-li:text-slate-600">
                                <MDEditor.Markdown source={quotation.scope_markdown} />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Development Pricing Items Table */}
                <Card className="border-slate-200/80 shadow-sm bg-white overflow-hidden">
                    <CardHeader className="border-b border-slate-100 bg-slate-50/50 py-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <Code className="w-4 h-4 text-emerald-600" />
                            جدول بنود التطوير والبرمجة (Our Work)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-right border-collapse">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase">
                                        <th className="py-3.5 px-6">البند / الميزة</th>
                                        <th className="py-3.5 px-4 text-center">الكمية</th>
                                        <th className="py-3.5 px-4 text-left">السعر الفردي</th>
                                        <th className="py-3.5 px-6 text-left">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-sm">
                                    {ourWorkItems.map((item, idx) => (
                                        <tr key={item.id || idx} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="py-4 px-6">
                                                <div className="font-bold text-slate-900">{item.title}</div>
                                                {item.description && (
                                                    <p className="text-xs text-slate-500 mt-1 max-w-xl leading-relaxed">{item.description}</p>
                                                )}
                                            </td>
                                            <td className="py-4 px-4 text-center font-mono text-slate-600">
                                                {item.quantity}
                                            </td>
                                            <td className="py-4 px-4 text-left font-mono text-slate-600">
                                                {item.price} {quotation.currency}
                                            </td>
                                            <td className="py-4 px-6 text-left font-mono font-bold text-slate-900">
                                                {item.total} {quotation.currency}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="bg-slate-50/80 font-bold text-slate-900 border-t border-slate-200">
                                        <td colSpan={3} className="py-4 px-6 text-left">إجمالي أعمال وبرمجة المشروع:</td>
                                        <td className="py-4 px-6 text-left font-mono text-base text-slate-900">
                                            {quotation.development_total} {quotation.currency}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Indicative External Costs (Hosting / Domain / SMS) */}
                {indicativeItems.length > 0 && (
                    <Card className="border-amber-200/90 bg-amber-50/20 shadow-sm overflow-hidden">
                        <CardHeader className="border-b border-amber-100 bg-amber-50/60 py-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <CardTitle className="text-base font-bold text-amber-950 flex items-center gap-2">
                                    <Server className="w-4 h-4 text-amber-600" />
                                    التكاليف الاسترشادية الخارجية (استضافة، دومين، خدمات طرف ثالث)
                                </CardTitle>
                                <Badge variant="outline" className="bg-amber-100/80 text-amber-800 border-amber-300 text-xs w-fit">
                                    يدفعها العميل مباشرة للمزود
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="divide-y divide-amber-100/80">
                                {indicativeItems.map((item, idx) => (
                                    <div key={item.id || idx} className="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div className="space-y-1">
                                            <h4 className="font-bold text-slate-900 text-sm">{item.title}</h4>
                                            {item.description && <p className="text-xs text-slate-600">{item.description}</p>}
                                            {item.external_link && (
                                                <a
                                                    href={item.external_link}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1.5 text-xs text-amber-700 hover:text-amber-900 font-semibold underline mt-1"
                                                >
                                                    <ExternalLink className="w-3.5 h-3.5" />
                                                    {item.link_label || 'رابط مزود الخدمة للحجز المباشر'}
                                                </a>
                                            )}
                                        </div>

                                        <div className="text-left font-mono">
                                            <span className="text-xs text-slate-500 block">تقديري</span>
                                            <span className="font-bold text-amber-900 text-base">{item.total} {quotation.currency}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Payment Gateways Badges Section */}
                <Card className="border-slate-200/80 shadow-sm bg-white p-6 sm:p-8">
                    <div className="text-center space-y-2 mb-6">
                        <h3 className="text-base font-bold text-slate-900 flex items-center justify-center gap-2">
                            <CreditCard className="w-5 h-5 text-indigo-600" />
                            طرق وبوابات الدفع الإلكتروني المعتمدة
                        </h3>
                        <p className="text-xs text-slate-500">
                            نوفر بوابات دفع مشفرة وآمنة بالكامل بالتعاون مع كبرى الشركات المعتمدة في مصر والشرق الأوسط والعالم
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                        {/* Visa */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <span className="font-extrabold text-[#1A1F71] text-sm italic">VISA</span>
                        </div>

                        {/* Mastercard */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <div className="flex -space-x-2">
                                <div className="w-4 h-4 rounded-full bg-[#EB001B]" />
                                <div className="w-4 h-4 rounded-full bg-[#F79E1B] opacity-80" />
                            </div>
                            <span>Mastercard</span>
                        </div>

                        {/* Meeza */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <span className="font-extrabold text-[#00828A]">ميزة Meeza</span>
                        </div>

                        {/* Kashier */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <span className="font-extrabold text-indigo-600 font-mono">Kashier</span>
                        </div>

                        {/* Instapay */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <span className="font-extrabold text-[#9b26b6]">InstaPay</span>
                        </div>

                        {/* Vodafone Cash / Wallets */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <span className="font-extrabold text-[#E60000]">المحافظ الإلكترونية (فودافون كاش)</span>
                        </div>

                        {/* Bank Transfer */}
                        <div className="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-slate-50/60 text-xs font-bold text-slate-700 shadow-xs">
                            <span>تحويل بنكي مباشر</span>
                        </div>
                    </div>
                </Card>
            </main>

            {/* Sticky Bottom Action Bar */}
            <div className="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-4 px-4 sm:px-6 shadow-2xl z-40 print:hidden">
                <div className="max-w-5xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold shrink-0">
                            50%
                        </div>
                        <div>
                            <span className="text-xs text-slate-500 block">الدفعة المقدمة لبدء العمل فوراً:</span>
                            <span className="font-mono text-xl sm:text-2xl font-extrabold text-slate-900">
                                {quotation.deposit_amount} {quotation.currency}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href={checkoutUrl} className="w-full sm:w-auto">
                            <Button className="w-full sm:w-auto bg-slate-900 text-white hover:bg-slate-800 text-sm sm:text-base font-bold px-8 py-6 rounded-2xl shadow-lg hover:shadow-xl transition-all gap-2">
                                <Sparkles className="w-5 h-5 text-amber-400" />
                                الموافقة على العرض وسداد الـ 50%
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
