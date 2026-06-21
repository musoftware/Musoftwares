import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Users, Download, Play, Square,
    AlertCircle, CheckCircle2, RefreshCw,
    Phone, Tag, ChevronDown, Clipboard, ShoppingBag
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () =>
    typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Lead card ─────────────────────────────────────────────────────────────────
function LeadCard({ lead, idx }: { lead: any; idx: number }) {
    const [copied, setCopied] = useState(false);
    const copyRow = () => {
        const text = [lead.name, lead.phone, lead.listing_title, lead.region, lead.price].filter(Boolean).join('\t');
        navigator.clipboard.writeText(text).then(() => { setCopied(true); setTimeout(() => setCopied(false), 1500); });
    };
    return (
        <div className="group flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0">
            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white text-xs font-black shrink-0">
                {idx + 1}
            </div>
            <div className="flex-1 grid grid-cols-2 md:grid-cols-5 gap-3 min-w-0">
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Name</p>
                    <p className="text-xs font-semibold text-slate-800 truncate">{lead.name || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Phone</p>
                    <p className="text-xs font-mono text-slate-600 truncate">{lead.phone || '—'}</p>
                </div>
                <div className="min-w-0 md:col-span-2">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Listing</p>
                    <p className="text-xs text-slate-600 truncate">{lead.listing_title || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Price</p>
                    <p className="text-xs font-semibold text-emerald-600 truncate">{lead.price || '—'}</p>
                </div>
            </div>
            <Button variant="ghost" size="icon" onClick={copyRow} className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 hover:bg-slate-100" title={__('general.copy_row')}>
                {copied ? <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> : <Clipboard className="w-3.5 h-3.5 text-slate-400" />}
            </Button>
        </div>
    );
}

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

function exportCSV(leads: any[]) {
    const header = 'Name,Phone,Listing Title,Region,Price,Source,URL';
    const rows = leads.map(l => [l.name ?? '', l.phone ?? '', l.listing_title ?? '', l.region ?? '', l.price ?? '', 'haraj', l.url ?? '']
        .map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    const csv = [header, ...rows].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
    a.download = `haraj-leads-${Date.now()}.csv`;
    a.click();
}

// ── Main ──────────────────────────────────────────────────────────────────────
export default function HarajRunner({ tool }: any) {
    const [keyword, setKeyword]   = useState('');
    const [limit, setLimit]       = useState(50);
    const [status, setStatus]     = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [leads, setLeads]       = useState<any[]>([]);
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [errorMsg, setError]    = useState('');
    const campaignIdRef = useRef<string>('');
    const wsRef = useRef<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);

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
                    if (msg.event === 'prospecting.lead.extracted' && msg.data?.lead?.campaign_id === campaignIdRef.current) {
                        setLeads(prev => [...prev, msg.data.lead]);
                        setProgress(p => Math.min(p + 2, 95));
                    }
                    if (msg.event === 'haraj.extract.completed' && msg.data?.campaignId === campaignIdRef.current) {
                        setStatus('done'); setProgress(100);
                        setProgressMsg(`Done — ${msg.data.total} leads extracted.`);
                    }
                    if (msg.event === 'haraj.extract.error' && msg.data?.campaignId === campaignIdRef.current) {
                        setError(msg.data.error || 'Extraction failed.'); setStatus('error');
                    }
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const resolver = (ws as any)._pending?.get(msg.requestId);
                        if (resolver) {
                            msg.type === 'plugin_rpc_error'
                                ? resolver.reject(new Error(msg.payload?.error))
                                : resolver.resolve(msg.payload);
                            (ws as any)._pending?.delete(msg.requestId);
                        }
                    }
                } catch { /* empty */ }
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
        ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: 'haraj', action, data } }));
        setTimeout(() => {
            if ((ws as any)._pending?.has(requestId)) {
                (ws as any)._pending.get(requestId).reject(new Error('Timeout'));
                (ws as any)._pending.delete(requestId);
            }
        }, 30000);
    });

    const handleStart = async () => {
        if (!keyword.trim()) return;
        const cId = `haraj_${Date.now()}`;
        campaignIdRef.current = cId;
        setStatus('running'); setLeads([]); setProgress(5);
        setProgressMsg('Starting extraction...'); setError('');
        try {
            await callRPC('haraj.extract.start', { keyword: keyword.trim(), limit, campaignId: cId });
            setProgressMsg(`Extracting leads for "${keyword}" from Haraj.com.sa...`);
        } catch (err: any) { setError(err.message); setStatus('error'); }
    };

    if (!connected) return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center font-sans">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-green-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">{__('general.connecting_to_runtime')}</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* Top bar */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-sm">
                        <ShoppingBag className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">{__('general.haraj_lead_extractor')}</span>
                    <span className="text-[9px] font-black uppercase tracking-widest text-slate-400 bg-slate-100 rounded px-1.5 py-0.5">{__('general.saudi_arabia')}</span>
                </div>
                <div className="flex items-center gap-3">
                    {leads.length > 0 && (
                        <Button variant="outline" onClick={() => exportCSV(leads)} className="h-8 gap-1.5 px-3 bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 text-xs font-bold">
                            <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
                    )}
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-green-50 border-green-200 text-green-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-green-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Extracting...' : status === 'done' ? `${leads.length} leads found` : 'Ready'}
                    </Badge>
                </div>
            </div>

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">
                {/* Config card */}
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <div className="mb-5">
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">{__('general.find_leads_on_haraj')}</h1>
                        <p className="text-sm text-slate-400 mt-1">{__('general.saudi_arabia_s_largest_classifieds_platform_search_any_category_and_extract_real_contact_leads')}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div className="md:col-span-2">
                            <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">{__('general.keyword_category')}</label>
                            <div className="relative">
                                <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <Input
                                    type="text" value={keyword} onChange={e => setKeyword(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && handleStart()}
                                    placeholder={__('general.cars_electronics_furniture_real_estate')}
                                    className="ps-9 h-11 text-sm bg-slate-50"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">{__('general.max_leads')}</label>
                            <div className="relative">
                                <Users className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <Input type="number" min={10} max={500} step={10} value={limit} onChange={e => setLimit(parseInt(e.target.value, 10))}
                                    className="ps-9 h-11 text-sm bg-slate-50" />
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3">
                        {status === 'running' ? (
                            <Button variant="outline" onClick={() => { setStatus('done'); setProgressMsg(`Stopped — ${leads.length} leads captured.`); }}
                                className="h-11 gap-2 px-6 bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100 hover:text-rose-800 text-sm font-bold">
                                <Square className="w-4 h-4" /> Stop
                            </Button>
                        ) : (
                            <Button onClick={handleStart} disabled={!keyword.trim()}
                                className="h-11 gap-2 px-6 bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md text-sm font-bold hover:opacity-90">
                                <Play className="w-4 h-4" />{__('general.start_extraction')}</Button>
                        )}
                        {leads.length > 0 && status !== 'running' && (
                            <Button variant="outline" onClick={() => { setLeads([]); setStatus('idle'); setProgress(0); }}
                                className="h-11 px-4 border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-600 text-sm font-medium">
                                Clear
                            </Button>
                        )}
                    </div>

                    {status === 'running' && (
                        <div className="mt-4 space-y-1.5 animate-in fade-in duration-300">
                            <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                <span className="flex items-center gap-1.5"><RefreshCw className="w-3 h-3 animate-spin" />{progressMsg}</span>
                                <span>{leads.length} leads so far</span>
                            </div>
                            <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div className="h-full bg-gradient-to-r from-green-500 to-emerald-400 transition-all duration-500 rounded-full" style={{ width: `${progress}%` }} />
                            </div>
                        </div>
                    )}

                    {status === 'error' && (
                        <div className="mt-4 flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-4 animate-in fade-in duration-300">
                            <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                            <p className="text-sm text-rose-700 font-medium">{errorMsg}</p>
                        </div>
                    )}
                </div>

                {/* Stats */}
                {leads.length > 0 && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 animate-in fade-in duration-300">
                        <StatCard label={__('general.total_leads')} value={leads.length} icon={Users} color="bg-green-50 border-green-200" />
                        <StatCard label={__('general.with_phone')} value={leads.filter((l: any) => l.phone).length} icon={Phone} />
                        <StatCard label={__('general.with_price')} value={leads.filter((l: any) => l.price).length} icon={Tag} />
                        <StatCard label={__('general.with_region')} value={leads.filter((l: any) => l.region).length} icon={ChevronDown} />
                    </div>
                )}

                {/* Leads table */}
                {leads.length > 0 && (
                    <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-3 duration-400">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                            <h3 className="font-bold text-slate-800 text-sm">{leads.length} Leads Extracted from Haraj</h3>
                            <Button onClick={() => exportCSV(leads)} className="h-8 gap-1.5 px-3 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold">
                                <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
                        </div>
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-3 px-5 py-2 bg-slate-50 border-b border-slate-100">
                            {['Name', 'Phone', 'Listing Title', '', 'Price'].map((h, i) => (
                                <p key={i} className="text-[9px] font-black uppercase tracking-wider text-slate-400">{h}</p>
                            ))}
                        </div>
                        <div className="divide-y divide-slate-100 max-h-[50vh] overflow-y-auto">
                            {leads.map((lead, i) => <LeadCard key={lead.id ?? i} lead={lead} idx={i} />)}
                        </div>
                        <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <p className="text-[10px] text-slate-400 font-medium">
                                {status === 'running' ? 'Live — more leads incoming...' : 'Extraction complete'}
                            </p>
                            <Button variant="ghost" onClick={() => exportCSV(leads)} className="h-auto p-0 text-[10px] font-bold text-green-600 hover:text-green-700 hover:bg-transparent">
                                Download all as CSV →
                            </Button>
                        </div>
                    </div>
                )}

                {/* Empty state */}
                {status === 'idle' && leads.length === 0 && (
                    <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                        <ShoppingBag className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                        <h3 className="text-sm font-bold text-slate-700">{__('general.haraj_has_millions_of_active_listings')}</h3>
                        <p className="text-xs text-slate-400 mt-2 max-w-xs mx-auto">{__('general.enter_a_keyword_or_category_to_extract_real_saudi_contact_leads_from_haraj_com_sa_the_largest_arabic_classifieds_platform_in_ksa')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}
