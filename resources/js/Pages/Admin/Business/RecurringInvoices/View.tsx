import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Calendar, Clock, User, List, History, AlertCircle, Edit, Trash2, X } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function View({ invoice, records, transactions, upcomingSchedule, total_stat }) {
    const historyItems = records || transactions || [];
    const scheduleItems = Array.isArray(upcomingSchedule) ? upcomingSchedule : [];
    const [activeTab, setActiveTab] = useState<'history' | 'schedule'>('history');

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this Recurring Invoice?')) {
            router.delete(route('admin.recurring_invoices.delete', invoice.id));
        }
    };

    const handleRemoveRecord = (recordId: number) => {
        if (confirm(__('general.confirm_remove_transaction') || 'Are you sure you want to remove this transaction?')) {
            router.delete(route('admin.recurring_invoices.records.delete', { invoice: invoice.id, record: recordId }), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminSidebarLayout title={`${invoice.user?.name || 'user'} - Payroll Details`} header="Business Operations">
            <Head title={`View Recurring Invoice - ${invoice.user?.name || 'user'}`} />

            <div className="mb-4 flex justify-between items-center">
                <Link href={route('admin.recurring_invoices.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_invoices')}</Link>
                <div className="flex gap-2">
                    <Link href={route('admin.recurring_invoices.edit', invoice.id)}>
                        <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                            <Edit className="w-4 h-4" /> {__('general.edit')}</Button>
                    </Link>
                    <Button variant="destructive" size="sm" className="flex items-center gap-1.5" onClick={handleDelete}>
                        <Trash2 className="w-4 h-4" />{__('general.delete_schedule')}</Button>
                </div>
            </div>

            {/* Overview Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {/* invoice Details Card */}
                <div className="bg-white p-6 rounded-xl border shadow-sm md:col-span-2">
                    <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.recurrence_overview')}</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.user')}</span>
                            <div className="flex items-center mt-1">
                                <div className="bg-slate-100 p-1.5 rounded-full me-2 text-slate-650 border">
                                    <User className="w-4 h-4" />
                                </div>
                                <div>
                                    <span className="text-sm font-semibold text-slate-800 block">{invoice.user?.name || 'Unknown user'}</span>
                                    <span className="text-xs text-gray-500 block">{invoice.user?.email || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.salary_rate')}</span>
                            <span className="text-sm font-bold text-slate-900 block mt-1">
                                {formatCurrency(invoice.amount, invoice.currency)}
                            </span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.title_description')}</span>
                            <span className="text-sm font-medium text-slate-800">{invoice.title}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.recurrence_pattern')}</span>
                            <span className="text-sm font-medium text-slate-800 capitalize">{invoice.details}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.note_reason')}</span>
                            <span className="text-sm font-medium text-slate-700 italic block mt-0.5">{invoice.reason || 'None'}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.start_date')}</span>
                            <span className="text-sm font-medium text-slate-800 block mt-0.5">{new Date(invoice.start_date).toLocaleDateString()}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.next_execution_date')}</span>
                            <span className="text-sm font-medium text-slate-800 flex items-center gap-1 mt-1">
                                <Clock className="w-4 h-4 text-slate-500" /> {new Date(invoice.current_date).toLocaleDateString()}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Performance Stats Card */}
                <div className="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.payroll_stats')}</h3>
                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-gray-500">Payruns Executed:</span>
                                <span className="text-sm font-bold text-slate-900">{total_stat.entries_count} times</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-gray-500">Cumulative Paid:</span>
                                <span className="text-sm font-bold text-red-650 bg-red-50 px-2 py-0.5 border border-red-100 rounded">
                                    {total_stat?.total_cost ?? total_stat?.cumulative_paid ?? '—'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="mt-4 pt-4 border-t text-xs text-gray-400 flex items-start gap-1">
                        <AlertCircle className="w-4 h-4 text-gray-400 shrink-0" />
                        <span>{__('general.cumulative_paid_represents_the_total_amount_generated_in_transaction_records_for_this_user')}</span>
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
                        <History className="w-4 h-4" /> Payroll History ({historyItems.length})
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
                            {historyItems.length === 0 ? (
                                <div className="text-center py-8 text-gray-500">
                                    <List className="w-8 h-8 mx-auto text-gray-300 mb-2" />
                                    <p className="text-sm">{__('general.no_payroll_transactions_have_been_recorded_yet_for_this_salary_schedule')}</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.date_recorded')}</th>
                                                <th className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.transaction_id')}</th>
                                                <th className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</th>
                                                <th className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.actions') || 'Actions'}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200">
                                            {historyItems.map((tx: any) => (
                                                <tr key={tx.id} className="hover:bg-slate-50">
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                                        {new Date(tx.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm font-mono text-slate-500">
                                                        #{tx.id}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm font-bold text-red-600 text-end">
                                                        -{formatCurrency(tx.amount, tx.currency)}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-end">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleRemoveRecord(tx.id)}
                                                            className="inline-flex items-center justify-center h-8 w-8 rounded-md text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors"
                                                            aria-label={__('general.remove_transaction') || 'Remove transaction'}
                                                            title={__('general.remove_transaction') || 'Remove'}
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </button>
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
                                    {scheduleItems.map((run: any, idx: number) => (
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
