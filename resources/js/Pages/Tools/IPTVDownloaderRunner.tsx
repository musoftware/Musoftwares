import React, { useState, useEffect, useRef } from 'react';
import {
    Tv, List, Bookmark, Download, Settings, Plus, Play, Square,
    Trash2, Search, ArrowRight, CheckCircle2, AlertCircle, RefreshCw,
    Folder, HardDrive, ShieldCheck, HelpCircle, Star, Terminal, ChevronRight
} from 'lucide-react';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Custom WebSocket Hook for Generic RPC + Broadcasts ────────────────────────
function useRuntimeRPC(pluginSlug: string) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const [activeTasks, setActiveTasks] = useState<any[]>([]);
    const pendingRequests = useRef(new Map());
    const onMessageCallbacks = useRef<Set<Function>>(new Set());

    useEffect(() => {
        let socket: WebSocket;
        let reconnectTimer: any;

        const connect = () => {
            socket = new WebSocket(getWsUrl());

            socket.onopen = () => {
                setConnected(true);
                // Ping to keep alive
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
                    
                    // Route WebSocket RPC Responses
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const resolver = pendingRequests.current.get(msg.requestId);
                        if (resolver) {
                            if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload.error));
                            else resolver.resolve(msg.payload);
                            pendingRequests.current.delete(msg.requestId);
                        }
                    }

                    if (msg.event === 'runtime.ready') {
                        setActiveTasks(msg.data?.activeTasks ?? []);
                    }

                    // Feed raw broadcast events to sub-components
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

            // Auto-timeout after 30s
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

    return { connected, callRPC, subscribeToEvents, activeTasks };
}

export default function IPTVDownloaderRunner() {
    const { connected, callRPC, subscribeToEvents } = useRuntimeRPC('iptv-downloader');

    // Navigation & Workspace State
    const [activeWorkspace, setActiveWorkspace] = useState<'dashboard' | 'playlists' | 'browser' | 'downloads'>('dashboard');
    
    // Playlists & Parsing
    const [playlists, setPlaylists] = useState<any[]>([]);
    const [playlistType, setPlaylistType] = useState<'m3u' | 'xtream'>('m3u');
    const [playlistName, setPlaylistName] = useState('');
    const [playlistUrl, setPlaylistUrl] = useState('');
    const [xtreamHost, setXtreamHost] = useState('');
    const [xtreamUser, setXtreamUser] = useState('');
    const [xtreamPass, setXtreamPass] = useState('');
    const [isParsing, setIsParsing] = useState(false);
    const [parseError, setParseError] = useState('');

    // Browser State
    const [selectedPlaylistId, setSelectedPlaylistId] = useState<string>('');
    const [streamType, setStreamType] = useState<'live' | 'vod' | 'series'>('live');
    const [groups, setGroups] = useState<any[]>([]);
    const [selectedGroup, setSelectedGroup] = useState<string>('');
    const [searchQuery, setSearchQuery] = useState('');
    const [bookmarkedOnly, setBookmarkedOnly] = useState(false);
    const [channels, setChannels] = useState<any[]>([]);
    const [totalChannelsCount, setTotalChannelsCount] = useState(0);
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 50;

    // Downloads State
    const [downloads, setDownloads] = useState<any[]>([]);
    const [downloadTasks, setDownloadTasks] = useState<Record<string, any>>({});
    const [taskLogs, setTaskLogs] = useState<Record<string, string[]>>({});
    const [showLogsTaskId, setShowLogsTaskId] = useState<string | null>(null);

    // Record Config Panel
    const [showRecordConfig, setShowRecordConfig] = useState<any | null>(null);
    const [recordDurationPreset, setRecordDurationPreset] = useState<number>(0); // 0 = unlimited VOD / LIVE
    const [recordDurationCustom, setRecordDurationCustom] = useState<string>('');

    // Load initial data
    useEffect(() => {
        if (connected) {
            fetchPlaylists();
            fetchDownloads();
        }
    }, [connected]);

    // Live WebSocket Event Subscriber
    useEffect(() => {
        const unsubscribe = subscribeToEvents((msg: any) => {
            // Task Progress Broadcast
            if (msg.event === 'task.progress') {
                const { taskId, percent, message } = msg.data;
                setDownloadTasks(prev => ({
                    ...prev,
                    [taskId]: {
                        ...prev[taskId],
                        percent,
                        message,
                        status: 'downloading'
                    }
                }));
            }

            // Task Log Broadcast
            if (msg.event === 'task.log') {
                const { taskId, level, message } = msg.data;
                setTaskLogs(prev => ({
                    ...prev,
                    [taskId]: [...(prev[taskId] || []), `[${level.toUpperCase()}] ${message}`].slice(-200) // limit log history buffer
                }));
            }

            // Task Done Broadcast
            if (msg.event === 'task.done') {
                const { taskId } = msg.data;
                setDownloadTasks(prev => {
                    const copy = { ...prev };
                    delete copy[taskId];
                    return copy;
                });
                fetchDownloads();
            }

            // Task Error Broadcast
            if (msg.event === 'task.error') {
                const { taskId, error } = msg.data;
                setDownloadTasks(prev => {
                    const copy = { ...prev };
                    delete copy[taskId];
                    return copy;
                });
                fetchDownloads();
            }
        });

        return () => unsubscribe();
    }, [subscribeToEvents]);

    // Fetch lists helper
    const fetchPlaylists = async () => {
        try {
            const res: any = await callRPC('list_playlists');
            setPlaylists(res.playlists);
            if (res.playlists.length > 0 && !selectedPlaylistId) {
                setSelectedPlaylistId(res.playlists[0].id);
            }
        } catch (err) {}
    };

    const fetchDownloads = async () => {
        try {
            const res: any = await callRPC('list_downloads');
            setDownloads(res.downloads);
        } catch (err) {}
    };

    // Load browser schema dynamically
    useEffect(() => {
        if (connected && selectedPlaylistId) {
            fetchGroups();
            setCurrentPage(1);
            fetchChannels(1);
        }
    }, [selectedPlaylistId, selectedGroup, searchQuery, bookmarkedOnly, streamType, connected]);

    const fetchGroups = async () => {
        try {
            const res: any = await callRPC('list_groups', { playlistId: selectedPlaylistId });
            setGroups(res.groups);
        } catch (err) {}
    };

    const fetchChannels = async (page = currentPage) => {
        try {
            const offset = (page - 1) * itemsPerPage;
            const res: any = await callRPC('list_channels', {
                playlistId: selectedPlaylistId,
                groupTitle: selectedGroup,
                searchQuery: searchQuery,
                bookmarkedOnly: bookmarkedOnly,
                streamType: streamType,
                limit: itemsPerPage,
                offset: offset
            });
            setChannels(res.channels);
            setTotalChannelsCount(res.totalCount);
        } catch (err) {}
    };

    const handlePageChange = (newPage: number) => {
        setCurrentPage(newPage);
        fetchChannels(newPage);
    };

    // ── Playlist Actions ──────────────────────────────────────────────────────
    const handleAddPlaylist = async () => {
        if (!playlistName || !playlistUrl) {
            setParseError('Please specify playlist name and target URL');
            return;
        }
        setIsParsing(true);
        setParseError('');
        try {
            await callRPC('add_playlist', { name: playlistName, url: playlistUrl });
            setPlaylistName('');
            setPlaylistUrl('');
            fetchPlaylists();
            setActiveWorkspace('browser');
        } catch (err: any) {
            setParseError(err.message || 'Failed to parse playlist URL');
        } finally {
            setIsParsing(false);
        }
    };

    const handleAddXtreamPlaylist = async () => {
        if (!playlistName || !xtreamHost || !xtreamUser || !xtreamPass) {
            setParseError('Please fill all Xtream fields');
            return;
        }
        setIsParsing(true);
        setParseError('');
        try {
            await callRPC('add_xtream_playlist', { name: playlistName, host: xtreamHost, username: xtreamUser, password: xtreamPass });
            setPlaylistName('');
            setXtreamHost('');
            setXtreamUser('');
            setXtreamPass('');
            fetchPlaylists();
            setActiveWorkspace('browser');
        } catch (err: any) {
            setParseError(err.message || 'Failed to parse Xtream playlist');
        } finally {
            setIsParsing(false);
        }
    };

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const name = file.name.replace(/\.[^/.]+$/, "");
        setIsParsing(true);
        setParseError('');

        const reader = new FileReader();
        reader.onload = async () => {
            try {
                await callRPC('add_playlist', {
                    name: name,
                    base64: reader.result
                });
                fetchPlaylists();
                setActiveWorkspace('browser');
            } catch (err: any) {
                setParseError(err.message || 'Failed parsing local playlist');
            } finally {
                setIsParsing(false);
            }
        };
        reader.readAsDataURL(file);
    };

    const handleDeletePlaylist = async (id: string) => {
        if (!confirm('Are you sure you want to delete this playlist and all associated channels?')) return;
        try {
            await callRPC('delete_playlist', { id });
            if (selectedPlaylistId === id) {
                setSelectedPlaylistId('');
                setChannels([]);
            }
            fetchPlaylists();
        } catch (err) {}
    };

    // ── Channel Actions ───────────────────────────────────────────────────────
    const handleToggleBookmark = async (channelId: string) => {
        try {
            await callRPC('toggle_bookmark', { channelId });
            // local update to state to prevent fully reloading channels list
            setChannels(prev => prev.map(ch => ch.id === channelId ? { ...ch, bookmarked: ch.bookmarked === 1 ? 0 : 1 } : ch));
        } catch (err) {}
    };

    // ── Download Workers Controls ─────────────────────────────────────────────
    const triggerDownload = async () => {
        if (!showRecordConfig) return;
        const channel = showRecordConfig;
        
        let finalDuration = recordDurationPreset;
        if (recordDurationPreset === -1) {
            // custom input
            finalDuration = parseInt(recordDurationCustom, 10) || 0;
        }

        setShowRecordConfig(null);
        setActiveWorkspace('downloads');

        try {
            const response = await fetch(`${getRuntimeHttp()}/plugins/iptv-downloader/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        channelName: channel.name,
                        url: channel.url,
                        durationSeconds: finalDuration
                    }
                })
            });
            const json = await response.json();
            if (!response.ok) throw new Error(json.error || 'Failed starting download worker');
            
            // Add a temporary local loading task record
            setDownloadTasks(prev => ({
                ...prev,
                [json.taskId]: {
                    id: json.taskId,
                    channel_name: channel.name,
                    url: channel.url,
                    percent: 0,
                    message: 'Initializing...',
                    status: 'downloading'
                }
            }));
            
            fetchDownloads();
        } catch (err: any) {
            alert('Error initiating stream: ' + err.message);
        }
    };

    const handleStopTask = async (taskId: string) => {
        try {
            await fetch(`${getRuntimeHttp()}/tasks/${taskId}/stop`, { method: 'POST' });
            setDownloadTasks(prev => {
                const copy = { ...prev };
                delete copy[taskId];
                return copy;
            });
            fetchDownloads();
        } catch (err) {}
    };

    const handleDeleteDownloadHistory = async (id: string) => {
        try {
            await callRPC('delete_download', { id });
            fetchDownloads();
        } catch (err) {}
    };

    // Derived quick metrics for dashboard
    const bookmarkedCount = channels.filter(ch => ch.bookmarked === 1).length;
    const activeDownloadsCount = Object.keys(downloadTasks).length;

    if (!connected) {
        return (
            <div className="min-h-[80vh] flex items-center justify-center font-sans bg-[#F9FAFB]">
                <div className="text-center space-y-6 max-w-sm p-8 bg-white border border-slate-100 rounded-3xl shadow-sm">
                    <div className="w-14 h-14 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-center mx-auto animate-pulse">
                        <Tv className="w-7 h-7 text-indigo-600 animate-spin" />
                    </div>
                    <div className="space-y-2">
                        <h3 className="font-bold text-slate-800">Linking Runtime Engine...</h3>
                        <p className="text-xs text-slate-500 leading-relaxed">Ensure the Musoftware desktop client is running on your computer to activate local playback and recording services.</p>
                    </div>
                    <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div className="w-1/2 h-full bg-indigo-600 rounded-full animate-infinite-loading" />
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
                        <div className="w-8 h-8 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100">
                            <Tv className="w-4.5 h-4.5 text-white" />
                        </div>
                        <div>
                            <span className="font-bold text-sm tracking-tight leading-none block">IPTV Recorder</span>
                            <span className="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mt-0.5 block">Desktop SDK</span>
                        </div>
                    </div>

                    <nav className="space-y-1">
                        <button
                            onClick={() => setActiveWorkspace('dashboard')}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-all ${activeWorkspace === 'dashboard' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:text-slate-950 hover:bg-slate-50'}`}
                        >
                            <Star className="w-4 h-4" /> Overview Dashboard
                        </button>
                        <button
                            onClick={() => setActiveWorkspace('playlists')}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-all ${activeWorkspace === 'playlists' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:text-slate-950 hover:bg-slate-50'}`}
                        >
                            <List className="w-4 h-4" /> Manage Playlists
                        </button>
                        <button
                            onClick={() => setActiveWorkspace('browser')}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-all ${activeWorkspace === 'browser' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:text-slate-950 hover:bg-slate-50'}`}
                        >
                            <Tv className="w-4 h-4" /> Channel Browser
                        </button>
                        <button
                            onClick={() => setActiveWorkspace('downloads')}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-all ${activeWorkspace === 'downloads' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:text-slate-950 hover:bg-slate-50'}`}
                        >
                            <Download className="w-4 h-4" />
                            <span className="flex-1 text-left">Downloads & Recs</span>
                            {activeDownloadsCount > 0 && (
                                <span className="bg-rose-500 text-white font-black text-[9px] px-2 py-0.5 rounded-full uppercase leading-none animate-pulse">
                                    {activeDownloadsCount} Active
                                </span>
                            )}
                        </button>
                    </nav>
                </div>

                <div className="p-4 border-t border-slate-100 bg-slate-50/50">
                    <div className="flex items-center gap-3 px-1.5 py-1">
                        <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse border-2 border-white shadow-sm shadow-emerald-200" />
                        <div>
                            <span className="text-[10px] font-black uppercase text-slate-800 tracking-wider">Engine Connected</span>
                            <span className="text-[9px] text-slate-400 block mt-0.5">Local SQLite & Workers Ready</span>
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
                        <span className="text-xs text-slate-400 font-semibold">{playlists.length} Playlists loaded</span>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-50 border border-slate-150">
                            <HardDrive className="w-3.5 h-3.5 text-slate-500" />
                            <span className="text-[10px] font-bold text-slate-600 uppercase">storage/downloads/</span>
                        </div>
                    </div>
                </header>

                <div className="flex-1 p-8 max-w-6xl w-full mx-auto space-y-6">
                    {/* WORKSPACE 1: DASHBOARD OVERVIEW */}
                    {activeWorkspace === 'dashboard' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-black tracking-tight text-slate-900">Workspace Dashboard</h1>
                                    <p className="text-sm text-slate-400 mt-1">Manage and record local streams directly on your hard drive.</p>
                                </div>
                            </div>

                            {/* Core Dashboard Cards Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                                    <div className="w-10 h-10 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-center">
                                        <List className="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-black uppercase text-slate-400 tracking-wider">Loaded Playlists</p>
                                        <p className="text-2xl font-extrabold text-slate-900 mt-1">{playlists.length}</p>
                                    </div>
                                </div>

                                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                                    <div className="w-10 h-10 bg-rose-50 border border-rose-100 rounded-2xl flex items-center justify-center">
                                        <Bookmark className="w-5 h-5 text-rose-600" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-black uppercase text-slate-400 tracking-wider">Bookmarked Channels</p>
                                        <p className="text-2xl font-extrabold text-slate-900 mt-1">
                                            {playlists.reduce((acc, curr) => acc + (curr.bookmarkedCount || 0), 0)}
                                        </p>
                                    </div>
                                </div>

                                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                                    <div className="w-10 h-10 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center justify-center">
                                        <Download className="w-5 h-5 text-emerald-600" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-black uppercase text-slate-400 tracking-wider">Downloads History</p>
                                        <p className="text-2xl font-extrabold text-slate-900 mt-1">{downloads.length}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Help & Info */}
                            <div className="bg-gradient-to-tr from-slate-900 to-indigo-950 text-white rounded-3xl p-8 relative overflow-hidden border border-indigo-950 shadow-xl shadow-indigo-100">
                                <div className="absolute top-0 right-0 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl" />
                                <div className="relative z-10 max-w-xl space-y-4">
                                    <div className="flex items-center gap-2 text-[10px] font-black text-indigo-400 tracking-widest uppercase">
                                        <ShieldCheck className="w-4 h-4" /> Apple-Grade Architecture
                                    </div>
                                    <h2 className="text-xl font-bold tracking-tight">Zero-Install Stream Recording</h2>
                                    <p className="text-sm text-slate-400 leading-relaxed">Our native Javascript engine parses live HLS indexes (.m3u8), tracks duplicates, and writes seamless TS stream chunks natively. No ffmpeg setup or complex local drivers required.</p>
                                    <div className="flex gap-4 pt-2">
                                        <button onClick={() => setActiveWorkspace('playlists')} className="px-4 py-2.5 bg-white text-slate-950 text-xs font-extrabold uppercase rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-md">
                                            Add M3U Playlist
                                        </button>
                                        <button onClick={() => setActiveWorkspace('browser')} className="px-4 py-2.5 bg-indigo-600 text-white text-xs font-extrabold uppercase rounded-xl hover:bg-indigo-700 transition-all active:scale-95 border border-indigo-500/30">
                                            Browse Channels
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* WORKSPACE 2: MANAGE PLAYLISTS */}
                    {activeWorkspace === 'playlists' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-black tracking-tight text-slate-900">Manage IPTV Playlists</h1>
                                <p className="text-sm text-slate-400 mt-1">Import online playlists or load raw local .m3u files.</p>
                            </div>

                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Left form box */}
                                <div className="lg:col-span-1 space-y-6">
                                    <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-5">
                                        <h3 className="font-extrabold text-slate-800 text-sm">Add New Playlist</h3>

                                        <div className="flex bg-slate-100 p-1 rounded-xl">
                                            <button onClick={() => setPlaylistType('m3u')} className={`flex-1 py-1.5 text-[10px] font-bold uppercase rounded-lg transition-all ${playlistType === 'm3u' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'}`}>M3U URL / File</button>
                                            <button onClick={() => setPlaylistType('xtream')} className={`flex-1 py-1.5 text-[10px] font-bold uppercase rounded-lg transition-all ${playlistType === 'xtream' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'}`}>Xtream API</button>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="space-y-1.5">
                                                <label className="text-[10px] font-black text-slate-400 uppercase">Playlist Name</label>
                                                <input type="text" value={playlistName} onChange={e => setPlaylistName(e.target.value)} placeholder="e.g. Premium HD US" className="w-full px-4 py-2.5 text-xs font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none transition-all bg-slate-50" />
                                            </div>

                                            {playlistType === 'm3u' ? (
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-black text-slate-400 uppercase">M3U Playlist URL</label>
                                                    <input type="url" value={playlistUrl} onChange={e => setPlaylistUrl(e.target.value)} placeholder="http://example.com/get.php?auth=..." className="w-full px-4 py-2.5 text-xs font-mono font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none transition-all bg-slate-50" />
                                                </div>
                                            ) : (
                                                <>
                                                    <div className="space-y-1.5">
                                                        <label className="text-[10px] font-black text-slate-400 uppercase">Server Host URL</label>
                                                        <input type="url" value={xtreamHost} onChange={e => setXtreamHost(e.target.value)} placeholder="http://example.com:8080" className="w-full px-4 py-2.5 text-xs font-mono font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none transition-all bg-slate-50" />
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="space-y-1.5">
                                                            <label className="text-[10px] font-black text-slate-400 uppercase">Username</label>
                                                            <input type="text" value={xtreamUser} onChange={e => setXtreamUser(e.target.value)} className="w-full px-4 py-2.5 text-xs font-mono font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none transition-all bg-slate-50" />
                                                        </div>
                                                        <div className="space-y-1.5">
                                                            <label className="text-[10px] font-black text-slate-400 uppercase">Password</label>
                                                            <input type="password" value={xtreamPass} onChange={e => setXtreamPass(e.target.value)} className="w-full px-4 py-2.5 text-xs font-mono font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none transition-all bg-slate-50" />
                                                        </div>
                                                    </div>
                                                </>
                                            )}
                                        </div>

                                        {parseError && (
                                            <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-2xl p-4">
                                                <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                                <p className="text-xs text-rose-700 font-bold leading-relaxed">{parseError}</p>
                                            </div>
                                        )}

                                        {playlistType === 'm3u' ? (
                                            <>
                                                <button onClick={handleAddPlaylist} disabled={isParsing || !playlistName.trim() || !playlistUrl.trim()} className="w-full py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase hover:bg-indigo-700 transition-all shadow-md active:scale-95 disabled:opacity-40 flex items-center justify-center gap-2">
                                                    {isParsing ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Plus className="w-4 h-4" />}
                                                    {isParsing ? 'Parsing Playlist...' : 'Import Playlist URL'}
                                                </button>
                                                <div className="relative flex items-center justify-center my-4">
                                                    <div className="absolute inset-0 flex items-center"><div className="w-full border-t border-slate-100" /></div>
                                                    <span className="relative px-3 bg-white text-[9px] font-bold text-slate-400 uppercase">Or Upload File</span>
                                                </div>
                                                <label className="w-full flex flex-col items-center justify-center py-5 border border-dashed border-slate-200 rounded-2xl hover:bg-slate-50/50 transition-all cursor-pointer">
                                                    <Folder className="w-6 h-6 text-slate-400 mb-1.5" />
                                                    <span className="text-[10px] font-bold text-slate-700">Choose .m3u playlist file</span>
                                                    <span className="text-[9px] text-slate-400 mt-0.5">Loads directly to local database</span>
                                                    <input type="file" accept=".m3u,.m3u8,.txt" onChange={handleFileUpload} className="hidden" />
                                                </label>
                                            </>
                                        ) : (
                                            <button onClick={handleAddXtreamPlaylist} disabled={isParsing || !playlistName.trim() || !xtreamHost.trim() || !xtreamUser.trim() || !xtreamPass.trim()} className="w-full py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase hover:bg-indigo-700 transition-all shadow-md active:scale-95 disabled:opacity-40 flex items-center justify-center gap-2">
                                                {isParsing ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Plus className="w-4 h-4" />}
                                                {isParsing ? 'Authenticating...' : 'Add Xtream Playlist'}
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {/* Right list */}
                                <div className="lg:col-span-2 space-y-4">
                                    <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                                        <h3 className="font-extrabold text-slate-800 text-sm">Loaded Playlists</h3>
                                        
                                        {playlists.length === 0 ? (
                                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl">
                                                <Tv className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                                <h3 className="text-xs font-bold text-slate-900">No Playlists Installed</h3>
                                                <p className="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Input a streaming subscription URL or load an M3U file to index television channels locally.</p>
                                            </div>
                                        ) : (
                                            <div className="divide-y divide-slate-100">
                                                {playlists.map(pl => (
                                                    <div key={pl.id} className="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4 group">
                                                        <div className="flex items-center gap-3.5">
                                                            <div className="w-10 h-10 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-center">
                                                                <Tv className="w-5 h-5 text-indigo-600" />
                                                            </div>
                                                            <div>
                                                                <h4 className="font-bold text-slate-800 text-xs leading-none">{pl.name}</h4>
                                                                <div className="flex items-center gap-3 text-[10px] font-bold text-slate-500 mt-2">
                                                                    <span>{pl.total_channels.toLocaleString()} Channels</span>
                                                                    <span>•</span>
                                                                    <span className="text-rose-500">{pl.bookmarkedCount} Bookmarked</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="flex items-center gap-2">
                                                            <button
                                                                onClick={() => { setSelectedPlaylistId(pl.id); setActiveWorkspace('browser'); }}
                                                                className="px-3.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-[10px] font-bold uppercase transition-all"
                                                            >
                                                                Open Browser
                                                            </button>
                                                            <button
                                                                onClick={() => handleDeletePlaylist(pl.id)}
                                                                className="p-2 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-all"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* WORKSPACE 3: CHANNEL BROWSER */}
                    {activeWorkspace === 'browser' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <h1 className="text-2xl font-black tracking-tight text-slate-900">Channel Index Browser</h1>
                                    <p className="text-sm text-slate-400 mt-1">Search channels, toggle bookmarks, and initiate recordings.</p>
                                </div>

                                <div className="flex items-center gap-3">
                                    <select
                                        value={selectedPlaylistId}
                                        onChange={e => { setSelectedPlaylistId(e.target.value); setSelectedGroup(''); }}
                                        className="px-4 py-2 border border-slate-250 rounded-xl bg-white text-xs font-bold shadow-sm outline-none"
                                    >
                                        <option value="">Select Playlist...</option>
                                        {playlists.map(pl => <option key={pl.id} value={pl.id}>{pl.name}</option>)}
                                    </select>

                                    <button
                                        onClick={() => setBookmarkedOnly(!bookmarkedOnly)}
                                        className={`px-4 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5 ${bookmarkedOnly ? 'bg-rose-500 border-rose-500 text-white' : 'bg-white border-slate-250 text-slate-700'}`}
                                    >
                                        <Star className={`w-3.5 h-3.5 ${bookmarkedOnly ? 'fill-white' : ''}`} /> Favorites Only
                                    </button>
                                </div>
                            </div>

                            {/* Stream Type Tabs */}
                            {playlists.length > 0 && selectedPlaylistId && (
                                <div className="flex border-b border-slate-200">
                                    <button onClick={() => { setStreamType('live'); setSelectedGroup(''); }} className={`px-6 py-3 text-xs font-bold uppercase transition-all border-b-2 ${streamType === 'live' ? 'text-indigo-600 border-indigo-600' : 'text-slate-400 border-transparent hover:text-slate-700'}`}>Live TV</button>
                                    <button onClick={() => { setStreamType('vod'); setSelectedGroup(''); }} className={`px-6 py-3 text-xs font-bold uppercase transition-all border-b-2 ${streamType === 'vod' ? 'text-indigo-600 border-indigo-600' : 'text-slate-400 border-transparent hover:text-slate-700'}`}>Movies (VOD)</button>
                                    <button onClick={() => { setStreamType('series'); setSelectedGroup(''); }} className={`px-6 py-3 text-xs font-bold uppercase transition-all border-b-2 ${streamType === 'series' ? 'text-indigo-600 border-indigo-600' : 'text-slate-400 border-transparent hover:text-slate-700'}`}>Series</button>
                                </div>
                            )}

                            {playlists.length === 0 ? (
                                <div className="py-20 text-center border border-dashed border-slate-200 rounded-3xl bg-white">
                                    <Tv className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                    <h3 className="text-xs font-bold text-slate-900">No Playlists Registered</h3>
                                    <button onClick={() => setActiveWorkspace('playlists')} className="mt-4 px-4 py-2 bg-indigo-600 text-white text-xs font-bold uppercase rounded-xl hover:bg-indigo-700 transition-all">
                                        Go Install Playlist
                                    </button>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                                    {/* Sidebar Categories Column */}
                                    <div className="lg:col-span-1 space-y-4">
                                        <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
                                            <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Categories</h3>
                                            
                                            <div className="relative">
                                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                                <input
                                                    type="text"
                                                    value={searchQuery}
                                                    onChange={e => setSearchQuery(e.target.value)}
                                                    placeholder="Search channels..."
                                                    className="w-full pl-9 pr-4 py-2 text-xs font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none transition-all bg-slate-50"
                                                />
                                            </div>

                                            <div className="max-h-[50vh] overflow-y-auto space-y-1 pr-1.5 scrollbar-thin">
                                                <button
                                                    onClick={() => setSelectedGroup('')}
                                                    className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-xs font-bold transition-all ${selectedGroup === '' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                                >
                                                    <span>All Groups</span>
                                                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/50">{totalChannelsCount}</span>
                                                </button>
                                                
                                                {groups.map(g => (
                                                    <button
                                                        key={g.name}
                                                        onClick={() => setSelectedGroup(g.name)}
                                                        className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-xs font-bold transition-all ${selectedGroup === g.name ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                                    >
                                                        <span className="truncate">{g.name}</span>
                                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/50">{g.count}</span>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Channels List Area */}
                                    <div className="lg:col-span-3 space-y-4">
                                        <div className="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                                            {channels.length === 0 ? (
                                                <div className="py-24 text-center">
                                                    <Search className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                                    <h3 className="text-xs font-bold text-slate-900">No Matching Channels</h3>
                                                    <p className="text-xs text-slate-500 mt-1">Try relaxing filters or changing search criteria</p>
                                                </div>
                                            ) : (
                                                <div className="divide-y divide-slate-100">
                                                    {channels.map(ch => (
                                                        <div key={ch.id} className="p-4 flex items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                                                            <div className="flex items-center gap-3 min-w-0">
                                                                <div className="w-10 h-10 bg-slate-100 border border-slate-200 rounded-xl overflow-hidden flex items-center justify-center shrink-0">
                                                                    {ch.logo ? (
                                                                        <img src={ch.logo} alt={ch.name} className="w-full h-full object-contain" onError={(e: any) => { e.target.src = ''; }} />
                                                                    ) : (
                                                                        <Tv className="w-4.5 h-4.5 text-slate-400" />
                                                                    )}
                                                                </div>

                                                                <div className="min-w-0">
                                                                    <div className="flex items-center gap-2">
                                                                        <h4 className="font-bold text-slate-800 text-xs truncate leading-none">{ch.name}</h4>
                                                                        <button onClick={() => handleToggleBookmark(ch.id)} className="p-0.5 rounded text-slate-300 hover:text-rose-500 hover:bg-rose-50 transition-all shrink-0">
                                                                            <Star className={`w-3.5 h-3.5 ${ch.bookmarked === 1 ? 'text-rose-500 fill-rose-500' : ''}`} />
                                                                        </button>
                                                                    </div>
                                                                    <span className="text-[10px] font-bold text-indigo-600 block mt-1.5 truncate uppercase">{ch.group_title}</span>
                                                                </div>
                                                            </div>

                                                            <button
                                                                onClick={() => setShowRecordConfig(ch)}
                                                                className="px-3.5 py-1.5 bg-indigo-600 text-white rounded-lg text-[10px] font-black uppercase hover:bg-indigo-700 transition-all flex items-center gap-1 shrink-0"
                                                            >
                                                                <Play className="w-3 h-3 fill-white" /> Record
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        {/* Pagination panel */}
                                        {totalChannelsCount > itemsPerPage && (
                                            <div className="flex items-center justify-between bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                                                <button
                                                    onClick={() => handlePageChange(Math.max(1, currentPage - 1))}
                                                    disabled={currentPage === 1}
                                                    className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 uppercase hover:bg-slate-100 disabled:opacity-40 transition-all active:scale-95"
                                                >
                                                    Previous
                                                </button>
                                                <span className="text-[11px] font-bold text-slate-500">
                                                    Page {currentPage} of {Math.ceil(totalChannelsCount / itemsPerPage)}
                                                </span>
                                                <button
                                                    onClick={() => handlePageChange(Math.min(Math.ceil(totalChannelsCount / itemsPerPage), currentPage + 1))}
                                                    disabled={currentPage >= Math.ceil(totalChannelsCount / itemsPerPage)}
                                                    className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 uppercase hover:bg-slate-100 disabled:opacity-40 transition-all active:scale-95"
                                                >
                                                    Next
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* WORKSPACE 4: DOWNLOADS & RECORDS */}
                    {activeWorkspace === 'downloads' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-black tracking-tight text-slate-900">Downloads & Recordings</h1>
                                <p className="text-sm text-slate-400 mt-1">Track active stream recording sessions and review files saved locally.</p>
                            </div>

                            {/* Active recording progress cards */}
                            {Object.keys(downloadTasks).length > 0 && (
                                <div className="space-y-4">
                                    <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Active Capture Sessions</h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {Object.values(downloadTasks).map(task => (
                                            <div key={task.id} className="bg-white border border-slate-200 rounded-3xl p-6 shadow-md relative overflow-hidden space-y-4 border-l-4 border-l-indigo-600">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="min-w-0">
                                                        <h4 className="font-extrabold text-slate-950 text-sm truncate leading-none">{task.channel_name}</h4>
                                                        <p className="text-[10px] font-mono text-slate-400 truncate mt-2">{task.url}</p>
                                                    </div>
                                                    <button
                                                        onClick={() => handleStopTask(task.id)}
                                                        className="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-[9px] font-bold uppercase transition-all flex items-center gap-1 border border-rose-100 shrink-0"
                                                    >
                                                        <Square className="w-2.5 h-2.5 fill-rose-600" /> Stop Rec
                                                    </button>
                                                </div>

                                                <div className="space-y-1.5">
                                                    <div className="flex justify-between text-[10px] font-bold text-slate-500">
                                                        <span>{task.message}</span>
                                                        <span>{task.percent}%</span>
                                                    </div>
                                                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                                        <div
                                                            className="h-full bg-indigo-600 transition-all duration-500 rounded-full"
                                                            style={{ width: `${task.percent}%` }}
                                                        />
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between pt-1 border-t border-slate-50">
                                                    <button
                                                        onClick={() => setShowLogsTaskId(showLogsTaskId === task.id ? null : task.id)}
                                                        className="text-[9px] font-bold text-slate-400 hover:text-slate-900 transition-colors uppercase tracking-wider flex items-center gap-1"
                                                    >
                                                        <Terminal className="w-3.5 h-3.5" />
                                                        {showLogsTaskId === task.id ? 'Hide Logs console' : 'View execution logs'}
                                                    </button>
                                                    <span className="text-[9px] px-2 py-0.5 bg-indigo-50 border border-indigo-100 rounded text-indigo-700 font-bold uppercase tracking-wider leading-none">
                                                        Recording Live
                                                    </span>
                                                </div>

                                                {/* Logs Terminal view */}
                                                {showLogsTaskId === task.id && (
                                                    <div className="bg-slate-950 text-emerald-400 font-mono text-[9px] p-3 rounded-2xl max-h-48 overflow-y-auto space-y-0.5 mt-4 leading-relaxed animate-in slide-in-from-top duration-300">
                                                        <p className="text-slate-500">// Stream worker terminal stdout capture</p>
                                                        {taskLogs[task.id]?.map((logLine, idx) => (
                                                            <p key={idx}>{logLine}</p>
                                                        )) || <p className="text-slate-500">Awaiting stream packets...</p>}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Completed History List */}
                            <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                                <h3 className="font-extrabold text-slate-800 text-sm">Download History & Archive</h3>

                                {downloads.length === 0 ? (
                                    <div className="py-20 text-center">
                                        <Download className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                        <h3 className="text-xs font-bold text-slate-900 font-semibold">No recordings yet</h3>
                                        <p className="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Active and completed recording files appear here. Trigger a capture session to begin.</p>
                                    </div>
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {downloads.map(dl => {
                                            const isDone = dl.status === 'completed';
                                            const isFailed = dl.status === 'failed';
                                            const isStopped = dl.status === 'stopped';
                                            const isDl = dl.status === 'downloading';

                                            return (
                                                <div key={dl.id} className="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4 group">
                                                    <div className="flex items-center gap-3.5 min-w-0">
                                                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border ${
                                                            isDone ? 'bg-emerald-50 border-emerald-100 text-emerald-600' :
                                                            isFailed ? 'bg-rose-50 border-rose-100 text-rose-600' :
                                                            isStopped ? 'bg-amber-50 border-amber-100 text-amber-600' :
                                                            'bg-indigo-50 border-indigo-100 text-indigo-600'
                                                        }`}>
                                                            {isDone ? <CheckCircle2 className="w-5 h-5" /> :
                                                             isFailed ? <AlertCircle className="w-5 h-5" /> :
                                                             <Download className="w-5 h-5 animate-pulse" />}
                                                        </div>

                                                        <div className="min-w-0">
                                                            <h4 className="font-bold text-slate-800 text-xs leading-none truncate">{dl.channel_name}</h4>
                                                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[9px] font-bold text-slate-400 mt-2 truncate">
                                                                <span className={`capitalize px-2 py-0.5 rounded border ${
                                                                    isDone ? 'bg-emerald-50 text-emerald-700 border-emerald-100' :
                                                                    isFailed ? 'bg-rose-50 text-rose-700 border-rose-100' :
                                                                    isStopped ? 'bg-amber-50 text-amber-700 border-amber-100' :
                                                                    'bg-indigo-50 text-indigo-700 border-indigo-100'
                                                                }`}>
                                                                    {dl.status}
                                                                </span>
                                                                <span>•</span>
                                                                <span>{ (dl.size_bytes / (1024 * 1024)).toFixed(1) } MB</span>
                                                                <span>•</span>
                                                                <span>{dl.duration}s capture duration</span>
                                                                <span>•</span>
                                                                <span className="font-mono truncate">{dl.file_path}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div className="flex items-center gap-2 shrink-0">
                                                        <button
                                                            onClick={() => handleDeleteDownloadHistory(dl.id)}
                                                            className="p-2 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-all"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </main>

            {/* RECORD CONFIGURATION POPUP MODAL */}
            {showRecordConfig && (
                <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center font-sans p-4 animate-in fade-in duration-200">
                    <div className="bg-white border border-slate-200 rounded-3xl p-6 w-full max-w-sm shadow-xl space-y-6 animate-in scale-in duration-200">
                        <div className="flex justify-between items-start gap-4">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-center">
                                    <Tv className="w-4.5 h-4.5 text-indigo-600" />
                                </div>
                                <div>
                                    <h3 className="font-extrabold text-slate-950 text-sm">Configure Stream Recording</h3>
                                    <span className="text-[10px] text-slate-400 block font-semibold mt-0.5">{showRecordConfig.name}</span>
                                </div>
                            </div>
                            <button onClick={() => setShowRecordConfig(null)} className="p-1 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-800 transition-all text-xs font-bold leading-none">
                                Close
                            </button>
                        </div>

                        {/* Presets and Custom timing selector */}
                        <div className="space-y-4">
                            <label className="text-[10px] font-black text-slate-400 uppercase tracking-wide">Recording Duration</label>
                            
                            <div className="grid grid-cols-2 gap-2.5">
                                {[
                                    { l: 'Full VOD / Live Unlim.', v: 0 },
                                    { l: '60 Seconds Test', v: 60 },
                                    { l: '5 Minutes Loop', v: 300 },
                                    { l: '1 Hour Broadcast', v: 3600 },
                                ].map(p => (
                                    <button
                                        key={p.v}
                                        onClick={() => setRecordDurationPreset(p.v)}
                                        className={`px-3 py-2.5 border rounded-xl text-[10px] font-bold text-center transition-all ${recordDurationPreset === p.v ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'}`}
                                    >
                                        {p.l}
                                    </button>
                                ))}
                                <button
                                    onClick={() => setRecordDurationPreset(-1)}
                                    className={`px-3 py-2.5 border rounded-xl text-[10px] font-bold text-center transition-all col-span-2 ${recordDurationPreset === -1 ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'}`}
                                >
                                    Custom Seconds Duration
                                </button>
                            </div>

                            {recordDurationPreset === -1 && (
                                <div className="space-y-1.5 animate-in slide-in-from-top-2 duration-200">
                                    <input
                                        type="number"
                                        value={recordDurationCustom}
                                        onChange={e => setRecordDurationCustom(e.target.value)}
                                        placeholder="Enter duration in seconds (e.g. 180)"
                                        className="w-full px-4 py-2.5 text-xs font-semibold border border-slate-200 focus:border-indigo-400 rounded-xl outline-none bg-slate-50"
                                    />
                                </div>
                            )}
                        </div>

                        <button
                            onClick={triggerDownload}
                            className="w-full py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase hover:bg-indigo-700 transition-all shadow-md active:scale-95 flex items-center justify-center gap-1.5"
                        >
                            <Play className="w-3.5 h-3.5 fill-white" /> Start Local Capture
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
