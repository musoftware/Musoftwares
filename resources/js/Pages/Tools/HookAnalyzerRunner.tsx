import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Loader2, AlertCircle, Wifi, WifiOff, Copy, Check, Zap,
    Target, ChevronDown
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category?: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function HookAnalyzerRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [mode, setMode]         = useState<'single'|'batch'>('single');
    const [url, setUrl]           = useState('');
    const [batchUrls, setBatch]   = useState('');
    const [status, setStatus]     = useState<'idle'|'running'|'done'|'error'>('idle');
    const [logs, setLogs]         = useState<string[]>([]);
    const [progress, setProgress] = useState(0);
    const [result, setResult]     = useState<any>(null);
    const [errMsg, setErrMsg]     = useState('');
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    const [elapsed, setElapsed]   = useState(0);
    const [copied, setCopied]     = useState(false);

    const wsRef = useRef<WebSocket|null>(null);
    const pollRef = useRef<any>(null);
    const timerRef = useRef<any>(null);

    useEffect(() => {
        fetch(`${base}/health`).then(r => setRtStatus(r.ok ? 'ok' : 'offline')).catch(() => setRtStatus('offline'));
    }, [base]);

    const connectWs = (tid: string) => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort + 1}/ws`);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                const d = msg.data ?? {};
                if (d.taskId && d.taskId !== tid) return;
                if (msg.event === 'task.log')      setLogs(l => [...l, d.message ?? '']);
                if (msg.event === 'task.progress') setProgress(d.percent ?? 0);
                if (msg.event === 'task.done') { setResult(d.result ?? {}); setStatus('done'); setProgress(100); clearInterval(timerRef.current); }
                if (msg.event === 'task.error') { setErrMsg(d.error ?? 'Unknown error'); setStatus('error'); clearInterval(timerRef.current); }
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
                if (d.status === 'done') { setResult(d.result ?? {}); setStatus('done'); clearInterval(pollRef.current); clearInterval(timerRef.current); }
                if (d.status === 'failed') { setErrMsg(d.error ?? 'Failed'); setStatus('error'); clearInterval(pollRef.current); clearInterval(timerRef.current); }
            } catch {}
        }, 1500);
    };

    const handleRun = async () => {
        const urls = mode === 'batch'
            ? batchUrls.split('\n').map(u => u.trim()).filter(Boolean)
            : [url.trim()];
        if (urls.length === 0 || urls[0] === '') return;

        setStatus('running'); setLogs([]); setResult(null); setErrMsg(''); setProgress(0); setElapsed(0);
        const start = Date.now();
        timerRef.current = setInterval(() => setElapsed(Math.floor((Date.now() - start) / 1000)), 1000);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: mode === 'batch' ? { mode: 'batch', urls } : { mode: 'single', url: urls[0] } }),
            });
            const data = await res.json();
            if (!res.ok) { setErrMsg(data.message || data.error || 'Error'); setStatus('error'); return; }
            connectWs(data.taskId);
            startPolling(data.taskId);
        } catch { setErrMsg('Cannot reach runtime'); setStatus('error'); clearInterval(timerRef.current); }
    };

    const gradeColor = (g: string) => {
        if (g === 'S' || g === 'A') return 'text-emerald-500 bg-emerald-50 border-emerald-200';
        if (g === 'B')              return 'text-blue-500 bg-blue-50 border-blue-200';
        if (g === 'C')              return 'text-amber-500 bg-amber-50 border-amber-200';
        return 'text-red-500 bg-red-50 border-red-200';
    };

    const analyses = result?.analyses ?? [];

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Hook Analyzer`} />
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600"><ArrowLeft className="h-5 w-5" /></Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                                <span className="text-xl">🎯</span>
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900">{tool.title}</h1>
                                <p className="text-xs text-slate-400">{subscription.plan_name} plan</p>
                            </div>
                        </div>
                    </div>
                    <div className={`flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border ${rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200'}`}>
                        {rtStatus === 'ok' ? <Wifi className="h-3.5 w-3.5" /> : <WifiOff className="h-3.5 w-3.5" />}
                        {rtStatus === 'ok' ? 'Runtime connected' : 'Offline'}
                    </div>
                </div>

                {/* Input */}
                <div className="bg-white border border-slate-200 rounded-xl p-6 mb-6 space-y-4">
                    <div className="flex gap-2">
                        <button onClick={() => setMode('single')} className={`px-3 py-1.5 text-xs font-medium rounded-lg border ${mode === 'single' ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-slate-600 border-slate-200'}`}>Single URL</button>
                        <button onClick={() => setMode('batch')} className={`px-3 py-1.5 text-xs font-medium rounded-lg border ${mode === 'batch' ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-slate-600 border-slate-200'}`}>Batch (multi-URL)</button>
                    </div>

                    {mode === 'single' ? (
                        <div className="flex gap-3">
                            <input type="text" value={url} onChange={e => setUrl(e.target.value)} onKeyDown={e => e.key === 'Enter' && handleRun()}
                                placeholder="https://www.tiktok.com/@user/video/..."
                                className="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500" />
                            <Button onClick={handleRun} disabled={rtStatus === 'offline' || !url.trim() || status === 'running'}
                                className="gap-2 bg-gradient-to-r from-orange-600 to-red-500 text-white px-6 h-[46px]">
                                {status === 'running' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Target className="h-4 w-4" />}
                                Analyze Hook
                            </Button>
                        </div>
                    ) : (
                        <>
                            <textarea value={batchUrls} onChange={e => setBatch(e.target.value)} rows={4}
                                placeholder="Paste one TikTok URL per line..."
                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500" />
                            <Button onClick={handleRun} disabled={rtStatus === 'offline' || !batchUrls.trim() || status === 'running'}
                                className="gap-2 bg-gradient-to-r from-orange-600 to-red-500 text-white">
                                {status === 'running' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Target className="h-4 w-4" />}
                                Analyze {batchUrls.split('\n').filter(l => l.trim()).length} Hook(s)
                            </Button>
                        </>
                    )}

                    {status === 'running' && (
                        <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div className="h-full bg-gradient-to-r from-orange-500 to-red-500 rounded-full transition-all" style={{ width: `${progress}%` }} />
                        </div>
                    )}
                </div>

                {status === 'error' && (
                    <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 mb-6">
                        <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /><span>{errMsg}</span>
                    </div>
                )}

                {/* Results */}
                {analyses.length > 0 && (
                    <div className="space-y-4">
                        {/* Summary */}
                        {result.average_score != null && (
                            <div className="bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl p-6 text-white flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-slate-400">Average Hook Score</p>
                                    <p className="text-4xl font-black">{result.average_score}<span className="text-lg text-slate-500">/100</span></p>
                                </div>
                                <div className="flex gap-6 text-center">
                                    {result.best_hook && (
                                        <div><p className="text-xs text-slate-400">Best</p><p className="text-lg font-bold text-emerald-400">{result.best_hook.score}</p></div>
                                    )}
                                    {result.worst_hook && (
                                        <div><p className="text-xs text-slate-400">Worst</p><p className="text-lg font-bold text-red-400">{result.worst_hook.score}</p></div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Individual results */}
                        {analyses.filter((a: any) => a.hook_score != null).map((a: any, i: number) => (
                            <div key={i} className="bg-white border border-slate-200 rounded-xl p-5">
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-3">
                                        {a.video_info?.cover_url && <img src={a.video_info.cover_url} alt="" className="w-10 h-13 rounded-lg object-cover" />}
                                        <div>
                                            <p className="text-xs font-semibold text-indigo-600">@{a.video_info?.author || 'unknown'}</p>
                                            <p className="text-[11px] text-slate-400 truncate max-w-[300px]">{a.first_line}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className={`text-2xl font-black ${a.hook_score >= 70 ? 'text-emerald-500' : a.hook_score >= 40 ? 'text-amber-500' : 'text-red-500'}`}>
                                            {a.hook_score}
                                        </span>
                                        <span className={`text-xs font-bold px-2 py-1 rounded-lg border ${gradeColor(a.grade)}`}>
                                            {a.grade_emoji} {a.grade}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2 mb-3">
                                    <span className="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 font-medium">{a.hook_pattern?.label}</span>
                                    <span className="text-[10px] text-slate-400">Retention: {a.retention_estimate}</span>
                                </div>

                                {a.engagement && a.engagement.plays > 0 && (
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 py-2.5 px-3 bg-slate-50 border border-slate-100 rounded-lg text-center text-xs text-slate-500 mb-3.5">
                                        <div>
                                            <span className="font-bold text-slate-800 block text-sm">{(a.engagement.plays).toLocaleString()}</span>
                                            <span className="text-[10px] text-slate-400">Plays</span>
                                        </div>
                                        <div>
                                            <span className="font-bold text-slate-800 block text-sm">{(a.engagement.likes).toLocaleString()}</span>
                                            <span className="text-[10px] text-slate-400">Likes ({a.engagement.like_rate})</span>
                                        </div>
                                        <div>
                                            <span className="font-bold text-slate-800 block text-sm">{(a.engagement.comments).toLocaleString()}</span>
                                            <span className="text-[10px] text-slate-400">Comments ({a.engagement.comment_rate})</span>
                                        </div>
                                        <div>
                                            <span className="font-bold text-indigo-600 block text-sm">{(a.engagement.shares).toLocaleString()}</span>
                                            <span className="text-[10px] text-indigo-400">Shares</span>
                                        </div>
                                    </div>
                                )}

                                {a.suggestions?.length > 0 && (
                                    <div className="space-y-1 mt-2">
                                        {a.suggestions.map((s: any, j: number) => (
                                            <p key={j} className={`text-xs ${s.priority === 'high' ? 'text-red-600' : 'text-amber-600'}`}>
                                                {s.priority === 'high' ? '❌' : '⚠️'} {s.text}
                                            </p>
                                        ))}
                                    </div>
                                )}

                                {a.alternative_hooks?.length > 0 && (
                                    <div className="mt-3 bg-emerald-50 rounded-lg p-3">
                                        <p className="text-[10px] font-semibold text-emerald-600 uppercase mb-1">Try instead:</p>
                                        {a.alternative_hooks.slice(0, 2).map((h: string, j: number) => (
                                            <p key={j} className="text-xs text-emerald-700">💡 {h}</p>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {!result && status !== 'running' && (
                    <div className="flex flex-col items-center justify-center h-64 text-center bg-white border border-dashed border-slate-200 rounded-xl">
                        <span className="text-4xl mb-3">🎯</span>
                        <p className="text-sm font-medium text-slate-500">Paste a TikTok URL to analyze its hook</p>
                        <p className="text-xs text-slate-400 mt-1">Get a Hook Score, pattern analysis, and improvement suggestions</p>
                    </div>
                )}
            </div>
        </ToolsPublicLayout>
    );
}
