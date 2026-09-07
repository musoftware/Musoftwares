import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { CreditCard, Receipt, Clock, MapPin, User, FileText, Folder, ChevronDown, ChevronUp } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';

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

    const isPaid = invoice.status === 'paid';
    const isCancelled = invoice.status === 'cancelled';

    return (
        <div className="min-h-screen bg-background text-foreground pb-16 transition-colors duration-200">
            <Head title={`Invoice #${invoice.id}`} />

            {/* Top Navigation Bar with Brand & Theme Toggle */}
            <header className="border-b border-border/80 bg-card/60 backdrop-blur-md sticky top-0 z-30 mb-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2.5 group">
                        <div className="w-8 h-8 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center p-1.5 transition-transform group-hover:scale-105">
                            <ApplicationLogo className="w-full h-full object-contain" />
                        </div>
                        <span className="font-bold text-base tracking-tight text-foreground">Musoftware</span>
                    </Link>
                    <div className="flex items-center gap-3">
                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                            isPaid
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                : isCancelled
                                ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                        }`}>
                            {__('general.status_' + invoice.status)}
                        </span>
                        <ThemeToggle className="h-9 w-9" />
                    </div>
                </div>
            </header>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Header */}
                <div className="text-center">
                    <h1 className="text-3xl font-bold tracking-tight text-foreground">{__('general.invoice')} #{invoice.id}</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {__('general.issued_on')}: {new Date(invoice.created_at).toLocaleDateString()}
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Invoice Details */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="border border-border shadow-sm bg-card text-card-foreground">
                            <CardHeader>
                                <CardTitle className="text-xl flex items-center text-foreground">
                                    <FileText className="w-5 h-5 me-2 text-primary" />
                                    {__('general.invoice_items')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {itemsList.map((item: any) => (
                                        <div key={item.id} className="flex justify-between items-center py-3 border-b last:border-0 border-border">
                                            <div>
                                                <h4 className="font-medium text-foreground">{item.item_title || item.name}</h4>
                                                {item.description && <p className="text-sm text-muted-foreground">{item.description}</p>}
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    {item.quantity || 1} x {formatCurrency(item.amount || item.rate || 0, invoice.currency)}
                                                </p>
                                            </div>
                                            <div className="font-semibold text-foreground">
                                                {formatCurrency(item.total_amount || item.total || 0, invoice.currency)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                            <CardFooter className="bg-muted/30 dark:bg-slate-800/40 border-t border-border flex justify-between items-center py-4 rounded-b-xl">
                                <span className="font-semibold text-foreground">{__('general.total')}</span>
                                <span className="text-2xl font-bold text-primary dark:text-indigo-400">
                                    {formatCurrency(invoice.total || invoice.amount, invoice.currency)}
                                </span>
                            </CardFooter>
                        </Card>

                        {/* Timer Summary & Detailed Accordion */}
                        {invoice.timer_metrics && (
                            <Card className="border border-border shadow-sm bg-card text-card-foreground">
                                <CardHeader className="bg-muted/30 dark:bg-slate-800/30 pb-3 border-b border-border rounded-t-xl">
                                    <CardTitle className="text-lg flex items-center justify-between text-foreground">
                                        <span className="flex items-center font-semibold">
                                            <Clock className="w-5 h-5 me-2 text-primary dark:text-indigo-400" />
                                            {__('general.time_tracking')} &amp; {__('general.summary')}
                                        </span>
                                        <span className="font-mono text-sm font-bold bg-muted/60 dark:bg-slate-800 text-foreground px-2.5 py-1 rounded-md border border-border">
                                            {invoice.timer_metrics.total_timer_str}
                                        </span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-5 space-y-4">
                                    {/* 4 Summary Metrics Cards */}
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div className="bg-muted/30 dark:bg-slate-800/50 p-3 rounded-xl border border-border">
                                            <span className="block text-[10px] font-bold text-muted-foreground uppercase mb-1">{__('general.total_time')}</span>
                                            <span className="font-mono text-sm font-extrabold text-foreground">{invoice.timer_metrics.total_timer_str}</span>
                                        </div>
                                        <div className="bg-blue-500/10 dark:bg-blue-950/40 p-3 rounded-xl border border-blue-500/20">
                                            <span className="block text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">{__('general.full_real_value') || 'القيمة الفعلية'}</span>
                                            <span className="font-mono text-sm font-bold text-blue-700 dark:text-blue-300">{invoice.timer_metrics.full_real_value_str}</span>
                                        </div>
                                        <div className="bg-emerald-500/10 dark:bg-emerald-950/40 p-3 rounded-xl border border-emerald-500/20">
                                            <span className="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">{__('general.billed_amount') || 'المبلغ الصافي'}</span>
                                            <span className="font-mono text-sm font-bold text-emerald-700 dark:text-emerald-300">{invoice.timer_metrics.billed_amount_str}</span>
                                        </div>
                                        {invoice.timer_metrics.has_discount ? (
                                            <div className="bg-purple-500/10 dark:bg-purple-950/40 p-3 rounded-xl border border-purple-500/20">
                                                <span className="block text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase mb-1">{__('general.discount_savings') || 'إجمالي الخصم'}</span>
                                                <span className="font-mono text-sm font-bold text-purple-700 dark:text-purple-300">-{invoice.timer_metrics.discount_savings_str}</span>
                                            </div>
                                        ) : (
                                            <div className="bg-muted/30 dark:bg-slate-800/50 p-3 rounded-xl border border-border">
                                                <span className="block text-[10px] font-bold text-muted-foreground uppercase mb-1">{__('general.sessions')}</span>
                                                <span className="font-mono text-sm font-bold text-foreground">{allTimers.length}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Row 2: Average Rate Insights */}
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-1">
                                        <div className="bg-muted/30 dark:bg-slate-800/40 border border-border p-3 rounded-xl">
                                            <span className="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">{__('general.avg_billed_rate') || 'متوسط الساعة المفوترة'}</span>
                                            <span className="font-mono text-sm font-bold text-foreground">
                                                {invoice.timer_metrics.avg_billed_rate_str} <span className="text-[10px] font-normal text-muted-foreground">{__('general.per_hour') || '/ hr'}</span>
                                            </span>
                                        </div>
                                        <div className="bg-muted/30 dark:bg-slate-800/40 border border-border p-3 rounded-xl">
                                            <span className="block text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">{__('general.avg_real_rate') || 'متوسط الساعة الفعلي'}</span>
                                            <span className="font-mono text-sm font-bold text-foreground">
                                                {invoice.timer_metrics.avg_real_rate_str} <span className="text-[10px] font-normal text-muted-foreground">{__('general.per_hour') || '/ hr'}</span>
                                            </span>
                                        </div>
                                        <div className="bg-muted/30 dark:bg-slate-800/40 border border-border p-3 rounded-xl col-span-2 sm:col-span-1">
                                            <span className="block text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase mb-1">{__('general.effective_discount') || 'معدل الخصم الفعلي'}</span>
                                            <span className="font-mono text-sm font-bold text-foreground">
                                                {invoice.timer_metrics.effective_discount_percent}%
                                            </span>
                                        </div>
                                    </div>

                                    {/* Row 3: Market Rate Comparison (Optional) */}
                                    {invoice.timer_metrics.market_hourly_rate > 0 && (
                                        <div className="border-t border-dashed border-border pt-4 mt-2">
                                            <h4 className="text-xs font-bold text-muted-foreground uppercase mb-3 flex items-center gap-1.5">
                                                <span>{__('general.market_comparison')}</span>
                                            </h4>
                                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                <div className="bg-indigo-500/10 dark:bg-indigo-950/40 p-3 rounded-xl border border-indigo-500/20">
                                                    <span className="block text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase mb-1">{__('general.market_hourly_rate')}</span>
                                                    <span className="font-mono text-sm font-bold text-indigo-700 dark:text-indigo-300">
                                                        {invoice.timer_metrics.market_hourly_rate_str} <span className="text-[10px] font-normal text-muted-foreground">{__('general.per_hour')}</span>
                                                    </span>
                                                </div>
                                                <div className="bg-indigo-500/10 dark:bg-indigo-950/40 p-3 rounded-xl border border-indigo-500/20">
                                                    <span className="block text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase mb-1">{__('general.market_value')}</span>
                                                    <span className="font-mono text-sm font-bold text-indigo-700 dark:text-indigo-300">
                                                        {invoice.timer_metrics.market_value_str}
                                                    </span>
                                                </div>
                                                {invoice.timer_metrics.has_market_discount && (
                                                    <div className="bg-emerald-500/10 dark:bg-emerald-950/40 p-3 rounded-xl border border-emerald-500/20 col-span-2 sm:col-span-1">
                                                        <span className="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">{__('general.actual_savings_vs_market')}</span>
                                                        <span className="font-mono text-sm font-bold text-emerald-700 dark:text-emerald-300">
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
                                                className="w-full flex items-center justify-between border-border hover:bg-muted text-foreground text-xs font-semibold py-2.5"
                                            >
                                                <span>
                                                    {__('general.view_detailed_time_sessions') || 'عرض تفاصيل جلسات العمل المسجلة'} ({allTimers.length})
                                                </span>
                                                {isTimersExpanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                                            </Button>

                                            {/* Detailed Timers Table */}
                                            {isTimersExpanded && (
                                                <div className="mt-3 border border-border rounded-xl overflow-hidden bg-card">
                                                    <table className="w-full text-xs">
                                                        <thead className="bg-muted/50 dark:bg-slate-800/60 border-b border-border">
                                                            <tr>
                                                                <th className="px-3 py-2 text-start font-semibold text-muted-foreground">{__('general.start')}</th>
                                                                <th className="px-3 py-2 text-start font-semibold text-muted-foreground">{__('general.end') || 'End'}</th>
                                                                <th className="px-3 py-2 text-start font-semibold text-muted-foreground">{__('general.duration')}</th>
                                                                <th className="px-3 py-2 text-end font-semibold text-muted-foreground">{invoice.currency_symbol || invoice.currency}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-border">
                                                            {allTimers.map((timer: any, idx: number) => (
                                                                <tr key={timer.id || idx} className="hover:bg-muted/30 dark:hover:bg-slate-800/30 transition-colors">
                                                                    <td className="px-3 py-2 font-mono text-[11px] text-muted-foreground">{timer.date_start}</td>
                                                                    <td className="px-3 py-2 font-mono text-[11px] text-muted-foreground">{timer.date_end}</td>
                                                                    <td className="px-3 py-2 font-mono font-medium text-foreground">{timer.duration_str}</td>
                                                                    <td className="px-3 py-2 text-end font-bold text-foreground">{formatCurrency(timer.amount, invoice.currency)}</td>
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
                        <Card className="border border-border shadow-sm bg-card text-card-foreground">
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center text-foreground font-semibold">
                                    <User className="w-5 h-5 me-2 text-muted-foreground" />
                                    {__('general.client_details')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-muted-foreground">
                                <div className="flex items-center gap-2 text-foreground">
                                    <User className="w-4 h-4 text-muted-foreground" /> 
                                    <span className="font-medium">{invoice.user?.name || __('general.n_a')}</span>
                                </div>
                                {invoice.project && (
                                    <div className="flex items-center gap-2 text-foreground">
                                        <Folder className="w-4 h-4 text-muted-foreground" /> 
                                        <span className="font-medium">{invoice.project.project_name || invoice.project.name}</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Payment Section */}
                    <div>
                        <Card className="sticky top-24 border border-border shadow-sm bg-card text-card-foreground">
                            <CardHeader className="bg-primary text-white rounded-t-xl py-4">
                                <CardTitle className="flex items-center text-lg font-bold text-white">
                                    <CreditCard className="w-5 h-5 me-2" />
                                    {__('general.payment')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="mb-6 space-y-3">
                                    <div className="flex justify-between items-center text-sm">
                                        <span className="text-muted-foreground font-medium">{__('general.status')}</span>
                                        <span className={`font-semibold px-2.5 py-0.5 rounded-full text-xs ${
                                            isPaid ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 
                                            isCancelled ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                                        }`}>
                                            {__('general.status_' + invoice.status)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center text-sm border-t border-border/60 pt-3">
                                        <span className="text-muted-foreground font-medium">{__('general.unpaid')}</span>
                                        <span className="font-bold text-foreground text-base">{formatCurrency(invoice.unpaid_total || invoice.total, invoice.currency)}</span>
                                    </div>
                                </div>

                                {!isPaid && !isCancelled ? (
                                    <form onSubmit={handlePayment} className="space-y-4 border-t border-border pt-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="guest_name" className="text-foreground text-xs font-semibold">{__('general.name')}</Label>
                                            <Input
                                                id="guest_name"
                                                value={data.guest_name}
                                                onChange={e => setData('guest_name', e.target.value)}
                                                required
                                                className="bg-background text-foreground border-input focus-visible:ring-primary"
                                            />
                                            {errors.guest_name && <p className="text-rose-500 text-xs">{errors.guest_name}</p>}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="guest_email" className="text-foreground text-xs font-semibold">{__('general.email')}</Label>
                                            <Input
                                                id="guest_email"
                                                type="email"
                                                value={data.guest_email}
                                                onChange={e => setData('guest_email', e.target.value)}
                                                required
                                                className="bg-background text-foreground border-input focus-visible:ring-primary"
                                            />
                                            {errors.guest_email && <p className="text-rose-500 text-xs">{errors.guest_email}</p>}
                                        </div>

                                        <Button type="submit" className="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-2.5 shadow-sm transition-all" disabled={processing}>
                                            {processing ? __('general.processing') : __('general.pay_now')}
                                        </Button>
                                    </form>
                                ) : (
                                    <div className="p-4 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 rounded-xl border border-emerald-500/20 text-center text-sm font-medium">
                                        {isPaid ? __('general.invoice_already_paid') : __('general.invoice_cancelled')}
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
