import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Loader2, AlertCircle, Wifi, WifiOff, Copy, Check, Zap, Dna
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category?: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function FormatExtractorRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [url, setUrl]           = useState('');
    const [niche, setNiche]       = useState('');
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
        if (!url.trim()) return;
        setStatus('running'); setLogs([]); setResult(null); setErrMsg(''); setProgress(0); setElapsed(0);
        const start = Date.now();
        timerRef.current = setInterval(() => setElapsed(Math.floor((Date.now() - start) / 1000)), 1000);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { url: url.trim(), niche: niche.trim() } }),
            });
            const data = await res.json();
            if (!res.ok) { setErrMsg(data.message || data.error || 'Error'); setStatus('error'); return; }
            connectWs(data.taskId);
            startPolling(data.taskId);
        } catch { setErrMsg('Cannot reach runtime'); setStatus('error'); clearInterval(timerRef.current); }
    };

    const copyBlueprint = () => {
        if (!result?.blueprint) return;
        const bp = result.blueprint;
        const text = `FORMAT BLUEPRINT\n${'='.repeat(40)}\n\nHOOK TYPE: ${bp.hook.type_label}\nTEMPLATE: ${bp.hook.template}\n\nSTRUCTURE: ${bp.structure.name}\n${bp.structure.template}\n\nDURATION: ${bp.duration.seconds}s (${bp.duration.format})\n${bp.duration.recommendation}\n\nSOUND: ${bp.sound.template}\n\nCAPTION FORMAT:\n${bp.caption.template}\nHashtag strategy: ${bp.caption.hashtag_strategy}`;
        navigator.clipboard.writeText(text);
        setCopied(true); setTimeout(() => setCopied(false), 2000);
    };

    const bp = result?.blueprint;

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Format Extractor`} />
            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600"><ArrowLeft className="h-5 w-5" /></Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                                <span className="text-xl">🧬</span>
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
                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Extract Format Blueprint</p>
                    <div className="flex gap-3">
                        <input type="text" value={url} onChange={e => setUrl(e.target.value)} onKeyDown={e => e.key === 'Enter' && handleRun()}
                            placeholder="Paste a viral TikTok URL..."
                            className="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                        <input type="text" value={niche} onChange={e => setNiche(e.target.value)}
                            placeholder="Niche (optional)"
                            className="w-40 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                        <Button onClick={handleRun} disabled={rtStatus === 'offline' || !url.trim() || status === 'running'}
                            className="gap-2 bg-gradient-to-r from-cyan-600 to-blue-500 text-white px-6 h-[46px]">
                            {status === 'running' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Dna className="h-4 w-4" />}
                            Extract
                        </Button>
                    </div>
                    {status === 'running' && (
                        <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div className="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all" style={{ width: `${progress}%` }} />
                        </div>
                    )}
                </div>

                {status === 'error' && (
                    <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 mb-6">
                        <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /><span>{errMsg}</span>
                    </div>
                )}

                {/* Blueprint Result */}
                {bp && (
                    <div className="space-y-4 animate-in fade-in duration-500">

                        {/* Source video */}
                        {result.source && (
                            <div className="bg-slate-900 rounded-xl p-5 text-white flex items-center gap-4">
                                {result.source.cover_url && <img src={result.source.cover_url} alt="" className="w-14 h-18 rounded-lg object-cover" />}
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-semibold">@{result.source.author}</p>
                                    <p className="text-xs text-slate-400">{result.source.performance_tier}</p>
                                </div>
                                <div className="flex gap-3 text-xs text-slate-400">
                                    <span>❤️ {result.source.like_rate}</span>
                                    <span>🔁 {result.source.share_rate}</span>
                                </div>
                            </div>
                        )}

                        {/* Blueprint cards */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {/* Hook */}
                            <div className="bg-white border border-slate-200 rounded-xl p-5">
                                <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">🎣 Hook Template</h3>
                                <span className="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 font-medium">{bp.hook.type_label}</span>
                                <div className="bg-purple-50 rounded-lg p-3 mt-3">
                                    <p className="text-sm font-mono text-purple-800">{bp.hook.template}</p>
                                </div>
                                <p className="text-[10px] text-slate-400 mt-2">Original: "{bp.hook.original}"</p>
                            </div>

                            {/* Structure */}
                            <div className="bg-white border border-slate-200 rounded-xl p-5">
                                <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">🏗️ Content Structure</h3>
                                <p className="text-sm font-semibold text-slate-800 mb-1">{bp.structure.name}</p>
                                <p className="text-xs text-slate-500 mb-3">{bp.structure.description}</p>
                                <pre className="bg-slate-50 rounded-lg p-3 text-[11px] text-slate-600 font-mono whitespace-pre-wrap">{bp.structure.template}</pre>
                            </div>

                            {/* Duration */}
                            <div className="bg-white border border-slate-200 rounded-xl p-5">
                                <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">⏱️ Duration</h3>
                                <p className="text-2xl font-black text-slate-800">{bp.duration.seconds}s</p>
                                <span className="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 font-medium">{bp.duration.format}</span>
                                <p className="text-xs text-slate-500 mt-2">{bp.duration.recommendation}</p>
                            </div>

                            {/* Sound */}
                            <div className="bg-white border border-slate-200 rounded-xl p-5">
                                <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">🎵 Sound Strategy</h3>
                                <p className="text-sm text-slate-700 font-medium">{bp.sound.sound_title}</p>
                                {bp.sound.is_original && <span className="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-600">Original Sound</span>}
                                <p className="text-xs text-slate-500 mt-2">{bp.sound.recommendation}</p>
                            </div>
                        </div>

                        {/* Caption Template */}
                        <div className="bg-white border border-slate-200 rounded-xl p-5">
                            <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">📝 Caption Format</h3>
                            <pre className="bg-slate-50 rounded-lg p-3 text-sm text-slate-700 font-mono">{bp.caption.template}</pre>
                            <div className="flex gap-4 mt-3 text-xs text-slate-500">
                                <span>Hashtags: {bp.caption.hashtag_strategy}</span>
                                <span>Emojis: {bp.caption.emoji_usage}</span>
                                <span>CTA: {bp.caption.has_cta ? '✅ Yes' : '❌ No'}</span>
                            </div>
                        </div>

                        {/* Quick Summary */}
                        {result.quick_summary && (
                            <div className="bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 rounded-xl p-5">
                                <h3 className="text-xs font-semibold text-cyan-600 uppercase tracking-wider mb-3">⚡ Quick Blueprint</h3>
                                <div className="grid grid-cols-2 md:grid-cols-5 gap-3 text-center">
                                    {Object.entries(result.quick_summary).map(([k, v]) => (
                                        <div key={k} className="bg-white rounded-lg p-2.5 border border-cyan-100">
                                            <p className="text-[9px] text-slate-400 uppercase">{k.replace(/_/g, ' ')}</p>
                                            <p className="text-xs font-semibold text-slate-700 mt-0.5">{String(v)}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <Button variant="outline" size="sm" onClick={copyBlueprint} className="gap-1.5">
                            {copied ? <Check className="h-3.5 w-3.5 text-emerald-500" /> : <Copy className="h-3.5 w-3.5" />}
                            {copied ? 'Copied!' : 'Copy Blueprint as Text'}
                        </Button>
                    </div>
                )}

                {!result && status !== 'running' && (
                    <div className="flex flex-col items-center justify-center h-64 text-center bg-white border border-dashed border-slate-200 rounded-xl">
                        <span className="text-4xl mb-3">🧬</span>
                        <p className="text-sm font-medium text-slate-500">Paste a viral TikTok URL</p>
                        <p className="text-xs text-slate-400 mt-1">Extract its format DNA — hook template, structure, sound strategy</p>
                    </div>
                )}
            </div>
        </ToolsPublicLayout>
    );
}
