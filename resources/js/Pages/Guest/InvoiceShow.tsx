import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { CreditCard, Receipt, Clock, MapPin, User, FileText, Folder, ChevronDown, ChevronUp } from 'lucide-react';
import { useForm } from '@inertiajs/react';

export default function InvoiceShow({ invoice, pay_url }: { invoice: any, pay_url: string }) {
    const { data, setData, post, processing, errors } = useForm({
        guest_name: invoice.user?.name || '',
        guest_email: invoice.user?.email || '',
    });

    const [isTimersExpanded, setIsTimersExpanded] = useState(false);

    const handlePayment = (e: React.FormEvent) => {
        e.preventDefault();
        post(pay_url);
    };

    const itemsList = Array.isArray(invoice.items)
        ? invoice.items
        : (Array.isArray(invoice.items?.data) ? invoice.items.data : []);

    const allTimers = itemsList.flatMap((item: any) => {
        const timersList = Array.isArray(item.timers)
            ? item.timers
            : (Array.isArray(item.timers?.data) ? item.timers.data : []);
        return timersList.map((t: any) => ({
            ...t,
            item_title: item.item_title || item.name || __('general.time_tracking'),
        }));
    });

    return (
        <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            <Head title={`Invoice #${invoice.id}`} />

            <div className="max-w-7xl mx-auto space-y-8">
                {/* Header */}
                <div className="text-center">
                    <h1 className="text-3xl font-bold text-gray-900">{__('general.invoice')} #{invoice.id}</h1>
                    <p className="mt-2 text-sm text-gray-500">
                        {__('general.issued_on')}: {new Date(invoice.created_at).toLocaleDateString()}
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Invoice Details */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl flex items-center">
                                    <FileText className="w-5 h-5 me-2 text-blue-600" />
                                    {__('general.invoice_items')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {itemsList.map((item: any) => (
                                        <div key={item.id} className="flex justify-between items-center py-3 border-b last:border-0 border-gray-100">
                                            <div>
                                                <h4 className="font-medium text-gray-900">{item.item_title || item.name}</h4>
                                                {item.description && <p className="text-sm text-gray-500">{item.description}</p>}
                                                <p className="text-xs text-gray-400 mt-1">
                                                    {item.quantity || 1} x {formatCurrency(item.amount || item.rate || 0, invoice.currency)}
                                                </p>
                                            </div>
                                            <div className="font-semibold text-gray-900">
                                                {formatCurrency(item.total_amount || item.total || 0, invoice.currency)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                            <CardFooter className="bg-gray-50 border-t flex justify-between items-center py-4">
                                <span className="font-medium text-gray-700">{__('general.total')}</span>
                                <span className="text-2xl font-bold text-blue-600">
                                    {formatCurrency(invoice.total || invoice.amount, invoice.currency)}
                                </span>
                            </CardFooter>
                        </Card>

                        {/* Timer Summary & Detailed Accordion */}
                        {invoice.timer_metrics && (
                            <Card className="border-gray-200 shadow-sm">
                                <CardHeader className="bg-gray-50/50 pb-3 border-b border-gray-100">
                                    <CardTitle className="text-lg flex items-center justify-between text-gray-800">
                                        <span className="flex items-center">
                                            <Clock className="w-5 h-5 me-2 text-slate-700" />
                                            {__('general.time_tracking')} & {__('general.summary')}
                                        </span>
                                        <span className="font-mono text-sm font-bold bg-slate-100 text-slate-800 px-2.5 py-1 rounded-md">
                                            {invoice.timer_metrics.total_timer_str}
                                        </span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-5 space-y-4">
                                    {/* 4 Summary Metrics Cards */}
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div className="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                            <span className="block text-[10px] font-bold text-gray-500 uppercase mb-1">{__('general.total_time')}</span>
                                            <span className="font-mono text-sm font-extrabold text-slate-900">{invoice.timer_metrics.total_timer_str}</span>
                                        </div>
                                        <div className="bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                                            <span className="block text-[10px] font-bold text-blue-600 uppercase mb-1">{__('general.full_real_value') || 'القيمة الفعلية'}</span>
                                            <span className="font-mono text-sm font-bold text-blue-700">{invoice.timer_metrics.full_real_value_str}</span>
                                        </div>
                                        <div className="bg-emerald-50/50 p-3 rounded-xl border border-emerald-100">
                                            <span className="block text-[10px] font-bold text-emerald-600 uppercase mb-1">{__('general.billed_amount') || 'المبلغ الصافي'}</span>
                                            <span className="font-mono text-sm font-bold text-emerald-700">{invoice.timer_metrics.billed_amount_str}</span>
                                        </div>
                                        {invoice.timer_metrics.has_discount ? (
                                            <div className="bg-purple-50/50 p-3 rounded-xl border border-purple-100">
                                                <span className="block text-[10px] font-bold text-purple-600 uppercase mb-1">{__('general.discount_savings') || 'إجمالي الخصم'}</span>
                                                <span className="font-mono text-sm font-bold text-purple-700">-{invoice.timer_metrics.discount_savings_str}</span>
                                            </div>
                                        ) : (
                                            <div className="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                                <span className="block text-[10px] font-bold text-gray-500 uppercase mb-1">{__('general.sessions')}</span>
                                                <span className="font-mono text-sm font-bold text-gray-800">{allTimers.length}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Row 2: Average Rate Insights */}
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-1">
                                        <div className="bg-slate-900 text-white p-3 rounded-xl">
                                            <span className="block text-[10px] font-bold text-emerald-400 uppercase mb-1">{__('general.avg_billed_rate') || 'متوسط الساعة المفوترة'}</span>
                                            <span className="font-mono text-sm font-bold text-emerald-300">
                                                {invoice.timer_metrics.avg_billed_rate_str} <span className="text-[10px] font-normal text-emerald-400">{__('general.per_hour') || '/ hr'}</span>
                                            </span>
                                        </div>
                                        <div className="bg-slate-900 text-white p-3 rounded-xl">
                                            <span className="block text-[10px] font-bold text-blue-400 uppercase mb-1">{__('general.avg_real_rate') || 'متوسط الساعة الفعلي'}</span>
                                            <span className="font-mono text-sm font-bold text-blue-300">
                                                {invoice.timer_metrics.avg_real_rate_str} <span className="text-[10px] font-normal text-blue-400">{__('general.per_hour') || '/ hr'}</span>
                                            </span>
                                        </div>
                                        <div className="bg-slate-900 text-white p-3 rounded-xl col-span-2 sm:col-span-1">
                                            <span className="block text-[10px] font-bold text-purple-400 uppercase mb-1">{__('general.effective_discount') || 'معدل الخصم الفعلي'}</span>
                                            <span className="font-mono text-sm font-bold text-purple-300">
                                                {invoice.timer_metrics.effective_discount_percent}%
                                            </span>
                                        </div>
                                    </div>

                                    {/* Row 3: Market Rate Comparison (Optional) */}
                                    {invoice.timer_metrics.market_hourly_rate > 0 && (
                                        <div className="border-t border-dashed border-gray-200 pt-4 mt-2">
                                            <h4 className="text-xs font-bold text-gray-500 uppercase mb-3 flex items-center gap-1.5">
                                                <span>{__('general.market_comparison')}</span>
                                            </h4>
                                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                <div className="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100">
                                                    <span className="block text-[10px] font-bold text-indigo-600 uppercase mb-1">{__('general.market_hourly_rate')}</span>
                                                    <span className="font-mono text-sm font-bold text-indigo-700">
                                                        {invoice.timer_metrics.market_hourly_rate_str} <span className="text-[10px] font-normal text-indigo-500">{__('general.per_hour')}</span>
                                                    </span>
                                                </div>
                                                <div className="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100">
                                                    <span className="block text-[10px] font-bold text-indigo-600 uppercase mb-1">{__('general.market_value')}</span>
                                                    <span className="font-mono text-sm font-bold text-indigo-700">
                                                        {invoice.timer_metrics.market_value_str}
                                                    </span>
                                                </div>
                                                {invoice.timer_metrics.has_market_discount && (
                                                    <div className="bg-emerald-50 text-emerald-800 p-3 rounded-xl border border-emerald-100 col-span-2 sm:col-span-1">
                                                        <span className="block text-[10px] font-bold text-emerald-600 uppercase mb-1">{__('general.actual_savings_vs_market')}</span>
                                                        <span className="font-mono text-sm font-bold text-emerald-700">
                                                            {invoice.timer_metrics.market_discount_savings_str} ({invoice.timer_metrics.market_discount_percent}%)
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {/* Expandable Accordion Button */}
                                    {allTimers.length > 0 && (
                                        <div className="pt-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => setIsTimersExpanded(!isTimersExpanded)}
                                                className="w-full flex items-center justify-between border-gray-200 hover:bg-gray-50 text-slate-800 text-xs font-semibold py-2.5"
                                            >
                                                <span>
                                                    {__('general.view_detailed_time_sessions') || 'عرض تفاصيل جلسات العمل المسجلة'} ({allTimers.length})
                                                </span>
                                                {isTimersExpanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                                            </Button>

                                            {/* Detailed Timers Table */}
                                            {isTimersExpanded && (
                                                <div className="mt-3 border rounded-xl overflow-hidden">
                                                    <table className="w-full text-xs">
                                                        <thead className="bg-gray-50 border-b border-gray-200">
                                                            <tr>
                                                                <th className="px-3 py-2 text-start font-semibold text-gray-600">{__('general.start')}</th>
                                                                <th className="px-3 py-2 text-start font-semibold text-gray-600">End</th>
                                                                <th className="px-3 py-2 text-start font-semibold text-gray-600">{__('general.duration')}</th>
                                                                <th className="px-3 py-2 text-end font-semibold text-gray-600">{invoice.currency_symbol || invoice.currency}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-gray-100">
                                                            {allTimers.map((timer: any, idx: number) => (
                                                                <tr key={timer.id || idx} className="hover:bg-gray-50/50">
                                                                    <td className="px-3 py-2 font-mono text-[11px] text-gray-600">{timer.date_start}</td>
                                                                    <td className="px-3 py-2 font-mono text-[11px] text-gray-600">{timer.date_end}</td>
                                                                    <td className="px-3 py-2 font-mono font-medium text-gray-900">{timer.duration_str}</td>
                                                                    <td className="px-3 py-2 text-end font-bold text-gray-900">{formatCurrency(timer.amount, invoice.currency)}</td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Client Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center">
                                    <User className="w-5 h-5 me-2 text-gray-400" />
                                    {__('general.client_details')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-gray-600">
                                <div className="flex items-center gap-2">
                                    <User className="w-4 h-4" /> 
                                    <span>{invoice.user?.name || __('general.n_a')}</span>
                                </div>
                                {invoice.project && (
                                    <div className="flex items-center gap-2">
                                        <Folder className="w-4 h-4" /> 
                                        <span>{invoice.project.name}</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Payment Section */}
                    <div>
                        <Card className="sticky top-6">
                            <CardHeader className="bg-blue-600 text-white rounded-t-xl">
                                <CardTitle className="flex items-center text-lg">
                                    <CreditCard className="w-5 h-5 me-2" />
                                    {__('general.payment')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="mb-6 space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{__('general.status')}</span>
                                        <span className={`font-semibold ${
                                            invoice.status === 'paid' ? 'text-green-600' : 
                                            invoice.status === 'cancelled' ? 'text-red-600' : 'text-orange-500'
                                        }`}>
                                            {__('general.status_' + invoice.status)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{__('general.unpaid')}</span>
                                        <span className="font-bold text-gray-900">{formatCurrency(invoice.unpaid_total || invoice.total, invoice.currency)}</span>
                                    </div>
                                </div>

                                {invoice.status !== 'paid' && invoice.status !== 'cancelled' ? (
                                    <form onSubmit={handlePayment} className="space-y-4 border-t pt-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="guest_name">{__('general.name')}</Label>
                                            <Input
                                                id="guest_name"
                                                value={data.guest_name}
                                                onChange={e => setData('guest_name', e.target.value)}
                                                required
                                            />
                                            {errors.guest_name && <p className="text-red-500 text-xs">{errors.guest_name}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="guest_email">{__('general.email')}</Label>
                                            <Input
                                                id="guest_email"
                                                type="email"
                                                value={data.guest_email}
                                                onChange={e => setData('guest_email', e.target.value)}
                                                required
                                            />
                                            {errors.guest_email && <p className="text-red-500 text-xs">{errors.guest_email}</p>}
                                        </div>

                                        <Button type="submit" className="w-full bg-blue-600 hover:bg-blue-700" disabled={processing}>
                                            {processing ? __('general.processing') : __('general.pay_now')}
                                        </Button>
                                    </form>
                                ) : (
                                    <div className="p-4 bg-green-50 text-green-700 rounded-md border border-green-100 text-center text-sm font-medium">
                                        {invoice.status === 'paid' ? __('general.invoice_already_paid') : __('general.invoice_cancelled')}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    );
}
