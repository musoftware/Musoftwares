import React, { useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Download, ShoppingBag, Receipt, XCircle, Calculator } from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Subscription {
    id: number; plan_name: string; billing_cycle: string; amount_paid: number;
    currency: string; status: string; is_active: boolean;
    starts_at: string; expires_at: string | null;
    tool: { slug: string; title: string; icon_url: string | null; category: string };
}
interface Props { subscriptions: Subscription[] }

export default function Billing({ subscriptions }: Props) {
    const handleCancel = (id: number) => {
        if (!confirm('Cancel this subscription? Access continues until the end of your current period.')) return;
        router.post(route('tools.subscriptions.cancel', id), {}, { preserveScroll: true });
    };

    const statusColors: Record<string, string> = {
        active:    'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-slate-100 text-slate-500',
        expired:   'bg-red-100 text-red-600',
        suspended: 'bg-amber-100 text-amber-700',
    };

    const totalMonthly = useMemo(() => {
        return subscriptions
            .filter(sub => sub.is_active && sub.status === 'active')
            .reduce((total, sub) => {
                if (sub.billing_cycle === 'yearly') {
                    return total + (sub.amount_paid / 12);
                }
                return total + sub.amount_paid;
            }, 0);
    }, [subscriptions]);

    return (
        <ToolsPublicLayout title="Billing" activeNav="billing">
            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
                {/* Page header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{__('general.billing_subscriptions')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.manage_your_tool_subscriptions_and_billing_history')}</p>
                    </div>
                    <Button variant="outline" size="sm" className="gap-2" onClick={() => router.visit(route('tools.explore'))}>
                        <ShoppingBag className="h-4 w-4" />{__('general.browse_more_tools')}</Button>
                </div>

                {subscriptions.length > 0 && (
                    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 flex items-center justify-center">
                                <Calculator className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-slate-900 dark:text-white">{__('general.monthly_run_rate')}</p>
                                <p className="text-xs text-slate-500">{__('general.total_estimated_cost_per_month_for_active_tools')}</p>
                            </div>
                        </div>
                        <div className="text-end">
                            <p className="text-2xl font-bold text-slate-900 dark:text-white">
                                {formatMoney(totalMonthly, 'USD')}
                                <span className="text-sm font-normal text-slate-500 ms-1">/mo</span>
                            </p>
                        </div>
                    </div>
                )}

                {subscriptions.length === 0 ? (
                    <EmptyState icon={Receipt} title={__('general.no_subscriptions_yet')}
                        description={__('general.subscribe_to_a_tool_to_see_your_billing_history_here')}
                        action={{ label: 'Browse Tools', href: route('tools.explore') }} />
                ) : (
                    <section className="space-y-4">
                        <h2 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.your_subscriptions')}</h2>
                        <div className="space-y-4">
                            {subscriptions.map(sub => (
                                <div key={sub.id} className={`bg-white dark:bg-slate-900 border rounded-xl p-5 shadow-sm transition-all hover:border-slate-300 dark:hover:border-slate-700 ${sub.is_active ? 'border-slate-200 dark:border-slate-800' : 'border-slate-100 dark:border-slate-800/50'}`}>
                                    <div className="flex items-start gap-4">
                                        <div className="w-11 h-11 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                                            {sub.tool.icon_url
                                                ? <img src={sub.tool.icon_url} alt="" className="w-7 h-7 object-contain" />
                                                : <Download className="h-5 w-5 text-slate-400" />}
                                        </div>

                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="font-semibold text-slate-900 dark:text-white">{sub.tool.title}</p>
                                                    <p className="text-sm text-slate-500">{sub.plan_name} · {sub.billing_cycle}</p>
                                                </div>
                                                <div className="text-end flex-shrink-0">
                                                    <Badge className={`${statusColors[sub.status] ?? ''} hover:${statusColors[sub.status]}`}>{sub.status}</Badge>
                                                    <p className="text-lg font-bold text-slate-900 dark:text-white mt-1">{formatMoney(sub.amount_paid, sub.currency)}</p>
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap gap-x-6 gap-y-1 mt-3 text-xs text-slate-500">
                                                <span>Started: {sub.starts_at}</span>
                                                {sub.expires_at && <span>Expires: {sub.expires_at}</span>}
                                            </div>
                                        </div>
                                    </div>

                                    {sub.is_active && (
                                        <div className="flex gap-3 mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
                                            <Button variant="outline" size="sm" className="gap-2 h-8"
                                                onClick={() => router.visit(route('tools.download.generate', sub.tool.slug))}>
                                                <Download className="h-3.5 w-3.5" /> Download
                                            </Button>
                                            <Button variant="outline" size="sm" className="gap-2 text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300 dark:border-red-900/30 dark:hover:bg-red-900/10 h-8"
                                                onClick={() => handleCancel(sub.id)}>
                                                <XCircle className="h-3.5 w-3.5" /> Cancel
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </ToolsPublicLayout>
    );
}

