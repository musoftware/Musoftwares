import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Search, Users, Download, Play, Square,
    AlertCircle, CheckCircle2, RefreshCw,
    ChevronDown, Clipboard, History,
    Zap, ArrowLeft, Calendar, Hash, ExternalLink,
    UserPlus, UserMinus, Heart, MessageSquare,
    ImageIcon, Shield, BadgeCheck, Lock, Globe2,
    Loader2, Plus, Trash2, Check
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Extraction Types ─────────────────────────────────────────────────────────
const EXTRACTION_TYPES = [
    { id: 'followers',  label: 'Followers',   icon: UserPlus,      placeholder: 'Enter username (e.g. cristiano)', color: 'from-purple-500 to-pink-500' },
    { id: 'following',  label: 'Following',   icon: UserMinus,     placeholder: 'Enter username (e.g. leomessi)',  color: 'from-pink-500 to-rose-500' },
    { id: 'hashtag',    label: 'Hashtag',     icon: Hash,          placeholder: 'Enter hashtag (e.g. fitness)',     color: 'from-amber-500 to-orange-500' },
    { id: 'likers',     label: 'Post Likers', icon: Heart,         placeholder: 'Enter post URL or shortcode',     color: 'from-rose-500 to-red-500' },
    { id: 'commenters', label: 'Commenters',  icon: MessageSquare, placeholder: 'Enter post URL or shortcode',     color: 'from-blue-500 to-indigo-500' },
    { id: 'posts',      label: 'User Posts',  icon: ImageIcon,     placeholder: 'Enter username (e.g. nike)',      color: 'from-emerald-500 to-teal-500' },
];

// ── User Card ────────────────────────────────────────────────────────────────
function UserCard({ user, idx }: { user: any; idx: number }) {
    const [copied, setCopied] = useState(false);

    const copyRow = () => {
        const text = [user.username, user.full_name, user.profile_url].filter(Boolean).join('\t');
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    return (
        <div className="group flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors border-b border-slate-100/80 dark:border-slate-800/80 last:border-0">
            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center text-white text-[10px] font-black shrink-0 shadow-sm">
                {idx + 1}
            </div>
            <div className="flex-1 grid grid-cols-2 md:grid-cols-5 gap-3 min-w-0 items-center">
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Username</p>
                    <p className="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate flex items-center gap-1">
                        @{user.username || '—'}
                        {user.is_verified ? <BadgeCheck className="w-3 h-3 text-blue-500 shrink-0" /> : null}
                    </p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Full Name</p>
                    <p className="text-xs text-slate-600 dark:text-slate-400 truncate">{user.full_name || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Privacy</p>
                    <p className="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-1">
                        {user.is_private ? <><Lock className="w-3 h-3 text-amber-500" /> Private</> : <><Globe2 className="w-3 h-3 text-emerald-500" /> Public</>}
                    </p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Source</p>
                    <Badge variant="outline" className="text-[9px] font-bold uppercase px-1.5 py-0">{user.source_type || '—'}</Badge>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Profile</p>
                    {user.profile_url ? (
                        <a href={user.profile_url} target="_blank" rel="noreferrer" className="text-xs text-blue-500 hover:underline truncate block flex items-center gap-1">
                            <ExternalLink className="w-3 h-3 shrink-0" /> View
                        </a>
                    ) : (
                        <p className="text-xs text-slate-500">—</p>
                    )}
                </div>
            </div>
            <Button
                variant="ghost" size="icon"
                onClick={copyRow}
                className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 hover:bg-slate-100 dark:hover:bg-slate-800"
                title="Copy row"
            >
                {copied ? <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> : <Clipboard className="w-3.5 h-3.5 text-slate-400" />}
            </Button>
        </div>
    );
}

// ── Stat Card ─────────────────────────────────────────────────────────────────
function StatCard({ label, value, icon: Icon, color = 'bg-slate-50 border-slate-200 dark:bg-slate-900 dark:border-slate-800' }: any) {
    return (
        <div className={`border rounded-2xl p-4 flex flex-col gap-2 ${color}`}>
            <Icon className="w-4 h-4 text-slate-500" />
            <div>
                <p className="text-xl font-black text-slate-800 dark:text-slate-100">{value}</p>
                <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">{label}</p>
            </div>
        </div>
    );
}

// ── Users Table ──────────────────────────────────────────────────────────────
function UsersTable({ users, status, onExport }: { users: any[]; status?: string; onExport: () => void }) {
    if (users.length === 0) return null;
    return (
        <div className="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-3 duration-400">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h3 className="font-bold text-slate-800 dark:text-slate-200 text-sm">{users.length} Users</h3>
                <Button
                    onClick={onExport}
                    className="h-8 gap-1.5 px-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 text-xs font-bold"
                >
                    <Download className="w-3.5 h-3.5" /> Export CSV
                </Button>
            </div>

            {/* Table header */}
            <div className="hidden md:grid grid-cols-5 gap-3 px-5 py-2 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800" style={{ paddingLeft: 'calc(2rem + 1.25rem + 1rem)' }}>
                {['Username', 'Full Name', 'Privacy', 'Source', 'Profile'].map(h => (
                    <p key={h} className="text-[9px] font-black uppercase tracking-wider text-slate-400">{h}</p>
                ))}
            </div>

            {/* Rows */}
            <div className="divide-y divide-slate-100 dark:divide-slate-800 max-h-[55vh] overflow-y-auto">
                {users.map((user, i) => (
                    <UserCard key={user.id ?? user.username ?? i} user={user} idx={i} />
                ))}
            </div>

            {/* Footer */}
            <div className="px-5 py-3 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <p className="text-[10px] text-slate-400 font-medium">
                    {status === 'running' ? '● Live — more users incoming...' : `Extraction complete`}
                </p>
                <Button
                    variant="ghost"
                    onClick={onExport}
                    className="h-auto p-0 text-[10px] font-bold text-purple-600 hover:text-purple-700 hover:bg-transparent"
                >
                    Download all as CSV →
                </Button>
            </div>
        </div>
    );
}

// ── CSV export ────────────────────────────────────────────────────────────────
function exportCSV(users: any[], prefix = 'instagram-users') {
    const header = 'Username,Full Name,Is Private,Is Verified,Follower Count,Source Type,Profile URL';
    const rows = users.map(u => [
        u.username ?? '', u.full_name ?? '', u.is_private ? 'Yes' : 'No',
        u.is_verified ? 'Yes' : 'No', u.follower_count ?? '',
        u.source_type ?? '', u.profile_url ?? ''
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    const csv = [header, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${prefix}-${Date.now()}.csv`;
    a.click();
}

// ════════════════════════════════════════════════════════════════════════════════
// ── Main Component ──────────────────────────────────────────────────────────────
// ════════════════════════════════════════════════════════════════════════════════
export default function InstagramRunner({ tool }: any) {
    // Form
    const [extractionType, setExtractionType] = useState('followers');
    const [target, setTarget]   = useState('');
    const [limit, setLimit]     = useState(100);

    // Run state
    const [status, setStatus]       = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [users, setUsers]         = useState<any[]>([]);
    const [progress, setProgress]   = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    const [errorMsg, setError]      = useState('');
    const campaignIdRef = useRef<string>('');

    // Accounts
    const [accounts, setAccounts] = useState<any[]>([]);
    const [selectedAccountId, setSelectedAccountId] = useState('');
    const [loadingAccounts, setLoadingAccounts] = useState(false);
    const [addingAccount, setAddingAccount] = useState(false);

    // Campaigns tab
    const [campaigns, setCampaigns]         = useState<any[]>([]);
    const [selectedCampaign, setSelectedCampaign] = useState<any>(null);
    const [campaignUsers, setCampaignUsers] = useState<any[]>([]);
    const [loadingCampaigns, setLoadingCampaigns] = useState(false);
    const [loadingDetail, setLoadingDetail] = useState(false);

    // Active tab
    const [activeTab, setActiveTab] = useState('extract');

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

                    // Real-time user display
                    if (msg.event === 'instagram.user.extracted') {
                        const user = msg.data?.user;
                        const cid = msg.data?.campaignId || user?.campaign_id;
                        if (user && cid === campaignIdRef.current) {
                            setUsers(prev => [...prev, user]);
                        }
                    }

                    // Progress events
                    if (msg.event === 'instagram.extract.progress' && msg.data?.campaignId === campaignIdRef.current) {
                        const d = msg.data;
                        if (d.status === 'authenticated') {
                            setProgressMsg(d.message || 'Authenticated ✓');
                        }
                        if (d.status === 'no_auth' || d.status === 'no_cookies') {
                            setProgressMsg(d.message || 'No session — please login to Instagram');
                            setError(d.message || '');
                        }
                        if (d.status === 'resolving') {
                            setProgressMsg(d.message || 'Resolving...');
                        }
                        if (d.status === 'searching') {
                            setProgressMsg(d.message || 'Searching...');
                        }
                        if (d.status === 'extracting' && d.extracted != null) {
                            const pct = Math.min(5 + (d.extracted / limit) * 90, 95);
                            setProgress(pct);
                            setProgressMsg(d.message || `Extracting... (page ${d.page || '?'})`);
                        }
                        if (d.status === 'completed') {
                            setStatus('done');
                            setProgress(100);
                            setProgressMsg(d.message || `Done — ${d.extracted || 0} users extracted.`);
                        }
                        if (d.status === 'stopping') {
                            setProgressMsg('Stopping extraction...');
                        }
                        if (d.status === 'stopped') {
                            setStatus('done');
                            setProgress(100);
                            setProgressMsg(d.message || `Stopped — ${d.extracted || 0} users captured.`);
                        }
                        if (d.status === 'error') {
                            setStatus('error');
                            setError(d.message || 'Unknown error');
                            setProgressMsg('');
                        }
                    }

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

    // ── Polling fallback: fetch users from DB every 4s while running ──
    useEffect(() => {
        if (status !== 'running' || !connected) return;
        const cid = campaignIdRef.current;
        if (!cid) return;

        const poll = setInterval(async () => {
            try {
                const ws = wsRef.current;
                if (!ws || ws.readyState !== WebSocket.OPEN) return;

                const res = await new Promise<any>((resolve, reject) => {
                    if (!(ws as any)._pending) (ws as any)._pending = new Map();
                    const requestId = Math.random().toString(36).slice(2);
                    (ws as any)._pending.set(requestId, { resolve, reject });
                    ws.send(JSON.stringify({
                        type: 'plugin_rpc', requestId,
                        payload: { plugin: 'instagram', action: 'instagram.leads.list', data: { campaignId: cid, limit: 1000 } }
                    }));
                    setTimeout(() => {
                        if ((ws as any)._pending?.has(requestId)) {
                            (ws as any)._pending.delete(requestId);
                            reject(new Error('poll timeout'));
                        }
                    }, 5000);
                });

                if (res?.leads && res.leads.length > 0) {
                    setUsers(prev => {
                        if (res.leads.length > prev.length) return res.leads;
                        return prev;
                    });
                }
            } catch {}
        }, 4000);

        return () => clearInterval(poll);
    }, [status, connected]);

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
                    plugin: 'instagram',
                    action,
                    data
                }
            }));
        });
    }, []);

    const fetchAccounts = useCallback(async () => {
        setLoadingAccounts(true);
        try {
            const data = await callRPC('instagram.sessions.list');
            if (data?.sessions) {
                setAccounts(data.sessions);
                if (data.sessions.length > 0 && !selectedAccountId) {
                    setSelectedAccountId(data.sessions[0].id);
                }
            }
        } catch (e) {}
        setLoadingAccounts(false);
    }, [callRPC, selectedAccountId]);

    useEffect(() => {
        if (connected) fetchAccounts();
    }, [connected, fetchAccounts]);

    const handleAddAccount = async () => {
        setAddingAccount(true);
        try {
            const data = await callRPC('instagram.session.add');
            await fetchAccounts();
            if (data?.session?.id) {
                setSelectedAccountId(data.session.id);
            }
        } catch (e: any) {
            alert('Failed to add account: ' + e.message);
        }
        setAddingAccount(false);
    };

    const handleDeleteAccount = async (id: string, e: React.MouseEvent) => {
        e.stopPropagation();
        if (!confirm('Are you sure you want to remove this account?')) return;
        try {
            await callRPC('instagram.session.delete', { id });
            if (selectedAccountId === id) setSelectedAccountId('');
            await fetchAccounts();
        } catch (e: any) {
            alert('Error deleting account: ' + e.message);
        }
    };

    // ── Start extraction ──
    const handleStart = async () => {
        if (!selectedAccountId) {
            alert('Please select or add an Instagram account first.');
            return;
        }

        setStatus('running');
        setUsers([]);
        setProgress(0);
        setProgressMsg('Starting...');
        setError('');

        const cid = `ig_cmp_${Date.now()}`;
        campaignIdRef.current = cid;

        try {
            await callRPC('instagram.extract.start', {
                type: extractionType,
                target,
                limit,
                campaignId: cid,
                sessionId: selectedAccountId
            });
        } catch (e: any) {
            setStatus('error');
            setError(e.message);
        }
    };

    // ── Stop ──
    const handleStop = async () => {
        const cId = campaignIdRef.current;
        if (cId) {
            try {
                await callRPC('instagram.extract.stop', { campaignId: cId });
            } catch {
                try { await callRPC('instagram.extract.stop.all', {}); } catch {}
            }
        }
        setStatus('done');
        setProgressMsg(`Stopped — ${users.length} users captured.`);
        setProgress(100);
    };

    // ── Load campaigns ──
    const loadCampaigns = useCallback(async () => {
        if (!connected) return;
        setLoadingCampaigns(true);
        try {
            const res = await callRPC('instagram.campaigns.list');
            if (res?.campaigns) setCampaigns(res.campaigns);
        } catch (err) {
            console.error('Failed to load campaigns', err);
        }
        setLoadingCampaigns(false);
    }, [connected, callRPC]);

    // ── Load campaign detail ──
    const openCampaign = async (campaign: any) => {
        setSelectedCampaign(campaign);
        setLoadingDetail(true);
        setCampaignUsers([]);
        try {
            const res = await callRPC('instagram.campaign.detail', { campaignId: campaign.id });
            if (res?.leads) setCampaignUsers(res.leads);
        } catch (err) {
            console.error('Failed to load campaign detail', err);
        }
        setLoadingDetail(false);
    };

    useEffect(() => {
        if (activeTab === 'campaigns' && connected) {
            loadCampaigns();
        }
    }, [activeTab, connected, loadCampaigns]);

    // ── Status helpers ──
    const statusBadge = (s: string) => {
        const map: Record<string, { color: string; label: string }> = {
            running:   { color: 'bg-purple-50 border-purple-200 text-purple-700 dark:bg-purple-950 dark:border-purple-800 dark:text-purple-300', label: 'Running' },
            completed: { color: 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300', label: 'Completed' },
            stopped:   { color: 'bg-slate-100 border-slate-200 text-slate-600 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400', label: 'Stopped' },
            failed:    { color: 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-950 dark:border-rose-800 dark:text-rose-300', label: 'Failed' },
        };
        const cfg = map[s] || map.completed;
        return (
            <Badge variant="outline" className={`text-[10px] font-bold uppercase px-2 py-0.5 ${cfg.color}`}>
                {cfg.label}
            </Badge>
        );
    };

    const currentType = EXTRACTION_TYPES.find(t => t.id === extractionType) || EXTRACTION_TYPES[0];

    // ── Connecting state ──
    if (!connected) return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center font-sans">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">Connecting to Runtime...</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans">
            {/* ── Top bar ── */}
            <div className="h-14 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400 rounded-lg flex items-center justify-center shadow-sm">
                        <svg className="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                            <circle cx="12" cy="12" r="5" />
                            <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none" />
                        </svg>
                    </div>
                    <span className="font-bold text-sm text-slate-800 dark:text-slate-200 tracking-tight">Instagram Tool</span>
                </div>
                <div className="flex items-center gap-3">
                    {users.length > 0 && status !== 'idle' && (
                        <Button
                            variant="outline"
                            onClick={() => exportCSV(users)}
                            className="h-8 gap-1.5 px-3 bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300 text-xs font-bold"
                        >
                            <Download className="w-3.5 h-3.5" /> Export CSV
                        </Button>
                    )}
                    <Badge variant="outline" className={`gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${status === 'running' ? 'bg-purple-50 border-purple-200 text-purple-700 dark:bg-purple-950 dark:border-purple-800 dark:text-purple-300' : status === 'done' ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300' : 'bg-slate-100 border-slate-200 text-slate-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400'}`}>
                        <div className={`w-1.5 h-1.5 rounded-full ${status === 'running' ? 'bg-purple-500 animate-pulse' : status === 'done' ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                        {status === 'running' ? 'Extracting...' : status === 'done' ? `${users.length} users found` : 'Ready'}
                    </Badge>
                </div>
            </div>

            {/* ── Content ── */}
            <div className="max-w-5xl mx-auto px-4 py-6">
                <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full max-w-md grid-cols-2 mb-6 bg-slate-100 dark:bg-slate-900 p-1 rounded-xl">
                        <TabsTrigger
                            value="extract"
                            className="gap-2 text-sm font-bold data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg"
                        >
                            <Zap className="w-4 h-4" /> Extract
                            {status === 'running' && (
                                <span className="ml-1 w-2 h-2 rounded-full bg-purple-500 animate-pulse" />
                            )}
                        </TabsTrigger>
                        <TabsTrigger
                            value="campaigns"
                            className="gap-2 text-sm font-bold data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg"
                        >
                            <History className="w-4 h-4" /> Campaigns
                            {campaigns.length > 0 && (
                                <span className="ml-1 text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-1.5 py-0 rounded-full font-bold">
                                    {campaigns.length}
                                </span>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    {/* ════════════════════════════════════════════════════ */}
                    {/* ── TAB 1: Extract ─────────────────────────────── */}
                    {/* ════════════════════════════════════════════════════ */}
                    <TabsContent value="extract" className="space-y-6">
                        {/* Config card */}
                        <div className="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                            <div className="mb-5">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Instagram Extraction</h1>
                                <p className="text-sm text-slate-400 mt-1">Extract followers, likers, commenters, hashtag users and more.</p>
                            </div>

                            {/* Extraction type selector */}
                            <div className="mb-4">
                                <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-2">Extraction Type</label>
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                    {EXTRACTION_TYPES.map(t => {
                                        const Icon = t.icon;
                                        const isActive = extractionType === t.id;
                                        return (
                                            <button
                                                key={t.id}
                                                onClick={() => { setExtractionType(t.id); setTarget(''); }}
                                                className={`flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all duration-200 ${
                                                    isActive
                                                        ? 'border-purple-400 bg-purple-50 dark:bg-purple-950/50 shadow-sm'
                                                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50'
                                                }`}
                                            >
                                                <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${t.color} flex items-center justify-center shadow-sm`}>
                                                    <Icon className="w-4 h-4 text-white" />
                                                </div>
                                                <span className={`text-[10px] font-bold ${isActive ? 'text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400'}`}>{t.label}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Form Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-[1fr_200px] gap-6 mb-8">
                                {/* Account Selection */}
                                <div className="md:col-span-2">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Selected Account</label>
                                    <div className="flex gap-2">
                                        <Select value={selectedAccountId} onValueChange={setSelectedAccountId}>
                                            <SelectTrigger className="flex-1 h-11 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                                                <SelectValue placeholder={loadingAccounts ? "Loading accounts..." : "Select an Instagram account"} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {accounts.map(acc => (
                                                    <SelectItem key={acc.id} value={acc.id}>
                                                        <div className="flex items-center justify-between w-full pr-4">
                                                            <div className="flex items-center gap-2">
                                                                {acc.profile_pic ? (
                                                                    <img src={acc.profile_pic} alt="" className="w-5 h-5 rounded-full" />
                                                                ) : (
                                                                    <div className="w-5 h-5 rounded-full bg-indigo-100 text-indigo-500 flex items-center justify-center text-[10px] font-bold">
                                                                        {acc.username?.[0]?.toUpperCase()}
                                                                    </div>
                                                                )}
                                                                <span className="font-medium text-slate-800 dark:text-slate-200">@{acc.username}</span>
                                                                {acc.full_name && <span className="text-xs text-slate-400 hidden sm:inline">- {acc.full_name}</span>}
                                                            </div>
                                                            <div
                                                                role="button"
                                                                onClick={(e) => handleDeleteAccount(acc.id, e)}
                                                                className="opacity-50 hover:opacity-100 hover:text-red-500 transition-opacity ml-4"
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </div>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                                {accounts.length === 0 && (
                                                    <div className="p-3 text-sm text-center text-slate-500">No accounts added yet.</div>
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            variant="outline"
                                            onClick={handleAddAccount}
                                            disabled={addingAccount}
                                            className="h-11 px-4 border-slate-200 dark:border-slate-800"
                                        >
                                            {addingAccount ? <Loader2 className="w-4 h-4 animate-spin" /> : <Plus className="w-4 h-4 mr-2" />}
                                            {addingAccount ? '' : 'Add Account'}
                                        </Button>
                                    </div>
                                    <p className="text-[10px] text-slate-400 mt-2">
                                        Note: If you have multiple Chrome profiles open, the system will automatically pull the logged-in Instagram account from all of them at once.
                                    </p>
                                </div>

                                {/* Target */}
                                <div className="md:col-span-2">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Target</label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="text"
                                            value={target}
                                            onChange={e => setTarget(e.target.value)}
                                            onKeyDown={e => e.key === 'Enter' && handleStart()}
                                            placeholder={currentType.placeholder}
                                            className="pl-9 h-11 text-sm bg-slate-50 dark:bg-slate-900"
                                        />
                                    </div>
                                </div>

                                {/* Limit */}
                                <div>
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1.5">Max Users</label>
                                    <div className="relative">
                                        <Users className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input
                                            type="number"
                                            min={10} max={5000} step={10}
                                            value={limit}
                                            onChange={e => setLimit(parseInt(e.target.value, 10))}
                                            className="pl-9 h-11 text-sm bg-slate-50 dark:bg-slate-900"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Start / Stop */}
                            <div className="flex gap-3">
                                {status === 'running' ? (
                                    <Button
                                        variant="outline"
                                        onClick={handleStop}
                                        className="h-11 gap-2 px-6 bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100 hover:text-rose-800 dark:bg-rose-950 dark:border-rose-800 dark:text-rose-300 text-sm font-bold"
                                    >
                                        <Square className="w-4 h-4" /> Stop
                                    </Button>
                                ) : (
                                    <Button
                                        onClick={handleStart}
                                        disabled={!target.trim()}
                                        className="h-11 gap-2 px-6 bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 text-white shadow-md text-sm font-bold hover:opacity-90"
                                    >
                                        <Play className="w-4 h-4" /> Start Extraction
                                    </Button>
                                )}
                                {users.length > 0 && status !== 'running' && (
                                    <Button
                                        variant="outline"
                                        onClick={async () => {
                                            await callRPC('instagram.leads.clear');
                                            setUsers([]);
                                            setStatus('idle');
                                            setProgress(0);
                                        }}
                                        className="h-11 px-4 border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-900 hover:text-slate-600 text-sm font-medium"
                                    >
                                        Clear
                                    </Button>
                                )}
                            </div>

                            {/* Progress */}
                            {status === 'running' && (
                                <div className="mt-4 space-y-1.5 animate-in fade-in duration-300">
                                    <div className="flex justify-between text-[11px] font-semibold text-slate-500">
                                        <span className="flex items-center gap-1.5"><RefreshCw className="w-3 h-3 animate-spin" />{progressMsg}</span>
                                        <span className="font-bold text-purple-600 dark:text-purple-400">{users.length} users so far</span>
                                    </div>
                                    <div className="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-orange-400 transition-all duration-500 rounded-full"
                                            style={{ width: `${progress}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Error */}
                            {status === 'error' && (
                                <div className="mt-4 flex items-start gap-2.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl p-4 animate-in fade-in duration-300">
                                    <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    <p className="text-sm text-rose-700 dark:text-rose-300 font-medium">{errorMsg}</p>
                                </div>
                            )}
                        </div>

                        {/* Stats */}
                        {users.length > 0 && (
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 animate-in fade-in duration-300">
                                <StatCard label="Total" value={users.length} icon={Users} color="bg-purple-50 border-purple-200 dark:bg-purple-950 dark:border-purple-800" />
                                <StatCard label="Private" value={users.filter((u: any) => u.is_private).length} icon={Lock} />
                                <StatCard label="Verified" value={users.filter((u: any) => u.is_verified).length} icon={BadgeCheck} color="bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800" />
                                <StatCard label="Type" value={currentType.label} icon={currentType.icon} />
                            </div>
                        )}

                        {/* Users table */}
                        <UsersTable users={users} status={status} onExport={() => exportCSV(users)} />

                        {/* Empty state */}
                        {status === 'idle' && users.length === 0 && (
                            <div className="py-20 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-white dark:bg-slate-950">
                                <div className="w-14 h-14 mx-auto mb-4 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg className="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                                        <circle cx="12" cy="12" r="5" />
                                        <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none" />
                                    </svg>
                                </div>
                                <h3 className="text-sm font-bold text-slate-700 dark:text-slate-300">Instagram Extraction Tool</h3>
                                <p className="text-xs text-slate-400 mt-2 max-w-xs mx-auto">
                                    Extract followers, likers, commenters, and hashtag users. Select a type, enter a target, and start extracting.
                                </p>
                            </div>
                        )}
                    </TabsContent>

                    {/* ════════════════════════════════════════════════════ */}
                    {/* ── TAB 2: Campaigns ───────────────────────────── */}
                    {/* ════════════════════════════════════════════════════ */}
                    <TabsContent value="campaigns" className="space-y-4">
                        {selectedCampaign ? (
                            <div className="space-y-4 animate-in fade-in slide-in-from-right-3 duration-300">
                                <div className="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                                    <button
                                        onClick={() => { setSelectedCampaign(null); setCampaignUsers([]); }}
                                        className="flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors mb-4"
                                    >
                                        <ArrowLeft className="w-3.5 h-3.5" /> Back to campaigns
                                    </button>

                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h2 className="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                                <Search className="w-4 h-4 text-purple-500" />
                                                {selectedCampaign.type}: "{selectedCampaign.target}"
                                            </h2>
                                            <div className="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="w-3 h-3" />
                                                    {new Date(selectedCampaign.created_at).toLocaleDateString()}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Users className="w-3 h-3" />
                                                    {selectedCampaign.total} users
                                                </span>
                                            </div>
                                        </div>
                                        {statusBadge(selectedCampaign.status)}
                                    </div>

                                    {campaignUsers.length > 0 && (
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                                            <StatCard label="Total" value={campaignUsers.length} icon={Users} color="bg-purple-50 border-purple-200 dark:bg-purple-950 dark:border-purple-800" />
                                            <StatCard label="Private" value={campaignUsers.filter(u => u.is_private).length} icon={Lock} />
                                            <StatCard label="Verified" value={campaignUsers.filter(u => u.is_verified).length} icon={BadgeCheck} color="bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800" />
                                            <StatCard label="Type" value={selectedCampaign.type} icon={Hash} />
                                        </div>
                                    )}
                                </div>

                                {loadingDetail ? (
                                    <div className="py-16 text-center">
                                        <div className="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">Loading users...</p>
                                    </div>
                                ) : (
                                    <UsersTable
                                        users={campaignUsers}
                                        onExport={() => exportCSV(campaignUsers, `ig-${selectedCampaign.type}-${selectedCampaign.target}`)}
                                    />
                                )}

                                {!loadingDetail && campaignUsers.length === 0 && (
                                    <div className="py-16 text-center bg-white dark:bg-slate-950 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                                        <Users className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                        <p className="text-sm text-slate-500 font-medium">No users in this campaign</p>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-4 animate-in fade-in duration-300">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-bold text-slate-800 dark:text-slate-200">Past Campaigns</h2>
                                    <Button
                                        variant="outline"
                                        onClick={loadCampaigns}
                                        disabled={loadingCampaigns}
                                        className="h-8 gap-1.5 text-xs font-bold"
                                    >
                                        <RefreshCw className={`w-3.5 h-3.5 ${loadingCampaigns ? 'animate-spin' : ''}`} /> Refresh
                                    </Button>
                                </div>

                                {loadingCampaigns && campaigns.length === 0 ? (
                                    <div className="py-16 text-center">
                                        <div className="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
                                        <p className="text-xs text-slate-400 font-medium">Loading campaigns...</p>
                                    </div>
                                ) : campaigns.length === 0 ? (
                                    <div className="py-20 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-white dark:bg-slate-950">
                                        <History className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                        <h3 className="text-sm font-bold text-slate-700 dark:text-slate-300">No campaigns yet</h3>
                                        <p className="text-xs text-slate-400 mt-2">Start an extraction from the Extract tab to see campaigns here.</p>
                                    </div>
                                ) : (
                                    <div className="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                                        {campaigns.map((c, i) => {
                                            const typeConfig = EXTRACTION_TYPES.find(t => t.id === c.type) || EXTRACTION_TYPES[0];
                                            const TypeIcon = typeConfig.icon;
                                            return (
                                                <button
                                                    key={c.id}
                                                    onClick={() => openCampaign(c)}
                                                    className={`w-full flex items-center gap-4 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors text-left ${i < campaigns.length - 1 ? 'border-b border-slate-100 dark:border-slate-800' : ''}`}
                                                >
                                                    <div className={`w-10 h-10 rounded-xl bg-gradient-to-br ${typeConfig.color} flex items-center justify-center shrink-0 shadow-sm`}>
                                                        <TypeIcon className="w-4.5 h-4.5 text-white" />
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <p className="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                                                                {c.type}: "{c.target}"
                                                            </p>
                                                            {statusBadge(c.status)}
                                                        </div>
                                                        <div className="flex items-center gap-4 mt-1 text-[11px] text-slate-400">
                                                            <span className="flex items-center gap-1">
                                                                <Users className="w-3 h-3" />
                                                                {c.total} users
                                                            </span>
                                                            <span className="flex items-center gap-1">
                                                                <Calendar className="w-3 h-3" />
                                                                {new Date(c.created_at).toLocaleDateString()}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <ChevronDown className="w-4 h-4 text-slate-300 -rotate-90 shrink-0" />
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    );
}
