import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Clock, DollarSign, Calendar, Play, Pause, Plus, Save, X } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

interface Timer {
    id: number | string;
    start_date: string;
    end_date: string;
    amount: number;
    duration_seconds: number;
    isNew?: boolean;
}

interface Props {
    item: {
        id: number;
        item_title: string;
        invoice_id: number;
        invoice_number: string | null;
        client_name: string | null;
    };
    timers: Timer[];
    total_seconds: number;
    total_billable: number;
    hour_rate: number;
}

function formatDuration(seconds: number): string {
    if (isNaN(seconds) || seconds < 0) return '00:00:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleString();
}

export default function TimerDetails({ item, timers: initialTimers, total_seconds, total_billable, hour_rate }: Props) {
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
            if (confirm('Are you sure you want to delete this session?')) {
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
                // On success, the backend should reload the page via redirect, 
                // but we clear the local new markers if needed.
                // Wait, Inertia handles the reload automatically.
            },
            onError: () => {
                setIsSaving(false);
            }
        });
    };

    const currentTotalSeconds = timers.reduce((sum, t) => sum + t.duration_seconds, 0) + liveSeconds;
    const currentTotalBillable = timers.reduce((sum, t) => sum + t.amount, 0) + ((liveSeconds / 3600) * rate);
    
    const hasUnsavedChanges = timers.some(t => t.isNew);

    return (
        <AdminSidebarLayout>
            <Head title={`Timer Details — ${item.item_title}`} />

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.invoices.show', item.invoice_id)}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-2" /> Back to Invoice
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-black text-gray-900">{item.item_title}</h1>
                        <p className="text-sm text-gray-500">
                            Invoice #{item.invoice_number} • {item.client_name}
                        </p>
                    </div>
                </div>
                
                <div className="flex items-center gap-2">
                    {hasUnsavedChanges && (
                        <div className="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-200">
                            Unsaved Sessions
                        </div>
                    )}
                    <Button onClick={handleSave} disabled={!hasUnsavedChanges || isSaving} className="bg-blue-600 hover:bg-blue-700">
                        <Save className="w-4 h-4 mr-2" /> Save Sessions
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
                                    <Play className="w-5 h-5 mr-2" /> Start Timer
                                </Button>
                            ) : (
                                <Button size="lg" onClick={handleStop} variant="destructive" className="h-14 px-8 shadow-sm animate-pulse">
                                    <Pause className="w-5 h-5 mr-2" /> Pause Timer
                                </Button>
                            )}
                            
                            {isRunning && (
                                <div className="flex flex-col">
                                    <span className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1 flex items-center">
                                        <span className="w-2 h-2 rounded-full bg-red-500 mr-2 animate-ping"></span>
                                        Running Session
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
                                <label className="text-xs font-bold text-gray-500 uppercase mb-1 block">Hourly Rate</label>
                                <Input 
                                    type="number" 
                                    min="0"
                                    value={rate} 
                                    onChange={(e) => setRate(parseFloat(e.target.value) || 0)}
                                    className="w-20 bg-white"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-bold text-gray-500 uppercase mb-1 block">Hours</label>
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
                                <label className="text-xs font-bold text-gray-500 uppercase mb-1 block">Mins</label>
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
                                <Plus className="w-4 h-4 mr-2 text-blue-500" /> Add Duration
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <Clock className="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tracked Time</p>
                            <p className="text-2xl font-black text-gray-900 tabular-nums">{formatDuration(currentTotalSeconds)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <DollarSign className="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Billable</p>
                            <p className="text-2xl font-black text-gray-900">{currentTotalBillable.toFixed(2)}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardContent className="p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <Calendar className="w-6 h-6 text-purple-600" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Sessions</p>
                            <p className="text-2xl font-black text-gray-900">{timers.length}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Sessions Table */}
            <Card className="shadow-sm overflow-hidden">
                <CardHeader className="bg-gray-50 border-b py-3">
                    <CardTitle className="text-base font-bold text-gray-900">Timer Sessions</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Start</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">End</th>
                                    <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Earnings</th>
                                    <th className="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {timers.map((timer, index) => (
                                    <tr key={timer.id} className={`transition-colors ${timer.isNew ? 'bg-amber-50/50 hover:bg-amber-50' : 'hover:bg-gray-50'}`}>
                                        <td className="px-4 py-3 text-gray-500 font-mono text-xs">
                                            {index + 1}
                                            {timer.isNew && <span className="ml-2 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">NEW</span>}
                                        </td>
                                        <td className="px-4 py-3 text-gray-900 font-medium">{formatDate(timer.start_date)}</td>
                                        <td className="px-4 py-3 text-gray-900 font-medium">{formatDate(timer.end_date)}</td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 tabular-nums">
                                                {formatDuration(timer.duration_seconds)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right font-bold text-gray-900 tabular-nums">{timer.amount.toFixed(2)}</td>
                                        <td className="px-4 py-3 text-center">
                                            <Button variant="ghost" size="sm" onClick={() => handleDelete(index)} className="h-8 w-8 p-0 text-gray-400 hover:text-red-600 hover:bg-red-50">
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {timers.length === 0 && !isRunning && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                            No timer sessions recorded. Click "Start Timer" or add a manual duration.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                            {(timers.length > 0 || isRunning) && (
                                <tfoot className="bg-gray-50 border-t">
                                    <tr>
                                        <td colSpan={3} className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Totals</td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center rounded bg-gray-200 px-2 py-1 text-xs font-bold text-gray-800 tabular-nums">
                                                {formatDuration(currentTotalSeconds)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right font-black text-gray-900 tabular-nums">{currentTotalBillable.toFixed(2)}</td>
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
