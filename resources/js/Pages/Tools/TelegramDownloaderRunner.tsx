import React, { useState, useEffect, useRef } from 'react';
import { Download, LayoutDashboard, Key, MonitorPlay, CheckCircle, AlertCircle, RefreshCw, Smartphone, Hash, Lock } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';

import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { __ } from '@/lib/i18n';

export default function TelegramDownloaderRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [activeTab, setActiveTab] = useState<'auth' | 'channels'>('auth');
    
    // Auth State
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [apiId, setApiId] = useState('');
    const [apiHash, setApiHash] = useState('');
    const [sessionString, setSessionString] = useState('');
    const [authPrompt, setAuthPrompt] = useState<string | null>(null);
    const [authInput, setAuthInput] = useState('');
    const [authError, setAuthError] = useState<string | null>(null);
    const [isAuthenticating, setIsAuthenticating] = useState(false);

    // Channels State
    const [channels, setChannels] = useState<any[]>([]);
    const [selectedChannel, setSelectedChannel] = useState<any>(null);
    const [downloadLimit, setDownloadLimit] = useState(50);
    const [outputDir, setOutputDir] = useState('C:/Downloads/TelegramMedia');
    
    // Logs
    const [realtimeLogs, setRealtimeLogs] = useState<any[]>([]);
    const [downloadProgress, setDownloadProgress] = useState(0);

    const addLog = (message: string) => {
        setRealtimeLogs(prev => [{ id: Math.random().toString(), message, time: new Date().toLocaleTimeString() }, ...prev].slice(0, 50));
    };

    const onBroadcast = (event: string, data: any) => {
        if (event === 'telegram-downloader.auth.required') {
            setAuthPrompt(data.type); // 'phone_number', 'verification_code', 'password'
            setAuthInput('');
            addLog(`Waiting for ${data.type.replace('_', ' ')}...`);
        }
        
        if (event === 'telegram-downloader.auth.success') {
            setIsAuthenticated(true);
            setSessionString(data.sessionString);
            setAuthPrompt(null);
            setIsAuthenticating(false);
            addLog('Successfully authenticated with Telegram.');
            // Save to local storage for future use
            localStorage.setItem('tg_api_id', apiId);
            localStorage.setItem('tg_api_hash', apiHash);
            localStorage.setItem('tg_session', data.sessionString);
        }

        if (event === 'telegram-downloader.auth.error') {
            setAuthError(data.error);
            setAuthPrompt(null);
            setIsAuthenticating(false);
            addLog(`Auth Error: ${data.error}`);
        }

        if (event === 'telegram-downloader.download.progress') {
            addLog(data.status);
            if (data.pct) setDownloadProgress(data.pct);
        }

        if (event === 'telegram-downloader.download.completed') {
            addLog(`Download completed! Processed ${data.processedCount} files.`);
            setDownloadProgress(0);
        }

        if (event === 'telegram-downloader.download.error') {
            addLog(`Download Error: ${data.error}`);
            setDownloadProgress(0);
        }
    };

    const { connected: agentConnected, callRPC, emitEvent, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('telegram-downloader', onBroadcast);

    useEffect(() => {
        if (agentConnected) {
            addLog('Connected to Local Runtime Agent.');
            const savedApiId = localStorage.getItem('tg_api_id');
            const savedApiHash = localStorage.getItem('tg_api_hash');
            const savedSession = localStorage.getItem('tg_session');
            if (savedApiId) setApiId(savedApiId);
            if (savedApiHash) setApiHash(savedApiHash);
            if (savedSession) setSessionString(savedSession);
        }
    }, [agentConnected]);

    const handleStartAuth = () => {
        if (!apiId || !apiHash) return alert("API ID and API Hash are required.");
        setIsAuthenticating(true);
        setAuthError(null);
        addLog('Initiating Telegram Authentication...');
        emitEvent('telegram-downloader.auth.start', { apiId, apiHash, sessionString });
    };

    const handleProvideAuthInput = () => {
        if (!authInput) return;
        emitEvent('telegram-downloader.auth.provide_input', { type: authPrompt, value: authInput });
        setAuthPrompt(null);
    };

    const fetchChannels = () => {
        addLog('Fetching channels...');
        emitEvent('telegram-downloader.channels.list', {});
        // In our worker we used respond(), which means we should use callRPC if we want the result synchronously
        // Let's use callRPC for getting channels.
        callRPC('telegram-downloader.channels.list').then((res: any) => {
            if (res.success) {
                setChannels(res.channels);
                addLog(`Found ${res.channels.length} channels.`);
            } else {
                addLog(`Error fetching channels: ${res.error}`);
            }
        }).catch(err => addLog(`RPC Error: ${err.message}`));
    };

    const startDownload = () => {
        if (!selectedChannel) return alert("Select a channel first.");
        addLog(`Initiating download for ${selectedChannel.title}...`);
        emitEvent('telegram-downloader.download.start', {
            channelId: selectedChannel.id,
            limit: downloadLimit,
            outputDir: outputDir
        });
    };

    if (!agentConnected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">{__('general.syncing_with_local_runtime_agent')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans flex flex-col antialiased">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />

            <header className="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center justify-between px-8 sticky top-0 z-30">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2.5">
                        <div className="w-6.5 h-6.5 bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                            <Download className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-bold text-sm tracking-tight text-slate-900">{__('general.telegram_downloader')}</span>
                    </div>
                    
                    <div className="h-5 w-px bg-slate-200" />
                    
                    <nav className="flex items-center gap-1.5">
                        <Button variant={activeTab === 'auth' ? 'default' : 'ghost'} size="sm" onClick={() => setActiveTab('auth')}>
                            {__('general.authentication')}</Button>
                        <Button variant={activeTab === 'channels' ? 'default' : 'ghost'} size="sm" onClick={() => setActiveTab('channels')} disabled={!isAuthenticated}>{__('general.channels_downloads')}</Button>
                    </nav>
                </div>
            </header>

            <div className="flex-1 flex overflow-hidden">
                {/* Main Workspace content */}
                <main className="flex-1 overflow-y-auto p-8 max-w-7xl mx-auto w-full">
                    {activeTab === 'auth' && (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-bold">{__('general.telegram_authentication')}</h1>
                                <p className="text-sm text-slate-500">{__('general.connect_to_your_telegram_account_using_mtproto_to_download_media_securely')}</p>
                            </div>

                            <Card className="max-w-xl">
                                <CardHeader>
                                    <CardTitle className="text-sm flex items-center gap-2">
                                        <Key className="w-4 h-4 text-slate-500" />{__('general.api_credentials')}</CardTitle>
                                    <CardDescription>{__('general.get_these_from_my_telegram_org')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {isAuthenticated ? (
                                        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                                            <CheckCircle className="w-6 h-6 text-emerald-600" />
                                            <div>
                                                <h4 className="font-bold text-emerald-900 text-sm">{__('general.authenticated')}</h4>
                                                <p className="text-xs text-emerald-700">{__('general.your_session_is_active_and_securely_saved')}</p>
                                            </div>
                                        </div>
                                    ) : (
                                        <>
                                            <div className="space-y-2">
                                                <Label>{__('general.api_id')}</Label>
                                                <Input value={apiId} onChange={e => setApiId(e.target.value)} placeholder={__('general.e_g_1234567')} disabled={isAuthenticating} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>{__('general.api_hash')}</Label>
                                                <Input value={apiHash} onChange={e => setApiHash(e.target.value)} placeholder={__('general.e_g_0123456789abcdef0123456789abcdef')} disabled={isAuthenticating} />
                                            </div>
                                            
                                            {authPrompt ? (
                                                <div className="p-4 border border-blue-200 bg-blue-50 rounded-xl space-y-3 mt-4">
                                                    <Label className="text-blue-900 font-bold flex items-center gap-2">
                                                        {authPrompt === 'phone_number' && <Smartphone className="w-4 h-4" />}
                                                        {authPrompt === 'verification_code' && <Hash className="w-4 h-4" />}
                                                        {authPrompt === 'password' && <Lock className="w-4 h-4" />}
                                                        Please enter your {authPrompt.replace('_', ' ')}
                                                    </Label>
                                                    <div className="flex gap-2">
                                                        <Input 
                                                            value={authInput} 
                                                            onChange={e => setAuthInput(e.target.value)} 
                                                            placeholder={`Enter ${authPrompt.replace('_', ' ')}...`}
                                                            autoFocus
                                                            onKeyDown={(e) => e.key === 'Enter' && handleProvideAuthInput()}
                                                        />
                                                        <Button onClick={handleProvideAuthInput}>{__('general.submit')}</Button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <Button onClick={handleStartAuth} disabled={isAuthenticating} className="w-full mt-4">
                                                    {isAuthenticating ? 'Connecting...' : 'Connect to Telegram'}
                                                </Button>
                                            )}

                                            {authError && (
                                                <div className="p-3 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg text-sm mt-4 flex items-center gap-2">
                                                    <AlertCircle className="w-4 h-4" /> {authError}
                                                </div>
                                            )}
                                        </>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {activeTab === 'channels' && (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h1 className="text-2xl font-bold">{__('general.channels_downloads')}</h1>
                                    <p className="text-sm text-slate-500">{__('general.select_a_channel_and_start_downloading_media')}</p>
                                </div>
                                <Button onClick={fetchChannels} variant="outline" className="gap-2">
                                    <RefreshCw className="w-4 h-4" />{__('general.refresh_channels')}</Button>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <Card className="md:col-span-2">
                                    <CardHeader>
                                        <CardTitle className="text-sm">{__('general.available_channels')}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-96 overflow-y-auto space-y-2 pe-2">
                                            {channels.length === 0 ? (
                                                <div className="text-center p-8 text-slate-500">{__('general.no_channels_fetched_click_refresh')}</div>
                                            ) : (
                                                channels.map(c => (
                                                    <div 
                                                        key={c.id} 
                                                        onClick={() => setSelectedChannel(c)}
                                                        className={`p-3 rounded-lg border cursor-pointer transition-colors ${selectedChannel?.id === c.id ? 'bg-blue-50 border-blue-200' : 'bg-white hover:bg-slate-50'}`}
                                                    >
                                                        <h4 className="font-semibold text-sm">{c.title}</h4>
                                                        <span className="text-xs text-slate-400">{c.isGroup ? 'Group' : 'Channel'} • {c.id}</span>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-sm">{__('general.download_settings')}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>{__('general.output_directory')}</Label>
                                            <Input value={outputDir} onChange={e => setOutputDir(e.target.value)} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>{__('general.message_limit')}</Label>
                                            <Input type="number" value={downloadLimit} onChange={e => setDownloadLimit(Number(e.target.value))} />
                                        </div>
                                        
                                        <div className="pt-4">
                                            <Button onClick={startDownload} disabled={!selectedChannel} className="w-full gap-2">
                                                <Download className="w-4 h-4" />{__('general.start_download')}</Button>
                                        </div>

                                        {downloadProgress > 0 && (
                                            <div className="mt-4 space-y-1">
                                                <div className="flex justify-between text-xs text-slate-500">
                                                    <span>{__('general.downloading')}</span>
                                                    <span>{downloadProgress}%</span>
                                                </div>
                                                <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                                    <div className="bg-blue-500 h-full" style={{ width: `${downloadProgress}%` }} />
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}
                </main>

                {/* Real-time Side Stream panel */}
                <aside className="w-72 border-s border-slate-200 bg-white flex flex-col hidden lg:flex shrink-0">
                    <div className="p-5 flex-1 flex flex-col min-h-0">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-1.5 h-1.5 rounded-full bg-blue-500 animate-ping" />
                            <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400">{__('general.live_activity_feed')}</h3>
                        </div>
                        <div className="flex-1 overflow-y-auto space-y-3 pe-1 font-sans scrollbar-thin">
                            {realtimeLogs.map(log => (
                                <div key={log.id} className="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <p className="text-slate-800 text-xs leading-relaxed font-medium">{log.message}</p>
                                    <span className="text-[10px] text-slate-400 font-mono mt-1 block">{log.time}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}
