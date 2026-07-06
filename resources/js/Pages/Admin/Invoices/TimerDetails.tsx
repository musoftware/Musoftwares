import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, Clock, Calendar as CalendarIcon, Play, Pause, Plus, Save, X,
    Edit, Link as LinkIcon,
} from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Timer {
    id: number | string;
    start_date: string;
    end_date: string;
    amount: number;
    duration_seconds: number;
    isNew?: boolean;
}

interface Currency {
    id: number;
    currency: string;
    symbol: string | null;
}

interface Props {
    item: any;
    invoice_currency: Currency | null;
    timers: Timer[];
    total_seconds: number;
    total_billable: number;
    span_seconds: number;
    system_base_rate: number;
    client_rate: number;
    hour_rate: number;
}

function formatDurationMS(seconds: number): string {
    if (isNaN(seconds) || seconds < 0) return '00:00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function parseDateTime(dateStr: string | null) {
    if (!dateStr) return { date: '—', time: '—', full: '—' };
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return { date: '—', time: '—', full: '—' };
    const pad = (n: number) => (n < 10 ? '0' + n : String(n));
    const full = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    return {
        date: d.toLocaleDateString(),
        time: d.toLocaleTimeString(),
        full,
    };
}

interface TimerCache {
    isRunning: boolean;
    activeSessionStart: string | null;
    timers: Timer[];
    reason: string;
    rate: number;
}

export default function TimerDetails({
    item, invoice_currency, timers: initialTimers, total_seconds, total_billable, span_seconds,
    system_base_rate, client_rate, hour_rate,
}: Props) {
    const storageKey = `timer-details-${item.id}`;

    const loadCache = (): TimerCache | null => {
        if (typeof window === 'undefined') return null;
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) return null;
            const parsed = JSON.parse(raw) as TimerCache;
            if (parsed && Array.isArray(parsed.timers)) return parsed;
        } catch {
            return null;
        }
        return null;
    };

    const cache = loadCache();

    const [timers, setTimers] = useState<Timer[]>(
        cache?.timers && cache.timers.length > 0 ? cache.timers : (initialTimers || [])
    );
    const [isRunning, setIsRunning] = useState<boolean>(cache?.isRunning ?? false);
    const [activeSessionStart, setActiveSessionStart] = useState<Date | null>(
        cache?.activeSessionStart ? new Date(cache.activeSessionStart) : null
    );
    const [liveSeconds, setLiveSeconds] = useState(0);

    const [manualHours, setManualHours] = useState('');
    const [manualMinutes, setManualMinutes] = useState('');
    const [rate, setRate] = useState<number>(cache?.rate ?? hour_rate);
    const [rateVisible, setRateVisible] = useState(false);
    const [reason, setReason] = useState(cache?.reason ?? (item.item_title || ''));
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const payload: TimerCache = {
            isRunning,
            activeSessionStart: activeSessionStart ? activeSessionStart.toISOString() : null,
            timers,
            reason,
            rate,
        };
        try {
            localStorage.setItem(storageKey, JSON.stringify(payload));
        } catch {
            // ignore quota errors
        }
    }, [storageKey, isRunning, activeSessionStart, timers, reason, rate]);

    useEffect(() => {
        if (!isRunning || !activeSessionStart) return;
        let raf: number;
        const tick = () => {
            setLiveSeconds((Date.now() - activeSessionStart.getTime()) / 1000);
            raf = requestAnimationFrame(tick);
        };
        raf = requestAnimationFrame(tick);
        return () => cancelAnimationFrame(raf);
    }, [isRunning, activeSessionStart]);

    const handleStart = () => {
        if (isRunning) return;
        setIsRunning(true);
        setActiveSessionStart(new Date());
        setLiveSeconds(0);
    };

    const handleStop = () => {
        if (!isRunning || !activeSessionStart) return;
        const end = new Date();
        const duration = (end.getTime() - activeSessionStart.getTime()) / 1000;
        const amount = (duration / 3600) * rate;

        const newSession: Timer = {
            id: 'new-' + crypto.randomUUID(),
            start_date: activeSessionStart.toISOString(),
            end_date: end.toISOString(),
            duration_seconds: duration,
            amount: parseFloat(amount.toFixed(3)),
            isNew: true,
        };

        setTimers((prev) => [...prev, newSession]);
        setIsRunning(false);
        setActiveSessionStart(null);
        setLiveSeconds(0);
    };

    const handleAddManual = () => {
        const h = parseInt(manualHours) || 0;
        const m = parseInt(manualMinutes) || 0;
        if (h === 0 && m === 0) return;
        const duration = (h * 3600) + (m * 60);
        const amount = (duration / 3600) * rate;
        const end = new Date();
        const start = new Date(end.getTime() - (duration * 1000));
        const newSession: Timer = {
            id: 'new-' + crypto.randomUUID(),
            start_date: start.toISOString(),
            end_date: end.toISOString(),
            duration_seconds: duration,
            amount: parseFloat(amount.toFixed(3)),
            isNew: true,
        };
        setTimers((prev) => [...prev, newSession]);
        setManualHours('');
        setManualMinutes('');
    };

    const handleDelete = (index: number) => {
        const timerToDelete = timers[index];
        if (!timerToDelete.isNew) {
            if (!confirm(__('general.are_you_sure_you_want_to_delete_this_session'))) return;
            const newTimers = [...timers];
            newTimers.splice(index, 1);
            setTimers(newTimers);
            router.delete(route('admin.invoices.timer-details.destroy', [item.id, timerToDelete.id]), {
                preserveScroll: true,
                onError: () => {
                    setTimers(timers);
                    toast.error(__('general.error_occurred') || 'Something went wrong');
                },
            });
        } else {
            const newTimers = [...timers];
            newTimers.splice(index, 1);
            setTimers(newTimers);
        }
    };

    const handleSave = () => {
        if (!reason.trim()) {
            toast.error(__('general.reason_is_empty_you_have') || 'Reason is required.');
            return;
        }
        const newSessions = timers.filter((t) => t.isNew);
        if (newSessions.length === 0) {
            toast.error(__('general.no_new_sessions_to_save') || 'No new sessions to save.');
            return;
        }
        if (isRunning) handleStop();
        setIsSaving(true);
        router.post(route('admin.invoices.timer-details.store', item.id), {
            sessions: newSessions as any,
            reason,
        }, {
            onSuccess: () => {
                setIsSaving(false);
                try { localStorage.removeItem(storageKey); } catch { /* empty */ }
                toast.success(__('general.saved') || 'Saved');
            },
            onError: () => setIsSaving(false),
        });
    };

    const currentTotalSeconds = timers.reduce((sum, t) => sum + t.duration_seconds, 0) + liveSeconds;
    const currentTotalBillable = timers.reduce((sum, t) => sum + t.amount, 0) + ((liveSeconds / 3600) * rate);

    const firstStartDate = timers.length > 0
        ? parseDateTime(timers[0].start_date).full
        : (activeSessionStart ? parseDateTime(activeSessionStart.toISOString()).full : '—');

    let lastEndDate = '—';
    if (isRunning) lastEndDate = parseDateTime(new Date().toISOString()).full;
    else if (timers.length > 0) lastEndDate = parseDateTime(timers[timers.length - 1].end_date).full;

    const rateUrl = item.project_id
        ? route('admin.projects.edit', item.project_id)
        : route('admin.users.edit', item.client_id || 0);

    return (
        <AdminSidebarLayout>
            <Head title={`${__('general.timer_details')} - Invoice #${item.invoice_number}`} />

            <div className="max-w-7xl mx-auto pb-12">
                <div className="mb-5">
                    <span className="text-xs font-bold tracking-widest text-slate-900 uppercase bg-slate-50 px-2 py-1 rounded mb-2 inline-block">
                        {__('billing.billing')}
                    </span>
                    <div className="flex items-baseline flex-wrap gap-3">
                        <h1 className="text-3xl font-extrabold tracking-tight text-gray-900">
                            {__('general.start_timer')}
                        </h1>
                        <div className="flex flex-wrap gap-2 items-center ms-auto">
                            <Link href={route('admin.invoices.show', item.invoice_id)}>
                                <Button variant="ghost" className="hover:bg-gray-100 text-gray-600">
                                    <ArrowLeft className="w-4 h-4 me-2" /> {__('general.back')}
                                </Button>
                            </Link>
                        </div>
                    </div>
                    <p className="text-gray-500 mt-2 mb-0 font-medium text-base">
                        {__('erp.invoice_2')} #{item.invoice_number} · {item.client_name}
                    </p>
                </div>

                <Card className="shadow-md border-0 rounded-2xl overflow-hidden">
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">
                                    {__('general.hour_rate')}
                                </label>
                                <div className="flex flex-col gap-2 mb-2.5">
                                    <div className="flex items-center gap-4 text-xs font-medium">
                                        <label className="flex items-center gap-1.5 cursor-pointer text-gray-700">
                                            <input
                                                type="radio"
                                                name="rateType"
                                                checked={rate === system_base_rate}
                                                onChange={() => setRate(system_base_rate)}
                                                className="w-3.5 h-3.5 text-slate-900 focus:ring-slate-900 border-gray-300"
                                                disabled={item.invoice_status !== 'unpaid'}
                                            />
                                            {__('admin.base_system_rate')} ({system_base_rate})
                                        </label>
                                        <label className={`flex items-center gap-1.5 cursor-pointer ${client_rate > 0 ? 'text-gray-700' : 'text-gray-400'}`}>
                                            <input
                                                type="radio"
                                                name="rateType"
                                                checked={rate === client_rate}
                                                onChange={() => setRate(client_rate)}
                                                className="w-3.5 h-3.5 text-slate-900 focus:ring-slate-900 border-gray-300"
                                                disabled={item.invoice_status !== 'unpaid' || client_rate <= 0}
                                            />
                                            {__('admin.client_rate')} ({client_rate > 0 ? client_rate : __('general.not_set')})
                                        </label>
                                    </div>
                                </div>
                                <div className="flex shadow-sm rounded-md">
                                    <Input
                                        type={rateVisible ? 'number' : 'password'}
                                        value={rate}
                                        onChange={(e) => setRate(parseFloat(e.target.value) || 0)}
                                        disabled={item.invoice_status !== 'unpaid'}
                                        className="font-mono font-bold tracking-widest rounded-e-none border-e-0 focus-visible:ring-0 bg-gray-50"
                                    />
                                    <Button type="button" variant="outline" className="rounded-none border-s-0 px-3 hover:bg-gray-100" onClick={() => setRateVisible((v) => !v)} aria-label={rateVisible ? __('general.hide_rate') : __('general.show_rate')}>
                                        {rateVisible ? <X className="w-4 h-4 text-gray-500" /> : <LinkIcon className="w-4 h-4 text-gray-500" />}
                                    </Button>
                                    <Link href={rateUrl} target="_blank">
                                        <Button type="button" variant="outline" className="rounded-s-none px-3 hover:bg-gray-100 border-s-0" aria-label={__('general.edit')}>
                                            <Edit className="w-4 h-4 text-slate-900" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">
                                    {__('general.reason_description')}
                                </label>
                                <Input
                                    type="text"
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder={__('general.what_did_you_work_on')}
                                    onKeyDown={(e) => { if (e.key === 'Enter') handleStart(); }}
                                    className="shadow-sm"
                                    disabled={item.invoice_status !== 'unpaid'}
                                />
                            </div>

                            <div className="md:col-span-2">
                                <div className="flex items-end gap-3 flex-wrap bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                    <div className="w-24">
                                        <label className="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 block">{__('general.hours')}</label>
                                        <Input type="number" min="0" placeholder="0" value={manualHours} onChange={(e) => setManualHours(e.target.value)} disabled={item.invoice_status !== 'unpaid'} />
                                    </div>
                                    <div className="w-24">
                                        <label className="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 block">{__('general.minutes')}</label>
                                        <Input type="number" min="0" max="59" placeholder="0" value={manualMinutes} onChange={(e) => setManualMinutes(e.target.value)} disabled={item.invoice_status !== 'unpaid'} />
                                    </div>
                                    <Button type="button" variant="secondary" onClick={handleAddManual} disabled={item.invoice_status !== 'unpaid'}>
                                        <Plus className="w-4 h-4 me-2" /> {__('general.add_duration')}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="border rounded-xl overflow-hidden mb-6">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-4 py-2 text-start font-semibold text-gray-600 w-1/4">{__('general.start')}</th>
                                        <th className="px-4 py-2 text-start font-semibold text-gray-600 w-1/4">End</th>
                                        <th className="px-4 py-2 text-start font-semibold text-gray-600 w-1/4">{__('general.duration')}</th>
                                        <th className="px-4 py-2 text-start font-semibold text-gray-600">{invoice_currency?.currency || 'Amount'}</th>
                                        <th className="px-4 py-2 w-12"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {timers.map((timer, index) => (
                                        <tr key={timer.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-2.5 font-mono text-xs text-gray-600">{parseDateTime(timer.start_date).full}</td>
                                            <td className="px-4 py-2.5 font-mono text-xs text-gray-600">{parseDateTime(timer.end_date).full}</td>
                                            <td className="px-4 py-2.5 font-mono text-xs font-medium">
                                                {formatDurationMS(timer.duration_seconds)}
                                                {timer.isNew && <span className="ms-2 text-[9px] font-bold bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-sm uppercase tracking-wider">New</span>}
                                            </td>
                                            <td className="px-4 py-2.5 font-bold text-gray-900">{formatMoney(timer.amount, invoice_currency)}</td>
                                            <td className="px-4 py-2.5 text-center">
                                                {item.invoice_status === 'unpaid' && (
                                                    <button onClick={() => handleDelete(index)} className="text-gray-400 hover:text-red-500 transition-colors" aria-label={__('general.delete')}>
                                                        <X className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {timers.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-6 text-center text-gray-400 text-sm">
                                                <Clock className="w-5 h-5 mx-auto mb-2 opacity-50" />
                                                {__('general.no_sessions_yet') || 'No sessions yet — start the timer to record time.'}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mb-6">
                            {isRunning && (
                                <div className="flex items-center gap-3 bg-red-50 text-red-900 px-4 py-3 rounded-lg mb-4 border border-red-100 font-mono text-sm motion-reduce:animate-none">
                                    <div className="w-2.5 h-2.5 rounded-full bg-red-500 motion-reduce:animate-none" />
                                    <span className="font-semibold uppercase tracking-wider text-xs">{__('general.timer_running')}</span>
                                    <span className="font-bold text-base">{formatDurationMS(liveSeconds)}</span>
                                    <span className="ms-auto font-bold">{formatMoney((liveSeconds / 3600) * rate, invoice_currency)}</span>
                                </div>
                            )}

                            <div className="flex items-center flex-wrap gap-3">
                                <Button
                                    onClick={handleStart}
                                    disabled={isRunning || item.invoice_status !== 'unpaid'}
                                    className="bg-slate-900 hover:bg-slate-900 shadow-sm"
                                >
                                    <Play className="w-4 h-4 me-2" /> {__('general.start')}
                                </Button>
                                <Button
                                    onClick={handleStop}
                                    disabled={!isRunning}
                                    variant="outline"
                                    className="border-gray-300 text-gray-700 hover:bg-gray-50"
                                >
                                    <Pause className="w-4 h-4 me-2" /> {__('general.pause')}
                                </Button>

                                <div className="ms-auto">
                                    <Button
                                        onClick={handleSave}
                                        disabled={isSaving || item.invoice_status !== 'unpaid'}
                                        className="bg-gray-900 hover:bg-gray-800 text-white shadow-sm px-6"
                                    >
                                        <Save className="w-4 h-4 me-2" /> {__('general.save')}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="bg-gray-50 rounded-xl p-4 flex flex-wrap gap-4 sm:gap-8 border border-gray-100">
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{__('general.start_date')}</span>
                                <div className="font-mono text-sm text-gray-800 break-words">{firstStartDate}</div>
                            </div>
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{__('general.end_date')}</span>
                                <div className="font-mono text-sm text-gray-800 break-words">{lastEndDate}</div>
                            </div>
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-slate-900 uppercase tracking-wider mb-1">{__('general.total_time')}</span>
                                <div className="font-mono text-lg font-bold text-slate-900">{formatDurationMS(currentTotalSeconds)}</div>
                            </div>
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-green-600 uppercase tracking-wider mb-1">{invoice_currency?.currency || 'Amount'}</span>
                                <div className="text-lg font-black text-green-700">{formatMoney(currentTotalBillable, invoice_currency)}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}