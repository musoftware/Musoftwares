import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, Square, Download, Hash, User, TrendingUp,
    Search, Loader2, CheckCircle2, AlertCircle, Wifi, WifiOff,
    ChevronDown, ChevronUp, Copy, Check, BarChart3, Database,
    Clock, Plus, Filter, Settings, Search as SearchIcon, Users,
    Video, Save, Trash2, PauseCircle
} from 'lucide-react';

interface Props {
    tool: { slug: string; title: string; icon_url: string | null; short_description: string; category?: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort: number;
    pluginSlug: string;
}

export default function TikTokIntelligenceRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    const [activeTab, setActiveTab] = useState<'discover'|'vault'|'jobs'|'settings'>('discover');

    // Fetch runtime status
    useEffect(() => {
        fetch(`${base}/health`).then(r => {
            setRtStatus(r.ok ? 'ok' : 'offline');
        }).catch(() => setRtStatus('offline'));
    }, [base]);

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 h-[calc(100vh-64px)] flex flex-col">
                {/* Header */}
                <div className="flex items-center justify-between mb-6 shrink-0">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                                {tool.icon_url
                                    ? <img src={tool.icon_url} className="w-8 h-8 object-contain" alt="" />
                                    : <BarChart3 className="h-6 w-6" />}
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-slate-900 tracking-tight">{tool.title}</h1>
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                        {subscription.plan_name} Intelligence
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className={`flex items-center gap-2 text-xs font-medium px-4 py-2 rounded-full shadow-sm border ${
                            rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                            rtStatus === 'offline' ? 'bg-red-50 text-red-600 border-red-200' :
                            'bg-slate-50 text-slate-500 border-slate-200'
                        }`}>
                            {rtStatus === 'ok' ? <Wifi className="h-4 w-4" /> : <WifiOff className="h-4 w-4" />}
                            {rtStatus === 'ok' ? 'Local Engine Connected' : rtStatus === 'offline' ? 'Local Engine Offline' : 'Connecting...'}
                        </div>
                    </div>
                </div>

                <div className="flex flex-1 gap-6 overflow-hidden">
                    {/* Sidebar Nav */}
                    <div className="w-64 shrink-0 flex flex-col gap-1">
                        {[
                            { id: 'discover', label: 'Discover Engine', icon: SearchIcon },
                            { id: 'vault', label: 'UGC Vault', icon: Database },
                            { id: 'jobs', label: 'Monitoring Jobs', icon: Clock },
                            { id: 'settings', label: 'Settings & Exports', icon: Settings },
                        ].map(t => (
                            <button
                                key={t.id}
                                onClick={() => setActiveTab(t.id as any)}
                                className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all ${
                                    activeTab === t.id
                                        ? 'bg-indigo-600 text-white shadow-md'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                }`}
                            >
                                <t.icon className="h-5 w-5" />
                                {t.label}
                            </button>
                        ))}

                        <div className="mt-auto p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <h4 className="text-xs font-semibold text-slate-900 mb-2 uppercase tracking-wider">Engine Stats</h4>
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Saved Creators</span>
                                    <span className="font-semibold text-slate-900">0</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Saved Videos</span>
                                    <span className="font-semibold text-slate-900">0</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Active Jobs</span>
                                    <span className="font-semibold text-slate-900">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Main Content Area */}
                    <div className="flex-1 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col relative">
                        {rtStatus === 'offline' && (
                            <div className="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
                                <div className="max-w-md w-full bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-xl">
                                    <div className="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <WifiOff className="h-8 w-8" />
                                    </div>
                                    <h2 className="text-xl font-bold text-slate-900 mb-2">Engine is Offline</h2>
                                    <p className="text-slate-500 mb-6 text-sm">
                                        The TikTok Intelligence Engine runs entirely locally on your machine for maximum privacy and anti-detection. Please start the Musoftware Runtime app to continue.
                                    </p>
                                    <Button className="w-full bg-indigo-600 hover:bg-indigo-500">Download Runtime App</Button>
                                </div>
                            </div>
                        )}

                        {activeTab === 'discover' && <DiscoverTab base={base} pluginSlug={pluginSlug} />}
                        {activeTab === 'vault' && <VaultTab base={base} pluginSlug={pluginSlug} />}
                        {activeTab === 'jobs' && <JobsTab base={base} pluginSlug={pluginSlug} />}
                        {activeTab === 'settings' && <div className="p-8"><h2 className="text-2xl font-bold">Settings</h2><p className="text-slate-500 mt-2">Export controls and runtime limits.</p></div>}
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}

function DiscoverTab({ base, pluginSlug }: { base: string, pluginSlug: string }) {
    const [action, setAction] = useState('keyword');
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState<'idle'|'running'|'done'|'error'>('idle');
    const [logs, setLogs] = useState<string[]>([]);
    const [results, setResults] = useState<any[]>([]);
    const [profile, setProfile] = useState<any>(null);
    const logsEnd = useRef<HTMLDivElement>(null);
    const timerRef = useRef<any>(null);

    useEffect(() => { logsEnd.current?.scrollIntoView({ behavior: 'smooth' }); }, [logs]);

    const handleRun = async () => {
        if (!query.trim()) return;
        setStatus('running'); setLogs(['Starting discovery engine...']); setResults([]); setProfile(null);
        
        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { action, query, max_count: 5 } }),
            });
            const data = await res.json();
            
            // For now, simulate WS/polling by waiting and fetching result
            // Normally we would connect to WS here
            const tid = data.taskId;
            
            timerRef.current = setInterval(async () => {
                const r = await fetch(`${base}/tasks/${tid}`);
                const d = await r.json();
                setLogs(d.logs?.map((l:any) => l.message) || []);
                if (d.status === 'done') {
                    setProfile(d.result?.profile);
                    setResults(d.result?.videos || []);
                    setStatus('done');
                    clearInterval(timerRef.current);
                } else if (d.status === 'error') {
                    setStatus('error');
                    clearInterval(timerRef.current);
                }
            }, 1000);

        } catch (e) {
            setStatus('error');
            setLogs(l => [...l, 'Error connecting to runtime.']);
        }
    };

    return (
        <div className="flex flex-col h-full bg-slate-50/50">
            {/* Search Header */}
            <div className="p-6 border-b border-slate-200 bg-white shrink-0">
                <div className="flex gap-4">
                    <div className="w-48">
                        <select className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none font-medium" value={action} onChange={e=>setAction(e.target.value)}>
                            <option value="keyword">Keyword Discovery</option>
                            <option value="hashtag">Hashtag Analysis</option>
                            <option value="profile">Profile Intelligence</option>
                        </select>
                    </div>
                    <div className="flex-1 relative">
                        <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                        <input 
                            type="text" 
                            placeholder="Enter keyword, hashtag, or username to analyze..." 
                            className="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none font-medium"
                            value={query}
                            onChange={e=>setQuery(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && status !== 'running' && handleRun()}
                        />
                    </div>
                    <Button 
                        disabled={status === 'running' || !query.trim()} 
                        onClick={handleRun}
                        className="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 h-auto rounded-xl font-semibold gap-2"
                    >
                        {status === 'running' ? <Loader2 className="h-5 w-5 animate-spin" /> : <Play className="h-5 w-5" />}
                        {status === 'running' ? 'Analyzing...' : 'Run Engine'}
                    </Button>
                </div>
            </div>

            {/* Main Discovery Area */}
            <div className="flex-1 overflow-auto p-6 flex flex-col gap-6">
                
                {status === 'idle' && results.length === 0 && (
                    <div className="m-auto text-center max-w-sm">
                        <div className="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <SearchIcon className="h-10 w-10" />
                        </div>
                        <h3 className="text-xl font-bold text-slate-900 mb-2">Ready to Discover</h3>
                        <p className="text-slate-500 text-sm">Enter a target above to extract creators, analyze UGC formats, and score engagement metrics.</p>
                    </div>
                )}

                {status === 'running' && (
                    <div className="bg-slate-900 rounded-2xl p-6 text-emerald-400 font-mono text-sm max-w-3xl w-full mx-auto shadow-2xl h-64 overflow-auto border border-slate-800">
                        <div className="flex items-center gap-3 mb-4 text-white">
                            <Loader2 className="h-5 w-5 animate-spin text-indigo-400" />
                            <span className="font-semibold">Engine Output Console</span>
                        </div>
                        {logs.map((l, i) => <div key={i} className="mb-1 opacity-90">{l}</div>)}
                        <div ref={logsEnd} />
                    </div>
                )}

                {profile && (
                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-start gap-6">
                        <img src={profile.avatar_url} className="w-24 h-24 rounded-full border-4 border-white shadow-lg object-cover" alt="" />
                        <div className="flex-1">
                            <div className="flex items-center justify-between mb-2">
                                <div>
                                    <h2 className="text-2xl font-bold text-slate-900">@{profile.username}</h2>
                                    <p className="text-slate-500 font-medium">{profile.nickname}</p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">
                                        Trust Score: {profile.trust_score}
                                    </span>
                                    <Button variant="outline" size="sm" className="gap-2">
                                        <Save className="h-4 w-4" /> Save Creator
                                    </Button>
                                </div>
                            </div>
                            <p className="text-sm text-slate-600 mb-4">{profile.bio}</p>
                            <div className="flex gap-8">
                                <div><p className="text-2xl font-bold text-slate-900">{(profile.followers/1000).toFixed(1)}k</p><p className="text-xs text-slate-500 uppercase tracking-wide font-semibold">Followers</p></div>
                                <div><p className="text-2xl font-bold text-slate-900">{(profile.likes/1000).toFixed(1)}k</p><p className="text-xs text-slate-500 uppercase tracking-wide font-semibold">Likes</p></div>
                                <div><p className="text-2xl font-bold text-slate-900">{profile.videos_count}</p><p className="text-xs text-slate-500 uppercase tracking-wide font-semibold">Videos</p></div>
                            </div>
                        </div>
                    </div>
                )}

                {results.length > 0 && (
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-slate-900">Recent Videos Analysis</h3>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {results.map((v, i) => (
                                <div key={i} className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm group hover:shadow-md transition-all">
                                    <div className="h-48 bg-slate-100 relative">
                                        <div className="absolute inset-0 flex items-center justify-center text-slate-300">
                                            <Video className="h-12 w-12" />
                                        </div>
                                        <div className="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-md font-medium">
                                            {v.duration_sec}s
                                        </div>
                                        <div className="absolute top-2 right-2 bg-indigo-600 text-white text-xs px-2 py-1 rounded-md font-bold shadow-sm">
                                            ER {v.engagement_rate}%
                                        </div>
                                    </div>
                                    <div className="p-4">
                                        <p className="text-sm text-slate-800 line-clamp-2 mb-4 h-10 font-medium">{v.description}</p>
                                        <div className="grid grid-cols-4 gap-2 text-center mb-4">
                                            <div className="bg-slate-50 rounded-lg p-1"><p className="text-xs font-bold text-slate-900">{v.plays}</p><p className="text-[10px] text-slate-400">Plays</p></div>
                                            <div className="bg-slate-50 rounded-lg p-1"><p className="text-xs font-bold text-slate-900">{v.likes}</p><p className="text-[10px] text-slate-400">Likes</p></div>
                                            <div className="bg-slate-50 rounded-lg p-1"><p className="text-xs font-bold text-slate-900">{v.comments}</p><p className="text-[10px] text-slate-400">Cmts</p></div>
                                            <div className="bg-slate-50 rounded-lg p-1"><p className="text-xs font-bold text-slate-900">{v.shares}</p><p className="text-[10px] text-slate-400">Shr</p></div>
                                        </div>
                                        <Button className="w-full bg-slate-900 hover:bg-slate-800 text-white gap-2">
                                            <Save className="h-4 w-4" /> Save to Vault
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

function VaultTab({ base, pluginSlug }: { base: string, pluginSlug: string }) {
    const [videos, setVideos] = useState<any[]>([]);
    
    useEffect(() => {
        fetch(`${base}/plugins/${pluginSlug}/api/vault`)
            .then(r => r.json())
            .then(d => { if (d.success) setVideos(d.videos); })
            .catch(console.error);
    }, [base, pluginSlug]);

    const handleExportCsv = () => {
        if (!videos.length) return;
        const headers = ['id', 'author', 'description', 'plays', 'likes', 'comments', 'shares', 'engagement_rate'];
        const rows = videos.map(v => headers.map(h => `"${String(v[h] ?? '').replace(/"/g,'""')}"`).join(','));
        const blob = new Blob([[headers.join(','), ...rows].join('\n')], { type: 'text/csv' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
        a.download = `tiktok-vault-export-${Date.now()}.csv`; a.click();
    };

    return (
        <div className="p-8 h-full flex flex-col">
            <div className="flex justify-between items-end mb-8">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">UGC Vault</h2>
                    <p className="text-slate-500 mt-1">Your locally saved high-performing content and creators.</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" className="gap-2"><Filter className="h-4 w-4"/> Filter</Button>
                    <Button variant="outline" className="gap-2" onClick={handleExportCsv}><Download className="h-4 w-4"/> Export CSV</Button>
                </div>
            </div>

            {videos.length === 0 ? (
                <div className="flex-1 flex items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl">
                    <div className="text-center text-slate-500">
                        <Database className="h-12 w-12 mx-auto mb-3 text-slate-300" />
                        <p>No saved videos yet.</p>
                    </div>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-4 overflow-auto">
                    {/* Render saved videos here */}
                    {videos.map(v => (
                         <div key={v.id} className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                             <div className="p-4">
                                 <p className="text-xs text-indigo-600 font-bold mb-1">@{v.author}</p>
                                 <p className="text-sm text-slate-800 line-clamp-2">{v.description}</p>
                             </div>
                         </div>
                    ))}
                </div>
            )}
        </div>
    );
}

function JobsTab({ base, pluginSlug }: { base: string, pluginSlug: string }) {
    const [jobs, setJobs] = useState<any[]>([]);

    useEffect(() => {
        fetch(`${base}/plugins/${pluginSlug}/api/jobs`)
            .then(r => r.json())
            .then(d => { if (d.success) setJobs(d.jobs); })
            .catch(console.error);
    }, [base, pluginSlug]);

    return (
        <div className="p-8 h-full flex flex-col">
             <div className="flex justify-between items-end mb-8">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">Monitoring Jobs</h2>
                    <p className="text-slate-500 mt-1">Continuous local tracking for hashtags and competitors.</p>
                </div>
                <Button className="bg-indigo-600 hover:bg-indigo-500 text-white gap-2">
                    <Plus className="h-4 w-4"/> New Job
                </Button>
            </div>

            <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm flex-1">
                <table className="w-full text-left text-sm text-slate-600">
                    <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-semibold">
                        <tr>
                            <th className="px-6 py-4">Target</th>
                            <th className="px-6 py-4">Type</th>
                            <th className="px-6 py-4">Interval</th>
                            <th className="px-6 py-4">Status</th>
                            <th className="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {jobs.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="px-6 py-12 text-center text-slate-400">
                                    No active monitoring jobs. Create one to start tracking.
                                </td>
                            </tr>
                        ) : (
                            jobs.map(j => (
                                <tr key={j.id} className="hover:bg-slate-50">
                                    <td className="px-6 py-4 font-medium text-slate-900">{j.target}</td>
                                    <td className="px-6 py-4"><span className="px-2 py-1 bg-slate-100 rounded text-xs">{j.type}</span></td>
                                    <td className="px-6 py-4">Every {j.interval_hours}h</td>
                                    <td className="px-6 py-4">
                                        <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${
                                            j.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
                                        }`}>
                                            {j.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-amber-600"><PauseCircle className="h-4 w-4" /></Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-red-600"><Trash2 className="h-4 w-4" /></Button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
