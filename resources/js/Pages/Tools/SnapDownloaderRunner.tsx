import React, { useState, useEffect, useRef } from 'react';
import {
    Video, Download, Play, Square, Settings, History,
    Search, AlertCircle, RefreshCw, Smartphone, 
    Folder, HardDrive, ShieldCheck, CheckCircle2, Terminal, ChevronRight
} from 'lucide-react';

const RUNTIME_HTTP = 'http://127.0.0.1:18400';
const WS_URL       = 'ws://127.0.0.1:18401/ws';

// ── Custom WebSocket Hook for Generic RPC + Broadcasts ────────────────────────
function useRuntimeRPC(pluginSlug: string) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef(new Map());
    const onMessageCallbacks = useRef<Set<Function>>(new Set());

    useEffect(() => {
        let socket: WebSocket;
        let reconnectTimer: NodeJS.Timeout;

        const connect = () => {
            socket = new WebSocket(WS_URL);

            socket.onopen = () => {
                setConnected(true);
                const pingInterval = setInterval(() => {
                    if (socket.readyState === WebSocket.OPEN) {
                        socket.send(JSON.stringify({ type: 'ping' }));
                    }
                }, 10000);
                (socket as any)._pingInterval = pingInterval;
            };

            socket.onclose = () => {
                setConnected(false);
                if ((socket as any)._pingInterval) clearInterval((socket as any)._pingInterval);
                reconnectTimer = setTimeout(connect, 3000);
            };

            socket.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);
                    
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const resolver = pendingRequests.current.get(msg.requestId);
                        if (resolver) {
                            if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload.error));
                            else resolver.resolve(msg.payload);
                            pendingRequests.current.delete(msg.requestId);
                        }
                    }

                    for (const cb of onMessageCallbacks.current) {
                        cb(msg);
                    }

                } catch (err) {}
            };
        };

        connect();
        setWs(socket!);

        return () => {
            if (socket) {
                socket.close();
                if ((socket as any)._pingInterval) clearInterval((socket as any)._pingInterval);
            }
            clearTimeout(reconnectTimer);
        };
    }, []);

    const callRPC = async (action: string, data: any = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            throw new Error('Local Runtime engine not connected. Start the Musoftware desktop client.');
        }

        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(7);
            pendingRequests.current.set(requestId, { resolve, reject });

            ws.send(JSON.stringify({
                type: 'plugin_rpc',
                requestId,
                payload: { plugin: pluginSlug, action, data }
            }));

            setTimeout(() => {
                if (pendingRequests.current.has(requestId)) {
                    pendingRequests.current.get(requestId).reject(new Error('Local engine request timed out'));
                    pendingRequests.current.delete(requestId);
                }
            }, 30000);
        });
    };

    const subscribeToEvents = (cb: Function) => {
        onMessageCallbacks.current.add(cb);
        return () => {
            onMessageCallbacks.current.delete(cb);
        };
    };

    return { connected, callRPC, subscribeToEvents };
}

export default function SnapDownloaderRunner() {
    const { connected, callRPC, subscribeToEvents } = useRuntimeRPC('snapdownloader');

    const [activeWorkspace, setActiveWorkspace] = useState<'dashboard' | 'extraction'>('dashboard');
    
    // Form state
    const [targetUrl, setTargetUrl] = useState('');
    const [filters, setFilters] = useState({
        stories: true,
        spotlights: true,
        highlights: false,
        episodes: false
    });
    
    // Extraction state
    const [isExtracting, setIsExtracting] = useState(false);
    const [logs, setLogs] = useState<{ id: string, type: string, message: string }[]>([]);
    const [successCount, setSuccessCount] = useState(0);
    const logsEndRef = useRef<HTMLDivElement>(null);

    // Auto-scroll logs
    useEffect(() => {
        if (logsEndRef.current) {
            logsEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [logs]);

    // Live WebSocket Event Subscriber
    useEffect(() => {
        const unsubscribe = subscribeToEvents((msg: any) => {
            if (!msg.event?.startsWith('snapdownloader.')) return;

            const newLog = {
                id: Math.random().toString(36).substr(2, 9),
                type: msg.event.split('.')[1], // log, item, error, completed
                message: msg.data.message || msg.data.error || JSON.stringify(msg.data)
            };

            setLogs(prev => [...prev, newLog]);

            if (msg.event === 'snapdownloader.item.success') {
                setSuccessCount(c => c + 1);
            }

            if (msg.event === 'snapdownloader.completed' || msg.event === 'snapdownloader.error') {
                setIsExtracting(false);
            }
        });

        return () => unsubscribe();
    }, [subscribeToEvents]);

    const handleStartExtraction = async () => {
        if (!targetUrl.trim()) return;
        
        setIsExtracting(true);
        setLogs([]);
        setSuccessCount(0);
        setActiveWorkspace('extraction');

        try {
            const response = await fetch(`${RUNTIME_HTTP}/plugins/snapdownloader/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        target: targetUrl.trim(),
                        filters,
                        concurrent: 5
                    }
                })
            });

            if (!response.ok) {
                const err = await response.json();
                throw new Error(err.error || err.message || 'Failed to start extraction');
            }
            
            // The process is now running in the background. Progress comes via WS.
        } catch (err: any) {
            setLogs(prev => [...prev, {
                id: Math.random().toString(36),
                type: 'error',
                message: err.message
            }]);
            setIsExtracting(false);
        }
    };

    if (!connected) {
        return (
            <div className="min-h-[80vh] flex items-center justify-center font-sans bg-[#F9FAFB]">
                <div className="text-center space-y-6 max-w-sm p-8 bg-white border border-slate-100 rounded-3xl shadow-sm">
                    <div className="w-14 h-14 bg-amber-50 border border-amber-100 rounded-2xl flex items-center justify-center mx-auto animate-pulse">
                        <Smartphone className="w-7 h-7 text-amber-500 animate-spin" />
                    </div>
                    <div className="space-y-2">
                        <h3 className="font-bold text-slate-800">Linking Runtime Engine...</h3>
                        <p className="text-xs text-slate-500 leading-relaxed">Ensure the Musoftware desktop client is running on your computer to activate local download services.</p>
                    </div>
                    <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div className="w-1/2 h-full bg-amber-500 rounded-full animate-infinite-loading" />
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#F9FAFB] text-slate-900 font-sans flex antialiased">
            {/* Dashboard Sidebar Navigation */}
            <aside className="w-60 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 sticky top-0 h-screen z-10">
                <div className="p-5 space-y-6">
                    <div className="flex items-center gap-2.5 px-1.5">
                        <div className="w-8 h-8 bg-amber-400 rounded-xl flex items-center justify-center shadow-lg shadow-amber-100">
                            <Smartphone className="w-4.5 h-4.5 text-slate-900" />
                        </div>
                        <div>
                            <span className="font-bold text-sm tracking-tight leading-none block">SnapDownloader</span>
                            <span className="text-[10px] font-bold text-amber-600 uppercase tracking-widest mt-0.5 block">Local Worker</span>
                        </div>
                    </div>

                    <nav className="space-y-1">
                        <button
                            onClick={() => setActiveWorkspace('dashboard')}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-all ${activeWorkspace === 'dashboard' ? 'bg-amber-50 text-amber-700' : 'text-slate-500 hover:text-slate-950 hover:bg-slate-50'}`}
                        >
                            <Download className="w-4 h-4" /> New Extraction
                        </button>
                        <button
                            onClick={() => setActiveWorkspace('extraction')}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-all ${activeWorkspace === 'extraction' ? 'bg-amber-50 text-amber-700' : 'text-slate-500 hover:text-slate-950 hover:bg-slate-50'}`}
                        >
                            <Terminal className="w-4 h-4" /> Live Terminal
                            {isExtracting && (
                                <span className="ml-auto w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                            )}
                        </button>
                    </nav>
                </div>

                <div className="p-4 border-t border-slate-100 bg-slate-50/50">
                    <div className="flex items-center gap-3 px-1.5 py-1">
                        <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse border-2 border-white shadow-sm shadow-emerald-200" />
                        <div>
                            <span className="text-[10px] font-black uppercase text-slate-800 tracking-wider">Engine Connected</span>
                            <span className="text-[9px] text-slate-400 block mt-0.5">Local Storage Active</span>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main Application Area */}
            <main className="flex-1 flex flex-col min-w-0">
                {/* Top header bar */}
                <header className="h-16 border-b border-slate-200 bg-white flex items-center justify-between px-8 sticky top-0 z-10">
                    <div className="flex items-center gap-3">
                        <span className="font-extrabold text-sm capitalize text-slate-900">{activeWorkspace} Workspace</span>
                        <ChevronRight className="w-3.5 h-3.5 text-slate-300" />
                        <span className="text-xs text-slate-400 font-semibold">{isExtracting ? 'Worker Active' : 'Idle'}</span>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-50 border border-slate-150">
                            <HardDrive className="w-3.5 h-3.5 text-slate-500" />
                            <span className="text-[10px] font-bold text-slate-600 uppercase">storage/downloads/</span>
                        </div>
                    </div>
                </header>

                <div className="flex-1 p-8 max-w-5xl w-full mx-auto space-y-6">
                    {/* WORKSPACE 1: NEW EXTRACTION DASHBOARD */}
                    {activeWorkspace === 'dashboard' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-black tracking-tight text-slate-900">Initiate Extraction</h1>
                                <p className="text-sm text-slate-400 mt-1">Download public Snapchat media directly to your hard drive.</p>
                            </div>

                            <div className="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm space-y-8">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black uppercase text-slate-400 tracking-wider">Target Profile</label>
                                    <div className="relative">
                                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300" />
                                        <input 
                                            type="text" 
                                            value={targetUrl}
                                            onChange={e => setTargetUrl(e.target.value)}
                                            placeholder="Enter username (e.g. cristiano) or full profile URL"
                                            className="w-full pl-12 pr-4 py-4 text-sm font-semibold border border-slate-200 focus:border-amber-400 focus:ring-4 focus:ring-amber-50 rounded-2xl outline-none transition-all"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <label className="text-[10px] font-black uppercase text-slate-400 tracking-wider">Media Filters</label>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        {[
                                            { id: 'stories', label: 'Stories' },
                                            { id: 'spotlights', label: 'Spotlights' },
                                            { id: 'highlights', label: 'Highlights' },
                                            { id: 'episodes', label: 'Episodes' },
                                        ].map(f => (
                                            <button
                                                key={f.id}
                                                onClick={() => setFilters(p => ({ ...p, [f.id]: !(p as any)[f.id] }))}
                                                className={`flex items-center gap-3 p-4 rounded-2xl border text-sm font-bold transition-all ${
                                                    (filters as any)[f.id] 
                                                        ? 'bg-amber-50 border-amber-200 text-amber-700 shadow-sm' 
                                                        : 'bg-white border-slate-200 text-slate-500 hover:border-amber-200 hover:bg-slate-50'
                                                }`}
                                            >
                                                <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                                                    (filters as any)[f.id] ? 'border-amber-500 bg-amber-500' : 'border-slate-300'
                                                }`}>
                                                    {(filters as any)[f.id] && <CheckCircle2 className="w-3 h-3 text-white" />}
                                                </div>
                                                {f.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <button 
                                    onClick={handleStartExtraction}
                                    disabled={isExtracting || !targetUrl.trim()}
                                    className="w-full py-4 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white rounded-2xl font-black text-sm uppercase tracking-wide flex items-center justify-center gap-3 transition-all active:scale-95 shadow-lg shadow-slate-900/20"
                                >
                                    {isExtracting ? <RefreshCw className="w-5 h-5 animate-spin" /> : <Play className="w-5 h-5 fill-current" />}
                                    {isExtracting ? 'Worker is Running...' : 'Start Extraction'}
                                </button>
                            </div>

                            {/* Help & Info */}
                            <div className="bg-gradient-to-tr from-amber-500 to-orange-500 text-white rounded-3xl p-8 relative overflow-hidden shadow-xl shadow-amber-200">
                                <div className="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full blur-3xl" />
                                <div className="relative z-10 max-w-xl space-y-4">
                                    <div className="flex items-center gap-2 text-[10px] font-black text-amber-100 tracking-widest uppercase">
                                        <ShieldCheck className="w-4 h-4" /> Secure Local Engine
                                    </div>
                                    <h2 className="text-xl font-bold tracking-tight">Zero-Cloud Media Processing</h2>
                                    <p className="text-sm text-amber-50/90 leading-relaxed">
                                        Your downloads bypass the cloud completely. Our Node.js extraction engine runs directly inside your local Musoftware Runtime, saving high-quality MP4 files straight to your hard drive securely and anonymously.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* WORKSPACE 2: EXTRACTION TERMINAL */}
                    {activeWorkspace === 'extraction' && (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300 h-[calc(100vh-120px)] flex flex-col">
                            <div className="flex items-center justify-between shrink-0">
                                <div>
                                    <h1 className="text-2xl font-black tracking-tight text-slate-900">Live Terminal</h1>
                                    <p className="text-sm text-slate-400 mt-1">Real-time local worker output and download progress.</p>
                                </div>
                                
                                <div className="flex items-center gap-4">
                                    <div className="text-right">
                                        <div className="text-2xl font-black text-emerald-600 leading-none">{successCount}</div>
                                        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Saved Items</div>
                                    </div>
                                </div>
                            </div>

                            <div className="flex-1 bg-slate-950 rounded-3xl shadow-xl border border-slate-800 p-4 flex flex-col overflow-hidden relative">
                                {/* Mac-style window controls */}
                                <div className="flex gap-2 mb-4 px-2">
                                    <div className="w-3 h-3 rounded-full bg-rose-500" />
                                    <div className="w-3 h-3 rounded-full bg-amber-500" />
                                    <div className="w-3 h-3 rounded-full bg-emerald-500" />
                                </div>

                                <div className="flex-1 overflow-y-auto font-mono text-xs p-2 space-y-2 scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent">
                                    {logs.length === 0 ? (
                                        <div className="text-slate-600 flex items-center justify-center h-full">
                                            Awaiting local worker initialization...
                                        </div>
                                    ) : (
                                        logs.map((log) => {
                                            let color = 'text-slate-300';
                                            let prefix = '[-]';
                                            
                                            if (log.type === 'item') {
                                                color = 'text-emerald-400';
                                                prefix = '[+]';
                                            } else if (log.type === 'error') {
                                                color = 'text-rose-400';
                                                prefix = '[!]';
                                            } else if (log.message?.includes('[*]')) {
                                                color = 'text-blue-300';
                                                prefix = '[*]';
                                            }

                                            return (
                                                <div key={log.id} className={`${color} leading-relaxed break-words`}>
                                                    <span className="opacity-50 mr-2">{prefix}</span>
                                                    {log.message?.replace(/\[.\]\s/, '')}
                                                </div>
                                            );
                                        })
                                    )}
                                    <div ref={logsEndRef} />
                                </div>

                                {isExtracting && (
                                    <div className="absolute bottom-6 right-6 flex items-center gap-2 bg-indigo-500/10 text-indigo-300 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full border border-indigo-500/20 backdrop-blur-sm">
                                        <RefreshCw className="w-3.5 h-3.5 animate-spin" /> Engine Running
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </main>
        </div>
    );
}
