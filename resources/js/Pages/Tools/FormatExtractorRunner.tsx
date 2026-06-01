import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Dna, Music, Layout, Clock, AlignLeft, Copy,
    CheckCircle, AlertCircle, RefreshCw, TrendingUp, Sparkles
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Copy-to-clipboard button ──────────────────────────────────────────────────
function CopyButton({ text }: { text: string }) {
    const [copied, setCopied] = useState(false);
    const copy = () => {
        navigator.clipboard.writeText(text).then(() => { setCopied(true); setTimeout(() => setCopied(false), 2000); });
    };
    return (
        <Button
            variant="ghost"
            size="sm"
            onClick={copy}
            className="flex items-center gap-1 h-7 text-[10px] font-bold text-slate-400 hover:text-slate-700 transition-colors px-2 rounded-lg hover:bg-slate-100"
        >
            {copied ? <CheckCircle className="w-3 h-3 text-emerald-500" /> : <Copy className="w-3 h-3" />}
            {copied ? 'Copied!' : 'Copy'}
        </Button>
    );
}

// ── Blueprint section block ───────────────────────────────────────────────────
function BlueprintBlock({
    icon: Icon, label, value, subvalue, copyable, color = 'bg-slate-50 border-slate-200', iconColor = 'text-slate-500'
}: {
    icon: any; label: string; value: string; subvalue?: string; copyable?: boolean; color?: string; iconColor?: string;
}) {
    return (
        <div className={`border rounded-2xl p-4 space-y-2.5 ${color}`}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Icon className={`w-4 h-4 ${iconColor}`} />
                    <span className="text-[9px] font-black uppercase tracking-wider text-slate-500">{label}</span>
                </div>
                {copyable && <CopyButton text={value} />}
            </div>
            <p className="text-sm font-bold text-slate-800 leading-snug">{value}</p>
            {subvalue && <p className="text-xs text-slate-500 leading-relaxed">{subvalue}</p>}
        </div>
    );
}

// ── Multiline code blueprint block ────────────────────────────────────────────
function CodeBlueprint({ title, content }: { title: string; content: string }) {
    return (
        <div className="bg-slate-900 border border-slate-700 rounded-2xl p-4 space-y-2 relative">
            <div className="flex items-center justify-between">
                <span className="text-[9px] font-black uppercase tracking-wider text-slate-400">{title}</span>
                <CopyButton text={content} />
            </div>
            <pre className="text-xs text-emerald-400 font-mono whitespace-pre-wrap leading-relaxed">{content}</pre>
        </div>
    );
}

// ── Performance tier badge ────────────────────────────────────────────────────
function TierBadge({ tier }: { tier: string }) {
    const isMega  = tier?.includes('Mega');
    const isViral = tier?.includes('Viral');
    const cls = isMega ? 'bg-amber-50 border-amber-200 text-amber-700' :
                isViral ? 'bg-rose-50 border-rose-200 text-rose-700' :
                          'bg-slate-100 border-slate-200 text-slate-600';
    return (
        <span className={`inline-flex items-center px-3 py-1 rounded-full border text-xs font-extrabold uppercase tracking-wider ${cls}`}>
            {tier}
        </span>
    );
}

// ── Main component ────────────────────────────────────────────────────────────
export default function FormatExtractorRunner({ tool }: any) {
    const [url, setUrl]     = useState('');
    const [niche, setNiche] = useState('');
    const [status, setStatus]     = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [result, setResult]     = useState<any>(null);
    const [errorMsg, setError]    = useState('');
    const [taskId, setTaskId]     = useState<string | null>(null);

    useEffect(() => {
        const ws = new WebSocket(getWsUrl());
        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.event === 'task.progress' && msg.data?.taskId === taskId) {
                    setProgress(msg.data.percent ?? 0);
                    setProgressMsg(msg.data.message ?? '');
                }
                if (msg.event === 'task.done' && msg.data?.taskId === taskId) {
                    if (msg.data.result) { setResult(msg.data.result); setStatus('done'); }
                    else { setError('No blueprint returned.'); setStatus('error'); }
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

    const handleExtract = async () => {
        if (!url.trim()) return;
        setStatus('running'); setProgress(5); setProgressMsg('Fetching video...'); setResult(null); setError('');
        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/format-extractor/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { url: url.trim(), niche: niche.trim() } }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Failed to start');
            setTaskId(json.taskId);
        } catch (err: any) {
            setError(err.message); setStatus('error');
        }
    };

    const bp  = result?.blueprint;
    const src = result?.source;
    const qs  = result?.quick_summary;

    // Full blueprint as copyable text
    const fullBlueprint = bp ? [
        `HOOK: ${qs?.hook_template}`,
        `STRUCTURE: ${qs?.structure}`,
        `DURATION: ${qs?.duration_target}`,
        `SOUND: ${qs?.sound_strategy}`,
        `CAPTION FORMAT: ${qs?.caption_format}`,
        '',
        `STRUCTURE TEMPLATE:`,
        bp.structure?.template,
        '',
        `SOUND RECOMMENDATION:`,
        bp.sound?.recommendation,
    ].join('\n') : '';

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* Top bar */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-teal-500 to-emerald-500 rounded-lg flex items-center justify-center shadow-sm">
                        <Dna className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">{__('general.format_dna_extractor')}</span>
                </div>
                <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                    {status === 'running' ? 'Extracting...' : status === 'done' ? 'Blueprint Ready' : 'Ready'}
                </Badge>
            </div>

            <div className="max-w-3xl mx-auto px-4 py-8 space-y-6">

                {/* Input card */}
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">{__('general.extract_the_viral_format')}</h1>
                        <p className="text-sm text-slate-400 mt-1">{__('general.not_copying_content_copying_the_psychology_and_structure')}</p>
                    </div>

                    <div className="space-y-3">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input
                                type="url"
                                value={url}
                                onChange={e => setUrl(e.target.value)}
                                onKeyDown={e => e.key === 'Enter' && handleExtract()}
                                placeholder={__('general.https_www_tiktok_com_user_video')}
                                className="pl-9 h-11 text-sm bg-slate-50 font-mono"
                            />
                        </div>
                        <Input
                            type="text"
                            value={niche}
                            onChange={e => setNiche(e.target.value)}
                            placeholder={__('general.niche_optional_e_g_fitness_finance_food')}
                            className="h-11 text-sm bg-slate-50"
                        />
                    </div>

                    <Button
                        onClick={handleExtract}
                        disabled={!url.trim() || status === 'running'}
                        className="w-full h-12 bg-gradient-to-r from-teal-500 to-emerald-500 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md flex items-center justify-center gap-2"
                    >
                        {status === 'running' ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Dna className="w-4 h-4" />}
                        {status === 'running' ? 'Extracting Blueprint...' : 'Extract Format'}
                    </Button>

                    {/* Progress */}
                    {status === 'running' && (
                        <div className="space-y-1.5 animate-in fade-in duration-300">
                            <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                <span>{progressMsg}</span><span>{progress}%</span>
                            </div>
                            <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    className="h-full bg-gradient-to-r from-teal-500 to-emerald-400 transition-all duration-500 rounded-full"
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

                {/* Blueprint results */}
                {status === 'done' && result && bp && (
                    <div className="space-y-4 animate-in fade-in slide-in-from-bottom-3 duration-400">

                        {/* Source hero */}
                        <div className="bg-gradient-to-tr from-slate-900 to-teal-950 border border-teal-800 rounded-2xl p-5 text-white shadow-xl relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-48 h-48 bg-teal-400/10 rounded-full blur-3xl" />
                            <div className="relative z-10">
                                <div className="flex items-start justify-between gap-4 mb-3">
                                    <div>
                                        <p className="text-[9px] font-black uppercase tracking-wider text-teal-400 mb-1">{__('general.source_video')}</p>
                                        <p className="text-sm font-bold">@{src?.author}</p>
                                        <p className="text-xs text-slate-400 mt-1 font-mono truncate">{url}</p>
                                    </div>
                                    {src?.performance_tier && <TierBadge tier={src.performance_tier} />}
                                </div>
                                <div className="flex items-center gap-4 pt-3 border-t border-teal-900">
                                    <div>
                                        <p className="text-[9px] font-bold text-slate-400 uppercase">Plays</p>
                                        <p className="text-sm font-black text-white">{Number(src?.plays).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p className="text-[9px] font-bold text-slate-400 uppercase">{__('general.like_rate')}</p>
                                        <p className="text-sm font-black text-white">{src?.like_rate}</p>
                                    </div>
                                    <div>
                                        <p className="text-[9px] font-bold text-slate-400 uppercase">Niche</p>
                                        <p className="text-sm font-black text-white capitalize">{result.niche_context}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Quick-copy full blueprint */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Sparkles className="w-4 h-4 text-teal-500" />
                                    <h3 className="font-bold text-slate-800 text-sm">{__('general.your_format_blueprint')}</h3>
                                </div>
                                <CopyButton text={fullBlueprint} />
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <BlueprintBlock icon={AlignLeft} label={__('general.hook_template')} value={qs?.hook_template} copyable color="bg-teal-50 border-teal-200" iconColor="text-teal-600" />
                                <BlueprintBlock icon={Layout} label="Structure" value={qs?.structure} copyable color="bg-violet-50 border-violet-200" iconColor="text-violet-600" />
                                <BlueprintBlock icon={Clock} label={__('general.target_duration')} value={qs?.duration_target} color="bg-blue-50 border-blue-200" iconColor="text-blue-600" />
                                <BlueprintBlock icon={Music} label={__('general.sound_strategy')} value={qs?.sound_strategy} color="bg-pink-50 border-pink-200" iconColor="text-pink-600" />
                            </div>
                        </div>

                        {/* Structure template */}
                        <CodeBlueprint
                            title={`Structure: ${bp.structure?.name}`}
                            content={bp.structure?.template}
                        />

                        {/* Hook section */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                            <h3 className="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <AlignLeft className="w-4 h-4 text-teal-500" />{__('general.hook_breakdown')}</h3>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="bg-slate-50 border border-slate-100 rounded-xl p-3">
                                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">Type</p>
                                    <p className="text-xs font-bold text-slate-800">{bp.hook?.type_label}</p>
                                </div>
                                <div className="bg-slate-50 border border-slate-100 rounded-xl p-3">
                                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">{__('general.original_hook')}</p>
                                    <p className="text-xs font-medium text-slate-600 italic line-clamp-2">"{bp.hook?.original}"</p>
                                </div>
                            </div>
                            <div className="bg-teal-900 rounded-xl p-3 flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-wider text-teal-400 mb-1">{__('general.fill_in_template')}</p>
                                    <p className="text-xs font-bold text-white">{bp.hook?.template}</p>
                                </div>
                                <CopyButton text={bp.hook?.template} />
                            </div>
                        </div>

                        {/* Caption format */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                            <h3 className="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <AlignLeft className="w-4 h-4 text-slate-500" />{__('general.caption_format')}</h3>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                {[
                                    { l: 'Hashtag Strategy', v: bp.caption?.hashtag_strategy },
                                    { l: 'Emoji Usage', v: bp.caption?.emoji_usage },
                                    { l: 'Has CTA', v: bp.caption?.has_cta ? '✓ Yes' : '✗ No' },
                                    { l: 'Lines', v: `${bp.caption?.line_count} line${bp.caption?.line_count !== 1 ? 's' : ''}` },
                                ].map(({ l, v }) => (
                                    <div key={l} className="bg-slate-50 border border-slate-100 rounded-xl p-2.5">
                                        <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">{l}</p>
                                        <p className="text-xs font-bold text-slate-700 capitalize">{v}</p>
                                    </div>
                                ))}
                            </div>
                            <div className="bg-slate-900 rounded-xl p-3 flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">{__('general.caption_template')}</p>
                                    <p className="text-xs font-bold text-emerald-400 font-mono whitespace-pre-wrap">{bp.caption?.template}</p>
                                </div>
                                <CopyButton text={bp.caption?.template} />
                            </div>
                        </div>

                        {/* Sound strategy */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                            <h3 className="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <Music className="w-4 h-4 text-pink-500" />{__('general.sound_strategy')}</h3>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="bg-pink-50 border border-pink-100 rounded-xl p-3">
                                    <p className="text-[9px] font-black uppercase tracking-wider text-pink-400 mb-1">Strategy</p>
                                    <p className="text-xs font-bold text-pink-800 capitalize">{bp.sound?.strategy?.replace('_', ' ')}</p>
                                </div>
                                <div className="bg-pink-50 border border-pink-100 rounded-xl p-3">
                                    <p className="text-[9px] font-black uppercase tracking-wider text-pink-400 mb-1">{__('general.sound_used')}</p>
                                    <p className="text-xs font-bold text-pink-800 truncate">{bp.sound?.sound_title || 'Unknown'}</p>
                                </div>
                            </div>
                            <div className="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-start justify-between gap-3">
                                <p className="text-xs text-slate-600 leading-relaxed">{bp.sound?.recommendation}</p>
                            </div>
                            <div className="bg-slate-900 rounded-xl p-3 flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">{__('general.your_sound_template')}</p>
                                    <p className="text-xs font-bold text-emerald-400">{bp.sound?.template}</p>
                                </div>
                                <CopyButton text={bp.sound?.template} />
                            </div>
                        </div>

                        {/* Duration recommendation */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                            <h3 className="font-bold text-slate-800 text-sm flex items-center gap-2 mb-3">
                                <Clock className="w-4 h-4 text-blue-500" />{__('general.duration_guide')}</h3>
                            <div className="flex items-center gap-4">
                                <div className="text-center">
                                    <div className="text-3xl font-black text-blue-700">{bp.duration?.seconds}s</div>
                                    <p className="text-[9px] font-bold uppercase text-blue-400">Original</p>
                                </div>
                                <div className="flex-1 bg-blue-50 border border-blue-100 rounded-xl p-3">
                                    <p className="text-xs text-blue-700 font-medium leading-relaxed">{bp.duration?.recommendation}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
