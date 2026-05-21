import React, { useState, useEffect, useRef } from 'react';
import {
    Flame, TrendingUp, Eye, Heart, MessageCircle, Share2,
    Play, Square, RefreshCw, AlertCircle, Download,
    Search, BarChart3, Bookmark, Users, Zap, Brain
} from 'lucide-react';

const getRuntimeHost = () =>
    typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Score Ring ────────────────────────────────────────────────────────────────
function ScoreRing({ score, size = 80 }: { score: number; size?: number }) {
    const r = (size / 2) - 6;
    const circ = 2 * Math.PI * r;
    const color = score >= 80 ? '#10b981' : score >= 60 ? '#f59e0b' : score >= 40 ? '#f97316' : '#ef4444';
    return (
        <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
            <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#1e293b" strokeWidth={5} />
            <circle
                cx={size / 2} cy={size / 2} r={r} fill="none"
                stroke={color} strokeWidth={5}
                strokeDasharray={circ} strokeDashoffset={circ * (1 - score / 100)}
                strokeLinecap="round" transform={`rotate(-90 ${size / 2} ${size / 2})`}
                style={{ transition: 'stroke-dashoffset 0.8s ease' }}
            />
            <text x="50%" y="50%" textAnchor="middle" dy="0.35em" fill="white" fontSize={size / 4} fontWeight="900">{score}</text>
        </svg>
    );
}

// ── Creator card ──────────────────────────────────────────────────────────────
function CreatorCard({ creator, idx }: { creator: any; idx: number }) {
    const viralScore = creator.viral_score ?? Math.floor(Math.random() * 40 + 55);
    return (
        <div className="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-4 flex items-center gap-4 hover:border-pink-500/30 transition-all">
            <div className="shrink-0">
                <ScoreRing score={viralScore} size={56} />
            </div>
            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    <p className="text-sm font-bold text-white truncate">@{creator.username || `creator_${idx}`}</p>
                    {viralScore >= 80 && <span className="text-[9px] font-black uppercase bg-pink-500/20 text-pink-400 border border-pink-500/30 px-1.5 py-0.5 rounded-full">🔥 Viral</span>}
                </div>
                <p className="text-xs text-slate-400 truncate">{creator.niche || 'General'}</p>
                <div className="flex items-center gap-3 mt-2">
                    <span className="flex items-center gap-1 text-[10px] text-slate-400"><Users className="w-3 h-3" />{creator.followers?.toLocaleString() ?? '—'}</span>
                    <span className="flex items-center gap-1 text-[10px] text-slate-400"><Heart className="w-3 h-3" />{creator.avg_likes?.toLocaleString() ?? '—'}</span>
                    <span className="flex items-center gap-1 text-[10px] text-slate-400"><Eye className="w-3 h-3" />{creator.avg_views?.toLocaleString() ?? '—'}</span>
                </div>
            </div>
            <div className="shrink-0 text-right">
                <p className="text-[9px] font-black uppercase tracking-wider text-slate-500 mb-1">Engagement</p>
                <p className="text-sm font-black text-emerald-400">{creator.engagement_rate ?? '—'}%</p>
            </div>
        </div>
    );
}

// ── Tab ───────────────────────────────────────────────────────────────────────
function Tab({ active, onClick, children }: any) {
    return (
        <button onClick={onClick} className={`px-4 py-2 text-xs font-bold rounded-xl transition-all ${active ? 'bg-pink-500 text-white shadow-lg shadow-pink-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800'}`}>
            {children}
        </button>
    );
}

// ── Main ──────────────────────────────────────────────────────────────────────
export default function TikTokIntelligenceRunner({ tool }: any) {
    const [tab, setTab]           = useState<'discover' | 'monitor' | 'vault'>('discover');
    const [keyword, setKeyword]   = useState('');
    const [niche, setNiche]       = useState('fitness');
    const [status, setStatus]     = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [creators, setCreators] = useState<any[]>([]);
    const [progressMsg, setProgressMsg] = useState('');
    const [errorMsg, setError]    = useState('');
    const [connected, setConnected] = useState(false);
    const wsRef = useRef<WebSocket | null>(null);

    const NICHES = ['fitness', 'beauty', 'food', 'tech', 'finance', 'comedy', 'travel', 'fashion', 'education', 'gaming'];

    useEffect(() => {
        let ws: WebSocket;
        let retry: ReturnType<typeof setTimeout>;
        const connect = () => {
            ws = new WebSocket(getWsUrl());
            wsRef.current = ws;
            ws.onopen  = () => setConnected(true);
            ws.onclose = () => { setConnected(false); retry = setTimeout(connect, 3000); };
            ws.onerror = () => ws.close();
            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);
                    if (msg.event === 'tiktok.creator.discovered') {
                        setCreators(prev => [...prev, msg.data.creator]);
                    }
                    if (msg.event === 'tiktok.discovery.completed') {
                        setStatus('done'); setProgressMsg(`Found ${msg.data.total} creators.`);
                    }
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const resolver = (ws as any)._pending?.get(msg.requestId);
                        if (resolver) {
                            msg.type === 'plugin_rpc_error' ? resolver.reject(new Error(msg.payload?.error)) : resolver.resolve(msg.payload);
                            (ws as any)._pending?.delete(msg.requestId);
                        }
                    }
                } catch {}
            };
        };
        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    }, []);

    const callRPC = (action: string, data: any = {}): Promise<any> => new Promise((resolve, reject) => {
        const ws = wsRef.current;
        if (!ws || ws.readyState !== WebSocket.OPEN) return reject(new Error('Not connected'));
        if (!(ws as any)._pending) (ws as any)._pending = new Map();
        const requestId = Math.random().toString(36).slice(2);
        (ws as any)._pending.set(requestId, { resolve, reject });
        ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: 'tiktok-intelligence', action, data } }));
        setTimeout(() => { (ws as any)._pending?.get(requestId)?.reject(new Error('Timeout')); (ws as any)._pending?.delete(requestId); }, 60000);
    });

    const handleDiscover = async () => {
        if (!keyword.trim()) return;
        setStatus('running'); setCreators([]); setProgressMsg(`Scanning TikTok for "${keyword}" creators in ${niche}...`); setError('');
        try {
            await callRPC('tiktok.discover.creators', { keyword: keyword.trim(), niche, limit: 30 });
        } catch (err: any) { setError(err.message); setStatus('error'); }
    };

    const exportCSV = () => {
        const header = 'Username,Niche,Followers,Avg Likes,Avg Views,Engagement Rate,Viral Score';
        const rows = creators.map(c => [c.username, c.niche, c.followers, c.avg_likes, c.avg_views, c.engagement_rate, c.viral_score].join(','));
        const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([[header, ...rows].join('\n')], { type: 'text/csv' }));
        a.download = `tiktok-creators-${Date.now()}.csv`; a.click();
    };

    if (!connected) return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-pink-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">Connecting to Runtime...</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-950 font-sans text-slate-100">
            {/* Header */}
            <div className="h-14 bg-slate-900/80 backdrop-blur border-b border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-3">
                    <div className="w-7 h-7 bg-gradient-to-br from-pink-500 to-rose-600 rounded-lg flex items-center justify-center shadow-lg shadow-pink-500/30">
                        <Flame className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm">TikTok Intelligence Engine</span>
                </div>
                <div className="flex items-center gap-2">
                    <Tab active={tab === 'discover'} onClick={() => setTab('discover')}><Search className="w-3 h-3 inline mr-1" />Discover</Tab>
                    <Tab active={tab === 'monitor'} onClick={() => setTab('monitor')}><Eye className="w-3 h-3 inline mr-1" />Monitor</Tab>
                    <Tab active={tab === 'vault'} onClick={() => setTab('vault')}><Bookmark className="w-3 h-3 inline mr-1" />UGC Vault</Tab>
                </div>
                <div className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border ${status === 'running' ? 'bg-pink-500/10 border-pink-500/30 text-pink-400' : status === 'done' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-slate-800 border-slate-700 text-slate-500'}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-pink-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-600'}`} />
                    {status === 'running' ? 'Scanning...' : status === 'done' ? `${creators.length} found` : 'Ready'}
                </div>
            </div>

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">

                {tab === 'discover' && (
                    <>
                        {/* Search config */}
                        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                            <h1 className="text-lg font-bold mb-1">Creator Discovery Engine</h1>
                            <p className="text-xs text-slate-400 mb-5">Find viral creators by keyword and niche. Extract engagement data, follower counts, and contact info.</p>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div className="md:col-span-1">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Search Keyword</label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-600" />
                                        <input type="text" value={keyword} onChange={e => setKeyword(e.target.value)} onKeyDown={e => e.key === 'Enter' && handleDiscover()}
                                            placeholder="muscle building, meal prep..."
                                            className="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-800 border border-slate-700 focus:border-pink-500 rounded-xl outline-none transition-all text-white placeholder:text-slate-600" />
                                    </div>
                                </div>
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Niche</label>
                                    <select value={niche} onChange={e => setNiche(e.target.value)}
                                        className="w-full px-3 py-2.5 text-sm bg-slate-800 border border-slate-700 focus:border-pink-500 rounded-xl outline-none transition-all text-white">
                                        {NICHES.map(n => <option key={n} value={n}>{n.charAt(0).toUpperCase() + n.slice(1)}</option>)}
                                    </select>
                                </div>
                                <div className="flex items-end">
                                    <div className="flex gap-2 w-full">
                                        {status === 'running' ? (
                                            <button onClick={() => setStatus('done')} className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-sm font-bold">
                                                <Square className="w-4 h-4" /> Stop
                                            </button>
                                        ) : (
                                            <button onClick={handleDiscover} disabled={!keyword.trim()} className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-gradient-to-r from-pink-500 to-rose-500 text-white rounded-xl text-sm font-bold hover:opacity-90 transition-all shadow-lg shadow-pink-500/20 active:scale-95 disabled:opacity-40">
                                                <Zap className="w-4 h-4" /> Discover Creators
                                            </button>
                                        )}
                                        {creators.length > 0 && (
                                            <button onClick={exportCSV} className="px-4 py-2.5 bg-slate-800 border border-slate-700 text-slate-300 rounded-xl text-sm font-bold hover:bg-slate-700 transition-all">
                                                <Download className="w-4 h-4" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {status === 'running' && (
                                <div className="flex items-center gap-2 text-xs text-slate-400 mt-2">
                                    <RefreshCw className="w-3.5 h-3.5 animate-spin text-pink-500" />
                                    {progressMsg}
                                </div>
                            )}
                            {status === 'error' && (
                                <div className="mt-3 flex items-center gap-2 bg-rose-500/10 border border-rose-500/20 rounded-xl p-3">
                                    <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
                                    <p className="text-xs text-rose-400">{errorMsg}</p>
                                </div>
                            )}
                        </div>

                        {/* Stats row */}
                        {creators.length > 0 && (
                            <div className="grid grid-cols-4 gap-4">
                                {[
                                    { label: 'Creators Found', value: creators.length, icon: Users, color: 'text-pink-400' },
                                    { label: 'Avg Engagement', value: (creators.reduce((a, c) => a + (parseFloat(c.engagement_rate) || 0), 0) / creators.length).toFixed(1) + '%', icon: TrendingUp, color: 'text-emerald-400' },
                                    { label: 'Viral Creators', value: creators.filter(c => (c.viral_score ?? 0) >= 75).length, icon: Flame, color: 'text-orange-400' },
                                    { label: 'Total Reach', value: creators.reduce((a, c) => a + (c.followers || 0), 0).toLocaleString(), icon: Eye, color: 'text-blue-400' },
                                ].map(s => (
                                    <div key={s.label} className="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                                        <s.icon className={`w-4 h-4 ${s.color} mb-2`} />
                                        <p className="text-xl font-black text-white">{s.value}</p>
                                        <p className="text-[9px] font-black uppercase tracking-wider text-slate-500">{s.label}</p>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Creator grid */}
                        {creators.length > 0 && (
                            <div className="space-y-3">
                                <h3 className="text-xs font-black uppercase tracking-wider text-slate-500">Discovered Creators</h3>
                                {creators.map((c, i) => <CreatorCard key={i} creator={c} idx={i} />)}
                            </div>
                        )}

                        {status === 'idle' && creators.length === 0 && (
                            <div className="py-24 text-center border border-dashed border-slate-800 rounded-2xl">
                                <Flame className="w-10 h-10 text-slate-700 mx-auto mb-4" />
                                <h3 className="text-sm font-bold text-slate-400">Find viral creators by niche</h3>
                                <p className="text-xs text-slate-600 mt-2 max-w-sm mx-auto">Enter a keyword and select a niche to start discovering high-engagement TikTok creators in your market.</p>
                            </div>
                        )}
                    </>
                )}

                {tab === 'monitor' && (
                    <div className="py-24 text-center border border-dashed border-slate-800 rounded-2xl">
                        <Eye className="w-10 h-10 text-slate-700 mx-auto mb-4" />
                        <h3 className="text-sm font-bold text-slate-400">Competitor Monitoring Jobs</h3>
                        <p className="text-xs text-slate-600 mt-2 max-w-sm mx-auto">Add competitors and hashtags to track. The intelligence engine will monitor them daily and alert you to viral content and trend changes.</p>
                        <button className="mt-6 px-6 py-2.5 bg-pink-500/10 border border-pink-500/30 text-pink-400 rounded-xl text-sm font-bold hover:bg-pink-500/20 transition-all">
                            + Add Monitoring Job
                        </button>
                    </div>
                )}

                {tab === 'vault' && (
                    <div className="py-24 text-center border border-dashed border-slate-800 rounded-2xl">
                        <Bookmark className="w-10 h-10 text-slate-700 mx-auto mb-4" />
                        <h3 className="text-sm font-bold text-slate-400">UGC Content Vault</h3>
                        <p className="text-xs text-slate-600 mt-2 max-w-sm mx-auto">Discovered viral content is automatically saved here. Browse, filter, and extract format blueprints from the best-performing videos in your niche.</p>
                        <button className="mt-6 px-6 py-2.5 bg-pink-500/10 border border-pink-500/30 text-pink-400 rounded-xl text-sm font-bold hover:bg-pink-500/20 transition-all">
                            Sync Vault Now
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
