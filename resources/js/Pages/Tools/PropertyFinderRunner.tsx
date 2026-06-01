import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Search, MapPin, Users, Download, Play, Square,
    AlertCircle, CheckCircle2, RefreshCw, Globe,
    Phone, ChevronDown, Clipboard, History,
    Zap, ArrowLeft, Calendar, Hash, ExternalLink,
    MessageCircle, Home, BedDouble, Bath, Ruler,
    Building2, Tag, MapPinned,
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
    { code: 'ae', name: 'UAE',          domain: 'propertyfinder.ae' },
    { code: 'bh', name: 'Bahrain',      domain: 'propertyfinder.bh' },
    { code: 'qa', name: 'Qatar',        domain: 'propertyfinder.qa' },
    { code: 'eg', name: 'Egypt',        domain: 'propertyfinder.eg' },
    { code: 'sa', name: 'Saudi Arabia', domain: 'bayut.sa' },
    { code: 'ma', name: 'Morocco',      domain: 'sarouty.ma' },
];

// ── Lead Card ────────────────────────────────────────────────────────────
function LeadCard({ lead, idx }: { lead: any; idx: number }) {
    const [copied, setCopied] = useState(false);

    const copyRow = () => {
        const text = [
            lead.title, lead.price, lead.property_type, lead.bedrooms, lead.bathrooms,
            lead.area, lead.phone, lead.whatsapp, lead.address, lead.reference, lead.url,
        ].filter(Boolean).join('\t');
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    return (
        <div className="group px-5 py-4 hover:bg-slate-50/80 transition-colors border-b border-slate-100/80 last:border-0">
            <div className="flex items-start gap-4">
                <div className="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-[10px] font-black shrink-0 shadow-sm mt-0.5">
                    {idx + 1}
                </div>
                <div className="flex-1 min-w-0 space-y-2">
                    {/* Title row */}
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-bold text-slate-800 leading-snug truncate" title={lead.title || ''}>
                                {lead.title || '—'}
                            </p>
                            <p className="text-xs text-slate-400 mt-0.5 truncate" title={lead.address || ''}>
                                <MapPinned className="w-3 h-3 inline mr-1" />{lead.address || '—'}
                            </p>
                        </div>
                        {lead.price && (
                            <Badge variant="outline" className="shrink-0 text-xs font-bold text-indigo-700 bg-indigo-50 border-indigo-200 px-2 py-0.5">
                                {lead.price}
                            </Badge>
                        )}
                    </div>

                    {/* Property details grid */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-x-4 gap-y-1.5">
                        <div className="min-w-0">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Type</p>
                            <p className="text-xs text-slate-600 truncate">{lead.property_type || '—'}</p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Beds</p>
                            <p className="text-xs text-slate-600 truncate flex items-center gap-1">
                                <BedDouble className="w-3 h-3 text-indigo-400" />{lead.bedrooms || '—'}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Baths</p>
                            <p className="text-xs text-slate-600 truncate flex items-center gap-1">
                                <Bath className="w-3 h-3 text-blue-400" />{lead.bathrooms || '—'}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Area</p>
                            <p className="text-xs text-slate-600 truncate flex items-center gap-1">
                                <Ruler className="w-3 h-3 text-teal-400" />{lead.area || '—'}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">Phone</p>
                            <p className="text-xs font-mono text-slate-600 truncate">{lead.phone || '—'}</p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">WhatsApp</p>
                            <p className="text-xs font-mono text-emerald-600 truncate">{lead.whatsapp || '—'}</p>
                        </div>
                    </div>

                    {/* Footer row */}
                    <div className="flex items-center gap-4 pt-1">
                        {lead.reference && (
                            <span className="text-[10px] text-slate-400 flex items-center gap-1">
                                <Tag className="w-3 h-3" />Ref: {lead.reference}
                            </span>
                        )}
                        {lead.furnishings && (
                            <span className="text-[10px] text-slate-400">{lead.furnishings}</span>
                        )}
                        {lead.url && (
                            <a href={lead.url} target="_blank" rel="noreferrer" className="text-[10px] text-blue-500 hover:underline flex items-center gap-0.5 ml-auto">
                                <ExternalLink className="w-3 h-3" /> View
                            </a>
                        )}
                    </div>
                </div>
                <Button
                    variant="ghost" size="icon"
                    onClick={copyRow}
                    className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 hover:bg-slate-100 shrink-0"
                    title={__('general.copy_row')}
                >
                    {copied ? <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> : <Clipboard className="w-3.5 h-3.5 text-slate-400" />}
                </Button>
            </div>
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
                <h3 className="font-bold text-slate-800 text-sm">{leads.length} Properties</h3>
                <Button
                    onClick={onExport}
                    className="h-8 gap-1.5 px-3 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold"
                >
                    <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
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
                    {status === 'running' ? '● Live — more properties incoming...' : `Extraction complete`}
                </p>
                <Button
                    variant="ghost"
                    onClick={onExport}
                    className="h-auto p-0 text-[10px] font-bold text-indigo-600 hover:text-indigo-700 hover:bg-transparent"
                >
                    Download all as CSV →
                </Button>
            </div>
        </div>
    );
}

// ── CSV export ────────────────────────────────────────────────────────────
function exportCSV(leads: any[], prefix = 'propertyfinder-leads') {
    const header = 'Title,Price,Type,Bedrooms,Bathrooms,Area,Furnishings,Reference,Address,Phone,WhatsApp,Source,URL';
    const rows = leads.map(l => [
        l.title ?? '', l.price ?? '', l.property_type ?? '', l.bedrooms ?? '',
        l.bathrooms ?? '', l.area ?? '', l.furnishings ?? '', l.reference ?? '',
        l.address ?? '', l.phone ?? '', l.whatsapp ?? '', l.source ?? '', l.url ?? ''
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
export default function PropertyFinderRunner({ tool }: any) {
    // Form
    const [keyword, setKeyword]   = useState('');
    const [country, setCountry]   = useState('ae');
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
                    if (msg.event === 'propertyfinder.extract.progress' && msg.data?.campaignId === campaignIdRef.current) {
                        const d = msg.data;
                        if (d.status === 'authenticated') {
                            setProgressMsg(d.message || 'Authenticated ✓');
                        }
                        if (d.status === 'extracting' && d.extracted != null) {
                            const pct = Math.min(5 + (d.extracted / limit) * 90, 95);
                            setProgress(pct);
                            setProgressMsg(`Extracting properties for "${keyword}"... (page ${d.page || '?'})`);
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
                            setProgressMsg(`Done — ${d.extracted || 0} properties extracted.`);
                        }
                        if (d.status === 'stopping') {
                            setProgressMsg('Stopping extraction...');
                        }
                        if (d.status === 'stopped') {
                            setStatus('done');
                            setProgress(100);
                            setProgressMsg(`Stopped — ${d.extracted || 0} properties captured.`);
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
                } catch {}
            };
        };

        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    }, []);

    // ── Real-time polling fallback ──
    useEffect(() => {
        if (status !== 'running' || !connected) return;
        const cid = campaignIdRef.current;
        if (!cid) return;

        const poll = setInterval(async () => {
            try {
                const ws = wsRef.current;
                if (!ws || ws.readyState !== WebSocket.OPEN) return;
                const res = await new Promise<any>((resolve, reject) => {
                    if (!(ws as any)._pending) (ws as any)._pending = new Map();
                    const requestId = Math.random().toString(36).slice(2);
                    (ws as any)._pending.set(requestId, { resolve, reject });
                    ws.send(JSON.stringify({
                        type: 'plugin_rpc', requestId,
                        payload: { plugin: 'propertyfinder', action: 'propertyfinder.leads.list', data: { campaignId: cid, limit: 500 } }
                    }));
                    setTimeout(() => {
                        if ((ws as any)._pending?.has(requestId)) {
                            (ws as any)._pending.delete(requestId);
                            reject(new Error('poll timeout'));
                        }
                    }, 5000);
                });
                if (res?.leads && res.leads.length > 0) {
                    setLeads(prev => res.leads.length > prev.length ? res.leads : prev);
                }
            } catch {}
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
            ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: 'propertyfinder', action, data } }));
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
        const cId = `pf_${Date.now()}`;
        campaignIdRef.current = cId;

        setStatus('running');
        setLeads([]);
        setProgress(5);
        setProgressMsg('Starting extraction...');
        setError('');

        try {
            await callRPC('propertyfinder.extract.start', {
                keyword: keyword.trim(),
                country,
                limit,
                campaignId: cId,
            });
            setProgressMsg(`Extracting properties for "${keyword}" in ${COUNTRIES.find(c => c.code === country)?.name}...`);
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
                await callRPC('propertyfinder.extract.stop', { campaignId: cId });
            } catch (err: any) {
                try { await callRPC('propertyfinder.extract.stop.all', {}); } catch {}
            }
        }
        setStatus('done');
        setProgressMsg(`Stopped — ${leads.length} properties captured.`);
        setProgress(100);
    };

    // ── Load campaigns ──
    const loadCampaigns = useCallback(async () => {
        if (!connected) return;
        setLoadingCampaigns(true);
        try {
            const res = await callRPC('propertyfinder.campaigns.list');
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
            const res = await callRPC('propertyfinder.campaign.detail', { campaignId: campaign.id });
            if (res?.leads) setCampaignLeads(res.leads);
        } catch (err) {
            console.error('Failed to load campaign detail', err);
        }
        setLoadingDetail(false);
    };

    // Load campaigns when tab changes
    useEffect(() => {
        if (activeTab === 'campaigns' && connected) {
            loadCampaigns();
        }
    }, [activeTab, connected, loadCampaigns]);

    // ── Status helpers ──
    const statusBadge = (s: string) => {
        const map: Record<string, { color: string; label: string }> = {
            running:   { color: 'bg-indigo-50 border-indigo-200 text-indigo-700', label: 'Running' },
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
                <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">{__('general.connecting_to_runtime')}</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* ── Top bar ── */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-lg flex items-center justify-center shadow-sm">
                        <Building2 className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">{__('general.propertyfinder_extractor')}</span>
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
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-indigo-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Extracting...' : status === 'done' ? `${leads.length} properties found` : 'Ready'}
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
                                <span className="ml-1 w-2 h-2 rounded-full bg-indigo-500 animate-pulse" />
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
                                <h1 className="text-xl font-bold tracking-tight text-slate-900">{__('general.find_real_estate_leads')}</h1>
                                <p className="text-sm text-slate-400 mt-1">{__('general.extract_property_listings_with_contact_details_from_propertyfinder_bayut_sarouty')}</p>
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
                                            placeholder={__('general.apartment_villa_office')}
                                            className="pl-9 h-11 text-sm bg-slate-50"
                                        />
                                    </div>
                                </div>

                                {/* Country */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">{__('general.country_site')}</label>
                                    <div className="relative">
                                        <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <select
                                            value={country}
                                            onChange={e => setCountry(e.target.value)}
                                            className="w-full pl-9 pr-8 h-11 text-sm border border-slate-200 focus:border-indigo-400 rounded-md outline-none transition-all bg-slate-50 appearance-none"
                                        >
                                            {COUNTRIES.map(c => (
                                                <option key={c.code} value={c.code}>{c.name} — {c.domain}</option>
                                            ))}
                                        </select>
                                        <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>

                                {/* Limit */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">{__('general.max_properties')}</label>
                                    <div className="relative">
                                        <Home className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="number"
                                            min={10} max={500} step={10}
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
                                        className="h-11 gap-2 px-6 bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-md text-sm font-bold hover:opacity-90"
                                    >
                                        <Play className="w-4 h-4" />{__('general.start_extraction')}</Button>
                                )}
                                {leads.length > 0 && status !== 'running' && (
                                    <Button
                                        variant="outline"
                                        onClick={async () => {
                                            await callRPC('propertyfinder.leads.clear');
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
                                        <span className="font-bold text-indigo-600">{leads.length} properties so far</span>
                                    </div>
                                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-500 rounded-full"
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
                                <StatCard label="Total" value={leads.length} icon={Building2} color="bg-indigo-50 border-indigo-200" />
                                <StatCard label={__('general.with_phone')} value={leads.filter((l: any) => l.phone).length} icon={Phone} />
                                <StatCard label="WhatsApp" value={leads.filter((l: any) => l.whatsapp).length} icon={MessageCircle} color="bg-emerald-50 border-emerald-200" />
                                <StatCard label={__('general.with_beds')} value={leads.filter((l: any) => l.bedrooms).length} icon={BedDouble} color="bg-blue-50 border-blue-200" />
                                <StatCard label="Country" value={COUNTRIES.find(c => c.code === country)?.name} icon={MapPin} />
                            </div>
                        )}

                        {/* Leads table */}
                        <LeadsTable leads={leads} status={status} onExport={() => exportCSV(leads)} />

                        {/* Empty state */}
                        {status === 'idle' && leads.length === 0 && (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <Building2 className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                <h3 className="text-sm font-bold text-slate-700">{__('general.propertyfinder_bayut_sarouty')}</h3>
                                <p className="text-xs text-slate-400 mt-2 max-w-xs mx-auto">{__('general.search_for_properties_across_the_middle_east_north_africa_extract_real_listings_with_contact_details_prices_and_property_specs')}</p>
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
                                                <Search className="w-4 h-4 text-indigo-500" />
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
                                                    {selectedCampaign.total} properties
                                                </span>
                                            </div>
                                        </div>
                                        {statusBadge(selectedCampaign.status)}
                                    </div>

                                    {/* Stats */}
                                    {campaignLeads.length > 0 && (
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                                            <StatCard label="Total" value={campaignLeads.length} icon={Building2} color="bg-indigo-50 border-indigo-200" />
                                            <StatCard label={__('general.with_phone')} value={campaignLeads.filter(l => l.phone).length} icon={Phone} />
                                            <StatCard label="WhatsApp" value={campaignLeads.filter(l => l.whatsapp).length} icon={MessageCircle} color="bg-emerald-50 border-emerald-200" />
                                            <StatCard label={__('general.with_beds')} value={campaignLeads.filter(l => l.bedrooms).length} icon={BedDouble} color="bg-blue-50 border-blue-200" />
                                        </div>
                                    )}
                                </div>

                                {/* Leads */}
                                {loadingDetail ? (
                                    <div className="py-16 text-center">
                                        <div className="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">{__('general.loading_properties')}</p>
                                    </div>
                                ) : (
                                    <LeadsTable
                                        leads={campaignLeads}
                                        onExport={() => exportCSV(campaignLeads, `campaign-${selectedCampaign.keyword}`)}
                                    />
                                )}

                                {!loadingDetail && campaignLeads.length === 0 && (
                                    <div className="py-16 text-center bg-white border border-dashed border-slate-200 rounded-2xl">
                                        <Building2 className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                        <p className="text-sm text-slate-500 font-medium">{__('general.no_properties_in_this_campaign')}</p>
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
                                        <div className="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
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
                                                <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-blue-100 flex items-center justify-center shrink-0">
                                                    <Search className="w-4.5 h-4.5 text-indigo-600" />
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
                                                            <Building2 className="w-3 h-3" />
                                                            {c.total} properties
                                                        </span>
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="w-3 h-3" />
                                                            {new Date(c.created_at).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                </div>
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
