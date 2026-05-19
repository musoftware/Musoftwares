import React, { useState, useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, LayoutDashboard, Settings, Plus, BarChart3, Database,
    Search, Loader2, CheckCircle2, AlertCircle, Wifi, WifiOff, Users, Link as LinkIcon
} from 'lucide-react';

interface Props {
    tool: { slug: string; title: string; icon_url: string | null; short_description: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort: number;
    pluginSlug: string;
}

export default function LeadIntelligenceRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;
    const wsUrl = `ws://127.0.0.1:${runtimePort}/ws`;

    const [rtStatus, setRtStatus] = useState<'checking' | 'ok' | 'offline'>('checking');
    const [activeTab, setActiveTab] = useState<'dashboard' | 'campaigns' | 'new_campaign' | 'settings'>('dashboard');
    
    // Data State
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [recentLeads, setRecentLeads] = useState<any[]>([]);
    const wsRef = useRef<WebSocket | null>(null);

    // Form State
    const [formName, setFormName] = useState('');
    const [formKeyword, setFormKeyword] = useState('');
    const [formCountry, setFormCountry] = useState('US');
    const [formSources, setFormSources] = useState<string[]>(['google_maps']);

    useEffect(() => {
        // Check runtime health
        fetch(`${base}/health`).then(r => {
            setRtStatus(r.ok ? 'ok' : 'offline');
            if (r.ok) {
                connectWs();
                fetchCampaigns();
            }
        }).catch(() => setRtStatus('offline'));

        return () => wsRef.current?.close();
    }, [base]);

    const connectWs = () => {
        const ws = new WebSocket(wsUrl);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                if (msg.event === 'prospecting.campaign.created' || msg.event === 'prospecting.campaign.updated') {
                    fetchCampaigns();
                }
                if (msg.event === 'prospecting.lead.extracted.ui') {
                    const lead = msg.data.lead;
                    setRecentLeads(prev => [lead, ...prev].slice(0, 10)); // Keep last 10
                    fetchCampaigns(); // Update counts
                }
            } catch (e) { }
        };
        wsRef.current = ws;
    };

    const fetchCampaigns = async () => {
        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    eventName: 'prospecting.campaigns.list',
                    payload: {}
                })
            });
            const data = await res.json();
            if (data.success) {
                setCampaigns(data.data);
            }
        } catch (e) {
            console.error('Failed to fetch campaigns', e);
        }
    };

    const handleCreateCampaign = async () => {
        if (!formName || !formKeyword) return;
        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    eventName: 'prospecting.campaign.create',
                    payload: {
                        name: formName,
                        keyword: formKeyword,
                        country: formCountry,
                        sources: formSources,
                        daily_limit: 100
                    }
                })
            });
            const data = await res.json();
            if (data.success) {
                setActiveTab('campaigns');
                setFormName('');
                setFormKeyword('');
                fetchCampaigns();
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleStartCampaign = async (campaignId: string) => {
        try {
            await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    eventName: 'prospecting.campaign.start',
                    payload: { campaignId }
                })
            });
        } catch (e) {
            console.error(e);
        }
    };

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col h-screen">
                {/* Top Navbar */}
                <div className="flex items-center justify-between mb-8 pb-4 border-b border-slate-200 shrink-0">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600 transition-colors">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 tracking-tight">Lead Intelligence Engine</h1>
                            <p className="text-xs text-slate-500 font-medium">{subscription.plan_name} Edition</p>
                        </div>
                    </div>
                    <div className={`flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full border shadow-sm ${
                        rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                        rtStatus === 'offline' ? 'bg-red-50 text-red-600 border-red-200' :
                                                 'bg-slate-50 text-slate-500 border-slate-200'
                    }`}>
                        {rtStatus === 'ok' ? <Wifi className="h-4 w-4" /> : <WifiOff className="h-4 w-4" />}
                        {rtStatus === 'ok' ? 'Local Engine Connected' : rtStatus === 'offline' ? 'Engine Offline' : 'Connecting...'}
                    </div>
                </div>

                <div className="flex gap-8 flex-1 min-h-0">
                    {/* Left Sidebar */}
                    <div className="w-56 shrink-0 space-y-1">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 px-3">Menu</p>
                        {[
                            { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
                            { id: 'campaigns', label: 'Campaigns', icon: Database },
                            { id: 'new_campaign', label: 'New Campaign', icon: Plus },
                            { id: 'settings', label: 'Settings', icon: Settings },
                        ].map(nav => (
                            <button
                                key={nav.id}
                                onClick={() => setActiveTab(nav.id as any)}
                                className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                                    activeTab === nav.id
                                        ? 'bg-slate-900 text-white shadow-md'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                }`}
                            >
                                <nav.icon className="h-4 w-4 opacity-80" />
                                {nav.label}
                            </button>
                        ))}
                    </div>

                    {/* Main Content Area */}
                    <div className="flex-1 bg-white border border-slate-200 rounded-2xl shadow-sm p-8 overflow-y-auto">
                        
                        {/* TAB: Dashboard */}
                        {activeTab === 'dashboard' && (
                            <div className="space-y-8 animate-in fade-in duration-300">
                                <div>
                                    <h2 className="text-2xl font-bold text-slate-900">Overview</h2>
                                    <p className="text-sm text-slate-500 mt-1">Real-time local extraction metrics.</p>
                                </div>
                                <div className="grid grid-cols-3 gap-6">
                                    <div className="bg-slate-50 rounded-xl p-5 border border-slate-100">
                                        <p className="text-sm font-semibold text-slate-500">Active Campaigns</p>
                                        <p className="text-3xl font-black text-slate-900 mt-2">{campaigns.filter(c => c.status === 'running').length}</p>
                                    </div>
                                    <div className="bg-slate-50 rounded-xl p-5 border border-slate-100">
                                        <p className="text-sm font-semibold text-slate-500">Total Leads</p>
                                        <p className="text-3xl font-black text-indigo-600 mt-2">
                                            {campaigns.reduce((sum, c) => sum + (c.extracted_count || 0), 0)}
                                        </p>
                                    </div>
                                    <div className="bg-slate-50 rounded-xl p-5 border border-slate-100">
                                        <p className="text-sm font-semibold text-slate-500">Database Size</p>
                                        <p className="text-3xl font-black text-slate-900 mt-2">Local</p>
                                    </div>
                                </div>

                                <div>
                                    <h3 className="text-lg font-bold text-slate-900 mb-4">Live Activity</h3>
                                    {recentLeads.length === 0 ? (
                                        <div className="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl">
                                            <p className="text-slate-500">No leads extracted recently. Start a campaign!</p>
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            {recentLeads.map((lead, i) => (
                                                <div key={i} className="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg shadow-sm">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-8 h-8 rounded bg-indigo-50 flex items-center justify-center text-indigo-600">
                                                            <Users className="w-4 h-4" />
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-bold text-slate-900">{lead.name}</p>
                                                            <p className="text-xs text-slate-500">{lead.email}</p>
                                                        </div>
                                                    </div>
                                                    <span className="text-xs font-semibold bg-slate-100 text-slate-600 px-2 py-1 rounded">
                                                        {lead.source}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* TAB: Campaigns */}
                        {activeTab === 'campaigns' && (
                            <div className="space-y-6 animate-in fade-in duration-300">
                                <div className="flex justify-between items-center">
                                    <h2 className="text-2xl font-bold text-slate-900">Campaigns</h2>
                                    <Button onClick={() => setActiveTab('new_campaign')} className="bg-slate-900 text-white hover:bg-slate-800">
                                        <Plus className="w-4 h-4 mr-2" /> New Campaign
                                    </Button>
                                </div>
                                <div className="space-y-4">
                                    {campaigns.length === 0 && (
                                        <p className="text-slate-500 py-4">No campaigns found. Create one to start extracting.</p>
                                    )}
                                    {campaigns.map(c => (
                                        <div key={c.id} className="flex items-center justify-between p-5 bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow transition-all">
                                            <div>
                                                <div className="flex items-center gap-3">
                                                    <h3 className="text-base font-bold text-slate-900">{c.name}</h3>
                                                    <span className={`text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full ${
                                                        c.status === 'running' ? 'bg-emerald-100 text-emerald-700' :
                                                        c.status === 'completed' ? 'bg-indigo-100 text-indigo-700' :
                                                        'bg-slate-100 text-slate-600'
                                                    }`}>
                                                        {c.status}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-slate-500 mt-1">Keyword: <span className="font-semibold text-slate-700">{c.keyword}</span> • {c.extracted_count} Leads</p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                {c.status === 'draft' && (
                                                    <Button size="sm" onClick={() => handleStartCampaign(c.id)} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                                        <Play className="w-3.5 h-3.5 mr-1.5" /> Start
                                                    </Button>
                                                )}
                                                <Button size="sm" variant="outline" className="text-slate-600">
                                                    View Leads
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* TAB: New Campaign */}
                        {activeTab === 'new_campaign' && (
                            <div className="max-w-2xl space-y-8 animate-in fade-in duration-300">
                                <div>
                                    <h2 className="text-2xl font-bold text-slate-900">Create Extraction Campaign</h2>
                                    <p className="text-sm text-slate-500 mt-1">Configure sources and keywords for the local worker.</p>
                                </div>
                                <div className="space-y-5 bg-slate-50 p-6 rounded-xl border border-slate-200">
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1">Campaign Name</label>
                                        <input value={formName} onChange={e => setFormName(e.target.value)} type="text" placeholder="e.g. Dubai Realtors" className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1">Target Keyword</label>
                                        <input value={formKeyword} onChange={e => setFormKeyword(e.target.value)} type="text" placeholder="e.g. Real Estate Agency" className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1">Sources</label>
                                        <div className="flex gap-3">
                                            {['google_maps', 'linkedin', 'telegram'].map(src => (
                                                <label key={src} className={`flex items-center gap-2 px-4 py-3 rounded-lg border cursor-pointer transition-colors ${formSources.includes(src) ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'}`}>
                                                    <input type="checkbox" className="hidden" checked={formSources.includes(src)} onChange={() => {
                                                        setFormSources(prev => prev.includes(src) ? prev.filter(s => s !== src) : [...prev, src]);
                                                    }} />
                                                    <span className="text-sm font-bold capitalize">{src.replace('_', ' ')}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                    <Button onClick={handleCreateCampaign} disabled={!formName || !formKeyword || formSources.length === 0} className="w-full h-12 bg-slate-900 hover:bg-slate-800 text-white font-bold text-base mt-4">
                                        Save & Create Campaign
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* TAB: Settings */}
                        {activeTab === 'settings' && (
                            <div className="space-y-6 animate-in fade-in duration-300">
                                <h2 className="text-2xl font-bold text-slate-900">Settings</h2>
                                <p className="text-slate-500">Local proxy configuration, deduplication rules, and general preferences.</p>
                                <div className="bg-amber-50 border border-amber-200 p-4 rounded-lg flex items-start gap-3 text-amber-800">
                                    <AlertCircle className="w-5 h-5 shrink-0 mt-0.5" />
                                    <div>
                                        <p className="font-bold">Settings are stored locally</p>
                                        <p className="text-sm opacity-90">All configuration applied here will be saved to your local `prospecting.db` via the runtime agent.</p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}
