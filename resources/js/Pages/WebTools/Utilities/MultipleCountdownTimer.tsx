import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Plus, Play, Pause, RotateCcw, X, Timer, Info } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

interface TimerData {
    id: string;
    label: string;
    hours: number;
    minutes: number;
    seconds: number;
    totalSeconds: number;
    remainingSeconds: number;
    isRunning: boolean;
}

export default function MultipleCountdownTimer() {
    const { toast } = useToast();
    const [newLabel, setNewLabel] = useState('');
    const [timers, setTimers] = useState<TimerData[]>([]);
    
    // We use a ref to hold an audio element so we don't trigger re-renders
    const audioRef = useRef<HTMLAudioElement | null>(null);

    const addTimer = useCallback((label?: string) => {
        setTimers(prev => {
            const defaultLabel = label || `Timer ${prev.length + 1}`;
            const newTimer: TimerData = {
                id: Math.random().toString(36).substring(2, 9),
                label: defaultLabel,
                hours: 0,
                minutes: 0,
                seconds: 0,
                totalSeconds: 0,
                remainingSeconds: 0,
                isRunning: false
            };
            return [...prev, newTimer];
        });
    }, []);

    useEffect(() => {
        audioRef.current = new Audio('/sounds/mixkit-short-rooster-crowing-2470.wav');
        
        // Add a default timer
        addTimer('Timer 1');
    }, [addTimer]);

    // Main interval to tick timers
    useEffect(() => {
        const interval = setInterval(() => {
            setTimers(prev => prev.map(timer => {
                if (timer.isRunning && timer.remainingSeconds > 0) {
                    const nextRemaining = timer.remainingSeconds - 1;
                    
                    if (nextRemaining <= 0) {
                        // Timer finished
                        if (audioRef.current) {
                            audioRef.current.play().catch(e => console.error("Audio playback failed:", e));
                        }
                        toast({ title: 'Timer Finished!', description: `"${timer.label}" has reached zero.` });
                        return { ...timer, remainingSeconds: 0, isRunning: false };
                    }
                    
                    return { ...timer, remainingSeconds: nextRemaining };
                }
                return timer;
            }));
        }, 1000);

        return () => clearInterval(interval);
    }, [toast]);



    const handleAddClick = () => {
        addTimer(newLabel.trim());
        setNewLabel('');
    };

    const removeTimer = (id: string) => {
        setTimers(prev => prev.filter(t => t.id !== id));
    };

    const updateTimerInput = (id: string, field: 'hours' | 'minutes' | 'seconds', value: number) => {
        setTimers(prev => prev.map(t => {
            if (t.id === id) {
                return { ...t, [field]: value };
            }
            return t;
        }));
    };

    const startTimer = (id: string) => {
        setTimers(prev => prev.map(t => {
            if (t.id === id) {
                let total = t.totalSeconds;
                let remaining = t.remainingSeconds;
                
                // If it's starting fresh or was reset (0 remaining and not running)
                if (t.remainingSeconds === 0 && t.totalSeconds === 0) {
                    total = (t.hours * 3600) + (t.minutes * 60) + t.seconds;
                    remaining = total;
                } else if (t.remainingSeconds === 0) {
                     // Restarting from previous inputs after finishing
                     total = (t.hours * 3600) + (t.minutes * 60) + t.seconds;
                     remaining = total;
                }
                
                if (remaining > 0) {
                    return { ...t, totalSeconds: total, remainingSeconds: remaining, isRunning: true };
                }
            }
            return t;
        }));
    };

    const pauseTimer = (id: string) => {
        setTimers(prev => prev.map(t => t.id === id ? { ...t, isRunning: false } : t));
    };

    const resetTimer = (id: string) => {
        setTimers(prev => prev.map(t => t.id === id ? { ...t, isRunning: false, remainingSeconds: 0, totalSeconds: 0 } : t));
    };

    const formatTime = (seconds: number) => {
        const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
        const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
        const s = (seconds % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
    };

    return (
        <WebToolsLayout title={__('general.multiple_countdown_timer')} activeNav="explore">
            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6">
                <div className="text-center mb-10">
                    <div className="inline-flex items-center justify-center p-3 bg-indigo-100 text-indigo-700 rounded-2xl mb-4">
                        <Timer className="w-8 h-8" />
                    </div>
                    <h1 className="text-3xl font-extrabold text-slate-900 sm:text-4xl">{__('general.multiple_countdown_timer')}</h1>
                    <p className="mt-4 text-lg text-slate-600 max-w-7xl mx-auto">
                        {__('general.create_and_manage_multiple_timers_simult')}</p>
                </div>

                <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-8 max-w-7xl mx-auto">
                    <div className="flex gap-3">
                        <Input 
                            placeholder="Timer Label (e.g., 'Boil Eggs')" 
                            value={newLabel}
                            onChange={(e) => setNewLabel(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleAddClick()}
                            className="flex-1"
                        />
                        <Button onClick={handleAddClick} className="gap-2">
                            <Plus className="w-4 h-4" />
                            {__('general.add_timer')}</Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
                    {timers.map(timer => {
                        const progress = timer.totalSeconds > 0 
                            ? ((timer.totalSeconds - timer.remainingSeconds) / timer.totalSeconds) * 100 
                            : 0;
                            
                        const isFinished = timer.totalSeconds > 0 && timer.remainingSeconds === 0 && !timer.isRunning;

                        return (
                            <Card key={timer.id} className={`shadow-sm transition-colors border-2 ${isFinished ? 'border-red-400 bg-red-50' : timer.isRunning ? 'border-indigo-400 bg-indigo-50/30' : 'border-slate-200'}`}>
                                <CardHeader className="pb-2 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                                    <CardTitle className="text-base font-semibold truncate pe-4 text-slate-800" title={timer.label}>
                                        {timer.label}
                                    </CardTitle>
                                    <Button variant="ghost" size="icon" className="h-6 w-6 text-slate-400 hover:text-red-500 shrink-0" onClick={() => removeTimer(timer.id)}>
                                        <X className="h-4 w-4" />
                                    </Button>
                                </CardHeader>
                                <CardContent className="px-4 pb-4">
                                    <div className="text-center mb-4">
                                        <div className={`text-5xl font-mono tracking-wider font-bold mb-4 ${isFinished ? 'text-red-600 animate-pulse' : 'text-slate-900'}`}>
                                            {formatTime(timer.remainingSeconds)}
                                        </div>
                                        
                                        {!timer.isRunning && timer.remainingSeconds === 0 && (
                                            <div className="flex items-center justify-center gap-2 mb-4">
                                                <Input 
                                                    type="number" min={0} max={99} 
                                                    className="w-16 text-center text-lg h-10" 
                                                    placeholder="00" 
                                                    value={timer.hours || ''} 
                                                    onChange={e => updateTimerInput(timer.id, 'hours', parseInt(e.target.value) || 0)} 
                                                />
                                                <span className="font-bold text-slate-400">:</span>
                                                <Input 
                                                    type="number" min={0} max={59} 
                                                    className="w-16 text-center text-lg h-10" 
                                                    placeholder="00" 
                                                    value={timer.minutes || ''} 
                                                    onChange={e => updateTimerInput(timer.id, 'minutes', parseInt(e.target.value) || 0)} 
                                                />
                                                <span className="font-bold text-slate-400">:</span>
                                                <Input 
                                                    type="number" min={0} max={59} 
                                                    className="w-16 text-center text-lg h-10" 
                                                    placeholder="00" 
                                                    value={timer.seconds || ''} 
                                                    onChange={e => updateTimerInput(timer.id, 'seconds', parseInt(e.target.value) || 0)} 
                                                />
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex justify-center gap-2 mb-4">
                                        {!timer.isRunning ? (
                                            <Button size="icon" variant="default" className="h-10 w-10 bg-emerald-600 hover:bg-emerald-700" onClick={() => startTimer(timer.id)}>
                                                <Play className="h-5 w-5 ms-1" />
                                            </Button>
                                        ) : (
                                            <Button size="icon" variant="destructive" className="h-10 w-10 text-white" onClick={() => pauseTimer(timer.id)}>
                                                <Pause className="h-5 w-5" />
                                            </Button>
                                        )}
                                        <Button size="icon" variant="secondary" className="h-10 w-10" onClick={() => resetTimer(timer.id)}>
                                            <RotateCcw className="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden"><div className="h-full bg-slate-900 transition-all duration-1000" style={{ width: `${progress}%` }} /></div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {timers.length === 0 && (
                    <div className="text-center py-12 text-slate-500">
                        {__('general.no_timers_added_yet_use_the_input_above')}</div>
                )}

                <div className="max-w-7xl mx-auto">
                    <Card className="bg-slate-50 border-slate-200">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base flex items-center gap-2 text-slate-700">
                                <Info className="w-4 h-4" />
                                {__('general.how_to_use')}</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-slate-600">
                            <ul className="list-disc ps-5 space-y-2">
                                <li><strong>Add a Timer:</strong> Enter a label (optional) and click "Add Timer".</li>
                                <li><strong>Set Time:</strong> {__('general.enter_hours_minutes_and_seconds_for_each')}</li>
                                <li><strong>Controls:</strong> {__('general.start_pause_or_reset_each_timer_individu')}</li>
                                <li><strong>Alarm:</strong> {__('general.an_audio_alarm_will_sound_when_a_timer_r')}</li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </WebToolsLayout>
    );
}
