import React, { useState, useEffect, useRef } from 'react';
import {
    Search, Play, Square, AlertCircle, RefreshCw, Server, User, Key,
    Folder, Tv, Terminal, Download, Trash2, List
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

function useRuntimeRPC(pluginSlug: string) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef(new Map());
    const onMessageCallbacks = useRef<Set<((...args: any[]) => any)>>(new Set());

    useEffect(() => {
        let socket: WebSocket;
        let reconnectTimer: any;

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
                            if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload.error));
                            else resolver.resolve(msg.payload);
                            pendingRequests.current.delete(msg.requestId);
                        }
                    }

                    for (const cb of onMessageCallbacks.current) {
                        cb(msg);
                    }
                } catch (err) { /* empty */ }
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

    const subscribeToEvents = (cb: ((...args: any[]) => any)) => {
        onMessageCallbacks.current.add(cb);
        return () => {
            onMessageCallbacks.current.delete(cb);
        };
    };

    return { connected, callRPC, subscribeToEvents };
}

export default function IPTVDownloaderRunner() {
    const { connected, callRPC, subscribeToEvents } = useRuntimeRPC('iptv-downloader');

    // Connection Info State
    const [connectionType, setConnectionType] = useState<'xtream' | 'm3u'>('xtream');
    const [xtreamHost, setXtreamHost] = useState('');
    const [xtreamUser, setXtreamUser] = useState('');
    const [xtreamPass, setXtreamPass] = useState('');
    const [m3uUrl, setM3uUrl] = useState('');
    const [isConnecting, setIsConnecting] = useState(false);
    const [connectionError, setConnectionError] = useState('');
    const [activePlaylistId, setActivePlaylistId] = useState<string>('');
    const [saveData, setSaveData] = useState(true);

    // Load saved connection info when connected
    useEffect(() => {
        if (!connected) return;

        callRPC('get_settings').then((res: any) => {
            const settings = res.settings || {};
            
            if (settings.iptv_saveData !== undefined) {
                setSaveData(settings.iptv_saveData === 'true');
            }
            
            if (settings.iptv_saveData !== 'false') {
                if (settings.iptv_connectionType === 'xtream' || settings.iptv_connectionType === 'm3u') {
                    setConnectionType(settings.iptv_connectionType);
                }
                if (settings.iptv_xtreamHost) setXtreamHost(settings.iptv_xtreamHost);
                if (settings.iptv_xtreamUser) setXtreamUser(settings.iptv_xtreamUser);
                if (settings.iptv_xtreamPass) setXtreamPass(settings.iptv_xtreamPass);
                if (settings.iptv_m3uUrl) setM3uUrl(settings.iptv_m3uUrl);
            }
        }).catch((err) => {
            console.error('Failed to load settings:', err);
        });
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [connected]);

    // Save connection info when it changes
    useEffect(() => {
        if (!connected) return;

        const timer = setTimeout(() => {
            const settingsToSave: Record<string, string> = {
                iptv_saveData: saveData.toString()
            };

            if (saveData) {
                settingsToSave.iptv_connectionType = connectionType;
                settingsToSave.iptv_xtreamHost = xtreamHost;
                settingsToSave.iptv_xtreamUser = xtreamUser;
                settingsToSave.iptv_xtreamPass = xtreamPass;
                settingsToSave.iptv_m3uUrl = m3uUrl;
            } else {
                settingsToSave.iptv_connectionType = '';
                settingsToSave.iptv_xtreamHost = '';
                settingsToSave.iptv_xtreamUser = '';
                settingsToSave.iptv_xtreamPass = '';
                settingsToSave.iptv_m3uUrl = '';
            }

            callRPC('save_settings', { settings: settingsToSave }).catch((err) => {
                console.error('Failed to save settings:', err);
            });
        }, 500);

        return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [connectionType, xtreamHost, xtreamUser, xtreamPass, m3uUrl, saveData, connected]);

    // Categories State
    const [groups, setGroups] = useState<any[]>([]);
    const [selectedGroup, setSelectedGroup] = useState<string>('');
    const [showLive, setShowLive] = useState(true);
    const [showMovies, setShowMovies] = useState(true);
    const [showSeries, setShowSeries] = useState(true);

    // Content State
    const [channels, setChannels] = useState<any[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedChannels, setSelectedChannels] = useState<Set<string>>(new Set());

    // Downloader Queue State
    const [downloadQueue, setDownloadQueue] = useState<any[]>([]);
    const [selectedQueueItems, setSelectedQueueItems] = useState<Set<string>>(new Set());
    const [downloadTasks, setDownloadTasks] = useState<Record<string, any>>({});
    
    // Logs State
    const [logs, setLogs] = useState<string[]>([]);
    const logsEndRef = useRef<HTMLDivElement>(null);

    // Listen for Runtime broadcasts
    useEffect(() => {
        const unsubscribe = subscribeToEvents((msg: any) => {
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

            if (msg.event === 'task.log') {
                const { level, message } = msg.data;
                const time = new Date().toLocaleTimeString();
                setLogs(prev => [...prev, `[${time}] [${level.toUpperCase()}] ${message}`].slice(-1000));
            }

            if (msg.event === 'task.done') {
                const { taskId } = msg.data;
                setDownloadTasks(prev => {
                    const copy = { ...prev };
                    if (copy[taskId]) copy[taskId].status = 'done';
                    return copy;
                });
            }

            if (msg.event === 'task.error') {
                const { taskId } = msg.data;
                setDownloadTasks(prev => {
                    const copy = { ...prev };
                    if (copy[taskId]) {
                        copy[taskId].status = 'error';
                        copy[taskId].message = 'Error';
                    }
                    return copy;
                });
            }
        });

        return () => unsubscribe();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [subscribeToEvents]);

    // Auto scroll logs
    useEffect(() => {
        logsEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [logs]);

    // Load groups when playlist changes
    useEffect(() => {
        if (connected && activePlaylistId) {
            fetchGroups();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activePlaylistId]);

    // Load channels when group/filters change
    useEffect(() => {
        if (connected && activePlaylistId) {
            fetchChannels();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedGroup, showLive, showMovies, showSeries]);

    const handleConnect = async () => {
        setIsConnecting(true);
        setConnectionError('');
        try {
            const name = 'Playlist_' + Math.random().toString(36).substring(7);
            
            // First clear old playlists to mimic a fresh connection
            const existingRes: any = await callRPC('list_playlists');
            for (const pl of existingRes.playlists) {
                await callRPC('delete_playlist', { id: pl.id });
            }

            if (connectionType === 'xtream') {
                if (!xtreamHost || !xtreamUser || !xtreamPass) {
                    throw new Error('Please fill all Xtream fields');
                }
                await callRPC('add_xtream_playlist', { name, host: xtreamHost, username: xtreamUser, password: xtreamPass });
            } else {
                if (!m3uUrl) throw new Error('Please enter M3U URL');
                await callRPC('add_playlist', { name, url: m3uUrl });
            }
            
            const updatedRes: any = await callRPC('list_playlists');
            if (updatedRes.playlists.length > 0) {
                setActivePlaylistId(updatedRes.playlists[0].id);
                setLogs(prev => [...prev, `[${new Date().toLocaleTimeString()}] [INFO] Connected successfully to server.`]);
            }
        } catch (err: any) {
            setConnectionError(err.message || 'Connection failed');
        } finally {
            setIsConnecting(false);
        }
    };

    const fetchGroups = async () => {
        try {
            const res: any = await callRPC('list_groups', { playlistId: activePlaylistId });
            setGroups(res.groups);
            if (res.groups.length > 0) setSelectedGroup('');
        } catch (err) { /* empty */ }
    };

    const fetchChannels = async () => {
        try {
            const allowedStreamTypes: string[] = [];
            if (showLive) allowedStreamTypes.push('live');
            if (showMovies) allowedStreamTypes.push('vod');
            if (showSeries) allowedStreamTypes.push('series');

            // if none checked, return empty
            if (allowedStreamTypes.length === 0) {
                setChannels([]);
                return;
            }

            // We do a combined fetch or just the first matched type for simplicity in UI clone
            // The API allows one streamType, so we fetch one by one and combine, or just rely on backend filter
            // Assuming we fetch all and filter in frontend for speed of UI clone if it's manageable
            
            // Actually, the easiest is to fetch with no streamType and let the DB return everything, then filter here or backend.
            // But if the backend requires streamType, we will fetch 'live', 'vod', 'series' sequentially
            let all: any[] = [];
            for (const type of allowedStreamTypes) {
                const res: any = await callRPC('list_channels', {
                    playlistId: activePlaylistId,
                    groupTitle: selectedGroup,
                    searchQuery: searchQuery,
                    streamType: type,
                    limit: 500, // Fetch top 500 for UI replication
                    offset: 0
                });
                all = [...all, ...res.channels];
            }
            setChannels(all);
            setSelectedChannels(new Set()); // Reset selections on new load
        } catch (err) { /* empty */ }
    };

    const handleSearch = () => {
        if (connected && activePlaylistId) {
            fetchChannels();
        }
    };

    const handleToggleChannelSelection = (id: string) => {
        setSelectedChannels(prev => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    const handleSelectAllChannels = (checked: boolean) => {
        if (checked) {
            setSelectedChannels(new Set(channels.map(c => c.id)));
        } else {
            setSelectedChannels(new Set());
        }
    };

    const handleAddToQueue = () => {
        const selected = channels.filter(c => selectedChannels.has(c.id));
        setDownloadQueue(prev => {
            const existingIds = new Set(prev.map(item => item.id));
            const newItems = selected.filter(s => !existingIds.has(s.id)).map(item => ({
                id: item.id,
                name: item.name,
                url: item.url,
                type: item.stream_type,
                status: 'queued', // queued, downloading, done, error
                percent: 0,
                message: 'Waiting...'
            }));
            return [...prev, ...newItems];
        });
        
        setSelectedChannels(new Set());
    };

    const handleToggleQueueSelection = (id: string) => {
        setSelectedQueueItems(prev => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };

    const handleSelectAllQueue = (checked: boolean) => {
        if (checked) {
            setSelectedQueueItems(new Set(downloadQueue.map(q => q.id)));
        } else {
            setSelectedQueueItems(new Set());
        }
    };

    const handleStartDownload = async () => {
        const toDownload = downloadQueue.filter(q => selectedQueueItems.has(q.id) && q.status !== 'downloading' && q.status !== 'done');
        
        for (const item of toDownload) {
            try {
                const response = await fetch(`${getRuntimeHttp()}/plugins/iptv-downloader/run`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        params: {
                            channelName: item.name,
                            url: item.url,
                            durationSeconds: 0 // Full download
                        }
                    })
                });
                const json = await response.json();
                if (!response.ok) throw new Error(json.error || 'Failed');
                
                // Track task mapping
                setDownloadTasks(prev => ({
                    ...prev,
                    [json.taskId]: {
                        id: json.taskId,
                        queueId: item.id,
                        percent: 0,
                        message: 'Initializing...',
                        status: 'downloading'
                    }
                }));
                
                // Update queue status
                setDownloadQueue(prev => prev.map(q => q.id === item.id ? { ...q, status: 'downloading', message: 'Starting...' } : q));

            } catch (err: any) {
                setLogs(prev => [...prev, `[${new Date().toLocaleTimeString()}] [ERROR] Failed to start download for ${item.name}: ${err.message}`]);
            }
        }
    };

    // Sync task progress to download queue UI
    useEffect(() => {
        if (Object.keys(downloadTasks).length > 0) {
            setDownloadQueue(prevQueue => {
                return prevQueue.map(q => {
                    // Find if there's a task for this queue item
                    const task = Object.values(downloadTasks).find((t: any) => t.queueId === q.id);
                    if (task) {
                        return { ...q, percent: task.percent, message: task.message, status: task.status };
                    }
                    return q;
                });
            });
        }
    }, [downloadTasks]);

    if (!connected) {
        return (
            <div className="min-h-[80vh] flex items-center justify-center font-sans bg-[#F9FAFB]">
                <div className="text-center space-y-6 max-w-sm p-8 bg-white border border-slate-100 rounded-3xl shadow-sm">
                    <div className="w-14 h-14 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-center mx-auto animate-pulse">
                        <Tv className="w-7 h-7 text-indigo-600 animate-spin" />
                    </div>
                    <div className="space-y-2">
                        <h3 className="font-bold text-slate-800">{__('general.linking_runtime_engine')}</h3>
                        <p className="text-xs text-slate-500 leading-relaxed">{__('general.ensure_the_musoftware_desktop_client_is_running_on_your_computer_to_activate_local_playback_and_recording_services')}</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col h-screen bg-[#F0F0F0] text-sm font-sans overflow-hidden">
            
            {/* Split Container Equivalent (Top area) */}
            <div className="flex-1 flex flex-row overflow-hidden border-b border-slate-300">
                
                {/* Left Panel */}
                <div className="w-80 flex flex-col border-e border-slate-300 bg-white">
                    
                    {/* Connection Info GroupBox */}
                    <div className="m-2 p-3 border border-slate-300 rounded-md">
                        <div className="flex items-center justify-between mb-3">
                            <span className="text-xs font-bold text-slate-700">{__('general.connection_info')}</span>
                            <div className="flex gap-2">
                                <Button variant="ghost" className={`h-6 px-2 py-0 text-[10px] ${connectionType === 'xtream' ? 'bg-slate-100 font-bold' : ''}`} onClick={() => setConnectionType('xtream')}>{__('general.xtream')}</Button>
                                <Button variant="ghost" className={`h-6 px-2 py-0 text-[10px] ${connectionType === 'm3u' ? 'bg-slate-100 font-bold' : ''}`} onClick={() => setConnectionType('m3u')}>M3U</Button>
                            </div>
                        </div>

                        {connectionType === 'xtream' ? (
                            <div className="space-y-2.5">
                                <div className="flex items-center gap-2">
                                    <Label className="w-16 text-end text-xs">{__('general.server')}</Label>
                                    <Input value={xtreamHost} onChange={e => setXtreamHost(e.target.value)} className="h-7 text-xs flex-1 rounded-sm" />
                                </div>
                                <div className="flex items-center gap-2">
                                    <Label className="w-16 text-end text-xs">{__('general.username')}</Label>
                                    <Input value={xtreamUser} onChange={e => setXtreamUser(e.target.value)} className="h-7 text-xs flex-1 rounded-sm" />
                                </div>
                                <div className="flex items-center gap-2">
                                    <Label className="w-16 text-end text-xs">{__('general.password')}</Label>
                                    <Input type="password" value={xtreamPass} onChange={e => setXtreamPass(e.target.value)} className="h-7 text-xs flex-1 rounded-sm" />
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-2.5">
                                <div className="flex items-center gap-2">
                                    <Label className="w-16 text-end text-xs">URL / Path</Label>
                                    <Input value={m3uUrl} onChange={e => setM3uUrl(e.target.value)} placeholder="http://..." className="h-7 text-xs flex-1 rounded-sm" />
                                </div>
                            </div>
                        )}

                        <div className="mt-3 flex items-center justify-between">
                            <div className="flex items-center space-x-1.5 ms-2">
                                <Checkbox id="chkSaveData" checked={saveData} onCheckedChange={(c) => setSaveData(c as boolean)} />
                                <Label htmlFor="chkSaveData" className="text-xs cursor-pointer text-slate-600">{__('general.save_data')}</Label>
                            </div>
                            <Button 
                                onClick={handleConnect} 
                                disabled={isConnecting}
                                className="h-7 bg-[#2ECC71] hover:bg-[#27AE60] text-white px-8 rounded-sm text-xs shadow-sm"
                            >
                                {isConnecting ? <RefreshCw className="w-3 h-3 animate-spin me-2" /> : null}
                                Connect
                            </Button>
                        </div>
                        {connectionError && <div className="mt-2 text-xs text-red-500 font-bold">{connectionError}</div>}
                    </div>

                    {/* Categories GroupBox */}
                    <div className="m-2 mt-0 p-3 border border-slate-300 rounded-md flex-1 flex flex-col min-h-0">
                        <span className="text-xs font-bold text-slate-700 mb-2 block">{__('general.categories')}</span>
                        
                        <div className="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200">
                            <div className="flex items-center space-x-1.5">
                                <Checkbox id="chkLive" checked={showLive} onCheckedChange={(c) => setShowLive(c as boolean)} />
                                <Label htmlFor="chkLive" className="text-xs cursor-pointer">{__('general.live')}</Label>
                            </div>
                            <div className="flex items-center space-x-1.5">
                                <Checkbox id="chkMovies" checked={showMovies} onCheckedChange={(c) => setShowMovies(c as boolean)} />
                                <Label htmlFor="chkMovies" className="text-xs cursor-pointer">{__('general.movies')}</Label>
                            </div>
                            <div className="flex items-center space-x-1.5">
                                <Checkbox id="chkSeries" checked={showSeries} onCheckedChange={(c) => setShowSeries(c as boolean)} />
                                <Label htmlFor="chkSeries" className="text-xs cursor-pointer">{__('general.series')}</Label>
                            </div>
                        </div>

                        <div className="flex-1 border border-slate-300 bg-white overflow-hidden">
                            <ScrollArea className="h-full">
                                <div className="divide-y divide-slate-100">
                                    <div 
                                        className={`px-3 py-1.5 text-xs cursor-pointer hover:bg-slate-50 ${selectedGroup === '' ? 'bg-[#007ACC] text-white font-bold' : ''}`}
                                        onClick={() => setSelectedGroup('')}
                                    >
                                        {__('general.all_categories')}</div>
                                    {groups.map((g, i) => (
                                        <div 
                                            key={i}
                                            className={`px-3 py-1.5 text-xs cursor-pointer hover:bg-slate-50 ${selectedGroup === g.title ? 'bg-[#007ACC] text-white font-bold' : ''}`}
                                            onClick={() => setSelectedGroup(g.title)}
                                        >
                                            {g.title}
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </div>
                    </div>

                </div>

                {/* Right Panel */}
                <div className="flex-1 flex flex-col p-2 bg-[#F0F0F0]">
                    <div className="border border-slate-300 rounded-md flex-1 flex flex-col min-h-0 p-3 bg-white">
                        <span className="text-xs font-bold text-slate-700 mb-2 block">{__('general.content')}</span>

                        {/* Search Box */}
                        <div className="flex items-center gap-2 mb-3">
                            <Input 
                                value={searchQuery} 
                                onChange={e => setSearchQuery(e.target.value)} 
                                onKeyDown={e => e.key === 'Enter' && handleSearch()}
                                className="h-7 text-xs flex-1 rounded-sm" 
                            />
                            <Button onClick={handleSearch} className="h-7 text-xs px-6 rounded-sm bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300">
                                {__('general.search')}</Button>
                        </div>

                        {/* Action Panel */}
                        <div className="flex items-center justify-between py-2 border-t border-slate-200 mb-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox 
                                    id="chkSelectAllContent" 
                                    checked={selectedChannels.size === channels.length && channels.length > 0} 
                                    onCheckedChange={(c) => handleSelectAllChannels(c as boolean)} 
                                />
                                <Label htmlFor="chkSelectAllContent" className="text-xs font-semibold cursor-pointer">{__('general.select_all')}</Label>
                            </div>
                            <Button 
                                onClick={handleAddToQueue}
                                disabled={selectedChannels.size === 0}
                                className="h-7 bg-[#E67E22] hover:bg-[#D35400] text-white px-6 rounded-sm text-xs font-bold shadow-sm"
                            >
                                {__('general.add_to_queue')}</Button>
                        </div>

                        {/* Content List */}
                        <div className="flex-1 border border-slate-300 bg-white overflow-hidden flex flex-col">
                            {/* Header row */}
                            <div className="flex border-b border-slate-200 bg-slate-50 px-2 py-1.5">
                                <div className="w-8"></div>
                                <div className="flex-1 text-xs font-bold text-slate-600">{__('general.name')}</div>
                            </div>
                            {/* Body */}
                            <ScrollArea className="flex-1">
                                {channels.map((ch) => (
                                    <div 
                                        key={ch.id} 
                                        className={`flex items-center border-b border-slate-100 px-2 py-1.5 hover:bg-slate-50 cursor-pointer ${selectedChannels.has(ch.id) ? 'bg-indigo-50/50' : ''}`}
                                        onClick={() => handleToggleChannelSelection(ch.id)}
                                    >
                                        <div className="w-8 flex items-center justify-center">
                                            <Checkbox 
                                                checked={selectedChannels.has(ch.id)} 
                                                onCheckedChange={() => handleToggleChannelSelection(ch.id)} 
                                            />
                                        </div>
                                        <div className="flex-1 text-xs truncate">
                                            {ch.name}
                                        </div>
                                    </div>
                                ))}
                                {channels.length === 0 && !isConnecting && activePlaylistId && (
                                    <div className="text-center py-10 text-slate-400 text-xs italic">{__('general.no_items_found_in_this_category')}</div>
                                )}
                            </ScrollArea>
                        </div>

                    </div>
                </div>

            </div>

            {/* Bottom Tab Control */}
            <div className="h-64 border-t border-slate-300 bg-[#F0F0F0] flex flex-col p-2 pt-0">
                <Tabs defaultValue="downloader" className="w-full flex-1 flex flex-col">
                    <TabsList className="h-8 justify-start bg-transparent rounded-none px-0 gap-1 border-b border-slate-300 w-full mb-2">
                        <TabsTrigger value="downloader" className="data-[state=active]:bg-white data-[state=active]:border-t data-[state=active]:border-x data-[state=active]:border-slate-300 border border-transparent border-b-0 rounded-t-sm text-xs px-6 h-full data-[state=active]:shadow-none relative z-10 data-[state=active]:-mb-px">
                            {__('general.downloader')}</TabsTrigger>
                        <TabsTrigger value="log" className="data-[state=active]:bg-white data-[state=active]:border-t data-[state=active]:border-x data-[state=active]:border-slate-300 border border-transparent border-b-0 rounded-t-sm text-xs px-6 h-full data-[state=active]:shadow-none relative z-10 data-[state=active]:-mb-px">
                            {__('general.log')}</TabsTrigger>
                    </TabsList>
                    
                    <TabsContent value="downloader" className="flex-1 m-0 flex flex-col min-h-0 data-[state=active]:flex bg-white border border-slate-300 p-2">
                        <div className="flex-1 border border-slate-300 flex flex-col min-h-0">
                            {/* Header */}
                            <div className="flex border-b border-slate-200 bg-slate-50 px-2 py-1.5">
                                <div className="w-8"></div>
                                <div className="w-1/2 text-xs font-bold text-slate-600 border-e border-slate-200 px-2">{__('general.name')}</div>
                                <div className="flex-1 text-xs font-bold text-slate-600 px-2">Progress / Status</div>
                            </div>
                            {/* Body */}
                            <ScrollArea className="flex-1 bg-white">
                                {downloadQueue.map((q) => (
                                    <div 
                                        key={q.id} 
                                        className={`flex items-center border-b border-slate-100 px-2 py-1.5 hover:bg-slate-50 cursor-pointer ${selectedQueueItems.has(q.id) ? 'bg-indigo-50/50' : ''}`}
                                        onClick={() => handleToggleQueueSelection(q.id)}
                                    >
                                        <div className="w-8 flex items-center justify-center">
                                            <Checkbox 
                                                checked={selectedQueueItems.has(q.id)} 
                                                onCheckedChange={() => handleToggleQueueSelection(q.id)} 
                                            />
                                        </div>
                                        <div className="w-1/2 text-xs truncate border-e border-slate-100 px-2 flex items-center gap-2">
                                            {q.name}
                                        </div>
                                        <div className="flex-1 text-xs truncate px-2 text-slate-600 font-mono">
                                            {q.status === 'downloading' ? `[${q.percent}%] ${q.message}` : q.message}
                                        </div>
                                    </div>
                                ))}
                            </ScrollArea>
                        </div>
                        <div className="h-10 mt-2 flex items-center border-t border-slate-200 pt-2 relative">
                            <Button 
                                onClick={handleStartDownload}
                                disabled={selectedQueueItems.size === 0}
                                className="bg-[#007ACC] hover:bg-[#005A9E] text-white font-bold h-7 px-6 rounded-sm text-xs shadow-sm absolute start-0"
                            >
                                {__('general.start_download')}</Button>
                            
                            <div className="flex items-center space-x-2 absolute start-[150px]">
                                <Checkbox 
                                    id="chkSelectAllQueue" 
                                    checked={selectedQueueItems.size === downloadQueue.length && downloadQueue.length > 0} 
                                    onCheckedChange={(c) => handleSelectAllQueue(c as boolean)} 
                                />
                                <Label htmlFor="chkSelectAllQueue" className="text-xs cursor-pointer">{__('general.select_all')}</Label>
                            </div>
                        </div>
                    </TabsContent>
                    
                    <TabsContent value="log" className="flex-1 m-0 data-[state=active]:flex flex-col min-h-0 bg-white border border-slate-300">
                        <div className="flex-1 font-mono text-[11px] overflow-auto p-2 text-slate-800 leading-relaxed bg-[#FAFAFA]">
                            {logs.map((log, i) => (
                                <div key={i} className={`whitespace-pre-wrap ${log.includes('[ERROR]') ? 'text-red-600 font-bold' : ''}`}>{log}</div>
                            ))}
                            <div ref={logsEndRef} />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    );
}
