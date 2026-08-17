import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { 
    FileText, Plus, Search, Eye, Edit3, Trash2, Copy, Check, 
    Share2, ExternalLink, MessageCircle, DollarSign, Layers,
    TrendingUp, CheckCircle2, Clock, Globe
} from 'lucide-react';
import { toast } from 'sonner';

interface QuotationItem {
    id: number;
    type: 'our_work' | 'indicative_cost';
    title: string;
    price: number;
    quantity: number;
    total: number;
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
    status: string;
    valid_until?: string;
    views_count: number;
    orders_count: number;
    paid_orders_count: number;
    created_at: string;
    items_count?: number;
    creator?: {
        id: number;
        name: string;
    };
    shortlink?: {
        id: number;
        short_code: string;
    };
}

interface IndexProps {
    quotations: {
        data: Quotation[];
        links: any[];
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        currency?: string;
    };
    metrics: {
        total_quotations: number;
        active_quotations: number;
        total_views: number;
        total_orders: number;
        total_collected: number;
    };
    currencies: Array<{ id: number; currency: string; name: string }>;
}

export default function Index({ quotations, filters, metrics, currencies }: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [currencyFilter, setCurrencyFilter] = useState(filters.currency || 'all');
    const [copiedId, setCopiedId] = useState<number | null>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/marketplace/quotations', {
            search,
            status: statusFilter !== 'all' ? statusFilter : undefined,
            currency: currencyFilter !== 'all' ? currencyFilter : undefined,
        }, { preserveState: true });
    };

    const handleFilterChange = (newStatus: string, newCurrency: string) => {
        setStatusFilter(newStatus);
        setCurrencyFilter(newCurrency);
        router.get('/admin/marketplace/quotations', {
            search: search || undefined,
            status: newStatus !== 'all' ? newStatus : undefined,
            currency: newCurrency !== 'all' ? newCurrency : undefined,
        }, { preserveState: true });
    };

    const copyShareLink = (quotation: Quotation) => {
        const publicUrl = quotation.shortlink 
            ? `${window.location.origin}/s/${quotation.shortlink.short_code}`
            : `${window.location.origin}/guest/quotations/${quotation.uuid}`;

        navigator.clipboard.writeText(publicUrl);
        setCopiedId(quotation.id);
        toast.success('تم نسخ الرابط العام لعرض السعر!');
        setTimeout(() => setCopiedId(null), 2500);
    };

    const handleDuplicate = (id: number) => {
        router.post(`/admin/marketplace/quotations/${id}/duplicate`, {}, {
            onSuccess: () => toast.success('تم استنساخ عرض السعر بنجاح'),
        });
    };

    const handleDelete = (id: number, title: string) => {
        if (confirm(`هل أنت متأكد من حذف عرض السعر "${title}"؟`)) {
            router.delete(`/admin/marketplace/quotations/${id}`, {
                onSuccess: () => toast.success('تم حذف عرض السعر بنجاح'),
            });
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100">نشط ومتاح للعملاء</Badge>;
            case 'draft':
                return <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-200">مسودة</Badge>;
            case 'archived':
                return <Badge variant="secondary">مؤرشف</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AdminSidebarLayout header="إدارة عروض الأسعار (Quotations)">
            <Head title="عروض الأسعار - Admin" />

            <div className="space-y-6 max-w-7xl mx-auto pb-12">
                {/* Header & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">عروض الأسعار والمقترحات العامة</h1>
                            <Badge variant="secondary" className="font-mono text-xs">{quotations.total}</Badge>
                        </div>
                        <p className="text-sm text-slate-500 mt-1">
                            إنشاء وإدارة عروض الأسعار العامة، وتوليد روابط دفع الـ 50% للعملاء مع احتساب تلقائي للتكاليف الاسترشادية.
                        </p>
                    </div>

                    <Link href="/admin/marketplace/quotations/create">
                        <Button className="bg-slate-900 text-white hover:bg-slate-800 shadow-sm gap-2">
                            <Plus className="w-4 h-4" />
                            إنشاء عرض سعر جديد
                        </Button>
                    </Link>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">إجمالي العروض</CardTitle>
                            <FileText className="h-4 w-4 text-slate-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{metrics.total_quotations}</div>
                            <p className="text-xs text-emerald-600 font-medium mt-1">
                                {metrics.active_quotations} عرض نشط وجاهز للإرسال
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">مشاهدات العروض</CardTitle>
                            <Globe className="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{metrics.total_views}</div>
                            <p className="text-xs text-slate-500 mt-1">إجمالي فتح العملاء للروابط العامة</p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">العروض المدفوعة (50%)</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{metrics.total_orders}</div>
                            <p className="text-xs text-slate-500 mt-1">عملاء سددوا الدفعة المقدمة وبدأوا العمل</p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">المحصل من الدفعات</CardTitle>
                            <DollarSign className="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">
                                {new Intl.NumberFormat('en-US', { minimumFractionDigits: 0 }).format(metrics.total_collected)}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">إجمالي الإيرادات المحصلة عبر العروض</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters & Search */}
                <Card className="border-slate-200/80 shadow-sm">
                    <CardContent className="p-4">
                        <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-3 items-center">
                            <div className="relative flex-1 w-full">
                                <Search className="w-4 h-4 absolute right-3 top-3 text-slate-400" />
                                <Input
                                    type="text"
                                    placeholder="البحث برقم العرض أو العنوان..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pr-9 bg-slate-50/50 border-slate-200"
                                />
                            </div>

                            <div className="flex gap-2 w-full md:w-auto">
                                <select
                                    value={statusFilter}
                                    onChange={(e) => handleFilterChange(e.target.value, currencyFilter)}
                                    className="h-10 px-3 text-sm bg-white border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    <option value="all">كافة الحالات</option>
                                    <option value="active">نشط (Active)</option>
                                    <option value="draft">مسودة (Draft)</option>
                                    <option value="archived">مؤرشف (Archived)</option>
                                </select>

                                <select
                                    value={currencyFilter}
                                    onChange={(e) => handleFilterChange(statusFilter, e.target.value)}
                                    className="h-10 px-3 text-sm bg-white border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    <option value="all">كافة العملات</option>
                                    {currencies.map(c => (
                                        <option key={c.id} value={c.currency}>{c.currency} ({c.name})</option>
                                    ))}
                                </select>

                                <Button type="submit" variant="secondary" className="shrink-0">
                                    بحث
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Quotations List */}
                {quotations.data.length === 0 ? (
                    <Card className="border-dashed border-2 border-slate-200 p-12 text-center bg-slate-50/50">
                        <FileText className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                        <h3 className="text-lg font-semibold text-slate-800">لا توجد عروض أسعار حتى الآن</h3>
                        <p className="text-sm text-slate-500 max-w-md mx-auto mt-1 mb-6">
                            قم بإنشاء أول عرض سعر لمشروعك، وشاركه مع العملاء لتسهيل الاتفاق وسداد الـ 50% مقدم مباشرة.
                        </p>
                        <Link href="/admin/marketplace/quotations/create">
                            <Button className="bg-slate-900 text-white hover:bg-slate-800 gap-2">
                                <Plus className="w-4 h-4" />
                                إضافة عرض سعر جديد
                            </Button>
                        </Link>
                    </Card>
                ) : (
                    <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-right border-collapse">
                                <thead>
                                    <tr className="bg-slate-50/80 border-b border-slate-200/80 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        <th className="py-3.5 px-4">رقم العرض & العنوان</th>
                                        <th className="py-3.5 px-4">أعمال التطوير (الأساس)</th>
                                        <th className="py-3.5 px-4">الدفعة المقدمة (50%)</th>
                                        <th className="py-3.5 px-4">استرشادي خارجي</th>
                                        <th className="py-3.5 px-4">المشاهدات / الطلبات</th>
                                        <th className="py-3.5 px-4">الحالة</th>
                                        <th className="py-3.5 px-4 text-center">إجراءات سريعة</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-sm">
                                    {quotations.data.map((quote) => (
                                        <tr key={quote.id} className="hover:bg-slate-50/60 transition-colors">
                                            <td className="py-4 px-4">
                                                <div className="flex flex-col">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                                            {quote.quotation_number}
                                                        </span>
                                                        <Link 
                                                            href={`/admin/marketplace/quotations/${quote.id}`}
                                                            className="font-bold text-slate-900 hover:text-indigo-600 transition-colors line-clamp-1"
                                                        >
                                                            {quote.title}
                                                        </Link>
                                                    </div>
                                                    <span className="text-xs text-slate-400 mt-1">
                                                        بواسطة: {quote.creator?.name || 'Admin'} • {new Date(quote.created_at).toLocaleDateString('ar-EG')}
                                                    </span>
                                                </div>
                                            </td>

                                            <td className="py-4 px-4 font-mono font-bold text-slate-900">
                                                {quote.development_total} {quote.currency}
                                            </td>

                                            <td className="py-4 px-4">
                                                <div className="flex flex-col">
                                                    <span className="font-mono font-bold text-emerald-600">
                                                        {quote.deposit_amount} {quote.currency}
                                                    </span>
                                                    <span className="text-[11px] text-slate-400">
                                                        ({quote.deposit_percentage}% مقدم)
                                                    </span>
                                                </div>
                                            </td>

                                            <td className="py-4 px-4">
                                                {quote.indicative_total > 0 ? (
                                                    <span className="font-mono text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-md font-medium border border-amber-100">
                                                        {quote.indicative_total} {quote.currency}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-slate-400">-</span>
                                                )}
                                            </td>

                                            <td className="py-4 px-4">
                                                <div className="flex items-center gap-3 text-xs">
                                                    <span className="text-slate-600 flex items-center gap-1 font-medium" title="عدد المشاهدات">
                                                        <Eye className="w-3.5 h-3.5 text-slate-400" />
                                                        {quote.views_count}
                                                    </span>
                                                    <span className="text-emerald-600 flex items-center gap-1 font-bold" title="الطلبات المدفوعة">
                                                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                                                        {quote.paid_orders_count || 0}
                                                    </span>
                                                </div>
                                            </td>

                                            <td className="py-4 px-4">
                                                {getStatusBadge(quote.status)}
                                            </td>

                                            <td className="py-4 px-4">
                                                <div className="flex items-center justify-center gap-1.5">
                                                    {/* Copy Public Short Link */}
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => copyShareLink(quote)}
                                                        className="h-8 px-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                                                        title="نسخ الرابط العام"
                                                    >
                                                        {copiedId === quote.id ? (
                                                            <Check className="w-4 h-4 text-emerald-600" />
                                                        ) : (
                                                            <Copy className="w-4 h-4" />
                                                        )}
                                                    </Button>

                                                    {/* View / Show */}
                                                    <Link href={`/admin/marketplace/quotations/${quote.id}`}>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="h-8 px-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                                                            title="معاينة وتفاصيل"
                                                        >
                                                            <Eye className="w-4 h-4" />
                                                        </Button>
                                                    </Link>

                                                    {/* Edit */}
                                                    <Link href={`/admin/marketplace/quotations/${quote.id}/edit`}>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="h-8 px-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                                                            title="تعديل"
                                                        >
                                                            <Edit3 className="w-4 h-4" />
                                                        </Button>
                                                    </Link>

                                                    {/* Duplicate */}
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => handleDuplicate(quote.id)}
                                                        className="h-8 px-2 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50"
                                                        title="استنساخ العرض"
                                                    >
                                                        <Layers className="w-4 h-4" />
                                                    </Button>

                                                    {/* Delete */}
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => handleDelete(quote.id, quote.title)}
                                                        className="h-8 px-2 text-slate-400 hover:text-red-600 hover:bg-red-50"
                                                        title="حذف"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Links */}
                        {quotations.links && quotations.links.length > 3 && (
                            <div className="p-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
                                <div>
                                    عرض {quotations.from} إلى {quotations.to} من أصل {quotations.total} عرض
                                </div>
                                <div className="flex gap-1">
                                    {quotations.links.map((link, idx) => (
                                        <Link
                                            key={idx}
                                            href={link.url || '#'}
                                            preserveState
                                            className={`px-3 py-1.5 text-xs rounded-md font-medium transition-colors ${
                                                link.active
                                                    ? 'bg-slate-900 text-white'
                                                    : link.url
                                                    ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                                                    : 'text-slate-300 pointer-events-none'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
