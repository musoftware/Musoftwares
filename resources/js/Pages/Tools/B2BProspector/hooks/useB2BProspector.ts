import { useState, useEffect, useCallback } from 'react';
import { 
    B2BCampaign, B2BLead, B2BInbox, B2BSequence, 
    LinkedInSession, RealtimeLog, B2BCampaignStats 
} from '../types/b2b.types';

export function useB2BProspectorState(agentConnected: boolean, callRPC: any) {
    const [realtimeLogs, setRealtimeLogs] = useState<RealtimeLog[]>([]);

    const addRealtimeLog = useCallback((message: string) => {
        setRealtimeLogs(prev => [{ 
            id: Math.random().toString(), 
            message, 
            time: new Date().toLocaleTimeString() 
        }, ...prev].slice(0, 50));
    }, []);

    return {
        realtimeLogs,
        addRealtimeLog
    };
}

export function useProspectingCampaigns(agentConnected: boolean, callRPC: any, addRealtimeLog: (msg: string) => void) {
    const [campaigns, setCampaigns] = useState<B2BCampaign[]>([]);
    const [runningCampaignIds, setRunningCampaignIds] = useState<string[]>([]);
    const [campaignStats, setCampaignStats] = useState<Record<string, B2BCampaignStats>>({});
    
    // New Campaign Form State
    const [showNewCampaignModal, setShowNewCampaignModal] = useState(false);
    const [newCampName, setNewCampName] = useState('');
    const [newCampKeyword, setNewCampKeyword] = useState('');
    const [newCampCountry, setNewCampCountry] = useState('USA');
    const [newCampCity, setNewCampCity] = useState('');
    const [newCampSources, setNewCampSources] = useState<string[]>(['linkedin']);
    const [newCampLimit, setNewCampLimit] = useState(100);

    const fetchCampaignStats = useCallback(async (campaignId: string) => {
        try {
            const stats: any = await callRPC('prospecting.campaign.stats', { campaignId });
            setCampaignStats(prev => ({ ...prev, [campaignId]: stats }));
        } catch (err) {
            console.error('Failed to load stats:', err);
        }
    }, [callRPC]);

    const fetchCampaignsList = useCallback(async () => {
        try {
            const list: B2BCampaign[] = await callRPC('prospecting.campaigns.list');
            setCampaigns(list || []);
            
            const running = (list || []).filter(c => c.status === 'running').map(c => c.id);
            setRunningCampaignIds(running);

            (list || []).forEach(c => {
                fetchCampaignStats(c.id);
            });
        } catch (err) {
            console.error('Failed to load campaigns:', err);
        }
    }, [callRPC, fetchCampaignStats]);

    useEffect(() => {
        if (agentConnected) {
            fetchCampaignsList();
        }
    }, [agentConnected, fetchCampaignsList]);

    const handleCreateCampaign = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newCampName.trim() || !newCampKeyword.trim()) return;

        try {
            await callRPC('prospecting.campaign.create', {
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

    const toggleSource = (source: string) => {
        setNewCampSources(prev => 
            prev.includes(source) ? prev.filter(s => s !== source) : [...prev, source]
        );
    };

    return {
        campaigns, setCampaigns,
        runningCampaignIds, setRunningCampaignIds,
        campaignStats,
        fetchCampaignsList, fetchCampaignStats,
        
        showNewCampaignModal, setShowNewCampaignModal,
        newCampName, setNewCampName,
        newCampKeyword, setNewCampKeyword,
        newCampCountry, setNewCampCountry,
        newCampCity, setNewCampCity,
        newCampSources, setNewCampSources,
        newCampLimit, setNewCampLimit,
        toggleSource,
        
        handleCreateCampaign,
        handleStartCampaign,
        handleStopCampaign
    };
}

export function useProspectingLeads(agentConnected: boolean, callRPC: any, addRealtimeLog: (msg: string) => void) {
    const [leads, setLeads] = useState<B2BLead[]>([]);
    const [totalLeads, setTotalLeads] = useState(0);
    const [leadsOffset, setLeadsOffset] = useState(0);
    const [leadsLimit] = useState(25);
    const [leadsSearch, setLeadsSearch] = useState('');
    const [leadsEmailFilter, setLeadsEmailFilter] = useState('');
    const [selectedCampaignId, setSelectedCampaignId] = useState<string>('');
    
    // CSV export state
    const [exportingJobId, setExportingJobId] = useState<string | null>(null);
    const [exportProgress, setExportProgress] = useState<number | null>(null);
    const [exportFilePath, setExportFilePath] = useState<string | null>(null);

    const fetchLeadsList = useCallback(async () => {
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
    }, [callRPC, selectedCampaignId, leadsLimit, leadsOffset, leadsSearch, leadsEmailFilter]);

    useEffect(() => {
        if (agentConnected) {
            fetchLeadsList();
        }
    }, [agentConnected, fetchLeadsList]);

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

    return {
        leads, setLeads,
        totalLeads, setTotalLeads,
        leadsOffset, setLeadsOffset,
        leadsLimit,
        leadsSearch, setLeadsSearch,
        leadsEmailFilter, setLeadsEmailFilter,
        selectedCampaignId, setSelectedCampaignId,
        
        exportingJobId, setExportingJobId,
        exportProgress, setExportProgress,
        exportFilePath, setExportFilePath,
        
        fetchLeadsList,
        handleExportLeads
    };
}

export function useProspectingInboxes(agentConnected: boolean, callRPC: any, addRealtimeLog: (msg: string) => void) {
    const [inboxes, setInboxes] = useState<B2BInbox[]>([]);
    const [newInboxEmail, setNewInboxEmail] = useState('');
    const [newInboxHost, setNewInboxHost] = useState('');
    const [newInboxPort, setNewInboxPort] = useState('587');
    const [newInboxUser, setNewInboxUser] = useState('');
    const [newInboxPass, setNewInboxPass] = useState('');
    const [newInboxImapHost, setNewInboxImapHost] = useState('');
    const [newInboxImapPort, setNewInboxImapPort] = useState('993');
    const [inboxTestingId, setInboxTestingId] = useState<string | null>(null);

    const fetchInboxesList = useCallback(async () => {
        try {
            const list: B2BInbox[] = await callRPC('prospecting.inboxes.list');
            setInboxes(list || []);
        } catch (err) {
            console.error('Failed to load inboxes:', err);
        }
    }, [callRPC]);

    useEffect(() => {
        if (agentConnected) {
            fetchInboxesList();
        }
    }, [agentConnected, fetchInboxesList]);

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

    const handleTestInbox = async (inbox: B2BInbox) => {
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

    return {
        inboxes, setInboxes,
        newInboxEmail, setNewInboxEmail,
        newInboxHost, setNewInboxHost,
        newInboxPort, setNewInboxPort,
        newInboxUser, setNewInboxUser,
        newInboxPass, setNewInboxPass,
        newInboxImapHost, setNewInboxImapHost,
        newInboxImapPort, setNewInboxImapPort,
        inboxTestingId, setInboxTestingId,
        fetchInboxesList,
        handleConnectInbox,
        handleTestInbox,
        handleDeleteInbox
    };
}

export function useProspectingSequences(agentConnected: boolean, callRPC: any, addRealtimeLog: (msg: string) => void) {
    const [selectedSequenceCampaignId, setSelectedSequenceCampaignId] = useState('');
    const [sequences, setSequences] = useState<B2BSequence[]>([]);
    const [seqSubject, setSeqSubject] = useState('');
    const [seqBody, setSeqBody] = useState('');
    const [seqDelay, setSeqDelay] = useState(1);
    const [savingSequence, setSavingSequence] = useState(false);

    const fetchSequencesList = useCallback(async (campaignId: string) => {
        try {
            const list: B2BSequence[] = await callRPC('prospecting.templates.list', { campaignId });
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
    }, [callRPC]);

    useEffect(() => {
        if (agentConnected && selectedSequenceCampaignId) {
            fetchSequencesList(selectedSequenceCampaignId);
        }
    }, [agentConnected, selectedSequenceCampaignId, fetchSequencesList]);

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

    return {
        selectedSequenceCampaignId, setSelectedSequenceCampaignId,
        sequences, setSequences,
        seqSubject, setSeqSubject,
        seqBody, setSeqBody,
        seqDelay, setSeqDelay,
        savingSequence, setSavingSequence,
        fetchSequencesList,
        handleSaveSequence
    };
}

export function useProspectingLinkedIn(agentConnected: boolean, callRPC: any, addRealtimeLog: (msg: string) => void) {
    const [linkedInSession, setLinkedInSession] = useState<LinkedInSession>({ hasSession: false, isValid: false, validatedAt: null });
    const [sessionCookieInput, setSessionCookieInput] = useState('');
    const [savingCookie, setSavingCookie] = useState(false);

    const fetchLinkedInSession = useCallback(async () => {
        try {
            const session: LinkedInSession = await callRPC('prospecting.auth.session.get');
            setLinkedInSession(session);
        } catch (err) {
            console.error('Failed to load LinkedIn session:', err);
        }
    }, [callRPC]);

    useEffect(() => {
        if (agentConnected) {
            fetchLinkedInSession();
            addRealtimeLog('Connected to Local Privacy Vault. System Online.');
        }
    }, [agentConnected, fetchLinkedInSession, addRealtimeLog]);

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

    return {
        linkedInSession, setLinkedInSession,
        sessionCookieInput, setSessionCookieInput,
        savingCookie, setSavingCookie,
        fetchLinkedInSession,
        handleSaveLinkedInCookie
    };
}
