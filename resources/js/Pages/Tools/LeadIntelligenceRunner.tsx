import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Loader2, AlertCircle, Wifi, WifiOff, Users, Mail,
    Play, Pause, Plus, CheckCircle2, XCircle, Search, Inbox,
    Target, Activity, Database, Sparkles, X, ChevronRight
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function LeadIntelligenceRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [activeTab, setActiveTab] = useState<'campaigns'|'leads'|'inboxes'>('campaigns');
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    const wsRef = useRef<WebSocket|null>(null);

    // State
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [leads, setLeads] = useState<any[]>([]);
    const [inboxes, setInboxes] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(false);

    // New Campaign Form
    const [showNewCampaign, setShowNewCampaign] = useState(false);
    const [newCampaign, setNewCampaign] = useState({ name: '', keyword: '', sources: ['linkedin'] });

    useEffect(() => {
        fetch(`${base}/health`).then(r => setRtStatus(r.ok ? 'ok' : 'offline')).catch(() => setRtStatus('offline'));
    }, [base]);

    useEffect(() => {
        if (rtStatus === 'ok') {
            connectWs();
            loadCampaigns();
        }
        return () => {
            if (wsRef.current) wsRef.current.close();
        };
    }, [rtStatus]);

    const connectWs = () => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort + 1}/ws`);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                if (msg.event === 'prospecting.campaign.updated' || msg.event === 'prospecting.campaign.created') {
                    loadCampaigns();
                }
                if (msg.event === 'prospecting.lead.extracted.ui') {
                    setLeads(prev => [msg.data.lead, ...prev]);
                    loadCampaigns(); // Update counts
                }
            } catch {}
        };
        wsRef.current = ws;
    };

    const emitToRuntime = async (event: string, payload: any = {}) => {
        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { action: event, payload } }),
            });
            const data = await res.json();
            const taskId = data.taskId;
            
            // Background tasks return immediately with success
            if (['prospecting.campaign.start', 'prospecting.campaign.stop'].includes(event)) {
                return { success: true };
            }

            // Sync tasks poll for completion
            for (let i = 0; i < 20; i++) {
                await new Promise(r => setTimeout(r, 250));
                const pollRes = await fetch(`${base}/tasks/${taskId}`);
                const pollData = await pollRes.json();
                if (pollData.status === 'done') return { success: true, data: pollData.result };
                if (pollData.status === 'failed') return { success: false, error: pollData.error };
            }
            return { success: false, error: 'Task timeout' };
        } catch (e) {
            console.error(e);
            return { success: false, error: 'Cannot reach runtime' };
        }
    };

    const loadCampaigns = async () => {
        setIsLoading(true);
        const res = await emitToRuntime('prospecting.campaigns.list');
        if (res && res.success) setCampaigns(res.data);
        setIsLoading(false);
    };

    const loadLeads = async (campaignId: string) => {
        setIsLoading(true);
        const res = await emitToRuntime('prospecting.leads.list', { campaignId });
        if (res && res.success) setLeads(res.data);
        setIsLoading(false);
    };

    const loadInboxes = async () => {
        setIsLoading(true);
        const res = await emitToRuntime('prospecting.inboxes.list');
        if (res && res.success) setInboxes(res.data);
        setIsLoading(false);
    };

    useEffect(() => {
        if (activeTab === 'inboxes') loadInboxes();
    }, [activeTab]);

    const handleCreateCampaign = async () => {
        if (!newCampaign.name || !newCampaign.keyword) return;
        setIsLoading(true);
        await emitToRuntime('prospecting.campaign.create', { ...newCampaign, daily_limit: 100 });
        setShowNewCampaign(false);
        setNewCampaign({ name: '', keyword: '', sources: ['linkedin'] });
        await loadCampaigns();
        setIsLoading(false);
    };

    const toggleCampaign = async (campaign: any) => {
        setIsLoading(true);
        if (campaign.status === 'running') {
            await emitToRuntime('prospecting.campaign.stop', { campaignId: campaign.id });
        } else {
            await emitToRuntime('prospecting.campaign.start', { campaignId: campaign.id });
        }
        await loadCampaigns();
        setIsLoading(false);
    };

    // Helper for initials
    const getInitials = (name: string) => {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
        return name.substring(0, 2).toUpperCase();
    };

    const renderCampaigns = () => (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Header & Create Button */}
            <div className="flex justify-between items-center bg-white/50 backdrop-blur-xl p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">Active Campaigns</h2>
                    <p className="text-sm text-slate-500 mt-1">Manage your automated AI prospecting engines.</p>
                </div>
                <Button 
                    onClick={() => setShowNewCampaign(true)} 
                    className="bg-indigo-600 hover:bg-indigo-500 text-white gap-2 shadow-lg shadow-indigo-600/20 rounded-xl h-12 px-6 transition-all hover:scale-105 active:scale-95"
                >
                    <Plus className="h-5 w-5" /> Launch New Campaign
                </Button>
            </div>

            {/* Create Campaign Modal/Inline */}
            {showNewCampaign && (
                <div className="bg-white p-8 rounded-2xl border border-indigo-100 shadow-xl shadow-indigo-900/5 relative animate-in zoom-in-95 duration-300">
                    <button 
                        onClick={() => setShowNewCampaign(false)}
                        className="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors bg-slate-50 p-2 rounded-full"
                    >
                        <X className="h-4 w-4" />
                    </button>
                    <div className="flex items-center gap-4 mb-6">
                        <div className="h-12 w-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <Target className="h-6 w-6" />
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900">Define Target Persona</h3>
                            <p className="text-sm text-slate-500">Set the parameters for your AI agent to hunt leads.</p>
                        </div>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">Campaign Name</label>
                            <input type="text" 
                                className="w-full bg-slate-50 border-0 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 rounded-xl px-4 py-3 text-sm text-slate-900 shadow-inner transition-all" 
                                value={newCampaign.name} onChange={e => setNewCampaign({...newCampaign, name: e.target.value})} 
                                placeholder="e.g. Q3 SaaS Founders in NYC" />
                        </div>
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">Target Keyword / Job Title</label>
                            <input type="text" 
                                className="w-full bg-slate-50 border-0 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 rounded-xl px-4 py-3 text-sm text-slate-900 shadow-inner transition-all" 
                                value={newCampaign.keyword} onChange={e => setNewCampaign({...newCampaign, keyword: e.target.value})} 
                                placeholder="e.g. CEO OR Founder OR Owner" />
                        </div>
                    </div>
                    <div className="mt-8 flex justify-end gap-3">
                        <Button variant="ghost" className="h-11 rounded-xl px-6" onClick={() => setShowNewCampaign(false)}>Cancel</Button>
                        <Button 
                            onClick={handleCreateCampaign} 
                            disabled={isLoading || !newCampaign.name || !newCampaign.keyword} 
                            className="bg-indigo-600 hover:bg-indigo-700 text-white h-11 rounded-xl px-8 shadow-md shadow-indigo-600/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:hover:scale-100 gap-2"
                        >
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Sparkles className="h-4 w-4" />}
                            Create & Dispatch Agent
                        </Button>
                    </div>
                </div>
            )}

            {/* Campaign Grid */}
            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {campaigns.length === 0 && !showNewCampaign && (
                    <div className="col-span-full text-center py-20 bg-white/50 backdrop-blur-sm rounded-2xl border border-dashed border-slate-300">
                        <div className="h-20 w-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <Target className="h-10 w-10 text-indigo-300" />
                        </div>
                        <h3 className="text-lg font-bold text-slate-900">No campaigns running</h3>
                        <p className="text-slate-500 mt-2 max-w-sm mx-auto">Dispatch your first AI prospecting agent to start discovering and enriching leads automatically.</p>
                        <Button onClick={() => setShowNewCampaign(true)} className="mt-6 bg-white text-indigo-600 border border-indigo-200 hover:bg-indigo-50 hover:border-indigo-300 rounded-xl">
                            Create Campaign
                        </Button>
                    </div>
                )}
                {campaigns.map(c => (
                    <div key={c.id} className="group bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:border-indigo-300/50 transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div className="flex justify-between items-start mb-4">
                                <div className="flex items-center gap-3">
                                    <div className={`h-10 w-10 rounded-full flex items-center justify-center border-2 ${c.status === 'running' ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-slate-50 border-slate-100 text-slate-400'}`}>
                                        <Activity className={`h-5 w-5 ${c.status === 'running' ? 'animate-pulse' : ''}`} />
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-slate-900 text-lg group-hover:text-indigo-600 transition-colors">{c.name}</h3>
                                        <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5 font-medium">
                                            <Search className="h-3.5 w-3.5" /> {c.keyword}
                                        </p>
                                    </div>
                                </div>
                                <span className={`px-3 py-1 text-[11px] font-black rounded-full uppercase tracking-widest border ${
                                    c.status === 'running' ? 'bg-emerald-50 text-emerald-600 border-emerald-200 shadow-sm shadow-emerald-500/10' : 'bg-slate-50 text-slate-500 border-slate-200'
                                }`}>
                                    {c.status}
                                </span>
                            </div>
                            
                            <div className="grid grid-cols-2 gap-4 text-center mt-6">
                                <div className="bg-slate-50/50 p-4 rounded-xl border border-slate-100 group-hover:bg-indigo-50/30 group-hover:border-indigo-100 transition-colors">
                                    <p className="text-4xl font-black text-indigo-600 tracking-tight">{c.extracted_count}</p>
                                    <p className="text-xs text-slate-500 font-semibold uppercase mt-1">Leads Extracted</p>
                                </div>
                                <div className="bg-slate-50/50 p-4 rounded-xl border border-slate-100 group-hover:bg-slate-50 transition-colors">
                                    <p className="text-4xl font-black text-slate-700 tracking-tight">{c.daily_limit}</p>
                                    <p className="text-xs text-slate-500 font-semibold uppercase mt-1">Daily Limit</p>
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 flex gap-3">
                            <Button 
                                onClick={() => toggleCampaign(c)} 
                                variant={c.status === 'running' ? 'outline' : 'default'}
                                className={`flex-1 gap-2 rounded-xl h-11 font-semibold transition-all hover:scale-[1.02] active:scale-95 ${
                                    c.status !== 'running' 
                                        ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/20 border-0' 
                                        : 'border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-slate-900'
                                }`}
                            >
                                {c.status === 'running' ? <><Pause className="h-4 w-4 fill-current"/> Pause Agent</> : <><Play className="h-4 w-4 fill-current"/> Start Agent</>}
                            </Button>
                            <Button 
                                variant="outline" 
                                className="flex-none w-11 h-11 p-0 rounded-xl border-slate-200 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 transition-colors" 
                                onClick={() => { setActiveTab('leads'); loadLeads(c.id); }}
                            >
                                <ChevronRight className="h-5 w-5" />
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );

    const renderLeads = () => (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="flex justify-between items-center bg-white/50 backdrop-blur-xl p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">Lead Database</h2>
                    <p className="text-sm text-slate-500 mt-1">Enriched prospects across all active campaigns.</p>
                </div>
                <Button variant="outline" onClick={() => setActiveTab('campaigns')} className="rounded-xl bg-white shadow-sm border-slate-200 text-slate-700 hover:bg-slate-50">
                    <ArrowLeft className="h-4 w-4 mr-2" /> Back to Campaigns
                </Button>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
                {isLoading && (
                    <div className="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-10 flex items-center justify-center">
                        <Loader2 className="h-8 w-8 text-indigo-600 animate-spin" />
                    </div>
                )}
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-slate-50/80 text-slate-500 text-xs uppercase font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th className="px-6 py-4">Prospect</th>
                                <th className="px-6 py-4">Position & Company</th>
                                <th className="px-6 py-4">Contact Detail</th>
                                <th className="px-6 py-4">AI Score</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {leads.length === 0 && !isLoading && (
                                <tr>
                                    <td colSpan={4} className="px-6 py-16 text-center">
                                        <Database className="h-10 w-10 text-slate-300 mx-auto mb-3" />
                                        <p className="text-slate-500 font-medium">No leads extracted yet.</p>
                                        <p className="text-xs text-slate-400 mt-1">Ensure your campaigns are running.</p>
                                    </td>
                                </tr>
                            )}
                            {leads.map(lead => (
                                <tr key={lead.id} className="hover:bg-slate-50/80 transition-colors group">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 border border-white shadow-sm flex items-center justify-center text-indigo-700 font-bold text-xs shrink-0 group-hover:scale-105 transition-transform">
                                                {getInitials(lead.name)}
                                            </div>
                                            <div>
                                                <div className="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{lead.name || 'Unknown Prospect'}</div>
                                                <div className="text-[11px] font-medium text-slate-400 mt-0.5 flex items-center gap-1">
                                                    <span className="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                                    <span className="capitalize">{lead.source}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="font-semibold text-slate-800">{lead.title || '—'}</div>
                                        <div className="text-xs font-medium text-slate-500 mt-0.5 flex items-center gap-1.5">
                                            {lead.company || '—'}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="font-semibold text-slate-900">{lead.email || '—'}</div>
                                        {lead.email && (
                                            <div className="flex items-center gap-1.5 mt-1.5">
                                                {lead.email_status === 'valid' ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold uppercase tracking-wider">
                                                        <XCircle className="h-2.5 w-2.5" /> Unknown
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="w-20 bg-slate-100 rounded-full h-2.5 overflow-hidden shadow-inner">
                                                <div className={`h-full rounded-full transition-all duration-1000 ${lead.lead_score >= 70 ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : lead.lead_score >= 40 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-red-400 to-red-500'}`} style={{ width: `${Math.max(10, lead.lead_score)}%` }}></div>
                                            </div>
                                            <span className="text-xs font-black text-slate-700 w-6">{lead.lead_score}</span>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );

    const renderInboxes = () => (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="flex justify-between items-center bg-white/50 backdrop-blur-xl p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">Email Infrastructure</h2>
                    <p className="text-sm text-slate-500 mt-1">Connect local SMTP accounts to bypass IP bans.</p>
                </div>
                <Button 
                    onClick={() => alert('Inbox connection wizard coming in the next update!')} 
                    className="bg-slate-900 hover:bg-slate-800 text-white gap-2 shadow-lg rounded-xl h-11 px-6 transition-all hover:scale-105 active:scale-95"
                >
                    <Plus className="h-4 w-4"/> Connect Inbox
                </Button>
            </div>
            
            <div className="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-100/50 p-5 rounded-2xl text-sm flex gap-4 shadow-inner">
                <div className="h-10 w-10 rounded-full bg-white flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                    <Sparkles className="h-5 w-5" />
                </div>
                <div className="flex-1">
                    <h4 className="font-bold text-indigo-900 mb-1">Local Sending Infrastructure Active</h4>
                    <p className="text-indigo-700/80 leading-relaxed">Emails sent via the Lead Intelligence Engine originate directly from your local machine using the connected SMTP accounts. This mimics natural human behavior, bypasses centralized cloud server IP bans, and drastically increases inbox placement rates.</p>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {inboxes.length === 0 ? (
                    <div className="col-span-full text-center py-16 bg-white/50 backdrop-blur-sm rounded-2xl border border-dashed border-slate-300">
                        <Mail className="h-10 w-10 text-slate-300 mx-auto mb-3" />
                        <p className="font-semibold text-slate-700">No inboxes connected</p>
                        <p className="text-sm text-slate-500 mt-1">Connect an SMTP/IMAP account to start sending campaigns.</p>
                    </div>
                ) : inboxes.map(inbox => (
                    <div key={inbox.id} className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group">
                        <div className="flex items-center gap-4">
                            <div className="h-12 w-12 bg-gradient-to-br from-slate-100 to-slate-50 border border-white shadow-sm rounded-xl flex items-center justify-center text-slate-600 group-hover:text-indigo-600 group-hover:scale-105 transition-all">
                                <Mail className="h-6 w-6" />
                            </div>
                            <div>
                                <p className="font-bold text-slate-900 truncate max-w-[180px]">{inbox.email}</p>
                                <p className="text-xs font-medium text-slate-400 mt-0.5">{inbox.smtp_host}</p>
                            </div>
                        </div>
                        <div className="h-3 w-3 rounded-full bg-emerald-500 border-2 border-white shadow-sm relative">
                            <div className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-50"></div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Musoftware`} />
            
            {/* Background elements for premium feel */}
            <div className="fixed inset-0 pointer-events-none -z-10 bg-slate-50/50">
                <div className="absolute top-0 left-1/4 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl opacity-50 mix-blend-multiply"></div>
                <div className="absolute bottom-1/4 right-1/4 w-[30rem] h-[30rem] bg-violet-400/10 rounded-full blur-3xl opacity-50 mix-blend-multiply"></div>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">

                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
                    <div className="flex items-start gap-5">
                        <Link href={route('tools.show', tool.slug)} className="mt-1 h-10 w-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-md transition-all">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center text-white shadow-lg shadow-slate-900/20">
                                    <Users className="h-6 w-6" />
                                </div>
                                <div>
                                    <h1 className="text-3xl font-black text-slate-900 tracking-tight">{tool.title}</h1>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 pl-[3.75rem]">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-100 px-2.5 py-0.5 rounded-full">
                                    {subscription.plan_name} Edition
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="flex items-center gap-3 bg-white/80 backdrop-blur-md p-1.5 rounded-full border border-slate-200 shadow-sm">
                        <div className={`flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider px-4 py-2 rounded-full ${rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>
                            {rtStatus === 'ok' ? (
                                <><div className="relative flex h-2 w-2"><span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></div> Local Agent Connected</>
                            ) : (
                                <><WifiOff className="h-3 w-3" /> Agent Offline</>
                            )}
                        </div>
                    </div>
                </div>

                {rtStatus === 'offline' && (
                    <div className="bg-white border-l-4 border-l-red-500 p-6 rounded-2xl shadow-xl shadow-red-900/5 mb-10 flex items-start gap-4 animate-in fade-in slide-in-from-top-4">
                        <div className="bg-red-50 p-2 rounded-full text-red-600 shrink-0 mt-0.5">
                            <AlertCircle className="h-6 w-6" />
                        </div>
                        <div>
                            <h3 className="text-slate-900 font-bold text-lg">Runtime Agent Disconnected</h3>
                            <p className="text-slate-600 mt-1 leading-relaxed max-w-3xl">The Lead Intelligence Engine requires the Musoftware Local Agent to run on your machine. This architecture ensures IP stealth, bypasses cloud scraping blocks, and keeps your data 100% private. Please start the local agent to access the dashboard.</p>
                            <Button onClick={() => window.open('/tools', '_blank')} className="mt-4 bg-slate-900 hover:bg-slate-800 text-white rounded-xl">Download Agent</Button>
                        </div>
                    </div>
                )}

                {rtStatus === 'ok' && (
                    <div className="flex flex-col lg:flex-row gap-8">
                        {/* Elegant Sidebar Navigation */}
                        <div className="lg:w-64 shrink-0">
                            <div className="bg-white/60 backdrop-blur-xl border border-slate-200/60 rounded-2xl p-3 shadow-sm sticky top-8 flex flex-col gap-1">
                                <button 
                                    onClick={() => setActiveTab('campaigns')} 
                                    className={`w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold transition-all relative overflow-hidden ${activeTab === 'campaigns' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm'}`}
                                >
                                    <Target className="h-4 w-4 relative z-10" /> 
                                    <span className="relative z-10">Active Campaigns</span>
                                </button>
                                <button 
                                    onClick={() => {setActiveTab('leads'); loadLeads('');}} 
                                    className={`w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold transition-all relative overflow-hidden ${activeTab === 'leads' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm'}`}
                                >
                                    <Database className="h-4 w-4 relative z-10" /> 
                                    <span className="relative z-10">Lead Database</span>
                                </button>
                                <button 
                                    onClick={() => setActiveTab('inboxes')} 
                                    className={`w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold transition-all relative overflow-hidden ${activeTab === 'inboxes' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm'}`}
                                >
                                    <Inbox className="h-4 w-4 relative z-10" /> 
                                    <span className="relative z-10">Email Infrastructure</span>
                                </button>
                            </div>
                        </div>

                        {/* Main Content Area */}
                        <div className="flex-1 min-w-0">
                            {activeTab === 'campaigns' && renderCampaigns()}
                            {activeTab === 'leads' && renderLeads()}
                            {activeTab === 'inboxes' && renderInboxes()}
                        </div>
                    </div>
                )}

            </div>
        </ToolsPublicLayout>
    );
}
