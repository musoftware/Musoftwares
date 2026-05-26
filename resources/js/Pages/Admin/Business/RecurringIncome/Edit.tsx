import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { ArrowLeft, Save } from 'lucide-react';

export default function Edit({ income, currencies, categories }) {
    const { errors } = usePage().props;
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const categoriesList = Array.isArray(categories) ? categories : (categories ? Object.values(categories) : []);

    const isInitialCustomReason = !['retainer', 'subscription', 'consulting', ...categoriesList.map(c => c.toLowerCase())].includes(income.reason?.toLowerCase());
    const [reasonOption, setReasonOption] = useState(isInitialCustomReason ? 'custom' : income.reason);

    const [editIncome, setEditIncome] = useState({
        title: income.title,
        amount: income.amount,
        currency: income.currency,
        reason_choice: isInitialCustomReason ? 'custom' : income.reason,
        custom_reason: isInitialCustomReason ? income.reason : '',
        start_date: income.start_date,
        recurring: income.recurring,
        recurring_times: income.recurring_times,
        recurring_times_week: income.recurring_times_week || [],
        recurring_times_month: income.recurring_times_month || [],
        recurring_times_year: income.recurring_times_year || [],
    });

    const handleUpdate = (e) => {
        e.preventDefault();
        router.put(route('admin.recurring_income.update', income.id), {
            ...editIncome,
            currency: parseInt(editIncome.currency as string) || editIncome.currency
        });
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
        <AdminSidebarLayout title="Edit Recurring Income" header="Business Operations">
            <Head title="Edit Recurring Income" />

            <div className="mb-4">
                <Link href={route('admin.recurring_income.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" /> Back to Recurring Income
                </Link>
            </div>

            <div className="bg-white border rounded-xl shadow-sm max-w-2xl overflow-hidden">
                <div className="border-b px-6 py-4 bg-slate-50">
                    <h2 className="text-lg font-bold text-slate-900">Edit Recurring Income Details</h2>
                    <p className="text-sm text-gray-500 mt-0.5">Modify the parameters for this recurring automated revenue.</p>
                </div>

                <form onSubmit={handleUpdate} className="p-6 space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="title">Title / Description</Label>
                        <Input id="title" required value={editIncome.title} onChange={e => setEditIncome({...editIncome, title: e.target.value})} placeholder="e.g. Monthly SaaS Subscription" />
                        {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="amount">Amount</Label>
                            <Input id="amount" type="number" step="any" required value={editIncome.amount} onChange={e => setEditIncome({...editIncome, amount: e.target.value})} placeholder="0.00" />
                            {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">Currency</Label>
                            <select id="currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editIncome.currency} onChange={e => setEditIncome({...editIncome, currency: e.target.value})}>
                                {currenciesList.map(c => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                            </select>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Category / Reason</Label>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={reasonOption} onChange={e => {
                                setReasonOption(e.target.value);
                                setEditIncome({...editIncome, reason_choice: e.target.value});
                            }}>
                                <option value="retainer">Retainer</option>
                                <option value="subscription">Subscription</option>
                                <option value="consulting">Consulting</option>
                                {categoriesList.filter(c => !['retainer', 'subscription', 'consulting'].includes(c.toLowerCase())).map((c, i) => (
                                    <option key={i} value={c}>{c}</option>
                                ))}
                                <option value="custom">-- Custom Reason --</option>
                            </select>
                            {reasonOption === 'custom' && (
                                <Input required placeholder="Specify reason..." value={editIncome.custom_reason} onChange={e => setEditIncome({...editIncome, custom_reason: e.target.value})} />
                            )}
                        </div>
                        {errors.reason_choice && <span className="text-red-600 text-xs block">{errors.reason_choice}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="start_date">Start Date</Label>
                        <Input id="start_date" type="date" required value={editIncome.start_date} onChange={e => setEditIncome({...editIncome, start_date: e.target.value})} />
                        {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date}</span>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="frequency">Frequency</Label>
                            <select id="frequency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editIncome.recurring} onChange={e => setEditIncome({...editIncome, recurring: e.target.value})}>
                                <option value="day">Daily</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                                <option value="year">Annually</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="interval">Interval (Every N)</Label>
                            <select id="interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editIncome.recurring_times} onChange={e => setEditIncome({...editIncome, recurring_times: parseInt(e.target.value) || 1})}>
                                {Array.from({ length: 30 }, (_, i) => i + 1).map(num => (
                                    <option key={num} value={num}>{num}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {editIncome.recurring === 'week' && (
                        <div className="space-y-2">
                            <Label htmlFor="week-days">Specific Week Days</Label>
                            <select
                                id="week-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                                value={editIncome.recurring_times_week}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setEditIncome({...editIncome, recurring_times_week: vals});
                                }}
                            >
                                {weekDays.map(wd => <option key={wd} value={wd}>{wd}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple days. Current: {editIncome.recurring_times_week.join(', ') || 'None'}</span>
                        </div>
                    )}

                    {editIncome.recurring === 'month' && (
                        <div className="space-y-2">
                            <Label htmlFor="month-days">Specific Month Days</Label>
                            <select
                                id="month-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                                value={editIncome.recurring_times_month}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setEditIncome({...editIncome, recurring_times_month: vals});
                                }}
                            >
                                {monthDays.map(d => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple days. Current: {editIncome.recurring_times_month.join(', ') || 'None'}</span>
                        </div>
                    )}

                    {editIncome.recurring === 'year' && (
                        <div className="space-y-2">
                            <Label htmlFor="year-days">Specific Year Dates</Label>
                            <select
                                id="year-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                                value={editIncome.recurring_times_year}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setEditIncome({...editIncome, recurring_times_year: vals});
                                }}
                            >
                                {yearDaysList.map(yd => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple dates. Current: {editIncome.recurring_times_year.join(', ') || 'None'}</span>
                        </div>
                    )}

                    <div className="flex gap-4 pt-4 border-t">
                        <Button type="submit" className="bg-black hover:bg-slate-800 text-white flex items-center gap-2">
                            <Save className="w-4 h-4" /> Save Changes
                        </Button>
                        <Link href={route('admin.recurring_income.index')}>
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
