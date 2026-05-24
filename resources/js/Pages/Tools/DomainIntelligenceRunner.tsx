import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Target, Play, Square, Download, Sparkles, Server, Network,
    AlertCircle, CheckCircle, RefreshCw, ChevronDown, ChevronUp, Settings, Globe, Shield, Activity
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function DomainIntelligenceRunner({ tool }: any) {
    const [domainsText, setDomainsText] = useState('');
    const [status, setStatus]   = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    
    const [results, setResults] = useState<any[]>([]);
    const [logs, setLogs] = useState<string[]>([]);
    
    const [errorMsg, setError]  = useState('');
    const [taskId, setTaskId]   = useState<string | null>(null);
    const wsRef = useRef<WebSocket | null>(null);

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
                
                // Custom event for intelligence result
                if (msg.event === 'domain_scanned') {
                    if (msg.data?.result) {
                        setResults(prev => [...prev, msg.data.result]);
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
        const domainList = domainsText.split('\n').map(d => d.trim()).filter(d => d);
        if (domainList.length === 0) return;

        setStatus('running'); setProgress(0); setProgressMsg('Initializing Scan...'); setResults([]); setLogs([]); setError('');

        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/domain-intelligence/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { domains: domainList } }),
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
            await fetch(`${getRuntimeHttp()}/tasks/${taskId}/stop`, { method: 'POST' });
            setStatus('done');
            setProgressMsg('Stopped by user');
        } catch (err) {
            console.error('Failed to stop task', err);
        }
    };

    const exportCsv = () => {
        if (results.length === 0) return;
        const headers = [
            "Domain", "Registrar", "Creation Date", "Expiry Date", "Name Servers", 
            "A Records", "AAAA Records", "MX Records", "TXT Records", "CNAME Records", 
            "Tech Stack", "Title", "Meta Description", "Subdomains"
        ];
        
        const csvRows = results.map(res => {
            return [
                res.domain,
                res.whois?.registrar || '',
                res.whois?.creationDate || '',
                res.whois?.expiryDate || '',
                (res.whois?.nameServers || []).join('; '),
                (res.dns?.a || []).join('; '),
                (res.dns?.aaaa || []).join('; '),
                (res.dns?.mx || []).map((m: any) => m.exchange).join('; '),
                (res.dns?.txt || []).flat().join('; '),
                (res.dns?.cname || []).join('; '),
                (res.techStack || []).join('; '),
                res.seo?.title || '',
                res.seo?.description || '',
                (res.subdomains || []).join('; ')
            ];
        });

        let csvContent = "data:text/csv;charset=utf-8," 
            + headers.join(",") + "\n"
            + csvRows.map(row => row.map(field => `"${(field || '').toString().replace(/"/g, '""')}"`).join(",")).join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `domain_intelligence_report.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <div className="min-h-[calc(100vh-theme(spacing.14))] bg-slate-50 font-sans flex flex-col">
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10 shrink-0">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-lg flex items-center justify-center shadow-sm">
                        <Globe className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">Domain Intelligence</span>
                </div>
                <div className="flex items-center gap-3">
                    <Button variant="outline" onClick={exportCsv} disabled={results.length === 0} className="h-8 text-xs font-bold gap-1.5">
                        <Download className="w-3.5 h-3.5" /> Export Report
                    </Button>
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Scanning...' : status === 'done' ? 'Finished' : 'Ready'}
                    </Badge>
                </div>
            </div>

            <div className="flex flex-1 overflow-hidden">
                {/* Sidebar Configuration */}
                <div className="w-80 bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto">
                    <div className="p-5 space-y-6">
                        <div>
                            <h2 className="text-lg font-bold tracking-tight text-slate-900">Target Setup</h2>
                            <p className="text-xs text-slate-500 mt-1">Enter domains to perform deep reconnaissance (one per line).</p>
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-bold text-slate-700">Domain Names</Label>
                                <div className="relative">
                                    <Globe className="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    <textarea
                                        value={domainsText}
                                        onChange={e => setDomainsText(e.target.value)}
                                        placeholder="musoftwares.com&#10;example.com"
                                        className="w-full flex min-h-[120px] rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium ring-offset-white placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 resize-y pl-9"
                                        disabled={status === 'running'}
                                    />
                                </div>
                            </div>

                            {status === 'running' ? (
                                <Button
                                    onClick={handleStop}
                                    variant="destructive"
                                    className="w-full h-11 rounded-xl text-sm font-bold shadow-md gap-2"
                                >
                                    <Square className="w-4 h-4 fill-current" />
                                    Stop Scan
                                </Button>
                            ) : (
                                <Button
                                    onClick={handleStart}
                                    disabled={!domainsText.trim()}
                                    className="w-full h-11 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md gap-2"
                                >
                                    <Play className="w-4 h-4 fill-current" />
                                    Launch Scan
                                </Button>
                            )}

                            {status === 'error' && (
                                <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-3">
                                    <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    <p className="text-xs text-rose-700 font-medium leading-relaxed">{errorMsg}</p>
                                </div>
                            )}
                            
                            {/* Live Logs */}
                            {logs.length > 0 && (
                                <div className="mt-6 pt-6 border-t border-slate-100">
                                    <Label className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 block">Live Activity</Label>
                                    <div className="space-y-1.5 max-h-48 overflow-y-auto pr-2">
                                        {logs.map((l, i) => (
                                            <div key={i} className="text-[10px] text-slate-600 truncate bg-slate-50 px-2 py-1.5 rounded border border-slate-100">
                                                {l}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Main Workspace Area */}
                <div className="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-y-auto">
                    {status === 'running' && (
                        <div className="bg-white border-b border-slate-200 px-6 py-3 shrink-0 flex items-center gap-4 animate-in fade-in slide-in-from-top-2 duration-300 sticky top-0 z-10">
                            <div className="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                                <RefreshCw className="w-4 h-4 text-indigo-600 animate-spin" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="flex justify-between items-end mb-1.5">
                                    <p className="text-xs font-bold text-slate-800 truncate">{progressMsg || 'Scanning...'}</p>
                                    <span className="text-[10px] font-black text-indigo-600">{progress}%</span>
                                </div>
                                <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div className="h-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-300 rounded-full" style={{ width: `${progress}%` }} />
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="flex-1 p-6 max-w-6xl mx-auto w-full">
                        {results.length === 0 && status !== 'running' ? (
                            <div className="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                                <div className="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center">
                                    <Globe className="w-8 h-8 text-slate-300" />
                                </div>
                                <p className="text-sm font-medium">Enter domains to generate an intelligence report.</p>
                            </div>
                        ) : (
                            <div className="space-y-8">
                                {results.map((res, index) => (
                                    <div key={index} className="space-y-6 animate-in fade-in duration-500 pt-6 first:pt-0 border-t-2 first:border-t-0 border-slate-200 border-dashed">
                                        {/* Header */}
                                        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-start justify-between gap-6">
                                            <div>
                                                <h1 className="text-2xl font-black text-slate-900 tracking-tight">{res.domain}</h1>
                                                <p className="text-sm text-slate-500 font-medium mt-1">Intelligence Report Generated Successfully</p>
                                            </div>
                                            {res.techStack && res.techStack.length > 0 && (
                                                <div className="flex flex-wrap gap-1.5 max-w-sm justify-end">
                                                    {res.techStack.map((tech: string, i: number) => (
                                                        <Badge key={i} variant="secondary" className="bg-violet-50 text-violet-700 hover:bg-violet-100 text-[10px] font-bold">
                                                            {tech}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* WHOIS Data */}
                                            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                                                <div className="bg-slate-50 border-b border-slate-100 px-5 py-3.5 flex items-center gap-2">
                                                    <Shield className="w-4 h-4 text-slate-500" />
                                                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">WHOIS Registration</h3>
                                                </div>
                                                <div className="p-5 space-y-4 flex-1">
                                                    <div>
                                                        <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Registrar</p>
                                                        <p className="text-sm font-medium text-slate-900">{res.whois?.registrar || 'Unknown'}</p>
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Creation Date</p>
                                                            <p className="text-sm font-medium text-slate-900">{res.whois?.creationDate ? new Date(res.whois.creationDate).toLocaleDateString() : 'Unknown'}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Expiry Date</p>
                                                            <p className="text-sm font-medium text-slate-900">{res.whois?.expiryDate ? new Date(res.whois.expiryDate).toLocaleDateString() : 'Unknown'}</p>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Name Servers</p>
                                                        <div className="space-y-1">
                                                            {res.whois?.nameServers?.length > 0 ? res.whois.nameServers.map((ns: string, i: number) => (
                                                                <p key={i} className="text-xs font-medium text-slate-700 bg-slate-50 p-1.5 rounded border border-slate-100">{ns}</p>
                                                            )) : <p className="text-sm text-slate-500">Not found</p>}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* DNS Records */}
                                            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                                                <div className="bg-slate-50 border-b border-slate-100 px-5 py-3.5 flex items-center gap-2">
                                                    <Network className="w-4 h-4 text-slate-500" />
                                                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">DNS Records</h3>
                                                </div>
                                                <div className="p-5 space-y-4 flex-1 max-h-[300px] overflow-y-auto">
                                                    <div>
                                                        <Badge variant="outline" className="mb-2 text-[10px] font-bold">A / AAAA Records (IPs)</Badge>
                                                        <div className="flex flex-wrap gap-2">
                                                            {res.dns?.a?.length > 0 ? res.dns.a.map((ip: string, i: number) => (
                                                                <span key={`a-${i}`} className="text-xs font-mono bg-slate-100 text-slate-800 px-2 py-1 rounded">A: {ip}</span>
                                                            )) : null}
                                                            {res.dns?.aaaa?.length > 0 ? res.dns.aaaa.map((ip: string, i: number) => (
                                                                <span key={`aaaa-${i}`} className="text-xs font-mono bg-slate-100 text-slate-800 px-2 py-1 rounded">AAAA: {ip}</span>
                                                            )) : null}
                                                            {(!res.dns?.a?.length && !res.dns?.aaaa?.length) && <span className="text-xs text-slate-500">None</span>}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <Badge variant="outline" className="mb-2 text-[10px] font-bold">MX Records (Mail)</Badge>
                                                        <div className="space-y-1">
                                                            {res.dns?.mx?.length > 0 ? res.dns.mx.map((mx: any, i: number) => (
                                                                <div key={i} className="text-xs font-mono bg-slate-100 text-slate-800 px-2 py-1 rounded truncate">
                                                                    <span className="text-slate-400 mr-2">Priority {mx.priority}</span>
                                                                    {mx.exchange}
                                                                </div>
                                                            )) : <span className="text-xs text-slate-500">None</span>}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <Badge variant="outline" className="mb-2 text-[10px] font-bold">TXT Records</Badge>
                                                        <div className="space-y-1">
                                                            {res.dns?.txt?.length > 0 ? res.dns.txt.map((txt: any, i: number) => (
                                                                <div key={i} className="text-[10px] font-mono bg-slate-100 text-slate-800 px-2 py-1.5 rounded break-all">
                                                                    {Array.isArray(txt) ? txt.join('') : txt}
                                                                </div>
                                                            )) : <span className="text-xs text-slate-500">None</span>}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <Badge variant="outline" className="mb-2 text-[10px] font-bold">CNAME Records</Badge>
                                                        <div className="flex flex-wrap gap-2">
                                                            {res.dns?.cname?.length > 0 ? res.dns.cname.map((cname: string, i: number) => (
                                                                <span key={i} className="text-xs font-mono bg-slate-100 text-slate-800 px-2 py-1 rounded">{cname}</span>
                                                            )) : <span className="text-xs text-slate-500">None</span>}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Subdomains */}
                                            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col md:col-span-2">
                                                <div className="bg-slate-50 border-b border-slate-100 px-5 py-3.5 flex items-center gap-2">
                                                    <Globe className="w-4 h-4 text-slate-500" />
                                                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">Discovered Subdomains ({res.subdomains?.length || 0})</h3>
                                                </div>
                                                <div className="p-5 flex-1 max-h-[300px] overflow-y-auto">
                                                    <div className="flex flex-wrap gap-2">
                                                        {res.subdomains?.length > 0 ? res.subdomains.map((sub: string, i: number) => (
                                                            <span key={i} className="text-[11px] font-mono bg-slate-100 text-slate-800 px-2 py-1 rounded border border-slate-200">{sub}</span>
                                                        )) : <span className="text-xs text-slate-500">None</span>}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* On-Page SEO / Meta Info */}
                                            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden md:col-span-2">
                                                <div className="bg-slate-50 border-b border-slate-100 px-5 py-3.5 flex items-center gap-2">
                                                    <Activity className="w-4 h-4 text-slate-500" />
                                                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700">On-Page Analysis</h3>
                                                </div>
                                                <div className="p-5">
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Page Title</p>
                                                            <p className="text-sm font-medium text-slate-900 line-clamp-2">{res.seo?.title || 'No Title Found'}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Meta Description</p>
                                                            <p className="text-xs text-slate-600 line-clamp-3 leading-relaxed">{res.seo?.description || 'No Description Found'}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Meta Keywords</p>
                                                            <p className="text-xs text-slate-600 line-clamp-2">{res.seo?.keywords || '-'}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-1">Server Header</p>
                                                            <p className="text-xs text-slate-600 font-mono">{res.seo?.server || '-'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
