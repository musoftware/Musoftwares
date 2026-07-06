import React, { useCallback, useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Trash2, Edit, Plus, Clock, Bell, ArrowLeft, Loader2 } from 'lucide-react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

type NoticeType = 'info' | 'success' | 'warning' | 'danger';

interface NoticeRow {
    id: number;
    title: string;
    message: string | null;
    type: NoticeType;
    start_date: string;
    recurring: string;
    recurring_times: number;
    recurring_times_week: string | null;
    recurring_times_month: string | null;
    recurring_times_year: string | null;
    is_active: boolean;
}

type FormState = {
    id?: number;
    title: string;
    message: string;
    type: NoticeType;
    start_date: string;
    recurring: 'day' | 'week' | 'month' | 'year';
    recurring_times: number;
    recurring_times_week: string[];
    recurring_times_month: string[];
    recurring_times_year: string[];
};

const TYPE_BADGE: Record<NoticeType, string> = {
    info: 'bg-blue-100 text-blue-700 ring-blue-200',
    success: 'bg-green-100 text-green-700 ring-green-200',
    warning: 'bg-amber-100 text-amber-700 ring-amber-200',
    danger: 'bg-red-100 text-red-700 ring-red-200',
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
            list.push({ val: `${d}-${mIdx + 1}`, label: `${d.toString().padStart(2, '0')} - ${month}` });
        }
    });
    return list;
};

const yearDaysList = getYearDaysList();

const emptyForm: FormState = {
    title: '',
    message: '',
    type: 'info',
    start_date: new Date().toISOString().slice(0, 10),
    recurring: 'month',
    recurring_times: 1,
    recurring_times_week: [],
    recurring_times_month: [],
    recurring_times_year: [],
};

export const NOTICES_MANAGER_OPEN_EVENT = 'notices-manager:open';

function scheduleLabel(n: NoticeRow): string {
    let label = `Every ${n.recurring_times} ${n.recurring}`;
    if (n.recurring === 'week' && n.recurring_times_week) {
        label += ` on [${n.recurring_times_week}]`;
    } else if (n.recurring === 'month' && n.recurring_times_month) {
        label += ` on [${n.recurring_times_month}]`;
    } else if (n.recurring === 'year' && n.recurring_times_year) {
        label += ` on [${n.recurring_times_year}]`;
    }
    return label;
}

export default function NoticesManager() {
    const [open, setOpen] = useState(false);
    const [view, setView] = useState<'list' | 'form'>('list');
    const [rows, setRows] = useState<NoticeRow[]>([]);
    const [stats, setStats] = useState<{ total_active: number; due_today: number }>({ total_active: 0, due_today: 0 });
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState<FormState>(emptyForm);
    const [submitting, setSubmitting] = useState(false);

    const fetchAll = useCallback(async () => {
        setLoading(true);
        try {
            const res = await axios.get(route('admin.recurring_notices.json'));
            setRows(res.data.notices ?? []);
            setStats(res.data.stats ?? { total_active: 0, due_today: 0 });
        } catch {
            toast('error');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        const handler = () => setOpen(true);
        window.addEventListener(NOTICES_MANAGER_OPEN_EVENT, handler);
        return () => window.removeEventListener(NOTICES_MANAGER_OPEN_EVENT, handler);
    }, []);

    useEffect(() => {
        if (open && view === 'list') {
            fetchAll();
        }
    }, [open, view, fetchAll]);

    useEffect(() => {
        if (!open) {
            setView('list');
            setForm(emptyForm);
        }
    }, [open]);

    const startCreate = () => {
        setForm(emptyForm);
        setView('form');
    };

    const startEdit = async (id: number) => {
        try {
            const res = await axios.get(route('admin.recurring_notices.show', id));
            const n = res.data.notice;
            setForm({
                id: n.id,
                title: n.title ?? '',
                message: n.message ?? '',
                type: n.type ?? 'info',
                start_date: n.start_date ?? emptyForm.start_date,
                recurring: n.recurring ?? 'month',
                recurring_times: n.recurring_times ?? 1,
                recurring_times_week: n.recurring_times_week ? String(n.recurring_times_week).split(',') : [],
                recurring_times_month: n.recurring_times_month ? String(n.recurring_times_month).split(',') : [],
                recurring_times_year: n.recurring_times_year ? String(n.recurring_times_year).split(',') : [],
            });
            setView('form');
        } catch {
            toast('error');
        }
    };

    const reloadBoardNotices = () => {
        try {
            router.reload({ only: ['recurring_notices_today'] });
        } catch {
            window.location.reload();
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            if (form.id) {
                await axios.put(route('admin.recurring_notices.update', form.id), form);
            } else {
                await axios.post(route('admin.recurring_notices.store'), form);
            }
            setView('list');
            await fetchAll();
            reloadBoardNotices();
        } catch {
            toast('error');
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm('Delete this notice?')) return;
        try {
            await axios.delete(route('admin.recurring_notices.delete', id));
            await fetchAll();
            reloadBoardNotices();
        } catch {
            toast('error');
        }
    };

    const handleToggle = async (id: number) => {
        try {
            await axios.post(route('admin.recurring_notices.toggle', id));
            await fetchAll();
            reloadBoardNotices();
        } catch {
            toast('error');
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogContent className="sm:max-w-[720px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Bell className="h-4 w-4 text-slate-700" />
                        {__('general.recurring_notices')}
                    </DialogTitle>
                    <DialogDescription className="text-xs text-slate-500">
                        {__('general.manage_recurring_notices_hint', {}, 'Schedule notices that surface on the board when due.')}
                    </DialogDescription>
                </DialogHeader>

                {view === 'list' ? (
                    <div className="mt-4 space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="rounded-lg border bg-slate-50 px-3 py-2">
                                <p className="text-[11px] font-medium uppercase tracking-wider text-slate-500">{__('general.active')}</p>
                                <p className="text-xl font-bold text-slate-900">{stats.total_active}</p>
                            </div>
                            <div className="rounded-lg border bg-amber-50 px-3 py-2">
                                <p className="text-[11px] font-medium uppercase tracking-wider text-amber-700">{__('general.due_today')}</p>
                                <p className="text-xl font-bold text-amber-900">{stats.due_today}</p>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button onClick={startCreate} className="bg-black hover:bg-slate-800 text-white h-9 text-xs">
                                <Plus className="h-4 w-4 me-1" />{__('general.add_recurring_notice')}
                            </Button>
                        </div>

                        {loading ? (
                            <div className="flex items-center justify-center py-10 text-slate-400">
                                <Loader2 className="h-4 w-4 animate-spin me-2" />
                                Loading…
                            </div>
                        ) : rows.length === 0 ? (
                            <div className="rounded-lg border border-dashed bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                {__('general.no_recurring_notices_found')}
                            </div>
                        ) : (
                            <div className="divide-y rounded-lg border bg-white">
                                {rows.map((n) => (
                                    <div key={n.id} className="flex items-start gap-3 p-3">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h4 className="truncate text-sm font-semibold text-slate-900">{n.title}</h4>
                                                <span className={`inline-flex rounded px-1.5 py-0.5 text-[10px] font-medium uppercase ring-1 ${TYPE_BADGE[n.type]}`}>{n.type}</span>
                                            </div>
                                            {n.message && <p className="mt-0.5 line-clamp-2 text-xs text-slate-600">{n.message}</p>}
                                            <div className="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                <span className="inline-flex items-center gap-1">
                                                    <Clock className="h-3 w-3" /> {scheduleLabel(n)}
                                                </span>
                                                <span>·</span>
                                                <span>{n.start_date}</span>
                                                <span className={`inline-flex items-center gap-1 ${n.is_active ? 'text-emerald-600' : 'text-slate-400'}`}>
                                                    {n.is_active ? __('general.active') : __('general.inactive')}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-1">
                                            <Switch checked={n.is_active} onCheckedChange={() => handleToggle(n.id)} />
                                            <Button variant="ghost" size="sm" onClick={() => startEdit(n.id)} className="text-slate-700 hover:text-black">
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => handleDelete(n.id)} className="text-red-600 hover:text-red-900">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                        <button
                            type="button"
                            onClick={() => setView('list')}
                            className="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-black"
                        >
                            <ArrowLeft className="h-3.5 w-3.5" />{__('general.back_to_recurring_notices')}
                        </button>

                        <div className="space-y-2">
                            <Label htmlFor="nm-title">{__('general.title')}</Label>
                            <Input id="nm-title" required value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="nm-message">{__('general.notice_message')}</Label>
                            <textarea
                                id="nm-message"
                                rows={3}
                                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white"
                                value={form.message}
                                onChange={(e) => setForm({ ...form, message: e.target.value })}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-2">
                                <Label htmlFor="nm-type">{__('general.notice_type')}</Label>
                                <select id="nm-type" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value as NoticeType })}>
                                    <option value="info">Info</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="danger">Danger</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="nm-start">{__('general.start_date')}</Label>
                                <Input id="nm-start" type="date" required value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-2">
                                <Label htmlFor="nm-freq">{__('general.frequency')}</Label>
                                <select id="nm-freq" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={form.recurring} onChange={(e) => setForm({ ...form, recurring: e.target.value as FormState['recurring'] })}>
                                    <option value="day">{__('general.daily')}</option>
                                    <option value="week">{__('general.weekly')}</option>
                                    <option value="month">{__('general.monthly')}</option>
                                    <option value="year">{__('general.annually')}</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="nm-int">Interval (Every N)</Label>
                                <select id="nm-int" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={form.recurring_times} onChange={(e) => setForm({ ...form, recurring_times: parseInt(e.target.value) || 1 })}>
                                    {Array.from({ length: 30 }, (_, i) => i + 1).map((num) => (
                                        <option key={num} value={num}>{num}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {form.recurring === 'week' && (
                            <div className="space-y-2">
                                <Label htmlFor="nm-week">{__('general.specific_week_days')}</Label>
                                <select
                                    id="nm-week"
                                    multiple
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                                    value={form.recurring_times_week}
                                    onChange={(e) => setForm({ ...form, recurring_times_week: Array.from(e.target.selectedOptions, (o) => o.value) })}
                                >
                                    {weekDays.map((wd) => <option key={wd} value={wd}>{wd}</option>)}
                                </select>
                                <span className="text-xs text-slate-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                            </div>
                        )}

                        {form.recurring === 'month' && (
                            <div className="space-y-2">
                                <Label htmlFor="nm-month">{__('general.specific_month_days')}</Label>
                                <select
                                    id="nm-month"
                                    multiple
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                                    value={form.recurring_times_month}
                                    onChange={(e) => setForm({ ...form, recurring_times_month: Array.from(e.target.selectedOptions, (o) => o.value) })}
                                >
                                    {monthDays.map((d) => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                                </select>
                                <span className="text-xs text-slate-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                            </div>
                        )}

                        {form.recurring === 'year' && (
                            <div className="space-y-2">
                                <Label htmlFor="nm-year">{__('general.specific_year_dates')}</Label>
                                <select
                                    id="nm-year"
                                    multiple
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                                    value={form.recurring_times_year}
                                    onChange={(e) => setForm({ ...form, recurring_times_year: Array.from(e.target.selectedOptions, (o) => o.value) })}
                                >
                                    {yearDaysList.map((yd) => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                                </select>
                                <span className="text-xs text-slate-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                            </div>
                        )}

                        <div className="flex justify-end gap-2 border-t pt-4">
                            <Button type="button" variant="outline" onClick={() => setView('list')} className="h-9 text-xs">
                                Cancel
                            </Button>
                            <Button type="submit" disabled={submitting} className="bg-black hover:bg-slate-800 text-white h-9 text-xs">
                                {submitting && <Loader2 className="h-3.5 w-3.5 me-1 animate-spin" />}
                                {form.id ? __('general.edit_recurring_notice') : __('general.create_recurring_notice')}
                            </Button>
                        </div>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function toast(type: 'success' | 'error') {
    if (typeof window === 'undefined') return;
    // Lightweight CSS-only notification fallback so we don't pull in a new dependency.
    const id = `notices-toast-${Date.now()}`;
    const el = document.createElement('div');
    el.id = id;
    el.textContent = type === 'success' ? 'Saved.' : 'Something went wrong.';
    el.className = `fixed bottom-4 right-4 z-[9999] rounded-md px-3 py-2 text-xs font-medium shadow-lg ${type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'}`;
    document.body.appendChild(el);
    window.setTimeout(() => el.remove(), 2200);
}
