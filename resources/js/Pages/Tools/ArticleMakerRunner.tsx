import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Search, Globe, Download, Play, Square, AlertCircle, CheckCircle2,
    RefreshCw, Zap, ArrowLeft, Calendar, Hash, ExternalLink,
    ChevronDown, Clipboard, History, FileText, Settings,
    BookOpen, Tag, Send, Link2, Key, Clock, BarChart3,
    Lightbulb, Sparkles, Type, List,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Audiences (subset for the dropdown) ──────────────────────────────────────
const AUDIENCES = [
    { value: 'en-us', label: 'United States (en)' },
    { value: 'en-uk', label: 'United Kingdom (en)' },
    { value: 'en-ca', label: 'Canada (en)' },
    { value: 'en-au', label: 'Australia (en)' },
    { value: 'en-in', label: 'India (en)' },
    { value: 'de-de', label: 'Germany (de)' },
    { value: 'fr-fr', label: 'France (fr)' },
    { value: 'es-es', label: 'Spain (es)' },
    { value: 'es-mx', label: 'Mexico (es)' },
    { value: 'pt-br', label: 'Brazil (pt)' },
    { value: 'it-it', label: 'Italy (it)' },
    { value: 'nl-nl', label: 'Netherlands (nl)' },
    { value: 'pl-pl', label: 'Poland (pl)' },
    { value: 'ru-ru', label: 'Russia (ru)' },
    { value: 'tr-tr', label: 'Turkey (tr)' },
    { value: 'ar-eg', label: 'Egypt (ar)' },
    { value: 'ar-sa', label: 'Saudi Arabia (ar)' },
    { value: 'ar-ae', label: 'UAE (ar)' },
    { value: 'ar-ma', label: 'Morocco (ar)' },
    { value: 'ar-jo', label: 'Jordan (ar)' },
    { value: 'jp-jp', label: 'Japan (jp)' },
];

const SOURCES = [
    { value: 'google', label: 'Google', icon: '🔍' },
    { value: 'youtube', label: 'YouTube', icon: '▶️' },
];

// ── Keyword Row ──────────────────────────────────────────────────────────────
function KeywordRow({ kw, idx }: { kw: any; idx: number }) {
    const [copied, setCopied] = useState(false);

    const copyRow = () => {
        const text = [kw.title, kw.subtitle].filter(Boolean).join('\t');
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    return (
        <div className="group flex items-center gap-3 px-5 py-3 hover:bg-slate-50/80 transition-colors border-b border-slate-100/80 last:border-0">
            <div className="w-7 h-7 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white text-[9px] font-black shrink-0 shadow-sm">
                {idx + 1}
            </div>
            <div className="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2 min-w-0 items-center">
                <div className="md:col-span-1 min-w-0">
                    <p className="text-xs font-semibold text-slate-800 truncate">{kw.title || '—'}</p>
                </div>
                <div className="md:col-span-2 min-w-0">
                    <p className="text-[11px] text-slate-500 truncate" title={kw.subtitle || ''}>
                        {kw.subtitle || <span className="text-slate-300 italic">No subtitles yet</span>}
                    </p>
                </div>
            </div>
            {kw.published_id > 0 && (
                <Badge variant="outline" className="text-[9px] font-bold bg-emerald-50 border-emerald-200 text-emerald-700 shrink-0">
                    Published
                </Badge>
            )}
            <Button
                variant="ghost" size="icon"
                onClick={copyRow}
                className="opacity-0 group-hover:opacity-100 transition-opacity h-7 w-7 hover:bg-slate-100"
                title="Copy row"
            >
                {copied ? <CheckCircle2 className="w-3 h-3 text-emerald-500" /> : <Clipboard className="w-3 h-3 text-slate-400" />}
            </Button>
        </div>
    );
}

// ── Stat Card ────────────────────────────────────────────────────────────────
function StatCard({ label, value, icon: Icon, color = 'bg-slate-50 border-slate-200' }: any) {
    return (
        <div className={`border rounded-2xl p-4 flex flex-col gap-2 ${color}`}>
            <Icon className="w-4 h-4 text-slate-500" />
            <div>
                <p className="text-xl font-black text-slate-800">{value}</p>
                <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">{label}</p>
            </div>
        </div>
    );
}

// ── Keywords Table ───────────────────────────────────────────────────────────
function KeywordsTable({ keywords, status, onExport }: { keywords: any[]; status?: string; onExport: () => void }) {
    if (keywords.length === 0) return null;
    return (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-3 duration-400">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 className="font-bold text-slate-800 text-sm">{keywords.length} Keywords</h3>
                <Button
                    onClick={onExport}
                    className="h-8 gap-1.5 px-3 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold"
                >
                    <Download className="w-3.5 h-3.5" /> Export CSV
                </Button>
            </div>

            {/* Table header */}
            <div className="hidden md:grid grid-cols-3 gap-2 px-5 py-2 bg-slate-50 border-b border-slate-100">
                <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Title / Keyword</p>
                <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 col-span-2">Subtitles (Related Keywords)</p>
            </div>

            {/* Rows */}
            <div className="divide-y divide-slate-100 max-h-[55vh] overflow-y-auto">
                {keywords.map((kw, i) => (
                    <KeywordRow key={kw.id ?? i} kw={kw} idx={i} />
                ))}
            </div>

            {/* Footer */}
            <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <p className="text-[10px] text-slate-400 font-medium">
                    {status === 'running' ? '● Live — more keywords incoming...' : 'Discovery complete'}
                </p>
                <Button
                    variant="ghost"
                    onClick={onExport}
                    className="h-auto p-0 text-[10px] font-bold text-violet-600 hover:text-violet-700 hover:bg-transparent"
                >
                    Download all as CSV →
                </Button>
            </div>
        </div>
    );
}

// ── CSV Export ────────────────────────────────────────────────────────────────
function exportCSV(keywords: any[], prefix = 'article-maker-keywords') {
    const header = 'Title,Subtitles,Niche,Published';
    const rows = keywords.map(k => [
        k.title ?? '', k.subtitle ?? '', k.niche ?? '', k.published_id > 0 ? 'Yes' : 'No',
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    const csv = [header, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${prefix}-${Date.now()}.csv`;
    a.click();
}

// ══════════════════════════════════════════════════════════════════════════════
// ── Main Component ───────────────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════════════════
export default function ArticleMakerRunner({ tool }: any) {
    // ── Discover form ──
    const [niche, setNiche]       = useState('');
    const [source, setSource]     = useState('google');
    const [audience, setAudience] = useState('en-us');
    const [limit, setLimit]       = useState(200);

    // ── Discover state ──
    const [discoverStatus, setDiscoverStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [keywords, setKeywords]     = useState<any[]>([]);
    const [progress, setProgress]     = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [errorMsg, setError]        = useState('');
    const campaignIdRef = useRef<string>('');

    // ── Subtitle state ──
    const [subtitleStatus, setSubtitleStatus] = useState<'idle' | 'running' | 'done'>('idle');
    const [subtitleMsg, setSubtitleMsg]       = useState('');

    // ── WordPress form ──
    const [wpUrl, setWpUrl]           = useState('');
    const [wpUser, setWpUser]         = useState('');
    const [wpPass, setWpPass]         = useState('');
    const [wpHtml, setWpHtml]         = useState('');
    const [postDelay, setPostDelay]   = useState(10);
    const [wpTestResult, setWpTestResult] = useState<{ success: boolean; message: string } | null>(null);

    // ── Publish state ──
    const [publishStatus, setPublishStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [publishMsg, setPublishMsg]       = useState('');
    const [publishProgress, setPublishProgress] = useState({ published: 0, total: 0 });

    // ── Campaigns tab ──
    const [campaigns, setCampaigns]           = useState<any[]>([]);
    const [selectedCampaign, setSelectedCampaign] = useState<any>(null);
    const [campaignKeywords, setCampaignKeywords] = useState<any[]>([]);
    const [loadingCampaigns, setLoadingCampaigns] = useState(false);
    const [loadingDetail, setLoadingDetail]       = useState(false);

    // ── Active tab ──
    const [activeTab, setActiveTab] = useState('discover');

    const wsRef = useRef<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);

    // ── WebSocket ──────────────────────────────────────────────────────────
    useEffect(() => {
        let ws: WebSocket;
        let retry: ReturnType<typeof setTimeout>;
        let retryDelay = 1000;

        const connect = () => {
            ws = new WebSocket(getWsUrl());
            wsRef.current = ws;

            ws.onopen = () => {
                setConnected(true);
                retryDelay = 1000; // Reset backoff on success
            };
            ws.onclose = () => {
                setConnected(false);
                retry = setTimeout(connect, retryDelay);
                retryDelay = Math.min(retryDelay * 1.5, 10000); // Exponential backoff, max 10s
            };
            ws.onerror = () => ws.close();

            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);

                    // ── Real-time keyword discovered ──
                    if (msg.event === 'article-maker.keyword.discovered') {
                        const kw = msg.data?.keyword;
                        const cid = msg.data?.campaignId;
                        if (kw && cid === campaignIdRef.current) {
                            setKeywords(prev => [...prev, kw]);
                        }
                    }

                    // ── Discovery progress ──
                    if (msg.event === 'article-maker.discover.progress' && msg.data?.campaignId === campaignIdRef.current) {
                        const d = msg.data;
                        if (d.status === 'starting') {
                            setProgressMsg(d.message || 'Starting...');
                        }
                        if (d.status === 'searching') {
                            const pct = d.totalQueues ? Math.min(5 + (d.queue / d.totalQueues) * 90, 95) : 50;
                            setProgress(pct);
                            setProgressMsg(d.message || 'Searching...');
                        }
                        if (d.status === 'completed') {
                            setDiscoverStatus('done');
                            setProgress(100);
                            setProgressMsg(d.message || `Done — ${d.extracted || 0} keywords.`);
                        }
                        if (d.status === 'stopped') {
                            setDiscoverStatus('done');
                            setProgress(100);
                            setProgressMsg(d.message || 'Stopped.');
                        }
                    }

                    // ── Subtitle progress ──
                    if (msg.event === 'article-maker.subtitles.progress' && msg.data?.campaignId === campaignIdRef.current) {
                        const d = msg.data;
                        if (d.status === 'processing' && d.keyword) {
                            setSubtitleMsg(`${d.processed}/${d.total}: "${d.keyword.title}"`);
                            // Update the keyword in our local state
                            setKeywords(prev => prev.map(k =>
                                k.id === d.keyword.id ? { ...k, subtitle: d.keyword.subtitle } : k
                            ));
                        }
                        if (d.status === 'completed' || d.status === 'stopped') {
                            setSubtitleStatus('done');
                            setSubtitleMsg(d.message || 'Done.');
                        }
                    }

                    // ── Publish progress ──
                    if (msg.event === 'article-maker.publish.progress' && msg.data?.campaignId === campaignIdRef.current) {
                        const d = msg.data;
                        setPublishMsg(d.message || '');
                        if (d.published != null) {
                            setPublishProgress({ published: d.published, total: d.total || 0 });
                        }
                        if (d.status === 'published' && d.postId) {
                            setKeywords(prev => prev.map(k =>
                                k.title === d.currentTitle ? { ...k, published_id: d.postId } : k
                            ));
                        }
                        if (d.status === 'completed' || d.status === 'stopped') {
                            setPublishStatus('done');
                        }
                        if (d.status === 'auth_failed') {
                            setPublishStatus('error');
                        }
                    }

                    // ── RPC responses ──
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const id = msg.requestId;
                        const resolver = (ws as any)._pending?.get(id);
                        if (resolver) {
                            msg.type === 'plugin_rpc_error'
                                ? resolver.reject(new Error(msg.payload?.error))
                                : resolver.resolve(msg.payload);
                            (ws as any)._pending?.delete(id);
                        }
                    }
                } catch {}
            };
        };

        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    }, []);

    // ── RPC helper ──
    const callRPC = useCallback((action: string, data: any = {}): Promise<any> => {
        return new Promise((resolve, reject) => {
            const ws = wsRef.current;
            if (!ws || ws.readyState !== WebSocket.OPEN) return reject(new Error('Not connected'));
            if (!(ws as any)._pending) (ws as any)._pending = new Map();
            const requestId = Math.random().toString(36).slice(2);
            (ws as any)._pending.set(requestId, { resolve, reject });
            ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: 'article-maker', action, data } }));
            setTimeout(() => {
                if ((ws as any)._pending?.has(requestId)) {
                    (ws as any)._pending.get(requestId).reject(new Error('Timeout'));
                    (ws as any)._pending.delete(requestId);
                }
            }, 30000);
        });
    }, []);

    // ── Start Discovery ──
    const handleStartDiscover = async () => {
        if (!niche.trim()) return;
        const cId = `am_${Date.now()}`;
        campaignIdRef.current = cId;

        setDiscoverStatus('running');
        setKeywords([]);
        setProgress(5);
        setProgressMsg('Starting keyword discovery...');
        setError('');

        try {
            await callRPC('article-maker.keywords.discover', {
                niche: niche.trim(), source, audience, limit, campaignId: cId,
            });
        } catch (err: any) {
            setError(err.message);
            setDiscoverStatus('error');
        }
    };

    // ── Stop Discovery ──
    const handleStopDiscover = async () => {
        const cId = campaignIdRef.current;
        if (cId) {
            try { await callRPC('article-maker.keywords.stop', { campaignId: cId }); } catch {}
        }
        setDiscoverStatus('done');
        setProgressMsg(`Stopped — ${keywords.length} keywords captured.`);
        setProgress(100);
    };

    // ── Fetch Subtitles ──
    const handleFetchSubtitles = async () => {
        const cId = campaignIdRef.current;
        if (!cId) return;
        setSubtitleStatus('running');
        setSubtitleMsg('Starting subtitle fetch...');
        try {
            await callRPC('article-maker.subtitles.fetch', { campaignId: cId, source, audience });
        } catch (err: any) {
            setSubtitleStatus('done');
            setSubtitleMsg(`Error: ${err.message}`);
        }
    };

    // ── WordPress Test ──
    const handleWpTest = async () => {
        setWpTestResult(null);
        try {
            const res = await callRPC('article-maker.wordpress.test', {
                baseUrl: wpUrl, username: wpUser, password: wpPass,
            });
            setWpTestResult(res);
        } catch (err: any) {
            setWpTestResult({ success: false, message: err.message });
        }
    };

    // ── WordPress Publish ──
    const handleStartPublish = async () => {
        const cId = campaignIdRef.current;
        if (!cId || !wpUrl || !wpUser || !wpPass) return;
        setPublishStatus('running');
        setPublishMsg('Starting publish...');
        setPublishProgress({ published: 0, total: 0 });
        try {
            await callRPC('article-maker.wordpress.publish', {
                campaignId: cId, baseUrl: wpUrl, username: wpUser, password: wpPass,
                htmlTemplate: wpHtml, postDelay,
            });
        } catch (err: any) {
            setPublishStatus('error');
            setPublishMsg(`Error: ${err.message}`);
        }
    };

    // ── Load campaigns ──
    const loadCampaigns = useCallback(async () => {
        if (!connected) return;
        setLoadingCampaigns(true);
        try {
            const res = await callRPC('article-maker.campaigns.list');
            if (res?.campaigns) setCampaigns(res.campaigns);
        } catch {}
        setLoadingCampaigns(false);
    }, [connected, callRPC]);

    // ── Load campaign detail ──
    const openCampaign = async (campaign: any) => {
        setSelectedCampaign(campaign);
        setLoadingDetail(true);
        setCampaignKeywords([]);
        try {
            const res = await callRPC('article-maker.campaign.detail', { campaignId: campaign.id });
            if (res?.keywords) setCampaignKeywords(res.keywords);
        } catch {}
        setLoadingDetail(false);
    };

    useEffect(() => {
        if (activeTab === 'campaigns' && connected) loadCampaigns();
    }, [activeTab, connected, loadCampaigns]);

    // ── Status Badge helper ──
    const statusBadge = (s: string) => {
        const map: Record<string, { color: string; label: string }> = {
            running:   { color: 'bg-violet-50 border-violet-200 text-violet-700', label: 'Running' },
            completed: { color: 'bg-emerald-50 border-emerald-200 text-emerald-700', label: 'Completed' },
            stopped:   { color: 'bg-slate-100 border-slate-200 text-slate-600', label: 'Stopped' },
            failed:    { color: 'bg-rose-50 border-rose-200 text-rose-700', label: 'Failed' },
        };
        const cfg = map[s] || map.completed;
        return (
            <Badge variant="outline" className={`text-[10px] font-bold uppercase px-2 py-0.5 ${cfg.color}`}>
                {cfg.label}
            </Badge>
        );
    };

    // ── Connection loading ──
    if (!connected) return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center font-sans">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-violet-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">Connecting to Runtime...</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* ── Top bar ── */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-sm">
                        <FileText className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">AI Article Writer</span>
                </div>
                <div className="flex items-center gap-3">
                    {keywords.length > 0 && discoverStatus !== 'idle' && (
                        <Button
                            variant="outline"
                            onClick={() => exportCSV(keywords)}
                            className="h-8 gap-1.5 px-3 bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 text-xs font-bold"
                        >
                            <Download className="w-3.5 h-3.5" /> Export CSV
                        </Button>
                    )}
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${discoverStatus === 'running' ? 'bg-violet-50 border-violet-200 text-violet-700' : discoverStatus === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${discoverStatus === 'running' ? 'bg-violet-500 animate-pulse' : discoverStatus === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {discoverStatus === 'running' ? 'Discovering...' : discoverStatus === 'done' ? `${keywords.length} keywords` : 'Ready'}
                    </Badge>
                </div>
            </div>

            {/* ── Content ── */}
            <div className="max-w-5xl mx-auto px-4 py-6">
                <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full max-w-lg grid-cols-3 mb-6 bg-slate-100 p-1 rounded-xl">
                        <TabsTrigger value="discover" className="gap-2 text-sm font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg">
                            <Zap className="w-4 h-4" /> Discover
                            {discoverStatus === 'running' && <span className="ml-1 w-2 h-2 rounded-full bg-violet-500 animate-pulse" />}
                        </TabsTrigger>
                        <TabsTrigger value="wordpress" className="gap-2 text-sm font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg">
                            <Send className="w-4 h-4" /> WordPress
                        </TabsTrigger>
                        <TabsTrigger value="campaigns" className="gap-2 text-sm font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg">
                            <History className="w-4 h-4" /> Campaigns
                            {campaigns.length > 0 && (
                                <span className="ml-1 text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0 rounded-full font-bold">
                                    {campaigns.length}
                                </span>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    {/* ══════════════════════════════════════════════════════════ */}
                    {/* ── TAB 1: Discover ──────────────────────────────────── */}
                    {/* ══════════════════════════════════════════════════════════ */}
                    <TabsContent value="discover" className="space-y-6">
                        {/* Config card */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                            <div className="mb-5">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900">Discover Long-Tail Keywords</h1>
                                <p className="text-sm text-slate-400 mt-1">Expand any niche into hundreds of unique keyword ideas using autocomplete suggestions.</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                {/* Niche */}
                                <div className="md:col-span-1">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Niche / Seed Keyword</label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="text"
                                            value={niche}
                                            onChange={e => setNiche(e.target.value)}
                                            onKeyDown={e => e.key === 'Enter' && handleStartDiscover()}
                                            placeholder="weight loss, crypto, SEO..."
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>

                                {/* Source */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Source</label>
                                    <div className="relative">
                                        <Globe className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <select
                                            value={source}
                                            onChange={e => setSource(e.target.value)}
                                            className="w-full pl-9 pr-8 h-11 text-sm border border-slate-200 focus:border-violet-400 rounded-md outline-none transition-all bg-slate-50 appearance-none"
                                        >
                                            {SOURCES.map(s => (
                                                <option key={s.value} value={s.value}>{s.icon} {s.label}</option>
                                            ))}
                                        </select>
                                        <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>

                                {/* Audience */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Region / Language</label>
                                    <div className="relative">
                                        <Globe className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <select
                                            value={audience}
                                            onChange={e => setAudience(e.target.value)}
                                            className="w-full pl-9 pr-8 h-11 text-sm border border-slate-200 focus:border-violet-400 rounded-md outline-none transition-all bg-slate-50 appearance-none"
                                        >
                                            {AUDIENCES.map(a => (
                                                <option key={a.value} value={a.value}>{a.label}</option>
                                            ))}
                                        </select>
                                        <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>

                                {/* Limit */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Max Keywords</label>
                                    <div className="relative">
                                        <Hash className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="number"
                                            min={10} max={2000} step={10}
                                            value={limit}
                                            onChange={e => setLimit(parseInt(e.target.value, 10))}
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Start / Stop */}
                            <div className="flex flex-wrap gap-3">
                                {discoverStatus === 'running' ? (
                                    <Button
                                        variant="outline"
                                        onClick={handleStopDiscover}
                                        className="h-11 gap-2 px-6 bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100 hover:text-rose-800 text-sm font-bold"
                                    >
                                        <Square className="w-4 h-4" /> Stop
                                    </Button>
                                ) : (
                                    <Button
                                        onClick={handleStartDiscover}
                                        disabled={!niche.trim()}
                                        className="h-11 gap-2 px-6 bg-gradient-to-r from-violet-500 to-indigo-600 text-white shadow-md text-sm font-bold hover:opacity-90"
                                    >
                                        <Play className="w-4 h-4" /> Start Discovery
                                    </Button>
                                )}

                                {/* Fetch Subtitles button */}
                                {keywords.length > 0 && discoverStatus !== 'running' && (
                                    <Button
                                        variant="outline"
                                        onClick={handleFetchSubtitles}
                                        disabled={subtitleStatus === 'running'}
                                        className="h-11 gap-2 px-4 border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 text-sm font-bold"
                                    >
                                        {subtitleStatus === 'running' ? (
                                            <RefreshCw className="w-4 h-4 animate-spin" />
                                        ) : (
                                            <BookOpen className="w-4 h-4" />
                                        )}
                                        Fetch Subtitles
                                    </Button>
                                )}

                                {/* Clear */}
                                {keywords.length > 0 && discoverStatus !== 'running' && (
                                    <Button
                                        variant="outline"
                                        onClick={async () => {
                                            await callRPC('article-maker.keywords.clear', { campaignId: campaignIdRef.current });
                                            setKeywords([]);
                                            setDiscoverStatus('idle');
                                            setProgress(0);
                                            setSubtitleStatus('idle');
                                            setSubtitleMsg('');
                                        }}
                                        className="h-11 px-4 border-slate-200 text-slate-500 hover:bg-slate-50 text-sm font-medium"
                                    >
                                        Clear
                                    </Button>
                                )}
                            </div>

                            {/* Progress */}
                            {discoverStatus === 'running' && (
                                <div className="mt-4 space-y-1.5 animate-in fade-in duration-300">
                                    <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                        <span className="flex items-center gap-1.5"><RefreshCw className="w-3 h-3 animate-spin" />{progressMsg}</span>
                                        <span className="font-bold text-violet-600">{keywords.length} keywords so far</span>
                                    </div>
                                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-violet-500 to-indigo-500 transition-all duration-500 rounded-full"
                                            style={{ width: `${progress}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Subtitle progress */}
                            {subtitleStatus === 'running' && (
                                <div className="mt-3 flex items-center gap-2 text-[11px] text-amber-600 font-semibold animate-in fade-in duration-300">
                                    <RefreshCw className="w-3 h-3 animate-spin" />
                                    <span>{subtitleMsg}</span>
                                </div>
                            )}

                            {/* Error */}
                            {discoverStatus === 'error' && (
                                <div className="mt-4 flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-4 animate-in fade-in duration-300">
                                    <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    <p className="text-sm text-rose-700 font-medium">{errorMsg}</p>
                                </div>
                            )}
                        </div>

                        {/* Stats */}
                        {keywords.length > 0 && (
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 animate-in fade-in duration-300">
                                <StatCard label="Total Keywords" value={keywords.length} icon={Lightbulb} color="bg-violet-50 border-violet-200" />
                                <StatCard label="With Subtitles" value={keywords.filter(k => k.subtitle).length} icon={BookOpen} color="bg-amber-50 border-amber-200" />
                                <StatCard label="Published" value={keywords.filter(k => k.published_id > 0).length} icon={Send} color="bg-emerald-50 border-emerald-200" />
                                <StatCard label="Source" value={SOURCES.find(s => s.value === source)?.label || source} icon={Globe} />
                            </div>
                        )}

                        {/* Keywords table */}
                        <KeywordsTable keywords={keywords} status={discoverStatus} onExport={() => exportCSV(keywords)} />

                        {/* Empty state */}
                        {discoverStatus === 'idle' && keywords.length === 0 && (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <Sparkles className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                <h3 className="text-sm font-bold text-slate-700">Discover thousands of keyword ideas</h3>
                                <p className="text-xs text-slate-400 mt-2 max-w-xs mx-auto">
                                    Enter a niche keyword and let AI Article Writer expand it into hundreds of unique long-tail keywords using Google & YouTube autocomplete.
                                </p>
                            </div>
                        )}
                    </TabsContent>

                    {/* ══════════════════════════════════════════════════════════ */}
                    {/* ── TAB 2: WordPress ─────────────────────────────────── */}
                    {/* ══════════════════════════════════════════════════════════ */}
                    <TabsContent value="wordpress" className="space-y-6">
                        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                            <div className="mb-5">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900">WordPress Publisher</h1>
                                <p className="text-sm text-slate-400 mt-1">Connect your WordPress site and bulk-publish articles from discovered keywords.</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                {/* WordPress URL */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">WordPress URL</label>
                                    <div className="relative">
                                        <Link2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="text"
                                            value={wpUrl}
                                            onChange={e => setWpUrl(e.target.value)}
                                            placeholder="https://your-site.com"
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>

                                {/* Username */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Username</label>
                                    <div className="relative">
                                        <Type className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="text"
                                            value={wpUser}
                                            onChange={e => setWpUser(e.target.value)}
                                            placeholder="admin"
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>

                                {/* Password */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Password</label>
                                    <div className="relative">
                                        <Key className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="password"
                                            value={wpPass}
                                            onChange={e => setWpPass(e.target.value)}
                                            placeholder="••••••••"
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                {/* HTML Template */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">HTML Template (appended to each post)</label>
                                    <textarea
                                        value={wpHtml}
                                        onChange={e => setWpHtml(e.target.value)}
                                        placeholder='<p>Read more about this topic on our site...</p>'
                                        rows={3}
                                        className="w-full p-3 text-sm border border-slate-200 rounded-md outline-none focus:border-violet-400 bg-slate-50 resize-y font-mono"
                                    />
                                </div>

                                {/* Post Delay */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Delay Between Posts (seconds)</label>
                                    <div className="relative">
                                        <Clock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="number"
                                            min={1} max={120} step={1}
                                            value={postDelay}
                                            onChange={e => setPostDelay(parseInt(e.target.value, 10))}
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                    <p className="text-[10px] text-slate-400 mt-1">Higher delay = safer from rate limits</p>
                                </div>
                            </div>

                            {/* Buttons */}
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    variant="outline"
                                    onClick={handleWpTest}
                                    disabled={!wpUrl || !wpUser || !wpPass}
                                    className="h-11 gap-2 px-5 border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-bold"
                                >
                                    <Link2 className="w-4 h-4" /> Test Connection
                                </Button>

                                {publishStatus === 'running' ? (
                                    <Button
                                        variant="outline"
                                        onClick={async () => { await callRPC('article-maker.wordpress.stop'); setPublishStatus('done'); }}
                                        className="h-11 gap-2 px-6 bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100 text-sm font-bold"
                                    >
                                        <Square className="w-4 h-4" /> Stop Publishing
                                    </Button>
                                ) : (
                                    <Button
                                        onClick={handleStartPublish}
                                        disabled={!wpUrl || !wpUser || !wpPass || keywords.length === 0}
                                        className="h-11 gap-2 px-6 bg-gradient-to-r from-violet-500 to-indigo-600 text-white shadow-md text-sm font-bold hover:opacity-90"
                                    >
                                        <Send className="w-4 h-4" /> Publish All ({keywords.filter(k => !k.published_id || k.published_id === 0).length} articles)
                                    </Button>
                                )}
                            </div>

                            {/* WP Test Result */}
                            {wpTestResult && (
                                <div className={`mt-4 flex items-start gap-2.5 rounded-xl p-4 animate-in fade-in duration-300 ${wpTestResult.success ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200'}`}>
                                    {wpTestResult.success ? (
                                        <CheckCircle2 className="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" />
                                    ) : (
                                        <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    )}
                                    <p className={`text-sm font-medium ${wpTestResult.success ? 'text-emerald-700' : 'text-rose-700'}`}>
                                        {wpTestResult.message}
                                    </p>
                                </div>
                            )}

                            {/* Publish Progress */}
                            {publishStatus === 'running' && (
                                <div className="mt-4 space-y-2 animate-in fade-in duration-300">
                                    <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                        <span className="flex items-center gap-1.5"><RefreshCw className="w-3 h-3 animate-spin" />{publishMsg}</span>
                                        <span className="font-bold text-violet-600">{publishProgress.published}/{publishProgress.total}</span>
                                    </div>
                                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500 rounded-full"
                                            style={{ width: `${publishProgress.total > 0 ? (publishProgress.published / publishProgress.total) * 100 : 0}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {publishStatus === 'done' && publishMsg && (
                                <div className="mt-4 flex items-center gap-2 text-[11px] text-emerald-600 font-semibold">
                                    <CheckCircle2 className="w-3.5 h-3.5" /> {publishMsg}
                                </div>
                            )}
                        </div>

                        {/* No keywords warning */}
                        {keywords.length === 0 && (
                            <div className="py-16 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <FileText className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                <h3 className="text-sm font-bold text-slate-700">No keywords to publish</h3>
                                <p className="text-xs text-slate-400 mt-2 max-w-xs mx-auto">
                                    Go to the Discover tab first to generate keywords, then come back here to publish them to WordPress.
                                </p>
                            </div>
                        )}
                    </TabsContent>

                    {/* ══════════════════════════════════════════════════════════ */}
                    {/* ── TAB 3: Campaigns ─────────────────────────────────── */}
                    {/* ══════════════════════════════════════════════════════════ */}
                    <TabsContent value="campaigns" className="space-y-4">
                        {selectedCampaign ? (
                            <div className="space-y-4 animate-in fade-in slide-in-from-right-3 duration-300">
                                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                                    <button
                                        onClick={() => { setSelectedCampaign(null); setCampaignKeywords([]); }}
                                        className="flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors mb-4"
                                    >
                                        <ArrowLeft className="w-3.5 h-3.5" /> Back to campaigns
                                    </button>

                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                                <Search className="w-4 h-4 text-violet-500" />
                                                "{selectedCampaign.niche}"
                                            </h2>
                                            <div className="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1">
                                                    <Globe className="w-3 h-3" />
                                                    {selectedCampaign.source} / {selectedCampaign.audience}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="w-3 h-3" />
                                                    {new Date(selectedCampaign.created_at).toLocaleDateString()}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Hash className="w-3 h-3" />
                                                    {selectedCampaign.total} keywords
                                                </span>
                                            </div>
                                        </div>
                                        {statusBadge(selectedCampaign.status)}
                                    </div>

                                    {campaignKeywords.length > 0 && (
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                                            <StatCard label="Total" value={campaignKeywords.length} icon={Lightbulb} color="bg-violet-50 border-violet-200" />
                                            <StatCard label="With Subtitles" value={campaignKeywords.filter(k => k.subtitle).length} icon={BookOpen} color="bg-amber-50 border-amber-200" />
                                            <StatCard label="Published" value={campaignKeywords.filter(k => k.published_id > 0).length} icon={Send} color="bg-emerald-50 border-emerald-200" />
                                            <StatCard label="Source" value={selectedCampaign.source} icon={Globe} />
                                        </div>
                                    )}
                                </div>

                                {loadingDetail ? (
                                    <div className="py-16 text-center">
                                        <div className="w-6 h-6 border-2 border-violet-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">Loading keywords...</p>
                                    </div>
                                ) : (
                                    <KeywordsTable
                                        keywords={campaignKeywords}
                                        onExport={() => exportCSV(campaignKeywords, `campaign-${selectedCampaign.niche}`)}
                                    />
                                )}

                                {!loadingDetail && campaignKeywords.length === 0 && (
                                    <div className="py-16 text-center bg-white border border-dashed border-slate-200 rounded-2xl">
                                        <Lightbulb className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                        <p className="text-sm text-slate-500 font-medium">No keywords in this campaign</p>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-4 animate-in fade-in duration-300">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-bold text-slate-800">Past Campaigns</h2>
                                    <Button
                                        variant="outline"
                                        onClick={loadCampaigns}
                                        disabled={loadingCampaigns}
                                        className="h-8 gap-1.5 text-xs font-bold"
                                    >
                                        <RefreshCw className={`w-3.5 h-3.5 ${loadingCampaigns ? 'animate-spin' : ''}`} /> Refresh
                                    </Button>
                                </div>

                                {loadingCampaigns && campaigns.length === 0 ? (
                                    <div className="py-16 text-center">
                                        <div className="w-6 h-6 border-2 border-violet-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">Loading campaigns...</p>
                                    </div>
                                ) : campaigns.length === 0 ? (
                                    <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                        <History className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                        <h3 className="text-sm font-bold text-slate-700">No campaigns yet</h3>
                                        <p className="text-xs text-slate-400 mt-2">Start a keyword discovery from the Discover tab to see campaigns here.</p>
                                    </div>
                                ) : (
                                    <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                                        {campaigns.map((c, i) => (
                                            <button
                                                key={c.id}
                                                onClick={() => openCampaign(c)}
                                                className={`w-full flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors text-left ${i < campaigns.length - 1 ? 'border-b border-slate-100' : ''}`}
                                            >
                                                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-100 to-indigo-100 flex items-center justify-center shrink-0">
                                                    <Search className="w-4 h-4 text-violet-600" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <p className="text-sm font-bold text-slate-800 truncate">"{c.niche}"</p>
                                                        {statusBadge(c.status)}
                                                    </div>
                                                    <div className="flex items-center gap-4 mt-1 text-[11px] text-slate-400">
                                                        <span className="flex items-center gap-1">
                                                            <Globe className="w-3 h-3" />
                                                            {c.source}
                                                        </span>
                                                        <span className="flex items-center gap-1">
                                                            <Lightbulb className="w-3 h-3" />
                                                            {c.total} keywords
                                                        </span>
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="w-3 h-3" />
                                                            {new Date(c.created_at).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                </div>
                                                <ExternalLink className="w-4 h-4 text-slate-300 shrink-0" />
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    );
}
