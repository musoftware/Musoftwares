import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Inbox, Send, Eye, BarChart3, Settings, Plus, Trash2, Play, Square, Pause,
    RefreshCw, Download, Upload, Filter, Users, MessageCircle, Image, Clock,
    CheckCircle2, XCircle, AlertCircle, MoreHorizontal, Search, Loader2, X,
    Globe, Link2, ChevronRight, Hash, FileText, Zap, Activity, Radio
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogClose,
} from '@/Components/ui/dialog';

// ── Runtime Connection ──────────────────────────────────────────────────────

const getRuntimeHost = () => typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

function useRPC(pluginId: string) {
    const wsRef = useRef<WebSocket | null>(null);
    const handlersRef = useRef<Map<string, (data: any) => void>>(new Map());
    const eventHandlersRef = useRef<((event: string, data: any) => void)[]>([]);
    const [connected, setConnected] = useState(false);

    useEffect(() => {
        const socket = new WebSocket(getWsUrl());
        socket.onopen = () => setConnected(true);
        socket.onclose = () => setConnected(false);
        socket.onerror = () => setConnected(false);
        socket.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.type === 'plugin_rpc_res' && msg.requestId) {
                    const handler = handlersRef.current.get(msg.requestId);
                    if (handler) { handler(msg.payload); handlersRef.current.delete(msg.requestId); }
                }
                if (msg.type === 'plugin_rpc_error' && msg.requestId) {
                    const handler = handlersRef.current.get(msg.requestId);
                    if (handler) { handler({ _error: msg.payload?.error || 'RPC Error' }); handlersRef.current.delete(msg.requestId); }
                }
                if (msg.event) {
                    eventHandlersRef.current.forEach(h => h(msg.event, msg.data));
                }
            } catch {}
        };
        wsRef.current = socket;
        return () => socket.close();
    }, []);

    const call = useCallback((action: string, data: any = {}): Promise<any> => {
        return new Promise((resolve, reject) => {
            if (!wsRef.current || wsRef.current.readyState !== WebSocket.OPEN) {
                reject(new Error('Runtime not connected'));
                return;
            }
            const reqId = `${action}_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`;
            handlersRef.current.set(reqId, (res) => {
                if (res?._error) reject(new Error(res._error));
                else resolve(res);
            });
            wsRef.current.send(JSON.stringify({
                type: 'plugin_rpc', requestId: reqId,
                payload: { plugin: pluginId, action, data },
            }));
            setTimeout(() => {
                if (handlersRef.current.has(reqId)) {
                    handlersRef.current.delete(reqId);
                    reject(new Error('RPC timeout'));
                }
            }, 30000);
        });
    }, [pluginId]);

    const onEvent = useCallback((handler: (event: string, data: any) => void) => {
        eventHandlersRef.current.push(handler);
        return () => { eventHandlersRef.current = eventHandlersRef.current.filter(h => h !== handler); };
    }, []);

    return { call, onEvent, connected };
}

// ── Types ───────────────────────────────────────────────────────────────────

interface Profile { id: number; name: string; page_id: string; folder_path: string; sender_mode: string; created_at: string; }
interface InboxUser { id: number; profile_id: number; name: string; user_id: string; message: string; link: string; status: string; created_at: string; }
interface Campaign { id: number; profile_id: number; name: string; status: string; sender_mode: string; sent_count: number; failed_count: number; total: number; delay_min: number; delay_max: number; created_at: string; }
interface CampaignLog { id: number; campaign_id: number; user_name: string; user_fb_id: string; status: string; error: string; sent_at: string; }
interface WatchSession { id: number; profile_id: number; webhook_url: string; interval_sec: number; max_count: number; status: string; created_at: string; }
interface WatchResult { id: number; session_id: number; fb_user_id: string; name: string; posted_at: string; }

type TabKey = 'inbox' | 'campaigns' | 'watch' | 'logs' | 'settings';

// ── Main Component ──────────────────────────────────────────────────────────

export default function FbInboxSenderRunner({ tool }: any) {
    const { call, onEvent, connected } = useRPC('fb-inbox-sender');
    const [activeTab, setActiveTab] = useState<TabKey>('inbox');
    const [profiles, setProfiles] = useState<Profile[]>([]);
    const [selectedProfileId, setSelectedProfileId] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);

    // Load profiles on mount
    useEffect(() => {
        if (connected) loadProfiles();
    }, [connected]);

    const loadProfiles = async () => {
        try {
            const res = await call('getProfiles');
            setProfiles(res.profiles || []);
            if (res.profiles?.length && !selectedProfileId) setSelectedProfileId(res.profiles[0].id);
        } catch {}
    };

    const tabs: { key: TabKey; label: string; icon: React.ReactNode }[] = [
        { key: 'inbox', label: 'Inbox', icon: <Inbox className="w-4 h-4" /> },
        { key: 'campaigns', label: 'Campaigns', icon: <Send className="w-4 h-4" /> },
        { key: 'watch', label: 'Watch', icon: <Eye className="w-4 h-4" /> },
        { key: 'logs', label: 'Logs', icon: <BarChart3 className="w-4 h-4" /> },
        { key: 'settings', label: 'Settings', icon: <Settings className="w-4 h-4" /> },
    ];

    return (
        <div className="min-h-screen bg-[#0a0a0f] text-white font-sans">
            {/* Header */}
            <div className="sticky top-0 z-30 bg-[#0a0a0f]/95 backdrop-blur-xl border-b border-white/5">
                <div className="max-w-6xl mx-auto px-4">
                    <div className="h-14 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                                <Inbox className="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h1 className="text-sm font-bold tracking-tight">{__('general.messenger_bulk_sender')}</h1>
                                <p className="text-[10px] text-white/40 font-medium">{__('general.page_inbox_automation')}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            {/* Profile Selector */}
                            <select
                                className="h-8 px-3 bg-white/5 border border-white/10 rounded-lg text-xs font-medium text-white/80 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                value={selectedProfileId || ''}
                                onChange={e => setSelectedProfileId(Number(e.target.value) || null)}
                            >
                                {profiles.length === 0 && <option value="">{__('general.no_profiles')}</option>}
                                {profiles.map(p => (
                                    <option key={p.id} value={p.id} className="bg-[#1a1a2e]">{p.name} ({p.page_id})</option>
                                ))}
                            </select>
                            <Badge variant="outline" className={`gap-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${connected ? 'border-emerald-500/30 text-emerald-400 bg-emerald-500/10' : 'border-red-500/30 text-red-400 bg-red-500/10'}`}>
                                <div className={`w-1.5 h-1.5 rounded-full ${connected ? 'bg-emerald-400 animate-pulse' : 'bg-red-400'}`} />
                                {connected ? 'Connected' : 'Offline'}
                            </Badge>
                        </div>
                    </div>

                    {/* Tab Bar */}
                    <div className="flex gap-1 pb-0 -mb-px overflow-x-auto no-scrollbar">
                        {tabs.map(tab => (
                            <button
                                key={tab.key}
                                onClick={() => setActiveTab(tab.key)}
                                className={`flex items-center gap-2 px-4 py-2.5 text-xs font-bold tracking-wide transition-all whitespace-nowrap border-b-2 ${
                                    activeTab === tab.key
                                        ? 'border-blue-500 text-blue-400'
                                        : 'border-transparent text-white/40 hover:text-white/60'
                                }`}
                            >
                                {tab.icon}
                                <span className="hidden sm:inline">{tab.label}</span>
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="max-w-6xl mx-auto px-4 py-6">
                {activeTab === 'inbox' && <InboxTab call={call} onEvent={onEvent} profileId={selectedProfileId} profiles={profiles} onProfilesChange={loadProfiles} />}
                {activeTab === 'campaigns' && <CampaignsTab call={call} onEvent={onEvent} profileId={selectedProfileId} />}
                {activeTab === 'watch' && <WatchTab call={call} onEvent={onEvent} profileId={selectedProfileId} />}
                {activeTab === 'logs' && <LogsTab call={call} profileId={selectedProfileId} />}
                {activeTab === 'settings' && <SettingsTab call={call} profiles={profiles} onProfilesChange={loadProfiles} />}
            </div>
        </div>
    );
}

// ── Inbox Tab ───────────────────────────────────────────────────────────────

function InboxTab({ call, onEvent, profileId, profiles, onProfilesChange }: any) {
    const [users, setUsers] = useState<InboxUser[]>([]);
    const [count, setCount] = useState(0);
    const [maxCount, setMaxCount] = useState(500);
    const [loadingInbox, setLoadingInbox] = useState(false);
    const [loadingProgress, setLoadingProgress] = useState(0);
    const [showAddProfile, setShowAddProfile] = useState(false);
    const [newProfileName, setNewProfileName] = useState('');
    const [newPageId, setNewPageId] = useState('');
    const [showLoadByIds, setShowLoadByIds] = useState(false);
    const [manualIds, setManualIds] = useState('');
    const [showFilter, setShowFilter] = useState(false);
    const [allowPatterns, setAllowPatterns] = useState('');
    const [blockPatterns, setBlockPatterns] = useState('');
    const [selectedUsers, setSelectedUsers] = useState<Set<number>>(new Set());

    useEffect(() => { if (profileId) loadUsers(); }, [profileId]);

    useEffect(() => {
        return onEvent((event: string, data: any) => {
            if (event === 'fb.inbox.user_found' && data.profileId === profileId) {
                setLoadingProgress(data.count);
                setUsers(prev => {
                    const exists = prev.some(u => u.user_id === data.user.user_id);
                    if (exists) return prev;
                    return [{ ...data.user, id: Date.now(), profile_id: profileId, status: 'loaded', created_at: new Date().toISOString() }, ...prev];
                });
                setCount(data.count);
            }
            if (event === 'fb.inbox.loaded' && data.profileId === profileId) {
                setLoadingInbox(false);
                loadUsers();
            }
        });
    }, [profileId, onEvent]);

    const loadUsers = async () => {
        if (!profileId) return;
        try {
            const res = await call('getInboxUsers', { profileId });
            setUsers(res.users || []);
            setCount(res.count || 0);
        } catch {}
    };

    const handleStartLoading = async () => {
        if (!profileId) return;
        setLoadingInbox(true);
        setLoadingProgress(0);
        try {
            await call('loadInbox', { profileId, maxCount });
        } catch { setLoadingInbox(false); }
    };

    const handleStopLoading = async () => {
        if (!profileId) return;
        try { await call('stopLoading', { profileId }); } catch {}
        setLoadingInbox(false);
    };

    const handleClear = async () => {
        if (!profileId) return;
        await call('clearInboxUsers', { profileId });
        setUsers([]);
        setCount(0);
    };

    const handleExport = async () => {
        if (!profileId) return;
        try {
            const res = await call('exportUsers', { profileId });
            const blob = new Blob(['\uFEFF' + res.csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `inbox_users_${profileId}_${Date.now()}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch {}
    };

    const handleAddProfile = async () => {
        if (!newProfileName.trim() || !newPageId.trim()) return;
        await call('saveProfile', { name: newProfileName, page_id: newPageId });
        setShowAddProfile(false);
        setNewProfileName('');
        setNewPageId('');
        onProfilesChange();
    };

    const handleLoadByIds = async () => {
        if (!profileId || !manualIds.trim()) return;
        const ids = manualIds.split(/[\n,]+/).map(s => s.trim()).filter(Boolean);
        await call('loadByIds', { profileId, ids });
        setShowLoadByIds(false);
        setManualIds('');
        loadUsers();
    };

    const handleApplyFilters = async () => {
        if (!profileId) return;
        const rules = [
            ...allowPatterns.split('\n').filter(Boolean).map(p => ({ type: 'allow', pattern: p.trim() })),
            ...blockPatterns.split('\n').filter(Boolean).map(p => ({ type: 'block', pattern: p.trim() })),
        ];
        await call('saveFilterRules', { profileId, rules });
        await call('applyFilters', { profileId });
        setShowFilter(false);
        loadUsers();
    };

    const handleDeleteSelected = async () => {
        if (selectedUsers.size === 0) return;
        await call('deleteInboxUsers', { ids: Array.from(selectedUsers) });
        setSelectedUsers(new Set());
        loadUsers();
    };

    return (
        <div className="space-y-6">
            {/* Quick Actions Bar */}
            <div className="flex flex-wrap gap-2">
                <Button
                    onClick={handleStartLoading}
                    disabled={loadingInbox || !profileId}
                    className="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs gap-2 h-9 rounded-xl shadow-lg shadow-blue-600/20"
                >
                    {loadingInbox ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Play className="w-3.5 h-3.5" />}
                    {loadingInbox ? `Loading (${loadingProgress})...` : 'Load Inbox'}
                </Button>
                {loadingInbox && (
                    <Button onClick={handleStopLoading} variant="destructive" className="font-bold text-xs gap-2 h-9 rounded-xl">
                        <Square className="w-3.5 h-3.5" /> Stop
                    </Button>
                )}
                <Button onClick={() => setShowLoadByIds(true)} variant="outline" className="font-bold text-xs gap-2 h-9 rounded-xl border-white/10 text-white/70 hover:bg-white/5" disabled={!profileId}>
                    <Hash className="w-3.5 h-3.5" />{__('general.load_by_ids')}</Button>
                <Button onClick={() => setShowFilter(true)} variant="outline" className="font-bold text-xs gap-2 h-9 rounded-xl border-white/10 text-white/70 hover:bg-white/5" disabled={!profileId}>
                    <Filter className="w-3.5 h-3.5" /> Filter
                </Button>
                <Button onClick={handleExport} variant="outline" className="font-bold text-xs gap-2 h-9 rounded-xl border-white/10 text-white/70 hover:bg-white/5" disabled={count === 0}>
                    <Download className="w-3.5 h-3.5" /> Export
                </Button>
                <Button onClick={() => setShowAddProfile(true)} variant="outline" className="font-bold text-xs gap-2 h-9 rounded-xl border-white/10 text-white/70 hover:bg-white/5">
                    <Plus className="w-3.5 h-3.5" />{__('general.add_profile')}</Button>
                {selectedUsers.size > 0 && (
                    <Button onClick={handleDeleteSelected} variant="destructive" className="font-bold text-xs gap-2 h-9 rounded-xl">
                        <Trash2 className="w-3.5 h-3.5" /> Delete ({selectedUsers.size})
                    </Button>
                )}
            </div>

            {/* Max Count Input */}
            <div className="flex items-center gap-3">
                <Label className="text-[10px] font-bold uppercase tracking-wider text-white/40">{__('general.max_users')}</Label>
                <Input
                    type="number" value={maxCount} onChange={e => setMaxCount(Number(e.target.value) || 500)}
                    className="w-28 h-8 bg-white/5 border-white/10 text-white text-xs"
                />
                <span className="text-xs text-white/30">{count} users loaded</span>
            </div>

            {/* Loading Progress */}
            {loadingInbox && (
                <div className="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4 space-y-2 animate-in fade-in">
                    <div className="flex items-center justify-between text-xs">
                        <span className="font-bold text-blue-400 flex items-center gap-2">
                            <Loader2 className="w-3.5 h-3.5 animate-spin" />{__('general.loading_inbox')}</span>
                        <span className="font-mono text-blue-300">{loadingProgress} / {maxCount}</span>
                    </div>
                    <div className="h-1.5 bg-blue-500/20 rounded-full overflow-hidden">
                        <div className="h-full bg-blue-500 rounded-full transition-all" style={{ width: `${Math.min((loadingProgress / maxCount) * 100, 100)}%` }} />
                    </div>
                </div>
            )}

            {/* Users Table */}
            <div className="bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-white/5">
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-white/30 w-10">
                                    <input type="checkbox" className="rounded bg-white/5 border-white/20"
                                        checked={selectedUsers.size === users.length && users.length > 0}
                                        onChange={e => setSelectedUsers(e.target.checked ? new Set(users.map(u => u.id)) : new Set())}
                                    />
                                </th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-white/30">Name</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-white/30 hidden md:table-cell">Message</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-white/30 hidden lg:table-cell">{__('general.user_id')}</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-white/30">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.length === 0 ? (
                                <tr><td colSpan={5} className="px-4 py-12 text-center text-xs text-white/20">{__('general.no_inbox_users_loaded_click_quot_load_inbox_quot_to_start')}</td></tr>
                            ) : users.slice(0, 200).map(user => (
                                <tr key={user.id} className="border-b border-white/[0.03] hover:bg-white/[0.02] transition-colors">
                                    <td className="px-4 py-2.5">
                                        <input type="checkbox" className="rounded bg-white/5 border-white/20"
                                            checked={selectedUsers.has(user.id)}
                                            onChange={e => {
                                                const next = new Set(selectedUsers);
                                                e.target.checked ? next.add(user.id) : next.delete(user.id);
                                                setSelectedUsers(next);
                                            }}
                                        />
                                    </td>
                                    <td className="px-4 py-2.5 text-xs font-semibold text-white/80">{user.name}</td>
                                    <td className="px-4 py-2.5 text-xs text-white/40 max-w-xs truncate hidden md:table-cell">{user.message}</td>
                                    <td className="px-4 py-2.5 text-xs font-mono text-white/30 hidden lg:table-cell">{user.user_id}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant="outline" className={`text-[9px] font-bold uppercase ${
                                            user.status === 'sent' ? 'border-emerald-500/30 text-emerald-400' :
                                            user.status === 'failed' ? 'border-red-500/30 text-red-400' :
                                            'border-white/10 text-white/40'
                                        }`}>{user.status}</Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {users.length > 200 && (
                    <div className="px-4 py-3 border-t border-white/5 text-xs text-white/30 text-center">
                        Showing 200 of {users.length} users
                    </div>
                )}
            </div>

            {/* Clear Button */}
            {count > 0 && (
                <Button onClick={handleClear} variant="ghost" className="text-xs text-red-400 hover:text-red-300 hover:bg-red-500/10 gap-2">
                    <Trash2 className="w-3.5 h-3.5" /> Clear All ({count})
                </Button>
            )}

            {/* Add Profile Dialog */}
            <Dialog open={showAddProfile} onOpenChange={setShowAddProfile}>
                <DialogContent className="bg-[#1a1a2e] border-white/10 text-white">
                    <DialogHeader><DialogTitle className="text-sm font-bold">{__('general.add_facebook_page_profile')}</DialogTitle></DialogHeader>
                    <div className="space-y-4">
                        <div><Label className="text-[10px] font-bold uppercase text-white/50">{__('general.profile_name')}</Label><Input value={newProfileName} onChange={e => setNewProfileName(e.target.value)} placeholder={__('general.my_business_page')} className="bg-white/5 border-white/10 text-white mt-1" /></div>
                        <div><Label className="text-[10px] font-bold uppercase text-white/50">{__('general.page_id')}</Label><Input value={newPageId} onChange={e => setNewPageId(e.target.value)} placeholder="123456789" className="bg-white/5 border-white/10 text-white mt-1" /></div>
                    </div>
                    <DialogFooter><Button onClick={handleAddProfile} className="bg-blue-600 hover:bg-blue-700 font-bold text-xs">{__('general.create_profile')}</Button></DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Load by IDs Dialog */}
            <Dialog open={showLoadByIds} onOpenChange={setShowLoadByIds}>
                <DialogContent className="bg-[#1a1a2e] border-white/10 text-white">
                    <DialogHeader><DialogTitle className="text-sm font-bold">{__('general.load_users_by_facebook_ids')}</DialogTitle></DialogHeader>
                    <div><Label className="text-[10px] font-bold uppercase text-white/50">User IDs (one per line)</Label>
                        <textarea value={manualIds} onChange={e => setManualIds(e.target.value)} placeholder={"100001234567890\n100009876543210"} rows={8}
                            className="w-full mt-1 p-3 bg-white/5 border border-white/10 rounded-lg text-xs font-mono text-white resize-none focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>
                    <DialogFooter><Button onClick={handleLoadByIds} className="bg-blue-600 hover:bg-blue-700 font-bold text-xs">{__('general.load_ids')}</Button></DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Filter Dialog */}
            <Dialog open={showFilter} onOpenChange={setShowFilter}>
                <DialogContent className="bg-[#1a1a2e] border-white/10 text-white">
                    <DialogHeader><DialogTitle className="text-sm font-bold">{__('general.filter_inbox_users')}</DialogTitle></DialogHeader>
                    <div className="space-y-4">
                        <div><Label className="text-[10px] font-bold uppercase text-emerald-400">Allow List (one per line)</Label>
                            <textarea value={allowPatterns} onChange={e => setAllowPatterns(e.target.value)} placeholder={__('general.keep_users_whose_message_contains')} rows={4}
                                className="w-full mt-1 p-3 bg-white/5 border border-white/10 rounded-lg text-xs text-white resize-none focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        </div>
                        <div><Label className="text-[10px] font-bold uppercase text-red-400">Block List (one per line)</Label>
                            <textarea value={blockPatterns} onChange={e => setBlockPatterns(e.target.value)} placeholder={__('general.remove_users_whose_message_contains')} rows={4}
                                className="w-full mt-1 p-3 bg-white/5 border border-white/10 rounded-lg text-xs text-white resize-none focus:outline-none focus:ring-1 focus:ring-red-500" />
                        </div>
                    </div>
                    <DialogFooter><Button onClick={handleApplyFilters} className="bg-blue-600 hover:bg-blue-700 font-bold text-xs">{__('general.apply_filters')}</Button></DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

// ── Campaigns Tab ───────────────────────────────────────────────────────────

function CampaignsTab({ call, onEvent, profileId }: any) {
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [showCreate, setShowCreate] = useState(false);
    const [name, setName] = useState('');
    const [senderMode, setSenderMode] = useState('www');
    const [delayMin, setDelayMin] = useState(5);
    const [delayMax, setDelayMax] = useState(15);
    const [messages, setMessages] = useState<string[]>(['']);
    const [liveProgress, setLiveProgress] = useState<Record<number, { sent: number; failed: number; percent: number }>>({});

    useEffect(() => { if (profileId) loadCampaigns(); }, [profileId]);

    useEffect(() => {
        return onEvent((event: string, data: any) => {
            if (event === 'fb.campaign.progress') {
                setLiveProgress(prev => ({ ...prev, [data.campaignId]: { sent: data.sent, failed: data.failed, percent: data.percent } }));
            }
            if (event === 'fb.campaign.completed' || event === 'fb.campaign.stopped') {
                loadCampaigns();
            }
        });
    }, [onEvent]);

    const loadCampaigns = async () => {
        try {
            const res = await call('getCampaigns', { profileId });
            setCampaigns(res.campaigns || []);
        } catch {}
    };

    const handleCreate = async () => {
        if (!name.trim() || !profileId) return;
        const validMessages = messages.filter(m => m.trim());
        await call('createCampaign', {
            profile_id: profileId, name, sender_mode: senderMode,
            delay_min: delayMin, delay_max: delayMax,
            messages: validMessages.length ? validMessages : ['Hello $$NAME$$!'],
        });
        setShowCreate(false);
        setName('');
        setMessages(['']);
        loadCampaigns();
    };

    const handleAction = async (campaignId: number, action: string) => {
        try { await call(action, { id: campaignId }); loadCampaigns(); } catch {}
    };

    const statusColor = (s: string) => {
        switch(s) {
            case 'running': return 'border-blue-500/30 text-blue-400 bg-blue-500/10';
            case 'completed': return 'border-emerald-500/30 text-emerald-400 bg-emerald-500/10';
            case 'paused': return 'border-amber-500/30 text-amber-400 bg-amber-500/10';
            case 'failed': case 'cancelled': return 'border-red-500/30 text-red-400 bg-red-500/10';
            default: return 'border-white/10 text-white/40';
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-lg font-bold tracking-tight">Campaigns</h2>
                <Button onClick={() => setShowCreate(true)} className="bg-blue-600 hover:bg-blue-700 font-bold text-xs gap-2 h-9 rounded-xl" disabled={!profileId}>
                    <Plus className="w-3.5 h-3.5" />{__('general.new_campaign')}</Button>
            </div>

            {campaigns.length === 0 ? (
                <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-12 text-center">
                    <Send className="w-8 h-8 text-white/10 mx-auto mb-3" />
                    <p className="text-xs text-white/20">{__('general.no_campaigns_yet_create_one_to_start_sending')}</p>
                </div>
            ) : (
                <div className="grid gap-4">
                    {campaigns.map(c => {
                        const progress = liveProgress[c.id];
                        return (
                            <div key={c.id} className="bg-white/[0.02] border border-white/5 rounded-2xl p-5 space-y-4 hover:border-white/10 transition-colors">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <h3 className="text-sm font-bold text-white/90">{c.name}</h3>
                                        <Badge variant="outline" className={`text-[9px] font-bold uppercase ${statusColor(c.status)}`}>{c.status}</Badge>
                                    </div>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-white/30 hover:text-white/60 hover:bg-white/5">
                                                <MoreHorizontal className="w-4 h-4" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="bg-[#1a1a2e] border-white/10 text-white sm:max-w-xs">
                                            <DialogHeader><DialogTitle className="text-sm">Actions</DialogTitle></DialogHeader>
                                            <div className="flex flex-col gap-2 py-2">
                                                {c.status === 'draft' && <Button variant="outline" className="justify-start border-white/10 text-white/70" onClick={() => handleAction(c.id, 'startCampaign')}><Play className="w-4 h-4 mr-2" /> Start</Button>}
                                                {c.status === 'running' && <Button variant="outline" className="justify-start border-white/10 text-white/70" onClick={() => handleAction(c.id, 'pauseCampaign')}><Pause className="w-4 h-4 mr-2" /> Pause</Button>}
                                                {c.status === 'paused' && <Button variant="outline" className="justify-start border-white/10 text-white/70" onClick={() => handleAction(c.id, 'resumeCampaign')}><Play className="w-4 h-4 mr-2" /> Resume</Button>}
                                                {['running', 'paused'].includes(c.status) && <Button variant="destructive" className="justify-start" onClick={() => handleAction(c.id, 'cancelCampaign')}><Square className="w-4 h-4 mr-2" /> Stop</Button>}
                                                <Button variant="destructive" className="justify-start" onClick={() => handleAction(c.id, 'deleteCampaign')}><Trash2 className="w-4 h-4 mr-2" /> Delete</Button>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                </div>

                                {/* Progress */}
                                <div className="flex items-center gap-4 text-xs text-white/40">
                                    <span className="flex items-center gap-1"><CheckCircle2 className="w-3 h-3 text-emerald-400" /> {progress?.sent ?? c.sent_count}</span>
                                    <span className="flex items-center gap-1"><XCircle className="w-3 h-3 text-red-400" /> {progress?.failed ?? c.failed_count}</span>
                                    <span className="flex items-center gap-1"><Users className="w-3 h-3" /> {c.total}</span>
                                    <span className="flex items-center gap-1"><Globe className="w-3 h-3" /> {c.sender_mode?.toUpperCase()}</span>
                                    <span className="flex items-center gap-1"><Clock className="w-3 h-3" /> {c.delay_min}-{c.delay_max}s</span>
                                </div>

                                {c.status === 'running' && (
                                    <div className="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                        <div className="h-full bg-blue-500 rounded-full transition-all" style={{ width: `${progress?.percent ?? 0}%` }} />
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Create Campaign Dialog */}
            <Dialog open={showCreate} onOpenChange={setShowCreate}>
                <DialogContent className="bg-[#1a1a2e] border-white/10 text-white max-w-lg">
                    <DialogHeader><DialogTitle className="text-sm font-bold">{__('general.create_campaign')}</DialogTitle></DialogHeader>
                    <div className="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                        <div><Label className="text-[10px] font-bold uppercase text-white/50">{__('general.campaign_name')}</Label><Input value={name} onChange={e => setName(e.target.value)} placeholder={__('general.my_campaign')} className="bg-white/5 border-white/10 text-white mt-1" /></div>

                        <div><Label className="text-[10px] font-bold uppercase text-white/50">{__('general.sender_mode')}</Label>
                            <div className="flex gap-2 mt-1">
                                {['www', 'm', 'mbasic'].map(m => (
                                    <Button key={m} variant={senderMode === m ? 'default' : 'outline'} onClick={() => setSenderMode(m)}
                                        className={`flex-1 text-xs font-bold h-9 ${senderMode === m ? 'bg-blue-600' : 'border-white/10 text-white/60'}`}>{m.toUpperCase()}</Button>
                                ))}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div><Label className="text-[10px] font-bold uppercase text-white/50">Delay Min (sec)</Label><Input type="number" value={delayMin} onChange={e => setDelayMin(Number(e.target.value))} className="bg-white/5 border-white/10 text-white mt-1" /></div>
                            <div><Label className="text-[10px] font-bold uppercase text-white/50">Delay Max (sec)</Label><Input type="number" value={delayMax} onChange={e => setDelayMax(Number(e.target.value))} className="bg-white/5 border-white/10 text-white mt-1" /></div>
                        </div>

                        <div>
                            <Label className="text-[10px] font-bold uppercase text-white/50">Message Templates (Rotation)</Label>
                            <p className="text-[10px] text-white/30 mt-0.5 mb-2">{__('general.use_name_to_insert_the_recipient_apos_s_name')}</p>
                            {messages.map((msg, idx) => (
                                <div key={idx} className="flex gap-2 mb-2">
                                    <textarea value={msg} onChange={e => { const n = [...messages]; n[idx] = e.target.value; setMessages(n); }} rows={2}
                                        placeholder={`Message template ${idx + 1}...`}
                                        className="flex-1 p-2.5 bg-white/5 border border-white/10 rounded-lg text-xs text-white resize-none focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                    {messages.length > 1 && (
                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-red-400 hover:bg-red-500/10 shrink-0 self-start mt-1"
                                            onClick={() => setMessages(messages.filter((_, i) => i !== idx))}><X className="w-3.5 h-3.5" /></Button>
                                    )}
                                </div>
                            ))}
                            <Button variant="outline" className="text-xs border-white/10 text-white/50 gap-1.5 h-8" onClick={() => setMessages([...messages, ''])}>
                                <Plus className="w-3 h-3" />{__('general.add_template')}</Button>
                        </div>
                    </div>
                    <DialogFooter><Button onClick={handleCreate} className="bg-blue-600 hover:bg-blue-700 font-bold text-xs">{__('general.create_campaign')}</Button></DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

// ── Watch Tab ───────────────────────────────────────────────────────────────

function WatchTab({ call, onEvent, profileId }: any) {
    const [sessions, setSessions] = useState<WatchSession[]>([]);
    const [results, setResults] = useState<WatchResult[]>([]);
    const [activeSessionId, setActiveSessionId] = useState<number | null>(null);
    const [webhookUrl, setWebhookUrl] = useState('');
    const [intervalSec, setIntervalSec] = useState(300);
    const [maxCount, setMaxCount] = useState(100);

    useEffect(() => { if (profileId) loadSessions(); }, [profileId]);

    useEffect(() => {
        return onEvent((event: string, data: any) => {
            if (event === 'fb.watch.user_found') {
                setResults(prev => [{ id: Date.now(), session_id: data.sessionId, fb_user_id: data.user.user_id, name: data.user.name, posted_at: new Date().toISOString() }, ...prev]);
            }
        });
    }, [onEvent]);

    const loadSessions = async () => {
        try {
            const res = await call('getWatchSessions', { profileId });
            setSessions(res.sessions || []);
        } catch {}
    };

    const handleStartWatch = async () => {
        if (!profileId) return;
        try {
            const res = await call('startWatch', { profile_id: profileId, webhook_url: webhookUrl, interval_sec: intervalSec, max_count: maxCount });
            setActiveSessionId(res.sessionId);
            loadSessions();
        } catch {}
    };

    const handleStopWatch = async (sessionId: number) => {
        try { await call('stopWatch', { sessionId }); loadSessions(); } catch {}
    };

    const loadResults = async (sessionId: number) => {
        setActiveSessionId(sessionId);
        try { const res = await call('getWatchResults', { sessionId }); setResults(res.results || []); } catch {}
    };

    return (
        <div className="space-y-6">
            {/* Config */}
            <Card className="bg-white/[0.02] border-white/5">
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-bold text-white/90 flex items-center gap-2"><Radio className="w-4 h-4 text-blue-400" />{__('general.watch_configuration')}</CardTitle>
                    <CardDescription className="text-xs text-white/30">{__('general.monitor_your_inbox_for_new_messages_and_post_found_user_ids_to_a_webhook')}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div><Label className="text-[10px] font-bold uppercase text-white/50">{__('general.webhook_url')}</Label><Input value={webhookUrl} onChange={e => setWebhookUrl(e.target.value)} placeholder={__('general.https_your_api_com_webhook')} className="bg-white/5 border-white/10 text-white mt-1 font-mono" /></div>
                    <div className="grid grid-cols-2 gap-3">
                        <div><Label className="text-[10px] font-bold uppercase text-white/50">Interval (seconds)</Label><Input type="number" value={intervalSec} onChange={e => setIntervalSec(Number(e.target.value))} className="bg-white/5 border-white/10 text-white mt-1" /></div>
                        <div><Label className="text-[10px] font-bold uppercase text-white/50">{__('general.max_count')}</Label><Input type="number" value={maxCount} onChange={e => setMaxCount(Number(e.target.value))} className="bg-white/5 border-white/10 text-white mt-1" /></div>
                    </div>
                </CardContent>
                <CardFooter>
                    <Button onClick={handleStartWatch} className="bg-blue-600 hover:bg-blue-700 font-bold text-xs gap-2 rounded-xl" disabled={!profileId}>
                        <Eye className="w-3.5 h-3.5" />{__('general.start_watching')}</Button>
                </CardFooter>
            </Card>

            {/* Active Sessions */}
            {sessions.length > 0 && (
                <div className="space-y-3">
                    <h3 className="text-xs font-bold uppercase tracking-wider text-white/40">{__('general.watch_sessions')}</h3>
                    {sessions.map(s => (
                        <div key={s.id} className="bg-white/[0.02] border border-white/5 rounded-xl p-4 flex items-center justify-between">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <Badge variant="outline" className={`text-[9px] font-bold uppercase ${s.status === 'running' ? 'border-emerald-500/30 text-emerald-400' : 'border-white/10 text-white/40'}`}>{s.status}</Badge>
                                    <span className="text-xs text-white/50">Every {s.interval_sec}s • Max {s.max_count}</span>
                                </div>
                                {s.webhook_url && <p className="text-[10px] font-mono text-white/20 truncate max-w-xs">{s.webhook_url}</p>}
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" className="text-xs border-white/10 text-white/50 h-7" onClick={() => loadResults(s.id)}>{__('general.view_results')}</Button>
                                {s.status === 'running' && <Button variant="destructive" size="sm" className="text-xs h-7" onClick={() => handleStopWatch(s.id)}>Stop</Button>}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Results Table */}
            {results.length > 0 && (
                <div className="bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden">
                    <div className="px-4 py-3 border-b border-white/5">
                        <h3 className="text-xs font-bold text-white/60">Discovered Users ({results.length})</h3>
                    </div>
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-white/5">
                                <th className="text-left px-4 py-2 text-[10px] font-bold uppercase text-white/30">Name</th>
                                <th className="text-left px-4 py-2 text-[10px] font-bold uppercase text-white/30">{__('general.fb_user_id')}</th>
                                <th className="text-left px-4 py-2 text-[10px] font-bold uppercase text-white/30">{__('general.found_at')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {results.slice(0, 100).map(r => (
                                <tr key={r.id} className="border-b border-white/[0.03]">
                                    <td className="px-4 py-2 text-xs text-white/70">{r.name}</td>
                                    <td className="px-4 py-2 text-xs font-mono text-white/40">{r.fb_user_id}</td>
                                    <td className="px-4 py-2 text-xs text-white/30">{new Date(r.posted_at).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

// ── Logs Tab ────────────────────────────────────────────────────────────────

function LogsTab({ call, profileId }: any) {
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [selectedCampaign, setSelectedCampaign] = useState<number | null>(null);
    const [logs, setLogs] = useState<CampaignLog[]>([]);

    useEffect(() => { if (profileId) loadCampaigns(); }, [profileId]);

    const loadCampaigns = async () => {
        try {
            const res = await call('getCampaigns', { profileId });
            setCampaigns(res.campaigns || []);
        } catch {}
    };

    const loadLogs = async (campaignId: number) => {
        setSelectedCampaign(campaignId);
        try {
            const res = await call('getCampaignLogs', { campaignId });
            setLogs(res.logs || []);
        } catch {}
    };

    return (
        <div className="space-y-6">
            <h2 className="text-lg font-bold tracking-tight">{__('general.campaign_logs')}</h2>

            <div className="flex gap-2 flex-wrap">
                {campaigns.map(c => (
                    <Button key={c.id} variant={selectedCampaign === c.id ? 'default' : 'outline'}
                        className={`text-xs font-bold h-8 rounded-xl ${selectedCampaign === c.id ? 'bg-blue-600' : 'border-white/10 text-white/50'}`}
                        onClick={() => loadLogs(c.id)}>
                        {c.name}
                    </Button>
                ))}
            </div>

            {logs.length > 0 ? (
                <div className="bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-white/5">
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase text-white/30">User</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase text-white/30 hidden md:table-cell">{__('general.fb_id')}</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase text-white/30">Status</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase text-white/30 hidden md:table-cell">Error</th>
                                <th className="text-left px-4 py-3 text-[10px] font-bold uppercase text-white/30">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.map(l => (
                                <tr key={l.id} className="border-b border-white/[0.03]">
                                    <td className="px-4 py-2.5 text-xs text-white/70">{l.user_name}</td>
                                    <td className="px-4 py-2.5 text-xs font-mono text-white/30 hidden md:table-cell">{l.user_fb_id}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant="outline" className={`text-[9px] font-bold uppercase ${l.status === 'sent' ? 'border-emerald-500/30 text-emerald-400' : 'border-red-500/30 text-red-400'}`}>
                                            {l.status === 'sent' ? <CheckCircle2 className="w-2.5 h-2.5 mr-1" /> : <XCircle className="w-2.5 h-2.5 mr-1" />}
                                            {l.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5 text-xs text-red-400/60 max-w-xs truncate hidden md:table-cell">{l.error}</td>
                                    <td className="px-4 py-2.5 text-xs text-white/30">{new Date(l.sent_at).toLocaleTimeString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="bg-white/[0.02] border border-white/5 rounded-2xl p-12 text-center">
                    <BarChart3 className="w-8 h-8 text-white/10 mx-auto mb-3" />
                    <p className="text-xs text-white/20">{selectedCampaign ? 'No logs for this campaign.' : 'Select a campaign to view logs.'}</p>
                </div>
            )}
        </div>
    );
}

// ── Settings Tab ────────────────────────────────────────────────────────────

function SettingsTab({ call, profiles, onProfilesChange }: any) {
    const [stats, setStats] = useState<any>(null);

    useEffect(() => { loadStats(); }, []);

    const loadStats = async () => {
        try { const res = await call('getGlobalStats'); setStats(res.stats); } catch {}
    };

    const handleDeleteProfile = async (id: number) => {
        try { await call('deleteProfile', { id }); onProfilesChange(); } catch {}
    };

    return (
        <div className="space-y-6">
            <h2 className="text-lg font-bold tracking-tight">Settings</h2>

            {/* Stats */}
            {stats && (
                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                    {[
                        { label: 'Profiles', value: stats.profiles, icon: <Users className="w-4 h-4" /> },
                        { label: 'Inbox Users', value: stats.totalUsers, icon: <Inbox className="w-4 h-4" /> },
                        { label: 'Campaigns', value: stats.totalCampaigns, icon: <Send className="w-4 h-4" /> },
                        { label: 'Sent', value: stats.totalSent, icon: <CheckCircle2 className="w-4 h-4 text-emerald-400" /> },
                        { label: 'Failed', value: stats.totalFailed, icon: <XCircle className="w-4 h-4 text-red-400" /> },
                    ].map(s => (
                        <div key={s.label} className="bg-white/[0.02] border border-white/5 rounded-xl p-4 text-center space-y-1">
                            <div className="flex justify-center text-white/20">{s.icon}</div>
                            <p className="text-xl font-black text-white/80">{s.value}</p>
                            <p className="text-[10px] font-bold uppercase tracking-wider text-white/30">{s.label}</p>
                        </div>
                    ))}
                </div>
            )}

            {/* Profiles Management */}
            <Card className="bg-white/[0.02] border-white/5">
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm font-bold text-white/90">{__('general.manage_profiles')}</CardTitle>
                    <CardDescription className="text-xs text-white/30">{__('general.each_profile_represents_a_facebook_page_with_its_own_browser_session')}</CardDescription>
                </CardHeader>
                <CardContent>
                    {profiles.length === 0 ? (
                        <p className="text-xs text-white/20 text-center py-4">{__('general.no_profiles_created_yet')}</p>
                    ) : (
                        <div className="space-y-2">
                            {profiles.map((p: Profile) => (
                                <div key={p.id} className="flex items-center justify-between bg-white/[0.02] border border-white/5 rounded-xl px-4 py-3">
                                    <div>
                                        <p className="text-xs font-bold text-white/80">{p.name}</p>
                                        <p className="text-[10px] font-mono text-white/30">Page ID: {p.page_id}</p>
                                    </div>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button variant="ghost" size="icon" className="h-7 w-7 text-white/20 hover:text-white/50 hover:bg-white/5">
                                                <MoreHorizontal className="w-3.5 h-3.5" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="bg-[#1a1a2e] border-white/10 text-white sm:max-w-xs">
                                            <DialogHeader><DialogTitle className="text-sm">{__('general.profile_actions')}</DialogTitle></DialogHeader>
                                            <div className="flex flex-col gap-2 py-2">
                                                <Button variant="outline" className="justify-start border-white/10 text-white/70" onClick={async () => { try { await call('launchBrowser', { profileId: p.id }); } catch {} }}>
                                                    <Globe className="w-4 h-4 mr-2" />{__('general.launch_browser')}</Button>
                                                <Button variant="outline" className="justify-start border-white/10 text-white/70" onClick={async () => { try { await call('closeBrowser', { profileId: p.id }); } catch {} }}>
                                                    <X className="w-4 h-4 mr-2" />{__('general.close_browser')}</Button>
                                                <Button variant="destructive" className="justify-start" onClick={() => handleDeleteProfile(p.id)}>
                                                    <Trash2 className="w-4 h-4 mr-2" />{__('general.delete_profile')}</Button>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
