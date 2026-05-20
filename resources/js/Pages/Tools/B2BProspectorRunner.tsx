import React, { useState, useEffect, useRef } from 'react';
import { LayoutDashboard, Users, Mail, Key, Settings, Play, Square, Download, Plus, CheckCircle, AlertCircle, Info, ChevronRight, Search, Trash2, MailQuestion, ShieldCheck, Zap, RefreshCw, Layers, HelpCircle, FileText } from 'lucide-react';

// Unified WebSocket client connecting to the Musoftware Runtime
function useRuntimeWS(pluginSlug: string, onBroadcast?: ((event: string, data: any) => void) | null) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef<Map<string, { resolve: Function, reject: Function }>>(new Map());
    const onBroadcastRef = useRef<((event: string, data: any) => void) | null>(null);

    onBroadcastRef.current = onBroadcast || null;

    useEffect(() => {
        const host = typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
        const socket = new WebSocket(`ws://${host}:18401/ws`);
        
        socket.onopen = () => setConnected(true);
        socket.onclose = () => setConnected(false);
        
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
                if (msg.event && onBroadcastRef.current) {
                    onBroadcastRef.current(msg.event, msg.data);
                }
            } catch (err) {}
        };
        
        setWs(socket);
        return () => socket.close();
    }, []);

    const callRPC = async (action: string, data: any = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) throw new Error('Not connected to runtime agent');
        
        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(7);
            pendingRequests.current.set(requestId, { resolve, reject });
            
            ws.send(JSON.stringify({
                type: 'plugin_rpc',
                requestId,
                payload: { plugin: pluginSlug, action, data }
            }));
            
            setTimeout(() => {
                const resolver = pendingRequests.current.get(requestId);
                if (resolver) {
                    resolver.reject(new Error('RPC request timed out'));
                    pendingRequests.current.delete(requestId);
                }
            }, 30000);
        });
    };

    return { connected, callRPC };
}

export default function B2BProspectorRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [activeTab, setActiveTab] = useState<'campaigns' | 'leads' | 'inboxes' | 'outreach' | 'linked-profiles'>('campaigns');

    // State Variables
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [leads, setLeads] = useState<any[]>([]);
    const [totalLeads, setTotalLeads] = useState(0);
    const [leadsOffset, setLeadsOffset] = useState(0);
    const [leadsLimit] = useState(25);
    const [leadsSearch, setLeadsSearch] = useState('');
    const [leadsEmailFilter, setLeadsEmailFilter] = useState('');
    const [inboxes, setInboxes] = useState<any[]>([]);
    
    // Auth Cookie (LinkedIn li_at Session Key)
    const [linkedInSession, setLinkedInSession] = useState<{ hasSession: boolean, isValid: boolean, validatedAt: string | null }>({ hasSession: false, isValid: false, validatedAt: null });
    const [sessionCookieInput, setSessionCookieInput] = useState('');
    const [savingCookie, setSavingCookie] = useState(false);

    // Selected campaign for filtering
    const [selectedCampaignId, setSelectedCampaignId] = useState<string>('');

    // Inbox forms
    const [newInboxEmail, setNewInboxEmail] = useState('');
    const [newInboxHost, setNewInboxHost] = useState('');
    const [newInboxPort, setNewInboxPort] = useState('587');
    const [newInboxUser, setNewInboxUser] = useState('');
    const [newInboxPass, setNewInboxPass] = useState('');
    const [newInboxImapHost, setNewInboxImapHost] = useState('');
    const [newInboxImapPort, setNewInboxImapPort] = useState('993');
    const [inboxTestingId, setInboxTestingId] = useState<string | null>(null);

    // Sequence Forms
    const [selectedSequenceCampaignId, setSelectedSequenceCampaignId] = useState('');
    const [sequences, setSequences] = useState<any[]>([]);
    const [seqSubject, setSeqSubject] = useState('');
    const [seqBody, setSeqBody] = useState('');
    const [seqDelay, setSeqDelay] = useState(1);
    const [savingSequence, setSavingSequence] = useState(false);

    // New Campaign Modal / Form
    const [showNewCampaignModal, setShowNewCampaignModal] = useState(false);
    const [newCampName, setNewCampName] = useState('');
    const [newCampKeyword, setNewCampKeyword] = useState('');
    const [newCampCountry, setNewCampCountry] = useState('USA');
    const [newCampCity, setNewCampCity] = useState('');
    const [newCampSources, setNewCampSources] = useState<string[]>(['linkedin']);
    const [newCampLimit, setNewCampLimit] = useState(100);

    // Real-time notification lists
    const [realtimeLogs, setRealtimeLogs] = useState<any[]>([]);
    
    // E2E or Running Campaign Status
    const [runningCampaignIds, setRunningCampaignIds] = useState<string[]>([]);
    const [campaignStats, setCampaignStats] = useState<Record<string, any>>({});

    // CSV export state
    const [exportingJobId, setExportingJobId] = useState<string | null>(null);
    const [exportProgress, setExportProgress] = useState<number | null>(null);
    const [exportFilePath, setExportFilePath] = useState<string | null>(null);

    const onBroadcast = (event: string, data: any) => {
        // Handle Realtime logs & events
        if (event === 'prospecting.lead.extracted.ui' && data.lead) {
            // Append lead if it matches selected campaign or no campaign selected
            if (!selectedCampaignId || data.lead.campaign_id === selectedCampaignId) {
                setLeads(prev => {
                    const exists = prev.some(l => l.id === data.lead.id);
                    if (exists) return prev;
                    return [data.lead, ...prev].slice(0, leadsLimit);
                });
                setTotalLeads(prev => prev + 1);
            }
            
            // Log activity
            addRealtimeLog(`Harvested: ${data.lead.name || 'Decision Maker'} (${data.lead.company || 'Unknown Inc.'})`);
            
            // Refresh stats for the campaign
            fetchCampaignStats(data.lead.campaign_id);
            fetchCampaignsList();
        }

        if (event === 'prospecting.campaign.updated') {
            const { campaignId, status, error } = data;
            setCampaigns(prev => prev.map(c => c.id === campaignId ? { ...c, status } : c));
            if (status === 'running') {
                setRunningCampaignIds(prev => [...prev.filter(id => id !== campaignId), campaignId]);
                addRealtimeLog(`Campaign started successfully.`);
            } else {
                setRunningCampaignIds(prev => prev.filter(id => id !== campaignId));
                if (status === 'completed') {
                    addRealtimeLog(`Campaign finished! All leads enriched and verified.`);
                } else if (status === 'failed') {
                    addRealtimeLog(`Campaign error: ${error || 'Unknown issue'}`);
                }
            }
            fetchCampaignsList();
        }

        if (event === 'lead.saved') {
            addRealtimeLog(`Email verified: Status is ${data.emailStatus || 'unverified'} (Score: ${data.score || 0}/100)`);
        }

        if (event === 'outreach.sent') {
            addRealtimeLog(`Outbound Sequence Sent for Lead ID: ${data.leadId}`);
        }

        if (event === 'export.progress') {
            setExportProgress(data.exported);
        }

        if (event === 'export.completed') {
            setExportProgress(null);
            setExportFilePath(data.filePath);
            addRealtimeLog(`Leads exported successfully to local path.`);
            setExportingJobId(null);
        }

        if (event === 'export.failed') {
            setExportProgress(null);
            alert(`Export failed: ${data.error}`);
            setExportingJobId(null);
        }

        if (event === 'inbox.connected') {
            addRealtimeLog(`Email connected: ${data.email} is ready to send messages.`);
            fetchInboxesList();
        }

        if (event === 'inbox.failed') {
            addRealtimeLog(`Email connection failed for ${data.email}: ${data.error}`);
            fetchInboxesList();
        }
    };

    const addRealtimeLog = (message: string) => {
        setRealtimeLogs(prev => [{ id: Math.random().toString(), message, time: new Date().toLocaleTimeString() }, ...prev].slice(0, 50));
    };

    const { connected: agentConnected, callRPC } = useRuntimeWS('b2b-prospector', onBroadcast);

    // Initial Fetching
    useEffect(() => {
        if (agentConnected) {
            fetchCampaignsList();
            fetchInboxesList();
            fetchLinkedInSession();
            addRealtimeLog('Connected to Local Privacy Vault. System Online.');
        }
    }, [agentConnected]);

    // Fetch leads when page/search changes
    useEffect(() => {
        if (agentConnected) {
            fetchLeadsList();
        }
    }, [selectedCampaignId, leadsOffset, leadsSearch, leadsEmailFilter, agentConnected]);

    // Fetch sequences when campaign changes in outreach
    useEffect(() => {
        if (agentConnected && selectedSequenceCampaignId) {
            fetchSequencesList(selectedSequenceCampaignId);
        }
    }, [selectedSequenceCampaignId, agentConnected]);

    const fetchCampaignsList = async () => {
        try {
            const list: any = await callRPC('prospecting.campaigns.list');
            setCampaigns(list || []);
            
            // Populate running campaign IDs
            const running = list.filter((c: any) => c.status === 'running').map((c: any) => c.id);
            setRunningCampaignIds(running);

            // Fetch stats for campaigns
            list.forEach((c: any) => {
                fetchCampaignStats(c.id);
            });
        } catch (err) {
            console.error('Failed to load campaigns:', err);
        }
    };

    const fetchCampaignStats = async (campaignId: string) => {
        try {
            const stats: any = await callRPC('prospecting.campaign.stats', { campaignId });
            setCampaignStats(prev => ({ ...prev, [campaignId]: stats }));
        } catch (err) {
            console.error('Failed to load stats:', err);
        }
    };

    const fetchLeadsList = async () => {
        try {
            const res: any = await callRPC('prospecting.leads.list', {
                campaignId: selectedCampaignId || null,
                limit: leadsLimit,
                offset: leadsOffset,
                search: leadsSearch || undefined,
                email_status: leadsEmailFilter || undefined
            });
            setLeads(res.leads || []);
            setTotalLeads(res.total || 0);
        } catch (err) {
            console.error('Failed to load leads:', err);
        }
    };

    const fetchInboxesList = async () => {
        try {
            const list: any = await callRPC('prospecting.inboxes.list');
            setInboxes(list || []);
        } catch (err) {
            console.error('Failed to load inboxes:', err);
        }
    };

    const fetchLinkedInSession = async () => {
        try {
            const session: any = await callRPC('prospecting.auth.session.get');
            setLinkedInSession(session);
        } catch (err) {
            console.error('Failed to load LinkedIn session:', err);
        }
    };

    const fetchSequencesList = async (campaignId: string) => {
        try {
            const list: any = await callRPC('prospecting.templates.list', { campaignId });
            setSequences(list || []);
            if (list && list.length > 0) {
                setSeqSubject(list[0].subject || '');
                setSeqBody(list[0].body || '');
                setSeqDelay(list[0].delay_days || 1);
            } else {
                setSeqSubject('');
                setSeqBody('');
                setSeqDelay(1);
            }
        } catch (err) {
            console.error('Failed to load sequences:', err);
        }
    };

    // Actions
    const handleSaveLinkedInCookie = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!sessionCookieInput.trim()) return;

        setSavingCookie(true);
        try {
            await callRPC('prospecting.auth.session.save', { cookie: sessionCookieInput.trim() });
            addRealtimeLog('Saved LinkedIn cookie. Verifying session...');
            setSessionCookieInput('');
            await fetchLinkedInSession();
        } catch (err: any) {
            alert(`Failed to save: ${err.message}`);
        } finally {
            setSavingCookie(false);
        }
    };

    const handleCreateCampaign = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newCampName.trim() || !newCampKeyword.trim()) return;

        try {
            const newCamp = await callRPC('prospecting.campaign.create', {
                name: newCampName.trim(),
                keyword: newCampKeyword.trim(),
                country: newCampCountry.trim() || null,
                city: newCampCity.trim() || null,
                sources: newCampSources,
                daily_limit: newCampLimit
            });
            
            setShowNewCampaignModal(false);
            setNewCampName('');
            setNewCampKeyword('');
            setNewCampCity('');
            
            addRealtimeLog(`New campaign "${newCampName}" created in draft mode.`);
            fetchCampaignsList();
        } catch (err: any) {
            alert(`Failed to create campaign: ${err.message}`);
        }
    };

    const handleStartCampaign = async (campaignId: string) => {
        try {
            addRealtimeLog('Warming up browser scraper and launching prospecting sequence...');
            await callRPC('prospecting.campaign.start', { campaignId });
            fetchCampaignsList();
        } catch (err: any) {
            alert(`Launch failed: ${err.message}`);
        }
    };

    const handleStopCampaign = async (campaignId: string) => {
        try {
            await callRPC('prospecting.campaign.stop', { campaignId });
            addRealtimeLog('Sent pause signal to active scraper worker.');
            fetchCampaignsList();
        } catch (err: any) {
            alert(`Pause failed: ${err.message}`);
        }
    };

    const handleConnectInbox = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newInboxEmail.trim() || !newInboxHost.trim() || !newInboxUser.trim() || !newInboxPass.trim()) {
            return alert('Please fill in SMTP credentials to connect mailbox');
        }

        try {
            addRealtimeLog(`Registering outbox connection for ${newInboxEmail}...`);
            await callRPC('prospecting.inbox.add', {
                email: newInboxEmail.trim(),
                smtp_host: newInboxHost.trim(),
                smtp_port: parseInt(newInboxPort),
                smtp_user: newInboxUser.trim(),
                smtp_pass: newInboxPass,
                imap_host: newInboxImapHost.trim() || null,
                imap_port: parseInt(newInboxImapPort) || null,
                use_tls: true,
                provider: 'custom'
            });

            setNewInboxEmail('');
            setNewInboxHost('');
            setNewInboxUser('');
            setNewInboxPass('');
            setNewInboxImapHost('');
            fetchInboxesList();
        } catch (err: any) {
            alert(`Connect mailbox failed: ${err.message}`);
        }
    };

    const handleTestInbox = async (inbox: any) => {
        setInboxTestingId(inbox.id);
        addRealtimeLog(`Testing connectivity for ${inbox.email}...`);
        try {
            const res: any = await callRPC('prospecting.inbox.test', {
                id: inbox.id,
                email: inbox.email,
                smtp_host: inbox.smtp_host || '',
                smtp_port: inbox.smtp_port || 587,
                smtp_user: inbox.smtp_user || '',
                smtp_pass: inbox.smtp_pass || '',
            });
            if (res.ok) {
                addRealtimeLog(`Verified outbox ${inbox.email}: SMTP server responded OK.`);
            } else {
                addRealtimeLog(`Outbox test failed for ${inbox.email}: ${res.message}`);
            }
            fetchInboxesList();
        } catch (err: any) {
            addRealtimeLog(`Connection test failed: ${err.message}`);
        } finally {
            setInboxTestingId(null);
        }
    };

    const handleDeleteInbox = async (id: string) => {
        if (!confirm('Are you sure you want to disconnect this mailbox?')) return;
        try {
            await callRPC('prospecting.inbox.delete', { id });
            addRealtimeLog('Disconnected sending mailbox.');
            fetchInboxesList();
        } catch (err: any) {
            alert(err.message);
        }
    };

    const handleSaveSequence = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedSequenceCampaignId) return alert('Select a campaign first');

        setSavingSequence(true);
        try {
            await callRPC('prospecting.template.save', {
                campaign_id: selectedSequenceCampaignId,
                name: 'Outreach Sequence Step 1',
                step: 1,
                subject: seqSubject,
                body: seqBody,
                delay_days: seqDelay
            });
            addRealtimeLog('Saved custom cold sequence for campaign.');
            fetchSequencesList(selectedSequenceCampaignId);
        } catch (err: any) {
            alert(`Save failed: ${err.message}`);
        } finally {
            setSavingSequence(false);
        }
    };

    const handleExportLeads = async () => {
        addRealtimeLog('Initiating streaming CSV export from secure privacy database...');
        try {
            const res: any = await callRPC('prospecting.leads.export', {
                campaignId: selectedCampaignId || null,
                email_status: leadsEmailFilter || undefined,
                search: leadsSearch || undefined
            });
            setExportingJobId(res.jobId);
            setExportProgress(0);
        } catch (err: any) {
            alert(`Export trigger failed: ${err.message}`);
        }
    };

    const toggleSource = (source: string) => {
        setNewCampSources(prev => 
            prev.includes(source) ? prev.filter(s => s !== source) : [...prev, source]
        );
    };

    const getEmailBadgeColor = (status: string) => {
        switch (status) {
            case 'valid': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'invalid': return 'bg-rose-50 text-rose-700 border-rose-200';
            case 'catchall': return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'unknown':
            default: return 'bg-slate-50 text-slate-500 border-slate-200';
        }
    };

    const getEmailLabel = (status: string) => {
        switch (status) {
            case 'valid': return 'Verified Clean';
            case 'invalid': return 'Risky Bounce';
            case 'catchall': return 'Accept All';
            case 'unknown':
            default: return 'Unverified';
        }
    };

    const getScoreBadge = (score: number) => {
        if (score >= 80) return 'bg-teal-50 text-teal-700 border-teal-200 font-semibold';
        if (score >= 50) return 'bg-indigo-50 text-indigo-600 border-indigo-200';
        return 'bg-slate-50 text-slate-500 border-slate-200';
    };

    if (!agentConnected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">Syncing with Local Runtime Agent...</p>
                    <p className="text-xs text-slate-400">Make sure your Musoftware Runtime is running on your machine.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans flex flex-col antialiased selection:bg-teal-500 selection:text-white">
            {/* Topbar Navigation Bar - Clean, Glassmorphism aesthetic */}
            <header className="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center justify-between px-8 sticky top-0 z-30">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2.5">
                        <div className="w-6.5 h-6.5 bg-gradient-to-tr from-teal-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md shadow-teal-500/20">
                            <Layers className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-bold text-sm tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">B2B Prospector</span>
                    </div>
                    
                    <div className="h-5 w-px bg-slate-200" />
                    
                    <nav className="flex items-center gap-1.5">
                        <button 
                            onClick={() => setActiveTab('campaigns')}
                            className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${activeTab === 'campaigns' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'}`}
                        >
                            Find Leads
                        </button>
                        <button 
                            onClick={() => setActiveTab('leads')}
                            className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${activeTab === 'leads' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'}`}
                        >
                            Lead Manager
                        </button>
                        <button 
                            onClick={() => setActiveTab('inboxes')}
                            className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${activeTab === 'inboxes' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'}`}
                        >
                            Sending Mailboxes
                        </button>
                        <button 
                            onClick={() => setActiveTab('outreach')}
                            className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${activeTab === 'outreach' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'}`}
                        >
                            Outreach Sequences
                        </button>
                        <button 
                            onClick={() => setActiveTab('linked-profiles')}
                            className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${activeTab === 'linked-profiles' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'}`}
                        >
                            LinkedIn Profile
                        </button>
                    </nav>
                </div>

                <div className="flex items-center gap-4">
                    {/* Database status indicator */}
                    <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-800">
                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-[10px] font-bold uppercase tracking-wider font-mono">Secure SQLite Active</span>
                    </div>
                </div>
            </header>

            {/* Main Multi-Workspace Layout */}
            <div className="flex-1 flex overflow-hidden">
                {/* Real-time Side Stream panel (Operational UX) */}
                <aside className="w-72 border-r border-slate-200 bg-white flex flex-col justify-between hidden lg:flex shrink-0">
                    <div className="p-5 flex-1 flex flex-col min-h-0">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-1.5 h-1.5 rounded-full bg-teal-500 animate-ping" />
                            <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400">Live Activity Feed</h3>
                        </div>
                        <div className="flex-1 overflow-y-auto space-y-3 pr-1 font-sans scrollbar-thin">
                            {realtimeLogs.length === 0 ? (
                                <div className="text-center py-20 text-slate-400 text-xs flex flex-col items-center gap-2">
                                    <HelpCircle className="w-6 h-6 text-slate-300" />
                                    <span>Activity feed is clear</span>
                                    <span>Launch a search to harvest leads</span>
                                </div>
                            ) : (
                                realtimeLogs.map(log => (
                                    <div key={log.id} className="p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                                        <p className="text-slate-800 text-xs leading-relaxed font-medium">{log.message}</p>
                                        <span className="text-[10px] text-slate-400 font-mono mt-1 block">{log.time}</span>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                    
                    <div className="p-5 border-t border-slate-100 bg-slate-50/50">
                        <div className="space-y-3">
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-semibold">LinkedIn Scraper:</span>
                                <span className={`font-bold ${linkedInSession.hasSession ? 'text-emerald-600' : 'text-slate-400'}`}>
                                    {linkedInSession.hasSession ? 'Linked Key' : 'Not Configured'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-semibold">Mailboxes connected:</span>
                                <span className="font-bold text-slate-900">{inboxes.length} Active</span>
                            </div>
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-semibold">Active Searches:</span>
                                <span className={`font-bold ${runningCampaignIds.length > 0 ? 'text-teal-600 animate-pulse' : 'text-slate-600'}`}>
                                    {runningCampaignIds.length} running
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>

                {/* Main Workspace content */}
                <main className="flex-1 overflow-y-auto p-8 max-w-5xl mx-auto w-full">
                    {/* WORKSPACE 1: FIND LEADS (CAMPAIGNS) */}
                    {activeTab === 'campaigns' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Lead Finder</h1>
                                    <p className="text-xs text-slate-500 mt-1">Create searching sequences to discover target accounts and verify their email data privately.</p>
                                </div>
                                <button 
                                    onClick={() => setShowNewCampaignModal(true)}
                                    className="flex items-center gap-1.5 px-4 py-2 bg-slate-950 text-white rounded-xl text-xs font-semibold hover:bg-slate-850 hover:shadow-lg hover:shadow-slate-950/10 transition-all active:scale-98"
                                >
                                    <Plus className="w-4 h-4" /> New Search Campaign
                                </button>
                            </div>

                            {/* Campaign Grid */}
                            {campaigns.length === 0 ? (
                                <div className="py-24 text-center border-2 border-dashed border-slate-200 rounded-3xl bg-white shadow-sm">
                                    <Users className="w-10 h-10 text-slate-300 mx-auto mb-4" />
                                    <h3 className="text-sm font-bold text-slate-900">No campaigns launched yet</h3>
                                    <p className="text-xs text-slate-500 mt-1.5 max-w-xs mx-auto">Create your first Lead Finder campaign to start sourcing verified emails locally.</p>
                                    <button 
                                        onClick={() => setShowNewCampaignModal(true)}
                                        className="mt-6 px-4 py-2 bg-slate-950 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition-all"
                                    >
                                        Create Campaign
                                    </button>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {campaigns.map(camp => {
                                        const stats = campaignStats[camp.id] || { total: 0, valid: 0, emailed: 0 };
                                        const isRunning = runningCampaignIds.includes(camp.id);
                                        
                                        return (
                                            <div key={camp.id} className="bg-white border border-slate-200/80 rounded-2xl p-6 hover:shadow-md hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden group">
                                                {isRunning && (
                                                    <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-teal-400 to-indigo-500 animate-pulse" />
                                                )}
                                                
                                                <div>
                                                    <div className="flex items-start justify-between mb-4">
                                                        <div>
                                                            <h3 className="font-bold text-slate-900 text-sm leading-tight group-hover:text-teal-600 transition-colors">{camp.name}</h3>
                                                            <span className="text-[10px] text-slate-400 mt-0.5 block font-mono">{camp.keyword} • {camp.city || camp.country || 'Global'}</span>
                                                        </div>
                                                        <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold tracking-wide uppercase border ${
                                                            camp.status === 'running' ? 'bg-teal-50 text-teal-700 border-teal-200' :
                                                            camp.status === 'completed' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                                                            'bg-slate-50 text-slate-500 border-slate-200'
                                                        }`}>
                                                            {camp.status}
                                                        </span>
                                                    </div>

                                                    {/* Scrape Target Detail */}
                                                    <div className="flex items-center gap-2 mt-2 py-1.5 px-2 bg-slate-50 rounded-lg text-[10px] text-slate-500">
                                                        <span className="font-bold">Sources:</span>
                                                        <span>{camp.sources?.join(', ') || 'LinkedIn'}</span>
                                                        <span className="h-2 w-px bg-slate-200" />
                                                        <span className="font-bold">Speed Limit:</span>
                                                        <span>{camp.daily_limit} leads</span>
                                                    </div>

                                                    {/* Metrics Grid */}
                                                    <div className="grid grid-cols-3 gap-2.5 my-5">
                                                        <div className="bg-slate-50/50 p-2.5 rounded-xl border border-slate-100 text-center">
                                                            <span className="text-slate-400 text-[10px] font-semibold block">Extracted</span>
                                                            <span className="text-slate-900 text-sm font-bold block mt-0.5">{stats.total}</span>
                                                        </div>
                                                        <div className="bg-emerald-50/20 p-2.5 rounded-xl border border-emerald-100/30 text-center">
                                                            <span className="text-emerald-700/60 text-[10px] font-semibold block">Clean Emails</span>
                                                            <span className="text-emerald-700 text-sm font-bold block mt-0.5">{stats.valid}</span>
                                                        </div>
                                                        <div className="bg-slate-50/50 p-2.5 rounded-xl border border-slate-100 text-center">
                                                            <span className="text-slate-400 text-[10px] font-semibold block">Emailed</span>
                                                            <span className="text-slate-900 text-sm font-bold block mt-0.5">{stats.emailed}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Bottom controls */}
                                                <div className="border-t border-slate-100 pt-4 flex items-center justify-between gap-3">
                                                    <button 
                                                        onClick={() => {
                                                            setSelectedCampaignId(camp.id);
                                                            setActiveTab('leads');
                                                        }}
                                                        className="text-[11px] font-bold text-slate-500 hover:text-slate-900 flex items-center gap-1 transition-colors"
                                                    >
                                                        View Leads <ChevronRight className="w-3.5 h-3.5" />
                                                    </button>
                                                    
                                                    {isRunning ? (
                                                        <button 
                                                            onClick={() => handleStopCampaign(camp.id)}
                                                            className="flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-100 rounded-lg text-[10px] font-bold transition-all active:scale-95"
                                                        >
                                                            <Square className="w-3 h-3 fill-rose-700" /> Pause Search
                                                        </button>
                                                    ) : (
                                                        <button 
                                                            onClick={() => handleStartCampaign(camp.id)}
                                                            className="flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-100 rounded-lg text-[10px] font-bold transition-all active:scale-95"
                                                        >
                                                            <Play className="w-3 h-3 fill-teal-700" /> Launch Scraper
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    )}

                    {/* WORKSPACE 2: LEAD MANAGER (CRM TABLE) */}
                    {activeTab === 'leads' && (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Lead Database</h1>
                                    <p className="text-xs text-slate-500 mt-1">Review harvested decision makers, verified contact emails, and outbound status.</p>
                                </div>
                                <button 
                                    onClick={handleExportLeads}
                                    disabled={!!exportingJobId}
                                    className="flex items-center justify-center gap-1.5 px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 rounded-xl text-xs font-semibold transition-all active:scale-98 shadow-sm disabled:opacity-50"
                                >
                                    <Download className="w-4 h-4" /> 
                                    {exportingJobId ? `Exporting (${exportProgress || 0} rows)...` : 'Download CSV'}
                                </button>
                            </div>

                            {/* Export Path Notification */}
                            {exportFilePath && (
                                <div className="p-3.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs flex items-start gap-2.5">
                                    <CheckCircle className="w-4.5 h-4.5 text-emerald-600 shrink-0 mt-0.5" />
                                    <div>
                                        <p className="font-bold">Leads Exported to Local Drive</p>
                                        <p className="text-[11px] opacity-90 mt-0.5 font-mono select-all">{exportFilePath}</p>
                                    </div>
                                </div>
                            )}

                            {/* Filters and Search toolbar */}
                            <div className="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col md:flex-row items-center gap-3.5">
                                <div className="relative flex-1 w-full">
                                    <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3.5" />
                                    <input 
                                        type="text" 
                                        placeholder="Search by name, company, or email..." 
                                        value={leadsSearch}
                                        onChange={(e) => { setLeadsSearch(e.target.value); setLeadsOffset(0); }}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-900 transition-all placeholder:text-slate-400"
                                    />
                                </div>

                                <div className="flex items-center gap-3.5 w-full md:w-auto shrink-0">
                                    {/* Campaign Selector */}
                                    <select 
                                        value={selectedCampaignId}
                                        onChange={(e) => { setSelectedCampaignId(e.target.value); setLeadsOffset(0); }}
                                        className="w-full md:w-44 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none"
                                    >
                                        <option value="">All Campaigns</option>
                                        {campaigns.map(c => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>

                                    {/* Email Health Status Selector */}
                                    <select 
                                        value={leadsEmailFilter}
                                        onChange={(e) => { setLeadsEmailFilter(e.target.value); setLeadsOffset(0); }}
                                        className="w-full md:w-44 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none"
                                    >
                                        <option value="">All Verification States</option>
                                        <option value="valid">Verified Clean</option>
                                        <option value="invalid">Risky Bounce</option>
                                        <option value="catchall">Accept All</option>
                                        <option value="unverified">Unverified</option>
                                    </select>
                                </div>
                            </div>

                            {/* Leads List / Table */}
                            {leads.length === 0 ? (
                                <div className="py-20 text-center bg-white border border-slate-200 rounded-2xl">
                                    <MailQuestion className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                    <h3 className="text-sm font-bold text-slate-900">No matching leads found</h3>
                                    <p className="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Verify that you have launched an active lead finder campaign, or try clearing search queries.</p>
                                </div>
                            ) : (
                                <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50/50 border-b border-slate-200 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                                                    <th className="p-4 pl-6">Profile</th>
                                                    <th className="p-4">Corporate Role</th>
                                                    <th className="p-4">Contact Info</th>
                                                    <th className="p-4">Quality Score</th>
                                                    <th className="p-4">Outbox status</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100 text-xs">
                                                {leads.map(lead => (
                                                    <tr key={lead.id} className="hover:bg-slate-50/50 transition-colors">
                                                        <td className="p-4 pl-6">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-8 h-8 bg-gradient-to-tr from-slate-100 to-slate-200 text-slate-600 rounded-lg flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                                                    {lead.name ? lead.name[0] : '?'}
                                                                </div>
                                                                <div>
                                                                    <span className="font-bold text-slate-900 block">{lead.name || 'Decision Maker'}</span>
                                                                    <span className="text-[10px] text-slate-400 block font-mono">{lead.source}</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="p-4">
                                                            <span className="text-slate-900 font-medium block">{lead.title || 'Executive'}</span>
                                                            <span className="text-[10px] text-slate-400 block">{lead.company || 'Confidential Company'}</span>
                                                        </td>
                                                        <td className="p-4">
                                                            <span className="text-slate-900 font-mono block select-all">{lead.email || 'Searching email...'}</span>
                                                            {lead.email && (
                                                                <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold border inline-block mt-1 ${getEmailBadgeColor(lead.email_status)}`}>
                                                                    {getEmailLabel(lead.email_status)}
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="p-4">
                                                            <span className={`px-2.5 py-1 rounded-lg text-[10px] font-bold border ${getScoreBadge(lead.lead_score)}`}>
                                                                {lead.lead_score ? `${lead.lead_score}/100` : 'Evaluating...'}
                                                            </span>
                                                        </td>
                                                        <td className="p-4">
                                                            <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold uppercase ${
                                                                lead.outreach_status === 'sent' ? 'bg-indigo-50 text-indigo-700' :
                                                                'bg-slate-50 text-slate-500'
                                                            }`}>
                                                                {lead.outreach_status}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    {/* Pagination Controls */}
                                    <div className="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                        <span>Showing {leads.length} of {totalLeads} harvested leads</span>
                                        <div className="flex items-center gap-2">
                                            <button 
                                                onClick={() => setLeadsOffset(prev => Math.max(0, prev - leadsLimit))}
                                                disabled={leadsOffset === 0}
                                                className="px-3 py-1.5 border border-slate-200 rounded-lg hover:bg-white transition-colors bg-slate-100 disabled:opacity-50 font-semibold"
                                            >
                                                Back
                                            </button>
                                            <button 
                                                onClick={() => setLeadsOffset(prev => prev + leadsLimit)}
                                                disabled={leadsOffset + leadsLimit >= totalLeads}
                                                className="px-3 py-1.5 border border-slate-200 rounded-lg hover:bg-white transition-colors bg-slate-100 disabled:opacity-50 font-semibold"
                                            >
                                                Next
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* WORKSPACE 3: OUTBOX CONNECTIONS (SMTP/IMAP) */}
                    {activeTab === 'inboxes' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Sending Mailboxes</h1>
                                <p className="text-xs text-slate-500 mt-1">Connect SMTP email accounts to send automated personalized outreach directly from your local network IP.</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* Connect Box Form */}
                                <div className="md:col-span-1 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                                    <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider">Connect Sending Mailbox</h3>
                                    
                                    <form onSubmit={handleConnectInbox} className="space-y-3 text-xs">
                                        <div className="space-y-1">
                                            <label className="text-slate-500 font-semibold">Sender Email Address</label>
                                            <input 
                                                type="email" 
                                                required
                                                placeholder="e.g. sales@yourdomain.com"
                                                value={newInboxEmail}
                                                onChange={(e) => setNewInboxEmail(e.target.value)}
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-slate-500 font-semibold">SMTP Sending Host</label>
                                            <input 
                                                type="text" 
                                                required
                                                placeholder="e.g. smtp.mailtrap.io"
                                                value={newInboxHost}
                                                onChange={(e) => setNewInboxHost(e.target.value)}
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div className="space-y-1">
                                                <label className="text-slate-500 font-semibold">SMTP Port</label>
                                                <input 
                                                    type="text" 
                                                    required
                                                    placeholder="587"
                                                    value={newInboxPort}
                                                    onChange={(e) => setNewInboxPort(e.target.value)}
                                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:outline-none"
                                                />
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-slate-500 font-semibold">IMAP Host (Opt.)</label>
                                                <input 
                                                    type="text" 
                                                    placeholder="e.g. imap.domain.com"
                                                    value={newInboxImapHost}
                                                    onChange={(e) => setNewInboxImapHost(e.target.value)}
                                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:outline-none"
                                                />
                                            </div>
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-slate-500 font-semibold">SMTP/IMAP Login User</label>
                                            <input 
                                                type="text" 
                                                required
                                                placeholder="sales@yourdomain.com"
                                                value={newInboxUser}
                                                onChange={(e) => setNewInboxUser(e.target.value)}
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-slate-500 font-semibold">SMTP/IMAP Secret Password</label>
                                            <input 
                                                type="password" 
                                                required
                                                placeholder="••••••••••••"
                                                value={newInboxPass}
                                                onChange={(e) => setNewInboxPass(e.target.value)}
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                        
                                        <button 
                                            type="submit"
                                            className="w-full py-2.5 bg-slate-950 text-white rounded-lg font-semibold hover:bg-slate-800 transition-all mt-4"
                                        >
                                            Add sending Mailbox
                                        </button>
                                    </form>
                                </div>

                                {/* Inbox List */}
                                <div className="md:col-span-2 space-y-4">
                                    <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                                        <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider mb-4">Active Sending Connections</h3>
                                        
                                        {inboxes.length === 0 ? (
                                            <div className="py-16 text-center">
                                                <Zap className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                                <h4 className="text-xs font-bold text-slate-900">No sending mailboxes connected yet</h4>
                                                <p className="text-[11px] text-slate-400 mt-1 max-w-xs mx-auto">Connecting a custom domain email account will enable automated outbound message triggers.</p>
                                            </div>
                                        ) : (
                                            <div className="divide-y divide-slate-100">
                                                {inboxes.map(inbox => {
                                                    const isTesting = inboxTestingId === inbox.id;
                                                    
                                                    return (
                                                        <div key={inbox.id} className="py-4 flex items-center justify-between first:pt-0 last:pb-0 gap-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-8 h-8 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-lg flex items-center justify-center shadow-sm">
                                                                    <Mail className="w-4 h-4" />
                                                                </div>
                                                                <div>
                                                                    <span className="font-bold text-slate-900 text-xs block select-all">{inbox.email}</span>
                                                                    <span className="text-[10px] text-slate-400 block font-mono">SMTP: {inbox.smtp_host}:{inbox.smtp_port}</span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div className="flex items-center gap-2">
                                                                <span className={`px-2 py-0.5 rounded-full text-[9px] font-bold border inline-block ${
                                                                    inbox.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                                                                }`}>
                                                                    {inbox.status === 'active' ? 'Operational' : 'Failed Connection'}
                                                                </span>
                                                                
                                                                <button 
                                                                    onClick={() => handleTestInbox(inbox)}
                                                                    disabled={isTesting}
                                                                    className="px-2.5 py-1 text-[10px] font-semibold border border-slate-200 rounded-md hover:bg-slate-50 bg-white transition-colors disabled:opacity-50 flex items-center gap-1"
                                                                >
                                                                    {isTesting ? <RefreshCw className="w-3 h-3 animate-spin" /> : null}
                                                                    Test SMTP
                                                                </button>
                                                                
                                                                <button 
                                                                    onClick={() => handleDeleteInbox(inbox.id)}
                                                                    className="p-1.5 text-slate-400 hover:text-rose-600 rounded-md hover:bg-rose-50 transition-colors"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                    
                                    <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex gap-3 text-xs text-slate-500">
                                        <Info className="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" />
                                        <div className="space-y-1">
                                            <p className="font-bold text-slate-800">Why connect custom SMTP?</p>
                                            <p className="leading-relaxed">Musoftware runs outbound emails **locally** from your network environment. This avoids shared cloud server IP ranges, drastically increasing inbox deliverability rates and ensuring enterprise safety.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* WORKSPACE 4: OUTREACH SEQUENCES (EMAIL TEMPLATES) */}
                    {activeTab === 'outreach' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Outreach Sequences</h1>
                                <p className="text-xs text-slate-500 mt-1">Write automated cold email content sequence templates. Personalize content using lead data variables.</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="md:col-span-1 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                                    <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider mb-4">Select Target Campaign</h3>
                                    
                                    <div className="space-y-2">
                                        {campaigns.length === 0 ? (
                                            <p className="text-xs text-slate-400">Create a campaign first to configure its email sequences.</p>
                                        ) : (
                                            campaigns.map(camp => (
                                                <button
                                                    key={camp.id}
                                                    onClick={() => setSelectedSequenceCampaignId(camp.id)}
                                                    className={`w-full text-left p-3 rounded-xl border text-xs font-semibold flex items-center justify-between transition-all ${
                                                        selectedSequenceCampaignId === camp.id 
                                                            ? 'border-slate-900 bg-slate-950 text-white shadow-sm' 
                                                            : 'border-slate-100 hover:border-slate-200 hover:bg-slate-50 text-slate-700'
                                                    }`}
                                                >
                                                    <div>
                                                        <span className="block truncate">{camp.name}</span>
                                                        <span className={`text-[9px] uppercase font-mono tracking-wider ${selectedSequenceCampaignId === camp.id ? 'text-teal-300' : 'text-slate-400'}`}>
                                                            {camp.keyword}
                                                        </span>
                                                    </div>
                                                    <ChevronRight className="w-4 h-4 shrink-0" />
                                                </button>
                                            ))
                                        )}
                                    </div>
                                </div>

                                <div className="md:col-span-2">
                                    {selectedSequenceCampaignId ? (
                                        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                                            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                                                <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider">Outreach Sequence Step 1</h3>
                                                <span className="px-2 py-0.5 rounded-full text-[9px] bg-slate-50 text-slate-500 font-bold border border-slate-200">Step 1 Outreach</span>
                                            </div>

                                            <form onSubmit={handleSaveSequence} className="space-y-4 text-xs">
                                                <div className="space-y-1">
                                                    <label className="text-slate-500 font-semibold block">Email Subject line</label>
                                                    <input 
                                                        type="text" 
                                                        required
                                                        placeholder="e.g. Quick question about {{company}}"
                                                        value={seqSubject}
                                                        onChange={(e) => setSeqSubject(e.target.value)}
                                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none"
                                                    />
                                                </div>

                                                <div className="space-y-1">
                                                    <div className="flex items-center justify-between mb-1">
                                                        <label className="text-slate-500 font-semibold">Message Body</label>
                                                        <span className="text-[10px] text-slate-400 font-mono">Variables: {"{{name}}"}, {"{{company}}"}, {"{{title}}"}</span>
                                                    </div>
                                                    <textarea 
                                                        required
                                                        rows={8}
                                                        placeholder={`Hi {{name}},\n\nSaw you are the {{title}} at {{company}}.\n\nWould love to discuss your local scraping setup.\n\nBest,\nSales Team`}
                                                        value={seqBody}
                                                        onChange={(e) => setSeqBody(e.target.value)}
                                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:bg-white focus:outline-none font-sans"
                                                    />
                                                </div>

                                                <div className="space-y-1 max-w-xs">
                                                    <label className="text-slate-500 font-semibold block">Time delay before sending</label>
                                                    <div className="flex items-center gap-2">
                                                        <input 
                                                            type="number" 
                                                            min={1} 
                                                            value={seqDelay}
                                                            onChange={(e) => setSeqDelay(parseInt(e.target.value) || 1)}
                                                            className="w-20 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 focus:bg-white focus:outline-none text-center font-bold"
                                                        />
                                                        <span className="text-slate-500">days after trigger</span>
                                                    </div>
                                                </div>

                                                <div className="pt-3 border-t border-slate-100 flex justify-end">
                                                    <button 
                                                        type="submit"
                                                        disabled={savingSequence}
                                                        className="px-5 py-2 bg-slate-950 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition-all active:scale-95 disabled:opacity-50"
                                                    >
                                                        {savingSequence ? 'Saving Template...' : 'Save Outreach sequence'}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    ) : (
                                        <div className="bg-white border border-slate-200 rounded-2xl p-16 text-center shadow-sm">
                                            <FileText className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                            <h4 className="text-xs font-bold text-slate-900">No Target Campaign Selected</h4>
                                            <p className="text-[11px] text-slate-400 mt-1 max-w-xs mx-auto">Select an active search campaign from the left sidebar list to edit its cold message sequences.</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* WORKSPACE 5: LINKED PROFILES (LINKEDIN SESSION KEY) */}
                    {activeTab === 'linked-profiles' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">Linked Accounts</h1>
                                <p className="text-xs text-slate-500 mt-1">Connect your accounts securely to power local deep crawling engines.</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="md:col-span-2 space-y-6">
                                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 bg-slate-100 text-slate-900 rounded-xl flex items-center justify-center font-bold text-sm">
                                                in
                                            </div>
                                            <div>
                                                <h3 className="font-bold text-slate-900 text-xs uppercase tracking-wider">LinkedIn Session Authenticator</h3>
                                                <span className="text-[10px] text-slate-400 block mt-0.5">Powers deep crawler to harvest business roles privately.</span>
                                            </div>
                                        </div>

                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
                                            <div>
                                                <span className="text-slate-500 font-semibold block">Authentication Status:</span>
                                                <span className={`font-bold mt-0.5 block text-xs ${linkedInSession.hasSession ? 'text-emerald-600' : 'text-slate-500'}`}>
                                                    {linkedInSession.hasSession ? 'Session Key Linked (Active)' : 'Not Connected'}
                                                </span>
                                            </div>
                                            {linkedInSession.validatedAt && (
                                                <div className="text-right">
                                                    <span className="text-slate-400 block text-[10px]">Last validated:</span>
                                                    <span className="font-mono text-slate-500 text-[10px] block mt-0.5">{new Date(linkedInSession.validatedAt).toLocaleDateString()}</span>
                                                </div>
                                            )}
                                        </div>

                                        <form onSubmit={handleSaveLinkedInCookie} className="space-y-4 text-xs">
                                            <div className="space-y-1">
                                                <label className="text-slate-500 font-semibold block">LinkedIn Connection Key (`li_at` value)</label>
                                                <input 
                                                    type="password" 
                                                    required
                                                    placeholder="Paste the session value e.g. AQEDAT..."
                                                    value={sessionCookieInput}
                                                    onChange={(e) => setSessionCookieInput(e.target.value)}
                                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none"
                                                />
                                                <p className="text-[10px] text-slate-400 mt-1 leading-relaxed">Your session token is saved securely in your **local SQLite database** only. It is never transmitted to our cloud backend servers.</p>
                                            </div>

                                            <div className="flex justify-end pt-2 border-t border-slate-100">
                                                <button 
                                                    type="submit"
                                                    disabled={savingCookie}
                                                    className="px-5 py-2 bg-slate-950 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition-all active:scale-95 disabled:opacity-50"
                                                >
                                                    {savingCookie ? 'Linking Token...' : 'Link Connection Key'}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div className="md:col-span-1 space-y-4 text-xs">
                                    <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                                        <h4 className="font-bold text-slate-900 uppercase tracking-wider text-[10px]">How to locate session key</h4>
                                        <p className="text-slate-500 leading-relaxed">1. Open Chrome or Edge and go to LinkedIn.com (log in).</p>
                                        <p className="text-slate-500 leading-relaxed">2. Right-click anywhere and choose **Inspect** or press F12.</p>
                                        <p className="text-slate-500 leading-relaxed">3. Go to the **Application** tab (Chrome) or **Storage** (Firefox).</p>
                                        <p className="text-slate-500 leading-relaxed">4. Click **Cookies** in the left sidebar, then click `https://www.linkedin.com`.</p>
                                        <p className="text-slate-500 leading-relaxed">5. Search for the cookie named **`li_at`** and copy its entire text Value.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </main>
            </div>

            {/* NEW CAMPAIGN DIALOG MODAL */}
            {showNewCampaignModal && (
                <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200">
                    <div className="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden border border-slate-200/80 animate-in zoom-in-95 duration-200">
                        <div className="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <h3 className="font-bold text-slate-900 text-sm tracking-tight">New Search Campaign</h3>
                            <button 
                                onClick={() => setShowNewCampaignModal(false)}
                                className="text-slate-400 hover:text-slate-900 font-bold text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleCreateCampaign} className="p-6 space-y-4 text-xs">
                            <div className="space-y-1">
                                <label className="text-slate-500 font-semibold block">Campaign Name</label>
                                <input 
                                    type="text" 
                                    required
                                    placeholder="e.g. US Tech Founders"
                                    value={newCampName}
                                    onChange={(e) => setNewCampName(e.target.value)}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none"
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="text-slate-500 font-semibold block">Target Search Keyword (Job title or company domain)</label>
                                <input 
                                    type="text" 
                                    required
                                    placeholder="e.g. Chief Executive Officer"
                                    value={newCampKeyword}
                                    onChange={(e) => setNewCampKeyword(e.target.value)}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-slate-500 font-semibold block">Country Filter</label>
                                    <input 
                                        type="text" 
                                        placeholder="USA"
                                        value={newCampCountry}
                                        onChange={(e) => setNewCampCountry(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-slate-500 font-semibold block">City Filter (Optional)</label>
                                    <input 
                                        type="text" 
                                        placeholder="San Francisco"
                                        value={newCampCity}
                                        onChange={(e) => setNewCampCity(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-slate-500 font-semibold block">Lead Search Sources</label>
                                <div className="flex items-center gap-3">
                                    <button 
                                        type="button"
                                        onClick={() => toggleSource('linkedin')}
                                        className={`px-4 py-2 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition-all ${
                                            newCampSources.includes('linkedin') 
                                                ? 'bg-slate-950 text-white border-slate-900 shadow-sm' 
                                                : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-slate-350'
                                        }`}
                                    >
                                        <CheckCircle className={`w-3.5 h-3.5 ${newCampSources.includes('linkedin') ? 'text-teal-400' : 'opacity-30'}`} />
                                        LinkedIn Profile Search
                                    </button>
                                    <button 
                                        type="button"
                                        onClick={() => toggleSource('google_maps')}
                                        className={`px-4 py-2 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition-all ${
                                            newCampSources.includes('google_maps') 
                                                ? 'bg-slate-950 text-white border-slate-900 shadow-sm' 
                                                : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-slate-350'
                                        }`}
                                    >
                                        <CheckCircle className={`w-3.5 h-3.5 ${newCampSources.includes('google_maps') ? 'text-teal-400' : 'opacity-30'}`} />
                                        Google Maps Places
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-1 max-w-[200px]">
                                <label className="text-slate-500 font-semibold block">Daily Speed Limit (leads)</label>
                                <input 
                                    type="number" 
                                    min={10} 
                                    max={1000}
                                    value={newCampLimit}
                                    onChange={(e) => setNewCampLimit(parseInt(e.target.value) || 100)}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs focus:bg-white focus:outline-none text-center font-bold"
                                />
                            </div>

                            <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-3.5">
                                <button 
                                    type="button"
                                    onClick={() => setShowNewCampaignModal(false)}
                                    className="px-4 py-2 text-slate-500 hover:text-slate-800 font-semibold"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    className="px-5 py-2 bg-slate-950 text-white rounded-xl font-semibold hover:bg-slate-850 transition-all active:scale-95"
                                >
                                    Save Draft Campaign
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
