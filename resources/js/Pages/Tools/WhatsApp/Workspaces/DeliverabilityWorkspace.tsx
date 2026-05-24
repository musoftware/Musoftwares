import React, { useState, useEffect } from 'react';
import { 
    Activity, Shield, Flame, Play, RefreshCw, AlertCircle, 
    Settings, CheckCircle2, Terminal, ShieldAlert, Sparkles, HelpCircle 
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';

interface WarmupSession {
    accountId: string;
    displayName: string;
    phoneNumber: string | null;
    state: string;
    warmupActive: boolean;
    trustScore: number;
    messagesCount: number;
}

interface WarmupActivityLog {
    sessionId: string;
    partnerPhone: string;
    text: string;
    direction: 'in' | 'out';
    state: 'starter' | 'chatting' | 'completed';
    timestamp: string;
}

export default function DeliverabilityWorkspace({
    t, locale, callRPC, daemonConnected, sessions: globalSessions, onActivityRef
}: any) {
    const [sessions, setSessions] = useState<WarmupSession[]>([]);
    const [loading, setLoading] = useState(false);
    const [triggering, setTriggering] = useState(false);
    const [intensity, setIntensity] = useState<'low' | 'medium' | 'high'>('medium');
    const [logs, setLogs] = useState<WarmupActivityLog[]>([]);

    const isArabic = locale === 'ar';

    const formatLogMessage = (log: WarmupActivityLog) => {
        const partner = log.partnerPhone || '';
        if (log.sessionId === 'SYSTEM') {
            return log.text;
        }
        if (log.direction === 'out') {
            if (log.state === 'starter') {
                return isArabic 
                    ? `تم بدء محادثة إحماء مع الرقم (${partner})`
                    : `Initiated warm-up dialogue with (${partner})`;
            }
            if (log.state === 'completed') {
                return isArabic
                    ? `اكتملت سلسلة المحادثة مع الرقم (${partner})`
                    : `Completed warm-up dialogue sequence with (${partner})`;
            }
            return isArabic
                ? `تم إرسال رسالة إحماء إلى الرقم (${partner}): "${log.text}"`
                : `Sent warm-up message to (${partner}): "${log.text}"`;
        } else {
            return isArabic
                ? `تم استقبال رد من الرقم (${partner}): "${log.text}"`
                : `Received response from (${partner}): "${log.text}"`;
        }
    };

    const translations = {
        en: {
            title: "Account Health & Safety",
            subtitle: "Prepare your accounts for bulk campaigns by building a secure sending reputation.",
            manualTrigger: "Send Test Warmup Message",
            manualTriggerSub: "Send a quick verification message between your accounts to ensure the warmup system is running.",
            statusActive: "Warming Up",
            statusInactive: "Inactive",
            trustScore: "Reputation Score",
            warmupToggled: "Auto-Warmup",
            messagesWarmed: "Warmup Messages",
            poolDetails: "Account Status",
            noAccounts: "No WhatsApp accounts connected. Connect your accounts to start warming them up.",
            consoleTitle: "Recent Activity",
            consoleSub: "Real-time updates of your warmup messages.",
            consoleEmpty: "No activity yet. Warmup runs automatically in the background.",
            intensityTitle: "Warmup Speed",
            intensitySub: "Adjust how frequently warmup messages are sent to match your account usage.",
            intensityLow: "Safe (For New Accounts)",
            intensityMedium: "Normal (Recommended)",
            intensityHigh: "Fast (For Active Accounts)",
            trustTierExcellent: "Healthy & Ready",
            trustTierGood: "Good Standing",
            trustTierNeedsWarmup: "Needs Warmup",
            connected: "Connected",
            disconnected: "Disconnected"
        },
        ar: {
            title: "صحة وأمان الحسابات",
            subtitle: "جهّز حساباتك للإرسال الجماعي عبر بناء سمعة إرسال آمنة وتجنب الحظر.",
            manualTrigger: "إرسال رسالة إحماء تجريبية",
            manualTriggerSub: "أرسل رسالة تحقق سريعة بين حساباتك للتأكد من عمل نظام الإحماء بشكل صحيح.",
            statusActive: "قيد الإحماء",
            statusInactive: "غير نشط",
            trustScore: "مستوى السمعة",
            warmupToggled: "الإحماء التلقائي",
            messagesWarmed: "رسائل الإحماء",
            poolDetails: "حالة الحسابات",
            noAccounts: "لا توجد حسابات واتساب مرتبطة حالياً. يرجى ربط حساباتك لبدء الإحماء.",
            consoleTitle: "النشاط الأخير",
            consoleSub: "تحديثات مباشرة لرسائل الإحماء الجارية حالياً.",
            consoleEmpty: "لا يوجد نشاط بعد. يعمل نظام الإحماء تلقائياً في الخلفية.",
            intensityTitle: "سرعة الإحماء",
            intensitySub: "اضبط معدل وتكرار رسائل الإحماء لتناسب حجم استخدام حسابك.",
            intensityLow: "آمن (للحسابات الجديدة)",
            intensityMedium: "طبيعي (موصى به)",
            intensityHigh: "سريع (للحسابات النشطة)",
            trustTierExcellent: "نشط وجاهز",
            trustTierGood: "سمعة جيدة",
            trustTierNeedsWarmup: "بحاجة إلى إحماء",
            connected: "متصل",
            disconnected: "غير متصل"
        }
    };

    const text = translations[locale] || translations.en;

    const refreshStatus = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getWarmupStatus');
            if (res && res.sessions) {
                setSessions(res.sessions);
            }
        } catch (err) {
            console.error('getWarmupStatus failed:', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        refreshStatus();
    }, [daemonConnected, globalSessions]);

    // Handle WebSocket live activity stream
    useEffect(() => {
        if (onActivityRef) {
            onActivityRef.current = (data: WarmupActivityLog) => {
                setLogs(prev => [data, ...prev].slice(0, 50));
                // Refresh status metrics dynamically when dialogues start or complete
                if (data.state === 'starter' || data.state === 'completed') {
                    refreshStatus();
                }
            };
        }
        return () => {
            if (onActivityRef) onActivityRef.current = null;
        };
    }, [onActivityRef]);

    const handleToggleWarmup = async (accountId: string, active: boolean) => {
        try {
            await callRPC('toggleWarmup', { accountId, active });
            setSessions(prev => prev.map(s => 
                s.accountId === accountId ? { ...s, warmupActive: active } : s
            ));
        } catch (err) {
            console.error('toggleWarmup failed:', err);
        }
    };

    const handleManualTrigger = async () => {
        const connectedWarmers = sessions.filter(s => s.warmupActive && s.state === 'connected');
        if (connectedWarmers.length < 2) {
            alert(isArabic 
                ? 'مطلوب حسابين نشطين ومتصلين على الأقل في حوض الإحماء لبدء محادثة ثنائية!' 
                : 'At least 2 active connected accounts are required in the warming pool to trigger a chat!'
            );
            return;
        }

        setTriggering(true);
        try {
            await callRPC('triggerManualWarmup', {});
            // Add a mock system log indicating trigger initiated
            const initLog: WarmupActivityLog = {
                sessionId: 'SYSTEM',
                partnerPhone: 'POOL',
                text: isArabic ? 'تم إطلاق دورة إحماء يدوية وتنسيق الحسابات...' : 'Manual warmup pairing sequence successfully dispatched...',
                direction: 'out',
                state: 'starter',
                timestamp: new Date().toISOString()
            };
            setLogs(prev => [initLog, ...prev]);
        } catch (err: any) {
            alert(`Trigger Error: ${err.message}`);
        } finally {
            setTimeout(() => setTriggering(false), 2000);
        }
    };

    // Helper to render health gauge color class
    const getHealthColor = (score: number) => {
        if (score >= 90) return 'text-emerald-500 stroke-emerald-500';
        if (score >= 75) return 'text-teal-500 stroke-teal-500';
        return 'text-amber-500 stroke-amber-500';
    };

    const getHealthBGColor = (score: number) => {
        if (score >= 90) return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        if (score >= 75) return 'bg-teal-500/10 text-teal-500 border-teal-500/20';
        return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
    };

    const getHealthText = (score: number) => {
        if (score >= 90) return text.trustTierExcellent;
        if (score >= 75) return text.trustTierGood;
        return text.trustTierNeedsWarmup;
    };

    return (
        <div className="space-y-8 animate-in fade-in duration-300">
            {/* ── HEADER ── */}
            <div className="relative overflow-hidden bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 text-white rounded-3xl p-6 md:p-8 shadow-2xl border border-teal-900/50">
                {/* Glowing subtle micro-animation background orbits */}
                <div className="absolute top-0 right-0 w-80 h-80 bg-teal-500/10 rounded-full blur-[100px] animate-pulse" />
                <div className="absolute -bottom-10 -left-10 w-60 h-60 bg-emerald-500/10 rounded-full blur-[80px] animate-pulse" />
                
                <div className="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-6 z-10">
                    <div className="space-y-2 max-w-2xl">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 text-teal-400 border border-teal-500/30 text-xs font-semibold">
                            <Sparkles className="w-3.5 h-3.5 animate-spin-slow" />
                            {isArabic ? "أمن وموثوقية الأرقام" : "Anti-Ban Warmup Engine"}
                        </div>
                        <h2 className="text-2xl md:text-3xl font-extrabold tracking-tight">{text.title}</h2>
                        <p className="text-xs md:text-sm text-teal-200/70 leading-relaxed font-medium">{text.subtitle}</p>
                    </div>

                    <div className="shrink-0 flex flex-col items-stretch md:items-end gap-3 w-full md:w-auto">
                        <Button
                            onClick={handleManualTrigger}
                            disabled={triggering || !daemonConnected}
                            className="bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-slate-900 font-bold px-6 py-5 rounded-2xl shadow-lg shadow-teal-500/20 flex items-center justify-center gap-2 border border-teal-400/20 transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                        >
                            <Flame className={`w-4 h-4 ${triggering ? 'animate-bounce' : 'animate-pulse text-amber-950'}`} />
                            <span>{text.manualTrigger}</span>
                        </Button>
                        <p className="text-[10px] text-teal-300/50 text-center md:text-end font-semibold max-w-xs">{text.manualTriggerSub}</p>
                    </div>
                </div>
            </div>

            {/* ── METRICS GRID & CONTROLS ── */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {/* Intensity Controller Card */}
                <div className="lg:col-span-1 bg-white dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-6">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2.5">
                            <div className="p-2 rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400">
                                <Settings className="w-4 h-4" />
                            </div>
                            <h3 className="font-extrabold text-slate-800 dark:text-slate-200 text-sm">{text.intensityTitle}</h3>
                        </div>
                        <p className="text-xs text-slate-400 leading-relaxed mt-1 font-medium">{text.intensitySub}</p>
                    </div>

                    <div className="space-y-2">
                        {[
                            { key: 'low', label: text.intensityLow, desc: isArabic ? "محادثة واحدة كل ساعتين" : "1 chat cycle every 2 hours" },
                            { key: 'medium', label: text.intensityMedium, desc: isArabic ? "محادثة واحدة كل 20 دقيقة" : "1 chat cycle every 20 minutes" },
                            { key: 'high', label: text.intensityHigh, desc: isArabic ? "محادثة واحدة كل 5 دقائق" : "1 chat cycle every 5 minutes" }
                        ].map((level) => (
                            <button
                                key={level.key}
                                onClick={() => setIntensity(level.key as any)}
                                className={`w-full text-start p-3.5 rounded-2xl border transition-all duration-300 flex items-center justify-between ${
                                    intensity === level.key 
                                        ? 'bg-teal-50/50 dark:bg-teal-950/20 border-teal-500/40 text-teal-800 dark:text-teal-400 shadow-sm ring-1 ring-teal-500/20' 
                                        : 'bg-transparent border-slate-100 hover:border-slate-200 dark:border-slate-800/80 text-slate-650 dark:text-slate-400'
                                }`}
                            >
                                <div className="space-y-0.5">
                                    <span className="text-xs font-bold block">{level.label}</span>
                                    <span className="text-[10px] text-slate-400 dark:text-slate-500 block font-medium">{level.desc}</span>
                                </div>
                                <div className={`w-4.5 h-4.5 rounded-full border-2 flex items-center justify-center transition-colors ${
                                    intensity === level.key ? 'border-teal-500 bg-teal-500' : 'border-slate-300 dark:border-slate-700'
                                }`}>
                                    {intensity === level.key && <div className="w-1.5 h-1.5 bg-white rounded-full" />}
                                </div>
                            </button>
                        ))}
                    </div>

                    <div className="bg-slate-50 dark:bg-slate-900/60 rounded-2xl p-4 flex items-start gap-3 border border-slate-100/50 dark:border-slate-800/50">
                        <AlertCircle className="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0 mt-0.5" />
                        <p className="text-[10px] text-slate-500 dark:text-slate-450 leading-relaxed font-semibold">
                            {isArabic 
                                ? "تنبيه: نوصي بوضع الكثافة المتوسطة لبناء سمعة طبيعية وتجنب تنبيه فلاتر واتساب التلقائية."
                                : "Recommendation: Keep warmup on Medium to build a natural conversational reputation gradually without raising filters."
                            }
                        </p>
                    </div>
                </div>

                {/* Warming Pool Status (Sessions list) */}
                <div className="lg:col-span-2 bg-white dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                    <div className="flex items-center justify-between border-b pb-4">
                        <div className="space-y-1">
                            <h3 className="font-extrabold text-slate-800 dark:text-slate-200 text-sm">{text.poolDetails}</h3>
                            <p className="text-xs text-slate-400 font-medium">
                                {isArabic ? `الأرقام المشاركة: ${sessions.length}` : `Participating linked accounts: ${sessions.length}`}
                            </p>
                        </div>
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            onClick={refreshStatus} 
                            disabled={loading}
                            className="rounded-xl h-8 px-2.5 text-xs text-slate-500 hover:text-slate-800 gap-1.5 hover:bg-slate-50"
                        >
                            <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
                            {isArabic ? 'تحديث' : 'Refresh'}
                        </Button>
                    </div>

                    <div className="overflow-y-auto max-h-[320px] pr-1 space-y-3 flex-1 min-h-[220px]">
                        {sessions.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-center p-6 space-y-3">
                                <ShieldAlert className="w-10 h-10 text-slate-350 dark:text-slate-700 stroke-[1.5]" />
                                <p className="text-xs font-semibold text-slate-400 max-w-xs">{text.noAccounts}</p>
                            </div>
                        ) : (
                            sessions.map((session) => (
                                <div 
                                    key={session.accountId}
                                    className="p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/20 hover:bg-slate-50/70 transition-all duration-300 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                                >
                                    <div className="flex items-center gap-4 w-full md:w-auto">
                                        {/* Circular Dial gauge representing Trust score */}
                                        <div className="relative w-12 h-12 shrink-0 flex items-center justify-center">
                                            <svg className="absolute w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                                <path
                                                    className="text-slate-100 dark:text-slate-800 stroke-current"
                                                    strokeWidth="3"
                                                    fill="none"
                                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                />
                                                <path
                                                    className={`transition-all duration-1000 ${getHealthColor(session.trustScore)}`}
                                                    strokeWidth="3.2"
                                                    strokeDasharray={`${session.trustScore}, 100`}
                                                    strokeLinecap="round"
                                                    fill="none"
                                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                />
                                            </svg>
                                            <span className="text-[10px] font-black text-slate-700 dark:text-slate-200 mt-0.5">{session.trustScore}%</span>
                                        </div>

                                        <div className="space-y-1 min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-bold text-xs text-slate-800 dark:text-slate-200 truncate">{session.displayName}</span>
                                                <span className={`px-2 py-0.5 rounded-md text-[8px] font-bold ${
                                                    session.state === 'connected' 
                                                        ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' 
                                                        : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                                                }`}>
                                                    {session.state === 'connected' ? text.connected : text.disconnected}
                                                </span>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-450 font-semibold">
                                                <span>{session.phoneNumber || '—'}</span>
                                                <span className="hidden md:inline">•</span>
                                                <span className="flex items-center gap-1">
                                                    <Flame className="w-3 h-3 text-amber-500" />
                                                    {session.messagesCount} {isArabic ? "رسائل" : "messages"}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-3 md:pt-0">
                                        <div className="flex flex-col items-start md:items-end">
                                            <span className="text-[9px] uppercase font-bold text-slate-400">{text.trustScore}</span>
                                            <span className={`px-2 py-0.5 mt-0.5 rounded-full text-[9px] font-extrabold border ${getHealthBGColor(session.trustScore)}`}>
                                                {getHealthText(session.trustScore)}
                                            </span>
                                        </div>
                                        
                                        <div className="flex items-center gap-2 border-l pl-3 dark:border-slate-800">
                                            <span className="text-[10px] font-bold text-slate-500 hidden md:inline">
                                                {session.warmupActive ? text.statusActive : text.statusInactive}
                                            </span>
                                            <Switch
                                                checked={session.warmupActive}
                                                onCheckedChange={(checked) => handleToggleWarmup(session.accountId, checked)}
                                                className="data-[state=checked]:bg-teal-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>

            {/* ── RECENT WARMING ACTIVITY TIMELINE ── */}
            <div className="bg-white dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col h-[400px]">
                
                <div className="relative flex items-center justify-between border-b pb-4 shrink-0 z-10">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <div className="w-2 h-2 rounded-full bg-teal-500 animate-pulse" />
                            <h3 className="font-extrabold text-slate-800 dark:text-slate-200 text-sm flex items-center gap-2">
                                <Activity className="w-4 h-4 text-teal-500" />
                                {text.consoleTitle}
                            </h3>
                        </div>
                        <p className="text-xs text-slate-450 font-medium">{text.consoleSub}</p>
                    </div>
                    <Badge variant="outline" className="border-teal-500/30 text-teal-600 dark:text-teal-400 text-xs px-2.5 py-0.5 rounded-xl font-semibold">
                        {text.warmupToggled}: {intensity.toUpperCase()}
                    </Badge>
                </div>

                <div className="relative flex-1 overflow-y-auto mt-4 space-y-3.5 pr-2 z-10">
                    {logs.length === 0 ? (
                        <div className="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400 dark:text-slate-650">
                            <Activity className="w-8 h-8 mb-2 stroke-[1.5]" />
                            <p className="text-xs font-medium max-w-xs">{text.consoleEmpty}</p>
                        </div>
                    ) : (
                        logs.map((log, index) => {
                            const date = new Date(log.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                            const isSystem = log.sessionId === 'SYSTEM';

                            return (
                                <div 
                                    key={index}
                                    className={`p-4 rounded-2xl border transition-all duration-300 ${
                                        isSystem 
                                            ? 'bg-amber-500/5 border-amber-500/10 text-amber-800 dark:text-amber-350' 
                                            : log.direction === 'out' 
                                                ? 'bg-teal-500/5 border-teal-500/10 text-teal-800 dark:text-teal-350' 
                                                : 'bg-emerald-500/5 border-emerald-500/10 text-emerald-800 dark:text-emerald-350'
                                    }`}
                                >
                                    <div className="flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 font-bold mb-1.5">
                                        <div className="flex items-center gap-2">
                                            <span className="font-semibold">{date}</span>
                                            {!isSystem && (
                                                <>
                                                    <span>•</span>
                                                    <span>
                                                        {isArabic ? "الحساب" : "Account"}: {log.sessionId}
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                        <span className={`text-[9px] uppercase font-extrabold tracking-wider px-2 py-0.5 rounded-full border ${
                                            log.state === 'starter' ? 'bg-teal-500/10 text-teal-600 border-teal-500/20' :
                                            log.state === 'completed' ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' :
                                            'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700'
                                        }`}>
                                            {log.state === 'starter' ? (isArabic ? 'بدء' : 'Initiated') :
                                             log.state === 'completed' ? (isArabic ? 'اكتمل' : 'Completed') :
                                             (isArabic ? 'محادثة' : 'Chatting')}
                                        </span>
                                    </div>

                                    <div className="flex items-start gap-2.5 text-xs leading-relaxed font-medium">
                                        <span className="flex-1 break-words">
                                            {formatLogMessage(log)}
                                        </span>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </div>
    );
}
