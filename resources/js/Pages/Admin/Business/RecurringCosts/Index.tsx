import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Trash2, Edit, Plus, DollarSign, TrendingDown, Clock, Search, X, Calendar, ArrowLeft, Eye } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const COLORS = ['#0f172a', '#334155', '#475569', '#64748b', '#94a3b8', '#cbd5e1'];

export default function Index({ costs, currencies, categories, stats }) {
    const { errors } = usePage().props;
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const categoriesList = Array.isArray(categories) ? categories : (categories ? Object.values(categories) : []);



    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this recurring cost?')) {
            router.delete(route('admin.recurring_costs.delete', id));
        }
    };

    const handleDeleteWithTransactions = (id) => {
        if (confirm('Are you sure you want to delete this recurring cost AND all its generated transactions? This cannot be undone.')) {
            router.delete(route('admin.recurring_costs.delete_with_transaction', id));
        }
    };

    const formatSchedule = (cost) => {
        let scheduleStr = `Every ${cost.recurring_times} ${cost.recurring}(s)`;
        if (cost.recurring === 'week' && cost.recurring_times_week) {
            scheduleStr += ` on [${cost.recurring_times_week}]`;
        } else if (cost.recurring === 'month' && cost.recurring_times_month) {
            scheduleStr += ` on day [${cost.recurring_times_month}]`;
        } else if (cost.recurring === 'year' && cost.recurring_times_year) {
            scheduleStr += ` on [${cost.recurring_times_year}]`;
        }
        return scheduleStr;
    };



    return (
        <AdminSidebarLayout title={__('general.recurring_costs')} header="Business Operations">
            <Head title={__('general.admin_recurring_costs')} />

            <div className="mb-4">
                <Link href={route('admin.finance.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_financial_ledger')}</Link>
            </div>

            {/* Top Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full mr-4 text-slate-800 border">
                        <DollarSign className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.estimated_monthly_overheads')}</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.monthly_total}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full mr-4 text-slate-800 border">
                        <TrendingDown className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.estimated_annual_overheads')}</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.annual_total}</h3>
                    </div>
                </div>
            </div>

            {/* Pie Charts */}
            {stats.chart_data && stats.chart_data.length > 0 && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div className="bg-white p-6 rounded-xl border shadow-sm">
                        <h3 className="text-sm text-gray-500 font-medium uppercase tracking-wider mb-4">{__('general.monthly_breakdown')}</h3>
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={stats.chart_data}
                                        dataKey="monthly"
                                        nameKey="name"
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={60}
                                        outerRadius={80}
                                        paddingAngle={2}
                                    >
                                        {stats.chart_data.map((entry: any, index: number) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip 
                                        formatter={(value) => formatCurrency(value, stats.business_currency_code)}
                                        contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1)' }}
                                    />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-xl border shadow-sm">
                        <h3 className="text-sm text-gray-500 font-medium uppercase tracking-wider mb-4">{__('general.annual_breakdown')}</h3>
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={stats.chart_data}
                                        dataKey="annual"
                                        nameKey="name"
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={60}
                                        outerRadius={80}
                                        paddingAngle={2}
                                    >
                                        {stats.chart_data.map((entry: any, index: number) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip 
                                        formatter={(value) => formatCurrency(value, stats.business_currency_code)}
                                        contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1)' }}
                                    />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>
            )}

            {/* Title & Actions Bar */}
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-xl font-bold text-slate-900">{__('general.active_recurring_costs')}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_repeated_automated_business_expenses_and_schedules')}</p>
                </div>

                <Link href={route('admin.recurring_costs.create')}>
                    <Button className="bg-black hover:bg-slate-800 text-white h-9">
                        <Plus className="w-4 h-4 mr-2" />{__('general.add_recurring_cost')}</Button>
                </Link>
            </div>

            {/* Data Table */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.title_schedule')}</th>
                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.start_date')}</th>
                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Category
                            </th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Amount
                            </th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Transactions
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {(costs.data as any).map((cost) => (
                            <tr key={cost.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="text-sm font-semibold text-gray-900">{cost.title}</div>
                                    <div className="text-xs text-gray-500 mt-1 flex items-center gap-1"><Clock className="w-3 h-3"/> {formatSchedule(cost)}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{new Date(cost.start_date).toLocaleDateString()}</div>
                                    <div className="text-xs text-gray-500 mt-0.5">Next: {cost.current_date}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="bg-slate-100 border text-slate-800 text-xs px-2 py-0.5 rounded font-medium">{cost.reason}</span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-sm font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded">
                                        {formatCurrency(cost.amount, cost.currency)}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-xs font-medium text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">{__('general.active_log')}</span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link href={route('admin.recurring_costs.view', cost.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black mr-1" title={__('general.view_details')}>
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Link href={route('admin.recurring_costs.edit', cost.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black mr-1" title="Edit">
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" className="text-orange-600 hover:text-orange-900 mr-1" onClick={() => handleDelete(cost.id)} title={__('general.delete_schedule_only')}>
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-900" onClick={() => handleDeleteWithTransactions(cost.id)} title={__('general.delete_everything')}>
                                        <Trash2 className="w-4 h-4 border border-red-200 rounded p-0.5" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {(costs.data as any).length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                    <Calendar className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    <h3 className="text-lg font-medium text-gray-900">{__('general.no_recurring_costs_found')}</h3>
                                    <p className="mt-1">{__('general.add_a_new_schedule_to_start_managing_automated_overheads')}</p>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {costs.links && costs.links.length > 3 && (
                <div className="flex justify-between items-center mt-6">
                    <div className="text-sm text-gray-500">
                        Showing {costs.from} to {costs.to} of {costs.total} entries
                    </div>
                    <div className="flex space-x-1">
                        {costs.links.map((link, idx) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                className={`px-3 py-2 border rounded text-sm ${link.active ? 'bg-black text-white border-black font-semibold' : 'bg-white text-gray-700 hover:bg-gray-50'} ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}
