import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Trash2, Edit, Plus, DollarSign, TrendingUp, Clock, Calendar, ArrowLeft, Eye, Power } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { __ } from '@/lib/i18n';

export default function Index({ incomes, currencies, categories, stats }) {
    const { errors } = usePage().props;
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const categoriesList = Array.isArray(categories) ? categories : (categories ? Object.values(categories) : []);

    // Dialog State
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [createReasonOption, setCreateReasonOption] = useState(categoriesList.length > 0 ? categoriesList[0] : 'custom');

    const defaultCurrencyId = currenciesList.find(c => c.currency === stats.business_currency_code)?.id || (currenciesList[0]?.id || '');

    const [newIncome, setNewIncome] = useState({
        title: '',
        amount: '',
        currency: defaultCurrencyId,
        reason_choice: categoriesList.length > 0 ? categoriesList[0] : 'custom',
        custom_reason: '',
        start_date: new Date().toISOString().slice(0, 10),
        recurring: 'month',
        recurring_times: 1,
        recurring_times_week: [] as string[],
        recurring_times_month: [] as string[],
        recurring_times_year: [] as string[],
    });

    const handleCreate = (e) => {
        e.preventDefault();
        router.post(route('admin.recurring_income.store'), {
            ...newIncome,
            currency: parseInt(newIncome.currency as string) || newIncome.currency
        }, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewIncome({
                    title: '',
                    amount: '',
                    currency: defaultCurrencyId,
                    reason_choice: categoriesList.length > 0 ? categoriesList[0] : 'custom',
                    custom_reason: '',
                    start_date: new Date().toISOString().slice(0, 10),
                    recurring: 'month',
                    recurring_times: 1,
                    recurring_times_week: [],
                    recurring_times_month: [],
                    recurring_times_year: [],
                });
                setCreateReasonOption(categoriesList.length > 0 ? categoriesList[0] : 'custom');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this recurring income?')) {
            router.delete(route('admin.recurring_income.delete', id));
        }
    };

    const handleToggleActive = (id) => {
        router.post(route('admin.recurring_income.toggle', id), {}, {
            preserveScroll: true
        });
    };

    const handleDeleteWithTransactions = (id) => {
        if (confirm('Are you sure you want to delete this recurring income AND all its generated transactions? This cannot be undone.')) {
            router.delete(route('admin.recurring_income.delete_with_transaction', id));
        }
    };

    const formatSchedule = (income) => {
        let scheduleStr = `Every ${income.recurring_times} ${income.recurring}(s)`;
        if (income.recurring === 'week' && income.recurring_times_week) {
            scheduleStr += ` on [${income.recurring_times_week}]`;
        } else if (income.recurring === 'month' && income.recurring_times_month) {
            scheduleStr += ` on day [${income.recurring_times_month}]`;
        } else if (income.recurring === 'year' && income.recurring_times_year) {
            scheduleStr += ` on [${income.recurring_times_year}]`;
        }
        return scheduleStr;
    };

    // Week days helper list
    const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // Month days helper list (1-31)
    const monthDays = Array.from({ length: 31 }, (_, i) => i + 1);

    // Month name helper
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    // Days in month helper for year selection
    const getYearDaysList = () => {
        const list: { val: string; label: string }[] = [];
        monthNames.forEach((month, mIdx) => {
            const daysInMonth = new Date(2024, mIdx + 1, 0).getDate(); // Leap year 2024 to support Feb 29
            for (let d = 1; d <= daysInMonth; d++) {
                list.push({
                    val: `${d}-${mIdx + 1}`,
                    label: `${d.toString().padStart(2, '0')} - ${month}`
                });
            }
        });
        return list;
    };

    const yearDaysList = getYearDaysList();

    return (
        <AdminSidebarLayout title={__('general.recurring_income')} header="Business Operations">
            <Head title={__('general.admin_recurring_income')} />

            <div className="mb-4">
                <Link href={route('admin.finance.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_financial_ledger')}</Link>
            </div>

            {/* Top Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full me-4 text-slate-800 border">
                        <DollarSign className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.estimated_monthly_revenue')}</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.monthly_total}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full me-4 text-slate-800 border">
                        <TrendingUp className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.estimated_annual_revenue')}</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.annual_total}</h3>
                    </div>
                </div>
            </div>

            {/* Title & Actions Bar */}
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-xl font-bold text-slate-900">{__('general.active_recurring_income')}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_repeated_automated_business_receipts_and_client_retainers')}</p>
                </div>

                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button className="bg-black hover:bg-slate-800 text-white h-9">
                            <Plus className="w-4 h-4 me-2" />{__('general.add_recurring_income')}</Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[450px] max-h-[85vh] overflow-y-auto">
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>{__('general.add_recurring_income')}</DialogTitle>
                                <DialogDescription>{__('general.add_a_new_recurring_revenue_entry_that_repeats_automatically')}</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">{__('general.title_description')}</Label>
                                    <Input id="title" required value={newIncome.title} onChange={e => setNewIncome({...newIncome, title: e.target.value})} placeholder={__('general.e_g_monthly_saas_subscription')} />
                                    {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">Amount</Label>
                                        <Input id="amount" type="number" step="any" required value={newIncome.amount} onChange={e => setNewIncome({...newIncome, amount: e.target.value})} placeholder="0.00" />
                                        {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="currency">Currency</Label>
                                        <select id="currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newIncome.currency} onChange={e => setNewIncome({...newIncome, currency: e.target.value})}>
                                            {currenciesList.map(c => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                                        </select>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label>{__('general.category_reason')}</Label>
                                    <div className="grid grid-cols-2 gap-2">
                                        <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={createReasonOption} onChange={e => {
                                            setCreateReasonOption(e.target.value);
                                            setNewIncome({...newIncome, reason_choice: e.target.value});
                                        }}>
                                            <option value="retainer">Retainer</option>
                                            <option value="subscription">Subscription</option>
                                            <option value="consulting">Consulting</option>
                                            {categoriesList.filter(c => !['retainer', 'subscription', 'consulting'].includes(c.toLowerCase())).map((c, i) => (
                                                <option key={i} value={c}>{c}</option>
                                            ))}
                                            <option value="custom">-- Custom Reason --</option>
                                        </select>
                                        {createReasonOption === 'custom' && (
                                            <Input required placeholder={__('general.specify_reason')} value={newIncome.custom_reason} onChange={e => setNewIncome({...newIncome, custom_reason: e.target.value})} />
                                        )}
                                    </div>
                                    {errors.reason_choice && <span className="text-red-600 text-xs block">{errors.reason_choice}</span>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="start_date">{__('general.start_date')}</Label>
                                    <Input id="start_date" type="date" required value={newIncome.start_date} onChange={e => setNewIncome({...newIncome, start_date: e.target.value})} />
                                    {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date}</span>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="frequency">Frequency</Label>
                                        <select id="frequency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newIncome.recurring} onChange={e => setNewIncome({...newIncome, recurring: e.target.value})}>
                                            <option value="day">Daily</option>
                                            <option value="week">Weekly</option>
                                            <option value="month">Monthly</option>
                                            <option value="year">Annually</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="interval">Interval (Every N)</Label>
                                        <select id="interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newIncome.recurring_times} onChange={e => setNewIncome({...newIncome, recurring_times: parseInt(e.target.value) || 1})}>
                                            {Array.from({ length: 30 }, (_, i) => i + 1).map(num => (
                                                <option key={num} value={num}>{num}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                {newIncome.recurring === 'week' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="week-days">{__('general.specific_week_days')}</Label>
                                        <select
                                            id="week-days"
                                            multiple
                                            className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                                            value={newIncome.recurring_times_week}
                                            onChange={e => {
                                                const vals = Array.from(e.target.selectedOptions, option => option.value);
                                                setNewIncome({...newIncome, recurring_times_week: vals});
                                            }}
                                        >
                                            {weekDays.map(wd => <option key={wd} value={wd}>{wd}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                                    </div>
                                )}

                                {newIncome.recurring === 'month' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="month-days">{__('general.specific_month_days')}</Label>
                                        <select
                                            id="month-days"
                                            multiple
                                            className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                                            value={newIncome.recurring_times_month}
                                            onChange={e => {
                                                const vals = Array.from(e.target.selectedOptions, option => option.value);
                                                setNewIncome({...newIncome, recurring_times_month: vals});
                                            }}
                                        >
                                            {monthDays.map(d => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                                    </div>
                                )}

                                {newIncome.recurring === 'year' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="year-days">{__('general.specific_year_dates')}</Label>
                                        <select
                                            id="year-days"
                                            multiple
                                            className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                                            value={newIncome.recurring_times_year}
                                            onChange={e => {
                                                const vals = Array.from(e.target.selectedOptions, option => option.value);
                                                setNewIncome({...newIncome, recurring_times_year: vals});
                                            }}
                                        >
                                            {yearDaysList.map(yd => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                                    </div>
                                )}
                            </div>
                            <DialogFooter>
                                <Button type="submit" className="bg-black hover:bg-slate-800 text-white w-full">{__('general.create_recurring_income')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {/* Data Table */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.title_schedule')}</th>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.start_date')}</th>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Category
                            </th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Amount
                            </th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Active
                            </th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                Transactions
                            </th>
                            <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {(incomes.data as any).map((income) => (
                            <tr key={income.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="text-sm font-semibold text-gray-900">{income.title}</div>
                                    <div className="text-xs text-gray-500 mt-1 flex items-center gap-1"><Clock className="w-3 h-3"/> {formatSchedule(income)}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{new Date(income.start_date).toLocaleDateString()}</div>
                                    <div className="text-xs text-gray-500 mt-0.5">Next: {income.current_date}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="bg-slate-100 border text-slate-800 text-xs px-2 py-0.5 rounded font-medium">{income.reason}</span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-sm font-bold text-emerald-650 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded">
                                        {formatCurrency(income.amount, income.currency)}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <Switch 
                                        checked={income.is_active} 
                                        onCheckedChange={() => handleToggleActive(income.id)} 
                                        aria-label="Toggle active status"
                                    />
                                    <div className="text-[10px] text-gray-500 mt-1">{income.is_active ? __('general.active') : __('general.inactive')}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-xs font-medium text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">{__('general.active_log')}</span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <Link href={route('admin.recurring_income.view', income.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title={__('general.view_details')}>
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Link href={route('admin.recurring_income.edit', income.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title="Edit">
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" className="text-orange-600 hover:text-orange-900 me-1" onClick={() => handleDelete(income.id)} title={__('general.delete_schedule_only')}>
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-900" onClick={() => handleDeleteWithTransactions(income.id)} title={__('general.delete_everything')}>
                                        <Trash2 className="w-4 h-4 border border-red-200 rounded p-0.5" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {(incomes.data as any).length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                    <Calendar className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    <h3 className="text-lg font-medium text-gray-900">{__('general.no_recurring_income_found')}</h3>
                                    <p className="mt-1">{__('general.add_a_new_schedule_to_start_managing_automated_revenue_streams')}</p>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {incomes.links && incomes.links.length > 3 && (
                <div className="flex justify-between items-center mt-6">
                    <div className="text-sm text-gray-500">
                        Showing {incomes.from} to {incomes.to} of {incomes.total} entries
                    </div>
                    <div className="flex space-x-1">
                        {incomes.links.map((link, idx) => (
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
