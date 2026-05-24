import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Target, Play, Square, Download, Sparkles,
    AlertCircle, CheckCircle, RefreshCw, ChevronDown, ChevronUp, Settings
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function ContentResearcherRunner({ tool }: any) {
    const [keyword, setKeyword] = useState('');
    const [engine, setEngine] = useState<'duckduckgo' | 'google'>('duckduckgo');
    const [recursive, setRecursive] = useState(false);
    
    const [status, setStatus]   = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    
    const [results, setResults] = useState<any[]>([]);
    const [logs, setLogs] = useState<string[]>([]);
    
    const [errorMsg, setError]  = useState('');
    const [taskId, setTaskId]   = useState<string | null>(null);
    const wsRef = useRef<WebSocket | null>(null);

    const [showAdvanced, setShowAdvanced] = useState(false);

    useEffect(() => {
        const ws = new WebSocket(getWsUrl());
        wsRef.current = ws;
        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                
                // Realtime task progress
                if (msg.event === 'task.progress' && msg.data?.taskId === taskId) {
                    if (msg.data.percent !== undefined) setProgress(msg.data.percent);
                    if (msg.data.message) {
                        setProgressMsg(msg.data.message);
                        setLogs(prev => [msg.data.message, ...prev].slice(0, 50));
                    }
                }
                
                // Realtime result push (custom event via wa_event pipeline)
                if (msg.event === 'link_scraped') {
                    if (msg.data?.result) {
                        setResults(prev => [msg.data.result, ...prev]);
                    }
                }

                if (msg.event === 'task.done' && msg.data?.taskId === taskId) {
                    setStatus('done');
                }
                if (msg.event === 'task.error' && msg.data?.taskId === taskId) {
                    setError(msg.data.error ?? 'Unknown error'); setStatus('error');
                }
            } catch {}
        };
        return () => ws.close();
    }, [taskId]);

    const handleStart = async () => {
        if (!keyword.trim()) return;

        setStatus('running'); setProgress(0); setProgressMsg('Initializing Search...'); setResults([]); setLogs([]); setError('');

        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/content-researcher/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { keyword: keyword.trim(), engine, recursive } }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Failed to start');
            setTaskId(json.taskId);
        } catch (err: any) {
            setError(err.message); setStatus('error');
        }
    };

    const handleStop = async () => {
        if (!taskId) return;
        try {
            await fetch(`${getRuntimeHttp()}/tasks/${taskId}/kill`, { method: 'POST' });
            setStatus('done');
            setProgressMsg('Stopped by user');
        } catch (err) {
            console.error('Failed to stop task', err);
        }
    };

    const exportCsv = () => {
        if (results.length === 0) return;
        const headers = ["URL", "Domain", "Title", "Meta Description", "Meta Keywords", "H1", "H2", "H3", "Images", "Internal Links", "Social Links", "Video Links"];
        
        let csvContent = "data:text/csv;charset=utf-8," 
            + headers.join(",") + "\n"
            + results.map(r => {
                return [
                    r.url, r.domain, r.title, r.description, r.keywords,
                    r.h1, r.h2, r.h3, r.images, r.internal_links, r.social_links, r.video_links
                ].map(field => `"${(field || '').toString().replace(/"/g, '""')}"`).join(",");
            }).join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${keyword.replace(/\s+/g, '_')}_seo_research.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <div className="min-h-screen bg-slate-50 font-sans flex flex-col">
            {/* Top bar */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10 shrink-0">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-indigo-600 to-blue-600 rounded-lg flex items-center justify-center shadow-sm">
                        <Target className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">Content Researcher</span>
                </div>
                <div className="flex items-center gap-3">
                    <Button variant="outline" onClick={exportCsv} disabled={results.length === 0} className="h-8 text-xs font-bold gap-1.5">
                        <Download className="w-3.5 h-3.5" /> Export CSV
                    </Button>
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Scraping...' : status === 'done' ? 'Finished' : 'Ready'}
                    </Badge>
                </div>
            </div>

            <div className="flex flex-1 overflow-hidden">
                {/* Sidebar Configuration */}
                <div className="w-80 bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto">
                    <div className="p-5 space-y-6">
                        <div>
                            <h2 className="text-lg font-bold tracking-tight text-slate-900">Campaign Setup</h2>
                            <p className="text-xs text-slate-500 mt-1">Configure your search scraping parameters.</p>
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-bold text-slate-700">Target Keyword</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <Input
                                        value={keyword}
                                        onChange={e => setKeyword(e.target.value)}
                                        onKeyDown={e => e.key === 'Enter' && handleStart()}
                                        placeholder="e.g. digital marketing tips"
                                        className="pl-9 h-11 text-sm bg-slate-50 font-medium"
                                        disabled={status === 'running'}
                                    />
                                </div>
                            </div>

                            {/* Progressive Disclosure: Advanced Settings */}
                            <div className="pt-2">
                                <Button 
                                    variant="ghost" 
                                    onClick={() => setShowAdvanced(!showAdvanced)}
                                    className="w-full justify-between h-9 px-3 text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg"
                                >
                                    <div className="flex items-center gap-2">
                                        <Settings className="w-3.5 h-3.5" />
                                        Advanced Configuration
                                    </div>
                                    {showAdvanced ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                                </Button>
                                
                                {showAdvanced && (
                                    <div className="mt-3 p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4 animate-in fade-in slide-in-from-top-2 duration-200">
                                        <div className="space-y-2">
                                            <Label className="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Search Engine</Label>
                                            <div className="flex border border-slate-200 rounded-lg overflow-hidden p-0.5 bg-white">
                                                {(['duckduckgo', 'google'] as const).map(m => (
                                                    <Button
                                                        variant="ghost"
                                                        key={m}
                                                        onClick={() => setEngine(m)}
                                                        disabled={status === 'running'}
                                                        className={`flex-1 h-7 text-[11px] font-bold transition-all rounded-md ${engine === m ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 hover:text-white' : 'text-slate-500 hover:text-slate-900'}`}
                                                    >
                                                        {m === 'duckduckgo' ? 'DuckDuckGo' : 'Google'}
                                                    </Button>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label className="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Scraping Mode</Label>
                                            <Button 
                                                variant="outline" 
                                                onClick={() => setRecursive(!recursive)}
                                                disabled={status === 'running'}
                                                className={`w-full justify-start h-auto p-3 text-left ${recursive ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-200 bg-white'}`}
                                            >
                                                <div>
                                                    <p className={`text-xs font-bold ${recursive ? 'text-indigo-900' : 'text-slate-700'}`}>Recursive Scraping</p>
                                                    <p className={`text-[10px] mt-0.5 leading-snug ${recursive ? 'text-indigo-600' : 'text-slate-500'}`}>Automatically search related keywords discovered during extraction.</p>
                                                </div>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {status === 'running' ? (
                                <Button
                                    onClick={handleStop}
                                    variant="destructive"
                                    className="w-full h-11 rounded-xl text-sm font-bold shadow-md gap-2"
                                >
                                    <Square className="w-4 h-4 fill-current" />
                                    Stop Campaign
                                </Button>
                            ) : (
                                <Button
                                    onClick={handleStart}
                                    disabled={!keyword.trim()}
                                    className="w-full h-11 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md gap-2"
                                >
                                    <Play className="w-4 h-4 fill-current" />
                                    Start Campaign
                                </Button>
                            )}

                            {/* Error */}
                            {status === 'error' && (
                                <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-3">
                                    <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    <p className="text-xs text-rose-700 font-medium leading-relaxed">{errorMsg}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Main Workspace Area */}
                <div className="flex-1 flex flex-col min-w-0 bg-slate-50">
                    {/* Live Progress Bar */}
                    {status === 'running' && (
                        <div className="bg-white border-b border-slate-200 px-6 py-3 shrink-0 flex items-center gap-4 animate-in fade-in slide-in-from-top-2 duration-300">
                            <div className="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                                <RefreshCw className="w-4 h-4 text-indigo-600 animate-spin" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="flex justify-between items-end mb-1.5">
                                    <p className="text-xs font-bold text-slate-800 truncate">{progressMsg || 'Processing...'}</p>
                                    <span className="text-[10px] font-black text-indigo-600">{progress}%</span>
                                </div>
                                <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div 
                                        className="h-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-300 rounded-full" 
                                        style={{ width: `${progress}%` }} 
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Data Table */}
                    <div className="flex-1 overflow-auto p-6">
                        {results.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                                <div className="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center">
                                    <Target className="w-8 h-8 text-slate-300" />
                                </div>
                                <p className="text-sm font-medium">No results yet. Start a campaign to see live data.</p>
                            </div>
                        ) : (
                            <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full animate-in fade-in duration-500">
                                <div className="overflow-x-auto flex-1">
                                    <table className="w-full text-left border-collapse min-w-[1200px]">
                                        <thead>
                                            <tr className="bg-slate-50 border-b border-slate-200 text-[10px] uppercase tracking-wider font-black text-slate-500 sticky top-0 z-10">
                                                <th className="px-4 py-3 whitespace-nowrap">Domain</th>
                                                <th className="px-4 py-3 whitespace-nowrap">Page Title</th>
                                                <th className="px-4 py-3 w-64">Meta Description</th>
                                                <th className="px-4 py-3 whitespace-nowrap">H1 Header</th>
                                                <th className="px-4 py-3 whitespace-nowrap text-right">Metrics</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {results.map((r, i) => (
                                                <tr key={i} className="hover:bg-slate-50/50 transition-colors group">
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-xs font-bold text-slate-900 truncate max-w-[150px]" title={r.domain}>{r.domain}</p>
                                                        <a href={r.url} target="_blank" rel="noopener noreferrer" className="text-[10px] text-blue-600 hover:underline truncate max-w-[150px] block mt-0.5" title={r.url}>Open Link</a>
                                                    </td>
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-xs font-medium text-slate-800 line-clamp-2" title={r.title}>{r.title || '-'}</p>
                                                    </td>
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-[11px] text-slate-600 line-clamp-3" title={r.description}>{r.description || '-'}</p>
                                                    </td>
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-[11px] font-medium text-slate-700 line-clamp-2" title={r.h1}>{r.h1 || '-'}</p>
                                                    </td>
                                                    <td className="px-4 py-3 align-top text-right">
                                                        <div className="flex flex-col items-end gap-1">
                                                            <Badge variant="secondary" className="bg-indigo-50 text-indigo-700 hover:bg-indigo-50 text-[9px] font-bold px-1.5 py-0">
                                                                {r.internal_links?.split(',').filter(Boolean).length || 0} Links
                                                            </Badge>
                                                            <Badge variant="secondary" className="bg-emerald-50 text-emerald-700 hover:bg-emerald-50 text-[9px] font-bold px-1.5 py-0">
                                                                {r.images?.split(',').filter(Boolean).length || 0} Images
                                                            </Badge>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="bg-slate-50 border-t border-slate-200 px-4 py-2 flex items-center justify-between text-[11px] font-bold text-slate-500">
                                    <span>Total Discovered: <span className="text-slate-900">{results.length}</span></span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
