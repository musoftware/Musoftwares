import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Calendar, Clock, User, List, History, AlertCircle, Edit, Trash2 } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';

export default function View({ salary, transactions, upcomingSchedule, total_stat }) {
    const [activeTab, setActiveTab] = useState<'history' | 'schedule'>('history');

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this recurring salary?')) {
            router.delete(route('admin.recurring_salaries.delete', salary.id));
        }
    };

    return (
        <AdminSidebarLayout title={`${salary.user?.name || 'Employee'} - Payroll Details`} header="Business Operations">
            <Head title={`View Recurring Salary - ${salary.user?.name || 'Employee'}`} />

            <div className="mb-4 flex justify-between items-center">
                <Link href={route('admin.recurring_salaries.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_salaries')}</Link>
                <div className="flex gap-2">
                    <Link href={route('admin.recurring_salaries.edit', salary.id)}>
                        <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                            <Edit className="w-4 h-4" /> Edit
                        </Button>
                    </Link>
                    <Button variant="destructive" size="sm" className="flex items-center gap-1.5" onClick={handleDelete}>
                        <Trash2 className="w-4 h-4" />{__('general.delete_schedule')}</Button>
                </div>
            </div>

            {/* Overview Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {/* Salary Details Card */}
                <div className="bg-white p-6 rounded-xl border shadow-sm md:col-span-2">
                    <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.recurrence_overview')}</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Employee</span>
                            <div className="flex items-center mt-1">
                                <div className="bg-slate-100 p-1.5 rounded-full mr-2 text-slate-650 border">
                                    <User className="w-4 h-4" />
                                </div>
                                <div>
                                    <span className="text-sm font-semibold text-slate-800 block">{salary.user?.name || 'Unknown Employee'}</span>
                                    <span className="text-xs text-gray-500 block">{salary.user?.email || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.salary_rate')}</span>
                            <span className="text-sm font-bold text-slate-900 block mt-1">
                                {formatCurrency(salary.amount, salary.currency)}
                            </span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.title_description')}</span>
                            <span className="text-sm font-medium text-slate-800">{salary.title}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.recurrence_pattern')}</span>
                            <span className="text-sm font-medium text-slate-800 capitalize">{salary.details}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.note_reason')}</span>
                            <span className="text-sm font-medium text-slate-700 italic block mt-0.5">{salary.reason || 'None'}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.start_date')}</span>
                            <span className="text-sm font-medium text-slate-800 block mt-0.5">{new Date(salary.start_date).toLocaleDateString()}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.next_execution_date')}</span>
                            <span className="text-sm font-medium text-slate-800 flex items-center gap-1 mt-1">
                                <Clock className="w-4 h-4 text-slate-500" /> {new Date(salary.current_date).toLocaleDateString()}
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
                                    {total_stat.total_cost}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="mt-4 pt-4 border-t text-xs text-gray-400 flex items-start gap-1">
                        <AlertCircle className="w-4 h-4 text-gray-400 shrink-0" />
                        <span>{__('general.cumulative_paid_represents_the_total_amount_generated_in_transaction_records_for_this_employee')}</span>
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
                        <History className="w-4 h-4" /> Payroll History ({transactions.length})
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
                                    <p className="text-sm">{__('general.no_payroll_transactions_have_been_recorded_yet_for_this_salary_schedule')}</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.date_recorded')}</th>
                                                <th className="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.transaction_id')}</th>
                                                <th className="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                                                <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
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
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm font-bold text-red-600 text-right">
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
                                        <th className="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.projected_date')}</th>
                                        <th className="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th className="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.estimated_amount')}</th>
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
                                                    <span className="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded">Recorded (Historical)</span>
                                                ) : (
                                                    <span className="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded font-medium">{__('general.pending_execution')}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm font-bold text-slate-700 text-right">
                                                {run.amount_str}
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
