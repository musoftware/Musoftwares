import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Calendar, Clock, DollarSign, List, History, AlertCircle, Edit, Trash2, Play } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function View({ cost, transactions, upcomingSchedule, total_stat }) {
    const [activeTab, setActiveTab] = useState<'history' | 'schedule'>('history');
    const [generating, setGenerating] = useState(false);

    const handleGenerateMissing = () => {
        if (confirm(__('general.confirm_generate_missing') || 'Are you sure you want to generate all missing past transactions up to today for this schedule?')) {
            setGenerating(true);
            router.post(route('admin.recurring_costs.generate_missing', cost.id), {}, {
                preserveScroll: true,
                onFinish: () => setGenerating(false),
            });
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this recurring cost?')) {
            router.delete(route('admin.recurring_costs.delete', cost.id));
        }
    };

    const handleDeleteWithTransactions = () => {
        if (confirm('Are you sure you want to delete this recurring cost AND all its generated transactions? This cannot be undone.')) {
            router.delete(route('admin.recurring_costs.delete_with_transaction', cost.id));
        }
    };

    return (
        <AdminSidebarLayout title={`${cost.title} - Recurring Details`} header="Business Operations">
            <Head title={`View Recurring Cost - ${cost.title}`} />

            <div className="mb-4 flex justify-between items-center">
                <Link href={route('admin.recurring_costs.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_costs')}</Link>
                <div className="flex items-center gap-2">
                    <Button variant="default" size="sm" className="bg-slate-900 hover:bg-slate-800 text-white flex items-center gap-1.5 shadow-sm" onClick={handleGenerateMissing} disabled={generating}>
                        <Play className="w-3.5 h-3.5 fill-current" />
                        {generating ? (__('general.loading') || 'Generating...') : (__('general.generate_missing_transactions') || 'Generate Missing')}
                    </Button>
                    <Link href={route('admin.recurring_costs.edit', cost.id)}>
                        <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                            <Edit className="w-4 h-4" /> {__('general.edit')}</Button>
                    </Link>
                    <Button variant="outline" size="sm" className="text-yellow-600 hover:text-yellow-900 flex items-center gap-1.5" onClick={handleDelete}>
                        <Trash2 className="w-4 h-4" />{__('general.delete_schedule')}</Button>
                    <Button variant="destructive" size="sm" className="flex items-center gap-1.5" onClick={handleDeleteWithTransactions}>
                        <Trash2 className="w-4 h-4" />{__('general.delete_all')}</Button>
                </div>
            </div>

            {/* Overview Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {/* Cost Details Card */}
                <div className="bg-white p-6 rounded-xl border shadow-sm md:col-span-2">
                    <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.recurrence_overview')}</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.description')}</span>
                            <span className="text-sm font-medium text-slate-800">{cost.title}</span>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.amount')}</span>
                            <span className="text-sm font-bold text-slate-900">{formatCurrency(cost.amount, cost.currency)}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.recurrence_pattern')}</span>
                            <span className="text-sm font-medium text-slate-800 capitalize">{cost.details}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.category_reason')}</span>
                            <span className="text-sm font-medium text-slate-800 bg-slate-100 border px-2 py-0.5 rounded inline-block mt-0.5">{cost.reason || 'None'}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.start_date')}</span>
                            <span className="text-sm font-medium text-slate-800">{new Date(cost.start_date).toLocaleDateString()}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.next_execution_date')}</span>
                            <span className="text-sm font-medium text-slate-800 flex items-center gap-1 mt-0.5">
                                <Clock className="w-4 h-4 text-slate-500" /> {cost.next_date ? new Date(cost.next_date).toLocaleDateString() : (cost.current_date ? new Date(cost.current_date).toLocaleDateString() : '—')}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Performance Stats Card */}
                <div className="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.ledger_stats')}</h3>
                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-gray-500">Total Runs Executed:</span>
                                <span className="text-sm font-bold text-slate-900">{total_stat.entries_count} times</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-gray-500">Cumulative Cost:</span>
                                <span className="text-sm font-bold text-red-600 bg-red-50 px-2 py-0.5 border border-red-100 rounded">
                                    {total_stat.total_cost}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="mt-4 pt-4 border-t text-xs text-gray-400 flex items-start gap-1">
                        <AlertCircle className="w-4 h-4 text-gray-400 shrink-0" />
                        <span>{__('general.the_cumulative_cost_is_calculated_based_on_generated_cost_transactions_associated_with_this_schedule')}</span>
                    </div>
                </div>
            </div>

            {/* Tabs & Tab Contents */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <div className="flex border-b bg-slate-50">
                    <button
                        className={`flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition-all ${
                            activeTab === 'history'
                                ? 'border-black text-black bg-white font-semibold'
                                : 'border-transparent text-gray-500 hover:text-black'
                        }`}
                        onClick={() => setActiveTab('history')}
                    >
                        <History className="w-4 h-4" /> Generated Transactions ({transactions.length})
                    </button>
                    <button
                        className={`flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition-all ${
                            activeTab === 'schedule'
                                ? 'border-black text-black bg-white font-semibold'
                                : 'border-transparent text-gray-500 hover:text-black'
                        }`}
                        onClick={() => setActiveTab('schedule')}
                    >
                        <Calendar className="w-4 h-4" />{__('general.next_15_scheduled_runs')}</button>
                </div>

                <div className="p-6">
                    {activeTab === 'history' && (
                        <div>
                            {transactions.length === 0 ? (
                                <div className="text-center py-8 text-gray-500">
                                    <List className="w-8 h-8 mx-auto text-gray-300 mb-2" />
                                    <p className="text-sm">{__('general.no_transactions_have_been_recorded_yet_for_this_recurring_cost')}</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.date_recorded')}</th>
                                                <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.transaction_id')}</th>
                                                <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.reason')}</th>
                                                <th className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200">
                                            {transactions.map((tx: any) => (
                                                <tr key={tx.id} className="hover:bg-slate-50">
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                                        {new Date(tx.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm font-mono text-slate-500">
                                                        #{tx.id}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-900">
                                                        {tx.reason}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm font-bold text-red-600 text-end">
                                                        -{formatCurrency(tx.amount, tx.currency)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'schedule' && (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.projected_date')}</th>
                                        <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.status')}</th>
                                        <th className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {upcomingSchedule.map((run: any, idx: number) => (
                                        <tr key={idx} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-800">
                                                {new Date(run.date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                {run.recorded ? (
                                                    <span className="text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 px-2 py-0.5 rounded">Recorded (Historical)</span>
                                                ) : (
                                                    <span className="text-xs bg-green-50 text-slate-900 border border-green-200 px-2 py-0.5 rounded font-medium">{__('general.pending_execution')}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-end">
                                                <span className={`text-sm font-bold ${run.recorded ? 'text-slate-900' : 'text-slate-400'}`}>
                                                    {run.amount_str}
                                                </span>
                                                <span className="block text-xs mt-0.5 text-gray-400">
                                                    {run.is_actual ? __('general.actual_amount') : __('general.estimated_amount')}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
