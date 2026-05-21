import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Video, Download, Play, Square, Settings,
    Search, RefreshCw, Smartphone,
    Folder, HardDrive, ShieldCheck, CheckCircle2, Terminal,
    ChevronRight, Clock, Zap, List, Activity, Trash2, FolderOpen,
    AlertTriangle, XCircle, CheckCircle, Pause, MoreVertical,
    FileVideo, Image as ImageIcon, Film, ArrowRight, StopCircle,
    Eye, ChevronDown, ChevronUp, History, LayoutGrid, Lock,
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

interface SavedFolder {
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

// ─── Log Terminal ─────────────────────────────────────────────────────────────
function LogTerminal({ logs, target, onClose }: {
    logs: { ts: number; level: string; message: string }[];
    target: string;
    onClose: () => void;
}) {
    const logsEndRef = useRef<HTMLDivElement>(null);
    useEffect(() => {
        logsEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [logs]);

    return (
        <div className="rounded-2xl border overflow-hidden" style={{ background: '#0a0c13', borderColor: 'rgba(255,255,255,0.06)' }}>
            <div className="flex items-center justify-between px-4 py-2.5 border-b" style={{ borderColor: 'rgba(255,255,255,0.04)' }}>
                <div className="flex gap-1.5">
                    <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f43f5e' }} />
                    <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#f59e0b' }} />
                    <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#10b981' }} />
                </div>
                <span className="text-[10px] font-mono truncate max-w-[120px] sm:max-w-xs" style={{ color: 'rgba(255,255,255,0.2)' }}>
                    {target}
                </span>
                <button onClick={onClose} className="p-1" style={{ color: 'rgba(255,255,255,0.3)' }}>
                    <XCircle className="w-3.5 h-3.5" />
                </button>
            </div>
            <div className="h-48 sm:h-64 overflow-y-auto p-4 font-mono text-[11px] space-y-1">
                {logs.length === 0 ? (
                    <div style={{ color: 'rgba(255,255,255,0.2)' }}>Awaiting output...</div>
                ) : logs.map((log, i) => {
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
    const [folders, setFolders] = useState<SavedFolder[]>([]);
    const [history, setHistory] = useState<Process[]>([]);
    const [selectedProcessId, setSelectedProcessId] = useState<string | null>(null);
    const [processLogs, setProcessLogs] = useState<{ ts: number; level: string; message: string }[]>([]);
    const [logsExpanded, setLogsExpanded] = useState(false);

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

    useEffect(() => {
        if (!selectedProcessId || !connected) return;
        callRPC('get_logs', { processId: selectedProcessId })
            .then(res => setProcessLogs(res.logs || []))
            .catch(() => {});
    }, [selectedProcessId, activeProcesses, callRPC, connected]);

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
        try { await callRPC('stop_process', { processId }); loadAll(); }
        catch (err: any) { alert(err.message); }
    };

    const handleQueueRemove = async (jobId: string) => {
        try { await callRPC('queue_remove', { jobId }); loadAll(); }
        catch (err: any) { alert(err.message); }
    };

    const handleOpenFolder = async (subdir?: string) => {
        try { await callRPC('open_folder', { subdir }); }
        catch (err: any) { alert(err.message); }
    };

    const handleClearHistory = async () => {
        try { await callRPC('clear_history', {}); setHistory([]); }
        catch (_) {}
    };

    const selectProcess = async (proc: Process) => {
        setSelectedProcessId(proc.id);
        setLogsExpanded(true);
        const res = await callRPC('get_logs', { processId: proc.id }).catch(() => ({ logs: [] }));
        setProcessLogs(res.logs || []);
    };

    const closeLogs = () => { setLogsExpanded(false); setSelectedProcessId(null); };

    // ─── Connection Loading Screen ─────────────────────────────────────────
    if (!connected) {
        return (
            <div className="min-h-screen flex items-center justify-center px-4" style={{ background: '#0f1117' }}>
                <div className="text-center space-y-6 w-full max-w-sm p-8 rounded-3xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
                    <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto relative" style={{ background: 'linear-gradient(135deg, #f59e0b, #d97706)' }}>
                        <Smartphone className="w-8 h-8 text-white" />
                        <span className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center">
                            <span className="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse" />
                        </span>
                    </div>
                    <div>
                        <h3 className="font-bold text-white text-lg">Connecting...</h3>
                        <p className="text-xs text-slate-500 mt-2 leading-relaxed">
                            Make sure the Musoftware desktop client is running on your computer.
                        </p>
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

    // ─── Nav Items ────────────────────────────────────────────────────────
    const navItems: { id: Workspace; icon: React.ReactNode; label: string; badge?: number }[] = [
        { id: 'new',     icon: <Download className="w-5 h-5" />,    label: 'Download' },
        { id: 'active',  icon: <Activity className="w-5 h-5" />,    label: 'Active',   badge: runningCount },
        { id: 'queue',   icon: <List className="w-5 h-5" />,        label: 'Queue',    badge: queuedCount },
        { id: 'folders', icon: <FolderOpen className="w-5 h-5" />,  label: 'Files' },
        { id: 'history', icon: <History className="w-5 h-5" />,     label: 'History' },
    ];

    // ─── Empty State ──────────────────────────────────────────────────────
    const EmptyState = ({ icon, title, sub, cta }: { icon: React.ReactNode; title: string; sub: string; cta?: React.ReactNode }) => (
        <div className="flex flex-col items-center justify-center py-16 sm:py-24 space-y-4 rounded-2xl border" style={{ borderColor: 'rgba(255,255,255,0.05)', background: 'rgba(255,255,255,0.02)' }}>
            <div className="w-14 h-14 rounded-2xl flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.05)' }}>
                {icon}
            </div>
            <div className="text-center px-4">
                <div className="font-semibold" style={{ color: 'rgba(255,255,255,0.4)' }}>{title}</div>
                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.2)' }}>{sub}</p>
            </div>
            {cta}
        </div>
    );

    return (
        <div
            className="flex flex-col md:flex-row antialiased"
            style={{
                background: '#0f1117',
                color: '#e2e8f0',
                fontFamily: "'Inter', system-ui, sans-serif",
                minHeight: '100vh',
            }}
        >
            {/* ─── Desktop Sidebar (hidden on mobile) ──────────────────────── */}
            <aside className="hidden md:flex w-56 flex-col border-r shrink-0 sticky top-0 h-screen" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                {/* Logo */}
                <div className="p-5 border-b" style={{ borderColor: 'rgba(255,255,255,0.06)' }}>
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)' }}>
                            <Film className="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <div className="font-bold text-white text-sm leading-none">SnapDownloader</div>
                            <div className="text-[10px] mt-0.5" style={{ color: '#f59e0b' }}>Media Saver</div>
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
                                className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all"
                                style={{
                                    background: active ? 'rgba(245,158,11,0.12)' : 'transparent',
                                    color: active ? '#f59e0b' : 'rgba(255,255,255,0.4)',
                                    minHeight: '44px',
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
                        <span className="w-2 h-2 rounded-full animate-pulse shrink-0" style={{ background: '#10b981', boxShadow: '0 0 6px #10b981' }} />
                        <div>
                            <div className="text-[10px] font-bold text-white">Connected</div>
                            <div className="text-[9px] mt-0.5" style={{ color: 'rgba(255,255,255,0.3)' }}>Ready</div>
                        </div>
                    </div>
                </div>
            </aside>

            {/* ─── Main Column ──────────────────────────────────────────────── */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">

                {/* Mobile Header */}
                <header className="flex md:hidden items-center justify-between px-4 py-3 border-b shrink-0 sticky top-0 z-10" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)' }}>
                            <Film className="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <div className="font-bold text-white text-sm leading-none">SnapDownloader</div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full animate-pulse" style={{ background: '#10b981', boxShadow: '0 0 5px #10b981' }} />
                        <span className="text-[10px] font-semibold" style={{ color: '#10b981' }}>Live</span>
                        <button onClick={loadAll} className="ml-1 p-2 rounded-lg" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}>
                            <RefreshCw className="w-3.5 h-3.5" />
                        </button>
                    </div>
                </header>

                {/* Desktop Header */}
                <header className="hidden md:flex h-14 items-center justify-between px-6 border-b shrink-0" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                    <div className="flex items-center gap-2 text-xs">
                        <span className="font-bold text-white capitalize">{navItems.find(n => n.id === activeWorkspace)?.label}</span>
                        <ChevronRight className="w-3.5 h-3.5" style={{ color: 'rgba(255,255,255,0.2)' }} />
                        <span style={{ color: 'rgba(255,255,255,0.35)' }}>
                            {runningCount > 0 ? `${runningCount} active` : 'No active downloads'}
                        </span>
                    </div>
                    <button onClick={loadAll} className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-semibold transition-all" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}>
                        <RefreshCw className="w-3 h-3" /> Refresh
                    </button>
                </header>

                {/* Scrollable Content */}
                <main className="flex-1 overflow-y-auto p-4 sm:p-6 pb-28 md:pb-6">

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║     NEW DOWNLOAD WORKSPACE   ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'new' && (
                        <div className="max-w-2xl mx-auto space-y-5">
                            <div>
                                <h1 className="text-xl sm:text-2xl font-black text-white">New Download</h1>
                                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Queue a Snapchat profile for saving</p>
                            </div>

                            {/* Form Card */}
                            <div className="rounded-2xl border p-4 sm:p-6 space-y-5" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
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
                                            placeholder="Username or Snapchat URL"
                                            className="w-full pl-11 pr-4 py-3.5 rounded-xl text-sm font-medium outline-none transition-all"
                                            style={{
                                                background: 'rgba(255,255,255,0.04)',
                                                border: `1px solid ${formError ? '#f43f5e' : 'rgba(255,255,255,0.08)'}`,
                                                color: '#fff',
                                                fontSize: '16px', // prevents iOS zoom on focus
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
                                                        minHeight: '52px',
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
                                    className="w-full py-4 rounded-xl font-black text-sm uppercase tracking-widest flex items-center justify-center gap-3 transition-all active:scale-95 disabled:opacity-40"
                                    style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)', color: '#000', minHeight: '52px' }}
                                >
                                    {isQueuing ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4 fill-current" />}
                                    {isQueuing ? 'Adding...' : 'Start Download'}
                                </button>
                            </div>

                            {/* Privacy Banner — outcome language, no architecture disclosure */}
                            <div className="rounded-2xl p-4 sm:p-5 flex items-start gap-4" style={{ background: 'rgba(245,158,11,0.07)', border: '1px solid rgba(245,158,11,0.15)' }}>
                                <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'rgba(245,158,11,0.15)' }}>
                                    <Lock className="w-4 h-4" style={{ color: '#f59e0b' }} />
                                </div>
                                <div>
                                    <div className="font-bold text-white text-xs">Fast & Private</div>
                                    <p className="text-[11px] mt-1 leading-relaxed" style={{ color: 'rgba(255,255,255,0.4)' }}>
                                        Your files are saved directly to your chosen folder. Nothing leaves your computer.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    ACTIVE PROCESSES          ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'active' && (
                        <div className="space-y-5 max-w-3xl mx-auto">
                            <div>
                                <h1 className="text-xl sm:text-2xl font-black text-white">Active Downloads</h1>
                                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Monitor and control running jobs in real time</p>
                            </div>

                            {activeProcesses.length === 0 && (
                                <EmptyState
                                    icon={<Activity className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                                    title="No active downloads"
                                    sub="Start a download from the Download tab"
                                    cta={
                                        <button onClick={() => setActiveWorkspace('new')} className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold" style={{ background: 'rgba(245,158,11,0.15)', color: '#f59e0b', minHeight: '44px' }}>
                                            <Download className="w-3.5 h-3.5" /> New Download
                                        </button>
                                    }
                                />
                            )}

                            <div className="space-y-4">
                                {activeProcesses.map(proc => (
                                    <div key={proc.id} className="rounded-2xl border overflow-hidden" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                        <div className="p-4 sm:p-5">
                                            <div className="flex items-start gap-3">
                                                {/* Status Icon */}
                                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style={{
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
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <span className="font-bold text-white text-sm truncate max-w-[160px] sm:max-w-xs">{proc.target}</span>
                                                        <StatusBadge status={proc.status} />
                                                    </div>
                                                    <div className="text-[11px] mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>
                                                        Started {timeAgo(proc.startTime)}
                                                        {proc.totalItems > 0 && ` · ${proc.successCount}/${proc.totalItems} items`}
                                                    </div>

                                                    {/* Progress */}
                                                    {proc.status === 'running' && (
                                                        <div className="mt-3 space-y-1.5">
                                                            <div className="flex items-center justify-between text-[10px]">
                                                                <span style={{ color: 'rgba(255,255,255,0.4)' }}>{proc.progressMsg}</span>
                                                                <span className="font-bold" style={{ color: '#3b82f6' }}>{proc.progress}%</span>
                                                            </div>
                                                            <div className="h-1.5 rounded-full overflow-hidden" style={{ background: 'rgba(255,255,255,0.06)' }}>
                                                                <div className="h-full rounded-full transition-all duration-500" style={{ width: `${proc.progress}%`, background: 'linear-gradient(90deg, #3b82f6, #6366f1)' }} />
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Stats */}
                                                    <div className="flex items-center gap-2 mt-2 flex-wrap">
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
                                            </div>

                                            {/* Actions Row — wraps on mobile */}
                                            <div className="flex items-center gap-2 mt-4 flex-wrap">
                                                <button
                                                    onClick={() => selectProcess(proc)}
                                                    className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[11px] font-semibold transition-all"
                                                    style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.6)', minHeight: '36px' }}
                                                >
                                                    <Terminal className="w-3 h-3" /> Logs
                                                </button>
                                                {proc.outputDir && (
                                                    <button
                                                        onClick={() => handleOpenFolder(proc.target.replace(/^@/, '').replace(/.*\//, ''))}
                                                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[11px] font-semibold transition-all"
                                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.6)', minHeight: '36px' }}
                                                    >
                                                        <FolderOpen className="w-3 h-3" /> Folder
                                                    </button>
                                                )}
                                                {proc.status === 'running' && (
                                                    <button
                                                        onClick={() => handleStopProcess(proc.id)}
                                                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[11px] font-bold transition-all ml-auto"
                                                        style={{ background: 'rgba(244,63,94,0.15)', color: '#f43f5e', border: '1px solid rgba(244,63,94,0.25)', minHeight: '36px' }}
                                                    >
                                                        <StopCircle className="w-3 h-3" /> Stop
                                                    </button>
                                                )}
                                            </div>
                                        </div>

                                        {/* Inline Terminal */}
                                        {selectedProcessId === proc.id && logsExpanded && (
                                            <div className="border-t" style={{ borderColor: 'rgba(255,255,255,0.05)', background: '#0a0c13' }}>
                                                <LogTerminal logs={processLogs} target={proc.target} onClose={closeLogs} />
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
                        <div className="space-y-5 max-w-3xl mx-auto">
                            <div>
                                <h1 className="text-xl sm:text-2xl font-black text-white">Queue</h1>
                                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Jobs waiting to run — processed one at a time</p>
                            </div>

                            {queue.length === 0 ? (
                                <EmptyState
                                    icon={<List className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                                    title="Queue is empty"
                                    sub="Add profiles from the Download tab"
                                />
                            ) : (
                                <div className="space-y-3">
                                    {queue.map((job, index) => (
                                        <div key={job.id} className="flex items-center gap-3 p-4 rounded-2xl border" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                            {/* Position indicator */}
                                            <div className="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-xs font-black" style={{
                                                background: job.status === 'running' ? 'rgba(59,130,246,0.15)' : 'rgba(255,255,255,0.05)',
                                                color: job.status === 'running' ? '#3b82f6' : 'rgba(255,255,255,0.3)',
                                            }}>
                                                {job.status === 'running' ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : `#${index + 1}`}
                                            </div>

                                            <div className="flex-1 min-w-0">
                                                <div className="font-semibold text-white text-sm truncate">{job.target}</div>
                                                <div className="flex items-center gap-2 mt-1 flex-wrap">
                                                    <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.3)' }}>Added {timeAgo(job.addedAt)}</span>
                                                    <StatusBadge status={job.status} />
                                                    <div className="flex items-center gap-1">
                                                        {Object.entries(job.filters || {}).filter(([, v]) => v).map(([k]) => (
                                                            <span key={k} className="text-[9px] font-bold px-1.5 py-0.5 rounded-full capitalize" style={{ background: 'rgba(245,158,11,0.1)', color: '#f59e0b' }}>{k}</span>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>

                                            {job.status !== 'running' && (
                                                <button
                                                    onClick={() => handleQueueRemove(job.id)}
                                                    className="p-2.5 rounded-xl transition-all shrink-0"
                                                    style={{ background: 'rgba(244,63,94,0.08)', color: 'rgba(244,63,94,0.6)', minWidth: '44px', minHeight: '44px' }}
                                                >
                                                    <Trash2 className="w-4 h-4 mx-auto" />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    SAVED FILES               ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'folders' && (
                        <div className="space-y-5 max-w-3xl mx-auto">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-xl sm:text-2xl font-black text-white">Saved Files</h1>
                                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Saved media, organised by profile</p>
                                </div>
                                <button onClick={() => handleOpenFolder()} className="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold" style={{ background: 'rgba(245,158,11,0.1)', color: '#f59e0b', minHeight: '44px' }}>
                                    <FolderOpen className="w-3.5 h-3.5" /> Open All
                                </button>
                            </div>

                            {folders.length === 0 ? (
                                <EmptyState
                                    icon={<FolderOpen className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                                    title="No saved files yet"
                                    sub="Files will appear here after your first download"
                                />
                            ) : (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    {folders.map(folder => (
                                        <button
                                            key={folder.path}
                                            onClick={() => handleOpenFolder(folder.name)}
                                            className="p-4 rounded-2xl border text-left transition-all hover:border-amber-500/30 active:scale-95"
                                            style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)', minHeight: '80px' }}
                                        >
                                            <div className="flex items-start gap-3">
                                                <div className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'rgba(245,158,11,0.1)' }}>
                                                    <FolderOpen className="w-4.5 h-4.5" style={{ color: '#f59e0b' }} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="font-bold text-white text-sm truncate">{folder.name}</div>
                                                    <div className="text-[11px] mt-0.5" style={{ color: 'rgba(255,255,255,0.35)' }}>
                                                        {folder.fileCount} file{folder.fileCount !== 1 ? 's' : ''} · {formatBytes(folder.totalSize)}
                                                    </div>
                                                </div>
                                                <ChevronRight className="w-4 h-4 shrink-0 mt-1" style={{ color: 'rgba(255,255,255,0.2)' }} />
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ╔══════════════════════════════╗ */}
                    {/* ║    HISTORY                   ║ */}
                    {/* ╚══════════════════════════════╝ */}
                    {activeWorkspace === 'history' && (
                        <div className="space-y-5 max-w-3xl mx-auto">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-xl sm:text-2xl font-black text-white">History</h1>
                                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>Past completed and stopped downloads</p>
                                </div>
                                {history.length > 0 && (
                                    <button onClick={handleClearHistory} className="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold" style={{ background: 'rgba(244,63,94,0.1)', color: '#f43f5e', minHeight: '44px' }}>
                                        <Trash2 className="w-3.5 h-3.5" /> Clear
                                    </button>
                                )}
                            </div>

                            {history.length === 0 ? (
                                <EmptyState
                                    icon={<History className="w-7 h-7" style={{ color: 'rgba(255,255,255,0.2)' }} />}
                                    title="No history yet"
                                    sub="Completed downloads will appear here"
                                />
                            ) : (
                                <div className="space-y-3">
                                    {history.map(item => (
                                        <div key={item.id} className="rounded-2xl border overflow-hidden" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                            <div className="flex items-center gap-3 p-4">
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
                                                    <div className="flex items-center gap-2 mt-1 flex-wrap">
                                                        <StatusBadge status={item.status} />
                                                        {item.successCount !== undefined && (
                                                            <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.35)' }}>{item.successCount} saved</span>
                                                        )}
                                                        {item.endTime && (
                                                            <span className="text-[10px]" style={{ color: 'rgba(255,255,255,0.25)' }}>{timeAgo(item.endTime)}</span>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Actions */}
                                                <div className="flex items-center gap-2 shrink-0">
                                                    <button
                                                        onClick={() => selectProcess(item)}
                                                        className="p-2.5 rounded-xl transition-all"
                                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)', minWidth: '44px', minHeight: '44px' }}
                                                    >
                                                        <Eye className="w-4 h-4 mx-auto" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleOpenFolder(item.target.replace(/^@/, '').replace(/.*\//, ''))}
                                                        className="p-2.5 rounded-xl transition-all"
                                                        style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)', minWidth: '44px', minHeight: '44px' }}
                                                    >
                                                        <FolderOpen className="w-4 h-4 mx-auto" />
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Log drawer */}
                                            {selectedProcessId === item.id && logsExpanded && (
                                                <div className="border-t" style={{ borderColor: 'rgba(255,255,255,0.05)' }}>
                                                    <LogTerminal logs={processLogs} target={item.target} onClose={closeLogs} />
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                </main>
            </div>

            {/* ─── Mobile Bottom Navigation ─────────────────────────────────── */}
            <nav
                className="flex md:hidden fixed bottom-0 left-0 right-0 z-20 border-t"
                style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.08)' }}
            >
                {navItems.map(item => {
                    const active = activeWorkspace === item.id;
                    return (
                        <button
                            key={item.id}
                            onClick={() => setActiveWorkspace(item.id)}
                            className="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 relative transition-all"
                            style={{ color: active ? '#f59e0b' : 'rgba(255,255,255,0.3)', minHeight: '60px' }}
                        >
                            {/* Badge */}
                            {item.badge !== undefined && item.badge > 0 && (
                                <span className="absolute top-2 right-1/4 w-4 h-4 rounded-full text-[9px] font-black flex items-center justify-center" style={{ background: '#f59e0b', color: '#000' }}>
                                    {item.badge}
                                </span>
                            )}
                            <span style={{ color: active ? '#f59e0b' : 'rgba(255,255,255,0.3)' }}>{item.icon}</span>
                            <span className="text-[10px] font-semibold">{item.label}</span>
                            {active && (
                                <span className="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-0.5 rounded-full" style={{ background: '#f59e0b' }} />
                            )}
                        </button>
                    );
                })}
            </nav>
        </div>
    );
}
