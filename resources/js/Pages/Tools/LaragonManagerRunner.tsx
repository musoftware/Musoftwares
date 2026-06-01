import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Folder, Code, Database, RefreshCw, FolderOpen,
    Play, Square, Settings, ExternalLink, Globe2
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function LaragonManagerRunner({ tool }: any) {
    const [projects, setProjects] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [errorMsg, setError] = useState('');
    const [rootPath, setRootPath] = useState('C:\\laragon\\www');
    
    const wsRef = useRef<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);

    // ── WebSocket ──
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
                    // RPC responses
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

    // ── RPC helper ──
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
                payload: {
                    plugin: 'laragon-manager',
                    action,
                    data
                }
            }));
        });
    }, []);

    const fetchProjects = useCallback(async () => {
        setLoading(true);
        setError('');
        try {
            const data = await callRPC('laragon.projects.list', { path: rootPath });
            if (data?.projects) {
                setProjects(data.projects);
            }
        } catch (e: any) {
            setError(e.message);
        }
        setLoading(false);
    }, [callRPC, rootPath]);

    useEffect(() => {
        if (connected) fetchProjects();
    }, [connected, fetchProjects]);

    const openInVSCode = async (path: string) => {
        try {
            await callRPC('laragon.project.open_vscode', { path });
        } catch (e: any) {
            alert('Failed to open VSCode: ' + e.message);
        }
    };

    if (!connected) return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center font-sans">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">{__('general.connecting_to_runtime')}</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans">
            <div className="h-14 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center shadow-sm">
                        <Folder className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 dark:text-slate-200 tracking-tight">{__('general.local_server_manager')}</span>
                </div>
                <div className="flex items-center gap-3">
                    <Input 
                        value={rootPath}
                        onChange={(e) => setRootPath(e.target.value)}
                        placeholder={__('general.c_laragon_www')}
                        className="h-8 w-48 text-xs font-mono"
                    />
                    <Button
                        variant="outline"
                        onClick={fetchProjects}
                        disabled={loading}
                        className="h-8 gap-1.5 px-3"
                    >
                        <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} /> Refresh
                    </Button>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 py-6">
                {errorMsg && (
                    <div className="mb-6 p-4 bg-red-50 text-red-600 rounded-xl text-sm font-medium">
                        {errorMsg}
                    </div>
                )}
                
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    {projects.map((p, i) => (
                        <div key={i} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                            <div className="flex items-start justify-between mb-3">
                                <div>
                                    <h3 className="font-bold text-slate-800 dark:text-slate-100 text-lg truncate" title={p.name}>
                                        {p.name}
                                    </h3>
                                    <p className="text-[10px] font-mono text-slate-400 truncate mt-0.5" title={p.path}>
                                        {p.path}
                                    </p>
                                </div>
                                <Badge variant="outline" className="bg-slate-50 dark:bg-slate-800 text-[10px]">
                                    {p.type}
                                </Badge>
                            </div>
                            
                            <div className="flex gap-2 mb-4">
                                {p.hasEnv && <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100 px-1.5 py-0 text-[9px] rounded">.env</Badge>}
                            </div>

                            <div className="flex items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 text-xs h-8 bg-slate-900 text-white hover:bg-slate-800"
                                    onClick={() => openInVSCode(p.path)}
                                >
                                    <Code className="w-3.5 h-3.5 mr-1.5" /> VSCode
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 text-xs h-8"
                                    onClick={() => window.open(p.localUrl, '_blank')}
                                >
                                    <Globe2 className="w-3.5 h-3.5 mr-1.5 text-blue-500" />{__('general.open_web')}</Button>
                            </div>
                        </div>
                    ))}
                </div>
                
                {projects.length === 0 && !loading && !errorMsg && (
                    <div className="text-center py-20">
                        <FolderOpen className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                        <h3 className="text-lg font-bold text-slate-600">{__('general.no_projects_found_1')}</h3>
                        <p className="text-slate-400 text-sm mt-1">{__('general.make_sure_laragon_is_installed_at_c_laragon_www')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}
