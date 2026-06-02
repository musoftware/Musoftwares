import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    ArrowLeft, Clock, DollarSign, Calendar, Play, Pause, Plus, Save, X, 
    PlayCircle, StopCircle, History, User, Folder, Eye, EyeOff, Edit, Link as LinkIcon
} from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
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
        invoice_status: string;
        client_name: string | null;
        client_id: number | null;
        project_name: string | null;
        project_id: number | null;
        date: string | null;
    };
    invoice_currency: Currency | null;
    timers: Timer[];
    total_seconds: number;
    total_billable: number;
    span_seconds: number;
    hour_rate: number;
}

function formatDurationMS(seconds: number): string {
    if (isNaN(seconds) || seconds < 0) return '00:00:00.000';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    const ms = Math.floor((seconds - Math.floor(seconds)) * 1000);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${String(ms).padStart(3, '0')}`;
}

function parseDateTime(dateStr: string | null) {
    if (!dateStr) return { date: '—', time: '—', full: '—' };
    const d = new Date(dateStr);
    const pad = (n: number) => (n < 10 ? '0' + n : n);
    const full = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    return {
        date: d.toLocaleDateString(),
        time: d.toLocaleTimeString(),
        full
    };
}

export default function TimerDetails({ item, invoice_currency, timers: initialTimers, total_seconds, total_billable, span_seconds, hour_rate }: Props) {
    const [timers, setTimers] = useState<Timer[]>(initialTimers || []);
    const [isRunning, setIsRunning] = useState(false);
    const [activeSessionStart, setActiveSessionStart] = useState<Date | null>(null);
    const [liveSeconds, setLiveSeconds] = useState(0);

    const [manualHours, setManualHours] = useState('');
    const [manualMinutes, setManualMinutes] = useState('');
    const [rate, setRate] = useState<number>(hour_rate);
    const [rateVisible, setRateVisible] = useState(false);
    const [reason, setReason] = useState(item.item_title || '');
    const [isSaving, setIsSaving] = useState(false);

    // Bridge State
    const [bridgeStatus, setBridgeStatus] = useState<'disconnected' | 'connecting' | 'connected'>('disconnected');
    const [bridgeAutoMode, setBridgeAutoMode] = useState(false);
    const [debugMsg, setDebugMsg] = useState('');
    const bridgeSocketRef = useRef<WebSocket | null>(null);
    const bridgeToken = 'mu-fixed-token-2026';

    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (isRunning && activeSessionStart) {
            interval = setInterval(() => {
                setLiveSeconds((new Date().getTime() - activeSessionStart.getTime()) / 1000);
            }, 100); // 100ms for fast MS update
        }
        return () => clearInterval(interval);
    }, [isRunning, activeSessionStart]);

    // Cleanup socket
    useEffect(() => {
        return () => {
            if (bridgeSocketRef.current) bridgeSocketRef.current.close();
        };
    }, []);

    const toggleBridge = () => {
        if (bridgeStatus === 'connected' || bridgeStatus === 'connecting') {
            if (bridgeSocketRef.current) bridgeSocketRef.current.close();
            setBridgeStatus('disconnected');
            setBridgeAutoMode(false);
            if (isRunning) handleStop();
        } else {
            setBridgeStatus('connecting');
            try {
                const ws = new WebSocket(`ws://127.0.0.1:17845/ws?token=${bridgeToken}`);
                bridgeSocketRef.current = ws;

                ws.onopen = () => {
                    setBridgeStatus('connected');
                    setBridgeAutoMode(true);
                };

                ws.onmessage = (event) => {
                    const msg = JSON.parse(event.data);
                    if (msg.type === 'event') {
                        if (msg.event === 'started' && !isRunning) handleStart();
                        else if (msg.event === 'stopped' && isRunning) handleStop();
                        setDebugMsg(`event: ${msg.event} (${new Date().toLocaleTimeString()})`);
                    }
                    if (msg.type === 'snapshot' || msg.type === 'state') {
                        if (msg.state === 'active' && !isRunning) handleStart();
                        else if (msg.state === 'idle' && isRunning) handleStop();
                        setDebugMsg(`${msg.type}: ${msg.state} (${new Date().toLocaleTimeString()})`);
                    }
                };

                ws.onclose = () => {
                    setBridgeStatus('disconnected');
                    setBridgeAutoMode(false);
                };

                ws.onerror = (err) => {
                    console.error('Bridge Error:', err);
                };
            } catch (e) {
                setBridgeStatus('disconnected');
            }
        }
    };

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
            isNew: true
        };

        setTimers(prev => [...prev, newSession]);
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
        if (!reason.trim()) {
            alert(__('Reason is empty. You have to type reason.'));
            return;
        }

        const newSessions = timers.filter(t => t.isNew);

        if (!confirm(__('Save timer entries to this invoice?'))) return;

        if (isRunning) handleStop();

        setIsSaving(true);
        router.post(route('admin.invoices.timer-details.store', item.id), {
            sessions: newSessions,
            reason: reason
        }, {
            onSuccess: () => setIsSaving(false),
            onError: () => setIsSaving(false)
        });
    };

    const currentTotalSeconds = timers.reduce((sum, t) => sum + t.duration_seconds, 0) + liveSeconds;
    const currentTotalBillable = timers.reduce((sum, t) => sum + t.amount, 0) + ((liveSeconds / 3600) * rate);

    const firstStartDate = timers.length > 0 ? parseDateTime(timers[0].start_date).full : (activeSessionStart ? parseDateTime(activeSessionStart.toISOString()).full : '—');
    let lastEndDate = '—';
    if (isRunning) lastEndDate = parseDateTime(new Date().toISOString()).full;
    else if (timers.length > 0) lastEndDate = parseDateTime(timers[timers.length - 1].end_date).full;

    const rateUrl = item.project_id ? route('admin.projects.edit', item.project_id) : route('admin.users.edit', item.client_id || 0);

    return (
        <AdminSidebarLayout>
            <Head title={`${__('Timer Details')} - Invoice #${item.invoice_number}`} />

            <div className="max-w-6xl mx-auto pb-12">
                <div className="mb-5">
                    <span className="text-xs font-bold tracking-widest text-indigo-600 uppercase bg-indigo-50 px-2 py-1 rounded mb-2 inline-block">
                        {__('Billing')}
                    </span>
                    <div className="flex items-baseline flex-wrap gap-3">
                        <h1 style={{ fontSize: '2.5rem', fontWeight: 800, letterSpacing: '-0.04em', marginBottom: 0 }} className="text-gray-900">
                            {__('Start Timer')}
                        </h1>
                        <div className="flex flex-wrap gap-2 items-center ml-auto">
                            <Link href={route('admin.invoices.show', item.invoice_id)}>
                                <Button variant="ghost" className="hover:bg-gray-100 text-gray-600">
                                    <ArrowLeft className="w-4 h-4 mr-2" /> {__('Back')}
                                </Button>
                            </Link>
                        </div>
                    </div>
                    <p className="text-gray-500 mt-2 mb-0 font-medium text-base">
                        {__('Invoice')} #{item.invoice_number} · {item.client_name}
                    </p>
                </div>

                {/* Info Cards (Modern Compact Row) */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div className="p-2 border rounded-xl bg-white flex items-center gap-3 shadow-sm">
                        <div className="rounded-full bg-blue-50 p-2 flex items-center justify-center w-10 h-10">
                            <User className="w-4 h-4 text-blue-600" />
                        </div>
                        <div className="overflow-hidden">
                            <small className="text-gray-400 block uppercase font-bold text-[10px] tracking-wider">Client</small>
                            <Link href={route('admin.users.show', item.client_id || 0)} target="_blank" className="font-bold text-gray-900 text-sm truncate block hover:text-blue-600 transition-colors">
                                {item.client_name}
                            </Link>
                        </div>
                    </div>

                    {item.project_name && (
                        <div className="p-2 border rounded-xl bg-white flex items-center gap-3 shadow-sm">
                            <div className="rounded-full bg-amber-50 p-2 flex items-center justify-center w-10 h-10">
                                <Folder className="w-4 h-4 text-amber-600" />
                            </div>
                            <div>
                                <small className="text-gray-400 block uppercase font-bold text-[10px] tracking-wider">Project</small>
                                <span className="font-bold text-gray-900 text-sm">{item.project_name}</span>
                            </div>
                        </div>
                    )}

                    <div className="p-2 border rounded-xl bg-white flex items-center gap-3 shadow-sm">
                        <div className="rounded-full bg-green-50 p-2 flex items-center justify-center w-10 h-10">
                            <Calendar className="w-4 h-4 text-green-600" />
                        </div>
                        <div>
                            <small className="text-gray-400 block uppercase font-bold text-[10px] tracking-wider">Date</small>
                            <span className="font-bold text-gray-900 text-sm">{item.date || parseDateTime(new Date().toISOString()).date}</span>
                        </div>
                    </div>
                </div>

                {/* Timer Component */}
                <Card className="shadow-md border-0 rounded-2xl overflow-hidden">
                    <CardContent className="p-6">
                        
                        {/* Top Controls */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Hour Rate</label>
                                <div className="flex shadow-sm rounded-md">
                                    <Input 
                                        type={rateVisible ? 'number' : 'password'} 
                                        value={rate} 
                                        readOnly 
                                        className="font-mono font-bold tracking-widest rounded-r-none border-r-0 focus-visible:ring-0 bg-gray-50"
                                    />
                                    <Button type="button" variant="outline" className="rounded-none border-l-0 px-3 hover:bg-gray-100" onClick={() => setRateVisible(!rateVisible)}>
                                        {rateVisible ? <EyeOff className="w-4 h-4 text-gray-500" /> : <Eye className="w-4 h-4 text-gray-500" />}
                                    </Button>
                                    <Link href={rateUrl} target="_blank">
                                        <Button type="button" variant="outline" className="rounded-l-none px-3 hover:bg-gray-100 border-l-0">
                                            <Edit className="w-4 h-4 text-blue-600" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Reason / Description</label>
                                <Input 
                                    type="text" 
                                    value={reason} 
                                    onChange={e => setReason(e.target.value)} 
                                    placeholder="What did you work on?" 
                                    onKeyDown={e => { if(e.key === 'Enter') handleStart() }}
                                    className="shadow-sm"
                                    disabled={item.invoice_status !== 'unpaid'}
                                />
                            </div>

                            <div className="md:col-span-2">
                                <div className="flex items-end gap-3 flex-wrap bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                    <div className="w-24">
                                        <label className="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 block">Hours</label>
                                        <Input type="number" min="0" placeholder="0" value={manualHours} onChange={e => setManualHours(e.target.value)} disabled={item.invoice_status !== 'unpaid'} />
                                    </div>
                                    <div className="w-24">
                                        <label className="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 block">Minutes</label>
                                        <Input type="number" min="0" max="59" placeholder="0" value={manualMinutes} onChange={e => setManualMinutes(e.target.value)} disabled={item.invoice_status !== 'unpaid'} />
                                    </div>
                                    <Button type="button" variant="secondary" onClick={handleAddManual} disabled={item.invoice_status !== 'unpaid'}>
                                        <Plus className="w-4 h-4 mr-2" /> Add Duration
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* Sessions Table */}
                        <div className="border rounded-xl overflow-hidden mb-6">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-4 py-2 text-left font-semibold text-gray-600 w-1/4">Start</th>
                                        <th className="px-4 py-2 text-left font-semibold text-gray-600 w-1/4">End</th>
                                        <th className="px-4 py-2 text-left font-semibold text-gray-600 w-1/4">Duration</th>
                                        <th className="px-4 py-2 text-left font-semibold text-gray-600">{invoice_currency?.code || 'Amount'}</th>
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
                                                {timer.isNew && <span className="ml-2 text-[9px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded-sm uppercase tracking-wider">New</span>}
                                            </td>
                                            <td className="px-4 py-2.5 font-bold text-gray-900">{formatCurrency(timer.amount, invoice_currency?.code)}</td>
                                            <td className="px-4 py-2.5 text-center">
                                                {item.invoice_status === 'unpaid' && (
                                                    <button onClick={() => handleDelete(index)} className="text-gray-400 hover:text-red-500 transition-colors">
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
                                                No sessions yet — start the timer to record time.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Control Bar */}
                        <div className="mb-6">
                            {isRunning && (
                                <div className="flex items-center gap-3 bg-red-50 text-red-900 px-4 py-3 rounded-lg mb-4 border border-red-100 font-mono text-sm">
                                    <div className="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></div>
                                    <span className="font-semibold uppercase tracking-wider text-xs">Timer running —</span>
                                    <span className="font-bold text-base">{formatDurationMS(liveSeconds)}</span>
                                    <span className="ml-auto font-bold">{formatCurrency((liveSeconds / 3600) * rate, invoice_currency?.code)}</span>
                                </div>
                            )}

                            <div className="flex items-center flex-wrap gap-3">
                                <Button 
                                    onClick={handleStart} 
                                    disabled={isRunning || item.invoice_status !== 'unpaid'} 
                                    className="bg-blue-600 hover:bg-blue-700 shadow-sm"
                                >
                                    <Play className="w-4 h-4 mr-2" /> Start
                                </Button>
                                <Button 
                                    onClick={handleStop} 
                                    disabled={!isRunning} 
                                    variant="outline" 
                                    className="border-gray-300 text-gray-700 hover:bg-gray-50"
                                >
                                    <Pause className="w-4 h-4 mr-2" /> Pause
                                </Button>

                                <div className="flex items-center gap-2 ml-2 pl-2 border-l border-gray-200">
                                    <Button
                                        variant="outline"
                                        onClick={toggleBridge}
                                        className={`transition-colors ${
                                            bridgeStatus === 'connected' ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100' : 
                                            (bridgeStatus === 'connecting' ? 'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100' : 'bg-gray-50 text-gray-600')
                                        }`}
                                    >
                                        <div className={`w-2 h-2 rounded-full mr-2 ${
                                            bridgeStatus === 'connected' ? 'bg-green-500' : 
                                            (bridgeStatus === 'connecting' ? 'bg-amber-500 animate-pulse' : 'bg-gray-400')
                                        }`}></div>
                                        {bridgeStatus === 'connected' ? 'Bridge On' : (bridgeStatus === 'connecting' ? 'Connecting…' : 'Bridge Off')}
                                    </Button>
                                    {bridgeStatus === 'connected' && debugMsg && (
                                        <span className="text-xs text-gray-400 font-mono hidden sm:inline-block max-w-xs truncate">{debugMsg}</span>
                                    )}
                                </div>

                                <div className="ml-auto">
                                    <Button 
                                        onClick={handleSave} 
                                        disabled={isSaving || item.invoice_status !== 'unpaid'} 
                                        className="bg-gray-900 hover:bg-gray-800 text-white shadow-sm px-6"
                                    >
                                        <Save className="w-4 h-4 mr-2" /> {__('Save')}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* Summary Bar */}
                        <div className="bg-gray-50 rounded-xl p-4 flex flex-wrap gap-4 sm:gap-8 border border-gray-100">
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Start Date</span>
                                <div className="font-mono text-sm text-gray-800">{firstStartDate}</div>
                            </div>
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">End Date</span>
                                <div className="font-mono text-sm text-gray-800">{lastEndDate}</div>
                            </div>
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Total Time</span>
                                <div className="font-mono text-lg font-bold text-blue-700">{formatDurationMS(currentTotalSeconds)}</div>
                            </div>
                            <div className="flex-1 min-w-[120px]">
                                <span className="block text-[10px] font-bold text-green-600 uppercase tracking-wider mb-1">{invoice_currency?.code || 'Amount'}</span>
                                <div className="text-lg font-black text-green-700">{formatCurrency(currentTotalBillable, invoice_currency?.code)}</div>
                            </div>
                        </div>

                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
