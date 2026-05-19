import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, Square, Shield, Thermometer,
    AlertCircle, Wifi, WifiOff, MessageCircle, HeartPulse,
    Activity, Clock, Flame
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

function ScoreRing({ score, max, size = 80, label, inverseColor = false }: { score: number; max: number; size?: number; label: string; inverseColor?: boolean }) {
    const pct = Math.round((score / max) * 100);
    const r = (size - 8) / 2;
    const circ = 2 * Math.PI * r;
    const offset = circ - (circ * pct) / 100;
    
    let color = pct >= 75 ? '#22c55e' : pct >= 50 ? '#f59e0b' : pct >= 30 ? '#f97316' : '#ef4444';
    if (inverseColor) {
        color = pct <= 25 ? '#22c55e' : pct <= 50 ? '#f59e0b' : pct <= 70 ? '#f97316' : '#ef4444';
    }

    return (
        <div className="flex flex-col items-center gap-1">
            <svg width={size} height={size} className="transform -rotate-90">
                <circle cx={size/2} cy={size/2} r={r} fill="none" stroke="#e2e8f0" strokeWidth="6" />
                <circle cx={size/2} cy={size/2} r={r} fill="none" stroke={color} strokeWidth="6"
                    strokeDasharray={circ} strokeDashoffset={offset} strokeLinecap="round"
                    className="transition-all duration-1000" />
            </svg>
            <div className="absolute flex flex-col items-center justify-center" style={{ width: size, height: size }}>
                <span className="text-lg font-bold text-slate-800">{score}</span>
                <span className="text-[9px] text-slate-400">/{max}</span>
            </div>
            <span className="text-[10px] text-slate-500 font-medium text-center mt-0.5">{label}</span>
        </div>
    );
}

export default function WaWarmupRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [status, setStatus] = useState<'idle'|'running'|'done'|'error'>('idle');
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    
    // Config state
    const [sessionName, setSessionName] = useState('warmup_1');
    const [poolNumbersRaw, setPoolNumbersRaw] = useState('');
    const [language, setLanguage] = useState<'en'|'ar'>('en');
    
    // Runtime state
    const [taskId, setTaskId] = useState<string | null>(null);
    const [logs, setLogs] = useState<string[]>([]);
    const [progress, setProgress] = useState(0);
    const [errMsg, setErrMsg] = useState('');
    const [result, setResult] = useState<any>(null);
    
    const wsRef = useRef<WebSocket | null>(null);
    const pollRef = useRef<any>(null);
    const logsEnd = useRef<HTMLDivElement>(null);

    useEffect(() => {
        fetch(`${base}/health`).then(r => setRtStatus(r.ok ? 'ok' : 'offline')).catch(() => setRtStatus('offline'));
    }, [base]);

    useEffect(() => { logsEnd.current?.scrollIntoView({ behavior: 'smooth' }); }, [logs]);

    const connectWs = (tid: string) => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort + 1}/ws`);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                const d = msg.data ?? {};
                if (d.taskId && d.taskId !== tid) return;
                
                if (msg.event === 'task.log') setLogs(l => [...l, d.message ?? '']);
                if (msg.event === 'task.progress') setProgress(d.percent ?? 0);
                
                if (msg.event === 'task.done') {
                    setResult(d.result ?? {});
                    setStatus('done');
                    setProgress(100);
                }
                if (msg.event === 'task.error') {
                    setErrMsg(d.error ?? 'Unknown error');
                    setStatus('error');
                }
            } catch {}
        };
        wsRef.current = ws;
    };

    const startPolling = (tid: string) => {
        pollRef.current = setInterval(async () => {
            try {
                const r = await fetch(`${base}/tasks/${tid}`);
                const d = await r.json();
                setLogs(d.logs?.map((l: any) => l.message ?? l) ?? []);
                if (d.status === 'done') {
                    setResult(d.result ?? {});
                    setStatus('done');
                    setProgress(100);
                    clearInterval(pollRef.current);
                }
                if (d.status === 'error' || d.status === 'failed') {
                    setErrMsg(d.error ?? 'Task failed');
                    setStatus('error');
                    clearInterval(pollRef.current);
                }
            } catch {}
        }, 1500);
    };

    const handleRun = async () => {
        if (!poolNumbersRaw.trim()) {
            setErrMsg('You need at least one pool number to warm up with.');
            setStatus('error');
            return;
        }

        const poolNumbers = poolNumbersRaw.split('\\n').map(c => c.trim()).filter(c => c);

        setStatus('running'); setLogs([]); setResult(null); setErrMsg(''); setProgress(0);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        session_name: sessionName,
                        pool_numbers: poolNumbers,
                        language
                    }
                }),
            });
            const data = await res.json();
            if (!res.ok) { setErrMsg(data.message || data.error || 'Runtime error'); setStatus('error'); return; }
            setTaskId(data.taskId);
            connectWs(data.taskId);
            startPolling(data.taskId);
        } catch (e: any) {
            setErrMsg('Cannot reach runtime. Is it running?');
            setStatus('error');
        }
    };

    const handleStop = async () => {
        if (taskId) await fetch(`${base}/tasks/${taskId}/stop`, { method: 'POST' });
        clearInterval(pollRef.current);
        wsRef.current?.close();
        setStatus('idle');
    };

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                                <Flame className="h-5 w-5 text-orange-600" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900">{tool.title}</h1>
                                <p className="text-xs text-slate-400">{subscription.plan_name} plan</p>
                            </div>
                        </div>
                    </div>
                    <div className={`flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border ${
                        rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                        rtStatus === 'offline' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 text-slate-500 border-slate-200'
                    }`}>
                        {rtStatus === 'ok' ? <Wifi className="h-3.5 w-3.5" /> : <WifiOff className="h-3.5 w-3.5" />}
                        {rtStatus === 'ok' ? 'Runtime connected' : rtStatus === 'offline' ? 'Runtime offline' : 'Checking...'}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: Configuration */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white border border-slate-200 rounded-xl p-6">
                            <h2 className="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <Thermometer className="h-4 w-4 text-slate-400" /> Warmup Configuration
                            </h2>
                            
                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Session Name</label>
                                    <input type="text" value={sessionName} onChange={e => setSessionName(e.target.value)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-orange-500 focus:border-orange-500" 
                                        placeholder="e.g. Number_1" />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Language</label>
                                    <select value={language} onChange={e => setLanguage(e.target.value as any)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-orange-500 focus:border-orange-500">
                                        <option value="en">English</option>
                                        <option value="ar">Arabic (العربية)</option>
                                    </select>
                                </div>
                            </div>

                            <div className="mb-4">
                                <label className="block text-xs font-medium text-slate-700 mb-1 flex justify-between">
                                    <span>Pool Numbers (Target numbers to converse with)</span>
                                    <span className="text-slate-400">{poolNumbersRaw.split('\\n').filter(x=>x.trim()).length} numbers</span>
                                </label>
                                <textarea 
                                    value={poolNumbersRaw} onChange={e => setPoolNumbersRaw(e.target.value)}
                                    className="w-full h-32 text-sm font-mono border-slate-200 rounded-lg focus:ring-orange-500 focus:border-orange-500"
                                    placeholder="201001234567&#10;201007654321"
                                />
                                <p className="text-[10px] text-slate-500 mt-1">These should be numbers you control that can auto-reply or are manned by humans.</p>
                            </div>
                        </div>

                        {/* Error Message */}
                        {status === 'error' && (
                            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /><span>{errMsg}</span>
                            </div>
                        )}

                        {/* Progress and Logs */}
                        {(status === 'running' || logs.length > 0) && (
                            <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3">
                                {status === 'running' && (
                                    <div className="space-y-1.5">
                                        <div className="flex justify-between text-xs text-slate-400">
                                            <span>Warmup Active...</span>
                                            <span>{progress}%</span>
                                        </div>
                                        <div className="h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                            <div className="h-full bg-orange-500 rounded-full transition-all duration-500" style={{ width: `${progress}%` }} />
                                        </div>
                                    </div>
                                )}
                                <div className="max-h-64 overflow-y-auto font-mono text-[11px] text-slate-300 space-y-1">
                                    {logs.map((l, i) => <div key={i}>{l}</div>)}
                                    <div ref={logsEnd} />
                                </div>
                            </div>
                        )}
                        
                        {/* Results */}
                        {result && result.health_report && (
                            <div className="bg-slate-50 border border-slate-200 rounded-xl p-6">
                                <h3 className="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <HeartPulse className="h-4 w-4 text-orange-500" /> Account Health Report
                                </h3>
                                
                                <div className="flex items-center justify-between mb-8">
                                    <div>
                                        <div className="text-xs text-slate-500 uppercase tracking-wider mb-1">Trust Grade</div>
                                        <div className="flex items-baseline gap-3">
                                            <span className={`text-6xl font-black ${result.health_report.grade === 'A+' || result.health_report.grade === 'A' ? 'text-green-500' : result.health_report.grade === 'B' ? 'text-amber-500' : 'text-red-500'}`}>
                                                {result.health_report.grade}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex gap-4">
                                        <ScoreRing score={result.health_report.score} max={100} size={70} label="Trust Score" />
                                        <ScoreRing score={Math.round(result.health_report.ban_probability * 100)} max={100} size={70} label="Ban Risk %" inverseColor />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div className="bg-white p-3 rounded-lg border border-slate-200">
                                        <div className="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Status</div>
                                        <div className="text-sm font-bold text-slate-800">
                                            {result.health_report.isReady ? '✅ Campaign Ready' : '⏳ Still Warming'}
                                        </div>
                                    </div>
                                    <div className="bg-white p-3 rounded-lg border border-slate-200">
                                        <div className="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Rec. Limit</div>
                                        <div className="text-sm font-bold text-slate-800">
                                            {result.health_report.daily_limit_recommendation} msgs/day
                                        </div>
                                    </div>
                                    <div className="bg-white p-3 rounded-lg border border-slate-200">
                                        <div className="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Msgs Sent</div>
                                        <div className="text-sm font-bold text-slate-800">{result.stats?.total_sent || 0}</div>
                                    </div>
                                    <div className="bg-white p-3 rounded-lg border border-slate-200">
                                        <div className="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Msgs Rcvd</div>
                                        <div className="text-sm font-bold text-slate-800">{result.stats?.total_received || 0}</div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right: Info & Actions */}
                    <div className="space-y-6">
                        <div className="bg-orange-50 border border-orange-200 rounded-xl p-6">
                            <h2 className="text-sm font-bold text-orange-900 mb-3 flex items-center gap-2">
                                <Shield className="h-4 w-4" /> Why Warmup?
                            </h2>
                            <p className="text-xs text-orange-800 leading-relaxed mb-4">
                                Meta tracks the ratio of outbound to inbound messages. Sending bulk campaigns from a fresh number without a warmup history guarantees a permanent ban.
                            </p>
                            <ul className="text-xs text-orange-800 space-y-2">
                                <li className="flex gap-2"><span>1.</span> Simulates organic 2-way chats</li>
                                <li className="flex gap-2"><span>2.</span> Gradually scales daily volume</li>
                                <li className="flex gap-2"><span>3.</span> Calculates trust & ban probability</li>
                            </ul>
                        </div>

                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-6">
                            {status === 'running' ? (
                                <Button onClick={handleStop} variant="outline" className="w-full gap-2 border-red-200 text-red-600 hover:bg-red-50">
                                    <Square className="h-4 w-4" /> Stop Warmup
                                </Button>
                            ) : (
                                <Button onClick={handleRun} disabled={rtStatus === 'offline'} className="w-full gap-2 bg-orange-600 hover:bg-orange-700 text-white h-12 text-base">
                                    <Play className="h-5 w-5" /> Start Daily Warmup
                                </Button>
                            )}
                            <p className="text-center text-[10px] text-slate-400 mt-3">
                                Run this once daily for 14 days to reach campaign readiness.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
