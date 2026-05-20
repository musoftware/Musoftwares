import React, { useState, useEffect, useRef } from 'react';
import { 
    LayoutDashboard, Users, MessageSquare, ShieldAlert, Globe, QrCode, 
    Power, Trash2, CheckCircle2, AlertCircle, RefreshCw, Sparkles, 
    Send, FileText, ChevronRight, Play, Square, Settings2, ShieldCheck, 
    HelpCircle, Eye, EyeOff, Upload, MoreHorizontal 
} from 'lucide-react';

// Translating text elements for Bilingual EN/AR support
const translations = {
    en: {
        title: "WhatsApp Campaign OS",
        subtitle: "Enterprise-Grade Bulk Dispatch Engine & Anti-Ban Safeguards",
        connectionStatus: "Engine Linked",
        connecting: "Linking to Daemon...",
        connected: "Online & Ready",
        disconnected: "Engine Offline",
        language: "العربية",
        tabs: {
            accounts: "Session Pool",
            campaign: "New Campaign",
            deliverability: "Live Monitor",
            scorecard: "Post-Campaign Health"
        },
        accounts: {
            title: "WhatsApp Session Manager",
            description: "Link multiple WhatsApp accounts with isolated browser sessions and proxies.",
            addAccount: "Add Account Session",
            accountId: "Session Name / ID",
            accountIdPlaceholder: "e.g., Sales-Account-1",
            proxy: "Proxy Connection String (SOCKS5/HTTP)",
            proxyPlaceholder: "e.g., http://user:pass@host:port",
            headless: "Headless Browser Mode (Invisible)",
            connect: "Launch & Connect Session",
            disconnect: "Disconnect",
            activeSessions: "Active Account Sessions",
            status: "Status",
            trustScore: "Trust Grade",
            qrTitle: "Scan QR Code to Connect",
            qrInstructions: "Open WhatsApp on your phone -> Menu or Settings -> Linked Devices -> Link a Device.",
            qrRefreshes: "Refreshes automatically every 20 seconds",
            noAccounts: "No sessions linked. Configure and launch a session above to start sending campaigns.",
            connectedBadge: "Connected",
            connectingBadge: "Connecting",
            qrPendingBadge: "QR Scanning Pending",
            disconnectedBadge: "Disconnected",
            bannedBadge: "Banned",
            errorBadge: "Error",
            idleBadge: "Idle"
        },
        campaign: {
            title: "Create Bulk Campaign",
            contactsLabel: "1. Contacts List (CSV or Raw)",
            contactsPlaceholder: "Paste phone numbers (one per line) or comma-separated names & companies.\nExample:\n+1234567890, John Doe, Acme Corp\n+9876543210, Jane Smith, Beta Inc",
            parsedContacts: "Parsed Contacts",
            personalizationTags: "Click to Insert Personalization Tags:",
            messageLabel: "2. Message Template",
            messagePlaceholder: "Write your message here... Use {name}, {phone}, {company} to personalize. Smart anti-ban variations will be generated.",
            attachmentLabel: "3. Attachment Options",
            attachmentModes: {
                none: "None",
                media: "Photo / Video / Document URL",
                vcard: "vCard Contact Card"
            },
            attachmentUrl: "Public Asset URL",
            attachmentUrlPlaceholder: "https://example.com/assets/flyer.jpg",
            vcardName: "Contact Full Name",
            vcardPhone: "Contact Phone Number",
            vcardCompany: "Contact Company Name",
            safetyLabel: "4. AI Safety & Anti-Ban Controls",
            typingSpeed: "Keystroke Typing Speed (WPM Range)",
            typoChance: "Typo Correction Probability",
            useSynonyms: "Enable Smart Message Variations (Synonym Swapping)",
            bellCurve: "Bell-Curve Delay Distribution (Natural Pacing)",
            trackDelivery: "Real-time Message Receipt Tracking (sent -> read)",
            stopOnBlock: "Emergency Circuit Breaker (Auto-Stop on Blocks)",
            maxBlockRate: "Max Block Rate Threshold",
            launchButton: "Launch Campaign Engine",
            activeCampaignWarning: "Campaign is currently running. Please wait for completion or stop it.",
            selectAccountError: "Please link at least one active WhatsApp account session before launching.",
            noContactsError: "Please add at least one valid recipient contact number.",
            emptyMessageError: "Please write a message template or configure an attachment."
        },
        deliverability: {
            title: "Live Deliverability Monitor",
            description: "Real-time tracking of message statuses as the campaign dispatches.",
            colPhone: "Recipient Phone",
            colName: "Name",
            colCompany: "Company",
            colStatus: "Progress Status",
            statusPending: "Pending",
            statusSent: "Sent",
            statusDelivered: "Delivered",
            statusRead: "Read & Opened",
            statusReplied: "Replied & Engaged",
            statusFailed: "Failed",
            statusBlocked: "Blocked / Flagged",
            campaignProgress: "Campaign Progress",
            stopCampaign: "Emergency Stop Campaign"
        },
        scorecard: {
            title: "Anti-Ban Campaign Diagnostics",
            description: "Post-campaign safety report and account health assessment.",
            totalProcessed: "Total Processed",
            sentSuccessfully: "Sent Successfully",
            failedOrSkipped: "Failed / Skipped",
            blocksReceived: "Blocks Flagged",
            blockRate: "Campaign Block Rate",
            trustGrade: "Account Health Grade",
            banProbability: "Ban Risk Probability",
            healthGradeTitle: "Safety Level Diagnostic",
            scoreExplanation: "Calculated based on reply ratios, daily pacing speed, and flagging frequency.",
            warmingRecommendation: "Warming & Anti-Ban Recommendation",
            grades: {
                excellent: "A+ Excellent Health. Ideal bilateral messaging. Keep moderate daily volume.",
                good: "B Safe Health. Minor lag in engagement. Recommended to enable synonym engine.",
                warning: "C Elevated Risk. Reciprocal reply ratio is low. Decrease daily limits immediately.",
                danger: "F High Danger. Rapidly approaching ban threshold. Perform account warming now."
            }
        }
    },
    ar: {
        title: "منصة إرسال حملات واتساب المكثفة",
        subtitle: "نظام إدارة الرسائل الذكي للمؤسسات مع أنظمة الحماية من الحظر",
        connectionStatus: "مرتبط بالخادم",
        connecting: "جاري الاتصال بالخادم المحلي...",
        connected: "متصل وجاهز",
        disconnected: "الخادم غير متصل",
        language: "English",
        tabs: {
            accounts: "مجمع الحسابات",
            campaign: "حملة جديدة",
            deliverability: "المراقبة المباشرة",
            scorecard: "صحة الحساب بعد الحملة"
        },
        accounts: {
            title: "إدارة جلسات حسابات الواتساب",
            description: "اربط عدة حسابات واتساب بجلسات متصفح معزولة تماماً مع إعدادات بروكسي خاصة.",
            addAccount: "إضافة جلسة حساب جديدة",
            accountId: "اسم / معرف الجلسة",
            accountIdPlaceholder: "مثال: حساب-المبيعات-1",
            proxy: "عنوان البروكسي (SOCKS5/HTTP)",
            proxyPlaceholder: "مثال: http://user:pass@host:port",
            headless: "وضع التصفح الخفي (خلف الكواليس)",
            connect: "تشغيل وربط الجلسة",
            disconnect: "قطع الاتصال",
            activeSessions: "جلسات الحسابات النشطة",
            status: "الحالة",
            trustScore: "درجة الموثوقية",
            qrTitle: "امسح رمز الاستجابة السريعة (QR) للاتصال",
            qrInstructions: "افتح تطبيق واتساب على هاتفك -> القائمة أو الإعدادات -> الأجهزة المرتبطة -> ربط جهاز جديد.",
            qrRefreshes: "يتحدث الرمز تلقائياً كل 20 ثانية",
            noAccounts: "لا توجد جلسات مرتبطة. يرجى تهيئة وتشغيل جلسة حساب أعلاه لبدء الحملات.",
            connectedBadge: "متصل",
            connectingBadge: "جاري الاتصال",
            qrPendingBadge: "في انتظار مسح الرمز",
            disconnectedBadge: "غير متصل",
            bannedBadge: "محظور",
            errorBadge: "خطأ",
            idleBadge: "خامل"
        },
        campaign: {
            title: "إنشاء حملة إرسال جماعي",
            contactsLabel: "1. قائمة جهات الاتصال (ملف CSV أو نص خام)",
            contactsPlaceholder: "ألصق الأرقام (رقم واحد في كل سطر) أو قيم مفصولة بفواصل للأرقام والأسماء والشركات.\nمثال:\n+1234567890, محمد أحمد, شركة الأمل\n+9876543210, سارة خالد, شركة التميز",
            parsedContacts: "جهات الاتصال المستخرجة",
            personalizationTags: "اضغط لإدراج وسوم التخصيص:",
            messageLabel: "2. قالب الرسالة النصية",
            messagePlaceholder: "اكتب رسالتك هنا... استخدم المتغيرات {name}، {phone}، {company} لتخصيص محتوى الرسائل لكل جهة اتصال.",
            attachmentLabel: "3. خيارات المرفقات",
            attachmentModes: {
                none: "لا يوجد",
                media: "رابط صورة / فيديو / مستند",
                vcard: "بطاقة اتصال (vCard)"
            },
            attachmentUrl: "رابط المرفق العام",
            attachmentUrlPlaceholder: "https://example.com/assets/flyer.jpg",
            vcardName: "الاسم الكامل لجهة الاتصال",
            vcardPhone: "رقم هاتف جهة الاتصال",
            vcardCompany: "اسم شركة جهة الاتصال",
            safetyLabel: "4. خيارات الأمان ومحاكاة الذكاء الاصطناعي",
            typingSpeed: "سرعة الكتابة ومحاكاة الضغطات (كلمة/دقيقة)",
            typoChance: "نسبة حدوث أخطاء إملائية وتصحيحها",
            useSynonyms: "تفعيل تنويع الكلمات الذكي (تبديل المرادفات)",
            bellCurve: "تفعيل توزيع التأخير على منحنى بيل الطبيعي",
            trackDelivery: "تتبع استلام الرسائل الفعلي (أرسلت -> قرأت)",
            stopOnBlock: "نظام التوقف التلقائي الطارئ عند حدوث حظر",
            maxBlockRate: "الحد الأقصى لنسبة الحظر المسموحة",
            launchButton: "بدء تشغيل محرك الحملة",
            activeCampaignWarning: "الحملة قيد التشغيل حالياً. يرجى الانتظار أو إيقافها طارئاً.",
            selectAccountError: "يرجى ربط حساب واتساب نشط واحد على الأقل قبل تشغيل الحملة.",
            noContactsError: "يرجى إضافة مستلم واحد صالح على الأقل.",
            emptyMessageError: "يرجى كتابة رسالة نصية أو تهيئة مرفق للإرسال."
        },
        deliverability: {
            title: "مراقب التسليم المباشر",
            description: "تتبع فوري لحالات إرسال وقراءة الرسائل أثناء تشغيل الحملة.",
            colPhone: "رقم المستلم",
            colName: "الاسم",
            colCompany: "الشركة",
            colStatus: "حالة الإرسال",
            statusPending: "في الانتظار",
            statusSent: "تم الإرسال",
            statusDelivered: "تم الاستلام",
            statusRead: "تمت القراءة والفتح",
            statusReplied: "تم الرد والتفاعل",
            statusFailed: "فشل الإرسال",
            statusBlocked: "تم الحظر / التبليغ",
            campaignProgress: "تقدم الحملة الميدانية",
            stopCampaign: "توقف طارئ للحملة"
        },
        scorecard: {
            title: "تشخيصات حماية الحسابات",
            description: "تقرير الأمان الشامل للجلسة ومستوى مخاطر الحظر.",
            totalProcessed: "إجمالي المعالجة",
            sentSuccessfully: "أرسل بنجاح",
            failedOrSkipped: "فشل / تخطي",
            blocksReceived: "التبليغات / الحظر",
            blockRate: "نسبة الحظر في الحملة",
            trustGrade: "درجة صحة الحساب",
            banProbability: "احتمالية التعرض للحظر",
            healthGradeTitle: "تشخيص مستوى الأمان الفعلي",
            scoreExplanation: "يتم حساب هذا التقييم بناءً على نسبة تفاعل الردود، نمط وتيرة الإرسال، ومعدل التبليغات في الشبكة.",
            warmingRecommendation: "إرشادات تهيئة وتدفئة الحساب للحماية",
            grades: {
                excellent: "A+ صحة ممتازة. حوار متبادل مثالي. حافظ على هذا المعدل المتوسط للإرسال اليومي.",
                good: "B صحة آمنة. تفاعل ردود طفيف. ينصح بتشغيل نظام تبديل المرادفات الذكي.",
                warning: "C خطر مرتفع. نسبة التفاعل والردود منخفضة جداً. قلل حدود الإرسال اليومية فوراً.",
                danger: "F خطر شديد. تقترب من عتبة الحظر النهائي. قم بعمل تدفئة للحساب وتوقف عن الحملات حالاً."
            }
        }
    }
};

// Simple React Custom hook to handle real-time WebSockets communication with the Daemon
function useRuntimeWS(pluginSlug: string, onBroadcast?: ((event: string, data: any) => void) | null) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef<Map<string, { resolve: Function, reject: Function }>>(new Map());
    const onBroadcastRef = useRef<((event: string, data: any) => void) | null>(null);

    onBroadcastRef.current = onBroadcast || null;

    useEffect(() => {
        const socket = new WebSocket('ws://127.0.0.1:18401/ws');
        
        socket.onopen = () => setConnected(true);
        socket.onclose = () => setConnected(false);
        
        socket.onmessage = (event) => {
            try {
                const msg = JSON.parse(event.data);
                // 1. Process RPC Responses
                if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                    const resolver = pendingRequests.current.get(msg.requestId);
                    if (resolver) {
                        if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload.error));
                        else resolver.resolve(msg.payload);
                        pendingRequests.current.delete(msg.requestId);
                    }
                }
                // 2. Process Broadcast Events from Event Kernel
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
            
            // Timeout after 30s for intensive operations
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

    // ── WebSocket event handling ─────────────────────────────────────────────
    const onBroadcast = (event: string, data: any) => {
        console.log(`[WS Event] ${event}`, data);
        
        // Handle Session Status events
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
                
                // Track QR data specifically
                if (event === 'whatsapp.session.qr_updated') {
                    setActiveQR(data.qr);
                    setQrSessionId(accountId);
                    return prev.map(s => s.accountId === accountId ? { ...s, state: 'qr_pending' } : s);
                }

                if (event === 'whatsapp.session.connected') {
                    if (qrSessionId === accountId) {
                        setActiveQR(null);
                        setQrSessionId(null);
                    }
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

        // Handle Campaign Progress updates in real-time
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

        // Handle message dispatch deliverability state changes
        if ((event === 'whatsapp.message.sent' || event === 'whatsapp.message.failed') && data.campaignId === runningCampaignIdRef.current) {
            setDeliverabilityGrid(prev => {
                return prev.map(row => {
                    if (row.phone === data.phone) {
                        return { 
                            ...row, 
                            status: event === 'whatsapp.message.sent' ? 'sent' : 'failed',
                            reason: data.reason || null 
                        };
                    }
                    return row;
                });
            });
        }

        // Handle campaign finished report details
        if (event === 'whatsapp.campaign.completed' && data.campaignId === runningCampaignIdRef.current) {
            setIsCampaignRunning(false);
            setCampaignResult(data);
            setActiveTab('scorecard');
        }
    };

    const { connected: daemonConnected, callRPC } = useRuntimeWS('whatsapp-sender', onBroadcast);

    // ── Application States ────────────────────────────────────────────────────
    const [sessions, setSessions] = useState<any[]>([]);
    
    // Connect Form fields
    const [newAccountId, setNewAccountId] = useState('');
    const [newProxy, setNewProxy] = useState('');
    const [newHeadless, setNewHeadless] = useState(false);
    
    // QR Code state
    const [activeQR, setActiveQR] = useState<string | null>(null);
    const [qrSessionId, setQrSessionId] = useState<string | null>(null);
    const [qrCountdown, setQrCountdown] = useState(20);

    // Campaign form states
    const [campaignName, setCampaignName] = useState('');
    const [selectedAccount, setSelectedAccount] = useState('');
    const [contactsText, setContactsText] = useState('');
    const [messageText, setMessageText] = useState('');
    
    // Attachment Modes
    const [attachmentMode, setAttachmentMode] = useState<'none' | 'media' | 'vcard'>('none');
    const [attachmentUrl, setAttachmentUrl] = useState('');
    const [vcardName, setVcardName] = useState('');
    const [vcardPhone, setVcardPhone] = useState('');
    const [vcardCompany, setVcardCompany] = useState('');

    // Safety Sliders & Toggles
    const [minWpm, setMinWpm] = useState(45);
    const [maxWpm, setMaxWpm] = useState(75);
    const [typoChance, setTypoChance] = useState(5); // in percent
    const [useSynonyms, setUseSynonyms] = useState(true);
    const [bellCurve, setBellCurve] = useState(true);
    const [trackDelivery, setTrackDelivery] = useState(true);
    const [stopOnBlock, setStopOnBlock] = useState(true);
    const [maxBlockRate, setMaxBlockRate] = useState(5); // in percent

    // Executing Campaign Tracker
    const [isCampaignRunning, setIsCampaignRunning] = useState(false);
    const [runningTaskId, setRunningTaskId] = useState<string | null>(null);
    const [campaignProgress, setCampaignProgress] = useState({ sent: 0, failed: 0, skipped: 0, blocked: 0, total: 0, percent: 0 });
    const [deliverabilityGrid, setDeliverabilityGrid] = useState<any[]>([]);
    const [campaignResult, setCampaignResult] = useState<any>(null);

    const runningCampaignIdRef = useRef<string | null>(null);
    const t = translations[locale];

    // Load active sessions from the pool on mount / daemon connected
    useEffect(() => {
        if (daemonConnected) {
            fetchSessions();
        }
    }, [daemonConnected]);

    // QR countdown Timer logic
    useEffect(() => {
        let timer: any;
        if (activeQR) {
            timer = setInterval(() => {
                setQrCountdown(prev => {
                    if (prev <= 1) return 20;
                    return prev - 1;
                });
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
            // Auto select first connected account if any
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
            // Instantly append temporary state in our grid
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

    // CSV Parse helper
    const parseContacts = () => {
        if (!contactsText.trim()) return [];
        const lines = contactsText.split('\n');
        return lines.map(line => {
            const parts = line.split(',');
            const phone = parts[0]?.trim().replace(/[^0-9+]/g, '') || '';
            const name = parts[1]?.trim() || '';
            const company = parts[2]?.trim() || '';
            return { phone, name, company };
        }).filter(c => c.phone.length > 6);
    };

    const insertTag = (tag: string) => {
        setMessageText(prev => prev + tag);
    };

    const handleLaunchCampaign = async () => {
        const parsed = parseContacts();
        if (!selectedAccount) {
            alert(t.campaign.selectAccountError);
            return;
        }
        if (parsed.length === 0) {
            alert(t.campaign.noContactsError);
            return;
        }
        if (!messageText.trim() && attachmentMode === 'none') {
            alert(t.campaign.emptyMessageError);
            return;
        }

        setIsCampaignRunning(true);
        setCampaignProgress({ sent: 0, failed: 0, skipped: 0, blocked: 0, total: parsed.length, percent: 0 });
        setCampaignResult(null);
        
        // Initialize deliverability monitoring grid
        const initialGrid = parsed.map(c => ({
            ...c,
            status: 'pending',
            reason: null
        }));
        setDeliverabilityGrid(initialGrid);

        const campId = `campaign-${Date.now()}`;
        runningCampaignIdRef.current = campId;

        // Route to the Deliverability Grid panel instantly
        setActiveTab('deliverability');

        try {
            // Trigger background execution task through runtime HTTP API
            const response = await fetch(`http://127.0.0.1:${runtimePort || 18400}/plugins/whatsapp-sender/run`, {
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
            await fetch(`http://127.0.0.1:${runtimePort || 18400}/tasks/${runningTaskId}/stop`, { method: 'POST' });
            setIsCampaignRunning(false);
        } catch (err) {
            console.error('Failed to stop task:', err);
        }
    };

    const getTrustScoreColor = (score: number) => {
        if (score >= 80) return 'text-emerald-500 border-emerald-500 bg-emerald-50/50';
        if (score >= 50) return 'text-amber-500 border-amber-500 bg-amber-50/50';
        return 'text-rose-500 border-rose-500 bg-rose-50/50';
    };

    const getTrustGrade = (score: number) => {
        if (score >= 85) return 'A+';
        if (score >= 70) return 'B';
        if (score >= 50) return 'C';
        return 'F';
    };

    const getParsedRecipients = parseContacts();

    return (
        <div 
            dir={locale === 'ar' ? 'rtl' : 'ltr'} 
            className={`min-h-screen bg-[#F8FAFC] text-slate-900 font-sans selection:bg-teal-500 selection:text-white transition-all duration-300 ${locale === 'ar' ? 'text-right' : 'text-left'}`}
        >
            {/* Nav Header Section */}
            <header className="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-20 px-6 flex items-center justify-between shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center shadow-md animate-pulse">
                        <MessageSquare className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h1 className="font-bold text-base tracking-tight leading-none text-slate-800">{t.title}</h1>
                        <p className="text-[10px] text-slate-400 mt-1 font-medium">{t.subtitle}</p>
                    </div>
                </div>

                {/* Sub Menu Links & State indicators */}
                <div className="flex items-center gap-4">
                    <div className={`flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-semibold uppercase tracking-wider transition-all duration-300 ${
                        daemonConnected 
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                            : 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse'
                    }`}>
                        <div className={`w-2 h-2 rounded-full ${daemonConnected ? 'bg-emerald-500 animate-ping' : 'bg-rose-500'}`} />
                        <span>{daemonConnected ? t.connected : t.disconnected}</span>
                    </div>

                    <button 
                        onClick={() => setLocale(locale === 'en' ? 'ar' : 'en')}
                        className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 transition-all rounded-lg text-xs font-semibold border border-slate-200"
                    >
                        <Globe className="w-4 h-4" />
                        <span>{t.language}</span>
                    </button>
                </div>
            </header>

            {/* Sub-tab Navigation */}
            <div className="bg-white border-b border-slate-200 px-6 flex items-center gap-2">
                <button 
                    onClick={() => setActiveTab('accounts')}
                    className={`px-4 py-3 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 ${activeTab === 'accounts' ? 'border-teal-600 text-teal-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-900'}`}
                >
                    <Users className="w-4 h-4" />
                    {t.tabs.accounts}
                </button>
                <button 
                    onClick={() => setActiveTab('campaign')}
                    className={`px-4 py-3 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 ${activeTab === 'campaign' ? 'border-teal-600 text-teal-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-900'}`}
                >
                    <Send className="w-4 h-4" />
                    {t.tabs.campaign}
                </button>
                <button 
                    onClick={() => setActiveTab('deliverability')}
                    className={`px-4 py-3 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 ${activeTab === 'deliverability' ? 'border-teal-600 text-teal-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-900'}`}
                >
                    <LayoutDashboard className="w-4 h-4" />
                    {t.tabs.deliverability}
                    {isCampaignRunning && (
                        <span className="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping inline-block" />
                    )}
                </button>
                {campaignResult && (
                    <button 
                        onClick={() => setActiveTab('scorecard')}
                        className={`px-4 py-3 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 ${activeTab === 'scorecard' ? 'border-teal-600 text-teal-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-900'}`}
                    >
                        <ShieldAlert className="w-4 h-4" />
                        {t.tabs.scorecard}
                    </button>
                )}
            </div>

            {/* Container view */}
            <main className="max-w-6xl mx-auto p-6 md:p-8">
                {/* ── ACCOUNTS TAB ── */}
                {activeTab === 'accounts' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        {/* QR Overlay for pending connection */}
                        {activeQR && (
                            <div className="bg-gradient-to-tr from-slate-950 to-slate-900 border border-slate-800 rounded-3xl p-6 text-white flex flex-col md:flex-row items-center gap-8 shadow-2xl relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl -z-10" />
                                
                                <div className="bg-white p-4 rounded-2xl shrink-0 shadow-lg border border-slate-100 flex flex-col items-center justify-center">
                                    <img src={activeQR} alt="WhatsApp QR Code" className="w-48 h-48 md:w-56 md:h-56" />
                                    <div className="flex items-center gap-1.5 mt-3 text-slate-500 text-xs font-bold">
                                        <RefreshCw className="w-3.5 h-3.5 animate-spin text-teal-600" />
                                        <span>{t.accounts.qrRefreshes} ({qrCountdown}s)</span>
                                    </div>
                                </div>

                                <div className="space-y-4 flex-1">
                                    <div className="flex items-center gap-2 text-xs font-bold bg-teal-500/10 text-teal-400 px-3 py-1.5 rounded-full border border-teal-500/20 w-fit">
                                        <QrCode className="w-3.5 h-3.5" />
                                        <span>{t.accounts.qrPendingBadge}</span>
                                    </div>
                                    <h3 className="text-xl font-bold tracking-tight">{t.accounts.qrTitle} ({qrSessionId})</h3>
                                    <p className="text-slate-400 text-sm leading-relaxed">{t.accounts.qrInstructions}</p>
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Connect new session column */}
                            <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm h-fit">
                                <div className="flex items-center gap-2.5 pb-4 border-b border-slate-100 mb-4">
                                    <Settings2 className="w-5 h-5 text-teal-600" />
                                    <h3 className="font-bold text-slate-800 text-sm">{t.accounts.addAccount}</h3>
                                </div>

                                <form onSubmit={handleConnectSession} className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{t.accounts.accountId}</label>
                                        <input 
                                            type="text"
                                            value={newAccountId}
                                            onChange={e => setNewAccountId(e.target.value)}
                                            placeholder={t.accounts.accountIdPlaceholder}
                                            className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">{t.accounts.proxy}</label>
                                        <input 
                                            type="text"
                                            value={newProxy}
                                            onChange={e => setNewProxy(e.target.value)}
                                            placeholder={t.accounts.proxyPlaceholder}
                                            className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all"
                                        />
                                    </div>
                                    <div className="flex items-center gap-3 py-1">
                                        <input 
                                            id="headless-toggle"
                                            type="checkbox"
                                            checked={newHeadless}
                                            onChange={e => setNewHeadless(e.target.checked)}
                                            className="w-4.5 h-4.5 text-teal-600 focus:ring-teal-500 border-slate-300 rounded"
                                        />
                                        <label htmlFor="headless-toggle" className="text-xs font-semibold text-slate-600 select-none cursor-pointer">{t.accounts.headless}</label>
                                    </div>
                                    <button 
                                        type="submit"
                                        disabled={!daemonConnected}
                                        className="w-full py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 disabled:bg-slate-300 disabled:shadow-none"
                                    >
                                        <Play className="w-3.5 h-3.5" />
                                        {t.accounts.connect}
                                    </button>
                                </form>
                            </div>

                            {/* Active accounts list column */}
                            <div className="lg:col-span-2 space-y-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-xl font-bold tracking-tight text-slate-850">{t.accounts.activeSessions}</h2>
                                        <p className="text-xs text-slate-400 mt-1">{t.accounts.description}</p>
                                    </div>
                                    <button 
                                        onClick={fetchSessions}
                                        className="p-2 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all active:scale-95"
                                    >
                                        <RefreshCw className="w-4 h-4" />
                                    </button>
                                </div>

                                {sessions.length === 0 ? (
                                    <div className="py-16 text-center border border-dashed border-slate-300 rounded-2xl bg-white space-y-4 px-6">
                                        <QrCode className="w-10 h-10 text-slate-300 mx-auto" />
                                        <div className="max-w-md mx-auto space-y-1">
                                            <h3 className="text-sm font-bold text-slate-700">{t.accounts.title}</h3>
                                            <p className="text-xs text-slate-400">{t.accounts.noAccounts}</p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {sessions.map(s => (
                                            <div 
                                                key={s.accountId} 
                                                className="bg-white border border-slate-200 rounded-2xl p-5 hover:border-slate-300 transition-all flex flex-col justify-between shadow-sm hover:shadow-md relative overflow-hidden"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="space-y-1.5">
                                                        <h4 className="font-bold text-slate-800 text-sm leading-none">{s.accountId}</h4>
                                                        <div className="flex items-center gap-1.5">
                                                            <div className={`w-1.5 h-1.5 rounded-full ${
                                                                s.state === 'connected' ? 'bg-emerald-500' :
                                                                s.state === 'connecting' ? 'bg-amber-500 animate-pulse' :
                                                                s.state === 'qr_pending' ? 'bg-indigo-500 animate-pulse' :
                                                                'bg-slate-400'
                                                            }`} />
                                                            <span className="text-[10px] font-bold text-slate-500 capitalize">
                                                                {s.state === 'connected' ? t.accounts.connectedBadge :
                                                                 s.state === 'connecting' ? t.accounts.connectingBadge :
                                                                 s.state === 'qr_pending' ? t.accounts.qrPendingBadge :
                                                                 s.state === 'banned' ? t.accounts.bannedBadge :
                                                                 s.state === 'error' ? t.accounts.errorBadge :
                                                                 t.accounts.disconnectedBadge}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div className={`w-9 h-9 border rounded-xl flex items-center justify-center text-xs font-black ${getTrustScoreColor(s.health?.trustScore || 50)}`}>
                                                        {getTrustGrade(s.health?.trustScore || 50)}
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between border-t border-slate-100 pt-4 mt-6">
                                                    <div className="text-[10px] text-slate-400 font-semibold">
                                                        {s.health?.lastConnected ? `${new Date(s.health.lastConnected).toLocaleDateString()}` : 'Never connected'}
                                                    </div>
                                                    <button 
                                                        onClick={() => handleDisconnectSession(s.accountId)}
                                                        className="p-1.5 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-400 hover:text-rose-600 rounded-lg transition-all active:scale-95"
                                                    >
                                                        <Power className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* ── CAMPAIGN TAB ── */}
                {activeTab === 'campaign' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            
                            {/* Left Composer Area */}
                            <div className="space-y-6">
                                {/* Contacts Parser card */}
                                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                                    <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <div className="flex items-center gap-2">
                                            <Users className="w-4.5 h-4.5 text-teal-600" />
                                            <h3 className="font-bold text-slate-800 text-sm">{t.campaign.contactsLabel}</h3>
                                        </div>
                                        {getParsedRecipients.length > 0 && (
                                            <span className="text-[10px] font-extrabold uppercase px-2.5 py-1 bg-teal-50 border border-teal-200 text-teal-700 rounded-full tracking-wider">
                                                {getParsedRecipients.length} {t.campaign.parsedContacts}
                                            </span>
                                        )}
                                    </div>

                                    <textarea 
                                        rows={6}
                                        value={contactsText}
                                        onChange={e => setContactsText(e.target.value)}
                                        placeholder={t.campaign.contactsPlaceholder}
                                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl p-4 outline-none transition-all resize-none font-mono"
                                    />
                                    
                                    {/* Parsed recipients visual grid preview */}
                                    {getParsedRecipients.length > 0 && (
                                        <div className="max-h-36 overflow-y-auto border border-slate-100 rounded-xl divide-y divide-slate-50">
                                            {getParsedRecipients.slice(0, 10).map((c, idx) => (
                                                <div key={idx} className="p-2.5 flex items-center justify-between text-[11px] font-medium text-slate-600 bg-slate-50/50">
                                                    <span className="font-mono text-slate-700 font-bold">{c.phone}</span>
                                                    <span className="truncate max-w-[120px]">{c.name || '—'}</span>
                                                    <span className="truncate max-w-[120px] text-slate-400">{c.company || '—'}</span>
                                                </div>
                                            ))}
                                            {getParsedRecipients.length > 10 && (
                                                <div className="p-2 text-center text-[10px] text-slate-400 font-bold">
                                                    + {getParsedRecipients.length - 10} more recipients
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Message editor card */}
                                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                                    <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                                        <MessageSquare className="w-4.5 h-4.5 text-teal-600" />
                                        <h3 className="font-bold text-slate-800 text-sm">{t.campaign.messageLabel}</h3>
                                    </div>

                                    {/* Personalization key injectors */}
                                    <div className="space-y-1.5">
                                        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{t.campaign.personalizationTags}</span>
                                        <div className="flex flex-wrap gap-2">
                                            {['{name}', '{phone}', '{company}'].map(tag => (
                                                <button 
                                                    key={tag}
                                                    onClick={() => insertTag(tag)}
                                                    className="px-2.5 py-1 text-[10px] font-bold text-teal-700 border border-teal-200 bg-teal-50/50 hover:bg-teal-100/50 transition-all rounded-md"
                                                >
                                                    {tag}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <textarea 
                                        rows={6}
                                        value={messageText}
                                        onChange={e => setMessageText(e.target.value)}
                                        placeholder={t.campaign.messagePlaceholder}
                                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl p-4 outline-none transition-all resize-none leading-relaxed"
                                    />
                                </div>
                            </div>

                            {/* Right Attachment & safety configuration Column */}
                            <div className="space-y-6">
                                {/* Attachment Options card */}
                                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                                    <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                                        <Sparkles className="w-4.5 h-4.5 text-teal-600" />
                                        <h3 className="font-bold text-slate-800 text-sm">{t.campaign.attachmentLabel}</h3>
                                    </div>

                                    <div className="flex border border-slate-200 rounded-xl overflow-hidden p-1 gap-1">
                                        {(['none', 'media', 'vcard'] as const).map(mode => (
                                            <button 
                                                key={mode}
                                                type="button"
                                                onClick={() => setAttachmentMode(mode)}
                                                className={`flex-1 py-1.5 rounded-lg text-xs font-bold transition-all ${attachmentMode === mode ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                            >
                                                {t.campaign.attachmentModes[mode]}
                                            </button>
                                        ))}
                                    </div>

                                    {attachmentMode === 'media' && (
                                        <div className="space-y-2.5 animate-in slide-in-from-top-2 duration-300">
                                            <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">{t.campaign.attachmentUrl}</label>
                                            <input 
                                                type="url"
                                                value={attachmentUrl}
                                                onChange={e => setAttachmentUrl(e.target.value)}
                                                placeholder={t.campaign.attachmentUrlPlaceholder}
                                                className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all"
                                            />
                                        </div>
                                    )}

                                    {attachmentMode === 'vcard' && (
                                        <div className="space-y-3.5 animate-in slide-in-from-top-2 duration-300">
                                            <div className="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{t.campaign.vcardName}</label>
                                                    <input 
                                                        type="text"
                                                        value={vcardName}
                                                        onChange={e => setVcardName(e.target.value)}
                                                        placeholder="John Doe"
                                                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-3 py-2 outline-none transition-all"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{t.campaign.vcardPhone}</label>
                                                    <input 
                                                        type="text"
                                                        value={vcardPhone}
                                                        onChange={e => setVcardPhone(e.target.value)}
                                                        placeholder="+123456789"
                                                        className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-3 py-2 outline-none transition-all"
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{t.campaign.vcardCompany}</label>
                                                <input 
                                                    type="text"
                                                    value={vcardCompany}
                                                    onChange={e => setVcardCompany(e.target.value)}
                                                    placeholder="Acme Corp"
                                                    className="w-full text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-3 py-2 outline-none transition-all"
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Safety configuration card */}
                                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                                    <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                                        <ShieldCheck className="w-4.5 h-4.5 text-teal-600" />
                                        <h3 className="font-bold text-slate-800 text-sm">{t.campaign.safetyLabel}</h3>
                                    </div>

                                    {/* Min/Max WPM speed sliders */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="font-semibold text-slate-500">{t.campaign.typingSpeed}</span>
                                            <span className="font-black text-slate-700">{minWpm} - {maxWpm} WPM</span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <input 
                                                type="range"
                                                min={30}
                                                max={60}
                                                value={minWpm}
                                                onChange={e => setMinWpm(Number(e.target.value))}
                                                className="h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-teal-600"
                                            />
                                            <input 
                                                type="range"
                                                min={61}
                                                max={100}
                                                value={maxWpm}
                                                onChange={e => setMaxWpm(Number(e.target.value))}
                                                className="h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-teal-600"
                                            />
                                        </div>
                                    </div>

                                    {/* Typo Correction percentage slider */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="font-semibold text-slate-500">{t.campaign.typoChance}</span>
                                            <span className="font-black text-slate-700">{typoChance}%</span>
                                        </div>
                                        <input 
                                            type="range"
                                            min={0}
                                            max={15}
                                            value={typoChance}
                                            onChange={e => setTypoChance(Number(e.target.value))}
                                            className="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-teal-600"
                                        />
                                    </div>

                                    {/* Anti ban toggle switches */}
                                    <div className="space-y-3.5 pt-2">
                                        <div className="flex items-center justify-between">
                                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.useSynonyms}</label>
                                            <input 
                                                type="checkbox"
                                                checked={useSynonyms}
                                                onChange={e => setUseSynonyms(e.target.checked)}
                                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                                            />
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.bellCurve}</label>
                                            <input 
                                                type="checkbox"
                                                checked={bellCurve}
                                                onChange={e => setBellCurve(e.target.checked)}
                                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                                            />
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.trackDelivery}</label>
                                            <input 
                                                type="checkbox"
                                                checked={trackDelivery}
                                                onChange={e => setTrackDelivery(e.target.checked)}
                                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                                            />
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <label className="text-xs font-semibold text-slate-600 select-none">{t.campaign.stopOnBlock}</label>
                                            <input 
                                                type="checkbox"
                                                checked={stopOnBlock}
                                                onChange={e => setStopOnBlock(e.target.checked)}
                                                className="w-4 h-4 text-teal-650 accent-teal-600 border-slate-350 rounded focus:ring-teal-500"
                                            />
                                        </div>
                                    </div>

                                    {stopOnBlock && (
                                        <div className="space-y-2 pt-1 animate-in slide-in-from-top-1 duration-200">
                                            <div className="flex items-center justify-between text-[11px]">
                                                <span className="font-semibold text-slate-450">{t.campaign.maxBlockRate}</span>
                                                <span className="font-black text-rose-600">{maxBlockRate}%</span>
                                            </div>
                                            <input 
                                                type="range"
                                                min={2}
                                                max={20}
                                                value={maxBlockRate}
                                                onChange={e => setMaxBlockRate(Number(e.target.value))}
                                                className="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-rose-600"
                                            />
                                        </div>
                                    )}
                                </div>

                                {/* Select dispatch accounts configuration */}
                                <div className="space-y-4">
                                    <div className="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                                        <input 
                                            type="text"
                                            value={campaignName}
                                            onChange={e => setCampaignName(e.target.value)}
                                            placeholder="Enter Campaign Identifier Name"
                                            className="flex-1 text-xs border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none transition-all bg-white"
                                        />
                                        <select 
                                            value={selectedAccount}
                                            onChange={e => setSelectedAccount(e.target.value)}
                                            className="text-xs border border-slate-200 rounded-xl px-3 py-2.5 outline-none bg-white font-bold text-slate-600"
                                        >
                                            <option value="">Select Routing Session</option>
                                            {sessions.filter(s => s.state === 'connected').map(s => (
                                                <option key={s.accountId} value={s.accountId}>{s.accountId}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <button 
                                        onClick={handleLaunchCampaign}
                                        disabled={isCampaignRunning}
                                        className="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-teal-650 hover:from-emerald-700 hover:to-teal-700 text-white rounded-2xl text-sm font-extrabold transition-all shadow-md active:scale-95 flex items-center justify-center gap-2.5 disabled:opacity-50"
                                    >
                                        <Send className="w-4 h-4" />
                                        {t.campaign.launchButton}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* ── DELIVERABILITY TAB ── */}
                {activeTab === 'deliverability' && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        {/* Overall campaign progress details card */}
                        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                                <div>
                                    <h3 className="font-bold text-slate-800 text-base">{t.deliverability.campaignProgress}</h3>
                                    <p className="text-xs text-slate-400 mt-1">{t.deliverability.description}</p>
                                </div>

                                {isCampaignRunning && (
                                    <button 
                                        onClick={handleStopCampaign}
                                        className="flex items-center gap-1.5 px-4 py-2 bg-rose-50 border border-rose-200 hover:bg-rose-100/50 text-rose-600 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95 shrink-0"
                                    >
                                        <Square className="w-3.5 h-3.5" />
                                        {t.deliverability.stopCampaign}
                                    </button>
                                )}
                            </div>

                            {/* Dynamic progress bar stats */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-xs font-bold text-slate-500">
                                    <span>{campaignProgress.percent}% Completed</span>
                                    <span>{campaignProgress.sent} / {campaignProgress.total} dispatched</span>
                                </div>
                                <div className="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div 
                                        className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500 ease-out rounded-full"
                                        style={{ width: `${campaignProgress.percent}%` }}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                                <div className="bg-slate-50 border border-slate-100 rounded-xl p-3 flex flex-col justify-center">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-slate-450">Processed</span>
                                    <span className="text-xl font-black text-slate-700 mt-1">{campaignProgress.sent + campaignProgress.failed + campaignProgress.skipped}</span>
                                </div>
                                <div className="bg-emerald-50/50 border border-emerald-100 rounded-xl p-3 flex flex-col justify-center">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Sent</span>
                                    <span className="text-xl font-black text-emerald-700 mt-1">{campaignProgress.sent}</span>
                                </div>
                                <div className="bg-rose-50/50 border border-rose-100 rounded-xl p-3 flex flex-col justify-center">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-rose-600">Blocked</span>
                                    <span className="text-xl font-black text-rose-700 mt-1">{campaignProgress.blocked}</span>
                                </div>
                                <div className="bg-amber-50/50 border border-amber-100 rounded-xl p-3 flex flex-col justify-center">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-amber-600">Skipped/Failed</span>
                                    <span className="text-xl font-black text-amber-700 mt-1">{campaignProgress.failed + campaignProgress.skipped}</span>
                                </div>
                            </div>
                        </div>

                        {/* Deliverability monitor contacts grid list */}
                        <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse text-xs">
                                    <thead>
                                        <tr className="bg-slate-50 border-b border-slate-200 text-slate-450 uppercase font-black tracking-wider">
                                            <th className="px-5 py-3 text-left">{t.deliverability.colPhone}</th>
                                            <th className="px-5 py-3 text-left">{t.deliverability.colName}</th>
                                            <th className="px-5 py-3 text-left">{t.deliverability.colCompany}</th>
                                            <th className="px-5 py-3 text-left">{t.deliverability.colStatus}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {deliverabilityGrid.length === 0 ? (
                                            <tr>
                                                <td colSpan={4} className="py-12 text-center text-slate-400 font-bold">
                                                    No messages sent yet. Create a campaign to start tracking delivery.
                                                </td>
                                            </tr>
                                        ) : (
                                            deliverabilityGrid.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 font-medium">
                                                    <td className="px-5 py-3 font-mono text-slate-700">{row.phone}</td>
                                                    <td className="px-5 py-3 text-slate-650">{row.name || '—'}</td>
                                                    <td className="px-5 py-3 text-slate-400">{row.company || '—'}</td>
                                                    <td className="px-5 py-3">
                                                        <span className={`px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase border ${
                                                            row.status === 'read' ? 'bg-emerald-50 border-emerald-250 text-emerald-700' :
                                                            row.status === 'delivered' ? 'bg-cyan-50 border-cyan-250 text-cyan-700' :
                                                            row.status === 'sent' ? 'bg-blue-50 border-blue-250 text-blue-700' :
                                                            row.status === 'replied' ? 'bg-indigo-50 border-indigo-250 text-indigo-700' :
                                                            row.status === 'blocked' ? 'bg-rose-50 border-rose-250 text-rose-700' :
                                                            row.status === 'failed' ? 'bg-rose-100 border-rose-300 text-rose-800' :
                                                            'bg-slate-50 border-slate-200 text-slate-400'
                                                        }`}>
                                                            {row.status === 'read' ? t.deliverability.statusRead :
                                                             row.status === 'delivered' ? t.deliverability.statusDelivered :
                                                             row.status === 'sent' ? t.deliverability.statusSent :
                                                             row.status === 'replied' ? t.deliverability.statusReplied :
                                                             row.status === 'blocked' ? t.deliverability.statusBlocked :
                                                             row.status === 'failed' ? t.deliverability.statusFailed :
                                                             t.deliverability.statusPending}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* ── SCORECARD TAB ── */}
                {activeTab === 'scorecard' && campaignResult && (
                    <div className="space-y-6 animate-in fade-in duration-300">
                        {/* Overall campaign scorecard summary statistics */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                                <span className="text-[10px] font-black text-slate-400 uppercase tracking-wider">{t.scorecard.totalProcessed}</span>
                                <div className="text-3xl font-black text-slate-800">{campaignResult.total}</div>
                            </div>
                            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                                <span className="text-[10px] font-black text-emerald-500 uppercase tracking-wider">{t.scorecard.sentSuccessfully}</span>
                                <div className="text-3xl font-black text-emerald-600">{campaignResult.sent}</div>
                            </div>
                            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                                <span className="text-[10px] font-black text-amber-500 uppercase tracking-wider">{t.scorecard.failedOrSkipped}</span>
                                <div className="text-3xl font-black text-amber-600">{campaignResult.failed + campaignResult.skipped}</div>
                            </div>
                            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                                <span className="text-[10px] font-black text-rose-500 uppercase tracking-wider">{t.scorecard.blocksReceived}</span>
                                <div className="text-3xl font-black text-rose-600">{campaignResult.blocked}</div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Health grade & block rate diagnostic */}
                            <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-6">
                                <div className="space-y-1">
                                    <h3 className="font-bold text-slate-800 text-sm">{t.scorecard.title}</h3>
                                    <p className="text-[10px] text-slate-400">{t.scorecard.description}</p>
                                </div>

                                <div className="flex flex-col items-center justify-center py-4 space-y-3">
                                    {/* Health badge */}
                                    <div className="w-24 h-24 rounded-full border-4 border-slate-850 flex items-center justify-center text-4xl font-black bg-gradient-to-tr from-slate-50 to-slate-100 text-slate-800 shadow-md">
                                        {campaignResult.healthGrade || 'A+'}
                                    </div>
                                    <div className="text-center">
                                        <span className="text-xs font-black text-slate-500 block">{t.scorecard.trustGrade}</span>
                                        <span className="text-[10px] text-slate-400 font-medium mt-1 inline-block">{t.scorecard.scoreExplanation}</span>
                                    </div>
                                </div>

                                <div className="space-y-3.5 border-t border-slate-100 pt-4">
                                    <div className="flex justify-between text-xs font-semibold">
                                        <span className="text-slate-450">{t.scorecard.blockRate}</span>
                                        <span className="font-black text-rose-600">{campaignResult.blockRate || '0%'}</span>
                                    </div>
                                    <div className="flex justify-between text-xs font-semibold">
                                        <span className="text-slate-450">{t.scorecard.banProbability}</span>
                                        <span className="font-black text-amber-600">{campaignResult.banProbability || 'Low'}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Safe recommendation instruction panel */}
                            <div className="lg:col-span-2 bg-gradient-to-tr from-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-6 text-white shadow-lg flex flex-col justify-between relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-48 h-48 bg-teal-500/10 rounded-full blur-3xl -z-10" />

                                <div className="space-y-4">
                                    <div className="flex items-center gap-2 text-teal-400">
                                        <ShieldCheck className="w-5 h-5" />
                                        <h3 className="font-extrabold tracking-tight text-sm uppercase">{t.scorecard.warmingRecommendation}</h3>
                                    </div>

                                    <div className="space-y-3 leading-relaxed">
                                        {/* Diagnosis output text block */}
                                        <p className="text-xs text-slate-350 font-bold border-l-2 border-teal-500 pl-3">
                                            {campaignResult.healthGrade === 'A+' ? t.scorecard.grades.excellent :
                                             campaignResult.healthGrade === 'B' ? t.scorecard.grades.good :
                                             campaignResult.healthGrade === 'C' ? t.scorecard.grades.warning :
                                             t.scorecard.grades.danger}
                                        </p>
                                        <p className="text-xs text-slate-400">
                                            {campaignResult.recommendation || 'Anti-ban safety score diagnostic is fully optimized. We recommend keeping moderate pacing levels, proxy routing, and using AI message variations regularly for optimal delivery results.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="border-t border-slate-800/80 pt-4 mt-6 flex justify-between items-center text-[10px] font-bold text-slate-500">
                                    <span>Campaign OS v3 Safeguards</span>
                                    <div className="flex items-center gap-1 bg-teal-500/10 text-teal-400 border border-teal-500/20 px-2 py-1 rounded-full uppercase">
                                        <Sparkles className="w-3 h-3" />
                                        <span>AI Warming Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </main>
        </div>
    );
}
