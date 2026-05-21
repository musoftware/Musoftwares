import React, { useState, useEffect, useRef } from 'react';
import ToolShellLayout from './WhatsApp/Layouts/ToolShellLayout';
import Sidebar from './WhatsApp/Components/Sidebar';
import AccountsWorkspace from './WhatsApp/Workspaces/AccountsWorkspace';
import CampaignWorkspace from './WhatsApp/Workspaces/CampaignWorkspace';
import GroupsWorkspace from './WhatsApp/Workspaces/GroupsWorkspace';
import GroupCampaignWorkspace from './WhatsApp/Workspaces/GroupCampaignWorkspace';
import TemplatesWorkspace from './WhatsApp/Workspaces/TemplatesWorkspace';
import CampaignsListWorkspace from './WhatsApp/Workspaces/CampaignsListWorkspace';
import CampaignReportWorkspace from './WhatsApp/Workspaces/CampaignReportWorkspace';

type TabId = 'accounts' | 'campaign' | 'groups' | 'group-campaign' | 'history' | 'report' | 'templates';

const translations = {
    en: {
        title: "WhatsApp Sender",
        subtitle: "Campaign Engine & Automation",
        language: "العربية",
        connected: "Runtime Connected",
        disconnected: "Runtime Offline",
        tabs: {
            accounts: "WA Accounts",
            campaign: "New Campaign",
            deliverability: "Live Activity",
            scorecard: "Report"
        },
        accounts: {
            qrRefreshes: "Refreshes in",
            qrPendingBadge: "Awaiting Scan",
            qrTitle: "Link Device",
            qrInstructions: "Open WhatsApp → Settings → Linked Devices → Scan this QR code.",
            addAccount: "Connect New Account",
            accountId: "Account Identifier",
            accountIdPlaceholder: "e.g. Sales Team 1",
            proxy: "Proxy URL (Optional)",
            proxyPlaceholder: "http://user:pass@host:port",
            headless: "Run in Background",
            connect: "Connect Account",
            activeSessions: "Active Connections",
            description: "Manage connected devices and proxies.",
            noAccounts: "No accounts connected yet. Link an account to start.",
            disconnect: "Disconnect Account",
            trustScore: "Health Score"
        },
        campaign: {
            contactsLabel: "Contacts List",
            parsedContacts: "Valid Targets",
            contactsPlaceholder: "Paste numbers or CSV:\nphone,name,company\n966501234567,Ahmed,Aramco\n971501234567,Fatima,",
            messageLabel: "Message Content",
            personalizationTags: "Variables:",
            messagePlaceholder: "Hi {name}! Welcome to our service.",
            attachmentLabel: "Attachments",
            attachmentModes: { none: 'None', media: 'Media Link', vcard: 'Contact Card' },
            attachmentUrl: "Public Media URL",
            attachmentUrlPlaceholder: "https://...",
            vcardName: "Contact Name",
            vcardPhone: "Phone Number",
            vcardCompany: "Company Name",
            safetyLabel: "Delivery Speed & Safety",
            typingSpeed: "Typing Speed",
            typoChance: "Typo Correction Rate",
            useSynonyms: "Use AI Synonyms",
            bellCurve: "Humanized Bell Curve Delays",
            trackDelivery: "Track Delivery Status",
            stopOnBlock: "Emergency Stop on Block",
            maxBlockRate: "Max Block Rate Threshold",
            launchButton: "Create & Start Campaign",
            selectAccountError: "Please select a connected WhatsApp account.",
            noContactsError: "Please provide at least one valid contact.",
            emptyMessageError: "Please provide a message or attachment."
        },
        deliverability: {
            campaignProgress: "Campaign Progress",
            description: "Real-time message delivery tracking.",
            stopCampaign: "Stop Campaign",
            colPhone: "Phone",
            colName: "Name",
            colCompany: "Company",
            colStatus: "Status",
            statusRead: "Read",
            statusDelivered: "Delivered",
            statusSent: "Sent",
            statusReplied: "Replied",
            statusBlocked: "Blocked",
            statusFailed: "Failed",
            statusPending: "Pending"
        },
        scorecard: {
            totalProcessed: "Total Targets",
            sentSuccessfully: "Sent",
            failedOrSkipped: "Failed",
            blocksReceived: "Blocked",
            title: "Delivery Health",
            description: "Post-campaign diagnostic.",
            trustGrade: "Health Grade",
            scoreExplanation: "Based on delivery rates.",
            blockRate: "Block Rate",
            banProbability: "Account Risk",
            warmingRecommendation: "Recommendation",
            grades: { excellent: "Excellent", good: "Good", warning: "Warning", danger: "Critical" }
        }
    },
    ar: {
        title: "مرسل الواتساب",
        subtitle: "محرك الحملات والأتمتة",
        language: "English",
        connected: "النظام متصل",
        disconnected: "النظام غير متصل",
        tabs: {
            accounts: "حسابات الواتساب",
            campaign: "حملة جديدة",
            deliverability: "النشاط المباشر",
            scorecard: "التقرير"
        },
        accounts: {
            qrRefreshes: "تحديث خلال",
            qrPendingBadge: "بانتظار المسح",
            qrTitle: "ربط جهاز",
            qrInstructions: "افتح الواتساب → الأجهزة المرتبطة → امسح الرمز.",
            addAccount: "ربط حساب جديد",
            accountId: "اسم الحساب",
            accountIdPlaceholder: "مثال: فريق المبيعات",
            proxy: "بروكسي (اختياري)",
            proxyPlaceholder: "http://user:pass@host:port",
            headless: "تشغيل في الخلفية",
            connect: "ربط الحساب",
            activeSessions: "الاتصالات النشطة",
            description: "إدارة الأجهزة المتصلة.",
            noAccounts: "لا توجد حسابات مرتبطة.",
            disconnect: "قطع الاتصال",
            trustScore: "درجة الصحة"
        },
        campaign: {
            contactsLabel: "قائمة الأرقام",
            parsedContacts: "جهات اتصال صحيحة",
            contactsPlaceholder: "phone,name,company\n966501234567,أحمد,أرامكو",
            messageLabel: "محتوى الرسالة",
            personalizationTags: "المتغيرات:",
            messagePlaceholder: "مرحباً {name}!",
            attachmentLabel: "المرفقات",
            attachmentModes: { none: 'بدون', media: 'رابط وسائط', vcard: 'بطاقة اتصال' },
            attachmentUrl: "رابط الوسائط",
            attachmentUrlPlaceholder: "https://...",
            vcardName: "الاسم",
            vcardPhone: "رقم الهاتف",
            vcardCompany: "الشركة",
            safetyLabel: "سرعة وأمان الإرسال",
            typingSpeed: "سرعة الكتابة",
            typoChance: "نسبة تصحيح الأخطاء",
            useSynonyms: "مرادفات الذكاء الاصطناعي",
            bellCurve: "تأخيرات منحنى الجرس",
            trackDelivery: "تتبع حالة التسليم",
            stopOnBlock: "إيقاف طوارئ عند الحظر",
            maxBlockRate: "الحد الأقصى لنسبة الحظر",
            launchButton: "إنشاء وبدء الحملة",
            selectAccountError: "يرجى تحديد حساب متصل.",
            noContactsError: "يرجى إدخال جهة اتصال واحدة على الأقل.",
            emptyMessageError: "يرجى إدخال رسالة أو مرفق."
        },
        deliverability: {
            campaignProgress: "تقدم الحملة",
            description: "تتبع تسليم الرسائل.",
            stopCampaign: "إيقاف الحملة",
            colPhone: "الهاتف",
            colName: "الاسم",
            colCompany: "الشركة",
            colStatus: "الحالة",
            statusRead: "تمت القراءة",
            statusDelivered: "تم التسليم",
            statusSent: "تم الإرسال",
            statusReplied: "تم الرد",
            statusBlocked: "محظور",
            statusFailed: "فشل",
            statusPending: "قيد الانتظار"
        },
        scorecard: {
            totalProcessed: "إجمالي المستهدفين",
            sentSuccessfully: "تم الإرسال",
            failedOrSkipped: "فشل",
            blocksReceived: "محظور",
            title: "صحة الإرسال",
            description: "تشخيص ما بعد الحملة.",
            trustGrade: "درجة الصحة",
            scoreExplanation: "بناءً على معدلات التسليم.",
            blockRate: "معدل الحظر",
            banProbability: "خطر الحساب",
            warmingRecommendation: "التوصية",
            grades: { excellent: "ممتاز", good: "جيد", warning: "تحذير", danger: "حرج" }
        }
    }
};

// ── Runtime WebSocket Hook ────────────────────────────────────────────────────

function useRuntimeWS(pluginSlug: string, onBroadcast?: ((event: string, data: any) => void) | null) {
    const [ws, setWs]           = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pending = useRef<Map<string, { resolve: Function; reject: Function }>>(new Map());
    const onBroadcastRef = useRef<((event: string, data: any) => void) | null>(null);
    onBroadcastRef.current = onBroadcast || null;

    useEffect(() => {
        const host   = typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
        const socket = new WebSocket(`ws://${host}:18401/ws`);

        socket.onopen  = () => setConnected(true);
        socket.onclose = () => { setConnected(false); };

        socket.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                // RPC response/error routing
                if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                    const r = pending.current.get(msg.requestId);
                    if (r) {
                        if (msg.type === 'plugin_rpc_error') r.reject(new Error(msg.payload?.error || 'RPC Error'));
                        else r.resolve(msg.payload);
                        pending.current.delete(msg.requestId);
                    }
                }
                // Broadcast events
                if (msg.event && onBroadcastRef.current) {
                    onBroadcastRef.current(msg.event, msg.data);
                }
            } catch (_) {}
        };

        setWs(socket);
        return () => socket.close();
    }, []);

    const callRPC = async (action: string, data: any = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) throw new Error('Not connected to runtime — is the Musoftware Runtime running?');
        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(2, 9);
            pending.current.set(requestId, { resolve, reject });
            ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: pluginSlug, action, data } }));
            setTimeout(() => {
                const r = pending.current.get(requestId);
                if (r) { r.reject(new Error('RPC timeout')); pending.current.delete(requestId); }
            }, 30_000);
        });
    };

    return { connected, callRPC };
}

// ── Main Component ────────────────────────────────────────────────────────────

export default function WhatsAppSenderRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [locale, setLocale]   = useState<'en' | 'ar'>('en');
    
    // Initialize state from URL
    const [activeTab, setActiveTab] = useState<TabId>(() => {
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            const path = params.get('path') as TabId;
            if (path && ['accounts', 'campaign', 'groups', 'group-campaign', 'history', 'report', 'templates'].includes(path)) {
                return path;
            }
        }
        return 'accounts';
    });
    
    const [reportCampaignId, setReportCampaignId] = useState<string | null>(() => {
        if (typeof window !== 'undefined') return new URLSearchParams(window.location.search).get('reportId');
        return null;
    });
    
    const [reportCampaignName, setReportCampaignName] = useState<string | null>(() => {
        if (typeof window !== 'undefined') return new URLSearchParams(window.location.search).get('reportName');
        return null;
    });

    // Sync state to URL
    useEffect(() => {
        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);
            url.searchParams.set('path', activeTab);
            
            if (activeTab === 'report' && reportCampaignId) {
                url.searchParams.set('reportId', reportCampaignId);
                if (reportCampaignName) {
                    url.searchParams.set('reportName', reportCampaignName);
                }
            } else {
                url.searchParams.delete('reportId');
                url.searchParams.delete('reportName');
            }
            
            window.history.replaceState({}, '', url.toString());
        }
    }, [activeTab, reportCampaignId, reportCampaignName]);

    // Sessions
    const [sessions, setSessions]               = useState<any[]>([]);
    const [newAccountId, setNewAccountId]       = useState('');
    const [newProxy, setNewProxy]               = useState('');
    const [newHeadless, setNewHeadless]         = useState(true);
    const [activeQR, setActiveQR]               = useState<string | null>(null);
    const [qrSessionId, setQrSessionId]         = useState<string | null>(null);
    const [qrCountdown, setQrCountdown]         = useState(20);

    // Campaign compose state
    const [campaignName, setCampaignName]       = useState('');
    const [selectedAccount, setSelectedAccount] = useState('');
    const [contactsText, setContactsText]       = useState('');
    
    // Simplified Campaign: Only use templates
    const [templates, setTemplates]             = useState<any[]>([]);
    const [selectedTemplateId, setSelectedTemplateId] = useState('');
    
    const [minWpm, setMinWpm]                   = useState(45);
    const [maxWpm, setMaxWpm]                   = useState(75);
    const [typoChance, setTypoChance]           = useState(5);
    const [useSynonyms, setUseSynonyms]         = useState(true);
    const [bellCurve, setBellCurve]             = useState(true);
    const [trackDelivery, setTrackDelivery]     = useState(true);
    const [stopOnBlock, setStopOnBlock]         = useState(true);
    const [maxBlockRate, setMaxBlockRate]       = useState(5);

    // Campaign state
    const [isCampaignRunning, setIsCampaignRunning] = useState(false);
    const [activeCampaigns, setActiveCampaigns]     = useState<Record<string, any>>({});
    const runningCampaignsCount = Object.keys(activeCampaigns).length;

    const t = translations[locale];

    // ── Broadcast event handler ───────────────────────────────────────────────

    const onBroadcast = (event: string, data: any) => {
        if (event.startsWith('whatsapp.session.')) {
            const { accountId } = data;
            if (!accountId) return;

            setSessions(prev => {
                const stateMap: Record<string, string> = {
                    'whatsapp.session.connecting':   'connecting',
                    'whatsapp.session.connected':    'connected',
                    'whatsapp.session.disconnected': 'disconnected',
                    'whatsapp.session.error':        'error',
                };
                const newState = stateMap[event];

                if (event === 'whatsapp.session.qr_updated') {
                    setActiveQR(data.qr);
                    setQrSessionId(accountId);
                    return prev.map(s => s.accountId === accountId ? { ...s, state: 'qr_pending' } : s);
                }
                if (event === 'whatsapp.session.connected' && qrSessionId === accountId) {
                    setActiveQR(null);
                    setQrSessionId(null);
                }
                if (newState) {
                    const match = prev.find(s => s.accountId === accountId);
                    if (match) return prev.map(s => s.accountId === accountId ? { ...s, state: newState } : s);
                    return [...prev, { accountId, state: newState, health: { trustScore: 50 } }];
                }
                return prev;
            });
        }

        if (event === 'whatsapp.campaign.progress') {
            setActiveCampaigns(prev => ({
                ...prev,
                [data.campaignId]: { sent: data.sent, failed: data.failed, total: data.total, percent: data.percent }
            }));
        }

        if (event === 'whatsapp.campaign.completed' || event === 'whatsapp.campaign.stopped' || event === 'whatsapp.campaign.failed') {
            setActiveCampaigns(prev => {
                const next = { ...prev };
                delete next[data.campaignId];
                return next;
            });
            setIsCampaignRunning(false);
        }
    };

    const { connected: daemonConnected, callRPC } = useRuntimeWS(pluginSlug || 'whatsapp-sender', onBroadcast);

    // ── Session & Template management ────────────────────────────────────────────────────

    useEffect(() => { 
        if (daemonConnected) {
            fetchSessions();
            fetchTemplates();
        }
    }, [daemonConnected]);

    const fetchTemplates = async () => {
        try {
            const res: any = await callRPC('getTemplates');
            setTemplates(res.templates || []);
        } catch (err) { console.error('fetchTemplates failed:', err); }
    };

    useEffect(() => {
        let timer: any;
        if (activeQR) {
            timer = setInterval(() => setQrCountdown(p => p <= 1 ? 20 : p - 1), 1000);
        } else {
            setQrCountdown(20);
        }
        return () => clearInterval(timer);
    }, [activeQR]);

    const fetchSessions = async () => {
        try {
            const res: any = await callRPC('getSessions');
            setSessions(res.sessions || []);
            const firstConnected = res.sessions?.find((s: any) => s.state === 'connected');
            if (firstConnected && !selectedAccount) setSelectedAccount(firstConnected.accountId);
        } catch (err) { console.error('fetchSessions failed:', err); }
    };

    const handleConnectSession = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newAccountId.trim()) return;
        handleReconnectSession(newAccountId.trim(), newProxy.trim() || null, newHeadless);
        setNewAccountId('');
        setNewProxy('');
    };

    const handleReconnectSession = async (accountId: string, proxy: string | null = null, headless: boolean = true) => {
        try {
            await callRPC('connectSession', { accountId, proxy, headless });
            setSessions(prev => [
                ...prev.filter(s => s.accountId !== accountId),
                { accountId, state: 'connecting', health: { trustScore: 50 } }
            ]);
        } catch (err: any) {
            alert(`Connect Error: ${err.message}`);
        }
    };

    const handleDisconnectSession = async (accountId: string) => {
        try {
            await callRPC('disconnectSession', { accountId });
            fetchSessions();
            if (qrSessionId === accountId) { setActiveQR(null); setQrSessionId(null); }
        } catch (err: any) {
            alert(`Disconnect Error: ${err.message}`);
        }
    };

    // ── Campaign compose ──────────────────────────────────────────────────────

    const parseContacts = () => {
        if (!contactsText.trim()) return [];
        return contactsText.split('\n').map(line => {
            const parts = line.split(',');
            return { phone: parts[0]?.trim().replace(/[^0-9+]/g, '') || '', name: parts[1]?.trim() || '', company: parts[2]?.trim() || '' };
        }).filter(c => c.phone.length >= 7);
    };

    const insertTag = (tag: string) => setMessageText(prev => prev + tag);

    const handleLaunchCampaign = async () => {
        const parsed = parseContacts();
        if (!selectedAccount) return alert(t.campaign.selectAccountError);
        if (parsed.length === 0)  return alert(t.campaign.noContactsError);
        if (!selectedTemplateId) return alert("Please select a template to use for this campaign.");

        const tpl = templates.find(t => t.id === selectedTemplateId);
        if (!tpl) return;

        setIsCampaignRunning(true);
        try {
            // Step 1: Create campaign record via RPC
            const res: any = await callRPC('createCampaign', {
                name:         campaignName.trim() || `Campaign ${new Date().toLocaleDateString()}`,
                accountId:    selectedAccount,
                contactsJson: parsed,
                message:      tpl.message,
                mediaUrl:     tpl.media_url,
                mediaType:    tpl.media_type,
                type:         'bulk',
                delayMs:      4000
            });

            // Step 2: Start it
            await callRPC('startCampaign', { campaignId: res.campaignId, accountId: selectedAccount });

            // Switch to history tab to monitor
            setActiveTab('history');
        } catch (err: any) {
            alert(`Campaign Error: ${err.message}`);
            setIsCampaignRunning(false);
        }
    };

    // ── Template → Campaign autofill ──────────────────────────────────────────

    const handleUseTemplate = (template: any) => {
        setSelectedTemplateId(template.id);
        setActiveTab('campaign');
    };

    const getParsedRecipients = parseContacts();

    return (
        <ToolShellLayout
            locale={locale}
            sidebar={
                <Sidebar
                    activeTab={activeTab}
                    setActiveTab={setActiveTab}
                    locale={locale}
                    setLocale={setLocale}
                    daemonConnected={daemonConnected}
                    isCampaignRunning={isCampaignRunning}
                    hasResult={false}
                    t={t}
                    runningCampaignsCount={runningCampaignsCount}
                />
            }
        >
            {activeTab === 'accounts' && (
                <AccountsWorkspace
                    t={t}
                    activeQR={activeQR}
                    qrCountdown={qrCountdown}
                    qrSessionId={qrSessionId}
                    newAccountId={newAccountId}
                    setNewAccountId={setNewAccountId}
                    newProxy={newProxy}
                    setNewProxy={setNewProxy}
                    newHeadless={newHeadless}
                    setNewHeadless={setNewHeadless}
                    daemonConnected={daemonConnected}
                    handleConnectSession={handleConnectSession}
                    handleReconnectSession={handleReconnectSession}
                    sessions={sessions}
                    fetchSessions={fetchSessions}
                    handleDisconnectSession={handleDisconnectSession}
                />
            )}

            {activeTab === 'campaign' && (
                <CampaignWorkspace
                    t={t}
                    contactsText={contactsText}
                    setContactsText={setContactsText}
                    getParsedRecipients={getParsedRecipients}
                    
                    templates={templates}
                    selectedTemplateId={selectedTemplateId}
                    setSelectedTemplateId={setSelectedTemplateId}

                    minWpm={minWpm}
                    setMinWpm={setMinWpm}
                    maxWpm={maxWpm}
                    setMaxWpm={setMaxWpm}
                    typoChance={typoChance}
                    setTypoChance={setTypoChance}
                    useSynonyms={useSynonyms}
                    setUseSynonyms={setUseSynonyms}
                    bellCurve={bellCurve}
                    setBellCurve={setBellCurve}
                    trackDelivery={trackDelivery}
                    setTrackDelivery={setTrackDelivery}
                    stopOnBlock={stopOnBlock}
                    setStopOnBlock={setStopOnBlock}
                    maxBlockRate={maxBlockRate}
                    setMaxBlockRate={setMaxBlockRate}
                    campaignName={campaignName}
                    setCampaignName={setCampaignName}
                    selectedAccount={selectedAccount}
                    setSelectedAccount={setSelectedAccount}
                    sessions={sessions}
                    handleLaunchCampaign={handleLaunchCampaign}
                    isCampaignRunning={isCampaignRunning}
                />
            )}

            {activeTab === 'group-campaign' && (
                <GroupCampaignWorkspace
                    callRPC={callRPC}
                    sessions={sessions}
                />
            )}

            {activeTab === 'templates' && (
                <TemplatesWorkspace
                    callRPC={callRPC}
                    onUseTemplate={handleUseTemplate}
                />
            )}

            {activeTab === 'groups' && (
                <GroupsWorkspace
                    t={t}
                    callRPC={callRPC}
                    selectedAccount={selectedAccount}
                    sessions={sessions}
                />
            )}

            {activeTab === 'history' && (
                <CampaignsListWorkspace
                    t={t}
                    callRPC={callRPC}
                    activeCampaigns={activeCampaigns}
                    onViewReport={(id: string, name: string) => {
                        setReportCampaignId(id);
                        setReportCampaignName(name);
                        setActiveTab('report');
                    }}
                />
            )}

            {activeTab === 'report' && reportCampaignId && (
                <CampaignReportWorkspace
                    t={t}
                    callRPC={callRPC}
                    campaignId={reportCampaignId}
                    campaignName={reportCampaignName}
                    onBack={() => setActiveTab('history')}
                />
            )}
        </ToolShellLayout>
    );
}
