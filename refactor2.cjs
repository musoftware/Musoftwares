const fs = require('fs');
const file = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/resources/js/Pages/Tools/WhatsAppSenderRunner.tsx';
let content = fs.readFileSync(file, 'utf8');

const newTranslations = `const translations = {
    en: {
        title: "WhatsApp Sender",
        subtitle: "Automated Messaging & Campaign Operations",
        runtimeActive: "System Online",
        runtimeOffline: "System Offline",
        languageToggle: "العربية",
        campaignSetup: "Campaign Details",
        humanizerSettings: "Delivery Speed & Safety",
        connectedAccounts: "WhatsApp Accounts",
        sessionDetails: "Active Connections",
        deliverabilityLogs: "Live Activity",
        parsedPreview: "Contacts Preview",
        scorecardTitle: "Campaign Report",
        sessionName: "Account Name",
        sessionNamePlaceholder: "e.g. Sales Team 1",
        campaignName: "Campaign Name",
        campaignNamePlaceholder: "e.g. Summer Promo 2026",
        contactsInput: "Contacts List",
        contactsPlaceholder: "Paste numbers or CSV here:\\nphone,name\\n201001234567,John\\n201007654321,Jane",
        msgTemplate: "Message Content",
        msgPlaceholder: "Hi {name}! Welcome to {company}.",
        tagsTip: "Click to insert variable:",
        spinSyntaxTip: "Use {word1|word2} for variations.",
        startCampaign: "Start Campaign",
        stopCampaign: "Stop Campaign",
        connectSession: "Connect Account",
        disconnectSession: "Disconnect",
        addingSession: "Connecting...",
        headlessMode: "Run in Background",
        proxyConfig: "Proxy URL (Optional)",
        proxyPlaceholder: "http://user:pass@host:port",
        connectNewAccount: "Link New Account",
        noSessions: "No accounts connected yet. Link an account below.",
        qrAwaiting: "Waiting for QR Scan",
        qrTip: "Open WhatsApp -> Settings -> Linked Devices -> Scan QR.",
        humanPreset: "Sending Speed",
        conservative: "Safe (Slow & Steady)",
        moderate: "Balanced (Recommended)",
        aggressive: "Fast (For trusted accounts)",
        attachmentConfig: "Attachments",
        attachMode: "Attachment Type",
        attachNone: "None",
        attachUrl: "Media Link",
        attachVcard: "Contact Card",
        vcardContactName: "Contact Name",
        vcardPhone: "Phone Number",
        vcardCompany: "Company Name",
        mediaUrlLabel: "Public Link (Image/Video/PDF)",
        mediaUrlPlaceholder: "https://...",
        statusPending: "Pending",
        statusSent: "Sent",
        statusDelivered: "Delivered",
        statusRead: "Read",
        statusReplied: "Replied",
        statusFailed: "Failed",
        statusBlocked: "Action Required",
        scoreTotal: "Total Targets",
        scoreSent: "Sent",
        scoreFailed: "Failed",
        scoreBlocked: "Action Required",
        scoreGrade: "Delivery Health",
        scoreBanProb: "Account Status",
        scoreAdvice: "Recommendation",
        scoreSuccessRate: "Success Rate",
        checkingStatus: "Checking connection...",
        stopWarning: "Stopping will cancel remaining messages.",
        sessionConnected: "Account connected!",
        sessionDisconn: "Account disconnected.",
        trustScore: "Health",
        banned: "Unavailable",
        active: "Active",
        warmupDays: "Warm-up Days",
        routingStrategy: "Account Rotation",
        routeHealthiest: "Smart Routing",
        routeLeastUsed: "Least Used",
        routeRoundRobin: "Equal Distribution",
        activeAccounts: "Selected Accounts",
        selectAtleastOne: "Please select an account first.",
        advancedSettings: "Advanced Settings"
    },
    ar: {
        title: "مرسل الواتساب",
        subtitle: "نظام المراسلة الآلية وإدارة الحملات",
        runtimeActive: "النظام متصل",
        runtimeOffline: "النظام غير متصل",
        languageToggle: "English",
        campaignSetup: "تفاصيل الحملة",
        humanizerSettings: "سرعة وأمان الإرسال",
        connectedAccounts: "حسابات الواتساب",
        sessionDetails: "الاتصالات النشطة",
        deliverabilityLogs: "النشاط المباشر",
        parsedPreview: "معاينة جهات الاتصال",
        scorecardTitle: "تقرير الحملة",
        sessionName: "اسم الحساب",
        sessionNamePlaceholder: "مثال: فريق المبيعات 1",
        campaignName: "اسم الحملة",
        campaignNamePlaceholder: "مثال: عرض الصيف 2026",
        contactsInput: "قائمة الأرقام",
        contactsPlaceholder: "الصق الأرقام هنا:\\nphone,name\\n201001234567,أحمد",
        msgTemplate: "محتوى الرسالة",
        msgPlaceholder: "مرحباً {name}! أهلاً بك في {company}.",
        tagsTip: "انقر لإدراج متغير:",
        spinSyntaxTip: "استخدم {كلمة1|كلمة2} للتنويع.",
        startCampaign: "بدء الحملة",
        stopCampaign: "إيقاف الحملة",
        connectSession: "ربط الحساب",
        disconnectSession: "قطع الاتصال",
        addingSession: "جاري الربط...",
        headlessMode: "تشغيل في الخلفية",
        proxyConfig: "بروكسي (اختياري)",
        proxyPlaceholder: "http://user:pass@host:port",
        connectNewAccount: "ربط حساب جديد",
        noSessions: "لا توجد حسابات مرتبطة. اربط حساباً أدناه.",
        qrAwaiting: "بانتظار مسح الرمز",
        qrTip: "افتح الواتساب -> الأجهزة المرتبطة -> امسح الرمز.",
        humanPreset: "سرعة الإرسال",
        conservative: "آمن (بطيء وثابت)",
        moderate: "متوازن (موصى به)",
        aggressive: "سريع (للحسابات الموثوقة)",
        attachmentConfig: "المرفقات",
        attachMode: "نوع المرفق",
        attachNone: "بدون",
        attachUrl: "رابط وسائط",
        attachVcard: "بطاقة اتصال",
        vcardContactName: "اسم جهة الاتصال",
        vcardPhone: "رقم الهاتف",
        vcardCompany: "اسم الشركة",
        mediaUrlLabel: "رابط الملف العام",
        mediaUrlPlaceholder: "https://...",
        statusPending: "قيد الانتظار",
        statusSent: "تم الإرسال",
        statusDelivered: "تم التسليم",
        statusRead: "تمت القراءة",
        statusReplied: "تم الرد",
        statusFailed: "فشل الإرسال",
        statusBlocked: "إجراء مطلوب",
        scoreTotal: "الإجمالي",
        scoreSent: "تم الإرسال",
        scoreFailed: "فشل",
        scoreBlocked: "إجراء مطلوب",
        scoreGrade: "صحة الإرسال",
        scoreBanProb: "حالة الحساب",
        scoreAdvice: "نصيحة",
        scoreSuccessRate: "نسبة النجاح",
        checkingStatus: "جاري فحص الاتصال...",
        stopWarning: "الإيقاف سيلغي الرسائل المتبقية.",
        sessionConnected: "تم الربط بنجاح!",
        sessionDisconn: "تم قطع الاتصال.",
        trustScore: "الصحة",
        banned: "غير متاح",
        active: "نشط",
        warmupDays: "أيام الإحماء",
        routingStrategy: "مداورة الحسابات",
        routeHealthiest: "توجيه ذكي",
        routeLeastUsed: "الأقل استخداماً",
        routeRoundRobin: "توزيع متساوٍ",
        activeAccounts: "الحسابات المحددة",
        selectAtleastOne: "يرجى تحديد حساب أولاً.",
        advancedSettings: "إعدادات متقدمة"
    }
};`;

content = content.replace(/const translations = \{[\s\S]*?    \}\n\};/, newTranslations);

const oldProxyHeadless = /<div className="grid grid-cols-1 md:grid-cols-2 gap-4\">[\s\S]*?placeholder=\{t\.proxyPlaceholder\}[\s\S]*?<\/div>\s*<\/div>\s*<div className="flex items-center gap-2\">[\s\S]*?<\/div>/;
const newProxyHeadless = `
                                        <div>
                                            <label className="block text-xs font-bold text-slate-700 mb-1.5">{t.sessionName}</label>
                                            <input 
                                                type="text" 
                                                required
                                                value={newSessionName} 
                                                onChange={e => setNewSessionName(e.target.value)}
                                                className="w-full text-xs font-bold border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                                                placeholder={t.sessionNamePlaceholder} 
                                            />
                                        </div>
                                        <details className="group/adv text-xs col-span-1 md:col-span-2">
                                            <summary className="cursor-pointer font-bold text-slate-500 flex items-center gap-1 list-none select-none py-2 outline-none">
                                                <Settings className="w-3 h-3" /> {t.advancedSettings}
                                            </summary>
                                            <div className="pt-3 space-y-3 pl-4 border-l-2 border-slate-100 mt-2">
                                                <div>
                                                    <label className="block text-xs font-bold text-slate-700 mb-1.5">{t.proxyConfig}</label>
                                                    <input 
                                                        type="text" 
                                                        value={newSessionProxy} 
                                                        onChange={e => setNewSessionProxy(e.target.value)}
                                                        className="w-full text-xs font-mono border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                                                        placeholder={t.proxyPlaceholder} 
                                                    />
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <input 
                                                        type="checkbox" 
                                                        id="headless-check"
                                                        checked={newSessionHeadless}
                                                        onChange={e => setNewSessionHeadless(e.target.checked)}
                                                        className="rounded text-emerald-600 focus:ring-emerald-500"
                                                    />
                                                    <label htmlFor="headless-check" className="text-xs font-bold text-slate-700 cursor-pointer select-none">
                                                        {t.headlessMode}
                                                    </label>
                                                </div>
                                            </div>
                                        </details>
`;

content = content.replace(oldProxyHeadless, newProxyHeadless);

// Remove the sliders safely
const startIndex = content.indexOf('{/* Custom granular controls sliders */}');
const endIndex = content.indexOf('{/* Action buttons dispatch */}');

if (startIndex !== -1 && endIndex !== -1) {
    const before = content.substring(0, startIndex);
    const after = content.substring(endIndex);
    // The divs before "Custom granular controls sliders" were:
    // </div> </div> (Closing the preset radios loop and the space-y-5 div, wait no. Let's just look closely)
    content = before + "                            </div>\n                        </div>\n\n                        " + after;
}

fs.writeFileSync(file, content);
console.log('Done refactoring');
