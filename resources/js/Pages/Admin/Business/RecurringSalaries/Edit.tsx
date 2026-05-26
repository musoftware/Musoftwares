import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { ArrowLeft, Save } from 'lucide-react';

export default function Edit({ salary, currencies, users }) {
    const { errors } = usePage().props;
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const usersList = Array.isArray(users) ? users : (users ? Object.values(users) : []);

    const [editSalary, setEditSalary] = useState({
        user_id: salary.user_id,
        title: salary.title,
        amount: salary.amount,
        currency: salary.currency,
        reason: salary.reason,
        start_date: salary.start_date,
        recurring: salary.recurring,
        recurring_times: salary.recurring_times,
        recurring_times_week: salary.recurring_times_week || [],
        // Convert numbers in recurring_times_month to strings for HTML multi-select compatibility
        recurring_times_month: (salary.recurring_times_month || []).map(val => val.toString()),
        recurring_times_year: salary.recurring_times_year || [],
    });

    const handleUpdate = (e) => {
        e.preventDefault();
        router.put(route('admin.recurring_salaries.update', salary.id), {
            ...editSalary,
            user_id: parseInt(editSalary.user_id as string) || editSalary.user_id,
            currency: parseInt(editSalary.currency as string) || editSalary.currency
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
        <AdminSidebarLayout title="Edit Recurring Salary" header="Business Operations">
            <Head title="Edit Recurring Salary" />

            <div className="mb-4">
                <Link href={route('admin.recurring_salaries.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" /> Back to Recurring Salaries
                </Link>
            </div>

            <div className="bg-white border rounded-xl shadow-sm max-w-2xl overflow-hidden">
                <div className="border-b px-6 py-4 bg-slate-50">
                    <h2 className="text-lg font-bold text-slate-900">Edit Recurring Salary Details</h2>
                    <p className="text-sm text-gray-500 mt-0.5">Modify the payroll parameters for this employee.</p>
                </div>

                <form onSubmit={handleUpdate} className="p-6 space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="user_id">Employee / User</Label>
                        <select id="user_id" required className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editSalary.user_id} onChange={e => setEditSalary({...editSalary, user_id: e.target.value})}>
                            <option value="">Select Employee...</option>
                            {usersList.map(u => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                        </select>
                        {errors.user_id && <span className="text-red-600 text-xs block">{errors.user_id}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="title">Title / Description</Label>
                        <Input id="title" required value={editSalary.title} onChange={e => setEditSalary({...editSalary, title: e.target.value})} placeholder="e.g. Monthly Salary" />
                        {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="amount">Amount</Label>
                            <Input id="amount" type="number" step="any" required value={editSalary.amount} onChange={e => setEditSalary({...editSalary, amount: e.target.value})} placeholder="0.00" />
                            {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">Currency</Label>
                            <select id="currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editSalary.currency} onChange={e => setEditSalary({...editSalary, currency: e.target.value})}>
                                {currenciesList.map(c => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                            </select>
                            {errors.currency && <span className="text-red-600 text-xs block">{errors.currency}</span>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="reason">Note / Custom Reason (Optional)</Label>
                        <Input id="reason" value={editSalary.reason} onChange={e => setEditSalary({...editSalary, reason: e.target.value})} placeholder="e.g. Senior Developer Rate" />
                        {errors.reason && <span className="text-red-600 text-xs block">{errors.reason}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="start_date">Start Date</Label>
                        <Input id="start_date" type="date" required value={editSalary.start_date} onChange={e => setEditSalary({...editSalary, start_date: e.target.value})} />
                        {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date}</span>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="frequency">Frequency</Label>
                            <select id="frequency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editSalary.recurring} onChange={e => setEditSalary({...editSalary, recurring: e.target.value})}>
                                <option value="day">Daily</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                                <option value="year">Annually</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="interval">Interval (Every N)</Label>
                            <select id="interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={editSalary.recurring_times} onChange={e => setEditSalary({...editSalary, recurring_times: parseInt(e.target.value) || 1})}>
                                {Array.from({ length: 30 }, (_, i) => i + 1).map(num => (
                                    <option key={num} value={num}>{num}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {editSalary.recurring === 'week' && (
                        <div className="space-y-2">
                            <Label htmlFor="week-days">Specific Week Days</Label>
                            <select
                                id="week-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                                value={editSalary.recurring_times_week}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setEditSalary({...editSalary, recurring_times_week: vals});
                                }}
                            >
                                {weekDays.map(wd => <option key={wd} value={wd}>{wd}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple days. Current: {editSalary.recurring_times_week.join(', ') || 'None'}</span>
                        </div>
                    )}

                    {editSalary.recurring === 'month' && (
                        <div className="space-y-2">
                            <Label htmlFor="month-days">Specific Month Days</Label>
                            <select
                                id="month-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                                value={editSalary.recurring_times_month}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setEditSalary({...editSalary, recurring_times_month: vals});
                                }}
                            >
                                {monthDays.map(d => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple days. Current: {editSalary.recurring_times_month.join(', ') || 'None'}</span>
                        </div>
                    )}

                    {editSalary.recurring === 'year' && (
                        <div className="space-y-2">
                            <Label htmlFor="year-days">Specific Year Dates</Label>
                            <select
                                id="year-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                                value={editSalary.recurring_times_year}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setEditSalary({...editSalary, recurring_times_year: vals});
                                }}
                            >
                                {yearDaysList.map(yd => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple dates. Current: {editSalary.recurring_times_year.join(', ') || 'None'}</span>
                        </div>
                    )}

                    <div className="flex gap-4 pt-4 border-t">
                        <Button type="submit" className="bg-black hover:bg-slate-800 text-white flex items-center gap-2">
                            <Save className="w-4 h-4" /> Save Changes
                        </Button>
                        <Link href={route('admin.recurring_salaries.index')}>
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
