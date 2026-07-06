import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

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

export default function Create() {
    const { errors } = usePage().props;

    const [form, setForm] = useState({
        title: '',
        message: '',
        type: 'info',
        start_date: new Date().toISOString().slice(0, 10),
        recurring: 'month',
        recurring_times: 1,
        recurring_times_week: [] as string[],
        recurring_times_month: [] as string[],
        recurring_times_year: [] as string[],
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.recurring_notices.store'), form);
    };

    return (
        <AdminSidebarLayout title={__('general.add_recurring_notice')} header="Business Operations">
            <Head title={__('general.add_recurring_notice')} />

            <div className="mb-4">
                <Link href={route('admin.recurring_notices.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_recurring_notices')}
                </Link>
            </div>

            <div className="bg-white p-6 rounded-xl border shadow-sm w-full max-w-7xl">
                <h2 className="text-xl font-bold text-slate-900 mb-2">{__('general.add_recurring_notice')}</h2>
                <p className="text-sm text-gray-500 mb-6">{__('general.create_a_notice_that_surfaces_on_the_board_when_due', {}, 'Create a notice that surfaces on the board when due.')}</p>

                <form onSubmit={handleCreate} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="title">{__('general.title')}</Label>
                        <Input id="title" required value={form.title} onChange={e => setForm({ ...form, title: e.target.value })} placeholder={__('general.title')} />
                        {errors.title && <span className="text-red-600 text-xs block">{errors.title as string}</span>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="message">{__('general.notice_message')}</Label>
                        <textarea
                            id="message"
                            rows={3}
                            className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white"
                            value={form.message}
                            onChange={e => setForm({ ...form, message: e.target.value })}
                            placeholder={__('general.notice_message')}
                        />
                        {errors.message && <span className="text-red-600 text-xs block">{errors.message as string}</span>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="type">{__('general.notice_type')}</Label>
                            <select id="type" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={form.type} onChange={e => setForm({ ...form, type: e.target.value })}>
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="danger">Danger</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="start_date">{__('general.start_date')}</Label>
                            <Input id="start_date" type="date" required value={form.start_date} onChange={e => setForm({ ...form, start_date: e.target.value })} />
                            {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date as string}</span>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="frequency">{__('general.frequency')}</Label>
                            <select id="frequency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={form.recurring} onChange={e => setForm({ ...form, recurring: e.target.value })}>
                                <option value="day">{__('general.daily')}</option>
                                <option value="week">{__('general.weekly')}</option>
                                <option value="month">{__('general.monthly')}</option>
                                <option value="year">{__('general.annually')}</option>
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="interval">Interval (Every N)</Label>
                            <select id="interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={form.recurring_times} onChange={e => setForm({ ...form, recurring_times: parseInt(e.target.value) || 1 })}>
                                {Array.from({ length: 30 }, (_, i) => i + 1).map(num => (
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
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setForm({ ...form, recurring_times_week: vals });
                                }}
                            >
                                {weekDays.map(wd => <option key={wd} value={wd}>{wd}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                            {errors.recurring_times_week && <span className="text-red-600 text-xs block">{errors.recurring_times_week as string}</span>}
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
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setForm({ ...form, recurring_times_month: vals });
                                }}
                            >
                                {monthDays.map(d => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                            {errors.recurring_times_month && <span className="text-red-600 text-xs block">{errors.recurring_times_month as string}</span>}
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
                                onChange={e => {
                                    const vals = Array.from(e.target.selectedOptions, option => option.value);
                                    setForm({ ...form, recurring_times_year: vals });
                                }}
                            >
                                {yearDaysList.map(yd => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                            </select>
                            <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                            {errors.recurring_times_year && <span className="text-red-600 text-xs block">{errors.recurring_times_year as string}</span>}
                        </div>
                    )}

                    <div className="pt-4 border-t">
                        <Button type="submit" className="bg-black hover:bg-slate-800 text-white">{__('general.create_recurring_notice')}</Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}
