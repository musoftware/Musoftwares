import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { 
    FileText, Plus, Search, Eye, Edit3, Trash2, Copy, Check, 
    Share2, MessageCircle, DollarSign,
    CheckCircle2, Globe
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

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
    currencies: Array<{ id: number; currency: string; symbol?: string }>;
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
        toast.success(__('quotations.link_copied'));
        setTimeout(() => setCopiedId(null), 2500);
    };

    const handleDuplicate = (id: number) => {
        router.post(`/admin/marketplace/quotations/${id}/duplicate`, {}, {
            onSuccess: () => toast.success(__('quotations.duplicated_success')),
        });
    };

    const handleDelete = (id: number, title: string) => {
        if (confirm(__('quotations.delete_confirm', { title }))) {
            router.delete(`/admin/marketplace/quotations/${id}`, {
                onSuccess: () => toast.success(__('quotations.deleted_success')),
            });
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100">{__('quotations.status_active')}</Badge>;
            case 'draft':
                return <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-200">{__('quotations.status_draft')}</Badge>;
            case 'archived':
                return <Badge variant="secondary">{__('quotations.status_archived')}</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AdminSidebarLayout header={__('quotations.title')}>
            <Head title={__('quotations.admin_title')} />

            <div className="space-y-6 max-w-7xl mx-auto pb-12">
                {/* Header & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('quotations.management_title')}</h1>
                            <Badge variant="secondary" className="font-mono text-xs">{quotations.total}</Badge>
                        </div>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('quotations.management_desc')}
                        </p>
                    </div>

                    <Link href="/admin/marketplace/quotations/create">
                        <Button className="bg-slate-900 text-white hover:bg-slate-800 shadow-sm gap-2">
                            <Plus className="w-4 h-4" />
                            {__('quotations.create_new')}
                        </Button>
                    </Link>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">{__('quotations.total_quotations')}</CardTitle>
                            <FileText className="h-4 w-4 text-slate-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{metrics.total_quotations}</div>
                            <p className="text-xs text-emerald-600 font-medium mt-1">
                                {__('quotations.active_quotations_count', { count: metrics.active_quotations })}
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">{__('quotations.total_views')}</CardTitle>
                            <Globe className="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{metrics.total_views}</div>
                            <p className="text-xs text-slate-500 mt-1">{__('quotations.total_views_desc')}</p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">{__('quotations.paid_orders')}</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">{metrics.total_orders}</div>
                            <p className="text-xs text-slate-500 mt-1">{__('quotations.paid_orders_desc')}</p>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-semibold text-slate-600 uppercase tracking-wider">{__('quotations.total_collected')}</CardTitle>
                            <DollarSign className="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-slate-900">
                                {new Intl.NumberFormat('en-US', { minimumFractionDigits: 0 }).format(metrics.total_collected)}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">{__('quotations.total_collected_desc')}</p>
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
                                    placeholder={__('quotations.search_placeholder')}
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
                                    <option value="all">{__('quotations.status_all')}</option>
                                    <option value="active">{__('quotations.status_active')}</option>
                                    <option value="draft">{__('quotations.status_draft')}</option>
                                    <option value="archived">{__('quotations.status_archived')}</option>
                                </select>

                                <select
                                    value={currencyFilter}
                                    onChange={(e) => handleFilterChange(statusFilter, e.target.value)}
                                    className="h-10 px-3 text-sm bg-white border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-slate-900"
                                >
                                    <option value="all">{__('quotations.currency_all')}</option>
                                    {currencies.map(c => (
                                        <option key={c.id} value={c.currency}>{c.currency} {c.symbol ? `(${c.symbol})` : ''}</option>
                                    ))}
                                </select>

                                <Button type="submit" variant="secondary" className="shrink-0">
                                    {__('quotations.search_button')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Quotations List */}
                {quotations.data.length === 0 ? (
                    <Card className="border-dashed border-2 border-slate-200 p-12 text-center bg-slate-50/50">
                        <FileText className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                        <h3 className="text-lg font-semibold text-slate-800">{__('quotations.empty_state_title')}</h3>
                        <p className="text-sm text-slate-500 max-w-md mx-auto mt-1 mb-6">
                            {__('quotations.empty_state_desc')}
                        </p>
                        <Link href="/admin/marketplace/quotations/create">
                            <Button className="bg-slate-900 text-white hover:bg-slate-800 gap-2">
                                <Plus className="w-4 h-4" />
                                {__('quotations.create_new')}
                            </Button>
                        </Link>
                    </Card>
                ) : (
                    <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-right border-collapse">
                                <thead>
                                    <tr className="bg-slate-50/80 border-b border-slate-200/80 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        <th className="py-3.5 px-4">{__('quotations.col_quotation')}</th>
                                        <th className="py-3.5 px-4">{__('quotations.col_development_total')}</th>
                                        <th className="py-3.5 px-4">{__('quotations.col_deposit_50')}</th>
                                        <th className="py-3.5 px-4">{__('quotations.col_indicative_total')}</th>
                                        <th className="py-3.5 px-4">{__('quotations.col_views')} / {__('quotations.col_orders')}</th>
                                        <th className="py-3.5 px-4">{__('quotations.col_status')}</th>
                                        <th className="py-3.5 px-4 text-center">{__('quotations.col_actions')}</th>
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
                                                        {new Date(quote.created_at).toLocaleDateString()}
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
                                                        ({quote.deposit_percentage}%)
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
                                                    <span className="text-slate-600 flex items-center gap-1 font-medium" title={__('quotations.col_views')}>
                                                        <Eye className="w-3.5 h-3.5 text-slate-400" />
                                                        {quote.views_count}
                                                    </span>
                                                    <span className="text-emerald-600 flex items-center gap-1 font-bold" title={__('quotations.paid_orders')}>
                                                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                                                        {quote.paid_orders_count || 0}
                                                    </span>
                                                </div>
                                            </td>

                                            <td className="py-4 px-4">
                                                {getStatusBadge(quote.status)}
                                            </td>

                                            <td className="py-4 px-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => copyShareLink(quote)}
                                                        className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900"
                                                        title={__('quotations.copy_shortlink')}
                                                    >
                                                        {copiedId === quote.id ? <Check className="w-4 h-4 text-emerald-600" /> : <Share2 className="w-4 h-4" />}
                                                    </Button>

                                                    <Link href={`/admin/marketplace/quotations/${quote.id}`}>
                                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900" title={__('quotations.view_details')}>
                                                            <Eye className="w-4 h-4" />
                                                        </Button>
                                                    </Link>

                                                    <Link href={`/admin/marketplace/quotations/${quote.id}/edit`}>
                                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900" title={__('quotations.save_changes')}>
                                                            <Edit3 className="w-4 h-4" />
                                                        </Button>
                                                    </Link>

                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDuplicate(quote.id)}
                                                        className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900"
                                                        title={__('quotations.duplicate')}
                                                    >
                                                        <Copy className="w-4 h-4" />
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(quote.id, quote.title)}
                                                        className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                                        title={__('quotations.delete')}
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
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
