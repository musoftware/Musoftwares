import React, { useState, useEffect, useRef } from 'react';
import ToolShellLayout from './WhatsApp/Layouts/ToolShellLayout';
import Sidebar from './WhatsApp/Components/Sidebar';
import AccountsWorkspace from './WhatsApp/Workspaces/AccountsWorkspace';
import CampaignWorkspace from './WhatsApp/Workspaces/CampaignWorkspace';
import DeliverabilityWorkspace from './WhatsApp/Workspaces/DeliverabilityWorkspace';
import ScorecardWorkspace from './WhatsApp/Workspaces/ScorecardWorkspace';

// Nested translation structure for cleaner workspace integration
const translations = {
    en: {
        title: "WhatsApp Sender",
        subtitle: "Automated Messaging & Campaign Operations",
        language: "العربية",
        connected: "System Online",
        disconnected: "System Offline",
        tabs: {
            accounts: "WhatsApp Accounts",
            campaign: "Campaign Details",
            deliverability: "Live Activity",
            scorecard: "Campaign Report"
        },
        accounts: {
            qrRefreshes: "Refreshes in",
            qrPendingBadge: "Awaiting Scan",
            qrTitle: "Link Device",
            qrInstructions: "Open WhatsApp on your phone -> Settings -> Linked Devices -> Scan this QR code.",
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
            contactsPlaceholder: "Paste numbers or CSV here:\nphone,name\n201001234567,John\n201007654321,Jane",
            messageLabel: "Message Content",
            personalizationTags: "Variables:",
            messagePlaceholder: "Hi {name}! Welcome to {company}.",
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
            launchButton: "Start Campaign",
            selectAccountError: "Please select a routing session first.",
            noContactsError: "Please provide valid contacts.",
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
            statusBlocked: "Action Required",
            statusFailed: "Failed",
            statusPending: "Pending"
        },
        scorecard: {
            totalProcessed: "Total Targets",
            sentSuccessfully: "Sent",
            failedOrSkipped: "Failed/Skipped",
            blocksReceived: "Action Required",
            title: "Delivery Health",
            description: "Post-campaign diagnostic and safety score.",
            trustGrade: "Health Grade",
            scoreExplanation: "Based on delivery rates and blocks.",
            blockRate: "Block Rate",
            banProbability: "Account Status Risk",
            warmingRecommendation: "Recommendation",
            grades: {
                excellent: "Excellent Health",
                good: "Good Health",
                warning: "Warning Level",
                danger: "Critical Level"
            }
        }
    },
    ar: {
        title: "مرسل الواتساب",
        subtitle: "نظام المراسلة الآلية وإدارة الحملات",
        language: "English",
        connected: "النظام متصل",
        disconnected: "النظام غير متصل",
        tabs: {
            accounts: "حسابات الواتساب",
            campaign: "تفاصيل الحملة",
            deliverability: "النشاط المباشر",
            scorecard: "تقرير الحملة"
        },
        accounts: {
            qrRefreshes: "تحديث خلال",
            qrPendingBadge: "بانتظار المسح",
            qrTitle: "ربط جهاز",
            qrInstructions: "افتح الواتساب -> الأجهزة المرتبطة -> امسح الرمز.",
            addAccount: "ربط حساب جديد",
            accountId: "اسم الحساب",
            accountIdPlaceholder: "مثال: فريق المبيعات 1",
            proxy: "بروكسي (اختياري)",
            proxyPlaceholder: "http://user:pass@host:port",
            headless: "تشغيل في الخلفية",
            connect: "ربط الحساب",
            activeSessions: "الاتصالات النشطة",
            description: "إدارة الأجهزة المتصلة والبروكسي.",
            noAccounts: "لا توجد حسابات مرتبطة.",
            disconnect: "قطع الاتصال",
            trustScore: "درجة الصحة"
        },
        campaign: {
            contactsLabel: "قائمة الأرقام",
            parsedContacts: "جهات اتصال صحيحة",
            contactsPlaceholder: "الصق الأرقام هنا:\nphone,name\n201001234567,أحمد",
            messageLabel: "محتوى الرسالة",
            personalizationTags: "المتغيرات:",
            messagePlaceholder: "مرحباً {name}! أهلاً بك في {company}.",
            attachmentLabel: "المرفقات",
            attachmentModes: { none: 'بدون', media: 'رابط وسائط', vcard: 'بطاقة اتصال' },
            attachmentUrl: "رابط الوسائط العام",
            attachmentUrlPlaceholder: "https://...",
            vcardName: "الاسم",
            vcardPhone: "رقم الهاتف",
            vcardCompany: "الشركة",
            safetyLabel: "سرعة وأمان الإرسال",
            typingSpeed: "سرعة الكتابة",
            typoChance: "نسبة تصحيح الأخطاء المطبعية",
            useSynonyms: "استخدام مرادفات الذكاء الاصطناعي",
            bellCurve: "تأخيرات منحنى الجرس البشري",
            trackDelivery: "تتبع حالة التسليم",
            stopOnBlock: "إيقاف طوارئ عند الحظر",
            maxBlockRate: "الحد الأقصى لنسبة الحظر",
            launchButton: "بدء الحملة",
            selectAccountError: "يرجى تحديد حساب التوجيه أولاً.",
            noContactsError: "يرجى تقديم جهات اتصال صحيحة.",
            emptyMessageError: "يرجى إدخال رسالة أو إرفاق ملف."
        },
        deliverability: {
            campaignProgress: "تقدم الحملة",
            description: "تتبع تسليم الرسائل في الوقت الفعلي.",
            stopCampaign: "إيقاف الحملة",
            colPhone: "الهاتف",
            colName: "الاسم",
            colCompany: "الشركة",
            colStatus: "الحالة",
            statusRead: "تمت القراءة",
            statusDelivered: "تم التسليم",
            statusSent: "تم الإرسال",
            statusReplied: "تم الرد",
            statusBlocked: "إجراء مطلوب",
            statusFailed: "فشل",
            statusPending: "قيد الانتظار"
        },
        scorecard: {
            totalProcessed: "إجمالي المستهدفين",
            sentSuccessfully: "تم الإرسال",
            failedOrSkipped: "فشل/تم التخطي",
            blocksReceived: "إجراء مطلوب",
            title: "صحة الإرسال",
            description: "تشخيص ما بعد الحملة ودرجة الأمان.",
            trustGrade: "درجة الصحة",
            scoreExplanation: "بناءً على معدلات التسليم والحظر.",
            blockRate: "معدل الحظر",
            banProbability: "خطر حالة الحساب",
            warmingRecommendation: "التوصية",
            grades: {
                excellent: "صحة ممتازة",
                good: "صحة جيدة",
                warning: "مستوى تحذير",
                danger: "مستوى حرج"
            }
        }
    }
};

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
        if (!ws || ws.readyState !== WebSocket.OPEN) throw new Error('Not connected to runtime daemon');
        
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

export default function WhatsAppSenderRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [locale, setLocale] = useState<'en' | 'ar'>('en');
    const [activeTab, setActiveTab] = useState<'accounts' | 'campaign' | 'deliverability' | 'scorecard'>('accounts');

    const [sessions, setSessions] = useState<any[]>([]);
    const [newAccountId, setNewAccountId] = useState('');
    const [newProxy, setNewProxy] = useState('');
    const [newHeadless, setNewHeadless] = useState(false);
    
    const [activeQR, setActiveQR] = useState<string | null>(null);
    const [qrSessionId, setQrSessionId] = useState<string | null>(null);
    const [qrCountdown, setQrCountdown] = useState(20);

    const [campaignName, setCampaignName] = useState('');
    const [selectedAccount, setSelectedAccount] = useState('');
    const [contactsText, setContactsText] = useState('');
    const [messageText, setMessageText] = useState('');
    
    const [attachmentMode, setAttachmentMode] = useState<'none' | 'media' | 'vcard'>('none');
    const [attachmentUrl, setAttachmentUrl] = useState('');
    const [vcardName, setVcardName] = useState('');
    const [vcardPhone, setVcardPhone] = useState('');
    const [vcardCompany, setVcardCompany] = useState('');

    const [minWpm, setMinWpm] = useState(45);
    const [maxWpm, setMaxWpm] = useState(75);
    const [typoChance, setTypoChance] = useState(5);
    const [useSynonyms, setUseSynonyms] = useState(true);
    const [bellCurve, setBellCurve] = useState(true);
    const [trackDelivery, setTrackDelivery] = useState(true);
    const [stopOnBlock, setStopOnBlock] = useState(true);
    const [maxBlockRate, setMaxBlockRate] = useState(5);

    const [isCampaignRunning, setIsCampaignRunning] = useState(false);
    const [runningTaskId, setRunningTaskId] = useState<string | null>(null);
    const [campaignProgress, setCampaignProgress] = useState({ sent: 0, failed: 0, skipped: 0, blocked: 0, total: 0, percent: 0 });
    const [deliverabilityGrid, setDeliverabilityGrid] = useState<any[]>([]);
    const [campaignResult, setCampaignResult] = useState<any>(null);

    const runningCampaignIdRef = useRef<string | null>(null);
    const t = translations[locale];

    const onBroadcast = (event: string, data: any) => {
        if (event.startsWith('whatsapp.session.')) {
            const accountId = data.accountId;
            if (!accountId) return;

            setSessions(prev => {
                const match = prev.find(s => s.accountId === accountId);
                const stateMap: Record<string, string> = {
                    'whatsapp.session.connecting': 'connecting',
                    'whatsapp.session.connected': 'connected',
                    'whatsapp.session.disconnected': 'disconnected',
                    'whatsapp.session.banned': 'banned',
                    'whatsapp.session.error': 'error',
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
                    if (match) {
                        return prev.map(s => s.accountId === accountId ? { ...s, state: newState, health: { ...s.health, trustScore: data.trustScore || s.health?.trustScore || 50 } } : s);
                    } else {
                        return [...prev, { accountId, state: newState, health: { trustScore: data.trustScore || 50 }, lastActivity: null, startedAt: null }];
                    }
                }
                return prev;
            });
        }

        if (event === 'whatsapp.campaign.progress' && data.campaignId === runningCampaignIdRef.current) {
            setCampaignProgress({
                sent: data.sent || 0,
                failed: data.failed || 0,
                skipped: data.skipped || 0,
                blocked: data.blocked || 0,
                total: data.total || 0,
                percent: data.percent || 0
            });
        }

        if ((event === 'whatsapp.message.sent' || event === 'whatsapp.message.failed') && data.campaignId === runningCampaignIdRef.current) {
            setDeliverabilityGrid(prev => prev.map(row => {
                if (row.phone === data.phone) {
                    return { ...row, status: event === 'whatsapp.message.sent' ? 'sent' : 'failed', reason: data.reason || null };
                }
                return row;
            }));
        }

        if (event === 'whatsapp.campaign.completed' && data.campaignId === runningCampaignIdRef.current) {
            setIsCampaignRunning(false);
            setCampaignResult(data);
            setActiveTab('scorecard');
        }
    };

    const { connected: daemonConnected, callRPC } = useRuntimeWS('whatsapp-sender', onBroadcast);

    useEffect(() => {
        if (daemonConnected) fetchSessions();
    }, [daemonConnected]);

    useEffect(() => {
        let timer: any;
        if (activeQR) {
            timer = setInterval(() => {
                setQrCountdown(prev => (prev <= 1 ? 20 : prev - 1));
            }, 1000);
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
            if (firstConnected && !selectedAccount) {
                setSelectedAccount(firstConnected.accountId);
            }
        } catch (err) {
            console.error('Failed to load active sessions:', err);
        }
    };

    const handleConnectSession = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newAccountId.trim()) return;

        try {
            await callRPC('connectSession', {
                accountId: newAccountId.trim(),
                proxy: newProxy.trim() || null,
                headless: newHeadless
            });
            setSessions(prev => [
                ...prev.filter(s => s.accountId !== newAccountId.trim()),
                { accountId: newAccountId.trim(), state: 'connecting', health: { trustScore: 50 } }
            ]);
            setNewAccountId('');
            setNewProxy('');
        } catch (err: any) {
            alert(`Connect Error: ${err.message}`);
        }
    };

    const handleDisconnectSession = async (accountId: string) => {
        try {
            await callRPC('disconnectSession', { accountId });
            fetchSessions();
            if (qrSessionId === accountId) {
                setActiveQR(null);
                setQrSessionId(null);
            }
        } catch (err: any) {
            alert(`Disconnect Error: ${err.message}`);
        }
    };

    const parseContacts = () => {
        if (!contactsText.trim()) return [];
        return contactsText.split('\n').map(line => {
            const parts = line.split(',');
            return {
                phone: parts[0]?.trim().replace(/[^0-9+]/g, '') || '',
                name: parts[1]?.trim() || '',
                company: parts[2]?.trim() || ''
            };
        }).filter(c => c.phone.length > 6);
    };

    const insertTag = (tag: string) => setMessageText(prev => prev + tag);

    const handleLaunchCampaign = async () => {
        const parsed = parseContacts();
        if (!selectedAccount) return alert(t.campaign.selectAccountError);
        if (parsed.length === 0) return alert(t.campaign.noContactsError);
        if (!messageText.trim() && attachmentMode === 'none') return alert(t.campaign.emptyMessageError);

        setIsCampaignRunning(true);
        setCampaignProgress({ sent: 0, failed: 0, skipped: 0, blocked: 0, total: parsed.length, percent: 0 });
        setCampaignResult(null);
        setDeliverabilityGrid(parsed.map(c => ({ ...c, status: 'pending', reason: null })));

        const campId = `campaign-${Date.now()}`;
        runningCampaignIdRef.current = campId;
        setActiveTab('deliverability');

        try {
            const host = typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
            const response = await fetch(`http://${host}:${runtimePort || 18400}/plugins/whatsapp-sender/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        action: 'send_bulk',
                        contacts: parsed,
                        message: messageText,
                        media_type: attachmentMode,
                        media_url: attachmentMode === 'media' ? attachmentUrl : null,
                        vcard_name: attachmentMode === 'vcard' ? vcardName : '',
                        vcard_phone: attachmentMode === 'vcard' ? vcardPhone : '',
                        vcard_company: attachmentMode === 'vcard' ? vcardCompany : '',
                        humanize: true,
                        aggressiveness: 'moderate',
                        delay_ms: 4000,
                        account_id: selectedAccount,
                        account_ids: [selectedAccount],
                        humanizer_wpm: [minWpm, maxWpm],
                        humanizer_typo_chance: typoChance / 100,
                        humanizer_use_synonyms: useSynonyms,
                        track_delivery: trackDelivery,
                        stop_on_block: stopOnBlock,
                        max_block_rate: maxBlockRate / 100,
                        campaign_id: campId,
                        campaign_name: campaignName.trim() || 'Bulk Campaign'
                    }
                })
            });

            if (!response.ok) {
                const errData = await response.json();
                throw new Error(errData.error || 'Failed to trigger worker');
            }

            const data = await response.json();
            setRunningTaskId(data.taskId);
        } catch (err: any) {
            alert(`Campaign Launch Failure: ${err.message}`);
            setIsCampaignRunning(false);
        }
    };

    const handleStopCampaign = async () => {
        if (!runningTaskId) return;
        try {
            const host = typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
            await fetch(`http://${host}:${runtimePort || 18400}/tasks/${runningTaskId}/stop`, { method: 'POST' });
            setIsCampaignRunning(false);
        } catch (err) {
            console.error('Failed to stop task:', err);
        }
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
                    hasResult={!!campaignResult}
                    t={t}
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
                    insertTag={insertTag}
                    messageText={messageText}
                    setMessageText={setMessageText}
                    attachmentMode={attachmentMode}
                    setAttachmentMode={setAttachmentMode}
                    attachmentUrl={attachmentUrl}
                    setAttachmentUrl={setAttachmentUrl}
                    vcardName={vcardName}
                    setVcardName={setVcardName}
                    vcardPhone={vcardPhone}
                    setVcardPhone={setVcardPhone}
                    vcardCompany={vcardCompany}
                    setVcardCompany={setVcardCompany}
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

            {activeTab === 'deliverability' && (
                <DeliverabilityWorkspace 
                    t={t}
                    isCampaignRunning={isCampaignRunning}
                    handleStopCampaign={handleStopCampaign}
                    campaignProgress={campaignProgress}
                    deliverabilityGrid={deliverabilityGrid}
                />
            )}

            {activeTab === 'scorecard' && campaignResult && (
                <ScorecardWorkspace 
                    t={t}
                    campaignResult={campaignResult}
                />
            )}
        </ToolShellLayout>
    );
}
