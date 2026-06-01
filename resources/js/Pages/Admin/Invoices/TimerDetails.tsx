import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Clock, DollarSign, Calendar, Play, Pause, Plus, Save, X, PlayCircle, StopCircle, History } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { formatMoney as formatCurrency } from '@/lib/utils';
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
    code: string;
    symbol: string | null;
}

interface Props {
    item: {
        id: number;
        item_title: string;
        invoice_id: number;
        invoice_number: string | null;
        client_name: string | null;
    };
    invoice_currency: Currency | null;
    timers: Timer[];
    total_seconds: number;
    total_billable: number;
    span_seconds: number;
    hour_rate: number;
}

function formatDuration(seconds: number): string {
    if (isNaN(seconds) || seconds < 0) return '00:00:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function formatHumanDuration(seconds: number): string {
    if (isNaN(seconds) || seconds < 0) return '0s';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    const parts = [];
    if (h > 0) parts.push(`${h}h`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}s`);
    return parts.join(' ');
}

function parseDateTime(dateStr: string | null) {
    if (!dateStr) return { date: '—', time: '—' };
    const d = new Date(dateStr);
    return {
        date: d.toLocaleDateString(),
        time: d.toLocaleTimeString()
    };
}

export default function TimerDetails({ item, invoice_currency, timers: initialTimers, total_seconds, total_billable, span_seconds, hour_rate }: Props) {
    const [timers, setTimers] = useState<Timer[]>(initialTimers || []);
    const [isRunning, setIsRunning] = useState(false);
    const [activeSessionStart, setActiveSessionStart] = useState<Date | null>(null);
    const [liveSeconds, setLiveSeconds] = useState(0);

    // Manual Duration State
    const [manualHours, setManualHours] = useState('');
    const [manualMinutes, setManualMinutes] = useState('');
    const [rate, setRate] = useState<number>(hour_rate);
    const [isSaving, setIsSaving] = useState(false);

    // Live ticker effect
    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (isRunning && activeSessionStart) {
            interval = setInterval(() => {
                setLiveSeconds(Math.floor((new Date().getTime() - activeSessionStart.getTime()) / 1000));
            }, 1000);
        }
        return () => clearInterval(interval);
    }, [isRunning, activeSessionStart]);

    const handleStart = () => {
        setIsRunning(true);
        setActiveSessionStart(new Date());
        setLiveSeconds(0);
    };

    const handleStop = () => {
        if (!isRunning || !activeSessionStart) return;
        const end = new Date();
        const duration = Math.floor((end.getTime() - activeSessionStart.getTime()) / 1000);
        const amount = (duration / 3600) * rate;

        const newSession: Timer = {
            id: 'new-' + crypto.randomUUID(),
            start_date: activeSessionStart.toISOString(),
            end_date: end.toISOString(),
            duration_seconds: duration,
            amount: parseFloat(amount.toFixed(2)),
            isNew: true
        };

        setTimers([...timers, newSession]);
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
            amount: parseFloat(amount.toFixed(2)),
            isNew: true
        };

        setTimers([...timers, newSession]);
        setManualHours('');
        setManualMinutes('');
    };

    const handleDelete = (index: number) => {
        const timerToDelete = timers[index];
        if (!timerToDelete.isNew) {
            if (confirm(__('general.are_you_sure_you_want_to_delete_this_session'))) {
                router.delete(route('admin.invoices.timer-details.destroy', [item.id, timerToDelete.id]), {
                    onSuccess: () => {
                        const newTimers = [...timers];
                        newTimers.splice(index, 1);
                        setTimers(newTimers);
                    }
                });
            }
        } else {
            const newTimers = [...timers];
            newTimers.splice(index, 1);
            setTimers(newTimers);
        }
    };

    const handleSave = () => {
        const newSessions = timers.filter(t => t.isNew);
        if (newSessions.length === 0) return;

        setIsSaving(true);
        router.post(route('admin.invoices.timer-details.store', item.id), {
            sessions: newSessions
        }, {
            onSuccess: () => {
                setIsSaving(false);
            },
            onError: () => {
                setIsSaving(false);
            }
        });
    };

    const currentTotalSeconds = timers.reduce((sum, t) => sum + t.duration_seconds, 0) + liveSeconds;
    const currentTotalBillable = timers.reduce((sum, t) => sum + t.amount, 0) + ((liveSeconds / 3600) * rate);

    // Live Span calculation
    const allStarts = timers.map(t => new Date(t.start_date).getTime());
    if (activeSessionStart) allStarts.push(activeSessionStart.getTime());

    const allEnds = timers.map(t => new Date(t.end_date).getTime());
    if (activeSessionStart) allEnds.push(new Date().getTime());

    const currentSpanSeconds = (allStarts.length > 0 && allEnds.length > 0)
        ? Math.floor((Math.max(...allEnds) - Math.min(...allStarts)) / 1000)
        : span_seconds;

    const hasUnsavedChanges = timers.some(t => t.isNew);

    return (
        <AdminSidebarLayout>
            <Head title={`${__('general.timer_details')} — ${item.item_title}`} />

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.invoices.show', item.invoice_id)}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-2" /> {__('general.back_to_invoice')}
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-black text-gray-900">{item.item_title}</h1>
                        <p className="text-sm text-gray-500">
                            {__('Invoice')} #{item.invoice_number} • {item.client_name}
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    {hasUnsavedChanges && (
                        <div className="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-200">
                            {__('general.unsaved_sessions')}
                        </div>
                    )}
                    <Button onClick={handleSave} disabled={!hasUnsavedChanges || isSaving} className="bg-blue-600 hover:bg-blue-700">
                        <Save className="w-4 h-4 mr-2" /> {__('general.save_sessions')}
                    </Button>
                </div>
            </div>

            {/* Controls Bar */}
            <Card className="shadow-sm mb-6 bg-white border-blue-100">
                <CardContent className="p-4 sm:p-5">
                    <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-6">

                        {/* Live Timer Controls */}
                        <div className="flex items-center gap-4">
                            {!isRunning ? (
                                <Button size="lg" onClick={handleStart} className="bg-green-600 hover:bg-green-700 h-14 px-8 shadow-sm">
                                    <Play className="w-5 h-5 mr-2" /> {__('general.start_timer')}
                                </Button>
                            ) : (
                                <Button size="lg" onClick={handleStop} variant="destructive" className="h-14 px-8 shadow-sm animate-pulse">
                                    <Pause className="w-5 h-5 mr-2" /> {__('general.pause_timer')}
                                </Button>
                            )}

                            {isRunning && (
                                <div className="flex flex-col">
                                    <span className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1 flex items-center">
                                        <span className="w-2 h-2 rounded-full bg-red-500 mr-2 animate-ping"></span>
                                        {__('general.running_session')}
                                    </span>
                                    <span className="text-2xl font-black text-gray-900 tabular-nums">
                                        {formatDuration(liveSeconds)}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Manual Duration Entry */}
                        <div className="flex flex-wrap items-end gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase mb-1 block">{__('Hourly Rate')}</label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={rate}
                                    onChange={(e) => setRate(parseFloat(e.target.value) || 0)}
                                    className="w-20 bg-white"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase mb-1 block">{__('general.hours')}</label>
                                <Input
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    value={manualHours}
                                    onChange={(e) => setManualHours(e.target.value)}
                                    className="w-16 bg-white"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase mb-1 block">{__('general.mins')}</label>
                                <Input
                                    type="number"
                                    min="0"
                                    max="59"
                                    placeholder="0"
                                    value={manualMinutes}
                                    onChange={(e) => setManualMinutes(e.target.value)}
                                    className="w-16 bg-white"
                                />
                            </div>
                            <Button onClick={handleAddManual} variant="outline" className="bg-white border-dashed">
                                <Plus className="w-4 h-4 mr-2 text-blue-500" /> {__('general.add_duration')}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <Clock className="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_tracked_time')}</p>
                            <p className="text-2xl font-black text-gray-900 tabular-nums">{formatDuration(currentTotalSeconds)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <History className="w-6 h-6 text-indigo-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_period_first_to_last')}</p>
                            <p className="text-2xl font-black text-gray-900 tabular-nums">{formatDuration(currentSpanSeconds)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <DollarSign className="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_billable')}</p>
                            <p className="text-2xl font-black text-green-700">{formatCurrency(currentTotalBillable, invoice_currency?.code)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <Calendar className="w-6 h-6 text-purple-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_sessions')}</p>
                            <p className="text-2xl font-black text-gray-900">{timers.length}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Sessions Table */}
            <Card className="shadow-sm overflow-hidden">
                <CardHeader className="bg-gray-50 border-b py-3 flex flex-row items-center justify-between">
                    <CardTitle className="text-base font-bold text-gray-900 flex items-center">
                        <Clock className="w-4 h-4 mr-2 text-primary" />
                        {__('general.logged_sessions')} ({timers.length})
                    </CardTitle>
                    <span className="badge bg-white text-gray-600 border rounded-full px-3 py-1 text-xs flex items-center shadow-sm">
                        <History className="w-3 h-3 mr-1" /> {__('History')}
                    </span>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.start_session')}</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.end_session')}</th>
                                    <th className="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">{__('Duration')}</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.earnings')}</th>
                                    <th className="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {timers.map((timer, index) => {
                                    const start = parseDateTime(timer.start_date);
                                    const end = parseDateTime(timer.end_date);

                                    return (
                                        <tr key={timer.id} className={`transition-colors ${timer.isNew ? 'bg-amber-50/50 hover:bg-amber-50' : 'hover:bg-gray-50'}`}>
                                            <td className="px-4 py-3 text-gray-500 font-mono text-xs">
                                                {index + 1}
                                                {timer.isNew && <span className="ml-2 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">{__('general.new')}</span>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center">
                                                    <div className="mr-2 text-green-600">
                                                        <PlayCircle className="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <div className="font-bold text-gray-900 text-sm">{start.date}</div>
                                                        <div className="text-gray-500 text-xs">{start.time}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center">
                                                    <div className="mr-2 text-red-500">
                                                        <StopCircle className="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <div className="font-bold text-gray-900 text-sm">{end.date}</div>
                                                        <div className="text-gray-500 text-xs">{end.time}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span className="inline-flex items-center rounded-full border bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">
                                                    {formatHumanDuration(timer.duration_seconds)}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right font-bold text-gray-900 tabular-nums">
                                                {formatCurrency(timer.amount, invoice_currency?.code)}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Button variant="ghost" size="sm" onClick={() => handleDelete(index)} className="h-8 w-8 p-0 text-gray-400 hover:text-red-600 hover:bg-red-50">
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {timers.length === 0 && !isRunning && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                            {__('No timer sessions recorded. Click "Start Timer" or add a manual duration.')}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                            {(timers.length > 0 || isRunning) && (
                                <tfoot className="bg-gray-50 border-t">
                                    <tr>
                                        <td colSpan={3} className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">{__('general.totals')}</td>
                                        <td className="px-4 py-3 text-center">
                                            <span className="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800 tabular-nums">
                                                {formatHumanDuration(currentTotalSeconds)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right font-black text-gray-900 tabular-nums">
                                            {formatCurrency(currentTotalBillable, invoice_currency?.code)}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminSidebarLayout>
    );
}
