import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { 
    ArrowRight, Edit3, Copy, Check, ExternalLink, Share2, 
    MessageCircle, Eye, CheckCircle2, DollarSign, Clock, 
    Printer, Layers, Trash2, Calendar, UserCheck, ShieldCheck, 
    Globe, Server, Code, FileText
} from 'lucide-react';
import { toast } from 'sonner';

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

interface QuotationOrder {
    id: number;
    uuid: string;
    order_number: string;
    client_name: string;
    client_email: string;
    client_phone?: string;
    client_whatsapp?: string;
    company_name?: string;
    notes?: string;
    deposit_amount: number;
    currency: string;
    status: string;
    paid_at?: string;
    payment_gateway?: string;
    payment_reference?: string;
    created_at: string;
    invoice?: {
        id: number;
        invoice_number: string;
        status: string;
    };
    user?: {
        id: number;
        name: string;
        email: string;
    };
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
    status: string;
    valid_until?: string;
    scope_markdown?: string;
    notes?: string;
    views_count: number;
    last_viewed_at?: string;
    created_at: string;
    items: QuotationItem[];
    orders: QuotationOrder[];
    creator?: {
        id: number;
        name: string;
    };
    shortlink?: {
        id: number;
        short_code: string;
    };
}

interface ShowProps {
    quotation: Quotation;
    publicUrl: string;
    shortUrl: string;
    whatsappShareUrl: string;
    whatsappMessage: string;
}

export default function Show({ quotation, publicUrl, shortUrl, whatsappShareUrl, whatsappMessage }: ShowProps) {
    const [copiedLink, setCopiedLink] = useState(false);
    const [copiedWa, setCopiedWa] = useState(false);

    const handleCopyShortLink = () => {
        navigator.clipboard.writeText(shortUrl);
        setCopiedLink(true);
        toast.success('تم نسخ الرابط المختصر لعرض السعر!');
        setTimeout(() => setCopiedLink(false), 2500);
    };

    const handleCopyWaText = () => {
        navigator.clipboard.writeText(whatsappMessage);
        setCopiedWa(true);
        toast.success('تم نسخ رسالة الواتساب الجاهزة!');
        setTimeout(() => setCopiedWa(false), 2500);
    };

    const handleDuplicate = () => {
        router.post(`/admin/marketplace/quotations/${quotation.id}/duplicate`, {}, {
            onSuccess: () => toast.success('تم استنساخ عرض السعر بنجاح'),
        });
    };

    const handleDelete = () => {
        if (confirm(`هل أنت متأكد من حذف عرض السعر "${quotation.title}"؟`)) {
            router.delete(`/admin/marketplace/quotations/${quotation.id}`, {
                onSuccess: () => toast.success('تم حذف عرض السعر بنجاح'),
            });
        }
    };

    const ourWorkItems = quotation.items?.filter(it => it.type === 'our_work') || [];
    const indicativeItems = quotation.items?.filter(it => it.type === 'indicative_cost') || [];
    const paidOrders = quotation.orders?.filter(o => o.status === 'paid') || [];

    return (
        <AdminSidebarLayout header={`عرض السعر (${quotation.quotation_number})`}>
            <Head title={`عرض سعر: ${quotation.title} - Admin`} />

            <div className="max-w-7xl mx-auto space-y-8 pb-16">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/admin/marketplace/quotations"
                            className="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
                        >
                            <ArrowRight className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                    {quotation.quotation_number}
                                </span>
                                <h1 className="text-xl font-bold text-slate-900">{quotation.title}</h1>
                                {quotation.status === 'active' ? (
                                    <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200">نشط</Badge>
                                ) : (
                                    <Badge variant="outline">{quotation.status}</Badge>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">
                                تم الإنشاء: {new Date(quotation.created_at).toLocaleDateString('ar-EG')} • بواسطة: {quotation.creator?.name || 'Admin'}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* WhatsApp Direct Share */}
                        <a
                            href={whatsappShareUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button className="bg-[#25D366] hover:bg-[#1EBE5D] text-white gap-1.5 shadow-sm">
                                <MessageCircle className="w-4 h-4" />
                                إرسال عبر WhatsApp
                            </Button>
                        </a>

                        {/* Open Public Preview */}
                        <a
                            href={publicUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="outline" className="border-slate-300 gap-1.5">
                                <ExternalLink className="w-4 h-4" />
                                معاينة صفحة العميل
                            </Button>
                        </a>

                        {/* Edit */}
                        <Link href={`/admin/marketplace/quotations/${quotation.id}/edit`}>
                            <Button variant="outline" className="border-slate-300 gap-1.5">
                                <Edit3 className="w-4 h-4" />
                                تعديل
                            </Button>
                        </Link>

                        {/* Duplicate */}
                        <Button
                            variant="outline"
                            onClick={handleDuplicate}
                            className="border-slate-300 gap-1.5 text-slate-700 hover:text-indigo-600"
                        >
                            <Layers className="w-4 h-4" />
                            استنساخ
                        </Button>

                        {/* Delete */}
                        <Button
                            variant="ghost"
                            onClick={handleDelete}
                            className="text-slate-400 hover:text-red-600 hover:bg-red-50"
                        >
                            <Trash2 className="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                {/* Quick Share Toolbox Card */}
                <Card className="border-indigo-100 bg-gradient-to-br from-indigo-50/40 via-white to-blue-50/20 shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-bold text-indigo-950 flex items-center gap-2">
                            <Share2 className="w-4 h-4 text-indigo-600" />
                            صندوق المشاركة السريعة للعملاء (Public Shortlink)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                            <div className="flex items-center gap-2 bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
                                <Globe className="w-4 h-4 text-slate-400 shrink-0" />
                                <input
                                    readOnly
                                    value={shortUrl}
                                    className="bg-transparent font-mono text-xs text-slate-800 flex-1 outline-none truncate"
                                />
                                <Button
                                    size="sm"
                                    onClick={handleCopyShortLink}
                                    className="bg-slate-900 text-white hover:bg-slate-800 text-xs shrink-0 gap-1"
                                >
                                    {copiedLink ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                                    نسخ الرابط
                                </Button>
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={handleCopyWaText}
                                    className="bg-white border-slate-200 text-slate-700 text-xs gap-1.5"
                                >
                                    {copiedWa ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <MessageCircle className="w-3.5 h-3.5 text-[#25D366]" />}
                                    نسخ رسالة الواتساب المجهزة
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* KPI Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-slate-200 shadow-sm bg-white">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase">إجمالي التطوير</CardTitle>
                            <Code className="h-4 w-4 text-slate-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">
                                {quotation.development_total} {quotation.currency}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">{ourWorkItems.length} بنود أعمال برمجية</p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 shadow-sm bg-white">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase">الدفعة المقدمة (50%)</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600">
                                {quotation.deposit_amount} {quotation.currency}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">نسبة {quotation.deposit_percentage}% لبدء العمل</p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 shadow-sm bg-white">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase">مشاهدات الرابط</CardTitle>
                            <Eye className="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{quotation.views_count}</div>
                            <p className="text-xs text-slate-500 mt-1">
                                {quotation.last_viewed_at ? `آخر مشاهدة: ${new Date(quotation.last_viewed_at).toLocaleTimeString('ar-EG')}` : 'لم تتم المشاهدة بعد'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 shadow-sm bg-white">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase">الطلبات المسددة</CardTitle>
                            <UserCheck className="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{paidOrders.length}</div>
                            <p className="text-xs text-emerald-600 font-medium mt-1">عملاء اعتمدوا العرض ودفعوا</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left 2 Cols: Scope & Items */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* Scope Markdown Preview */}
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader className="border-b border-slate-100 pb-4">
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-indigo-600" />
                                    نطاق العمل، المخرجات، والشروط (Markdown)
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6" data-color-mode="light">
                                {quotation.scope_markdown ? (
                                    <MDEditor.Markdown source={quotation.scope_markdown} />
                                ) : (
                                    <p className="text-sm text-slate-400">لا يوجد نص مخصص لنطاق العمل.</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Items Table */}
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader className="border-b border-slate-100 pb-4">
                                <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <Code className="w-4 h-4 text-emerald-600" />
                                    تفاصيل بنود التسعير (أعمالنا للتطوير)
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-4">
                                <div className="divide-y divide-slate-100">
                                    {ourWorkItems.map((it) => (
                                        <div key={it.id} className="py-3.5 flex items-center justify-between">
                                            <div>
                                                <h5 className="font-bold text-slate-900 text-sm">{it.title}</h5>
                                                {it.description && <p className="text-xs text-slate-500 mt-0.5">{it.description}</p>}
                                            </div>
                                            <div className="text-left font-mono">
                                                <span className="font-bold text-slate-900">{it.total} {quotation.currency}</span>
                                                {it.quantity > 1 && (
                                                    <span className="text-[11px] text-slate-400 block">({it.quantity} × {it.price})</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Indicative Costs Table */}
                        {indicativeItems.length > 0 && (
                            <Card className="border-amber-200/80 bg-amber-50/20 shadow-sm">
                                <CardHeader className="border-b border-amber-100 pb-4">
                                    <CardTitle className="text-base font-bold text-amber-950 flex items-center gap-2">
                                        <Server className="w-4 h-4 text-amber-600" />
                                        التكاليف الاسترشادية الخارجية (استضافات ودومينات)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-4">
                                    <div className="divide-y divide-amber-100">
                                        {indicativeItems.map((it) => (
                                            <div key={it.id} className="py-3 flex items-center justify-between">
                                                <div>
                                                    <h5 className="font-bold text-slate-900 text-sm">{it.title}</h5>
                                                    {it.external_link && (
                                                        <a
                                                            href={it.external_link}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1 text-xs text-amber-700 hover:underline mt-0.5"
                                                        >
                                                            <ExternalLink className="w-3 h-3" />
                                                            {it.link_label || 'رابط مزود الخدمة'}
                                                        </a>
                                                    )}
                                                </div>
                                                <div className="text-left font-mono">
                                                    <span className="font-bold text-amber-900">{it.total} {quotation.currency}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Right 1 Col: Orders / Client Submissions */}
                    <div className="space-y-6">
                        <Card className="border-slate-200 shadow-sm">
                            <CardHeader className="border-b border-slate-100 pb-3">
                                <CardTitle className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <UserCheck className="w-4 h-4 text-emerald-600" />
                                    العملاء المعتمدين وسداد الدفعة ({quotation.orders?.length || 0})
                                </CardTitle>
                                <CardDescription>
                                    العملاء الذين سجلوا بياناتهم وقاموا بالسداد عبر الرابط العام.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-4">
                                {(!quotation.orders || quotation.orders.length === 0) ? (
                                    <div className="text-center py-8 text-slate-400 text-xs">
                                        لم يقم أي عميل بسداد الدفعة المقدمة عبر هذا الرابط حتى الآن.
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {quotation.orders.map((order) => (
                                            <div key={order.id} className="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-mono text-xs font-bold text-slate-500">{order.order_number}</span>
                                                    {order.status === 'paid' ? (
                                                        <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px]">مدفوع 50%</Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="text-[10px]">{order.status}</Badge>
                                                    )}
                                                </div>

                                                <div>
                                                    <h6 className="font-bold text-slate-900 text-sm">{order.client_name}</h6>
                                                    <p className="text-xs text-slate-500">{order.client_email}</p>
                                                    {order.client_phone && <p className="text-xs text-slate-500 font-mono mt-0.5">{order.client_phone}</p>}
                                                </div>

                                                <div className="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                                    <span className="font-mono font-bold text-emerald-600">
                                                        {order.deposit_amount} {order.currency}
                                                    </span>
                                                    {order.invoice && (
                                                        <Link
                                                            href={`/admin/invoices?search=${order.invoice.invoice_number}`}
                                                            className="text-indigo-600 hover:underline inline-flex items-center gap-1"
                                                        >
                                                            فاتورة #{order.invoice.invoice_number}
                                                        </Link>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Internal Notes */}
                        {quotation.notes && (
                            <Card className="border-slate-200 shadow-sm bg-slate-50/50">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-bold text-slate-700">ملاحظات الإدارة الداخلية</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-xs text-slate-600 whitespace-pre-wrap">{quotation.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
