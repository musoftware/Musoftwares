import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Video, Download, Play, Square, Settings,
    Search, RefreshCw, Smartphone,
    Folder, HardDrive, ShieldCheck, CheckCircle2, Terminal,
    ChevronRight, Clock, Zap, List, Activity, Trash2, FolderOpen,
    AlertTriangle, XCircle, CheckCircle, Pause, MoreVertical,
    FileVideo, Image as ImageIcon, Film, ArrowRight, StopCircle,
    Eye, ChevronDown, ChevronUp, History, LayoutGrid,
} from 'lucide-react';

// ─── Runtime Helpers ─────────────────────────────────────────────────────────
const getRuntimeHost = () =>
    typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

// ─── WebSocket RPC Hook ────────────────────────────────────────────────────────
function useRuntimeRPC(pluginSlug: string) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef(new Map<string, { resolve: Function; reject: Function }>());
    const onMessageCallbacks = useRef<Set<Function>>(new Set());

    useEffect(() => {
        let socket: WebSocket;
        let reconnectTimer: ReturnType<typeof setTimeout>;

        const connect = () => {
            socket = new WebSocket(getWsUrl());

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
                            if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload?.error || 'RPC Error'));
                            else resolver.resolve(msg.payload);
                            pendingRequests.current.delete(msg.requestId);
                        }
                    }
                    for (const cb of onMessageCallbacks.current) cb(msg);
                } catch (_) {}
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

    const callRPC = useCallback(async (action: string, data: any = {}): Promise<any> => {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            throw new Error('Runtime not connected');
        }
        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(7);
            pendingRequests.current.set(requestId, { resolve, reject });
            ws.send(JSON.stringify({
                type: 'plugin_rpc',
                requestId,
                payload: { plugin: pluginSlug, action, data },
            }));
            setTimeout(() => {
                if (pendingRequests.current.has(requestId)) {
                    pendingRequests.current.get(requestId)!.reject(new Error('Request timed out'));
                    pendingRequests.current.delete(requestId);
                }
            }, 30000);
        });
    }, [ws, pluginSlug]);

    const subscribeToEvents = useCallback((cb: Function) => {
        onMessageCallbacks.current.add(cb);
        return () => onMessageCallbacks.current.delete(cb);
    }, []);

    return { connected, callRPC, subscribeToEvents };
}

// ─── Types ────────────────────────────────────────────────────────────────────
interface Process {
    id: string;
    target: string;
    status: 'running' | 'completed' | 'error' | 'stopped';
    progress: number;
    progressMsg: string;
    startTime: string;
    successCount: number;
    totalItems: number;
    logs: { ts: number; level: string; message: string }[];
    outputDir: string | null;
    endTime?: string;
}

interface QueueJob {
    id: string;
    target: string;
    status: 'pending' | 'running';
    addedAt: string;
    filters: Record<string, boolean>;
}

interface Folder {
    name: string;
    fileCount: number;
    totalSize: number;
    path: string;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatBytes(b: number) {
    if (b < 1024) return `${b} B`;
    if (b < 1024 * 1024) return `${(b / 1024).toFixed(1)} KB`;
    if (b < 1024 * 1024 * 1024) return `${(b / 1024 / 1024).toFixed(1)} MB`;
    return `${(b / 1024 / 1024 / 1024).toFixed(2)} GB`;
}

function timeAgo(iso: string) {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
}

function StatusBadge({ status }: { status: string }) {
    const map: Record<string, { color: string; icon: React.ReactNode; label: string }> = {
        running:   { color: 'bg-blue-500/10 text-blue-400 border-blue-500/20',    icon: <RefreshCw className="w-3 h-3 animate-spin" />, label: 'Running' },
        completed: { color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', icon: <CheckCircle className="w-3 h-3" />,   label: 'Done' },
        error:     { color: 'bg-rose-500/10 text-rose-400 border-rose-500/20',    icon: <XCircle className="w-3 h-3" />,        label: 'Error' },
        stopped:   { color: 'bg-slate-500/10 text-slate-400 border-slate-500/20', icon: <Square className="w-3 h-3" />,         label: 'Stopped' },
        pending:   { color: 'bg-amber-500/10 text-amber-400 border-amber-500/20', icon: <Clock className="w-3 h-3" />,          label: 'Queued' },
    };
    const cfg = map[status] || map.pending;
    return (
        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wider ${cfg.color}`}>
            {cfg.icon} {cfg.label}
        </span>
    );
}

// ─── Main Component ────────────────────────────────────────────────────────────
export default function SnapDownloaderRunner() {
    const { connected, callRPC, subscribeToEvents } = useRuntimeRPC('snapdownloader');

    type Workspace = 'new' | 'active' | 'queue' | 'folders' | 'history';
    const [activeWorkspace, setActiveWorkspace] = useState<Workspace>('new');

    // Form state
    const [targetUrl, setTargetUrl] = useState('');
    const [filters, setFilters] = useState({ stories: true, spotlights: true, highlights: false, episodes: false });
    const [isQueuing, setIsQueuing] = useState(false);
    const [formError, setFormError] = useState('');

    // Data state
    const [activeProcesses, setActiveProcesses] = useState<Process[]>([]);
    const [queue, setQueue] = useState<QueueJob[]>([]);
    const [folders, setFolders] = useState<Folder[]>([]);
    const [history, setHistory] = useState<Process[]>([]);
    const [selectedProcessId, setSelectedProcessId] = useState<string | null>(null);
    const [processLogs, setProcessLogs] = useState<{ ts: number; level: string; message: string }[]>([]);
    const [logsExpanded, setLogsExpanded] = useState(false);
    const logsEndRef = useRef<HTMLDivElement>(null);

    // Polling
    const pollTimer = useRef<ReturnType<typeof setInterval> | null>(null);

    const loadAll = useCallback(async () => {
        try {
            const [activeRes, queueRes, histRes] = await Promise.all([
                callRPC('get_active', {}),
                callRPC('get_queue', {}),
                callRPC('get_history', {}),
            ]);
            setActiveProcesses(activeRes.processes || []);
            setQueue(queueRes.queue || []);
            setHistory(histRes.history || []);
        } catch (_) {}
    }, [callRPC]);

    const loadFolders = useCallback(async () => {
        try {
            const res = await callRPC('get_folders', {});
            setFolders(res.folders || []);
        } catch (_) {}
    }, [callRPC]);

    useEffect(() => {
        if (!connected) return;
        loadAll();
        pollTimer.current = setInterval(loadAll, 3000);
        return () => { if (pollTimer.current) clearInterval(pollTimer.current); };
    }, [connected, loadAll]);

    useEffect(() => {
        if (activeWorkspace === 'folders') loadFolders();
    }, [activeWorkspace, loadFolders]);

    // Load logs for selected process
    useEffect(() => {
        if (!selectedProcessId || !connected) return;
        callRPC('get_logs', { processId: selectedProcessId })
            .then(res => setProcessLogs(res.logs || []))
            .catch(() => {});
    }, [selectedProcessId, activeProcesses, callRPC, connected]);

    useEffect(() => {
        if (logsEndRef.current) {
            logsEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [processLogs]);

    // Subscribe to WS events for live updates
    useEffect(() => {
        const unsub = subscribeToEvents((msg: any) => {
            if (!msg.event?.startsWith('snapdownloader.')) return;
            if (['snapdownloader.process_started', 'snapdownloader.process_completed',
                 'snapdownloader.process_error', 'snapdownloader.process_stopped',
                 'snapdownloader.progress', 'snapdownloader.log'].includes(msg.event)) {
                loadAll();
            }
        });
        return () => unsub();
    }, [subscribeToEvents, loadAll]);

    // Actions
    const handleQueueAdd = async () => {
        if (!targetUrl.trim()) return;
        setFormError('');
        setIsQueuing(true);
        try {
            await callRPC('queue_add', { target: targetUrl.trim(), filters, concurrent: 5 });
            setTargetUrl('');
            setActiveWorkspace('active');
            loadAll();
        } catch (err: any) {
            setFormError(err.message);
        } finally {
            setIsQueuing(false);
        }
    };

    const handleStopProcess = async (processId: string) => {
        try {
            await callRPC('stop_process', { processId });
            loadAll();
        } catch (err: any) {
            alert(err.message);
        }
    };

    const handleQueueRemove = async (jobId: string) => {
        try {
            await callRPC('queue_remove', { jobId });
            loadAll();
        } catch (err: any) {
            alert(err.message);
        }
    };

    const handleOpenFolder = async (subdir?: string) => {
        try {
            await callRPC('open_folder', { subdir });
        } catch (err: any) {
            alert(err.message);
        }
    };

    const handleClearHistory = async () => {
        try {
            await callRPC('clear_history', {});
            setHistory([]);
        } catch (_) {}
    };

    const selectProcess = async (proc: Process) => {
        setSelectedProcessId(proc.id);
        setLogsExpanded(true);
        const res = await callRPC('get_logs', { processId: proc.id }).catch(() => ({ logs: [] }));
        setProcessLogs(res.logs || []);
    };

    // ─── Connection Loading Screen ────────────────────────────────────────────
    if (!connected) {
        return (
            <div className="min-h-[80vh] flex items-center justify-center" style={{ background: '#0f1117' }}>
                <div className="text-center space-y-6 max-w-sm p-10 rounded-3xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
                    <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto relative" style={{ background: 'linear-gradient(135deg, #f59e0b, #d97706)' }}>
                        <Smartphone className="w-8 h-8 text-white" />
                        <span className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center">
                            <span className="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse" />
                        </span>
                    </div>
                    <div>
                        <h3 className="font-bold text-white text-lg">Connecting to Runtime...</h3>
                        <p className="text-xs text-slate-500 mt-2 leading-relaxed">Make sure the Musoftware desktop client is running on your machine.</p>
                    </div>
                    <div className="h-1 rounded-full overflow-hidden" style={{ background: 'rgba(255,255,255,0.05)' }}>
                        <div className="h-full rounded-full animate-pulse" style={{ width: '60%', background: 'linear-gradient(90deg, #f59e0b, #d97706)' }} />
                    </div>
                </div>
            </div>
        );
    }

    const runningCount = activeProcesses.filter(p => p.status === 'running').length;
    const queuedCount = queue.filter(j => j.status === 'pending').length;

    // ─── Sidebar Nav ─────────────────────────────────────────────────────────
    const navItems: { id: Workspace; icon: React.ReactNode; label: string; badge?: number }[] = [
        { id: 'new',     icon: <Download className="w-4 h-4" />,    label: 'New Download' },
        { id: 'active',  icon: <Activity className="w-4 h-4" />,    label: 'Active',   badge: runningCount },
        { id: 'queue',   icon: <List className="w-4 h-4" />,        label: 'Queue',    badge: queuedCount },
        { id: 'folders', icon: <FolderOpen className="w-4 h-4" />,  label: 'Saved Files' },
        { id: 'history', icon: <History className="w-4 h-4" />,     label: 'History' },
    ];

    return (
        <div className="flex h-screen overflow-hidden text-sm antialiased" style={{ background: '#0f1117', color: '#e2e8f0', fontFamily: "'Inter', system-ui, sans-serif" }}>

            {/* ─── Sidebar ─────────────────────────────────────────────────── */}
            <aside className="w-56 flex flex-col border-r shrink-0" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                {/* Logo */}
                <div className="p-5 border-b" style={{ borderColor: 'rgba(255,255,255,0.06)' }}>
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)' }}>
                            <Film className="w-4.5 h-4.5 text-white" />
                        </div>
                        <div>
                            <div className="font-bold text-white text-sm leading-none">SnapDownloader</div>
                            <div className="text-[10px] mt-0.5" style={{ color: '#f59e0b' }}>Local Engine</div>
                        </div>
                    </div>
                </div>

                {/* Nav */}
                <nav className="flex-1 p-3 space-y-0.5">
                    {navItems.map(item => {
                        const active = activeWorkspace === item.id;
                        return (
                            <button
                                key={item.id}
                                onClick={() => setActiveWorkspace(item.id)}
                                className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group"
                                style={{
                                    background: active ? 'rgba(245,158,11,0.12)' : 'transparent',
                                    color: active ? '#f59e0b' : 'rgba(255,255,255,0.4)',
                                }}
                            >
                                <span style={{ color: active ? '#f59e0b' : 'rgba(255,255,255,0.3)' }}>{item.icon}</span>
                                <span className="flex-1 text-left">{item.label}</span>
                                {item.badge !== undefined && item.badge > 0 && (
                                    <span className="w-5 h-5 rounded-full text-[9px] font-black flex items-center justify-center" style={{ background: '#f59e0b', color: '#000' }}>
                                        {item.badge}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </nav>

                {/* Runtime Status */}
                <div className="p-4 border-t" style={{ borderColor: 'rgba(255,255,255,0.06)' }}>
                    <div className="flex items-center gap-2.5 px-2">
                        <span className="w-2 h-2 rounded-full animate-pulse" style={{ background: '#10b981', boxShadow: '0 0 6px #10b981' }} />
                        <div>
                            <div className="text-[10px] font-bold text-white">Engine Online</div>
                            <div className="text-[9px] mt-0.5" style={{ color: 'rgba(255,255,255,0.3)' }}>Local Runtime Active</div>
                        </div>
                    </div>
                </div>
            </aside>

            {/* ─── Main Area ───────────────────────────────────────────────── */}
            <main className="flex-1 flex flex-col min-w-0 overflow-hidden">

                {/* Header */}
                <header className="h-14 flex items-center justify-between px-6 border-b shrink-0" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                    <div className="flex items-center gap-2 text-xs">
                        <span className="font-bold text-white capitalize">{navItems.find(n => n.id === activeWorkspace)?.label}</span>
                        <ChevronRight className="w-3.5 h-3.5" style={{ color: 'rgba(255,255,255,0.2)' }} />
                        <span style={{ color: 'rgba(255,255,255,0.35)' }}>
                            {runningCount > 0 ? `${runningCount} active process${runningCount > 1 ? 'es' : ''}` : 'No active processes'}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <button onClick={loadAll} className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-semibold transition-all" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}>
                            <RefreshCw className="w-3 h-3" /> Refresh
                        </button>
                    </div>
                </header>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║     NEW DOWNLOAD WORKSPACE   ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'new' && (
                        <div className="max-w-2xl mx-auto space-y-6">
                            <div>
                                <h1 className="text-2xl font-black text-white">New Download</h1>
                                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Queue a Snapchat profile for local extraction</p>
                            </div>

                            {/* Form Card */}
                            <div className="rounded-2xl border p-6 space-y-6" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                {/* URL Input */}
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>Target Profile</label>
                                    <div className="relative">
                                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: 'rgba(255,255,255,0.25)' }} />
                                        <input
                                            type="text"
                                            value={targetUrl}
                                            onChange={e => { setTargetUrl(e.target.value); setFormError(''); }}
                                            onKeyDown={e => e.key === 'Enter' && handleQueueAdd()}
                                            placeholder="Username (e.g. cristiano) or full Snapchat URL"
                                            className="w-full pl-11 pr-4 py-3.5 rounded-xl text-sm font-medium outline-none transition-all"
                                            style={{
                                                background: 'rgba(255,255,255,0.04)',
                                                border: `1px solid ${formError ? '#f43f5e' : 'rgba(255,255,255,0.08)'}`,
                                                color: '#fff',
                                            }}
                                        />
                                    </div>
                                    {formError && <p className="text-xs" style={{ color: '#f43f5e' }}>{formError}</p>}
                                </div>

                                {/* Media Filters */}
                                <div className="space-y-3">
                                    <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>Media Types</label>
                                    <div className="grid grid-cols-2 gap-2.5">
                                        {[
                                            { id: 'stories',    label: 'Stories',    icon: <Video className="w-4 h-4" /> },
                                            { id: 'spotlights', label: 'Spotlights', icon: <Zap className="w-4 h-4" /> },
                                            { id: 'highlights', label: 'Highlights', icon: <Film className="w-4 h-4" /> },
                                            { id: 'episodes',   label: 'Episodes',   icon: <FileVideo className="w-4 h-4" /> },
                                        ].map(f => {
                                            const active = (filters as any)[f.id];
                                            return (
                                                <button
                                                    key={f.id}
                                                    onClick={() => setFilters(p => ({ ...p, [f.id]: !(p as any)[f.id] }))}
                                                    className="flex items-center gap-3 p-3.5 rounded-xl border transition-all text-left"
                                                    style={{
                                                        background: active ? 'rgba(245,158,11,0.1)' : 'rgba(255,255,255,0.02)',
                                                        borderColor: active ? 'rgba(245,158,11,0.4)' : 'rgba(255,255,255,0.06)',
                                                        color: active ? '#f59e0b' : 'rgba(255,255,255,0.4)',
                                                    }}
                                                >
                                                    {f.icon}
                                                    <span className="text-xs font-bold">{f.label}</span>
                                                    {active && <CheckCircle2 className="w-3.5 h-3.5 ml-auto" />}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* CTA */}
                                <button
                                    onClick={handleQueueAdd}
                                    disabled={isQueuing || !targetUrl.trim()}
                                    className="w-full py-3.5 rounded-xl font-black text-sm uppercase tracking-widest flex items-center justify-center gap-3 transition-all active:scale-95 disabled:opacity-40"
                                    style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)', color: '#000' }}
                                >
                                    {isQueuing ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4 fill-current" />}
                                    {isQueuing ? 'Adding to Queue...' : 'Start Download'}
                                </button>
                            </div>

                            {/* Info Banner */}
                            <div className="rounded-2xl p-5 flex items-start gap-4" style={{ background: 'rgba(245,158,11,0.07)', border: '1px solid rgba(245,158,11,0.15)' }}>
                                <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'rgba(245,158,11,0.15)' }}>
                                    <ShieldCheck className="w-4.5 h-4.5" style={{ color: '#f59e0b' }} />
                                </div>
                                <div>
                                    <div className="font-bold text-white text-xs">Zero-Cloud Processing</div>
                                    <p className="text-[11px] mt-1 leading-relaxed" style={{ color: 'rgba(255,255,255,0.4)' }}>
                                        Downloads run entirely on your machine via the local Node.js engine. Files are saved directly to your hard drive — no cloud upload.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    ACTIVE PROCESSES          ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'active' && (
                        <div className="space-y-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-black text-white">Active Processes</h1>
                                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Monitor and control running downloads in real-time</p>
                                </div>
                            </div>

                            {activeProcesses.length === 0 && (
                                <div className="flex flex-col items-center justify-center py-20 space-y-4 rounded-2xl border" style={{ borderColor: 'rgba(255,255,255,0.05)', background: 'rgba(255,255,255,0.02)' }}>
                                    <div className="w-14 h-14 rounded-2xl flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.05)' }}>
                                        <Activity className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold" style={{ color: 'rgba(255,255,255,0.4)' }}>No active processes</div>
                                        <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.2)' }}>Start a download from the New Download tab</p>
                                    </div>
                                    <button onClick={() => setActiveWorkspace('new')} className="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all" style={{ background: 'rgba(245,158,11,0.15)', color: '#f59e0b' }}>
                                        <Download className="w-3.5 h-3.5" /> New Download
                                    </button>
                                </div>
                            )}

                            <div className="space-y-4">
                                {activeProcesses.map(proc => (
                                    <div key={proc.id} className="rounded-2xl border overflow-hidden" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                        {/* Process Header */}
                                        <div className="p-5">
                                            <div className="flex items-start gap-4">
                                                {/* Icon */}
                                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5" style={{
                                                    background: proc.status === 'running' ? 'rgba(59,130,246,0.15)' :
                                                               proc.status === 'completed' ? 'rgba(16,185,129,0.15)' :
                                                               proc.status === 'error' ? 'rgba(244,63,94,0.15)' : 'rgba(255,255,255,0.05)',
                                                }}>
                                                    {proc.status === 'running' && <RefreshCw className="w-4.5 h-4.5 animate-spin" style={{ color: '#3b82f6' }} />}
                                                    {proc.status === 'completed' && <CheckCircle className="w-4.5 h-4.5" style={{ color: '#10b981' }} />}
                                                    {proc.status === 'error' && <XCircle className="w-4.5 h-4.5" style={{ color: '#f43f5e' }} />}
                                                    {proc.status === 'stopped' && <Square className="w-4.5 h-4.5" style={{ color: 'rgba(255,255,255,0.4)' }} />}
                                                </div>

                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-3 flex-wrap">
                                                        <span className="font-bold text-white truncate max-w-xs">{proc.target}</span>
                                                        <StatusBadge status={proc.status} />
                                                    </div>
                                                    <div className="text-[11px] mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>
                                                        Started {timeAgo(proc.startTime)}
                                                        {proc.totalItems > 0 && ` · ${proc.successCount}/${proc.totalItems} items`}
                                                    </div>
                                                </div>

                                                {/* Actions */}
                                                <div className="flex items-center gap-2 shrink-0">
                                                    <button
                                                        onClick={() => selectProcess(proc)}
                                                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.6)' }}
                                                    >
                                                        <Terminal className="w-3 h-3" /> Logs
                                                    </button>
                                                    {proc.outputDir && (
                                                        <button
                                                            onClick={() => handleOpenFolder(proc.target.replace(/^@/, '').replace(/.*\//, ''))}
                                                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                                                            style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.6)' }}
                                                        >
                                                            <FolderOpen className="w-3 h-3" /> Folder
                                                        </button>
                                                    )}
                                                    {proc.status === 'running' && (
                                                        <button
                                                            onClick={() => handleStopProcess(proc.id)}
                                                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                                            style={{ background: 'rgba(244,63,94,0.15)', color: '#f43f5e', border: '1px solid rgba(244,63,94,0.25)' }}
                                                        >
                                                            <StopCircle className="w-3 h-3" /> Stop
                                                        </button>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Progress Bar */}
                                            {proc.status === 'running' && (
                                                <div className="mt-4 space-y-2">
                                                    <div className="flex items-center justify-between text-[10px]">
                                                        <span style={{ color: 'rgba(255,255,255,0.4)' }}>{proc.progressMsg}</span>
                                                        <span className="font-bold" style={{ color: '#3b82f6' }}>{proc.progress}%</span>
                                                    </div>
                                                    <div className="h-1.5 rounded-full overflow-hidden" style={{ background: 'rgba(255,255,255,0.06)' }}>
                                                        <div
                                                            className="h-full rounded-full transition-all duration-500"
                                                            style={{ width: `${proc.progress}%`, background: 'linear-gradient(90deg, #3b82f6, #6366f1)' }}
                                                        />
                                                    </div>
                                                </div>
                                            )}

                                            {/* Stats Pills */}
                                            <div className="flex items-center gap-2 mt-3 flex-wrap">
                                                <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold" style={{ background: 'rgba(16,185,129,0.1)', color: '#10b981' }}>
                                                    <CheckCircle className="w-3 h-3" /> {proc.successCount} saved
                                                </div>
                                                {proc.totalItems > 0 && (
                                                    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.4)' }}>
                                                        <Film className="w-3 h-3" /> {proc.totalItems} total
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Inline Terminal Logs */}
                                        {selectedProcessId === proc.id && logsExpanded && (
                                            <div className="border-t" style={{ borderColor: 'rgba(255,255,255,0.05)', background: '#0a0c13' }}>
                                                <div className="flex items-center justify-between px-4 py-2 border-b" style={{ borderColor: 'rgba(255,255,255,0.04)' }}>
                                                    <div className="flex gap-1.5">
                                                        <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f43f5e' }} />
                                                        <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f59e0b' }} />
                                                        <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#10b981' }} />
                                                    </div>
                                                    <span className="text-[10px] font-mono" style={{ color: 'rgba(255,255,255,0.2)' }}>stdout — {proc.target}</span>
                                                    <button onClick={() => setLogsExpanded(false)} style={{ color: 'rgba(255,255,255,0.3)' }}>
                                                        <ChevronUp className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                                <div className="h-56 overflow-y-auto p-4 font-mono text-[11px] space-y-1">
                                                    {processLogs.length === 0 ? (
                                                        <div style={{ color: 'rgba(255,255,255,0.2)' }}>Awaiting output...</div>
                                                    ) : processLogs.map((log, i) => {
                                                        const color =
                                                            log.level === 'error' ? '#f43f5e' :
                                                            log.message?.includes('[+]') ? '#10b981' :
                                                            log.message?.includes('[*]') ? '#60a5fa' :
                                                            log.message?.includes('[!]') ? '#f59e0b' :
                                                            'rgba(255,255,255,0.5)';
                                                        return (
                                                            <div key={i} className="leading-relaxed break-all" style={{ color }}>
                                                                <span style={{ color: 'rgba(255,255,255,0.15)', marginRight: '8px' }}>
                                                                    {new Date(log.ts).toLocaleTimeString()}
                                                                </span>
                                                                {log.message}
                                                            </div>
                                                        );
                                                    })}
                                                    <div ref={logsEndRef} />
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    DOWNLOAD QUEUE            ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'queue' && (
                        <div className="space-y-5">
                            <div>
                                <h1 className="text-2xl font-black text-white">Download Queue</h1>
                                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Jobs waiting to be processed — executed one at a time</p>
                            </div>

                            {queue.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-20 space-y-4 rounded-2xl border" style={{ borderColor: 'rgba(255,255,255,0.05)', background: 'rgba(255,255,255,0.02)' }}>
                                    <div className="w-14 h-14 rounded-2xl flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.05)' }}>
                                        <List className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold" style={{ color: 'rgba(255,255,255,0.4)' }}>Queue is empty</div>
                                        <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.2)' }}>Add downloads from the New Download tab</p>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {queue.map((job, index) => (
                                        <div key={job.id} className="flex items-center gap-4 p-4 rounded-2xl border" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                            {/* Position */}
                                            <div className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-xs font-black" style={{ background: job.status === 'running' ? 'rgba(59,130,246,0.15)' : 'rgba(255,255,255,0.05)', color: job.status === 'running' ? '#3b82f6' : 'rgba(255,255,255,0.3)' }}>
                                                {job.status === 'running' ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : `#${index + 1}`}
                                            </div>

                                            <div className="flex-1 min-w-0">
                                                <div className="font-semibold text-white text-sm truncate">{job.target}</div>
                                                <div className="flex items-center gap-3 mt-1">
                                                    <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.3)' }}>Added {timeAgo(job.addedAt)}</span>
                                                    <StatusBadge status={job.status} />
                                                    <div className="flex items-center gap-1">
                                                        {Object.entries(job.filters || {}).filter(([, v]) => v).map(([k]) => (
                                                            <span key={k} className="text-[9px] font-bold px-1.5 py-0.5 rounded-full capitalize" style={{ background: 'rgba(245,158,11,0.1)', color: '#f59e0b' }}>{k}</span>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>

                                            {job.status === 'pending' && (
                                                <button
                                                    onClick={() => handleQueueRemove(job.id)}
                                                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                                                    style={{ background: 'rgba(244,63,94,0.1)', color: '#f43f5e' }}
                                                >
                                                    <Trash2 className="w-3 h-3" /> Remove
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    SAVED FILES / FOLDERS     ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'folders' && (
                        <div className="space-y-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-black text-white">Saved Files</h1>
                                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Downloaded profiles stored on your local hard drive</p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => handleOpenFolder()}
                                        className="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all"
                                        style={{ background: 'rgba(245,158,11,0.12)', color: '#f59e0b', border: '1px solid rgba(245,158,11,0.2)' }}
                                    >
                                        <FolderOpen className="w-3.5 h-3.5" /> Open Downloads Folder
                                    </button>
                                    <button onClick={loadFolders} className="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}>
                                        <RefreshCw className="w-3.5 h-3.5" /> Refresh
                                    </button>
                                </div>
                            </div>

                            {folders.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-20 space-y-4 rounded-2xl border" style={{ borderColor: 'rgba(255,255,255,0.05)', background: 'rgba(255,255,255,0.02)' }}>
                                    <div className="w-14 h-14 rounded-2xl flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.05)' }}>
                                        <Folder className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold" style={{ color: 'rgba(255,255,255,0.4)' }}>No downloads yet</div>
                                        <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.2)' }}>Run a download to see files here</p>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    {folders.map(folder => (
                                        <div key={folder.name} className="p-5 rounded-2xl border group cursor-pointer transition-all" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}
                                             onClick={() => handleOpenFolder(folder.name)}>
                                            <div className="flex items-start gap-3">
                                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'rgba(245,158,11,0.1)' }}>
                                                    <Folder className="w-5 h-5" style={{ color: '#f59e0b' }} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="font-bold text-white text-sm truncate">{folder.name}</div>
                                                    <div className="flex items-center gap-3 mt-1">
                                                        <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.3)' }}>
                                                            {folder.fileCount} file{folder.fileCount !== 1 ? 's' : ''}
                                                        </span>
                                                        <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.3)' }}>
                                                            {formatBytes(folder.totalSize)}
                                                        </span>
                                                    </div>
                                                </div>
                                                <FolderOpen className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-all" style={{ color: '#f59e0b' }} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    HISTORY                   ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'history' && (
                        <div className="space-y-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-black text-white">Download History</h1>
                                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Past 50 completed jobs this session</p>
                                </div>
                                {history.length > 0 && (
                                    <button onClick={handleClearHistory} className="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all" style={{ background: 'rgba(244,63,94,0.1)', color: '#f43f5e' }}>
                                        <Trash2 className="w-3.5 h-3.5" /> Clear History
                                    </button>
                                )}
                            </div>

                            {history.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-20 space-y-4 rounded-2xl border" style={{ borderColor: 'rgba(255,255,255,0.05)', background: 'rgba(255,255,255,0.02)' }}>
                                    <div className="w-14 h-14 rounded-2xl flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.05)' }}>
                                        <History className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />
                                    </div>
                                    <div className="font-semibold" style={{ color: 'rgba(255,255,255,0.4)' }}>No history yet</div>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {history.map(item => (
                                        <div key={item.id} className="flex items-center gap-4 p-4 rounded-2xl border" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                            <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{
                                                background: item.status === 'completed' ? 'rgba(16,185,129,0.1)' :
                                                           item.status === 'error' ? 'rgba(244,63,94,0.1)' : 'rgba(255,255,255,0.05)',
                                            }}>
                                                {item.status === 'completed' && <CheckCircle className="w-4 h-4" style={{ color: '#10b981' }} />}
                                                {item.status === 'error' && <XCircle className="w-4 h-4" style={{ color: '#f43f5e' }} />}
                                                {item.status === 'stopped' && <Square className="w-4 h-4" style={{ color: 'rgba(255,255,255,0.3)' }} />}
                                            </div>

                                            <div className="flex-1 min-w-0">
                                                <div className="font-semibold text-white text-sm truncate">{item.target}</div>
                                                <div className="flex items-center gap-3 mt-1">
                                                    <StatusBadge status={item.status} />
                                                    {item.successCount !== undefined && (
                                                        <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.35)' }}>{item.successCount} items saved</span>
                                                    )}
                                                    {item.endTime && (
                                                        <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.25)' }}>{timeAgo(item.endTime)}</span>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <button
                                                    onClick={() => selectProcess(item)}
                                                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                                                    style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}
                                                >
                                                    <Eye className="w-3 h-3" /> View Logs
                                                </button>
                                                <button
                                                    onClick={() => handleOpenFolder(item.target.replace(/^@/, '').replace(/.*\//, ''))}
                                                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all"
                                                    style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}
                                                >
                                                    <FolderOpen className="w-3 h-3" /> Folder
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Log Drawer for history */}
                            {selectedProcessId && logsExpanded && activeWorkspace === 'history' && (
                                <div className="rounded-2xl border overflow-hidden" style={{ background: '#0a0c13', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <div className="flex items-center justify-between px-4 py-2.5 border-b" style={{ borderColor: 'rgba(255,255,255,0.04)' }}>
                                        <div className="flex gap-1.5">
                                            <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f43f5e' }} />
                                            <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f59e0b' }} />
                                            <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#10b981' }} />
                                        </div>
                                        <span className="text-[10px] font-mono" style={{ color: 'rgba(255,255,255,0.2)' }}>Process Logs</span>
                                        <button onClick={() => { setLogsExpanded(false); setSelectedProcessId(null); }} style={{ color: 'rgba(255,255,255,0.3)' }}>
                                            <XCircle className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <div className="h-72 overflow-y-auto p-4 font-mono text-[11px] space-y-1">
                                        {processLogs.map((log, i) => {
                                            const color = log.level === 'error' ? '#f43f5e' :
                                                          log.message?.includes('[+]') ? '#10b981' :
                                                          log.message?.includes('[*]') ? '#60a5fa' :
                                                          log.message?.includes('[!]') ? '#f59e0b' :
                                                          'rgba(255,255,255,0.4)';
                                            return (
                                                <div key={i} className="leading-relaxed break-all" style={{ color }}>
                                                    <span style={{ color: 'rgba(255,255,255,0.15)', marginRight: '8px' }}>{new Date(log.ts).toLocaleTimeString()}</span>
                                                    {log.message}
                                                </div>
                                            );
                                        })}
                                        <div ref={logsEndRef} />
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                </div>
            </main>
        </div>
    );
}
