import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import MDEditor from '@uiw/react-md-editor';
import { 
    ArrowRight, Edit3, Trash2, Copy, Check, 
    Share2, ExternalLink, MessageCircle, DollarSign,
    Layers, CheckCircle2, Clock, Globe, Code, Server,
    FileText, User, Receipt
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

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
    deposit_amount: number;
    currency: string;
    status: string;
    invoice_id?: number;
    paid_at?: string;
    created_at: string;
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
        toast.success(__('quotations.link_copied'));
        setTimeout(() => setCopiedLink(false), 2500);
    };

    const handleCopyWaText = () => {
        navigator.clipboard.writeText(whatsappMessage);
        setCopiedWa(true);
        toast.success(__('quotations.wa_copied'));
        setTimeout(() => setCopiedWa(false), 2500);
    };

    const handleDuplicate = () => {
        router.post(`/admin/marketplace/quotations/${quotation.id}/duplicate`, {}, {
            onSuccess: () => toast.success(__('quotations.duplicated_success')),
        });
    };

    const handleDelete = () => {
        if (confirm(__('quotations.delete_confirm', { title: quotation.title }))) {
            router.delete(`/admin/marketplace/quotations/${quotation.id}`, {
                onSuccess: () => toast.success(__('quotations.deleted_success')),
            });
        }
    };

    const ourWorkItems = quotation.items?.filter(i => i.type === 'our_work') || [];
    const indicativeItems = quotation.items?.filter(i => i.type === 'indicative_cost') || [];
    const paidOrders = quotation.orders?.filter(o => o.status === 'paid') || [];

    return (
        <AdminSidebarLayout header={`${__('quotations.title')}: ${quotation.quotation_number}`}>
            <Head title={`${__('quotations.title')}: ${quotation.title} - ${__('quotations.admin_title')}`} />

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
                                    <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200">{__('quotations.status_active')}</Badge>
                                ) : (
                                    <Badge variant="outline">{quotation.status}</Badge>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">
                                {new Date(quotation.created_at).toLocaleDateString()}
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
                                {__('quotations.share_whatsapp')}
                            </Button>
                        </a>

                        {/* Open Public Preview */}
                        <a
                            href={publicUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="outline" className="border-slate-300 gap-1.5 text-slate-700">
                                <ExternalLink className="w-4 h-4" />
                                {__('quotations.preview_public')}
                            </Button>
                        </a>

                        {/* Edit Button */}
                        <Link href={`/admin/marketplace/quotations/${quotation.id}/edit`}>
                            <Button variant="outline" className="border-slate-300 gap-1.5 text-slate-700">
                                <Edit3 className="w-4 h-4" />
                                {__('quotations.save_changes')}
                            </Button>
                        </Link>

                        {/* Duplicate Button */}
                        <Button
                            variant="outline"
                            onClick={handleDuplicate}
                            className="border-slate-300 gap-1.5 text-slate-700"
                            title={__('quotations.duplicate')}
                        >
                            <Copy className="w-4 h-4" />
                        </Button>

                        {/* Delete Button */}
                        <Button
                            variant="outline"
                            onClick={handleDelete}
                            className="border-red-200 text-red-600 hover:bg-red-50 gap-1.5"
                            title={__('quotations.delete')}
                        >
                            <Trash2 className="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                {/* Share Box & ShortLink Hub */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card className="md:col-span-2 border-indigo-100 bg-gradient-to-br from-indigo-50/50 via-white to-sky-50/30 shadow-sm">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <Share2 className="w-4 h-4 text-indigo-600" />
                                {__('quotations.share_toolbox')}
                            </CardTitle>
                            <CardDescription>
                                {__('quotations.management_desc')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* ShortLink Box */}
                            <div className="flex flex-col sm:flex-row gap-2 items-center bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                                <div className="flex-1 w-full text-left font-mono text-sm text-indigo-700 font-semibold px-2 truncate">
                                    {shortUrl}
                                </div>
                                <Button
                                    type="button"
                                    onClick={handleCopyShortLink}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white gap-1.5 w-full sm:w-auto shrink-0 shadow-sm"
                                >
                                    {copiedLink ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                                    {copiedLink ? __('service_playbooks.copied') : __('quotations.copy_shortlink')}
                                </Button>
                            </div>

                            {/* WhatsApp Copy Prompt */}
                            <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                                <div className="truncate w-full font-mono text-[11px] text-slate-500">
                                    {whatsappMessage.substring(0, 80)}...
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={handleCopyWaText}
                                    className="shrink-0 gap-1 text-slate-700 border-slate-300"
                                >
                                    {copiedWa ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                    {copiedWa ? __('service_playbooks.copied') : __('quotations.wa_copied')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stats & Views Card */}
                    <Card className="border-slate-200 shadow-sm bg-white flex flex-col justify-between">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {__('quotations.total_views')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-3xl font-extrabold font-mono text-slate-900">{quotation.views_count}</span>
                                <Globe className="w-8 h-8 text-indigo-200" />
                            </div>
                            <div className="pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                                {quotation.last_viewed_at && (
                                    <p className="flex items-center gap-1 text-slate-600">
                                        <Clock className="w-3.5 h-3.5 text-slate-400" />
                                        {new Date(quotation.last_viewed_at).toLocaleString()}
                                    </p>
                                )}
                                <p className="text-emerald-600 font-medium">
                                    {paidOrders.length} {__('quotations.paid_orders')}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Financial Overview Banner */}
                <div className="p-6 rounded-2xl bg-slate-900 text-white shadow-md">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-slate-700/60">
                        <div className="space-y-1">
                            <span className="text-xs text-slate-400">{__('quotations.calc_dev_total')}</span>
                            <div className="text-2xl font-bold font-mono text-white">
                                {quotation.development_total} <span className="text-xs font-normal text-slate-400">{quotation.currency}</span>
                            </div>
                        </div>

                        <div className="space-y-1 pt-3 md:pt-0 md:pr-6">
                            <span className="text-xs text-emerald-400 font-semibold flex items-center gap-1">
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                {__('quotations.calc_deposit', { pct: quotation.deposit_percentage })}
                            </span>
                            <div className="text-2xl font-extrabold font-mono text-emerald-400">
                                {quotation.deposit_amount} <span className="text-xs font-normal text-emerald-200">{quotation.currency}</span>
                            </div>
                            <span className="text-[11px] text-slate-400 block">{__('quotations.calc_deposit_sub')}</span>
                        </div>

                        <div className="space-y-1 pt-3 md:pt-0 md:pr-6">
                            <span className="text-xs text-amber-300 font-medium">{__('quotations.calc_indicative_total')}</span>
                            <div className="text-2xl font-bold font-mono text-amber-300">
                                {quotation.indicative_total} <span className="text-xs font-normal text-amber-200">{quotation.currency}</span>
                            </div>
                            <span className="text-[11px] text-slate-400 block">{__('quotations.calc_indicative_sub')}</span>
                        </div>

                        <div className="space-y-1 pt-3 md:pt-0 md:pr-6">
                            <span className="text-xs text-slate-400">{__('quotations.calc_grand_total')}</span>
                            <div className="text-2xl font-bold font-mono text-slate-200">
                                {quotation.grand_total} <span className="text-xs font-normal text-slate-400">{quotation.currency}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Scope & Deliverables Markdown Section */}
                {quotation.scope_markdown && (
                    <Card className="border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader className="border-b border-slate-100 bg-slate-50/50 pb-4">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <FileText className="w-4 h-4 text-indigo-600" />
                                {__('quotations.section_scope')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 prose prose-slate max-w-none text-right" data-color-mode="light">
                            <MDEditor.Markdown source={quotation.scope_markdown} />
                        </CardContent>
                    </Card>
                )}

                {/* Hybrid Items Table */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Our Work Items */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 pb-4">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <Code className="w-4 h-4 text-emerald-600" />
                                {__('quotations.tab_our_work')} ({ourWorkItems.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0 divide-y divide-slate-100">
                            {ourWorkItems.length === 0 ? (
                                <div className="p-6 text-center text-xs text-slate-400">{__('quotations.empty_state_title')}</div>
                            ) : (
                                ourWorkItems.map(item => (
                                    <div key={item.id} className="p-4 flex items-start justify-between gap-3 hover:bg-slate-50/50 transition-colors">
                                        <div className="space-y-0.5">
                                            <h4 className="font-semibold text-sm text-slate-900">{item.title}</h4>
                                            {item.description && <p className="text-xs text-slate-500">{item.description}</p>}
                                            <span className="text-[11px] text-slate-400 font-mono">
                                                {item.quantity} × {item.price} {quotation.currency}
                                            </span>
                                        </div>
                                        <div className="font-mono font-bold text-sm text-slate-900 shrink-0">
                                            {item.total} {quotation.currency}
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    {/* Indicative External Costs */}
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader className="border-b border-slate-100 pb-4">
                            <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                                <Server className="w-4 h-4 text-amber-600" />
                                {__('quotations.tab_indicative_cost')} ({indicativeItems.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0 divide-y divide-slate-100">
                            {indicativeItems.length === 0 ? (
                                <div className="p-6 text-center text-xs text-slate-400">{__('quotations.empty_state_title')}</div>
                            ) : (
                                indicativeItems.map(item => (
                                    <div key={item.id} className="p-4 flex items-start justify-between gap-3 hover:bg-slate-50/50 transition-colors">
                                        <div className="space-y-1">
                                            <h4 className="font-semibold text-sm text-slate-900">{item.title}</h4>
                                            {item.description && <p className="text-xs text-slate-500">{item.description}</p>}
                                            {item.external_link && (
                                                <a 
                                                    href={item.external_link} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    className="inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline font-medium"
                                                >
                                                    <ExternalLink className="w-3 h-3" />
                                                    {item.link_label || item.external_link}
                                                </a>
                                            )}
                                        </div>
                                        <div className="font-mono font-bold text-sm text-amber-600 shrink-0">
                                            {item.total} {quotation.currency}
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Orders / Client Invoices List */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <CardTitle className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <Receipt className="w-4 h-4 text-indigo-600" />
                            {__('quotations.paid_orders_card')} ({quotation.orders?.length || 0})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {(!quotation.orders || quotation.orders.length === 0) ? (
                            <div className="p-8 text-center text-sm text-slate-500">
                                {__('quotations.no_orders_yet')}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-right text-sm">
                                    <thead className="bg-slate-50 text-xs font-semibold text-slate-600 uppercase border-b border-slate-200">
                                        <tr>
                                            <th className="py-3 px-4">{__('quotations.client_name')}</th>
                                            <th className="py-3 px-4">{__('quotations.client_email')}</th>
                                            <th className="py-3 px-4">{__('quotations.client_phone')}</th>
                                            <th className="py-3 px-4">{__('quotations.paid_amount')}</th>
                                            <th className="py-3 px-4">{__('quotations.col_status')}</th>
                                            <th className="py-3 px-4">{__('quotations.paid_at')}</th>
                                            <th className="py-3 px-4 text-center">{__('quotations.col_actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {quotation.orders.map(order => (
                                            <tr key={order.id} className="hover:bg-slate-50/50">
                                                <td className="py-3 px-4 font-semibold text-slate-900">{order.client_name}</td>
                                                <td className="py-3 px-4 text-slate-600">{order.client_email}</td>
                                                <td className="py-3 px-4 font-mono text-xs">{order.client_phone || '-'}</td>
                                                <td className="py-3 px-4 font-mono font-bold text-emerald-600">
                                                    {order.deposit_amount} {order.currency}
                                                </td>
                                                <td className="py-3 px-4">
                                                    {order.status === 'paid' ? (
                                                        <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200">Paid (50%)</Badge>
                                                    ) : (
                                                        <Badge variant="outline">{order.status}</Badge>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-xs text-slate-500">
                                                    {order.paid_at ? new Date(order.paid_at).toLocaleString() : '-'}
                                                </td>
                                                <td className="py-3 px-4 text-center">
                                                    {order.invoice_id && (
                                                        <Link href={`/admin/invoices/${order.invoice_id}`}>
                                                            <Button size="sm" variant="ghost" className="text-xs text-indigo-600 hover:text-indigo-700 gap-1">
                                                                <FileText className="w-3.5 h-3.5" />
                                                                {__('quotations.view_invoice')}
                                                            </Button>
                                                        </Link>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
