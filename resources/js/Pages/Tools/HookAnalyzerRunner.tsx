import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Target, Plus, Trash2, BarChart2, Sparkles,
    AlertCircle, CheckCircle, RefreshCw, ChevronDown, ChevronUp
} from 'lucide-react';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Grade badge ───────────────────────────────────────────────────────────────
function GradeBadge({ grade, emoji }: { grade: string; emoji: string }) {
    const cls =
        grade === 'S' ? 'bg-amber-50  border-amber-300  text-amber-700' :
        grade === 'A' ? 'bg-rose-50   border-rose-300   text-rose-700'  :
        grade === 'B' ? 'bg-emerald-50 border-emerald-300 text-emerald-700' :
        grade === 'C' ? 'bg-slate-100 border-slate-300  text-slate-700' :
        grade === 'D' ? 'bg-orange-50 border-orange-300 text-orange-700' :
                        'bg-red-50    border-red-300    text-red-700';
    return (
        <div className={`w-14 h-14 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm ${cls}`}>
            <span className="text-xl font-black leading-none">{grade}</span>
            <span className="text-sm leading-none mt-0.5">{emoji}</span>
        </div>
    );
}

// ── Hook score bar ────────────────────────────────────────────────────────────
function HookScoreBar({ score }: { score: number }) {
    const color =
        score >= 70 ? 'from-emerald-500 to-teal-400' :
        score >= 50 ? 'from-amber-500 to-yellow-400' :
                      'from-rose-500 to-red-400';
    return (
        <div className="space-y-1">
            <div className="flex justify-between text-xs font-bold text-slate-500">
                <span>Hook Score</span><span>{score}/100</span>
            </div>
            <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                <div
                    className={`h-full bg-gradient-to-r ${color} transition-all duration-700 rounded-full`}
                    style={{ width: `${score}%` }}
                />
            </div>
        </div>
    );
}

// ── Single analysis card ──────────────────────────────────────────────────────
function AnalysisCard({ a, idx }: { a: any; idx: number }) {
    const [open, setOpen] = useState(idx === 0);

    if (a.error) {
        return (
            <div className="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-start gap-2.5">
                <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                <div>
                    <p className="text-xs font-bold text-rose-700 truncate">{a.url}</p>
                    <p className="text-xs text-rose-600 mt-1">{a.error}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            {/* Summary row */}
            <button
                onClick={() => setOpen(v => !v)}
                className="w-full flex items-center gap-4 p-5 hover:bg-slate-50 transition-colors text-left"
            >
                <GradeBadge grade={a.grade} emoji={a.grade_emoji} />
                <div className="flex-1 min-w-0 space-y-1.5">
                    <p className="text-xs font-mono text-slate-400 truncate">{a.url}</p>
                    <p className="text-sm font-bold text-slate-800 line-clamp-1">"{a.first_line}"</p>
                    <HookScoreBar score={a.hook_score} />
                </div>
                <div className="flex flex-col items-center shrink-0">
                    <span className="text-lg font-black text-slate-800">{a.hook_score}</span>
                    <span className="text-[9px] font-bold text-slate-400 uppercase">/100</span>
                </div>
                {open ? <ChevronUp className="w-4 h-4 text-slate-400 shrink-0" /> : <ChevronDown className="w-4 h-4 text-slate-400 shrink-0" />}
            </button>

            {/* Expanded detail */}
            {open && (
                <div className="px-5 pb-5 space-y-4 border-t border-slate-100 pt-4 animate-in fade-in duration-200">
                    {/* Pattern + retention */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-slate-50 border border-slate-100 rounded-xl p-3">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">Hook Pattern</p>
                            <p className="text-xs font-bold text-slate-700">{a.hook_pattern?.label}</p>
                            <p className="text-[10px] text-slate-400 mt-0.5">Power: {a.hook_pattern?.power}/10</p>
                        </div>
                        <div className="bg-slate-50 border border-slate-100 rounded-xl p-3">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">Retention Estimate</p>
                            <p className="text-xs font-bold text-slate-700">{a.retention_estimate}</p>
                        </div>
                    </div>

                    {/* Engagement stats (if available) */}
                    {a.engagement?.plays > 0 && (
                        <div className="grid grid-cols-3 gap-2">
                            {[
                                { l: 'Plays', v: Number(a.engagement.plays).toLocaleString() },
                                { l: 'Like Rate', v: a.engagement.like_rate },
                                { l: 'Share Rate', v: a.engagement.share_rate },
                            ].map(({ l, v }) => (
                                <div key={l} className="bg-emerald-50 border border-emerald-100 rounded-xl p-2.5">
                                    <p className="text-[9px] font-black uppercase tracking-wider text-emerald-600 mb-0.5">{l}</p>
                                    <p className="text-xs font-black text-emerald-800">{v}</p>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Improvement suggestions */}
                    {a.suggestions?.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Improvements</p>
                            {a.suggestions.map((s: any, i: number) => (
                                <div key={i} className={`flex items-start gap-2 px-3 py-2.5 rounded-xl border ${s.priority === 'high' ? 'bg-rose-50 border-rose-100' : 'bg-amber-50 border-amber-100'}`}>
                                    <AlertCircle className={`w-3.5 h-3.5 mt-0.5 shrink-0 ${s.priority === 'high' ? 'text-rose-500' : 'text-amber-500'}`} />
                                    <p className={`text-xs font-medium ${s.priority === 'high' ? 'text-rose-700' : 'text-amber-700'}`}>{s.text}</p>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Alternative hooks */}
                    {a.alternative_hooks?.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Try These Instead</p>
                            {a.alternative_hooks.map((h: string, i: number) => (
                                <div key={i} className="flex items-start gap-2 bg-violet-50 border border-violet-100 rounded-xl px-3 py-2.5">
                                    <Sparkles className="w-3.5 h-3.5 text-violet-500 mt-0.5 shrink-0" />
                                    <p className="text-xs font-medium text-violet-800">{h}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── Main component ────────────────────────────────────────────────────────────
export default function HookAnalyzerRunner({ tool }: any) {
    const [mode, setMode]       = useState<'single' | 'batch'>('single');
    const [singleUrl, setSingleUrl] = useState('');
    const [batchUrls, setBatchUrls] = useState<string[]>(['']);
    const [status, setStatus]   = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [result, setResult]   = useState<any>(null);
    const [errorMsg, setError]  = useState('');
    const [taskId, setTaskId]   = useState<string | null>(null);
    const wsRef = useRef<WebSocket | null>(null);

    useEffect(() => {
        const ws = new WebSocket(getWsUrl());
        wsRef.current = ws;
        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.event === 'task.progress' && msg.data?.taskId === taskId) {
                    setProgress(msg.data.percent ?? 0);
                    setProgressMsg(msg.data.message ?? '');
                }
                if (msg.event === 'task.done' && msg.data?.taskId === taskId) {
                    if (msg.data.result) { setResult(msg.data.result); setStatus('done'); }
                    else { setError('No result returned.'); setStatus('error'); }
                }
                if (msg.event === 'task.error' && msg.data?.taskId === taskId) {
                    setError(msg.data.error ?? 'Unknown error'); setStatus('error');
                }
            } catch {}
        };
        return () => ws.close();
    }, [taskId]);

    useEffect(() => {
        if (!taskId || status !== 'running') return;
        const iv = setInterval(async () => {
            try {
                const r = await fetch(`${getRuntimeHttp()}/tasks/${taskId}`);
                const d = await r.json();
                if (d.result) { setResult(d.result); setStatus('done'); clearInterval(iv); }
                if (d.status === 'failed') { setError(d.error ?? 'Failed'); setStatus('error'); clearInterval(iv); }
                if (typeof d.progress === 'number') setProgress(d.progress);
            } catch {}
        }, 2000);
        return () => clearInterval(iv);
    }, [taskId, status]);

    const handleAnalyze = async () => {
        const urls = mode === 'single'
            ? [singleUrl.trim()]
            : batchUrls.filter(u => u.trim());

        if (urls.length === 0 || urls.some(u => !u)) return;

        setStatus('running'); setProgress(5); setProgressMsg('Starting...'); setResult(null); setError('');

        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/hook-analyzer/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { mode, url: urls[0], urls } }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Failed to start');
            setTaskId(json.taskId);
        } catch (err: any) {
            setError(err.message); setStatus('error');
        }
    };

    const addUrl = () => setBatchUrls(v => [...v, '']);
    const removeUrl = (i: number) => setBatchUrls(v => v.filter((_, idx) => idx !== i));
    const updateUrl = (i: number, val: string) => setBatchUrls(v => v.map((u, idx) => idx === i ? val : u));

    const analyses = result?.analyses ?? [];
    const avg = result?.average_score;

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* Top bar */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-violet-600 to-purple-600 rounded-lg flex items-center justify-center shadow-sm">
                        <Target className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">Hook Analyzer</span>
                </div>
                <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                    {status === 'running' ? 'Analyzing...' : status === 'done' ? 'Done' : 'Ready'}
                </div>
            </div>

            <div className="max-w-3xl mx-auto px-4 py-8 space-y-6">

                {/* Input card */}
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">Score your hook</h1>
                        <p className="text-sm text-slate-400 mt-1">Get a Hook Score, grade, pattern detection, and specific improvements.</p>
                    </div>

                    {/* Mode toggle */}
                    <div className="flex border border-slate-200 rounded-xl overflow-hidden p-1 gap-1 w-fit">
                        {(['single', 'batch'] as const).map(m => (
                            <button
                                key={m}
                                onClick={() => setMode(m)}
                                className={`px-4 py-1.5 rounded-lg text-xs font-bold transition-all ${mode === m ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                            >
                                {m === 'single' ? 'Single URL' : 'Batch (up to 10)'}
                            </button>
                        ))}
                    </div>

                    {/* Single URL input */}
                    {mode === 'single' && (
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input
                                type="url"
                                value={singleUrl}
                                onChange={e => setSingleUrl(e.target.value)}
                                onKeyDown={e => e.key === 'Enter' && handleAnalyze()}
                                placeholder="https://www.tiktok.com/@user/video/..."
                                className="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 focus:border-violet-400 rounded-xl outline-none transition-all bg-slate-50 font-mono"
                            />
                        </div>
                    )}

                    {/* Batch URL inputs */}
                    {mode === 'batch' && (
                        <div className="space-y-2.5">
                            {batchUrls.map((u, i) => (
                                <div key={i} className="flex gap-2">
                                    <div className="relative flex-1">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">{i + 1}</span>
                                        <input
                                            type="url"
                                            value={u}
                                            onChange={e => updateUrl(i, e.target.value)}
                                            placeholder="https://www.tiktok.com/@user/video/..."
                                            className="w-full pl-8 pr-4 py-2.5 text-sm border border-slate-200 focus:border-violet-400 rounded-xl outline-none transition-all bg-slate-50 font-mono"
                                        />
                                    </div>
                                    {batchUrls.length > 1 && (
                                        <button onClick={() => removeUrl(i)} className="p-2.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>
                            ))}
                            {batchUrls.length < 10 && (
                                <button onClick={addUrl} className="flex items-center gap-1.5 text-xs font-bold text-violet-600 hover:text-violet-700 transition-colors">
                                    <Plus className="w-3.5 h-3.5" /> Add another URL
                                </button>
                            )}
                        </div>
                    )}

                    {/* Analyze button */}
                    <button
                        onClick={handleAnalyze}
                        disabled={status === 'running' || (mode === 'single' ? !singleUrl.trim() : batchUrls.every(u => !u.trim()))}
                        className="w-full py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl text-sm font-bold hover:opacity-90 transition-all shadow-md active:scale-95 disabled:opacity-40 disabled:shadow-none flex items-center justify-center gap-2"
                    >
                        {status === 'running' ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Target className="w-4 h-4" />}
                        {status === 'running' ? 'Analyzing Hooks...' : mode === 'batch' ? `Analyze ${batchUrls.filter(u => u.trim()).length} Videos` : 'Analyze Hook'}
                    </button>

                    {/* Progress */}
                    {status === 'running' && (
                        <div className="space-y-1.5 animate-in fade-in duration-300">
                            <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                <span>{progressMsg}</span><span>{progress}%</span>
                            </div>
                            <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    className="h-full bg-gradient-to-r from-violet-500 to-purple-400 transition-all duration-500 rounded-full"
                                    style={{ width: `${progress}%` }}
                                />
                            </div>
                        </div>
                    )}

                    {/* Error */}
                    {status === 'error' && (
                        <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-4">
                            <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                            <p className="text-sm text-rose-700 font-medium">{errorMsg}</p>
                        </div>
                    )}
                </div>

                {/* Results */}
                {status === 'done' && result && (
                    <div className="space-y-4 animate-in fade-in slide-in-from-bottom-3 duration-400">

                        {/* Summary bar (batch only) */}
                        {analyses.length > 1 && avg != null && (
                            <div className="bg-gradient-to-tr from-violet-900 to-purple-900 border border-violet-700 rounded-2xl p-5 text-white flex items-center justify-between shadow-lg">
                                <div>
                                    <p className="text-xs font-bold text-violet-300 uppercase tracking-wider">Average Hook Score</p>
                                    <p className="text-4xl font-black mt-1">{avg}<span className="text-lg font-bold text-violet-400">/100</span></p>
                                </div>
                                <div className="text-right">
                                    <p className="text-xs font-bold text-violet-300 uppercase tracking-wider">Videos Analyzed</p>
                                    <p className="text-4xl font-black mt-1">{analyses.length}</p>
                                </div>
                            </div>
                        )}

                        {/* Individual analysis cards */}
                        {analyses.map((a: any, i: number) => (
                            <AnalysisCard key={i} a={a} idx={i} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
