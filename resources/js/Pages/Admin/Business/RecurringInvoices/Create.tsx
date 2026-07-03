import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { ArrowLeft, Plus, Save } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Create({ currencies, users }) {
    const { errors } = usePage().props;
    const currenciesList = Array.isArray(currencies) ? currencies : (currencies ? Object.values(currencies) : []);
    const usersList = Array.isArray(users) ? users : (users ? Object.values(users) : []);

    const defaultCurrencyId = currenciesList[0]?.id || '';
    const defaultUserId = usersList[0]?.id || '';

    const [form, setForm] = useState({
        user_id: defaultUserId,
        title: '',
        amount: '',
        currency: defaultCurrencyId,
        reason: '',
        start_date: new Date().toISOString().slice(0, 10),
        recurring: 'month',
        recurring_times: 1,
        recurring_times_week: [] as string[],
        recurring_times_month: [] as string[],
        recurring_times_year: [] as string[],
    });
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            route('admin.recurring_invoices.store'),
            {
                ...form,
                user_id: parseInt(form.user_id as string) || form.user_id,
                currency: parseInt(form.currency as string) || form.currency,
            },
            {
                onFinish: () => setSubmitting(false),
            }
        );
    };

    const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const monthDays = Array.from({ length: 31 }, (_, i) => i + 1);
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    const getYearDaysList = () => {
        const list: { val: string; label: string }[] = [];
        monthNames.forEach((month, mIdx) => {
            const daysInMonth = new Date(2024, mIdx + 1, 0).getDate();
            for (let d = 1; d <= daysInMonth; d++) {
                list.push({
                    val: `${d}-${mIdx + 1}`,
                    label: `${d.toString().padStart(2, '0')} - ${month}`,
                });
            }
        });
        return list;
    };

    const yearDaysList = getYearDaysList();

    const initialClientOptions = usersList.map((u: any) => ({
        value: u.id,
        label: u.name ? `${u.name} (${u.email})` : u.email,
    }));

    return (
        <AdminSidebarLayout title={__('general.add_recurring_invoice')} header="Business Operations">
            <Head title={__('general.add_recurring_invoice')} />

            <div className="mb-4">
                <Link href={route('admin.recurring_invoices.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_invoices')}</Link>
            </div>

            <div className="bg-white border rounded-xl shadow-sm w-full max-w-7xl overflow-hidden">
                <div className="border-b px-6 py-4 bg-slate-50">
                    <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <Plus className="w-5 h-5 text-slate-500" />
                        {__('general.add_recurring_invoice')}
                    </h2>
                    <p className="text-sm text-gray-500 mt-0.5">{__('general.create_a_repeated_salary_payment_schedule_for_a_team_member')}</p>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="user_id">{__('general.user_user')}</Label>
                        <PremiumCombobox
                            value={form.user_id || null}
                            onChange={(val) => setForm({ ...form, user_id: val as any })}
                            options={initialClientOptions}
                            asyncEndpoint={route('admin.projects.search-clients')}
                            searchParam="q"
                            placeholder={__('general.select_user')}
                            searchPlaceholder={`${__('general.search') || 'Search'} ${__('general.user_user').toLowerCase()}...`}
                            emptyText={__('general.no_results') || 'No clients found.'}
                        />
                        {errors.user_id && <span className="text-red-600 text-xs block">{errors.user_id}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="title">{__('general.title_description')}</Label>
                        <Input
                            id="title"
                            required
                            value={form.title}
                            onChange={(e) => setForm({ ...form, title: e.target.value })}
                            placeholder={__('general.e_g_monthly_salary')}
                        />
                        {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="amount">{__('general.amount')}</Label>
                            <Input
                                id="amount"
                                type="number"
                                step="any"
                                required
                                value={form.amount}
                                onChange={(e) => setForm({ ...form, amount: e.target.value })}
                                placeholder="0.00"
                            />
                            {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">{__('general.currency')}</Label>
                            <select
                                id="currency"
                                required
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                                value={form.currency}
                                onChange={(e) => setForm({ ...form, currency: e.target.value })}
                            >
                                <option value="">{__('general.select_currency') || 'Select currency'}</option>
                                {currenciesList.map((c: any) => (
                                    <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>
                                ))}
                            </select>
                            {errors.currency && <span className="text-red-600 text-xs block">{errors.currency}</span>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="reason">Note / Custom Reason (Optional)</Label>
                        <Input
                            id="reason"
                            value={form.reason}
                            onChange={(e) => setForm({ ...form, reason: e.target.value })}
                            placeholder={__('general.e_g_senior_backend_dev_rate')}
                        />
                        {errors.reason && <span className="text-red-600 text-xs block">{errors.reason}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="start_date">{__('general.start_date')}</Label>
                        <Input
                            id="start_date"
                            type="date"
                            required
                            value={form.start_date}
                            onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                        />
                        {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date}</span>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="frequency">{__('general.frequency')}</Label>
                            <select
                                id="frequency"
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                                value={form.recurring}
                                onChange={(e) => setForm({ ...form, recurring: e.target.value })}
                            >
                                <option value="day">{__('general.daily')}</option>
                                <option value="week">{__('general.weekly')}</option>
                                <option value="month">{__('general.monthly')}</option>
                                <option value="year">{__('general.annually')}</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="interval">Interval (Every N)</Label>
                            <select
                                id="interval"
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10"
                                value={form.recurring_times}
                                onChange={(e) => setForm({ ...form, recurring_times: parseInt(e.target.value) || 1 })}
                            >
                                {Array.from({ length: 30 }, (_, i) => i + 1).map((num) => (
                                    <option key={num} value={num}>{num}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {form.recurring === 'week' && (
                        <div className="space-y-2">
                            <Label htmlFor="week-days">{__('general.specific_week_days')}</Label>
                            <select
                                id="week-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                                value={form.recurring_times_week}
                                onChange={(e) => {
                                    const vals = Array.from(e.target.selectedOptions, (option) => option.value);
                                    setForm({ ...form, recurring_times_week: vals });
                                }}
                            >
                                {weekDays.map((wd) => <option key={wd} value={wd}>{wd}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                            {errors.recurring_times_week && <span className="text-red-600 text-xs block">{errors.recurring_times_week}</span>}
                        </div>
                    )}

                    {form.recurring === 'month' && (
                        <div className="space-y-2">
                            <Label htmlFor="month-days">{__('general.specific_month_days')}</Label>
                            <select
                                id="month-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                                value={form.recurring_times_month}
                                onChange={(e) => {
                                    const vals = Array.from(e.target.selectedOptions, (option) => option.value);
                                    setForm({ ...form, recurring_times_month: vals });
                                }}
                            >
                                {monthDays.map((d) => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                            {errors.recurring_times_month && <span className="text-red-600 text-xs block">{errors.recurring_times_month}</span>}
                        </div>
                    )}

                    {form.recurring === 'year' && (
                        <div className="space-y-2">
                            <Label htmlFor="year-days">{__('general.specific_year_dates')}</Label>
                            <select
                                id="year-days"
                                multiple
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                                value={form.recurring_times_year}
                                onChange={(e) => {
                                    const vals = Array.from(e.target.selectedOptions, (option) => option.value);
                                    setForm({ ...form, recurring_times_year: vals });
                                }}
                            >
                                {yearDaysList.map((yd) => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                            {errors.recurring_times_year && <span className="text-red-600 text-xs block">{errors.recurring_times_year}</span>}
                        </div>
                    )}

                    <div className="flex gap-4 pt-4 border-t">
                        <Button type="submit" disabled={submitting} className="bg-black hover:bg-slate-800 text-white flex items-center gap-2">
                            <Save className="w-4 h-4" />
                            {__('general.create_recurring_invoice')}
                        </Button>
                        <Link href={route('admin.recurring_invoices.index')}>
                            <Button type="button" variant="outline">{__('general.cancel')}</Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
