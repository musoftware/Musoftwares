import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Users, Download, Play, Square, AlertCircle, CheckCircle2, RefreshCw,
    Phone, ChevronDown, Clipboard, History, Zap, ArrowLeft, Calendar,
    Hash, ExternalLink, Send, Plus, Trash2, Shield, Search, Loader2,
    UserPlus, MessageCircle, Settings2, Radio, Eye, Copy, Check,
    AtSign, Globe2,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl       = () => `ws://${getRuntimeHost()}:18401/ws`;

/* ── RPC helper ─────────────────────────────────────────────────── */
let rpcCounter = 0;
const pendingRPC = new Map<number, { resolve: Function; reject: Function }>();

function sendRPC(ws: WebSocket | null, action: string, params: any = {}): Promise<any> {
    return new Promise((resolve, reject) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            reject(new Error('Not connected'));
            return;
        }
        const id = ++rpcCounter;
        pendingRPC.set(id, { resolve, reject });
        ws.send(JSON.stringify({ type: 'plugin_rpc', id, plugin: 'telegram-tool', action, params }));
        setTimeout(() => {
            if (pendingRPC.has(id)) {
                pendingRPC.delete(id);
                reject(new Error('RPC timeout'));
            }
        }, 30000);
    });
}

/* ── CSV Export ──────────────────────────────────────────────────── */
function exportMembersCSV(members: any[], prefix = 'telegram-members') {
    const header = 'User ID,First Name,Last Name,Phone,Username,Group,Status';
    const rows = members.map(m => [
        m.user_id ?? '', m.first_name ?? '', m.last_name ?? '',
        m.phone ?? '', m.username ?? '', m.group_name ?? '', m.status ?? ''
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    const csv = [header, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${prefix}-${Date.now()}.csv`;
    a.click();
}

/* ── Stat Card ──────────────────────────────────────────────────── */
function StatCard({ label, value, icon: Icon, color = 'bg-slate-50 border-slate-200' }: any) {
    return (
        <div className={`border rounded-2xl p-4 flex flex-col gap-2 ${color}`}>
            <Icon className="w-4 h-4 text-slate-500" />
            <div>
                <p className="text-xl font-black text-slate-800">{value}</p>
                <p className="text-[9px] font-black uppercase tracking-wider text-slate-400">{label}</p>
            </div>
        </div>
    );
}

/* ── Member Row ──────────────────────────────────────────────────── */
function MemberCard({ member, idx }: { member: any; idx: number }) {
    const [copied, setCopied] = useState(false);
    const copyRow = () => {
        const text = [member.user_id, member.first_name, member.last_name, member.phone, member.username, member.group_name].filter(Boolean).join('\t');
        navigator.clipboard.writeText(text).then(() => { setCopied(true); setTimeout(() => setCopied(false), 1500); });
    };

    return (
        <div className="group flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/80 transition-colors border-b border-slate-100/80 last:border-0">
            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center text-white text-[10px] font-black shrink-0 shadow-sm">
                {idx + 1}
            </div>
            <div className="flex-1 grid grid-cols-2 md:grid-cols-6 gap-3 min-w-0 items-center">
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Name</p>
                    <p className="text-xs font-semibold text-slate-800 truncate">{`${member.first_name || ''} ${member.last_name || ''}`.trim() || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">{__('general.user_id')}</p>
                    <p className="text-xs font-mono text-slate-600 truncate">{member.user_id || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Phone</p>
                    <p className="text-xs font-mono text-emerald-600 truncate">{member.phone || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Username</p>
                    {member.username ? (
                        <a href={`https://t.me/${member.username}`} target="_blank" rel="noreferrer" className="text-xs font-mono text-blue-500 hover:underline truncate block">
                            @{member.username}
                        </a>
                    ) : (
                        <p className="text-xs text-slate-500">—</p>
                    )}
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Group</p>
                    <p className="text-xs text-slate-600 truncate">{member.group_name || '—'}</p>
                </div>
                <div className="min-w-0">
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Profile</p>
                    {member.username ? (
                        <a href={`https://t.me/${member.username}`} target="_blank" rel="noreferrer" className="text-xs text-blue-500 hover:underline flex items-center gap-1">
                            <ExternalLink className="w-3 h-3 shrink-0" /> View
                        </a>
                    ) : (
                        <p className="text-xs text-slate-500">—</p>
                    )}
                </div>
            </div>
            <Button variant="ghost" size="icon" onClick={copyRow}
                className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 hover:bg-slate-100" title={__('general.copy_row')}>
                {copied ? <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> : <Clipboard className="w-3.5 h-3.5 text-slate-400" />}
            </Button>
        </div>
    );
}

/* ── Members Table ──────────────────────────────────────────────── */
function MembersTable({ members, status, onExport }: { members: any[]; status?: string; onExport: () => void }) {
    if (members.length === 0) return null;
    return (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-3 duration-400">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 className="font-bold text-slate-800 text-sm">{members.length} Members</h3>
                <Button onClick={onExport} className="h-8 gap-1.5 px-3 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold">
                    <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
            </div>
            <div className="hidden md:grid grid-cols-6 gap-3 px-5 py-2 bg-slate-50 border-b border-slate-100">
                {['Name', 'User ID', 'Phone', 'Username', 'Group', 'Profile'].map(h => (
                    <p key={h} className="text-[9px] font-black uppercase tracking-wider text-slate-400">{h}</p>
                ))}
            </div>
            <div className="divide-y divide-slate-100 max-h-[55vh] overflow-y-auto">
                {members.map((m, i) => <MemberCard key={m.id ?? i} member={m} idx={i} />)}
            </div>
            <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <p className="text-[10px] text-slate-400 font-medium">
                    {status === 'running' ? '● Live — more members incoming...' : `Extraction complete`}
                </p>
                <Button variant="ghost" onClick={onExport}
                    className="h-auto p-0 text-[10px] font-bold text-blue-600 hover:text-blue-700 hover:bg-transparent">
                    Download all as CSV →
                </Button>
            </div>
        </div>
    );
}

/* ══════════════════════════════════════════════════════════════════
   ── Main Component ─────────────────────────────────────────────
   ══════════════════════════════════════════════════════════════════ */
export default function TelegramToolRunner({ tool }: any) {
    // Connection
    const wsRef = useRef<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);

    // Active tab
    const [activeTab, setActiveTab] = useState('extract');

    // ── Sessions state ──
    const [sessions, setSessions] = useState<any[]>([]);
    const [newPhone, setNewPhone] = useState('');
    const [otpPhone, setOtpPhone] = useState('');
    const [otpCode, setOtpCode] = useState('');
    const [addingSession, setAddingSession] = useState(false);
    const [verifying, setVerifying] = useState(false);

    // ── Extract state ──
    const [selectedSession, setSelectedSession] = useState('');
    const [groups, setGroups] = useState<any[]>([]);
    const [selectedGroups, setSelectedGroups] = useState<Set<string>>(new Set());
    const [loadingGroups, setLoadingGroups] = useState(false);
    const [extractStatus, setExtractStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [members, setMembers] = useState<any[]>([]);
    const [extractProgress, setExtractProgress] = useState('');
    const extractCampaignRef = useRef('');

    // ── Send state ──
    const [sendSessions, setSendSessions] = useState<string[]>([]);
    const [recipientsText, setRecipientsText] = useState('');
    const [messageText, setMessageText] = useState('');
    const [sendMinDelay, setSendMinDelay] = useState(3);
    const [sendMaxDelay, setSendMaxDelay] = useState(8);
    const [sendStatus, setSendStatus] = useState<'idle' | 'running' | 'done'>('idle');
    const [sendLogs, setSendLogs] = useState<any[]>([]);
    const [sendStats, setSendStats] = useState({ sent: 0, failed: 0, total: 0 });
    const sendCampaignRef = useRef('');

    // ── Campaigns state ──
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [selectedCampaign, setSelectedCampaign] = useState<any>(null);
    const [campaignMembers, setCampaignMembers] = useState<any[]>([]);
    const [loadingCampaigns, setLoadingCampaigns] = useState(false);

    /* ── WebSocket ── */
    useEffect(() => {
        let ws: WebSocket;
        let retryTimer: ReturnType<typeof setTimeout>;

        const connect = () => {
            ws = new WebSocket(getWsUrl());
            wsRef.current = ws;
            ws.onopen  = () => setConnected(true);
            ws.onclose = () => { setConnected(false); retryTimer = setTimeout(connect, 3000); };
            ws.onerror = () => ws.close();

            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);

                    // RPC responses
                    if (msg.type === 'plugin_rpc_response' && msg.id && pendingRPC.has(msg.id)) {
                        const { resolve } = pendingRPC.get(msg.id)!;
                        pendingRPC.delete(msg.id);
                        resolve(msg.result);
                        return;
                    }

                    // Real-time extraction events
                    if (msg.event === 'prospecting.lead.extracted') {
                        const lead = msg.data?.lead;
                        const cid = msg.data?.campaignId;
                        if (lead && cid === extractCampaignRef.current) {
                            setMembers(prev => [...prev, lead]);
                        }
                    }

                    if (msg.event === 'telegram-tool.extract.progress') {
                        const d = msg.data;
                        if (d?.campaignId === extractCampaignRef.current) {
                            setExtractProgress(`Extracting from "${d.currentGroup}"... ${d.total} members found`);
                        }
                    }

                    if (msg.event === 'telegram-tool.extract.complete') {
                        const d = msg.data;
                        if (d?.campaignId === extractCampaignRef.current) {
                            setExtractStatus('done');
                            setExtractProgress(`Done — ${d.total} members extracted (${d.withPhone} with phone, ${d.withUsername} with username)`);
                        }
                    }

                    if (msg.event === 'telegram-tool.extract.status') {
                        const d = msg.data;
                        if (d?.campaignId === extractCampaignRef.current) {
                            setExtractProgress(d.message || '');
                        }
                    }

                    // Real-time send events
                    if (msg.event === 'telegram-tool.send.message') {
                        const d = msg.data;
                        if (d?.campaignId === sendCampaignRef.current) {
                            setSendStats({ sent: d.sent, failed: d.failed, total: d.total });
                            setSendLogs(prev => [{
                                id: d.logId,
                                name: d.recipientName,
                                status: d.status,
                                error: d.error,
                                session: d.session,
                            }, ...prev]);
                        }
                    }

                    if (msg.event === 'telegram-tool.send.complete') {
                        const d = msg.data;
                        if (d?.campaignId === sendCampaignRef.current) {
                            setSendStatus('done');
                        }
                    }
                } catch {}
            };
        };

        connect();
        return () => { clearTimeout(retryTimer); ws?.close(); };
    }, []);

    /* ── RPC shortcut ── */
    const rpc = useCallback((action: string, params: any = {}) => sendRPC(wsRef.current, action, params), []);

    /* ── Load sessions ── */
    const loadSessions = useCallback(async () => {
        try {
            const res = await rpc('telegram-tool.sessions.list');
            if (res?.ok) setSessions(res.sessions || []);
        } catch {}
    }, [rpc]);

    useEffect(() => {
        if (connected) loadSessions();
    }, [connected, loadSessions]);

    /* ── Session actions ── */
    const handleAddSession = async () => {
        if (!newPhone.trim()) return;
        setAddingSession(true);
        try {
            const res = await rpc('telegram-tool.sessions.add', { phone: newPhone });
            if (res?.ok) {
                setOtpPhone(newPhone);
                setNewPhone('');
                await loadSessions();
            }
        } finally { setAddingSession(false); }
    };

    const handleVerifyOTP = async () => {
        if (!otpCode.trim()) return;
        setVerifying(true);
        try {
            const res = await rpc('telegram-tool.sessions.verify', { phone: otpPhone, code: otpCode });
            if (res?.ok) {
                setOtpPhone('');
                setOtpCode('');
                await loadSessions();
            }
        } finally { setVerifying(false); }
    };

    const handleRemoveSession = async (phone: string) => {
        await rpc('telegram-tool.sessions.remove', { phone });
        await loadSessions();
    };

    /* ── Load groups ── */
    const handleLoadGroups = async () => {
        if (!selectedSession) return;
        setLoadingGroups(true);
        try {
            const res = await rpc('telegram-tool.groups.list', { sessionPhone: selectedSession });
            if (res?.ok) setGroups(res.groups || []);
        } finally { setLoadingGroups(false); }
    };

    /* ── Start extraction ── */
    const handleStartExtraction = async () => {
        if (!selectedSession || selectedGroups.size === 0) return;
        const targetGroups = groups.filter(g => selectedGroups.has(g.id));
        setExtractStatus('running');
        setMembers([]);
        setExtractProgress('Starting extraction...');
        try {
            const res = await rpc('telegram-tool.extract.start', {
                sessionPhone: selectedSession,
                groups: targetGroups,
            });
            if (res?.ok) {
                extractCampaignRef.current = res.campaignId;
            }
        } catch {
            setExtractStatus('error');
        }
    };

    const handleStopExtraction = async () => {
        if (extractCampaignRef.current) {
            await rpc('telegram-tool.extract.stop', { campaignId: extractCampaignRef.current });
            setExtractStatus('done');
        }
    };

    /* ── Start sending ── */
    const handleStartSending = async () => {
        if (!sendSessions.length || !recipientsText.trim() || !messageText.trim()) return;
        const recipients = recipientsText.split('\n').filter(l => l.trim()).map(line => {
            const parts = line.split(',').map(s => s.trim());
            return {
                id: parts[0] || '',
                name: parts[1] || parts[0] || '',
                first_name: parts[1] || '',
                phone: parts[0] || '',
            };
        });

        setSendStatus('running');
        setSendLogs([]);
        setSendStats({ sent: 0, failed: 0, total: recipients.length });

        try {
            const res = await rpc('telegram-tool.send.start', {
                sessionPhones: sendSessions,
                recipients,
                message: messageText,
                minDelay: sendMinDelay,
                maxDelay: sendMaxDelay,
            });
            if (res?.ok) sendCampaignRef.current = res.campaignId;
        } catch {
            setSendStatus('idle');
        }
    };

    const handleStopSending = async () => {
        if (sendCampaignRef.current) {
            await rpc('telegram-tool.send.stop', { campaignId: sendCampaignRef.current });
            setSendStatus('done');
        }
    };

    /* ── Load campaigns ── */
    const handleLoadCampaigns = async () => {
        setLoadingCampaigns(true);
        try {
            const res = await rpc('telegram-tool.campaigns.list');
            if (res?.ok) setCampaigns(res.campaigns || []);
        } finally { setLoadingCampaigns(false); }
    };

    const handleViewCampaign = async (campaign: any) => {
        setSelectedCampaign(campaign);
        try {
            const res = await rpc('telegram-tool.members.list', { campaignId: campaign.id, limit: 500 });
            if (res?.ok) setCampaignMembers(res.members || []);
        } catch {}
    };

    /* ── Toggle group selection ── */
    const toggleGroup = (id: string) => {
        setSelectedGroups(prev => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    };

    const toggleAllGroups = () => {
        if (selectedGroups.size === groups.length) {
            setSelectedGroups(new Set());
        } else {
            setSelectedGroups(new Set(groups.map(g => g.id)));
        }
    };

    const connectedSessions = sessions.filter(s => s.status === 'connected');

    // ══════════════════════════════════════════════════════════════
    // ── RENDER ──────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════
    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 font-sans">
            {/* ── Header ── */}
            <div className="relative overflow-hidden border-b border-slate-200/60">
                <div className="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-cyan-500/5 to-indigo-500/5" />
                <div className="absolute top-[-50%] right-[-10%] w-[40%] h-[200%] rounded-full bg-blue-400/5 blur-3xl" />
                <div className="relative max-w-7xl mx-auto px-5 py-6 md:py-8">
                    <div className="flex items-center gap-4 mb-4">
                        <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                            <Send className="w-6 h-6" />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-black text-slate-900 tracking-tight">{__('general.telegram_tool')}</h1>
                            <p className="text-xs text-slate-500 font-medium">{__('general.extract_members_send_messages_manage_sessions')}</p>
                        </div>
                        <div className="ml-auto flex items-center gap-2">
                            <Badge variant={connected ? 'default' : 'destructive'} className="gap-1.5 font-bold text-[10px]">
                                <span className={`w-1.5 h-1.5 rounded-full ${connected ? 'bg-emerald-400 animate-pulse' : 'bg-red-400'}`} />
                                {connected ? 'Connected' : 'Offline'}
                            </Badge>
                        </div>
                    </div>

                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList className="bg-slate-100/70 border border-slate-200/60 p-1 rounded-xl">
                            <TabsTrigger value="extract" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg text-xs font-bold gap-1.5">
                                <Users className="w-3.5 h-3.5" /> Extract
                            </TabsTrigger>
                            <TabsTrigger value="send" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg text-xs font-bold gap-1.5">
                                <MessageCircle className="w-3.5 h-3.5" /> Send
                            </TabsTrigger>
                            <TabsTrigger value="sessions" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg text-xs font-bold gap-1.5">
                                <Shield className="w-3.5 h-3.5" /> Sessions
                            </TabsTrigger>
                            <TabsTrigger value="history" className="data-[state=active]:bg-white data-[state=active]:shadow-sm rounded-lg text-xs font-bold gap-1.5"
                                onClick={() => { if (activeTab !== 'history') handleLoadCampaigns(); }}>
                                <History className="w-3.5 h-3.5" /> History
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-5 py-6 space-y-6">

                {/* ════════════════ EXTRACT TAB ════════════════ */}
                {activeTab === 'extract' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        {/* Session + Group Selector */}
                        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 md:p-6 space-y-5">
                            <div className="flex items-center gap-3 mb-1">
                                <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center">
                                    <Users className="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <h2 className="text-sm font-bold text-slate-800">{__('general.group_member_extraction')}</h2>
                                    <p className="text-[10px] text-slate-400 font-medium">{__('general.select_account_groups_then_extract_all_members')}</p>
                                </div>
                            </div>

                            {/* Session Picker */}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">{__('general.active_session')}</label>
                                {connectedSessions.length === 0 ? (
                                    <div className="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center gap-2">
                                        <AlertCircle className="w-4 h-4 shrink-0" />
                                        <span>{__('general.no_connected_sessions_go_to')}<button onClick={() => setActiveTab('sessions')} className="font-bold underline">Sessions</button>{__('general.tab_to_add_one')}</span>
                                    </div>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {connectedSessions.map(s => (
                                            <button key={s.phone} onClick={() => { setSelectedSession(s.phone); setGroups([]); setSelectedGroups(new Set()); }}
                                                className={`px-3 py-2 rounded-xl border text-xs font-bold transition-all ${selectedSession === s.phone
                                                    ? 'bg-blue-50 border-blue-300 text-blue-700 ring-2 ring-blue-200'
                                                    : 'bg-white border-slate-200 text-slate-600 hover:border-blue-200 hover:bg-blue-50/50'}`}>
                                                <span className="flex items-center gap-1.5">
                                                    <span className="w-2 h-2 rounded-full bg-emerald-400" />
                                                    {s.phone}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Load Groups Button */}
                            {selectedSession && (
                                <Button onClick={handleLoadGroups} disabled={loadingGroups}
                                    className="gap-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold">
                                    {loadingGroups ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <RefreshCw className="w-3.5 h-3.5" />}
                                    Load Groups
                                </Button>
                            )}

                            {/* Groups Grid */}
                            {groups.length > 0 && (
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                            {selectedGroups.size} of {groups.length} groups selected
                                        </label>
                                        <Button variant="ghost" onClick={toggleAllGroups}
                                            className="h-auto p-0 text-[10px] font-bold text-blue-600 hover:text-blue-700 hover:bg-transparent">
                                            {selectedGroups.size === groups.length ? 'Deselect all' : 'Select all'}
                                        </Button>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-[40vh] overflow-y-auto p-0.5">
                                        {groups.map(g => (
                                            <button key={g.id} onClick={() => toggleGroup(g.id)}
                                                className={`flex items-center gap-3 p-3 rounded-xl border text-left transition-all ${selectedGroups.has(g.id)
                                                    ? 'bg-blue-50 border-blue-300 ring-1 ring-blue-200'
                                                    : 'bg-white border-slate-200 hover:border-blue-200'}`}>
                                                <div className={`w-5 h-5 rounded-md border-2 flex items-center justify-center shrink-0 transition-colors ${selectedGroups.has(g.id) ? 'bg-blue-500 border-blue-500' : 'border-slate-300'}`}>
                                                    {selectedGroups.has(g.id) && <Check className="w-3 h-3 text-white" />}
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="text-xs font-bold text-slate-800 truncate">{g.name}</p>
                                                    <p className="text-[10px] text-slate-400">{g.memberCount?.toLocaleString()} members · {g.type}</p>
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Extract Button */}
                            {groups.length > 0 && (
                                <div className="flex items-center gap-3 pt-2 border-t border-slate-100">
                                    {extractStatus === 'running' ? (
                                        <Button onClick={handleStopExtraction} variant="destructive" className="gap-1.5 text-xs font-bold">
                                            <Square className="w-3.5 h-3.5" />{__('general.stop_extraction')}</Button>
                                    ) : (
                                        <Button onClick={handleStartExtraction} disabled={selectedGroups.size === 0}
                                            className="gap-1.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white text-xs font-bold shadow-lg shadow-blue-500/20">
                                            <Play className="w-3.5 h-3.5" /> Extract Members from {selectedGroups.size} Group{selectedGroups.size !== 1 ? 's' : ''}
                                        </Button>
                                    )}
                                    {extractProgress && (
                                        <p className="text-[10px] text-slate-500 font-medium animate-pulse">{extractProgress}</p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Stats */}
                        {members.length > 0 && (
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <StatCard label={__('general.total_members')} value={members.length} icon={Users} color="bg-blue-50/80 border-blue-200/60" />
                                <StatCard label={__('general.with_phone')} value={members.filter(m => m.phone).length} icon={Phone} color="bg-emerald-50/80 border-emerald-200/60" />
                                <StatCard label={__('general.with_username')} value={members.filter(m => m.username).length} icon={AtSign} color="bg-purple-50/80 border-purple-200/60" />
                                <StatCard label={__('general.unique_groups')} value={new Set(members.map(m => m.group_name)).size} icon={Globe2} color="bg-amber-50/80 border-amber-200/60" />
                            </div>
                        )}

                        {/* Members Table */}
                        <MembersTable members={members} status={extractStatus} onExport={() => exportMembersCSV(members)} />
                    </div>
                )}

                {/* ════════════════ SEND TAB ════════════════ */}
                {activeTab === 'send' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 md:p-6 space-y-5">
                            <div className="flex items-center gap-3 mb-1">
                                <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-500 to-pink-400 flex items-center justify-center">
                                    <MessageCircle className="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <h2 className="text-sm font-bold text-slate-800">{__('general.message_sender')}</h2>
                                    <p className="text-[10px] text-slate-400 font-medium">{__('general.send_messages_to_contacts_with_multi_session_rotation')}</p>
                                </div>
                            </div>

                            {/* Session selector for sending */}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">Sessions to Use (multi-select)</label>
                                {connectedSessions.length === 0 ? (
                                    <div className="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center gap-2">
                                        <AlertCircle className="w-4 h-4 shrink-0" />
                                        <span>{__('general.no_connected_sessions_available')}</span>
                                    </div>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {connectedSessions.map(s => (
                                            <button key={s.phone} onClick={() => {
                                                setSendSessions(prev =>
                                                    prev.includes(s.phone) ? prev.filter(p => p !== s.phone) : [...prev, s.phone]
                                                );
                                            }}
                                                className={`px-3 py-2 rounded-xl border text-xs font-bold transition-all ${sendSessions.includes(s.phone)
                                                    ? 'bg-violet-50 border-violet-300 text-violet-700 ring-2 ring-violet-200'
                                                    : 'bg-white border-slate-200 text-slate-600 hover:border-violet-200'}`}>
                                                <span className="flex items-center gap-1.5">
                                                    <span className={`w-2 h-2 rounded-full ${sendSessions.includes(s.phone) ? 'bg-violet-400' : 'bg-slate-300'}`} />
                                                    {s.phone}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Recipients */}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                    Recipients (one per line: phone or user_id,name)
                                </label>
                                <Textarea value={recipientsText} onChange={e => setRecipientsText(e.target.value)}
                                    placeholder="201001234567,Ahmed&#10;201009876543,Sara&#10;201005555555"
                                    rows={5} className="font-mono text-xs resize-y" />
                                <p className="text-[10px] text-slate-400">{recipientsText.split('\n').filter(l => l.trim()).length} recipients</p>
                            </div>

                            {/* Message */}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">Message</label>
                                <Textarea value={messageText} onChange={e => setMessageText(e.target.value)}
                                    placeholder={__('general.type_your_message_here')} rows={4} className="text-xs resize-y" />
                            </div>

                            {/* Delay */}
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">Min Delay (sec)</label>
                                    <Input type="number" value={sendMinDelay} onChange={e => setSendMinDelay(+e.target.value)}
                                        min={1} max={60} className="text-xs font-mono" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">Max Delay (sec)</label>
                                    <Input type="number" value={sendMaxDelay} onChange={e => setSendMaxDelay(+e.target.value)}
                                        min={1} max={120} className="text-xs font-mono" />
                                </div>
                            </div>

                            {/* Send Button */}
                            <div className="flex items-center gap-3 pt-2 border-t border-slate-100">
                                {sendStatus === 'running' ? (
                                    <Button onClick={handleStopSending} variant="destructive" className="gap-1.5 text-xs font-bold">
                                        <Square className="w-3.5 h-3.5" />{__('general.stop_sending')}</Button>
                                ) : (
                                    <Button onClick={handleStartSending}
                                        disabled={!sendSessions.length || !recipientsText.trim() || !messageText.trim()}
                                        className="gap-1.5 bg-gradient-to-r from-violet-600 to-pink-500 hover:from-violet-700 hover:to-pink-600 text-white text-xs font-bold shadow-lg shadow-violet-500/20">
                                        <Send className="w-3.5 h-3.5" />{__('general.start_sending')}</Button>
                                )}
                            </div>
                        </div>

                        {/* Send Stats */}
                        {(sendStatus === 'running' || sendStatus === 'done') && (
                            <>
                                <div className="grid grid-cols-3 gap-3">
                                    <StatCard label="Sent" value={sendStats.sent} icon={CheckCircle2} color="bg-emerald-50/80 border-emerald-200/60" />
                                    <StatCard label="Failed" value={sendStats.failed} icon={AlertCircle} color="bg-red-50/80 border-red-200/60" />
                                    <StatCard label="Total" value={sendStats.total} icon={Users} color="bg-slate-50/80 border-slate-200/60" />
                                </div>

                                {/* Send Log */}
                                <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                                    <div className="px-5 py-4 border-b border-slate-100">
                                        <h3 className="font-bold text-slate-800 text-sm">{__('general.send_log')}</h3>
                                    </div>
                                    <div className="divide-y divide-slate-100 max-h-[40vh] overflow-y-auto">
                                        {sendLogs.map((log, i) => (
                                            <div key={log.id || i} className="flex items-center gap-3 px-5 py-3">
                                                <div className={`w-2 h-2 rounded-full shrink-0 ${log.status === 'sent' ? 'bg-emerald-400' : 'bg-red-400'}`} />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-xs font-semibold text-slate-700 truncate">{log.name}</p>
                                                    {log.error && <p className="text-[10px] text-red-500">{log.error}</p>}
                                                </div>
                                                <Badge variant={log.status === 'sent' ? 'default' : 'destructive'} className="text-[10px]">
                                                    {log.status}
                                                </Badge>
                                                <span className="text-[10px] text-slate-400 font-mono">{log.session}</span>
                                            </div>
                                        ))}
                                        {sendLogs.length === 0 && (
                                            <div className="px-5 py-8 text-center text-xs text-slate-400">
                                                {sendStatus === 'running' ? 'Waiting for messages...' : 'No messages sent yet.'}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                )}

                {/* ════════════════ SESSIONS TAB ════════════════ */}
                {activeTab === 'sessions' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 md:p-6 space-y-5">
                            <div className="flex items-center gap-3 mb-1">
                                <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-400 flex items-center justify-center">
                                    <Shield className="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <h2 className="text-sm font-bold text-slate-800">{__('general.telegram_sessions')}</h2>
                                    <p className="text-[10px] text-slate-400 font-medium">{__('general.add_and_manage_your_telegram_accounts')}</p>
                                </div>
                            </div>

                            {/* Add session */}
                            {otpPhone ? (
                                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-3">
                                    <p className="text-xs font-bold text-blue-800">Enter the code sent to {otpPhone}</p>
                                    <div className="flex gap-2">
                                        <Input value={otpCode} onChange={e => setOtpCode(e.target.value)}
                                            placeholder="12345" className="font-mono text-sm tracking-widest max-w-[200px]" maxLength={6} />
                                        <Button onClick={handleVerifyOTP} disabled={verifying || !otpCode.trim()}
                                            className="gap-1 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white">
                                            {verifying ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Check className="w-3.5 h-3.5" />}
                                            Verify
                                        </Button>
                                        <Button variant="ghost" onClick={() => { setOtpPhone(''); setOtpCode(''); }} className="text-xs">
                                            Cancel
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex gap-2">
                                    <div className="relative flex-1 max-w-xs">
                                        <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <Input value={newPhone} onChange={e => setNewPhone(e.target.value)}
                                            placeholder="+201001234567" className="pl-9 font-mono text-xs" />
                                    </div>
                                    <Button onClick={handleAddSession} disabled={addingSession || !newPhone.trim()}
                                        className="gap-1 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white">
                                        {addingSession ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Plus className="w-3.5 h-3.5" />}
                                        Add Session
                                    </Button>
                                </div>
                            )}

                            {/* Sessions list */}
                            <div className="space-y-2">
                                {sessions.length === 0 ? (
                                    <div className="text-center py-10 text-slate-400">
                                        <Shield className="w-8 h-8 mx-auto mb-2 opacity-30" />
                                        <p className="text-xs font-medium">{__('general.no_sessions_added_yet')}</p>
                                        <p className="text-[10px]">{__('general.add_a_telegram_phone_number_to_get_started')}</p>
                                    </div>
                                ) : sessions.map(s => (
                                    <div key={s.phone} className="flex items-center gap-4 p-4 border border-slate-200 rounded-xl hover:bg-slate-50/50 transition-colors">
                                        <div className={`w-3 h-3 rounded-full shrink-0 ${s.status === 'connected' ? 'bg-emerald-400' : s.status === 'awaiting_code' ? 'bg-amber-400 animate-pulse' : 'bg-red-400'}`} />
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-bold text-slate-800 font-mono">{s.phone}</p>
                                            <p className="text-[10px] text-slate-400">{s.name || 'No name'} · Added {new Date(s.created_at).toLocaleDateString()}</p>
                                        </div>
                                        <Badge variant={s.status === 'connected' ? 'default' : s.status === 'awaiting_code' ? 'secondary' : 'destructive'}
                                            className="text-[10px] font-bold capitalize">
                                            {s.status.replace(/_/g, ' ')}
                                        </Badge>
                                        <Button variant="ghost" size="icon" onClick={() => handleRemoveSession(s.phone)}
                                            className="h-8 w-8 text-slate-400 hover:text-red-500 hover:bg-red-50">
                                            <Trash2 className="w-3.5 h-3.5" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* ════════════════ HISTORY TAB ════════════════ */}
                {activeTab === 'history' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        {selectedCampaign ? (
                            // Campaign detail view
                            <div className="space-y-4">
                                <Button variant="ghost" onClick={() => { setSelectedCampaign(null); setCampaignMembers([]); }}
                                    className="gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-800">
                                    <ArrowLeft className="w-3.5 h-3.5" />{__('general.back_to_history')}</Button>
                                <div className="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                                    <div className="flex items-center gap-3 mb-3">
                                        <Badge variant={selectedCampaign.type === 'extract' ? 'default' : 'secondary'} className="text-[10px] font-bold capitalize">
                                            {selectedCampaign.type}
                                        </Badge>
                                        <Badge variant={selectedCampaign.status === 'completed' ? 'default' : 'destructive'} className="text-[10px] font-bold capitalize">
                                            {selectedCampaign.status}
                                        </Badge>
                                    </div>
                                    <h3 className="font-bold text-slate-800 text-sm mb-1">{selectedCampaign.target_name || 'Campaign'}</h3>
                                    <p className="text-[10px] text-slate-400">{new Date(selectedCampaign.created_at).toLocaleString()} · {selectedCampaign.total} results</p>
                                </div>
                                <MembersTable members={campaignMembers} onExport={() => exportMembersCSV(campaignMembers, `campaign-${selectedCampaign.id}`)} />
                            </div>
                        ) : (
                            // Campaign list
                            <div className="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 md:p-6 space-y-5">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-500 to-orange-400 flex items-center justify-center">
                                            <History className="w-4 h-4 text-white" />
                                        </div>
                                        <div>
                                            <h2 className="text-sm font-bold text-slate-800">{__('general.campaign_history')}</h2>
                                            <p className="text-[10px] text-slate-400 font-medium">{__('general.past_extraction_and_sending_campaigns')}</p>
                                        </div>
                                    </div>
                                    <Button variant="ghost" onClick={handleLoadCampaigns} disabled={loadingCampaigns} className="gap-1 text-xs">
                                        <RefreshCw className={`w-3.5 h-3.5 ${loadingCampaigns ? 'animate-spin' : ''}`} /> Refresh
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {campaigns.length === 0 ? (
                                        <div className="text-center py-10 text-slate-400">
                                            <History className="w-8 h-8 mx-auto mb-2 opacity-30" />
                                            <p className="text-xs font-medium">{__('general.no_campaigns_yet')}</p>
                                            <p className="text-[10px]">{__('general.start_an_extraction_or_send_campaign_to_see_it_here')}</p>
                                        </div>
                                    ) : campaigns.map(c => (
                                        <button key={c.id} onClick={() => handleViewCampaign(c)}
                                            className="w-full flex items-center gap-4 p-4 border border-slate-200 rounded-xl hover:bg-slate-50/50 transition-colors text-left">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${c.type === 'extract' ? 'bg-blue-50 text-blue-500' : 'bg-violet-50 text-violet-500'}`}>
                                                {c.type === 'extract' ? <Users className="w-5 h-5" /> : <MessageCircle className="w-5 h-5" />}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-xs font-bold text-slate-800 truncate">{c.target_name || 'Campaign'}</p>
                                                <p className="text-[10px] text-slate-400">
                                                    {new Date(c.created_at).toLocaleString()} · {c.total} results
                                                </p>
                                            </div>
                                            <Badge variant={c.status === 'completed' ? 'default' : c.status === 'running' ? 'secondary' : 'destructive'}
                                                className="text-[10px] font-bold capitalize shrink-0">
                                                {c.status}
                                            </Badge>
                                            <ChevronDown className="w-4 h-4 text-slate-300 -rotate-90" />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
