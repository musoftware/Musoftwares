import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Search, Loader2, Trash2, CheckCircle, FileWarning, Copy, HardDrive } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

function formatBytes(bytes: number, decimals = 2) {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

export default function DuplicateFinderRunner({ tool }: any) {
    const [targetPath, setTargetPath] = useState('');
    const [status, setStatus] = useState<'idle' | 'running' | 'completed' | 'error'>('idle');
    const [progress, setProgress] = useState({ message: '', pct: 0 });
    const [duplicates, setDuplicates] = useState<any[]>([]);
    const [errorMsg, setError] = useState('');
    
    // Deletion state
    const [selectedFiles, setSelectedFiles] = useState<Set<string>>(new Set());
    const [deleting, setDeleting] = useState(false);

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
                    
                    if (msg.event === 'duplicate-finder.progress') {
                        const d = msg.data;
                        if (d.status === 'completed') {
                            setStatus('completed');
                            setProgress({ message: d.message, pct: 100 });
                            setDuplicates(d.duplicates || []);
                            
                            // Auto-select all duplicates (but keep original unchecked)
                            const toSelect = new Set<string>();
                            (d.duplicates || []).forEach((group: any) => {
                                group.duplicates.forEach((dup: string) => toSelect.add(dup));
                            });
                            setSelectedFiles(toSelect);
                            
                        } else if (d.status === 'error') {
                            setStatus('error');
                            setError(d.message);
                        } else {
                            setStatus('running');
                            setProgress({ message: d.message, pct: d.pct || 0 });
                        }
                    }

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
    }, []);

    const callRPC = useCallback((action: string, data: any = {}) => {
        return new Promise<any>((resolve, reject) => {
            if (!wsRef.current || wsRef.current.readyState !== WebSocket.OPEN) {
                return reject(new Error('WebSocket is not connected'));
            }
            const id = Math.random().toString(36).substring(7);
            const ws = wsRef.current as any;
            if (!ws._pending) ws._pending = new Map();
            ws._pending.set(id, { resolve, reject });
            ws.send(JSON.stringify({
                type: 'plugin_rpc',
                requestId: id,
                payload: { plugin: 'duplicate-finder', action, data }
            }));
        });
    }, []);

    const handleStart = async () => {
        if (!targetPath) return alert('Enter target path to scan');
        setStatus('running');
        setError('');
        setDuplicates([]);
        setSelectedFiles(new Set());
        setProgress({ message: 'Starting scan...', pct: 0 });
        
        try {
            await callRPC('duplicate-finder.scan', { targetPath });
        } catch (e: any) {
            setStatus('error');
            setError(e.message);
        }
    };

    const toggleSelection = (file: string) => {
        const next = new Set(selectedFiles);
        if (next.has(file)) next.delete(file);
        else next.add(file);
        setSelectedFiles(next);
    };

    const handleDelete = async () => {
        if (selectedFiles.size === 0) return;
        if (!confirm(`Are you sure you want to permanently delete ${selectedFiles.size} files?`)) return;
        
        setDeleting(true);
        try {
            const files = Array.from(selectedFiles);
            const res = await callRPC('duplicate-finder.delete', { files });
            alert(`Deleted ${res.deleted} files successfully. ${res.failed > 0 ? `Failed to delete ${res.failed} files.` : ''}`);
            
            // Remove deleted items from UI
            const newDuplicates = duplicates.map(group => {
                return {
                    ...group,
                    duplicates: group.duplicates.filter((d: string) => !selectedFiles.has(d)),
                    original: selectedFiles.has(group.original) ? null : group.original
                };
            }).filter(group => group.original !== null && group.duplicates.length > 0);
            
            setDuplicates(newDuplicates);
            setSelectedFiles(new Set());
        } catch (e: any) {
            alert('Deletion error: ' + e.message);
        }
        setDeleting(false);
    };

    if (!connected) return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center">
            <div className="w-8 h-8 border-2 border-slate-500 border-t-transparent rounded-full animate-spin" />
        </div>
    );

    const totalWastedSpace = duplicates.reduce((acc, group) => acc + (group.size * group.duplicates.length), 0);
    const selectedWastedSpace = Array.from(selectedFiles).reduce((acc, file) => {
        const group = duplicates.find(g => g.original === file || g.duplicates.includes(file));
        return acc + (group ? group.size : 0);
    }, 0);

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans">
            <div className="h-14 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center px-6 sticky top-0 z-10">
                <Copy className="w-5 h-5 text-amber-500 me-2" />
                <span className="font-bold text-sm">{__('general.duplicate_file_finder')}</span>
            </div>

            <div className="max-w-4xl mx-auto px-4 py-8">
                <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm mb-6">
                    <h2 className="text-lg font-bold mb-4 flex items-center gap-2">
                        <HardDrive className="w-5 h-5 text-slate-400" />{__('general.target_directory')}</h2>
                    
                    <div className="flex gap-2">
                        <Input 
                            value={targetPath} 
                            onChange={e => setTargetPath(e.target.value)} 
                            placeholder={__('general.e_g_d_pictures')} 
                            className="flex-1"
                            disabled={status === 'running'}
                        />
                        <Button onClick={handleStart} className="bg-amber-500 hover:bg-amber-600 text-white font-bold px-6" disabled={status === 'running' || !targetPath}>
                            {status === 'running' ? <Loader2 className="w-4 h-4 me-2 animate-spin" /> : <Search className="w-4 h-4 me-2" />}
                            {status === 'running' ? 'Scanning...' : 'Scan Now'}
                        </Button>
                    </div>
                    
                    {errorMsg && <p className="mt-4 text-sm text-red-500 font-medium">{errorMsg}</p>}
                    
                    {status === 'running' && (
                        <div className="mt-6 space-y-2">
                            <div className="flex justify-between text-xs font-bold text-slate-500">
                                <span>{progress.message}</span>
                                {progress.pct > 0 && <span>{progress.pct}%</span>}
                            </div>
                            {progress.pct > 0 && (
                                <div className="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div className="bg-amber-500 h-full transition-all duration-300" style={{ width: `${progress.pct}%` }} />
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {status === 'completed' && (
                    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div className="p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <h3 className="font-bold text-slate-800 dark:text-slate-200">
                                    Found {duplicates.length} duplicate groups
                                </h3>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    Wasted space: {formatBytes(totalWastedSpace)}
                                </p>
                            </div>
                            
                            {selectedFiles.size > 0 && (
                                <Button 
                                    variant="destructive" 
                                    onClick={handleDelete}
                                    disabled={deleting}
                                    className="gap-2 font-bold shadow-sm"
                                >
                                    {deleting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
                                    Delete {selectedFiles.size} Files ({formatBytes(selectedWastedSpace)})
                                </Button>
                            )}
                        </div>

                        {duplicates.length === 0 ? (
                            <div className="p-12 text-center">
                                <CheckCircle className="w-12 h-12 text-emerald-400 mx-auto mb-3" />
                                <h3 className="text-lg font-bold text-slate-700 dark:text-slate-300">{__('general.clean_no_duplicates_found')}</h3>
                            </div>
                        ) : (
                            <div className="divide-y divide-slate-100 dark:divide-slate-800 max-h-[60vh] overflow-y-auto">
                                {duplicates.map((group, i) => (
                                    <div key={i} className="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <div className="flex items-center gap-3 mb-2">
                                            <Badge variant="outline" className="text-xs font-mono bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border-amber-200">
                                                {formatBytes(group.size)}
                                            </Badge>
                                            <span className="text-[10px] text-slate-400 font-mono">MD5: {group.hash.substring(0, 16)}...</span>
                                        </div>
                                        
                                        <div className="space-y-1.5 ps-2 border-s-2 border-slate-200 dark:border-slate-700 ms-2">
                                            <div className="flex items-start gap-3 p-1.5 rounded-md text-sm">
                                                <input 
                                                    type="checkbox"
                                                    checked={selectedFiles.has(group.original)}
                                                    onChange={() => toggleSelection(group.original)}
                                                    className="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                                                />
                                                <span className="text-slate-600 dark:text-slate-400 break-all flex-1">
                                                    <span className="font-bold text-emerald-600 dark:text-emerald-500 me-2 text-[10px] uppercase">Original</span>
                                                    {group.original}
                                                </span>
                                            </div>
                                            
                                            {group.duplicates.map((dup: string, j: number) => (
                                                <div key={j} className="flex items-start gap-3 p-1.5 rounded-md text-sm bg-red-50/50 dark:bg-red-950/10">
                                                    <input 
                                                        type="checkbox"
                                                        checked={selectedFiles.has(dup)}
                                                        onChange={() => toggleSelection(dup)}
                                                        className="mt-0.5 rounded border-red-300 text-red-500 focus:ring-red-500"
                                                    />
                                                    <span className="text-slate-700 dark:text-slate-300 break-all flex-1">
                                                        <span className="font-bold text-red-500 me-2 text-[10px] uppercase">Duplicate</span>
                                                        {dup}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
