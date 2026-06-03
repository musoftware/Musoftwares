import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    MapPin, Search, Play, Square, Download, Plus, Trash2,
    Globe, Phone, Mail, Star, Building2, RefreshCw, ChevronRight,
    Settings, Filter, HelpCircle, CheckCircle, AlertCircle,
    Zap, Database, Eye, EyeOff, SlidersHorizontal, X, ExternalLink
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';

import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { __ } from '@/lib/i18n';

// ── Helper: status badge style ────────────────────────────────────────────────
function statusStyle(status: string) {
    switch (status) {
        case 'running':   return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'completed': return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'paused':    return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'failed':    return 'bg-rose-50 text-rose-700 border-rose-200';
        default:          return 'bg-slate-50 text-slate-500 border-slate-200';
    }
}

function statusLabel(status: string) {
    switch (status) {
        case 'running':   return 'Extracting';
        case 'completed': return 'Completed';
        case 'paused':    return 'Paused';
        case 'failed':    return 'Failed';
        default:          return 'Draft';
    }
}

// ── New Campaign Modal ────────────────────────────────────────────────────────
function NewCampaignModal({
    onClose, onCreated, callRPC,
}: {
    onClose: () => void;
    onCreated: (c: any) => void;
    callRPC: (a: string, d?: any) => Promise<any>;
}) {
    const [name, setName]           = useState('');
    const [keyword, setKeyword]     = useState('');
    const [location, setLocation]   = useState('');
    const [limit, setLimit]         = useState(100);
    const [deepCrawl, setDeepCrawl] = useState(false);
    const [saving, setSaving]       = useState(false);
    const [error, setError]         = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!keyword.trim() || !location.trim()) return setError('Keyword and location are required.');
        setSaving(true);
        setError(null);
        try {
            const c = await callRPC('gm.campaign.create', {
                name:        name.trim() || undefined,
                keyword:     keyword.trim(),
                location:    location.trim(),
                limit_count: limit,
                deep_crawl:  deepCrawl,
            });
            onCreated(c);
            onClose();
        } catch (err: any) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm animate-in fade-in duration-200">
            <Card className="w-full max-w-md shadow-2xl overflow-hidden border-slate-200">
                {/* Header */}
                <div className="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 className="text-sm font-bold">{__('general.new_extraction_campaign')}</h2>
                        <p className="text-xs text-muted-foreground mt-0.5">{__('general.define_keyword_and_location_to_start_finding_businesses')}</p>
                    </div>
                    <Button variant="ghost" size="icon" onClick={onClose} className="h-8 w-8 text-slate-400">
                        <X className="w-4 h-4" />
                    </Button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="p-6 space-y-5">
                    {/* Campaign name */}
                    <div className="space-y-1.5">
                        <Label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">{__('general.campaign_name')}<span className="font-normal text-slate-400">(optional)</span></Label>
                        <Input
                            type="text"
                            placeholder='e.g. "Restaurants – Amman"'
                            value={name}
                            onChange={e => setName(e.target.value)}
                        />
                    </div>

                    {/* Keyword */}
                    <div className="space-y-1.5">
                        <Label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Keyword <span className="text-rose-500">*</span></Label>
                        <div className="relative">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <Input
                                type="text"
                                placeholder='e.g. "Coffee Shop", "Car Wash", "Dentist"'
                                value={keyword}
                                onChange={e => setKeyword(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </div>

                    {/* Location */}
                    <div className="space-y-1.5">
                        <Label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Location <span className="text-rose-500">*</span></Label>
                        <div className="relative">
                            <MapPin className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <Input
                                type="text"
                                placeholder='e.g. "Amman, Jordan", "Dubai, UAE", "New York"'
                                value={location}
                                onChange={e => setLocation(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </div>

                    {/* Limit */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">{__('general.max_results')}</Label>
                            <span className="text-sm font-bold text-slate-900 font-mono">{limit}</span>
                        </div>
                        <input
                            type="range"
                            min={10} max={500} step={10}
                            value={limit}
                            onChange={e => setLimit(parseInt(e.target.value))}
                            className="w-full accent-slate-900"
                        />
                        <div className="flex justify-between text-[10px] text-slate-400 font-mono">
                            <span>10 (Fast)</span>
                            <span>500 (Complete)</span>
                        </div>
                    </div>

                    {/* Deep crawl toggle */}
                    <div className="p-4 bg-amber-50 border border-amber-100 rounded-xl space-y-2">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-bold text-amber-900">{__('general.email_discovery')}</p>
                                <p className="text-[11px] text-amber-700 mt-0.5">{__('general.visits_each_business_website_to_find_contact_emails_slower_but_more_complete')}</p>
                            </div>
                            <Switch checked={deepCrawl} onCheckedChange={setDeepCrawl} />
                        </div>
                    </div>

                    {/* Error */}
                    {error && (
                        <div className="flex items-center gap-2 p-3 bg-rose-50 border border-rose-100 rounded-xl text-xs text-rose-700">
                            <AlertCircle className="w-4 h-4 shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    {/* Actions */}
                    <div className="flex gap-3 pt-1">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            className="flex-1"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={saving || !keyword.trim() || !location.trim()}
                            className="flex-1 gap-2"
                        >
                            {saving && <RefreshCw className="w-3.5 h-3.5 animate-spin" />}
                            {saving ? 'Creating...' : 'Create Campaign'}
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    );
}

// ── Settings Panel ────────────────────────────────────────────────────────────
function SettingsPanel({ callRPC }: { callRPC: (a: string, d?: any) => Promise<any> }) {
    const [settings, setSettings]   = useState<any>(null);
    const [proxiesText, setProxies] = useState('');
    const [saving, setSaving]       = useState(false);
    const [saved, setSaved]         = useState(false);

    useEffect(() => {
        callRPC('gm.settings.get').then(s => {
            setSettings(s);
            setProxies((s.proxies || []).join('\n'));
        }).catch(() => {});
    }, []);

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        try {
            await callRPC('gm.settings.save', {
                headless:   settings.headless,
                proxies:    proxiesText.split('\n').map((p: string) => p.trim()).filter(Boolean),
                speed:      settings.speed,
            });
            setSaved(true);
            setTimeout(() => setSaved(false), 2500);
        } catch (_) {} finally { setSaving(false); }
    };

    if (!settings) return (
        <div className="flex items-center justify-center py-20">
            <div className="w-6 h-6 border-2 border-slate-300 border-t-slate-900 rounded-full animate-spin" />
        </div>
    );

    return (
        <form onSubmit={handleSave} className="space-y-8 max-w-2xl">
            <div>
                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-700 bg-clip-text text-transparent">Settings</h1>
                <p className="text-xs text-slate-500 mt-1">{__('general.configure_extraction_behaviour_speed_and_proxy_rotation')}</p>
            </div>

            {/* Speed */}
            <Card className="p-6 space-y-4">
                <h3 className="text-sm font-bold">{__('general.extraction_speed')}</h3>
                <div className="grid grid-cols-3 gap-3">
                    {[
                        { id: 'fast',      label: 'Fast',     desc: 'Quick scan, fewer results verified' },
                        { id: 'balanced',  label: 'Balanced', desc: 'Recommended for most campaigns' },
                        { id: 'safe',      label: 'Safe',     desc: 'Slower, human-like delays to avoid blocks' },
                    ].map(opt => (
                        <button
                            key={opt.id}
                            type="button"
                            onClick={() => setSettings((s: any) => ({ ...s, speed: opt.id }))}
                            className={`p-4 rounded-xl border text-left transition-all ${settings.speed === opt.id ? 'border-slate-900 bg-slate-950 text-white' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300'}`}
                        >
                            <p className="text-xs font-bold">{opt.label}</p>
                            <p className={`text-[11px] mt-1 ${settings.speed === opt.id ? 'text-slate-300' : 'text-slate-500'}`}>{opt.desc}</p>
                        </button>
                    ))}
                </div>
            </Card>

            {/* Headless */}
            <Card className="p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-sm font-bold">{__('general.invisible_mode')}</h3>
                        <p className="text-xs text-muted-foreground mt-0.5">{__('general.run_the_browser_invisibly_in_the_background_disable_only_for_debugging')}</p>
                    </div>
                    <Switch checked={settings.headless} onCheckedChange={(checked) => setSettings((s: any) => ({ ...s, headless: checked }))} />
                </div>
            </Card>

            {/* Proxies */}
            <Card className="p-6 space-y-3">
                <div>
                    <h3 className="text-sm font-bold">{__('general.proxy_rotation')}<span className="text-xs font-normal text-muted-foreground">(Optional)</span></h3>
                    <p className="text-xs text-muted-foreground mt-0.5">One proxy per line. Format: <code className="text-slate-700 bg-slate-100 px-1 rounded">{__('general.http_user_pass_ip_port')}</code></p>
                </div>
                <Textarea
                    value={proxiesText}
                    onChange={e => setProxies(e.target.value)}
                    rows={5}
                    placeholder={"http://user:pass@192.168.1.1:8080\nhttp://user:pass@192.168.1.2:8080"}
                    className="font-mono text-xs resize-none"
                />
            </Card>

            <Button
                type="submit"
                disabled={saving}
                className="gap-2 px-5"
            >
                {saving ? <RefreshCw className="w-4 h-4 animate-spin" /> : saved ? <CheckCircle className="w-4 h-4 text-emerald-400" /> : null}
                {saved ? 'Saved!' : saving ? 'Saving...' : 'Save Settings'}
            </Button>
        </form>
    );
}

// ── Main Component ────────────────────────────────────────────────────────────
export default function GoogleMapsRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [activeTab, setActiveTab] = useState<'campaigns' | 'results' | 'settings'>('campaigns');

    // Campaigns
    const [campaigns, setCampaigns]       = useState<any[]>([]);
    const [showNewModal, setShowNewModal] = useState(false);
    const [runningIds, setRunningIds]     = useState<Set<string>>(new Set());
    const [campaignStats, setCampaignStats] = useState<Record<string, any>>({});

    // Results
    const [results, setResults]             = useState<any[]>([]);
    const [totalResults, setTotalResults]   = useState(0);
    const [resultsOffset, setResultsOffset] = useState(0);
    const [resultsLimit]                    = useState(50);
    const [filterCampaignId, setFilterCampaignId] = useState('');
    const [filterSearch, setFilterSearch]         = useState('');
    const [filterHasEmail, setFilterHasEmail]     = useState(false);
    const [filterHasPhone, setFilterHasPhone]     = useState(false);

    // Realtime
    const [logs, setLogs] = useState<any[]>([]);

    // Export
    const [exportingJobId, setExportingJobId]   = useState<string | null>(null);
    const [exportProgress, setExportProgress]   = useState<number | null>(null);
    const [exportFilePath, setExportFilePath]   = useState<string | null>(null);

    const addLog = (msg: string) => {
        setLogs(prev => [{ id: Math.random().toString(36), msg, time: new Date().toLocaleTimeString() }, ...prev].slice(0, 60));
    };

    // ── WebSocket broadcast handler ───────────────────────────────────────────
    const onBroadcast = useCallback((event: string, data: any) => {
        if (event === 'gm.result.extracted') {
            const r = data.result;
            setResults(prev => {
                const exists = prev.some(x => x.id === r.id);
                if (exists) return prev;
                return [r, ...prev].slice(0, resultsLimit);
            });
            setTotalResults(prev => prev + 1);
            addLog(`Found: ${r.company}${r.phone ? ` · ${r.phone}` : ''}${r.email ? ` · ${r.email}` : ''}`);
            // Refresh campaign stats
            if (data.campaignId) refreshStats(data.campaignId);
        }

        if (event === 'gm.result.crawling') {
            addLog(`Scanning website: ${data.company}...`);
        }

        if (event === 'gm.campaign.updated') {
            const { campaignId, status, stats } = data;
            setCampaigns(prev => prev.map(c => c.id === campaignId ? { ...c, status } : c));
            setRunningIds(prev => {
                const next = new Set(prev);
                if (status === 'running') next.add(campaignId);
                else next.delete(campaignId);
                return next;
            });
            if (stats) setCampaignStats(prev => ({ ...prev, [campaignId]: stats }));
            if (status === 'completed') { addLog('Extraction complete!'); fetchCampaigns(); }
            if (status === 'paused')    { addLog('Extraction paused.'); fetchCampaigns(); }
            if (status === 'failed')    { addLog(`Extraction error: ${data.error || 'Unknown'}`); fetchCampaigns(); }
        }

        if (event === 'gm.campaign.created') {
            setCampaigns(prev => [data.campaign, ...prev]);
            addLog(`New campaign created: "${data.campaign.name}"`);
        }

        if (event === 'gm.campaign.deleted') {
            setCampaigns(prev => prev.filter(c => c.id !== data.campaignId));
        }

        if (event === 'gm.export.progress') {
            setExportProgress(data.exported);
        }

        if (event === 'gm.export.completed') {
            setExportProgress(null);
            setExportFilePath(data.filePath);
            setExportingJobId(null);
            addLog(`Exported ${data.rowCount} rows to local folder.`);
        }

        if (event === 'gm.scraper.started') {
            addLog(`Starting search: "${data.keyword}" in ${data.location} (max ${data.maxLimit})`);
        }

        if (event === 'gm.scraper.error') {
            addLog(`Error: ${data.error}`);
        }
    }, [resultsLimit]);

    const { connected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('google-maps', onBroadcast);

    // ── Initial data fetch ────────────────────────────────────────────────────
    const fetchCampaigns = useCallback(async () => {
        try {
            const list: any[] = (await callRPC('gm.campaigns.list')) as any[];
            setCampaigns(list || []);
            const running = new Set<string>(list.filter(c => c.status === 'running').map(c => c.id));
            setRunningIds(running);
            const statsMap: Record<string, any> = {};
            list.forEach(c => { statsMap[c.id] = c.stats || {}; });
            setCampaignStats(statsMap);
        } catch (err) { console.error('fetchCampaigns:', err); }
    }, [callRPC]);

    const refreshStats = useCallback(async (campaignId: string) => {
        try {
            const stats = await callRPC('gm.campaign.stats', { campaignId });
            setCampaignStats(prev => ({ ...prev, [campaignId]: stats }));
        } catch (_) {}
    }, [callRPC]);

    const fetchResults = useCallback(async () => {
        try {
            const res: any = await callRPC('gm.results.list', {
                campaignId:  filterCampaignId || null,
                limit:       resultsLimit,
                offset:      resultsOffset,
                search:      filterSearch     || null,
                has_email:   filterHasEmail   || undefined,
                has_phone:   filterHasPhone   || undefined,
            });
            setResults(res.results || []);
            setTotalResults(res.total || 0);
        } catch (err) { console.error('fetchResults:', err); }
    }, [callRPC, filterCampaignId, resultsLimit, resultsOffset, filterSearch, filterHasEmail, filterHasPhone]);

    useEffect(() => {
        if (connected) {
            fetchCampaigns();
            addLog('Connected. Ready to extract leads from Google Maps.');
        }
    }, [connected]);

    useEffect(() => {
        if (connected) fetchResults();
    }, [connected, filterCampaignId, resultsOffset, filterSearch, filterHasEmail, filterHasPhone]);

    // ── Actions ───────────────────────────────────────────────────────────────
    const handleStart = async (campaignId: string) => {
        try {
            addLog('Launching browser and navigating to Google Maps...');
            await callRPC('gm.campaign.start', { campaignId });
            setRunningIds(prev => new Set([...prev, campaignId]));
            setCampaigns(prev => prev.map(c => c.id === campaignId ? { ...c, status: 'running' } : c));
        } catch (err: any) { alert(`Could not start: ${err.message}`); }
    };

    const handleStop = async (campaignId: string) => {
        try {
            await callRPC('gm.campaign.stop', { campaignId });
            setRunningIds(prev => { const n = new Set(prev); n.delete(campaignId); return n; });
            setCampaigns(prev => prev.map(c => c.id === campaignId ? { ...c, status: 'paused' } : c));
            addLog('Pausing extraction...');
        } catch (err: any) { alert(`Could not pause: ${err.message}`); }
    };

    const handleDelete = async (campaignId: string, name: string) => {
        if (!confirm(`Delete campaign "${name}" and all its results?`)) return;
        try {
            await callRPC('gm.campaign.delete', { campaignId });
            setCampaigns(prev => prev.filter(c => c.id !== campaignId));
            addLog(`Campaign "${name}" deleted.`);
        } catch (err: any) { alert(`Delete failed: ${err.message}`); }
    };

    const handleExport = async () => {
        try {
            const res: any = await callRPC('gm.results.export', { campaignId: filterCampaignId || null });
            setExportingJobId(res.jobId);
            setExportProgress(0);
            setExportFilePath(null);
            addLog('Preparing CSV export...');
        } catch (err: any) { alert(`Export failed: ${err.message}`); }
    };

    const handleCampaignCreated = (c: any) => {
        setCampaigns(prev => [{ ...c, stats: { total: 0, withEmail: 0, withPhone: 0, withWebsite: 0 } }, ...prev]);
        addLog(`Campaign "${c.name}" ready. Click Start to begin extraction.`);
    };

    // ── Not connected state ───────────────────────────────────────────────────
    if (!connected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">{__('general.connecting_to_runtime_1')}</p>
                    <p className="text-xs text-slate-400">{__('general.make_sure_the_musoftware_runtime_is_running_on_your_machine')}</p>
                </div>
            </div>
        );
    }

    // ── Totals across all campaigns ───────────────────────────────────────────
    const totalExtracted = Object.values(campaignStats).reduce((s: number, st: any) => s + (st?.total || 0), 0);
    const totalEmails    = Object.values(campaignStats).reduce((s: number, st: any) => s + (st?.withEmail || 0), 0);
    const totalPhones    = Object.values(campaignStats).reduce((s: number, st: any) => s + (st?.withPhone || 0), 0);

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans flex flex-col antialiased">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />

            {/* ── Topbar ── */}
            <header className="h-14 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center justify-between px-6 sticky top-0 z-30 shrink-0">
                <div className="flex items-center gap-5">
                    {/* Logo */}
                    <div className="flex items-center gap-2">
                        <div className="w-6 h-6 bg-gradient-to-tr from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center shadow-md shadow-emerald-500/25">
                            <MapPin className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-bold text-sm tracking-tight text-slate-900">{__('general.maps_lead_extractor')}</span>
                    </div>

                    <div className="h-4 w-px bg-slate-200" />

                    {/* Navigation */}
                    <nav className="flex items-center gap-1">
                        {[
                            { id: 'campaigns', label: 'Campaigns' },
                            { id: 'results',   label: 'Results' },
                            { id: 'settings',  label: 'Settings' },
                        ].map(tab => (
                            <Button
                                key={tab.id}
                                variant={activeTab === tab.id ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setActiveTab(tab.id as any)}
                            >
                                {tab.label}
                            </Button>
                        ))}
                    </nav>
                </div>

                {/* Runtime indicator */}
                <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-800">
                    <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    <span className="text-[10px] font-bold uppercase tracking-wider">Live</span>
                </div>
            </header>

            {/* ── Body ── */}
            <div className="flex-1 flex overflow-hidden">

                {/* ── Sidebar: Live Activity ── */}
                <aside className="w-64 border-r border-slate-200 bg-white hidden lg:flex flex-col shrink-0">
                    {/* Stats */}
                    <div className="p-4 border-b border-slate-100 grid grid-cols-3 gap-2">
                        {[
                            { label: 'Extracted', value: totalExtracted, color: 'text-slate-900' },
                            { label: 'Emails', value: totalEmails, color: 'text-emerald-700' },
                            { label: 'Phones', value: totalPhones, color: 'text-sky-700' },
                        ].map(s => (
                            <div key={s.label} className="text-center">
                                <p className={`text-base font-black ${s.color}`}>{s.value}</p>
                                <p className="text-[10px] text-slate-400 font-semibold mt-0.5">{s.label}</p>
                            </div>
                        ))}
                    </div>

                    {/* Live Feed */}
                    <div className="flex-1 flex flex-col min-h-0 p-4">
                        <div className="flex items-center gap-1.5 mb-3">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping" />
                            <h3 className="text-[10px] font-black uppercase tracking-wider text-slate-400">{__('general.live_activity')}</h3>
                        </div>
                        <div className="flex-1 overflow-y-auto space-y-2 scrollbar-thin">
                            {logs.length === 0 ? (
                                <div className="text-center py-16 flex flex-col items-center gap-2">
                                    <HelpCircle className="w-6 h-6 text-slate-300" />
                                    <span className="text-xs text-slate-400">{__('general.create_a_campaign_to_get_started')}</span>
                                </div>
                            ) : logs.map(l => (
                                <div key={l.id} className="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                    <p className="text-[11px] text-slate-700 leading-relaxed font-medium">{l.msg}</p>
                                    <span className="text-[10px] text-slate-400 font-mono mt-0.5 block">{l.time}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Active campaigns count */}
                    {runningIds.size > 0 && (
                        <div className="p-4 border-t border-slate-100 bg-emerald-50/50">
                            <div className="flex items-center gap-2">
                                <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                                <span className="text-xs font-bold text-emerald-800">{runningIds.size} Campaign{runningIds.size > 1 ? 's' : ''} Extracting</span>
                            </div>
                        </div>
                    )}
                </aside>

                {/* ── Main Content ── */}
                <main className="flex-1 overflow-y-auto p-8">

                    {/* ══ TAB: CAMPAIGNS ══ */}
                    {activeTab === 'campaigns' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300 max-w-5xl mx-auto">
                            {/* Header */}
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-700 bg-clip-text text-transparent">Campaigns</h1>
                                    <p className="text-xs text-slate-500 mt-1">{__('general.each_campaign_targets_a_keyword_location_and_extracts_matching_businesses')}</p>
                                </div>
                                <Button
                                    onClick={() => setShowNewModal(true)}
                                    className="gap-1.5"
                                >
                                    <Plus className="w-3.5 h-3.5" />{__('general.new_campaign')}</Button>
                            </div>

                            {/* Campaign Grid */}
                            {campaigns.length === 0 ? (
                                <Card className="py-28 text-center border-dashed">
                                    <div className="w-14 h-14 bg-gradient-to-br from-emerald-100 to-teal-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <MapPin className="w-7 h-7 text-emerald-600" />
                                    </div>
                                    <h3 className="text-sm font-bold text-slate-900">{__('general.no_campaigns_yet')}</h3>
                                    <p className="text-xs text-slate-500 mt-1.5 max-w-xs mx-auto">{__('general.create_your_first_campaign_to_start_extracting_businesses_from_google_maps')}</p>
                                    <Button
                                        onClick={() => setShowNewModal(true)}
                                        className="mt-6"
                                    >{__('general.create_first_campaign')}</Button>
                                </Card>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    {campaigns.map(camp => {
                                        const stats    = campaignStats[camp.id] || {};
                                        const isRunning = runningIds.has(camp.id);

                                        return (
                                            <Card key={camp.id} className="overflow-hidden hover:shadow-md transition-all relative group border-slate-200/80">
                                                {/* Running progress bar */}
                                                {isRunning && (
                                                    <div className="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-emerald-400 via-teal-400 to-sky-400 animate-pulse" />
                                                )}

                                                <CardContent className="p-5">
                                                    {/* Card header */}
                                                    <div className="flex items-start justify-between mb-3">
                                                        <div className="min-w-0 flex-1 pr-3">
                                                            <h3 className="font-bold text-slate-900 text-sm truncate group-hover:text-emerald-700 transition-colors">{camp.name}</h3>
                                                            <div className="flex items-center gap-1.5 mt-0.5">
                                                                <Search className="w-3 h-3 text-slate-400 shrink-0" />
                                                                <span className="text-[11px] text-slate-500 font-mono truncate">{camp.keyword}</span>
                                                                <span className="text-slate-300">·</span>
                                                                <MapPin className="w-3 h-3 text-slate-400 shrink-0" />
                                                                <span className="text-[11px] text-slate-500 truncate">{camp.location}</span>
                                                            </div>
                                                        </div>
                                                        <Badge variant="outline" className={`shrink-0 ${statusStyle(camp.status)}`}>
                                                            {statusLabel(camp.status)}
                                                        </Badge>
                                                    </div>

                                                    {/* Stats row */}
                                                    <div className="grid grid-cols-4 gap-2 my-4">
                                                        {[
                                                            { label: 'Found',    value: stats.total || 0,       icon: Building2, color: 'text-slate-900' },
                                                            { label: 'Phones',   value: stats.withPhone || 0,   icon: Phone,     color: 'text-sky-700' },
                                                            { label: 'Websites', value: stats.withWebsite || 0, icon: Globe,     color: 'text-violet-700' },
                                                            { label: 'Emails',   value: stats.withEmail || 0,   icon: Mail,      color: 'text-emerald-700' },
                                                        ].map(m => (
                                                            <div key={m.label} className="text-center bg-slate-50 rounded-xl p-2 border border-slate-100">
                                                                <m.icon className={`w-3 h-3 mx-auto mb-1 ${m.color}`} />
                                                                <p className={`text-sm font-black ${m.color}`}>{m.value}</p>
                                                                <p className="text-[9px] text-slate-400 font-semibold">{m.label}</p>
                                                            </div>
                                                        ))}
                                                    </div>

                                                    {/* Badges */}
                                                    <div className="flex items-center gap-1.5 mb-4">
                                                        <Badge variant="secondary" className="font-mono text-[10px]">max {camp.limit_count}</Badge>
                                                        {camp.deep_crawl && (
                                                            <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-100">{__('general.email_discovery')}</Badge>
                                                        )}
                                                    </div>

                                                    {/* Actions */}
                                                    <div className="flex items-center justify-between border-t border-slate-100 pt-3">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => { setFilterCampaignId(camp.id); setActiveTab('results'); }}
                                                            className="text-xs h-8 text-slate-500 hover:text-slate-900"
                                                        >{__('general.view_results')}<ChevronRight className="w-3.5 h-3.5 ml-1" />
                                                        </Button>

                                                        <div className="flex items-center gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => handleDelete(camp.id, camp.name)}
                                                                className="h-8 w-8 text-slate-300 hover:text-rose-500 hover:bg-rose-50"
                                                                title={__('general.delete_campaign')}
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </Button>

                                                            {isRunning ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => handleStop(camp.id)}
                                                                    className="h-8 bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-100 text-[11px]"
                                                                >
                                                                    <Square className="w-3 h-3 fill-rose-600 mr-1" /> Pause
                                                                </Button>
                                                            ) : (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => handleStart(camp.id)}
                                                                    className="h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-100 text-[11px]"
                                                                >
                                                                    <Play className="w-3 h-3 fill-emerald-600 mr-1" />
                                                                    {camp.status === 'paused' || camp.status === 'completed' || camp.status === 'failed' ? 'Re-run' : 'Start'}
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ══ TAB: RESULTS ══ */}
                    {activeTab === 'results' && (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300 max-w-6xl mx-auto">
                            {/* Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-700 bg-clip-text text-transparent">Results</h1>
                                    <p className="text-xs text-slate-500 mt-1">{totalResults.toLocaleString()} businesses extracted across all campaigns.</p>
                                </div>
                                <Button
                                    variant="outline"
                                    onClick={handleExport}
                                    disabled={!!exportingJobId || totalResults === 0}
                                    className="gap-1.5 shadow-sm"
                                >
                                    <Download className="w-3.5 h-3.5" />
                                    {exportingJobId ? `Exporting (${exportProgress || 0})...` : 'Export CSV'}
                                </Button>
                            </div>

                            {/* Export notification */}
                            {exportFilePath && (
                                <div className="p-3.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs flex items-start gap-2.5">
                                    <CheckCircle className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                    <div>
                                        <p className="font-bold">{__('general.file_saved_to_your_computer')}</p>
                                        <p className="text-[11px] opacity-90 mt-0.5 font-mono select-all break-all">{exportFilePath}</p>
                                    </div>
                                </div>
                            )}

                            {/* Filters */}
                            <div className="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                {/* Search */}
                                <div className="relative flex-1 w-full">
                                    <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                    <Input
                                        placeholder={__('general.search_by_name_address_category_email')}
                                        value={filterSearch}
                                        onChange={e => { setFilterSearch(e.target.value); setResultsOffset(0); }}
                                        className="pl-8"
                                    />
                                </div>

                                {/* Campaign filter */}
                                <select
                                    value={filterCampaignId}
                                    onChange={e => { setFilterCampaignId(e.target.value); setResultsOffset(0); }}
                                    className="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-slate-900 w-full sm:w-44"
                                >
                                    <option value="">{__('general.all_campaigns')}</option>
                                    {campaigns.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>

                                {/* Quick filters */}
                                <div className="flex items-center gap-2 shrink-0">
                                    <Button
                                        type="button"
                                        variant={filterHasEmail ? 'default' : 'outline'}
                                        onClick={() => { setFilterHasEmail(v => !v); setResultsOffset(0); }}
                                        className={`h-8 px-2.5 text-[11px] gap-1 ${filterHasEmail ? 'bg-emerald-950 hover:bg-emerald-900 border-emerald-950 text-white' : ''}`}
                                    >
                                        <Mail className="w-3 h-3" /> Email
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={filterHasPhone ? 'default' : 'outline'}
                                        onClick={() => { setFilterHasPhone(v => !v); setResultsOffset(0); }}
                                        className={`h-8 px-2.5 text-[11px] gap-1 ${filterHasPhone ? 'bg-sky-950 hover:bg-sky-900 border-sky-950 text-white' : ''}`}
                                    >
                                        <Phone className="w-3 h-3" /> Phone
                                    </Button>
                                </div>
                            </div>

                            {/* Results table */}
                            {results.length === 0 ? (
                                <Card className="py-20 text-center border-dashed">
                                    <Database className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                    <p className="text-sm font-bold text-slate-600">{__('general.no_results_yet')}</p>
                                    <p className="text-xs text-slate-400 mt-1">{__('general.run_a_campaign_to_extract_businesses_here')}</p>
                                </Card>
                            ) : (
                                <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-slate-100 bg-slate-50/80">
                                                    {['Business', 'Category', 'Rating', 'Phone', 'Email', 'Website', 'Address', ''].map(h => (
                                                        <th key={h} className="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">{h}</th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {results.map(r => (
                                                    <tr key={r.id} className="hover:bg-slate-50/50 transition-colors group">
                                                        <td className="px-4 py-3 min-w-[180px]">
                                                            <p className="text-xs font-bold text-slate-900 truncate max-w-[200px]">{r.company || '—'}</p>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className="text-[11px] text-slate-500 truncate block max-w-[130px]">{r.category || '—'}</span>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {r.rating ? (
                                                                <span className="flex items-center gap-1 text-xs text-amber-600 font-semibold">
                                                                    <Star className="w-3 h-3 fill-amber-400" />
                                                                    {r.rating}
                                                                    {r.review_count && <span className="text-slate-400 font-normal">({r.review_count})</span>}
                                                                </span>
                                                            ) : <span className="text-slate-300 text-xs">—</span>}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {r.phone ? (
                                                                <a href={`tel:${r.phone}`} className="text-xs text-sky-700 font-mono hover:text-sky-900 transition-colors">{r.phone}</a>
                                                            ) : <span className="text-slate-300 text-xs">—</span>}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {r.email ? (
                                                                <a href={`mailto:${r.email}`} className="text-xs text-emerald-700 font-mono hover:text-emerald-900 transition-colors truncate block max-w-[180px]">{r.email}</a>
                                                            ) : <span className="text-slate-300 text-xs">—</span>}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {r.website ? (
                                                                <a href={r.website} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 text-xs text-violet-700 hover:text-violet-900 transition-colors">
                                                                    <Globe className="w-3 h-3 shrink-0" />
                                                                    <span className="truncate max-w-[120px]">{r.website.replace(/^https?:\/\/(www\.)?/, '')}</span>
                                                                </a>
                                                            ) : <span className="text-slate-300 text-xs">—</span>}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className="text-xs text-slate-500 truncate block max-w-[180px]">{r.address || '—'}</span>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {r.maps_url && (
                                                                <a href={r.maps_url} target="_blank" rel="noopener noreferrer" className="p-1.5 rounded-lg text-slate-300 hover:text-emerald-600 hover:bg-emerald-50 transition-all block">
                                                                    <ExternalLink className="w-3.5 h-3.5" />
                                                                </a>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Pagination */}
                                    {totalResults > resultsLimit && (
                                        <div className="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                                            <span className="text-xs text-slate-500">
                                                Showing {resultsOffset + 1}–{Math.min(resultsOffset + resultsLimit, totalResults)} of {totalResults.toLocaleString()}
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={resultsOffset === 0}
                                                    onClick={() => setResultsOffset(v => Math.max(0, v - resultsLimit))}
                                                >Previous</Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={resultsOffset + resultsLimit >= totalResults}
                                                    onClick={() => setResultsOffset(v => v + resultsLimit)}
                                                >Next</Button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ══ TAB: SETTINGS ══ */}
                    {activeTab === 'settings' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <SettingsPanel callRPC={callRPC} />
                        </div>
                    )}

                </main>
            </div>

            {/* ── New Campaign Modal ── */}
            {showNewModal && (
                <NewCampaignModal
                    onClose={() => setShowNewModal(false)}
                    onCreated={handleCampaignCreated}
                    callRPC={callRPC}
                />
            )}
        </div>
    );
}
