import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Create({ currencies, categories, stats }) {
    const { errors } = usePage().props;
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const categoriesList = Array.isArray(categories) ? categories : (categories ? Object.values(categories) : []);

    const [createReasonOption, setCreateReasonOption] = useState(categoriesList.length > 0 ? categoriesList[0] : 'custom');

    const defaultCurrencyId = currenciesList.find(c => c.currency === stats.business_currency_code)?.id || (currenciesList[0]?.id || '');

    const [newCost, setNewCost] = useState({
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
        router.post(route('admin.recurring_costs.store'), {
            ...newCost,
            currency: parseInt(newCost.currency as string) || newCost.currency
        });
    };

    const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const monthDays = Array.from({ length: 31 }, (_, i) => i + 1);
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const getYearDaysList = () => {
        const list: { val: string; label: string }[] = [];
        monthNames.forEach((month, mIdx) => {
            const daysInMonth = new Date(2024, mIdx + 1, 0).getDate();
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
        <AdminSidebarLayout title={__('general.add_recurring_cost')} header="Business Operations">
            <Head title={__('general.add_recurring_cost')} />

            <div className="mb-4">
                <Link href={route('admin.recurring_costs.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_costs')}</Link>
            </div>

            <div className="bg-white p-6 rounded-xl border shadow-sm max-w-2xl">
                <h2 className="text-xl font-bold text-slate-900 mb-2">{__('general.add_recurring_cost')}</h2>
                <p className="text-sm text-gray-500 mb-6">{__('general.add_a_new_overhead_expense_that_repeats_automatically')}</p>

                <form onSubmit={handleCreate} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="title">{__('general.title_description')}</Label>
                        <Input id="title" required value={newCost.title} onChange={e => setNewCost({...newCost, title: e.target.value})} placeholder={__('general.e_g_aws_hosting_fee')} />
                        {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="amount">{__('general.amount')}</Label>
                            <Input id="amount" type="number" step="any" required value={newCost.amount} onChange={e => setNewCost({...newCost, amount: e.target.value})} placeholder="0.00" />
                            {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">{__('general.currency')}</Label>
                            <select id="currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newCost.currency} onChange={e => setNewCost({...newCost, currency: e.target.value})}>
                                {currenciesList.map(c => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                            </select>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>{__('general.category_reason')}</Label>
                        <div className="grid grid-cols-2 gap-2">
                            <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={createReasonOption} onChange={e => {
                                setCreateReasonOption(e.target.value);
                                setNewCost({...newCost, reason_choice: e.target.value});
                            }}>
                                <option value="internet">{__('general.internet')}</option>
                                <option value="electricity">{__('general.electricity')}</option>
                                <option value="salary">{__('general.salary')}</option>
                                {categoriesList.filter(c => !['internet', 'electricity', 'salary'].includes(c.toLowerCase())).map((c, i) => (
                                    <option key={i} value={c}>{c}</option>
                                ))}
                                <option value="custom">-- Custom Reason --</option>
                            </select>
                            {createReasonOption === 'custom' && (
                                <Input required placeholder={__('general.specify_reason')} value={newCost.custom_reason} onChange={e => setNewCost({...newCost, custom_reason: e.target.value})} />
                            )}
                        </div>
                        {errors.reason_choice && <span className="text-red-600 text-xs block">{errors.reason_choice}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="start_date">{__('general.start_date')}</Label>
                        <Input id="start_date" type="date" required value={newCost.start_date} onChange={e => setNewCost({...newCost, start_date: e.target.value})} />
                        {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date}</span>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="frequency">{__('general.frequency')}</Label>
                            <select id="frequency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newCost.recurring} onChange={e => setNewCost({...newCost, recurring: e.target.value})}>
                                <option value="day">{__('general.daily')}</option>
                                <option value="week">{__('general.weekly')}</option>
                                <option value="month">{__('general.monthly')}</option>
                                <option value="year">{__('general.annually')}</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="interval">Interval (Every N)</Label>
                            <select id="interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newCost.recurring_times} onChange={e => setNewCost({...newCost, recurring_times: parseInt(e.target.value) || 1})}>
                                {Array.from({ length: 30 }, (_, i) => i + 1).map(num => (
                                    <option key={num} value={num}>{num}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {newCost.recurring === 'week' && (
                        <div className="space-y-2">
                            <Label htmlFor="week-days">{__('general.specific_week_days')}</Label>
                            <select
                                id="week-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                                value={newCost.recurring_times_week}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setNewCost({...newCost, recurring_times_week: vals});
                                }}
                            >
                                {weekDays.map(wd => <option key={wd} value={wd}>{wd}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                        </div>
                    )}

                    {newCost.recurring === 'month' && (
                        <div className="space-y-2">
                            <Label htmlFor="month-days">{__('general.specific_month_days')}</Label>
                            <select
                                id="month-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                                value={newCost.recurring_times_month}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setNewCost({...newCost, recurring_times_month: vals});
                                }}
                            >
                                {monthDays.map(d => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                        </div>
                    )}

                    {newCost.recurring === 'year' && (
                        <div className="space-y-2">
                            <Label htmlFor="year-days">{__('general.specific_year_dates')}</Label>
                            <select
                                id="year-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                                value={newCost.recurring_times_year}
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setNewCost({...newCost, recurring_times_year: vals});
                                }}
                            >
                                {yearDaysList.map(yd => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                        </div>
                    )}
                    <div className="pt-4 border-t">
                        <Button type="submit" className="bg-black hover:bg-slate-800 text-white">{__('general.create_recurring_cost')}</Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
