import React, { useState, useEffect, useRef } from 'react';
import {
    Target, Play, Square, Download, FileText,
    AlertCircle, RefreshCw, Mail, ShoppingCart, CheckCircle, XCircle
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function DomainProspectorRunner({ tool }: any) {
    const [domainsInput, setDomainsInput] = useState('');
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
                
                if (msg.event === 'task.progress' && msg.data?.taskId === taskId) {
                    if (msg.data.percent !== undefined) setProgress(msg.data.percent);
                    if (msg.data.message) {
                        setProgressMsg(msg.data.message);
                        setLogs(prev => [msg.data.message, ...prev].slice(0, 50));
                    }
                }
                
                if (msg.event === 'domain_prospected') {
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
        const domains = domainsInput.split('\n').map(d => d.trim()).filter(Boolean);
        if (domains.length === 0) return;

        setStatus('running'); setProgress(0); setProgressMsg('Initializing Prospecting Campaign...'); setResults([]); setLogs([]); setError('');

        try {
            const res = await fetch(`${getRuntimeHttp()}/plugins/domain-prospector/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { domains } }),
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
        const headers = ["Domain", "Status", "Registered", "For Sale", "Emails", "Title"];
        
        let csvContent = "data:text/csv;charset=utf-8," 
            + headers.join(",") + "\n"
            + results.map(r => {
                return [
                    r.domain, r.status, r.isRegistered ? 'Yes' : 'No', r.isForSale ? 'Yes' : 'No', 
                    (r.emails || []).join('; '), r.title || ''
                ].map(field => `"${(field || '').toString().replace(/"/g, '""')}"`).join(",");
            }).join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `domain_prospects.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <div className="min-h-screen bg-slate-50 font-sans flex flex-col">
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10 shrink-0">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-indigo-600 to-rose-600 rounded-lg flex items-center justify-center shadow-sm">
                        <Target className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">Domain Prospector</span>
                </div>
                <div className="flex items-center gap-3">
                    <Button variant="outline" onClick={exportCsv} disabled={results.length === 0} className="h-8 text-xs font-bold gap-1.5">
                        <Download className="w-3.5 h-3.5" /> Export CSV
                    </Button>
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Prospecting...' : status === 'done' ? 'Finished' : 'Ready'}
                    </Badge>
                </div>
            </div>

            <div className="flex flex-1 overflow-hidden">
                {/* Sidebar Configuration */}
                <div className="w-80 bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto">
                    <div className="p-5 space-y-6">
                        <div>
                            <h2 className="text-lg font-bold tracking-tight text-slate-900">Campaign Setup</h2>
                            <p className="text-xs text-slate-500 mt-1">Batch analyze domains for acquisition and seller contact extraction.</p>
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-bold text-slate-700 flex justify-between">
                                    <span>Target Domains</span>
                                    <span className="text-slate-400 font-normal">{domainsInput.split('\n').filter(d => d.trim()).length} total</span>
                                </Label>
                                <textarea
                                    value={domainsInput}
                                    onChange={e => setDomainsInput(e.target.value)}
                                    placeholder="example.com&#10;awesome-domain.io&#10;acquire-me.net"
                                    className="w-full h-48 p-3 text-sm bg-slate-50 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                                    disabled={status === 'running'}
                                />
                            </div>

                            {status === 'running' ? (
                                <Button
                                    onClick={handleStop}
                                    variant="destructive"
                                    className="w-full h-11 rounded-xl text-sm font-bold shadow-md gap-2"
                                >
                                    <Square className="w-4 h-4 fill-current" />
                                    Stop Prospecting
                                </Button>
                            ) : (
                                <Button
                                    onClick={handleStart}
                                    disabled={!domainsInput.trim()}
                                    className="w-full h-11 bg-gradient-to-r from-indigo-600 to-rose-600 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md gap-2"
                                >
                                    <Play className="w-4 h-4 fill-current" />
                                    Start Prospecting
                                </Button>
                            )}

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
                                    <div className="h-full bg-gradient-to-r from-indigo-500 to-rose-500 transition-all duration-300 rounded-full" style={{ width: `${progress}%` }} />
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="flex-1 overflow-auto p-6">
                        {results.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                                <div className="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center">
                                    <FileText className="w-8 h-8 text-slate-300" />
                                </div>
                                <p className="text-sm font-medium">Add domains and start the campaign to prospect.</p>
                            </div>
                        ) : (
                            <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full animate-in fade-in duration-500">
                                <div className="overflow-x-auto flex-1">
                                    <table className="w-full text-left border-collapse min-w-[800px]">
                                        <thead>
                                            <tr className="bg-slate-50 border-b border-slate-200 text-[10px] uppercase tracking-wider font-black text-slate-500 sticky top-0 z-10">
                                                <th className="px-4 py-3 whitespace-nowrap">Domain</th>
                                                <th className="px-4 py-3 whitespace-nowrap">Status</th>
                                                <th className="px-4 py-3 whitespace-nowrap">For Sale</th>
                                                <th className="px-4 py-3 w-64">Contact Emails</th>
                                                <th className="px-4 py-3 whitespace-nowrap">Page Title</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {results.map((r, i) => (
                                                <tr key={i} className="hover:bg-slate-50/50 transition-colors group">
                                                    <td className="px-4 py-3 align-middle">
                                                        <a href={`http://${r.domain}`} target="_blank" rel="noopener noreferrer" className="text-sm font-bold text-slate-900 hover:text-indigo-600 hover:underline">{r.domain}</a>
                                                    </td>
                                                    <td className="px-4 py-3 align-middle">
                                                        {r.isRegistered ? (
                                                            <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-200 gap-1 text-[10px]"><CheckCircle className="w-3 h-3" /> Registered</Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 gap-1 text-[10px]"><ShoppingCart className="w-3 h-3" /> Available</Badge>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 align-middle">
                                                        {r.isForSale ? (
                                                            <Badge className="bg-indigo-100 text-indigo-700 hover:bg-indigo-100 border-indigo-200 text-[10px]">Likely For Sale</Badge>
                                                        ) : (
                                                            <span className="text-xs text-slate-400">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 align-middle">
                                                        {r.emails && r.emails.length > 0 ? (
                                                            <div className="flex flex-wrap gap-1">
                                                                {r.emails.map((e: string, j: number) => (
                                                                    <span key={j} className="inline-flex items-center gap-1 text-[11px] font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                                        <Mail className="w-3 h-3 text-slate-400" />
                                                                        {e}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs text-slate-400">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 align-middle">
                                                        <p className="text-[11px] text-slate-600 line-clamp-2" title={r.title}>{r.title || '-'}</p>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="bg-slate-50 border-t border-slate-200 px-4 py-2 flex items-center justify-between text-[11px] font-bold text-slate-500">
                                    <span>Total Prospected: <span className="text-slate-900">{results.length}</span></span>
                                    <span>Emails Found: <span className="text-slate-900">{results.reduce((acc, r) => acc + (r.emails?.length || 0), 0)}</span></span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
