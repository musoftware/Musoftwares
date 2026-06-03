import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Shield, Play, Loader2, Folder, CheckCircle } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function ObfuscatorRunner({ tool }: any) {
    const [sourcePath, setSourcePath] = useState('');
    const [outputPath, setOutputPath] = useState('');
    const [level, setLevel] = useState('medium');
    
    const [status, setStatus] = useState<'idle' | 'running' | 'completed' | 'error'>('idle');
    const [progress, setProgress] = useState({ processed: 0, total: 0, currentFile: '', message: '' });
    const [errorMsg, setError] = useState('');
    const [scanResult, setScanResult] = useState<{ count: number, files: string[] } | null>(null);

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
                    
                    if (msg.event === 'obfuscator.progress') {
                        const d = msg.data;
                        if (d.status === 'running') {
                            setStatus('running');
                            setProgress({ processed: d.processed, total: d.total, currentFile: d.currentFile || '', message: d.message || '' });
                        } else if (d.status === 'completed') {
                            setStatus('completed');
                            setProgress(p => ({ ...p, processed: d.processed || p.total, message: d.message || 'Complete' }));
                        } else if (d.status === 'error') {
                            setStatus('error');
                            setError(d.message);
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
                } catch {}
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
                payload: { plugin: 'js-obfuscator', action, data }
            }));
        });
    }, []);

    const handleScan = async () => {
        if (!sourcePath) return alert('Enter source path');
        try {
            const data = await callRPC('obfuscator.scan', { sourcePath });
            setScanResult(data);
            if (!outputPath) {
                setOutputPath(sourcePath + '_obfuscated');
            }
        } catch (e: any) {
            alert(e.message);
        }
    };

    const handleStart = async () => {
        if (!sourcePath || !outputPath) return alert('Enter paths');
        setStatus('running');
        setError('');
        setProgress({ processed: 0, total: scanResult?.count || 0, currentFile: '', message: '' });
        try {
            await callRPC('obfuscator.run', { sourcePath, outputPath, level });
        } catch (e: any) {
            setStatus('error');
            setError(e.message);
        }
    };

    if (!connected) return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center">
            <div className="w-8 h-8 border-2 border-slate-500 border-t-transparent rounded-full animate-spin" />
        </div>
    );

    const pct = progress.total > 0 ? Math.round((progress.processed / progress.total) * 100) : 0;

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans">
            <div className="h-14 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center px-6 sticky top-0 z-10">
                <Shield className="w-5 h-5 text-indigo-500 mr-2" />
                <span className="font-bold text-sm">{__('general.js_code_protector')}</span>
            </div>

            <div className="max-w-3xl mx-auto px-4 py-8">
                <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <h2 className="text-lg font-bold mb-4">Configuration</h2>
                    
                    <div className="space-y-4 mb-6">
                        <div>
                            <label className="text-xs font-bold text-slate-500 uppercase">{__('general.source_file_or_directory')}</label>
                            <div className="flex gap-2 mt-1">
                                <Input 
                                    value={sourcePath} 
                                    onChange={e => setSourcePath(e.target.value)} 
                                    placeholder={__('general.c_projects_my_app_src')} 
                                    className="flex-1"
                                />
                                <Button onClick={handleScan} variant="secondary">Scan</Button>
                            </div>
                        </div>

                        {scanResult && (
                            <div className="p-3 bg-indigo-50 text-indigo-700 rounded-lg text-sm">
                                Found {scanResult.count} JavaScript files.
                            </div>
                        )}

                        <div>
                            <label className="text-xs font-bold text-slate-500 uppercase">{__('general.output_directory')}</label>
                            <Input 
                                value={outputPath} 
                                onChange={e => setOutputPath(e.target.value)} 
                                placeholder={__('general.c_projects_my_app_dist')} 
                                className="mt-1"
                            />
                        </div>

                        <div>
                            <label className="text-xs font-bold text-slate-500 uppercase">{__('general.obfuscation_level')}</label>
                            <Select value={level} onValueChange={(val) => setLevel(val || '')}>
                                <SelectTrigger className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Low (Faster, larger size)</SelectItem>
                                    <SelectItem value="medium">Medium (Recommended)</SelectItem>
                                    <SelectItem value="high">High (Slower, smaller size, max protection)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="pt-4 border-t border-slate-100 dark:border-slate-800">
                        {status === 'running' ? (
                            <div className="space-y-2">
                                <div className="flex justify-between text-xs font-bold text-slate-500">
                                    <span>{progress.processed} / {progress.total} Files</span>
                                    <span>{pct}%</span>
                                </div>
                                <div className="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div className="bg-indigo-500 h-full transition-all duration-300" style={{ width: `${pct}%` }} />
                                </div>
                                <p className="text-[10px] text-slate-400 truncate">{progress.currentFile}</p>
                            </div>
                        ) : status === 'completed' ? (
                            <div className="p-4 bg-emerald-50 text-emerald-700 rounded-lg flex items-center gap-2 font-bold">
                                <CheckCircle className="w-5 h-5" /> Obfuscation Complete! ({progress.total} files)
                            </div>
                        ) : (
                            <Button onClick={handleStart} className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold h-11" disabled={!scanResult || scanResult.count === 0}>
                                <Play className="w-4 h-4 mr-2" />{__('general.start_obfuscation')}</Button>
                        )}
                        
                        {errorMsg && <p className="mt-4 text-sm text-red-500 font-medium">{errorMsg}</p>}
                    </div>
                </div>
            </div>
        </div>
    );
}
