import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Search, MapPin, Users, Download, Play, Square,
    AlertCircle, CheckCircle2, RefreshCw, Globe,
    Phone, Mail, ChevronDown, Clipboard, History,
    Zap, ArrowLeft, Calendar, Hash, ExternalLink,
    MessageCircle,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Countries ────────────────────────────────────────────────────────────
const COUNTRIES = [
    { code: 'jo', name: 'Jordan' },
    { code: 'sa', name: 'Saudi Arabia' },
    { code: 'ae', name: 'UAE' },
    { code: 'kw', name: 'Kuwait' },
    { code: 'bh', name: 'Bahrain' },
    { code: 'om', name: 'Oman' },
    { code: 'qa', name: 'Qatar' },
    { code: 'eg', name: 'Egypt' },
    { code: 'iq', name: 'Iraq' },
    { code: 'lb', name: 'Lebanon' },
    { code: 'ma', name: 'Morocco' },
];

// ── Lead Card ────────────────────────────────────────────────────────────
function LeadCard({ lead, idx }: { lead: any; idx: number }) {
    const [copied, setCopied] = useState(false);

    const copyRow = () => {
        const text = [lead.name, lead.phone, lead.whatsapp, lead.email, lead.listing_title, lead.url].filter(Boolean).join('\t');
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    return (
        <div className="group flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/80 transition-colors border-b border-slate-100/80 last:border-0">
            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white text-[10px] font-black shrink-0 shadow-sm">
                {idx + 1}
            </div>
            <div className="flex-1 grid grid-cols-2 md:grid-cols-6 gap-3 min-w-0 items-center">
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Name</p>
                    <p className="text-xs font-semibold text-slate-800 truncate">{lead.name || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Phone</p>
                    <p className="text-xs font-mono text-slate-600 truncate">{lead.phone || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">WhatsApp</p>
                    <p className="text-xs font-mono text-emerald-600 truncate">{lead.whatsapp || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Email</p>
                    <p className="text-xs font-mono text-slate-600 truncate">{lead.email || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Details</p>
                    <p className="text-xs text-slate-600 truncate" title={lead.listing_title || ''}>{lead.listing_title || '—'}</p>
                    <p className="text-[10px] text-slate-400 truncate" title={lead.region || ''}>{lead.region || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Link</p>
                    {lead.url ? (
                        <a href={lead.url} target="_blank" rel="noreferrer" className="text-xs text-blue-500 hover:underline truncate block flex items-center gap-1">
                            <ExternalLink className="w-3 h-3 shrink-0" /> View
                        </a>
                    ) : (
                        <p className="text-xs text-slate-500">—</p>
                    )}
                </div>
            </div>
            <Button
                variant="ghost" size="icon"
                onClick={copyRow}
                className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 hover:bg-slate-100"
                title={__('general.copy_row')}
            >
                {copied ? <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> : <Clipboard className="w-3.5 h-3.5 text-slate-400" />}
            </Button>
        </div>
    );
}

// ── Stat card ─────────────────────────────────────────────────────────────
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

// ── Leads Table ──────────────────────────────────────────────────────────
function LeadsTable({ leads, status, onExport }: { leads: any[]; status?: string; onExport: () => void }) {
    if (leads.length === 0) return null;
    return (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-3 duration-400">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 className="font-bold text-slate-800 text-sm">{leads.length} Leads</h3>
                <Button
                    onClick={onExport}
                    className="h-8 gap-1.5 px-3 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold"
                >
                    <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
            </div>

            {/* Table header */}
            <div className="hidden md:grid grid-cols-6 gap-3 px-5 py-2 bg-slate-50 border-b border-slate-100">
                {['Name', 'Phone', 'WhatsApp', 'Email', 'Details', 'Link'].map(h => (
                    <p key={h} className="text-[9px] font-black uppercase tracking-wider text-slate-400">{h}</p>
                ))}
            </div>

            {/* Rows */}
            <div className="divide-y divide-slate-100 max-h-[55vh] overflow-y-auto">
                {leads.map((lead, i) => (
                    <LeadCard key={lead.id ?? i} lead={lead} idx={i} />
                ))}
            </div>

            {/* Footer */}
            <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <p className="text-[10px] text-slate-400 font-medium">
                    {status === 'running' ? '● Live — more leads incoming...' : `Extraction complete`}
                </p>
                <Button
                    variant="ghost"
                    onClick={onExport}
                    className="h-auto p-0 text-[10px] font-bold text-orange-600 hover:text-orange-700 hover:bg-transparent"
                >
                    Download all as CSV →
                </Button>
            </div>
        </div>
    );
}

// ── CSV export ────────────────────────────────────────────────────────────
function exportCSV(leads: any[], prefix = 'opensooq-leads') {
    const header = 'Name,Phone,WhatsApp,Email,Source,Listing Title,Region,URL';
    const rows = leads.map(l => [
        l.name ?? '', l.phone ?? '', l.whatsapp ?? '', l.email ?? '', l.source ?? '',
        l.listing_title ?? '', l.region ?? '', l.url ?? ''
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    const csv = [header, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${prefix}-${Date.now()}.csv`;
    a.click();
}

// ════════════════════════════════════════════════════════════════════════════
// ── Main Component ──────────────────────────────────────────────────────────
// ════════════════════════════════════════════════════════════════════════════
export default function OpensooqRunner({ tool }: any) {
    // Form
    const [keyword, setKeyword]   = useState('');
    const [country, setCountry]   = useState('jo');
    const [limit, setLimit]       = useState(50);

    // Run state
    const [status, setStatus]     = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [leads, setLeads]       = useState<any[]>([]);
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [errorMsg, setError]    = useState('');
    const campaignIdRef = useRef<string>('');

    // Campaigns tab
    const [campaigns, setCampaigns]     = useState<any[]>([]);
    const [selectedCampaign, setSelectedCampaign] = useState<any>(null);
    const [campaignLeads, setCampaignLeads]       = useState<any[]>([]);
    const [loadingCampaigns, setLoadingCampaigns] = useState(false);
    const [loadingDetail, setLoadingDetail]       = useState(false);

    // Active tab
    const [activeTab, setActiveTab] = useState('extract');

    const wsRef = useRef<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);

    // ── WebSocket ──
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

                    // Real-time lead display
                    if (msg.event === 'prospecting.lead.extracted') {
                        const lead = msg.data?.lead;
                        const cid = msg.data?.campaignId || lead?.campaign_id;
                        if (lead && cid === campaignIdRef.current) {
                            setLeads(prev => [...prev, lead]);
                        }
                    }

                    // Progress events
                    if (msg.event === 'opensooq.extract.progress' && msg.data?.campaignId === campaignIdRef.current) {
                        const d = msg.data;
                        // Cookie/auth status
                        if (d.status === 'authenticated') {
                            setProgressMsg(d.message || 'Authenticated ✓');
                        }
                        if (d.status === 'no_auth' || d.status === 'no_cookies') {
                            setProgressMsg(d.message || 'No cookies — phone reveal may not work');
                            setError(d.message || '');
                        }
                        if (d.status === 'extracting' && d.extracted != null) {
                            const pct = Math.min(5 + (d.extracted / limit) * 90, 95);
                            setProgress(pct);
                            setProgressMsg(`Extracting leads for "${keyword}"... (page ${d.page || '?'})`);
                        }
                        if (d.status === 'searching') {
                            setProgressMsg(`Searching page ${d.page || '?'}...`);
                        }
                        if (d.status === 'launching') {
                            setProgressMsg('Launching browser...');
                        }
                        if (d.status === 'completed') {
                            setStatus('done');
                            setProgress(100);
                            setProgressMsg(`Done — ${d.extracted || 0} leads extracted.`);
                        }
                        if (d.status === 'stopping') {
                            setProgressMsg('Stopping extraction...');
                        }
                        if (d.status === 'stopped') {
                            setStatus('done');
                            setProgress(100);
                            setProgressMsg(`Stopped — ${d.extracted || 0} leads captured.`);
                        }
                    }

                    // RPC responses
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
                } catch { /* empty */ }
            };
        };

        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // ── Real-time polling fallback: fetch leads from DB every 4s while running ──
    useEffect(() => {
        if (status !== 'running' || !connected) return;
        const cid = campaignIdRef.current;
        if (!cid) return;

        const poll = setInterval(async () => {
            try {
                const ws = wsRef.current;
                if (!ws || ws.readyState !== WebSocket.OPEN) return;

                // Quick RPC to get leads from DB
                const res = await new Promise<any>((resolve, reject) => {
                    if (!(ws as any)._pending) (ws as any)._pending = new Map();
                    const requestId = Math.random().toString(36).slice(2);
                    (ws as any)._pending.set(requestId, { resolve, reject });
                    ws.send(JSON.stringify({
                        type: 'plugin_rpc', requestId,
                        payload: { plugin: 'opensooq', action: 'opensooq.leads.list', data: { campaignId: cid, limit: 500 } }
                    }));
                    setTimeout(() => {
                        if ((ws as any)._pending?.has(requestId)) {
                            (ws as any)._pending.delete(requestId);
                            reject(new Error('poll timeout'));
                        }
                    }, 5000);
                });

                if (res?.leads && res.leads.length > 0) {
                    setLeads(prev => {
                        // Only update if DB has more leads than local state
                        if (res.leads.length > prev.length) {
                            return res.leads;
                        }
                        return prev;
                    });
                }
            } catch { /* empty */ }
        }, 4000);

        return () => clearInterval(poll);
    }, [status, connected]);

    // ── RPC helper ──
    const callRPC = useCallback((action: string, data: any = {}): Promise<any> => {
        return new Promise((resolve, reject) => {
            const ws = wsRef.current;
            if (!ws || ws.readyState !== WebSocket.OPEN) return reject(new Error('Not connected'));
            if (!(ws as any)._pending) (ws as any)._pending = new Map();
            const requestId = Math.random().toString(36).slice(2);
            (ws as any)._pending.set(requestId, { resolve, reject });
            ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: 'opensooq', action, data } }));
            setTimeout(() => {
                if ((ws as any)._pending?.has(requestId)) {
                    (ws as any)._pending.get(requestId).reject(new Error('Timeout'));
                    (ws as any)._pending.delete(requestId);
                }
            }, 30000);
        });
    }, []);

    // ── Start extraction ──
    const handleStart = async () => {
        if (!keyword.trim()) return;
        const cId = `opensooq_${Date.now()}`;
        campaignIdRef.current = cId;

        setStatus('running');
        setLeads([]);
        setProgress(5);
        setProgressMsg('Starting extraction...');
        setError('');

        try {
            await callRPC('opensooq.extract.start', {
                keyword: keyword.trim(),
                country,
                limit,
                campaignId: cId,
            });
            setProgressMsg(`Extracting leads for "${keyword}" in ${COUNTRIES.find(c => c.code === country)?.name}...`);
        } catch (err: any) {
            setError(err.message);
            setStatus('error');
        }
    };

    // ── Stop ──
    const handleStop = async () => {
        const cId = campaignIdRef.current;
        if (cId) {
            try {
                await callRPC('opensooq.extract.stop', { campaignId: cId });
            } catch (err: any) {
                try { await callRPC('opensooq.extract.stop.all', { /* empty */ }); } catch { /* empty */ }
            }
        }
        setStatus('done');
        setProgressMsg(`Stopped — ${leads.length} leads captured.`);
        setProgress(100);
    };

    // ── Load campaigns ──
    const loadCampaigns = useCallback(async () => {
        if (!connected) return;
        setLoadingCampaigns(true);
        try {
            const res = await callRPC('opensooq.campaigns.list');
            if (res?.campaigns) setCampaigns(res.campaigns);
        } catch (err) {
            console.error('Failed to load campaigns', err);
        }
        setLoadingCampaigns(false);
    }, [connected, callRPC]);

    // ── Load campaign detail ──
    const openCampaign = async (campaign: any) => {
        setSelectedCampaign(campaign);
        setLoadingDetail(true);
        setCampaignLeads([]);
        try {
            const res = await callRPC('opensooq.campaign.detail', { campaignId: campaign.id });
            if (res?.leads) setCampaignLeads(res.leads);
        } catch (err) {
            console.error('Failed to load campaign detail', err);
        }
        setLoadingDetail(false);
    };

    // Load campaigns when tab changes to 'campaigns'
    useEffect(() => {
        if (activeTab === 'campaigns' && connected) {
            loadCampaigns();
        }
    }, [activeTab, connected, loadCampaigns]);

    // ── Status helpers ──
    const statusBadge = (s: string) => {
        const map: Record<string, { color: string; label: string }> = {
            running:   { color: 'bg-orange-50 border-orange-200 text-orange-700', label: 'Running' },
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

    if (!connected) return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center font-sans">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">{__('general.connecting_to_runtime')}</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* ── Top bar ── */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-orange-500 to-rose-500 rounded-lg flex items-center justify-center shadow-sm">
                        <Globe className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">{__('general.opensooq_lead_extractor')}</span>
                </div>
                <div className="flex items-center gap-3">
                    {leads.length > 0 && status !== 'idle' && (
                        <Button
                            variant="outline"
                            onClick={() => exportCSV(leads)}
                            className="h-8 gap-1.5 px-3 bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 text-xs font-bold"
                        >
                            <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
                    )}
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-orange-50 border-orange-200 text-orange-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-orange-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Extracting...' : status === 'done' ? `${leads.length} leads found` : 'Ready'}
                    </Badge>
                </div>
            </div>

            {/* ── Content ── */}
            <div className="max-w-5xl mx-auto px-4 py-6">
                <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full max-w-md grid-cols-2 mb-6 bg-slate-100 p-1 rounded-xl">
                        <TabsTrigger
                            value="extract"
                            className="gap-2 text-sm font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg"
                        >
                            <Zap className="w-4 h-4" /> Extract
                            {status === 'running' && (
                                <span className="ml-1 w-2 h-2 rounded-full bg-orange-500 animate-pulse" />
                            )}
                        </TabsTrigger>
                        <TabsTrigger
                            value="campaigns"
                            className="gap-2 text-sm font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg"
                        >
                            <History className="w-4 h-4" /> Campaigns
                            {campaigns.length > 0 && (
                                <span className="ml-1 text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0 rounded-full font-bold">
                                    {campaigns.length}
                                </span>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    {/* ════════════════════════════════════════════════════ */}
                    {/* ── TAB 1: Extract ─────────────────────────────── */}
                    {/* ════════════════════════════════════════════════════ */}
                    <TabsContent value="extract" className="space-y-6">
                        {/* Config card */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                            <div className="mb-5">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900">{__('general.find_leads_on_opensooq')}</h1>
                                <p className="text-sm text-slate-400 mt-1">{__('general.the_largest_arab_classifieds_platform_real_people_real_phone_numbers')}</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                {/* Keyword */}
                                <div className="md:col-span-1">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">{__('general.keyword_category')}</label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="text"
                                            value={keyword}
                                            onChange={e => setKeyword(e.target.value)}
                                            onKeyDown={e => e.key === 'Enter' && handleStart()}
                                            placeholder={__('general.real_estate_cars_jobs')}
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>

                                {/* Country */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Country</label>
                                    <div className="relative">
                                        <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <select
                                            value={country}
                                            onChange={e => setCountry(e.target.value)}
                                            className="w-full pl-9 pr-8 h-11 text-sm border border-slate-200 focus:border-orange-400 rounded-md outline-none transition-all bg-slate-50 appearance-none"
                                        >
                                            {COUNTRIES.map(c => (
                                                <option key={c.code} value={c.code}>{c.name}</option>
                                            ))}
                                        </select>
                                        <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>

                                {/* Limit */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">{__('general.max_leads')}</label>
                                    <div className="relative">
                                        <Users className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="number"
                                            min={10} max={1000} step={10}
                                            value={limit}
                                            onChange={e => setLimit(parseInt(e.target.value, 10))}
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Start / Stop */}
                            <div className="flex gap-3">
                                {status === 'running' ? (
                                    <Button
                                        variant="outline"
                                        onClick={handleStop}
                                        className="h-11 gap-2 px-6 bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100 hover:text-rose-800 text-sm font-bold"
                                    >
                                        <Square className="w-4 h-4" /> Stop
                                    </Button>
                                ) : (
                                    <Button
                                        onClick={handleStart}
                                        disabled={!keyword.trim()}
                                        className="h-11 gap-2 px-6 bg-gradient-to-r from-orange-500 to-rose-500 text-white shadow-md text-sm font-bold hover:opacity-90"
                                    >
                                        <Play className="w-4 h-4" />{__('general.start_extraction')}</Button>
                                )}
                                {leads.length > 0 && status !== 'running' && (
                                    <Button
                                        variant="outline"
                                        onClick={async () => {
                                            await callRPC('opensooq.leads.clear');
                                            setLeads([]);
                                            setStatus('idle');
                                            setProgress(0);
                                        }}
                                        className="h-11 px-4 border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-600 text-sm font-medium"
                                    >
                                        Clear
                                    </Button>
                                )}
                            </div>

                            {/* Progress */}
                            {status === 'running' && (
                                <div className="mt-4 space-y-1.5 animate-in fade-in duration-300">
                                    <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                        <span className="flex items-center gap-1.5"><RefreshCw className="w-3 h-3 animate-spin" />{progressMsg}</span>
                                        <span className="font-bold text-orange-600">{leads.length} leads so far</span>
                                    </div>
                                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-orange-500 to-rose-400 transition-all duration-500 rounded-full"
                                            style={{ width: `${progress}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Error */}
                            {status === 'error' && (
                                <div className="mt-4 flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-4 animate-in fade-in duration-300">
                                    <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    <p className="text-sm text-rose-700 font-medium">{errorMsg}</p>
                                </div>
                            )}
                        </div>

                        {/* Stats */}
                        {leads.length > 0 && (
                            <div className="grid grid-cols-2 md:grid-cols-5 gap-4 animate-in fade-in duration-300">
                                <StatCard label="Total" value={leads.length} icon={Users} color="bg-orange-50 border-orange-200" />
                                <StatCard label={__('general.with_phone')} value={leads.filter((l: any) => l.phone).length} icon={Phone} />
                                <StatCard label="WhatsApp" value={leads.filter((l: any) => l.whatsapp).length} icon={MessageCircle} color="bg-emerald-50 border-emerald-200" />
                                <StatCard label={__('general.with_email')} value={leads.filter((l: any) => l.email).length} icon={Mail} />
                                <StatCard label="Country" value={COUNTRIES.find(c => c.code === country)?.name} icon={MapPin} />
                            </div>
                        )}

                        {/* Leads table */}
                        <LeadsTable leads={leads} status={status} onExport={() => exportCSV(leads)} />

                        {/* Empty state */}
                        {status === 'idle' && leads.length === 0 && (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <Globe className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                <h3 className="text-sm font-bold text-slate-700">{__('general.opensooq_has_millions_of_active_listings')}</h3>
                                <p className="text-xs text-slate-400 mt-2 max-w-xs mx-auto">{__('general.enter_a_keyword_and_pick_a_country_to_extract_real_contact_leads_from_the_largest_arab_classifieds_platform')}</p>
                            </div>
                        )}
                    </TabsContent>

                    {/* ════════════════════════════════════════════════════ */}
                    {/* ── TAB 2: Campaigns ───────────────────────────── */}
                    {/* ════════════════════════════════════════════════════ */}
                    <TabsContent value="campaigns" className="space-y-4">
                        {/* Campaign Detail View */}
                        {selectedCampaign ? (
                            <div className="space-y-4 animate-in fade-in slide-in-from-right-3 duration-300">
                                {/* Back + Title */}
                                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                                    <button
                                        onClick={() => { setSelectedCampaign(null); setCampaignLeads([]); }}
                                        className="flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors mb-4"
                                    >
                                        <ArrowLeft className="w-3.5 h-3.5" />{__('general.back_to_campaigns')}</button>

                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                                <Search className="w-4 h-4 text-orange-500" />
                                                "{selectedCampaign.keyword}"
                                            </h2>
                                            <div className="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="w-3 h-3" />
                                                    {COUNTRIES.find(c => c.code === selectedCampaign.country)?.name || selectedCampaign.country}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="w-3 h-3" />
                                                    {new Date(selectedCampaign.created_at).toLocaleDateString()}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Hash className="w-3 h-3" />
                                                    {selectedCampaign.total} leads
                                                </span>
                                            </div>
                                        </div>
                                        {statusBadge(selectedCampaign.status)}
                                    </div>

                                    {/* Stats */}
                                    {campaignLeads.length > 0 && (
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                                            <StatCard label="Total" value={campaignLeads.length} icon={Users} color="bg-orange-50 border-orange-200" />
                                            <StatCard label={__('general.with_phone')} value={campaignLeads.filter(l => l.phone).length} icon={Phone} />
                                            <StatCard label="WhatsApp" value={campaignLeads.filter(l => l.whatsapp).length} icon={MessageCircle} color="bg-emerald-50 border-emerald-200" />
                                            <StatCard label={__('general.with_email')} value={campaignLeads.filter(l => l.email).length} icon={Mail} />
                                        </div>
                                    )}
                                </div>

                                {/* Leads */}
                                {loadingDetail ? (
                                    <div className="py-16 text-center">
                                        <div className="w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">{__('general.loading_leads')}</p>
                                    </div>
                                ) : (
                                    <LeadsTable
                                        leads={campaignLeads}
                                        onExport={() => exportCSV(campaignLeads, `campaign-${selectedCampaign.keyword}`)}
                                    />
                                )}

                                {!loadingDetail && campaignLeads.length === 0 && (
                                    <div className="py-16 text-center bg-white border border-dashed border-slate-200 rounded-2xl">
                                        <Users className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                        <p className="text-sm text-slate-500 font-medium">{__('general.no_leads_in_this_campaign')}</p>
                                    </div>
                                )}
                            </div>
                        ) : (
                            /* Campaign List View */
                            <div className="space-y-4 animate-in fade-in duration-300">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-bold text-slate-800">{__('general.past_campaigns')}</h2>
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
                                        <div className="w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">{__('general.loading_campaigns')}</p>
                                    </div>
                                ) : campaigns.length === 0 ? (
                                    <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                        <History className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                        <h3 className="text-sm font-bold text-slate-700">{__('general.no_campaigns_yet')}</h3>
                                        <p className="text-xs text-slate-400 mt-2">{__('general.start_an_extraction_from_the_extract_tab_to_see_campaigns_here')}</p>
                                    </div>
                                ) : (
                                    <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                                        {campaigns.map((c, i) => (
                                            <button
                                                key={c.id}
                                                onClick={() => openCampaign(c)}
                                                className={`w-full flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors text-left ${i < campaigns.length - 1 ? 'border-b border-slate-100' : ''}`}
                                            >
                                                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-100 to-rose-100 flex items-center justify-center shrink-0">
                                                    <Search className="w-4.5 h-4.5 text-orange-600" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <p className="text-sm font-bold text-slate-800 truncate">"{c.keyword}"</p>
                                                        {statusBadge(c.status)}
                                                    </div>
                                                    <div className="flex items-center gap-4 mt-1 text-[11px] text-slate-400">
                                                        <span className="flex items-center gap-1">
                                                            <MapPin className="w-3 h-3" />
                                                            {COUNTRIES.find(co => co.code === c.country)?.name || c.country}
                                                        </span>
                                                        <span className="flex items-center gap-1">
                                                            <Users className="w-3 h-3" />
                                                            {c.total} leads
                                                        </span>
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="w-3 h-3" />
                                                            {new Date(c.created_at).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                </div>
                                                <ChevronDown className="w-4 h-4 text-slate-300 -rotate-90 shrink-0" />
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
