import React, { useState, useEffect } from 'react';
import { Search, Users, MessageCircle, Heart, Play, Square, Download, Activity, ExternalLink, UserCheck, UserPlus } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

export default function FacebookExtractorRunner({ tool }: any) {
    const [url, setUrl] = useState('');
    const [type, setType] = useState('members');
    const [limit, setLimit] = useState<number | ''>('');
    const [status, setStatus] = useState<'idle' | 'running' | 'completed' | 'stopped'>('idle');
    const [count, setCount] = useState(0);
    const [extractionId, setExtractionId] = useState<string | null>(null);
    const [errorMsg, setError] = useState('');

    const [ws, setWs] = useState<WebSocket | null>(null);

    useEffect(() => {
        const socket = new WebSocket(getWsUrl());
        
        socket.onopen = () => {
            console.log('WS Connected');
        };

        socket.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                
                if (msg.event === 'facebook_extractor.progress' && msg.data?.extractionId === extractionId) {
                    setCount(msg.data.count);
                    setStatus('running');
                }
                if (msg.event === 'facebook_extractor.completed' && msg.data?.extractionId === extractionId) {
                    setCount(msg.data.total);
                    setStatus('completed');
                }
                if (msg.event === 'facebook_extractor.stopped' && msg.data?.extractionId === extractionId) {
                    setCount(msg.data.total);
                    setStatus('stopped');
                }
                
                // Watch for general RPC responses
                if (msg.type === 'plugin_rpc_res' && msg.payload?.status === 'started') {
                    setExtractionId(msg.payload.extractionId);
                    setStatus('running');
                    setCount(0);
                    setError('');
                }
                if (msg.type === 'plugin_rpc_res' && msg.payload?.status === 'stopped') {
                    setStatus('stopped');
                }
                if (msg.type === 'plugin_rpc_error') {
                    setError(msg.payload?.error || 'Unknown error');
                    setStatus('idle');
                }
            } catch (err) {}
        };
        
        setWs(socket);
        return () => socket.close();
    }, [extractionId]);

    const handleStart = () => {
        if (!url.trim()) return;
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            setError('Runtime not connected. Is Musoftware Desktop running?');
            return;
        }

        // Send RPC to start extraction
        ws.send(JSON.stringify({
            type: 'plugin_rpc',
            requestId: Date.now().toString(),
            payload: {
                plugin: 'facebook-extractor',
                action: 'start_extraction',
                data: {
                    targetUrl: url.trim(),
                    type,
                    limit: typeof limit === 'number' ? limit : 0
                }
            }
        }));
    };

    const handleStop = () => {
        if (!extractionId || !ws || ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify({
            type: 'plugin_rpc',
            requestId: Date.now().toString(),
            payload: {
                plugin: 'facebook-extractor',
                action: 'stop_extraction',
                data: { extractionId }
            }
        }));
    };

    const handleExport = (format: 'csv' | 'txt' | 'ids') => {
        if (!extractionId || !ws || ws.readyState !== WebSocket.OPEN) return;
        
        const reqId = `export_${Date.now()}`;
        
        // Setup temporary listener for this specific request
        const listener = (e: MessageEvent) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.type === 'plugin_rpc_res' && msg.requestId === reqId) {
                    ws.removeEventListener('message', listener);
                    
                    const payload = msg.payload;
                    if (!payload || !payload.data) return;
                    
                    const { headers, rows } = payload.data;
                    let fileContent = '';
                    let mimeType = 'text/plain;charset=utf-8;';
                    
                    if (format === 'csv') {
                        mimeType = 'text/csv;charset=utf-8;';
                        fileContent = [
                            headers.join(','),
                            ...rows.map((row: string[]) => row.map((cell: string) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
                        ].join('\n');
                    } else {
                        // For txt and ids, rows is an array of strings
                        fileContent = rows.join('\n');
                    }
                    
                    const blob = new Blob(['\uFEFF' + fileContent], { type: mimeType });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = payload.filename || `facebook_export_${extractionId}.${format === 'csv' ? 'csv' : 'txt'}`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            } catch (err) {}
        };
        
        ws.addEventListener('message', listener);
        
        ws.send(JSON.stringify({
            type: 'plugin_rpc',
            requestId: reqId,
            payload: {
                plugin: 'facebook-extractor',
                action: 'export_data',
                data: { extractionId, format }
            }
        }));
    };

    return (
        <div className="min-h-screen bg-slate-50 font-sans">
            {/* Top bar */}
            <div className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-[#1877F2] rounded-lg flex items-center justify-center shadow-sm">
                        <Users className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm text-slate-800 tracking-tight">{__('general.amc_facebook_data_extractor')}</span>
                </div>
                <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-slate-100 border-slate-200 text-slate-500'}`}>
                    <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'}`} />
                    {status === 'running' ? 'Extracting...' : status.toUpperCase()}
                </Badge>
            </div>

            <div className="max-w-3xl mx-auto px-4 py-8 space-y-6">
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">{__('general.facebook_data_extractor')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.extract_members_followers_following_comments_and_likes_directly_from_the_facebook_ui_without_apis')}</p>
                    </div>

                    <div className="space-y-4">
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-2.5">
                            <Button 
                                variant={type === 'members' ? 'default' : 'outline'} 
                                className={`h-12 flex flex-col items-center justify-center gap-1 p-2 ${type === 'members' ? 'bg-[#1877F2] hover:bg-[#166FE5] text-white' : ''}`}
                                onClick={() => setType('members')}
                            >
                                <Users className="w-4 h-4 shrink-0" />
                                <span className="text-[10px] md:text-xs text-center font-medium leading-none">{__('general.group_members')}</span>
                            </Button>
                            <Button 
                                variant={type === 'followers' ? 'default' : 'outline'} 
                                className={`h-12 flex flex-col items-center justify-center gap-1 p-2 ${type === 'followers' ? 'bg-[#1877F2] hover:bg-[#166FE5] text-white' : ''}`}
                                onClick={() => setType('followers')}
                            >
                                <UserPlus className="w-4 h-4 shrink-0" />
                                <span className="text-[10px] md:text-xs text-center font-medium leading-none">Followers</span>
                            </Button>
                            <Button 
                                variant={type === 'following' ? 'default' : 'outline'} 
                                className={`h-12 flex flex-col items-center justify-center gap-1 p-2 ${type === 'following' ? 'bg-[#1877F2] hover:bg-[#166FE5] text-white' : ''}`}
                                onClick={() => setType('following')}
                            >
                                <UserCheck className="w-4 h-4 shrink-0" />
                                <span className="text-[10px] md:text-xs text-center font-medium leading-none">Following</span>
                            </Button>
                            <Button 
                                variant={type === 'comments' ? 'default' : 'outline'} 
                                className={`h-12 flex flex-col items-center justify-center gap-1 p-2 ${type === 'comments' ? 'bg-[#1877F2] hover:bg-[#166FE5] text-white' : ''}`}
                                onClick={() => setType('comments')}
                            >
                                <MessageCircle className="w-4 h-4 shrink-0" />
                                <span className="text-[10px] md:text-xs text-center font-medium leading-none">{__('general.post_comments')}</span>
                            </Button>
                            <Button 
                                variant={type === 'likes' ? 'default' : 'outline'} 
                                className={`h-12 flex flex-col items-center justify-center gap-1 p-2 ${type === 'likes' ? 'bg-[#1877F2] hover:bg-[#166FE5] text-white' : ''}`}
                                onClick={() => setType('likes')}
                            >
                                <Heart className="w-4 h-4 shrink-0" />
                                <span className="text-[10px] md:text-xs text-center font-medium leading-none">{__('general.post_likes')}</span>
                            </Button>
                        </div>

                        <div className="space-y-3">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <Input
                                    type="url"
                                    value={url}
                                    onChange={e => setUrl(e.target.value)}
                                    placeholder={
                                        type === 'members' ? "https://www.facebook.com/groups/..." :
                                        (type === 'followers' || type === 'following') ? "https://www.facebook.com/profile.php?id=... OR username" :
                                        "https://www.facebook.com/post/..."
                                    }
                                    className="pl-9 h-11 text-sm bg-slate-50 font-mono"
                                    disabled={status === 'running'}
                                />
                            </div>
                            <div className="flex gap-3">
                                <Input
                                    type="number"
                                    value={limit}
                                    onChange={e => setLimit(e.target.value === '' ? '' : parseInt(e.target.value))}
                                    placeholder={__('general.limit_leave_empty_for_infinite')}
                                    className="h-11 text-sm bg-slate-50"
                                    disabled={status === 'running'}
                                />
                            </div>
                        </div>

                        {errorMsg && (
                            <div className="p-3 bg-red-50 text-red-600 text-sm font-medium rounded-lg border border-red-100">
                                {errorMsg}
                            </div>
                        )}

                        <div className="flex gap-3">
                            {status !== 'running' ? (
                                <Button
                                    onClick={handleStart}
                                    disabled={!url.trim()}
                                    className="flex-1 h-12 bg-[#1877F2] hover:bg-[#166FE5] text-white rounded-xl font-bold shadow-md"
                                >
                                    <Play className="w-4 h-4 mr-2 fill-current" />{__('general.start_extraction')}</Button>
                            ) : (
                                <Button
                                    onClick={handleStop}
                                    variant="destructive"
                                    className="flex-1 h-12 rounded-xl font-bold shadow-md"
                                >
                                    <Square className="w-4 h-4 mr-2 fill-current" />{__('general.stop_extraction')}</Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Progress / Results Card */}
                {(status === 'running' || status === 'completed' || status === 'stopped' || count > 0) && (
                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6 animate-in fade-in slide-in-from-bottom-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <Activity className="w-5 h-5 text-blue-500" />{__('general.extraction_progress')}</h2>
                            <span className="text-2xl font-black text-[#1877F2]">{count.toLocaleString()}</span>
                        </div>
                        
                        {status === 'running' && (
                            <div className="space-y-2">
                                <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div className="h-full bg-[#1877F2] w-full rounded-full animate-pulse" />
                                </div>
                                <p className="text-xs text-slate-500 text-center animate-pulse">{__('general.automating_browser_to_load_more_results_please_keep_the_facebook_tab_active')}</p>
                            </div>
                        )}

                        {(status === 'completed' || status === 'stopped') && (
                            <div className="pt-4 border-t border-slate-100 flex flex-col gap-3">
                                <p className="text-sm text-slate-600 font-medium text-center">
                                    {status === 'completed' ? 'Extraction completed successfully.' : 'Extraction stopped by user.'}
                                </p>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <Button 
                                        variant="outline" 
                                        className="h-11 font-bold gap-2 text-[#1877F2] border-[#1877F2] hover:bg-blue-50"
                                        onClick={() => handleExport('csv')}
                                    >
                                        <Download className="w-4 h-4" />{__('general.download_csv')}</Button>
                                    <Button 
                                        variant="outline" 
                                        className="h-11 font-bold gap-2 text-slate-600 border-slate-300 hover:bg-slate-50"
                                        onClick={() => handleExport('txt')}
                                    >
                                        <Download className="w-4 h-4" />{__('general.download_txt')}</Button>
                                    <Button 
                                        variant="outline" 
                                        className="h-11 font-bold gap-2 text-emerald-600 border-emerald-300 hover:bg-emerald-50"
                                        onClick={() => handleExport('ids')}
                                    >
                                        <Download className="w-4 h-4" />{__('general.export_ids_only')}</Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
