import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, Square, Download, Hash, User, TrendingUp,
    Search, Loader2, CheckCircle2, AlertCircle, Wifi, WifiOff,
    ChevronDown, ChevronUp, Copy, Check
} from 'lucide-react';
import ViralAutopsyRunner from '@/Pages/Tools/ViralAutopsyRunner';
import HookAnalyzerRunner from '@/Pages/Tools/HookAnalyzerRunner';
import FormatExtractorRunner from '@/Pages/Tools/FormatExtractorRunner';
import WhatsAppSenderRunner from '@/Pages/Tools/WhatsAppSenderRunner';
import WhatsAppSenderProRunner from '@/Pages/Tools/WhatsAppSenderProRunner';
import WaWarmupRunner from '@/Pages/Tools/WaWarmupRunner';
import WaAiAgentRunner from '@/Pages/Tools/WaAiAgentRunner';
import WaFunnelBuilderRunner from '@/Pages/Tools/WaFunnelBuilderRunner';
import LeadIntelligenceRunner from '@/Pages/Tools/LeadIntelligenceRunner';
import TikTokIntelligenceRunner from '@/Pages/Tools/TikTokIntelligenceRunner';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

interface VideoResult {
    id: string; description: string; author: string; author_name: string;
    likes: number; comments: number; shares: number; plays: number;
    duration_sec: number; hashtags: string; engagement_rate: string;
    cover_url: string; created_at: number; download_url?: string;
}

type Action = 'keyword' | 'hashtag' | 'profile' | 'trending';

const ACTIONS: { value: Action; label: string; icon: any; placeholder: string; needsQuery: boolean }[] = [
    { value: 'keyword',  label: 'Keyword',  icon: Search,     placeholder: 'e.g. fitness motivation',   needsQuery: true },
    { value: 'hashtag',  label: 'Hashtag',  icon: Hash,       placeholder: 'e.g. viral (no #)',          needsQuery: true },
    { value: 'profile',  label: 'Profile',  icon: User,       placeholder: 'e.g. charlidamelio (no @)', needsQuery: true },
    { value: 'trending', label: 'Trending', icon: TrendingUp, placeholder: '',                            needsQuery: false },
];

function fmt(n: number): string {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
    return String(n);
}

export default function Runner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    // ── Route to tool-specific runner if available ──────────────────────────
    const component = tool.runner_component || pluginSlug;
    if (component === 'viral-autopsy')    return <ViralAutopsyRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'hook-analyzer')    return <HookAnalyzerRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'format-extractor') return <FormatExtractorRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'whatsapp-sender')  return <WhatsAppSenderRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'whatsapp-sender-pro') return <WhatsAppSenderProRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'wa-warmup')        return <WaWarmupRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'wa-ai-agent')      return <WaAiAgentRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'wa-funnel-engine') return <WaFunnelBuilderRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'lead-intelligence') return <LeadIntelligenceRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;
    if (component === 'email-prospector' || pluginSlug === 'email-prospector') return <LeadIntelligenceRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug="lead-intelligence" />;
    if (component === 'tiktok-intelligence') return <TikTokIntelligenceRunner tool={tool} subscription={subscription} runtimePort={runtimePort} pluginSlug={pluginSlug} />;

    // ── Default: TikTok Scraper runner (original) ──────────────────────────
    const base = `http://127.0.0.1:${runtimePort}`;

    const [action,    setAction]    = useState<Action>('keyword');
    const [query,     setQuery]     = useState('');
    const [maxCount,  setMaxCount]  = useState(30);
    const [exportCsv, setExportCsv] = useState(false);
    const [taskId,    setTaskId]    = useState<string | null>(null);
    const [status,    setStatus]    = useState<'idle'|'running'|'done'|'error'>('idle');
    const [logs,      setLogs]      = useState<string[]>([]);
    const [progress,  setProgress]  = useState(0);
    const [results,   setResults]   = useState<VideoResult[]>([]);
    const [profileData, setProfileData] = useState<any>(null);
    const [errMsg,    setErrMsg]    = useState('');
    const [rtStatus,  setRtStatus]  = useState<'checking'|'ok'|'offline'>('checking');
    const [expandedId, setExpandedId] = useState<string | null>(null);
    const [copied,    setCopied]    = useState(false);
    const [copiedUrl, setCopiedUrl] = useState<string|null>(null);
    const [sortBy,    setSortBy]    = useState<'default'|'likes'|'plays'|'comments'|'shares'>('default');
    const [elapsed,   setElapsed]   = useState(0);
    const wsRef    = useRef<WebSocket | null>(null);
    const pollRef  = useRef<any>(null);
    const timerRef = useRef<any>(null);
    const logsEnd  = useRef<HTMLDivElement>(null);

    // ── Check runtime on mount ──────────────────────────────────────────────
    useEffect(() => {
        fetch(`${base}/health`).then(r => {
            setRtStatus(r.ok ? 'ok' : 'offline');
        }).catch(() => setRtStatus('offline'));
    }, [base]);

    // ── Auto-scroll logs ────────────────────────────────────────────────────
    useEffect(() => { logsEnd.current?.scrollIntoView({ behavior: 'smooth' }); }, [logs]);

    // ── WebSocket for real-time events ──────────────────────────────────────
    const connectWs = (tid: string) => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort}/ws`);
        ws.onmessage = (ev) => {
            try {
                // Payload shape: { event: string, data: { taskId, ... }, ts: number }
                const msg = JSON.parse(ev.data);
                const d   = msg.data ?? {};

                // Filter to events for THIS task only
                if (d.taskId && d.taskId !== tid) return;

                if (msg.event === 'task.log')      setLogs(l => [...l, d.message ?? '']);
                if (msg.event === 'task.progress') setProgress(d.percent ?? 0);

                if (msg.event === 'task.done') {
                    const result = d.result ?? {};
                    const vids = Array.isArray(result.videos)
                        ? result.videos
                        : (result.profile?.videos ?? []);
                    setResults(vids);
                    setProfileData(result.profile ?? null);
                    setStatus('done');
                    setProgress(100);
                    clearInterval(timerRef.current);
                }

                if (msg.event === 'task.error') {
                    setErrMsg(d.error ?? d.message ?? 'Unknown error');
                    setStatus('error');
                    clearInterval(timerRef.current);
                }
            } catch {}
        };
        wsRef.current = ws;
    };

    // ── Poll fallback if WS unavailable ─────────────────────────────────────
    const startPolling = (tid: string) => {
        pollRef.current = setInterval(async () => {
            try {
                const r = await fetch(`${base}/tasks/${tid}`);
                const d = await r.json();
                setLogs(d.logs?.map((l: any) => l.message ?? l) ?? []);
                if (d.status === 'done') {
                    const result = d.result ?? {};
                    const vids = Array.isArray(result.videos)
                        ? result.videos
                        : (result.profile?.videos ?? []);
                    setResults(vids);
                    setProfileData(result.profile ?? null);
                    setStatus('done');
                    setProgress(100);
                    clearInterval(pollRef.current);
                    clearInterval(timerRef.current);
                }
                if (d.status === 'error' || d.status === 'failed') {
                    setErrMsg(d.error ?? d.result?.error ?? 'Task failed');
                    setStatus('error');
                    clearInterval(pollRef.current);
                    clearInterval(timerRef.current);
                }
            } catch {}
        }, 1500);
    };

    // ── Run ─────────────────────────────────────────────────────────────────
    const handleRun = async () => {
        const act = ACTIONS.find(a => a.value === action)!;
        if (act.needsQuery && !query.trim()) return;

        setStatus('running'); setLogs([]); setResults([]); setProfileData(null);
        setErrMsg(''); setProgress(0); setTaskId(null); setElapsed(0); setSortBy('default');

        // Start elapsed timer
        const startTime = Date.now();
        timerRef.current = setInterval(() => {
            setElapsed(Math.floor((Date.now() - startTime) / 1000));
        }, 1000);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: { action, query: query.trim(), max_count: maxCount, export_csv: exportCsv, headless: true }
                }),
            });
            const data = await res.json();
            if (!res.ok) {
                setErrMsg(data.message || data.error || 'Runtime error');
                setStatus('error'); return;
            }
            setTaskId(data.taskId);
            connectWs(data.taskId);
            startPolling(data.taskId);
        } catch (e: any) {
            setErrMsg('Cannot reach runtime at ' + base + '. Is it running?');
            setStatus('error');
            clearInterval(timerRef.current);
        }
    };

    const handleStop = async () => {
        if (taskId) await fetch(`${base}/tasks/${taskId}/stop`, { method: 'POST' });
        clearInterval(pollRef.current);
        clearInterval(timerRef.current);
        wsRef.current?.close();
        setStatus('idle');
    };

    const downloadCsv = () => {
        if (!results.length) return;
        const headers = ['id','author','author_name','description','likes','comments','shares','plays','engagement_rate','hashtags','duration_sec'];
        const rows = results.map(v => headers.map(h => `"${String((v as any)[h] ?? '').replace(/"/g,'""')}"`).join(','));
        const blob = new Blob([[headers.join(','), ...rows].join('\n')], { type: 'text/csv' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
        a.download = `tiktok-${action}-${query || 'trending'}-${Date.now()}.csv`; a.click();
    };

    const copyResults = () => {
        navigator.clipboard.writeText(JSON.stringify(results, null, 2));
        setCopied(true); setTimeout(() => setCopied(false), 2000);
    };

    const copyUrl = (url: string) => {
        navigator.clipboard.writeText(url);
        setCopiedUrl(url); setTimeout(() => setCopiedUrl(null), 2000);
    };

    const sortedResults = [...results].sort((a, b) => {
        if (sortBy === 'likes')    return b.likes    - a.likes;
        if (sortBy === 'plays')    return b.plays    - a.plays;
        if (sortBy === 'comments') return b.comments - a.comments;
        if (sortBy === 'shares')   return b.shares   - a.shares;
        return 0;
    });

    const totalStats = results.reduce((acc, v) => ({
        likes: acc.likes + v.likes, plays: acc.plays + v.plays,
        comments: acc.comments + v.comments, shares: acc.shares + v.shares,
    }), { likes: 0, plays: 0, comments: 0, shares: 0 });

    const currentAction = ACTIONS.find(a => a.value === action)!;

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600 transition-colors">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                                {tool.icon_url
                                    ? <img src={tool.icon_url} className="w-7 h-7 object-contain" alt="" />
                                    : <span className="text-xl">🎵</span>}
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900">{tool.title}</h1>
                                <p className="text-xs text-slate-400">{subscription.plan_name} plan</p>
                            </div>
                        </div>
                    </div>

                    {/* Runtime status */}
                    <div className={`flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border ${
                        rtStatus === 'ok'      ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                        rtStatus === 'offline' ? 'bg-red-50 text-red-600 border-red-200' :
                                                 'bg-slate-50 text-slate-500 border-slate-200'
                    }`}>
                        {rtStatus === 'ok' ? <Wifi className="h-3.5 w-3.5" /> : <WifiOff className="h-3.5 w-3.5" />}
                        {rtStatus === 'ok' ? 'Runtime connected' : rtStatus === 'offline' ? 'Runtime offline' : 'Checking...'}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-6">

                    {/* ── LEFT: Config panel ────────────────────────────────── */}
                    <div className="space-y-5">

                        {/* Action selector */}
                        <div className="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
                            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Scrape Mode</p>
                            <div className="grid grid-cols-2 gap-2">
                                {ACTIONS.map(a => {
                                    const Icon = a.icon;
                                    return (
                                        <button
                                            key={a.value}
                                            onClick={() => { setAction(a.value); setQuery(''); }}
                                            className={`flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium border transition-all ${
                                                action === a.value
                                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                                            }`}
                                        >
                                            <Icon className="h-4 w-4" /> {a.label}
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Query input */}
                            {currentAction.needsQuery && (
                                <div>
                                    <label className="block text-xs font-medium text-slate-500 mb-1.5">
                                        {action === 'keyword' ? 'Keyword' : action === 'hashtag' ? 'Hashtag' : 'Username'}
                                    </label>
                                    <input
                                        type="text"
                                        value={query}
                                        onChange={e => setQuery(e.target.value)}
                                        onKeyDown={e => e.key === 'Enter' && status !== 'running' && handleRun()}
                                        placeholder={currentAction.placeholder}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>
                            )}

                            {/* Max count */}
                            <div>
                                <div className="flex justify-between text-xs font-medium text-slate-500 mb-1.5">
                                    <span>Max results</span>
                                    <span className="text-indigo-600 font-semibold">{maxCount}</span>
                                </div>
                                <input
                                    type="range" min={5} max={100} step={5}
                                    value={maxCount}
                                    onChange={e => setMaxCount(Number(e.target.value))}
                                    className="w-full accent-indigo-600"
                                />
                                <div className="flex justify-between text-[10px] text-slate-400 mt-0.5">
                                    <span>5</span><span>100</span>
                                </div>
                            </div>

                            {/* CSV toggle */}
                            <label className="flex items-center justify-between cursor-pointer">
                                <span className="text-sm text-slate-600">Export CSV</span>
                                <div
                                    onClick={() => setExportCsv(v => !v)}
                                    className={`w-10 h-5 rounded-full transition-colors relative ${exportCsv ? 'bg-indigo-600' : 'bg-slate-200'}`}
                                >
                                    <div className={`absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform ${exportCsv ? 'translate-x-5' : ''}`} />
                                </div>
                            </label>

                            {/* Run / Stop */}
                            {status === 'running' ? (
                                <Button onClick={handleStop} variant="outline" className="w-full gap-2 border-red-200 text-red-600 hover:bg-red-50">
                                    <Square className="h-4 w-4" /> Stop
                                </Button>
                            ) : (
                                <Button
                                    onClick={handleRun}
                                    disabled={rtStatus === 'offline' || (currentAction.needsQuery && !query.trim())}
                                    className="w-full gap-2 bg-indigo-600 hover:bg-indigo-500 text-white h-10"
                                >
                                    <Play className="h-4 w-4" />
                                    {status === 'idle' ? 'Run Scraper' : status === 'done' ? 'Run Again' : 'Run Scraper'}
                                </Button>
                            )}
                        </div>

                        {/* Progress + logs */}
                        {(status === 'running' || logs.length > 0) && (
                            <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3">
                                {status === 'running' && (
                                    <div className="space-y-1.5">
                                        <div className="flex justify-between text-xs text-slate-400">
                                            <span className="flex items-center gap-1.5">
                                                <Loader2 className="h-3 w-3 animate-spin" />
                                                Running... {elapsed}s
                                            </span>
                                            <span>{progress}%</span>
                                        </div>
                                        <div className="h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                            <div className="h-full bg-indigo-500 rounded-full transition-all duration-500" style={{ width: `${progress}%` }} />
                                        </div>
                                    </div>
                                )}
                                <div className="max-h-40 overflow-y-auto space-y-0.5 font-mono text-[11px]">
                                    {logs.map((l, i) => (
                                        <div key={i} className="text-slate-300 leading-relaxed">{l}</div>
                                    ))}
                                    <div ref={logsEnd} />
                                </div>
                            </div>
                        )}

                        {/* Error */}
                        {status === 'error' && (
                            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                <span>{errMsg}</span>
                            </div>
                        )}
                    </div>

                    {/* ── RIGHT: Results ────────────────────────────────────── */}
                    <div>

                        {/* Profile card */}
                        {profileData && (
                            <div className="bg-white border border-slate-200 rounded-xl p-4 mb-4 flex items-center gap-4">
                                {profileData.avatar && <img src={profileData.avatar} className="w-12 h-12 rounded-full object-cover" alt="" />}
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold text-slate-900">@{profileData.username}</p>
                                    <p className="text-xs text-slate-500 truncate">{profileData.bio}</p>
                                </div>
                                <div className="flex gap-4 text-center shrink-0">
                                    {[['Followers', profileData.followers],['Videos', profileData.videos],['Likes', profileData.likes]].map(([l,v]) => (
                                        <div key={String(l)}>
                                            <p className="text-xs font-bold text-slate-900">{fmt(Number(v ?? 0))}</p>
                                            <p className="text-[10px] text-slate-400">{l}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Summary stats */}
                        {results.length > 0 && (
                            <div className="grid grid-cols-4 gap-2 mb-4">
                                {[['❤️ Likes', totalStats.likes],['▶️ Plays', totalStats.plays],['💬 Comments', totalStats.comments],['🔁 Shares', totalStats.shares]].map(([l,v]) => (
                                    <div key={String(l)} className="bg-white border border-slate-200 rounded-lg p-2.5 text-center">
                                        <p className="text-sm font-bold text-slate-900">{fmt(Number(v))}</p>
                                        <p className="text-[10px] text-slate-400">{l}</p>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Toolbar */}
                        {results.length > 0 && (
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                    <span className="text-sm font-semibold text-slate-900">{results.length} videos</span>
                                    {/* Sort */}
                                    <select
                                        value={sortBy}
                                        onChange={e => setSortBy(e.target.value as any)}
                                        className="ml-2 text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white text-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >
                                        <option value="default">Sort: Default</option>
                                        <option value="plays">Most Played</option>
                                        <option value="likes">Most Liked</option>
                                        <option value="comments">Most Comments</option>
                                        <option value="shares">Most Shared</option>
                                    </select>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" onClick={copyResults} className="gap-1.5 h-8 text-xs">
                                        {copied ? <Check className="h-3.5 w-3.5 text-emerald-500" /> : <Copy className="h-3.5 w-3.5" />}
                                        {copied ? 'Copied' : 'JSON'}
                                    </Button>
                                    <Button variant="outline" size="sm" onClick={downloadCsv} className="gap-1.5 h-8 text-xs">
                                        <Download className="h-3.5 w-3.5" /> CSV
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Empty state */}
                        {results.length === 0 && status !== 'running' && (
                            <div className="flex flex-col items-center justify-center h-64 text-center bg-white border border-dashed border-slate-200 rounded-xl">
                                <span className="text-4xl mb-3">🎵</span>
                                <p className="text-sm font-medium text-slate-500">Configure and run the scraper</p>
                                <p className="text-xs text-slate-400 mt-1">Results will appear here</p>
                            </div>
                        )}

                        {/* Running skeleton */}
                        {status === 'running' && results.length === 0 && (
                            <div className="space-y-3">
                                {[...Array(4)].map((_, i) => (
                                    <div key={i} className="bg-white border border-slate-100 rounded-xl p-4 animate-pulse">
                                        <div className="flex gap-3">
                                            <div className="w-16 h-16 rounded-lg bg-slate-100 shrink-0" />
                                            <div className="flex-1 space-y-2">
                                                <div className="h-3 bg-slate-100 rounded w-3/4" />
                                                <div className="h-3 bg-slate-100 rounded w-1/2" />
                                                <div className="h-2 bg-slate-100 rounded w-1/4" />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Results grid */}
                        {sortedResults.length > 0 && (
                            <div className="space-y-3">
                                {sortedResults.map((v, i) => (
                                    <div key={v.id || i} className="bg-white border border-slate-200 rounded-xl overflow-hidden hover:border-slate-300 transition-all">
                                        <div className="flex items-start gap-3 p-4">
                                            {/* Cover thumbnail */}
                                            <div className="w-16 h-20 rounded-lg bg-slate-100 overflow-hidden shrink-0 relative group">
                                                {v.cover_url
                                                    ? <img src={v.cover_url} alt="" className="w-full h-full object-cover" onError={e => { (e.target as any).style.display='none'; }} />
                                                    : <div className="w-full h-full flex items-center justify-center text-2xl">🎵</div>
                                                }
                                                {v.duration_sec > 0 && (
                                                    <span className="absolute bottom-1 right-1 text-[9px] bg-black/70 text-white px-1 rounded">{v.duration_sec}s</span>
                                                )}
                                            </div>

                                            {/* Info */}
                                            <div className="flex-1 min-w-0">
                                                <p className="text-xs font-semibold text-indigo-600">@{v.author} {v.author_name && v.author_name !== v.author && <span className="text-slate-400 font-normal">· {v.author_name}</span>}</p>
                                                <p className="text-sm text-slate-700 mt-0.5 line-clamp-2 leading-snug">{v.description || '—'}</p>

                                                {/* Stats */}
                                                <div className="flex items-center gap-3 mt-2 flex-wrap">
                                                    {[['❤️', fmt(v.likes)],['▶️', fmt(v.plays)],['💬', fmt(v.comments)],['🔁', fmt(v.shares)]].map(([lbl,val]) => (
                                                        <span key={String(lbl)} className="flex items-center gap-0.5 text-[11px] text-slate-500">
                                                            <span>{lbl}</span><span className="font-semibold text-slate-700">{val}</span>
                                                        </span>
                                                    ))}
                                                    <span className="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">ER {v.engagement_rate}</span>
                                                </div>

                                                {v.hashtags && <p className="text-[10px] text-slate-400 mt-1 truncate">{v.hashtags}</p>}

                                                {/* Action buttons */}
                                                <div className="flex items-center gap-2 mt-2.5">
                                                    {v.download_url && (
                                                        <a
                                                            href={v.download_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            download
                                                            className="flex items-center gap-1 text-[11px] font-medium px-2.5 py-1 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 transition-colors"
                                                        >
                                                            <Download className="h-3 w-3" /> Download
                                                        </a>
                                                    )}
                                                    {v.download_url && (
                                                        <button
                                                            onClick={() => copyUrl(v.download_url as string)}
                                                            className="flex items-center gap-1 text-[11px] font-medium px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                                                        >
                                                            {copiedUrl === v.download_url ? <Check className="h-3 w-3 text-emerald-500" /> : <Copy className="h-3 w-3" />}
                                                            {copiedUrl === v.download_url ? 'Copied' : 'Copy URL'}
                                                        </button>
                                                    )}
                                                    <a
                                                        href={`https://tiktok.com/@${v.author}/video/${v.id}`}
                                                        target="_blank" rel="noreferrer"
                                                        className="flex items-center gap-1 text-[11px] text-slate-400 hover:text-slate-600 transition-colors ml-auto"
                                                    >
                                                        View on TikTok ↗
                                                    </a>
                                                </div>
                                            </div>

                                            {/* Expand raw */}
                                            <button
                                                onClick={() => setExpandedId(expandedId === v.id ? null : v.id)}
                                                className="shrink-0 p-1 rounded hover:bg-slate-50 text-slate-400"
                                            >
                                                {expandedId === v.id ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                                            </button>
                                        </div>

                                        {expandedId === v.id && (
                                            <div className="border-t border-slate-100 bg-slate-50 p-4">
                                                <pre className="text-[10px] text-slate-600 overflow-x-auto">{JSON.stringify(v, null, 2)}</pre>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
