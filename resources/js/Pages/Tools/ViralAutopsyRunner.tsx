import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Zap, TrendingUp, MessageSquare, Music, Layout, Clock,
    AlertCircle, CheckCircle, BarChart2, Sparkles, ExternalLink,
    ChevronDown, ChevronUp, RefreshCw
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Score ring component ──────────────────────────────────────────────────────
function ScoreRing({ score, max, label, color }: { score: number; max: number; label: string; color: string }) {
    const pct = Math.round((score / max) * 100);
    const r   = 26;
    const circ = 2 * Math.PI * r;
    const dash = (pct / 100) * circ;
    return (
        <div className="flex flex-col items-center gap-1.5">
            <div className="relative w-16 h-16 flex items-center justify-center">
                <svg className="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 64 64">
                    <circle cx="32" cy="32" r={r} fill="none" stroke="#f1f5f9" strokeWidth="5" />
                    <circle
                        cx="32" cy="32" r={r} fill="none"
                        stroke={color} strokeWidth="5"
                        strokeDasharray={`${dash} ${circ}`}
                        strokeLinecap="round"
                        className="transition-all duration-700"
                    />
                </svg>
                <span className="text-sm font-black text-slate-800">{score}<span className="text-[9px] font-bold text-slate-400">/{max}</span></span>
            </div>
            <span className="text-[9px] font-bold uppercase tracking-wider text-slate-500 text-center leading-tight">{label}</span>
        </div>
    );
}

// ── Verdict badge ─────────────────────────────────────────────────────────────
function VerdictBadge({ score }: { score: number }) {
    const { label, emoji, cls } =
        score >= 80 ? { label: 'Viral Masterpiece', emoji: '🏆', cls: 'bg-amber-50 border-amber-200 text-amber-700' } :
        score >= 65 ? { label: 'Strong Performer',  emoji: '🔥', cls: 'bg-rose-50  border-rose-200  text-rose-700'  } :
        score >= 50 ? { label: 'Above Average',     emoji: '📈', cls: 'bg-emerald-50 border-emerald-200 text-emerald-700' } :
        score >= 35 ? { label: 'Average',           emoji: '📊', cls: 'bg-slate-50  border-slate-200  text-slate-600' } :
                      { label: 'Needs Work',        emoji: '⚠️', cls: 'bg-slate-50  border-slate-200  text-slate-500' };
    return (
        <Badge variant="outline" className={`gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider ${cls}`}>
            {emoji} {label}
        </Badge>
    );
}

// ── Suggestion pill ───────────────────────────────────────────────────────────
function SuggestionPill({ text }: { text: string }) {
    return (
        <div className="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-xl px-4 py-2.5">
            <AlertCircle className="w-3.5 h-3.5 text-amber-500 mt-0.5 shrink-0" />
            <p className="text-xs text-amber-700 font-medium leading-snug">{text}</p>
        </div>
    );
}

// ── Stat chip ─────────────────────────────────────────────────────────────────
function StatChip({ label, value, highlight }: { label: string; value: string; highlight?: boolean }) {
    return (
        <div className={`flex flex-col p-3 rounded-xl border ${highlight ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100'}`}>
            <span className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">{label}</span>
            <span className={`text-sm font-black ${highlight ? 'text-emerald-700' : 'text-slate-700'}`}>{value}</span>
        </div>
    );
}

// ── Collapsible section ───────────────────────────────────────────────────────
function Section({ icon: Icon, title, children, color = 'text-slate-600' }: any) {
    const [open, setOpen] = useState(true);
    return (
        <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <Button
                variant="ghost"
                onClick={() => setOpen(v => !v)}
                className="w-full h-auto flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition-colors rounded-none"
            >
                <div className="flex items-center gap-2.5">
                    <Icon className={`w-4 h-4 ${color}`} />
                    <h3 className="font-bold text-slate-800 text-sm">{title}</h3>
                </div>
                {open ? <ChevronUp className="w-4 h-4 text-slate-400" /> : <ChevronDown className="w-4 h-4 text-slate-400" />}
            </Button>
            {open && <div className="px-5 pb-5 pt-1">{children}</div>}
        </div>
    );
}

// ── Main component ────────────────────────────────────────────────────────────
export default function ViralAutopsyRunner({ tool }: any) {
    const [url, setUrl]         = useState('');
    const [status, setStatus]   = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [result, setResult]   = useState<any>(null);
    const [errorMsg, setError]  = useState('');
    const [taskId, setTaskId]   = useState<string | null>(null);
    const wsRef = useRef<WebSocket | null>(null);

    // Subscribe to runtime WS events
    useEffect(() => {
        const ws = new WebSocket(getWsUrl());
        wsRef.current = ws;

        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                const ev  = msg.event;
                const d   = msg.data;

                if (ev === 'task.progress' && d?.taskId === taskId) {
                    setProgress(d.percent ?? 0);
                    setProgressMsg(d.message ?? '');
                }
                if (ev === 'task.done' && d?.taskId === taskId) {
                    if (d.result) { setResult(d.result); setStatus('done'); }
                    else { setError('Analysis returned no data.'); setStatus('error'); }
                }
                if (ev === 'task.error' && d?.taskId === taskId) {
                    setError(d.error ?? 'Unknown error'); setStatus('error');
                }
            } catch {}
        };
        return () => ws.close();
    }, [taskId]);

    const handleAnalyze = async () => {
        if (!url.trim()) return;
        setStatus('running'); setProgress(5); setProgressMsg('Starting analysis...'); setResult(null); setError('');

        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/viral-autopsy/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { url: url.trim() } }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Failed to start');
            setTaskId(json.taskId);
        } catch (err: any) {
            setError(err.message); setStatus('error');
        }
    };

    // Also poll task if WS doesn't fire
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

    const vs = result?.viral_score;
    const eng = result?.engagement;
    const cap = result?.caption;
    const snd = result?.sound;
    const str = result?.structure;
    const tim = result?.timing;

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* Top bar */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-rose-500 to-orange-500 rounded-lg flex items-center justify-center shadow-sm">
                        <Zap className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">{__('general.viral_videos_finder')}</span>
                </div>
                <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                    {status === 'running' ? 'Analyzing...' : status === 'done' ? 'Analysis Complete' : 'Ready'}
                </Badge>
            </div>

            <div className="max-w-4xl mx-auto px-4 py-8 space-y-6">

                {/* URL Input card */}
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">{__('general.why_did_it_go_viral')}</h1>
                        <p className="text-sm text-slate-400 mt-1">{__('general.paste_any_tiktok_url_get_a_full_breakdown_with_a_viral_score_out_of_100')}</p>
                    </div>

                    <div className="flex gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input
                                type="url"
                                value={url}
                                onChange={e => setUrl(e.target.value)}
                                onKeyDown={e => e.key === 'Enter' && handleAnalyze()}
                                placeholder={__('general.https_www_tiktok_com_user_video')}
                                className="pl-9 h-11 text-sm bg-slate-50 font-mono"
                            />
                        </div>
                        <Button
                            onClick={handleAnalyze}
                            disabled={!url.trim() || status === 'running'}
                            className="gap-2 px-5 h-11 bg-gradient-to-r from-rose-500 to-orange-500 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md"
                        >
                            {status === 'running' ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Zap className="w-4 h-4" />}
                            {status === 'running' ? 'Analyzing...' : 'Analyze'}
                        </Button>
                    </div>

                    {/* Progress bar */}
                    {status === 'running' && (
                        <div className="space-y-1.5 animate-in fade-in duration-300">
                            <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                <span>{progressMsg}</span>
                                <span>{progress}%</span>
                            </div>
                            <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    className="h-full bg-gradient-to-r from-rose-500 to-orange-400 transition-all duration-500 rounded-full"
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
                    <div className="space-y-5 animate-in fade-in slide-in-from-bottom-3 duration-400">

                        {/* Overall Viral Score hero */}
                        <div className="bg-gradient-to-tr from-slate-900 to-slate-800 border border-slate-700 rounded-2xl p-6 text-white relative overflow-hidden shadow-xl">
                            <div className="absolute top-0 right-0 w-64 h-64 bg-rose-500/10 rounded-full blur-3xl -z-0" />
                            <div className="relative z-10 flex flex-col md:flex-row items-center gap-6">

                                {/* Big score */}
                                <div className="flex flex-col items-center shrink-0">
                                    <div className="relative w-28 h-28 flex items-center justify-center">
                                        <svg className="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 112 112">
                                            <circle cx="56" cy="56" r="44" fill="none" stroke="#1e293b" strokeWidth="8" />
                                            <circle
                                                cx="56" cy="56" r="44" fill="none"
                                                stroke={vs.total >= 65 ? '#f43f5e' : vs.total >= 50 ? '#fb923c' : '#94a3b8'}
                                                strokeWidth="8"
                                                strokeDasharray={`${(vs.total / 100) * 276} 276`}
                                                strokeLinecap="round"
                                                className="transition-all duration-1000"
                                            />
                                        </svg>
                                        <div className="text-center">
                                            <div className="text-3xl font-black text-white">{vs.total}</div>
                                            <div className="text-[9px] font-bold text-slate-400 uppercase">/ 100</div>
                                        </div>
                                    </div>
                                    <VerdictBadge score={vs.total} />
                                </div>

                                {/* Score breakdown */}
                                <div className="flex-1">
                                    <h2 className="text-base font-bold text-white mb-1">{result.video?.author_name || result.video?.author}</h2>
                                    <p className="text-xs text-slate-400 line-clamp-2 mb-4">{result.video?.caption}</p>
                                    <div className="flex flex-wrap gap-4">
                                        {Object.values(vs.breakdown).map((dim: any) => (
                                            <ScoreRing
                                                key={dim.label}
                                                score={dim.score} max={dim.max} label={dim.label}
                                                color={dim.score / dim.max >= 0.7 ? '#10b981' : dim.score / dim.max >= 0.4 ? '#f59e0b' : '#f43f5e'}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Engagement metrics */}
                        <Section icon={TrendingUp} title={__('general.engagement_metrics')} color="text-emerald-600">
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                <StatChip label="Plays" value={Number(eng.plays).toLocaleString()} highlight />
                                <StatChip label={__('general.like_rate')} value={eng.like_rate} highlight={parseFloat(eng.like_rate) > 5} />
                                <StatChip label={__('general.comment_rate')} value={eng.comment_rate} />
                                <StatChip label={__('general.share_rate')} value={eng.share_rate} />
                            </div>
                            {eng.signals?.length > 0 && (
                                <div className="space-y-2">
                                    {eng.signals.map((s: string, i: number) => (
                                        <div key={i} className="flex items-start gap-2 bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2">
                                            <CheckCircle className="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0" />
                                            <p className="text-xs text-emerald-800 font-medium">{s}</p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Section>

                        {/* Caption & Hook */}
                        <Section icon={MessageSquare} title={__('general.caption_hook_analysis')} color="text-violet-600">
                            <div className="grid grid-cols-2 gap-3 mb-4">
                                <StatChip label={__('general.hook_type')} value={cap.hook_type_label} />
                                <StatChip label={__('general.caption_score')} value={`${cap.score}/30`} highlight={cap.score >= 20} />
                                <StatChip label="Hashtags" value={`${cap.hashtag_count} tags`} />
                                <StatChip label={__('general.word_count')} value={`${cap.word_count} words`} highlight={cap.word_count <= 30} />
                            </div>
                            {cap.triggers?.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 mb-4">
                                    {cap.triggers.map((t: string) => (
                                        <Badge variant="outline" key={t} className="bg-violet-50 border-violet-100 text-violet-700 rounded-full text-[10px] font-bold uppercase tracking-wider">{t}</Badge>
                                    ))}
                                </div>
                            )}
                            {cap.suggestions?.map((s: string, i: number) => <SuggestionPill key={i} text={s} />)}
                        </Section>

                        {/* Sound strategy */}
                        <Section icon={Music} title={__('general.sound_strategy')} color="text-pink-600">
                            <div className="grid grid-cols-2 gap-3 mb-3">
                                <StatChip label="Sound" value={snd.title} />
                                <StatChip label="Type" value={snd.is_original ? 'Original Audio' : 'Trending Sound'} highlight={!snd.is_original} />
                            </div>
                            {snd.analysis?.map((s: string, i: number) => (
                                <div key={i} className="flex items-start gap-2 bg-pink-50 border border-pink-100 rounded-xl px-3 py-2 mb-2">
                                    <Music className="w-3.5 h-3.5 text-pink-400 mt-0.5 shrink-0" />
                                    <p className="text-xs text-pink-800 font-medium">{s}</p>
                                </div>
                            ))}
                            {snd.suggestions?.map((s: string, i: number) => <SuggestionPill key={i} text={s} />)}
                        </Section>

                        {/* Content structure */}
                        <Section icon={Layout} title={__('general.content_structure')} color="text-blue-600">
                            <div className="grid grid-cols-2 gap-3 mb-3">
                                <StatChip label="Duration" value={`${str.duration_seconds}s`} highlight={str.duration_seconds <= 30} />
                                <StatChip label="Format" value={str.duration_label?.replace('_', ' ')} />
                            </div>
                            {str.analysis?.map((s: string, i: number) => (
                                <div key={i} className="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl px-3 py-2 mb-2">
                                    <Layout className="w-3.5 h-3.5 text-blue-400 mt-0.5 shrink-0" />
                                    <p className="text-xs text-blue-800 font-medium">{s}</p>
                                </div>
                            ))}
                        </Section>

                        {/* Posting timing */}
                        <Section icon={Clock} title={__('general.posting_timing')} color="text-teal-600">
                            <div className="grid grid-cols-3 gap-3 mb-3">
                                <StatChip label="Day" value={tim.day} />
                                <StatChip label={__('general.hour_utc')} value={`${tim.hour_utc}:00`} />
                                <StatChip label={__('general.peak_hour')} value={tim.is_peak ? '✓ Yes' : '✗ No'} highlight={tim.is_peak} />
                            </div>
                            {tim.suggestions?.map((s: string, i: number) => <SuggestionPill key={i} text={s} />)}
                        </Section>

                        {/* All suggestions */}
                        {result.suggestions?.length > 0 && (
                            <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-3">
                                <div className="flex items-center gap-2 mb-2">
                                    <Sparkles className="w-4 h-4 text-amber-500" />
                                    <h3 className="font-bold text-slate-800 text-sm">{result.suggestions.length} Action Items</h3>
                                </div>
                                {result.suggestions.map((s: string, i: number) => (
                                    <SuggestionPill key={i} text={s} />
                                ))}
                            </div>
                        )}

                        {/* View original */}
                        <a
                            href={url} target="_blank" rel="noopener noreferrer"
                            className="flex items-center justify-center gap-2 w-full py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all"
                        >
                            <ExternalLink className="w-4 h-4" />{__('general.view_original_video')}</a>
                    </div>
                )}
            </div>
        </div>
    );
}
