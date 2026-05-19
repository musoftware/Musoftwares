import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Loader2, AlertCircle, Wifi, WifiOff, Copy, Check,
    ExternalLink, TrendingUp, MessageCircle, Share2, Bookmark, Clock,
    Music, Target, Zap, ChevronDown, ChevronUp
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

function fmt(n: number): string {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
    return String(n);
}

function ScoreRing({ score, max, size = 80, label }: { score: number; max: number; size?: number; label: string }) {
    const pct = Math.round((score / max) * 100);
    const r = (size - 8) / 2;
    const circ = 2 * Math.PI * r;
    const offset = circ - (circ * pct) / 100;
    const color = pct >= 75 ? '#22c55e' : pct >= 50 ? '#f59e0b' : pct >= 30 ? '#f97316' : '#ef4444';

    return (
        <div className="flex flex-col items-center gap-1">
            <svg width={size} height={size} className="transform -rotate-90">
                <circle cx={size/2} cy={size/2} r={r} fill="none" stroke="#1e293b" strokeWidth="6" />
                <circle cx={size/2} cy={size/2} r={r} fill="none" stroke={color} strokeWidth="6"
                    strokeDasharray={circ} strokeDashoffset={offset} strokeLinecap="round"
                    className="transition-all duration-1000" />
            </svg>
            <div className="absolute flex flex-col items-center justify-center" style={{ width: size, height: size }}>
                <span className="text-lg font-bold" style={{ color }}>{score}</span>
                <span className="text-[9px] text-slate-400">/{max}</span>
            </div>
            <span className="text-[10px] text-slate-400 font-medium text-center mt-0.5">{label}</span>
        </div>
    );
}

export default function ViralAutopsyRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [url,       setUrl]       = useState('');
    const [taskId,    setTaskId]    = useState<string | null>(null);
    const [status,    setStatus]    = useState<'idle'|'running'|'done'|'error'>('idle');
    const [logs,      setLogs]      = useState<string[]>([]);
    const [progress,  setProgress]  = useState(0);
    const [result,    setResult]    = useState<any>(null);
    const [errMsg,    setErrMsg]    = useState('');
    const [rtStatus,  setRtStatus]  = useState<'checking'|'ok'|'offline'>('checking');
    const [elapsed,   setElapsed]   = useState(0);
    const [copied,    setCopied]    = useState(false);
    const [showLogs,  setShowLogs]  = useState(false);

    const wsRef    = useRef<WebSocket | null>(null);
    const pollRef  = useRef<any>(null);
    const timerRef = useRef<any>(null);
    const logsEnd  = useRef<HTMLDivElement>(null);

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
                if (msg.event === 'task.log')      setLogs(l => [...l, d.message ?? '']);
                if (msg.event === 'task.progress') setProgress(d.percent ?? 0);
                if (msg.event === 'task.done') {
                    setResult(d.result ?? {});
                    setStatus('done');
                    setProgress(100);
                    clearInterval(timerRef.current);
                }
                if (msg.event === 'task.error') {
                    setErrMsg(d.error ?? 'Unknown error');
                    setStatus('error');
                    clearInterval(timerRef.current);
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
                    clearInterval(timerRef.current);
                }
                if (d.status === 'error' || d.status === 'failed') {
                    setErrMsg(d.error ?? 'Task failed');
                    setStatus('error');
                    clearInterval(pollRef.current);
                    clearInterval(timerRef.current);
                }
            } catch {}
        }, 1500);
    };

    const handleRun = async () => {
        if (!url.trim()) return;
        setStatus('running'); setLogs([]); setResult(null); setErrMsg(''); setProgress(0); setElapsed(0);
        const startTime = Date.now();
        timerRef.current = setInterval(() => setElapsed(Math.floor((Date.now() - startTime) / 1000)), 1000);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { url: url.trim() } }),
            });
            const data = await res.json();
            if (!res.ok) { setErrMsg(data.message || data.error || 'Runtime error'); setStatus('error'); return; }
            setTaskId(data.taskId);
            connectWs(data.taskId);
            startPolling(data.taskId);
        } catch (e: any) {
            setErrMsg('Cannot reach runtime. Is it running?');
            setStatus('error');
            clearInterval(timerRef.current);
        }
    };

    const copyJson = () => {
        navigator.clipboard.writeText(JSON.stringify(result, null, 2));
        setCopied(true); setTimeout(() => setCopied(false), 2000);
    };

    const vs = result?.viral_score;
    const eng = result?.engagement;

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Viral Autopsy`} />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600"><ArrowLeft className="h-5 w-5" /></Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                                <span className="text-xl">🔬</span>
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

                {/* Input */}
                <div className="bg-white border border-slate-200 rounded-xl p-6 mb-6">
                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Paste TikTok URL</p>
                    <div className="flex gap-3">
                        <input
                            type="text" value={url} onChange={e => setUrl(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && status !== 'running' && handleRun()}
                            placeholder="https://www.tiktok.com/@user/video/1234567890..."
                            className="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                        <Button onClick={handleRun} disabled={rtStatus === 'offline' || !url.trim() || status === 'running'}
                            className="gap-2 bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-90 text-white px-6 h-[46px]">
                            {status === 'running' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Zap className="h-4 w-4" />}
                            {status === 'running' ? `Analyzing... ${elapsed}s` : 'Analyze'}
                        </Button>
                    </div>

                    {/* Progress */}
                    {status === 'running' && (
                        <div className="mt-4 space-y-2">
                            <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div className="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500" style={{ width: `${progress}%` }} />
                            </div>
                            <button onClick={() => setShowLogs(!showLogs)} className="text-[11px] text-slate-400 hover:text-slate-600">
                                {showLogs ? 'Hide' : 'Show'} logs
                            </button>
                            {showLogs && (
                                <div className="max-h-32 overflow-y-auto bg-slate-900 rounded-lg p-3 font-mono text-[11px] text-slate-300 space-y-0.5">
                                    {logs.map((l, i) => <div key={i}>{l}</div>)}
                                    <div ref={logsEnd} />
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Error */}
                {status === 'error' && (
                    <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 mb-6">
                        <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /><span>{errMsg}</span>
                    </div>
                )}

                {/* ── RESULTS ── */}
                {result && vs && (
                    <div className="space-y-6 animate-in fade-in duration-500">

                        {/* Viral Score Hero */}
                        <div className="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-slate-400 mb-1">Viral Score</p>
                                    <div className="flex items-baseline gap-3">
                                        <span className="text-6xl font-black">{vs.total}</span>
                                        <span className="text-2xl text-slate-500">/100</span>
                                    </div>
                                    <p className="text-lg font-semibold mt-2">
                                        <span className="mr-2">{vs.verdict_emoji}</span>{vs.verdict}
                                    </p>
                                </div>

                                {/* Breakdown rings */}
                                <div className="flex gap-5">
                                    {Object.entries(vs.breakdown).map(([key, dim]: [string, any]) => (
                                        <div key={key} className="relative flex flex-col items-center">
                                            <ScoreRing score={dim.score} max={dim.max} size={70} label={dim.label} />
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Video info strip */}
                            {result.video && (
                                <div className="flex items-center gap-4 mt-6 pt-6 border-t border-slate-700">
                                    {result.video.cover_url && (
                                        <img src={result.video.cover_url} alt="" className="w-14 h-18 rounded-lg object-cover" />
                                    )}
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-white">@{result.video.author}</p>
                                        <p className="text-xs text-slate-400 truncate">{result.video.caption}</p>
                                    </div>
                                    {eng && (
                                        <div className="flex gap-4 text-xs text-slate-400">
                                            <span>▶️ {fmt(eng.plays)}</span>
                                            <span>❤️ {fmt(eng.likes)}</span>
                                            <span>💬 {fmt(eng.comments)}</span>
                                            <span>🔁 {fmt(eng.shares)}</span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Analysis cards grid */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">

                            {/* Caption Analysis */}
                            {result.caption && (
                                <div className="bg-white border border-slate-200 rounded-xl p-5">
                                    <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">📝 Caption & Hook</h3>
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-slate-700">Hook Type</span>
                                            <span className="text-sm font-semibold text-purple-600">{result.caption.hook_type_label}</span>
                                        </div>
                                        <div className="bg-slate-50 rounded-lg p-3">
                                            <p className="text-xs text-slate-500 mb-1">First Line</p>
                                            <p className="text-sm text-slate-800 font-medium">"{result.caption.first_line}"</p>
                                        </div>
                                        {result.caption.triggers?.length > 0 && (
                                            <div className="flex flex-wrap gap-1.5">
                                                {result.caption.triggers.map((t: string) => (
                                                    <span key={t} className="text-[10px] px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 font-medium">{t}</span>
                                                ))}
                                            </div>
                                        )}
                                        <div className="flex gap-4 text-xs text-slate-500">
                                            <span>{result.caption.word_count} words</span>
                                            <span>{result.caption.hashtag_count} hashtags</span>
                                            <span>{result.caption.emoji_count} emojis</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Engagement */}
                            {eng && (
                                <div className="bg-white border border-slate-200 rounded-xl p-5">
                                    <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">📊 Engagement</h3>
                                    <div className="grid grid-cols-2 gap-2 mb-3">
                                        {[
                                            ['Like Rate', eng.like_rate, eng.benchmark_comparison?.likes],
                                            ['Comment Rate', eng.comment_rate, eng.benchmark_comparison?.comments],
                                            ['Share Rate', eng.share_rate, eng.benchmark_comparison?.shares],
                                            ['Total ER', eng.total_eng_rate, null],
                                        ].map(([label, val, bench]) => (
                                            <div key={String(label)} className="bg-slate-50 rounded-lg p-2.5">
                                                <p className="text-[10px] text-slate-400">{label}</p>
                                                <p className="text-sm font-bold text-slate-800">{val}</p>
                                                {bench && (
                                                    <span className={`text-[9px] font-medium ${bench === 'above_avg' ? 'text-emerald-500' : bench === 'average' ? 'text-amber-500' : 'text-red-400'}`}>
                                                        {bench === 'above_avg' ? '↑ Above avg' : bench === 'average' ? '→ Average' : '↓ Below avg'}
                                                    </span>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                    {eng.signals?.length > 0 && (
                                        <div className="space-y-1">
                                            {eng.signals.map((s: string, i: number) => (
                                                <p key={i} className="text-xs text-slate-600">{s}</p>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Sound */}
                            {result.sound && (
                                <div className="bg-white border border-slate-200 rounded-xl p-5">
                                    <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">🎵 Sound Strategy</h3>
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2">
                                            <Music className="h-4 w-4 text-slate-400" />
                                            <span className="text-sm text-slate-700">{result.sound.title}</span>
                                            {result.sound.is_original && <span className="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-600">Original</span>}
                                        </div>
                                        {result.sound.analysis?.map((a: string, i: number) => (
                                            <p key={i} className="text-xs text-slate-500">{a}</p>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Timing */}
                            {result.timing && (
                                <div className="bg-white border border-slate-200 rounded-xl p-5">
                                    <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">⏰ Posting Timing</h3>
                                    <div className="space-y-2">
                                        {result.timing.analysis?.map((a: string, i: number) => (
                                            <p key={i} className="text-xs text-slate-600">{a}</p>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Suggestions */}
                        {result.suggestions?.length > 0 && (
                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                <h3 className="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-3">💡 Improvement Suggestions ({result.suggestion_count})</h3>
                                <ul className="space-y-2">
                                    {result.suggestions.map((s: string, i: number) => (
                                        <li key={i} className="flex items-start gap-2 text-sm text-amber-800">
                                            <span className="text-amber-500 mt-0.5">→</span>{s}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex gap-3">
                            <Button variant="outline" size="sm" onClick={copyJson} className="gap-1.5">
                                {copied ? <Check className="h-3.5 w-3.5 text-emerald-500" /> : <Copy className="h-3.5 w-3.5" />}
                                {copied ? 'Copied' : 'Copy JSON'}
                            </Button>
                        </div>
                    </div>
                )}

                {/* Empty state */}
                {!result && status !== 'running' && (
                    <div className="flex flex-col items-center justify-center h-64 text-center bg-white border border-dashed border-slate-200 rounded-xl">
                        <span className="text-4xl mb-3">🔬</span>
                        <p className="text-sm font-medium text-slate-500">Paste a TikTok video URL above</p>
                        <p className="text-xs text-slate-400 mt-1">Get a full viral analysis with score breakdown</p>
                    </div>
                )}
            </div>
        </ToolsPublicLayout>
    );
}
