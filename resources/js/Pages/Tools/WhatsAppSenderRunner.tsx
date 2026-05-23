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
import InboxWorkspace from './WhatsApp/Workspaces/InboxWorkspace';
import AutoReplyWorkspace from './WhatsApp/Workspaces/AutoReplyWorkspace';
import ContactsWorkspace from './WhatsApp/Workspaces/ContactsWorkspace';
import DashboardWorkspace from './WhatsApp/Workspaces/DashboardWorkspace';
import MediaLibraryWorkspace from './WhatsApp/Workspaces/MediaLibraryWorkspace';
import BroadcastListsWorkspace from './WhatsApp/Workspaces/BroadcastListsWorkspace';
import DeliverabilityWorkspace from './WhatsApp/Workspaces/DeliverabilityWorkspace';

type TabId = 'accounts' | 'campaign' | 'groups' | 'group-campaign' | 'history' | 'report' | 'templates' | 'inbox' | 'auto-reply' | 'contacts' | 'dashboard' | 'media' | 'broadcast' | 'deliverability';

const translations = {
    en: {
        title: "WhatsApp Sender",
        subtitle: "Campaign Engine & Automation",
        language: "العربية",
        connected: "Runtime Connected",
        disconnected: "Runtime Offline",
        tabs: {
            dashboard: "Dashboard",
            accounts: "WA Accounts",
            campaign: "New Campaign",
            "group-campaign": "Group Campaign",
            templates: "Templates",
            inbox: "Inbox",
            groups: "My Groups",
            history: "Campaigns",
            report: "Report",
            "auto-reply": "Auto-Reply",
            contacts: "Contacts",
            media: "Media Library",
            broadcast: "Direct Send Lists",
            deliverability: "Warmup & Health",
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
            trustScore: "Health Score",
            deleteSession: "Delete Account",
            rename: "Rename Session",
            reconnect: "Reconnect",
            checkStatus: "Check Status",
            checkStatusResult: "Status",
            showPhoto: "Profile Photo",
            photoOnlyConnected: "Only available when connected"
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
        inbox: {
            title: "Inbox",
            searchPlaceholder: "Search conversations...",
            emptyStateTitle: "No conversations yet",
            emptyStateSub: "Send a campaign and wait for replies",
            noMessages: "No messages in this conversation",
            typePlaceholder: "Type a message...",
            unreadBadge: "unread",
            detailsTitle: "WhatsApp Inbox",
            detailsSub: "Select a conversation to view messages and reply directly to your customers.",
            noMessagesYet: "No messages yet",
            sending: "Sending...",
            replyFailed: "Failed to send: "
        },
        groups: {
            title: "Groups Management",
            subtitle: "Manage, create, and add members to groups.",
            noAccountTitle: "No Account Selected",
            noAccountSub: "Please connect and select a WhatsApp account from the Accounts tab first.",
            createNew: "Create New Group",
            groupName: "Group Name",
            groupNamePlaceholder: "My Awesome Group",
            participants: "Initial Participants (Comma/Newline separated)",
            participantsPlaceholder: "20101234567, 20109876543",
            createBtn: "Create Group",
            bulkAdd: "Bulk Add Members",
            selectGroup: "Select Target Group",
            chooseGroupPlaceholder: "Choose a group...",
            newMembers: "New Members (Comma/Newline separated)",
            newMembersPlaceholder: "20101234567, 20109876543",
            addBtn: "Add Members",
            yourGroups: "Your Groups",
            noGroups: "No groups found for this account.",
            createSuccess: "Group created successfully!",
            addSuccess: "Members added successfully!",
            selectAccountError: "Select an account first",
            nameAndParticipantError: "Name and at least 1 participant required",
            selectAccountGroupError: "Select account and group",
            noParticipantsError: "No participants provided"
        },
        groupCampaign: {
            title: "Group Campaign",
            subtitle: "Create a WhatsApp group, add contacts, send a message — all in one flow.",
            step1: "Account & Group",
            step2: "Add Contacts",
            step3: "Compose Message",
            step4: "Launch",
            accountLabel: "WhatsApp Account",
            noAccountWarning: "No connected accounts. Please connect a WhatsApp account first.",
            groupNameLabel: "Group Name",
            groupNamePlaceholder: "e.g. VIP Customers Q2 2025",
            deleteAfterLabel: "Delete group after sending",
            deleteAfterSub: "The bot will leave the group automatically once the message is delivered.",
            contactsLabel: "Contact List",
            validContacts: "valid contacts",
            contactsPlaceholder: "One per line — phone,name,company format:\n966501234567,Ahmed Al-Rashid,Aramco\n966507654321,Fatima,\n971501234567",
            limitWarning: "WhatsApp limits groups to 1024 participants.",
            moreContacts: "more",
            msgTypeLabel: "Message Type",
            mediaUrlLabel: "Media URL",
            captionLabel: "Message / Caption",
            captionPlaceholder: "Hello group members! We have an exclusive offer for you…",
            summaryTitle: "Campaign Summary",
            colAccount: "Account",
            colGroupName: "Group Name",
            colRecipients: "Recipients",
            colMediaType: "Media Type",
            colDeleteAfter: "Delete Group After",
            yes: "Yes",
            no: "No",
            msgPreview: "Message Preview",
            backBtn: "Back",
            nextBtn: "Next",
            launchingBtn: "Launching…",
            launchBtn: "Launch Group Campaign",
            launchedTitle: "Group Campaign Launched!",
            launchedSub: "Creating group and sending to contacts…",
            startAnotherBtn: "Start Another Group Campaign"
        },
        templates: {
            title: "Message Templates",
            subtitle: "Save reusable messages and media — load them instantly when creating campaigns.",
            newBtn: "New Template",
            searchPlaceholder: "Search templates by name, content, or tag…",
            emptyTitle: "No Templates Yet",
            emptySub: "Create your first template to speed up campaign creation.",
            createFirstBtn: "Create First Template",
            editTitle: "Edit Template",
            newTemplateTitle: "New Template",
            nameLabel: "Template Name",
            namePlaceholder: "e.g. Welcome Message",
            partsLabel: "Message Parts",
            addPart: "Choose file",
            uploading: "Uploading...",
            change: "Change",
            orUrl: "Or paste URL here...",
            captionPlaceholder: "Caption for this (optional)",
            tagsLabel: "Tags (comma-separated)",
            tagsPlaceholder: "promo, welcome, arabic",
            cancel: "Cancel",
            saving: "Saving...",
            saveBtn: "Save Template",
            part: "part",
            parts: "parts",
            deleteConfirm: "Delete this template?",
            nameRequiredError: "Template name is required",
            partRequiredError: "Add at least one message or media part",
            partTypes: {
                text: "Text",
                image: "Image",
                video: "Video",
                document: "Document",
                audio: "Audio"
            },
            useBtn: "Use Template",
            editBtn: "Edit",
            deleteBtn: "Delete",
            moveUp: "Move up",
            moveDown: "Move down",
            remove: "Remove",
            writeMessagePlaceholder: "Write your message... Use {name}, {phone}, {company} for variables"
        },
        history: {
            title: "Campaigns",
            subtitle: "Manage all your campaigns — start, pause, stop, or review reports.",
            colCampaign: "Campaign",
            colType: "Type",
            colStatus: "Status",
            colProgress: "Progress",
            colSent: "Sent",
            colFailed: "Failed",
            colAccount: "Account",
            colCreated: "Created",
            colActions: "Actions",
            ready: "Ready",
            running: "Running",
            paused: "Paused",
            stopped: "Stopped",
            completed: "Completed",
            failedStatus: "Failed",
            retryBtn: "Retry",
            nextIn: "next in",
            deleteConfirm: "Delete this campaign? This cannot be undone.",
            emptyTitle: "No campaigns yet",
            emptySub: "Create your first campaign to get started.",
            stopCampaign: "Stop Campaign"
        },
        report: {
            title: "Campaign Report",
            retryAllBtn: "Retry All Failed",
            exportBtn: "Export CSV",
            breakdownTitle: "Delivery Breakdown",
            breakdownSub: "Current status per contact",
            overviewTitle: "Status Overview",
            overviewSub: "Current status count per contact",
            total: "Total",
            sent: "Sent",
            delivered: "Delivered",
            read: "Read",
            replied: "Replied",
            failed: "Failed",
            pending: "Pending",
            tableTitle: "Per-Contact Engagement",
            allStatus: "All Status",
            colPhone: "Phone",
            colName: "Name",
            colStatus: "Status",
            colSent: "Sent At",
            colDelivered: "Delivered At",
            colRead: "Read At",
            colReplied: "Replied At",
            colError: "Error",
            colAction: "Action",
            retrySingleBtn: "Retry",
            resendSingleBtn: "Resend",
            noContactsFilter: "No contacts with status",
            noContactsRecorded: "No contacts recorded for this campaign yet."
        }
    },
    ar: {
        title: "مرسل الواتساب",
        subtitle: "محرك الحملات والأتمتة",
        language: "English",
        connected: "النظام متصل",
        disconnected: "النظام غير متصل",
        tabs: {
            dashboard: "لوحة التحكم",
            accounts: "حسابات الواتساب",
            campaign: "حملة جديدة",
            "group-campaign": "حملة المجموعات",
            templates: "القوالب",
            inbox: "البريد الوارد",
            groups: "مجموعاتي",
            history: "الحملات",
            report: "التقرير",
            "auto-reply": "الرد التلقائي",
            contacts: "جهات الاتصال",
            media: "مكتبة الوسائط",
            broadcast: "قوائم الإرسال المباشر",
            deliverability: "إحماء الحسابات",
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
            trustScore: "درجة الصحة",
            deleteSession: "حذف الحساب",
            rename: "تعديل الاسم",
            reconnect: "إعادة الاتصال",
            checkStatus: "فحص الحالة",
            checkStatusResult: "الحالة",
            showPhoto: "صورة الحساب",
            photoOnlyConnected: "متاح فقط عند الاتصال"
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
        inbox: {
            title: "البريد الوارد",
            searchPlaceholder: "البحث في المحادثات...",
            emptyStateTitle: "لا توجد محادثات بعد",
            emptyStateSub: "أرسل حملة وانتظر الردود لتظهر هنا",
            noMessages: "لا توجد رسائل في هذه المحادثة",
            typePlaceholder: "اكتب رسالة...",
            unreadBadge: "غير مقروءة",
            detailsTitle: "صندوق وارد الواتساب",
            detailsSub: "اختر محادثة لعرض الرسائل والرد مباشرة على عملائك.",
            noMessagesYet: "لا توجد رسائل بعد",
            sending: "جاري الإرسال...",
            replyFailed: "فشل الإرسال: "
        },
        groups: {
            title: "إدارة المجموعات",
            subtitle: "إنشاء المجموعات وإدارتها وإضافة الأعضاء إليها دفعة واحدة.",
            noAccountTitle: "لم يتم اختيار حساب",
            noAccountSub: "يرجى الاتصال واختيار حساب واتساب من علامة تبويب الحسابات أولاً.",
            createNew: "إنشاء مجموعة جديدة",
            groupName: "اسم المجموعة",
            groupNamePlaceholder: "مجموعتي المتميزة",
            participants: "المشاركون الأولون (مفصولين بفاصلة أو سطر جديد)",
            participantsPlaceholder: "20101234567, 20109876543",
            createBtn: "إنشاء المجموعة",
            bulkAdd: "إضافة أعضاء دفعة واحدة",
            selectGroup: "اختر المجموعة المستهدفة",
            chooseGroupPlaceholder: "اختر مجموعة...",
            newMembers: "أعضاء جدد (مفصولين بفاصلة أو سطر جديد)",
            newMembersPlaceholder: "20101234567, 20109876543",
            addBtn: "إضافة الأعضاء",
            yourGroups: "مجموعاتك",
            noGroups: "لم يتم العثور على مجموعات لهذا الحساب.",
            createSuccess: "تم إنشاء المجموعة بنجاح!",
            addSuccess: "تم إضافة الأعضاء بنجاح!",
            selectAccountError: "يرجى تحديد حساب أولاً",
            nameAndParticipantError: "اسم المجموعة ومشارك واحد على الأقل مطلوبان",
            selectAccountGroupError: "يرجى تحديد الحساب والمجموعة المستهدفة",
            noParticipantsError: "لم يتم تقديم مشاركين"
        },
        groupCampaign: {
            title: "حملة المجموعات",
            subtitle: "أنشئ مجموعة واتساب، أضف جهات الاتصال، أرسل رسالتك — كل ذلك في خطوة واحدة متكاملة.",
            step1: "الحساب والمجموعة",
            step2: "إضافة الأرقام",
            step3: "كتابة الرسالة",
            step4: "إطلاق الحملة",
            accountLabel: "حساب الواتساب",
            noAccountWarning: "لا توجد حسابات متصلة. يرجى ربط حساب واتساب أولاً.",
            groupNameLabel: "اسم المجموعة",
            groupNamePlaceholder: "مثال: عملاء VIP الربع الثاني 2025",
            deleteAfterLabel: "حذف المجموعة بعد الإرسال",
            deleteAfterSub: "سيغادر البوت المجموعة تلقائياً بعد تسليم الرسالة.",
            contactsLabel: "قائمة جهات الاتصال",
            validContacts: "أرقام صحيحة",
            contactsPlaceholder: "رقم واحد في كل سطر — بصيغة: الرقم,الاسم,الشركة\n966501234567,أحمد الراشد,أرامكو\n966507654321,فاطمة,\n971501234567",
            limitWarning: "يحد واتساب المجموعات بـ 1024 مشاركاً كحد أقصى.",
            moreContacts: "أرقام أخرى",
            msgTypeLabel: "نوع الرسالة",
            mediaUrlLabel: "رابط الوسائط",
            captionLabel: "الرسالة / شرح المرفق",
            captionPlaceholder: "مرحباً بأعضاء المجموعة! لدينا عرض حصري لكم...",
            summaryTitle: "ملخص الحملة",
            colAccount: "الحساب المستخدم",
            colGroupName: "اسم المجموعة",
            colRecipients: "المستلمون",
            colMediaType: "نوع الوسائط",
            colDeleteAfter: "حذف المجموعة بعد الإرسال",
            yes: "نعم",
            no: "لا",
            msgPreview: "معاينة الرسالة",
            backBtn: "الخلف",
            nextBtn: "التالي",
            launchingBtn: "جاري الإطلاق...",
            launchBtn: "إطلاق حملة المجموعات",
            launchedTitle: "تم إطلاق حملة المجموعات بنجاح!",
            launchedSub: "جاري إنشاء المجموعة وإرسال الرسائل إلى جهات الاتصال...",
            startAnotherBtn: "بدء حملة مجموعات أخرى"
        },
        templates: {
            title: "قوالب الرسائل",
            subtitle: "احفظ الرسائل والوسائط لإعادة استخدامها وتحميلها فوراً عند إنشاء الحملات.",
            newBtn: "قالب جديد",
            searchPlaceholder: "البحث في القوالب بالاسم، المحتوى، أو الوسم...",
            emptyTitle: "لا توجد قوالب بعد",
            emptySub: "أنشئ قالبك الأول لتسريع عملية إطلاق الحملات مستقبلاً.",
            createFirstBtn: "أنشئ قالبك الأول",
            editTitle: "تعديل القالب",
            newTemplateTitle: "قالب جديد",
            nameLabel: "اسم القالب",
            namePlaceholder: "مثال: رسالة الترحيب بالعملاء",
            partsLabel: "أجزاء الرسالة",
            addPart: "اختر ملفاً",
            uploading: "جاري الرفع...",
            change: "تغيير",
            orUrl: "أو الصق الرابط هنا...",
            captionPlaceholder: "شرح توضيحي للمرفق (اختياري)",
            tagsLabel: "الوسوم (مفصولة بفاصلة)",
            tagsPlaceholder: "عرض، ترحيب، عربي",
            cancel: "إلغاء",
            saving: "جاري الحفظ...",
            saveBtn: "حفظ القالب",
            part: "جزء",
            parts: "أجزاء",
            deleteConfirm: "هل أنت متأكد من حذف هذا القالب؟",
            nameRequiredError: "اسم القالب مطلوب",
            partRequiredError: "يرجى إضافة جزء نصي أو وسائط واحد على الأقل",
            partTypes: {
                text: "نص",
                image: "صورة",
                video: "فيديو",
                document: "مستند",
                audio: "صوت"
            },
            useBtn: "استخدام القالب",
            editBtn: "تعديل",
            deleteBtn: "حذف",
            moveUp: "نقل للأعلى",
            moveDown: "نقل للأسفل",
            remove: "إزالة",
            writeMessagePlaceholder: "اكتب رسالتك... استخدم {name}، {phone}، {company} للمتغيرات"
        },
        history: {
            title: "الحملات الإعلانية",
            subtitle: "إدارة كافة حملاتك — تشغيل، إيقاف مؤقت، إيقاف نهائي، أو مراجعة التقارير بالتفصيل.",
            colCampaign: "الحملة",
            colType: "النوع",
            colStatus: "الحالة",
            colProgress: "التقدم",
            colSent: "الناجح",
            colFailed: "الفاشل",
            colAccount: "الحساب",
            colCreated: "تاريخ الإنشاء",
            colActions: "الإجراءات",
            ready: "جاهزة",
            running: "جاري الإرسال",
            paused: "موقوفة مؤقتاً",
            stopped: "موقوفة",
            completed: "مكتملة",
            failedStatus: "فاشلة",
            retryBtn: "إعادة المحاولة",
            nextIn: "الإرسال القادم خلال",
            deleteConfirm: "هل أنت متأكد من حذف هذه الحملة؟ لا يمكن التراجع عن هذا الإجراء.",
            emptyTitle: "لا توجد حملات بعد",
            emptySub: "أنشئ حملتك الأولى للبدء في الإرسال والوصول لعملائك.",
            stopCampaign: "إيقاف الحملة"
        },
        report: {
            title: "تقرير الحملة التفصيلي",
            retryAllBtn: "إعادة محاولة كافة الأرقام الفاشلة",
            exportBtn: "تصدير كملف Excel CSV",
            breakdownTitle: "تحليل عمليات التسليم",
            breakdownSub: "الحالة التفصيلية لكل جهة اتصال",
            overviewTitle: "نظرة عامة على الحالات",
            overviewSub: "مجموع أعداد الحالات لكل جهة اتصال",
            total: "الإجمالي",
            sent: "تم الإرسال",
            delivered: "تم التسليم",
            read: "تمت القراءة",
            replied: "تم الرد",
            failed: "فشل الإرسال",
            pending: "قيد الانتظار",
            tableTitle: "مستوى تفاعل جهات الاتصال",
            allStatus: "كافة الحالات",
            colPhone: "رقم الهاتف",
            colName: "الاسم",
            colStatus: "الحالة",
            colSent: "تاريخ الإرسال",
            colDelivered: "تاريخ التسليم",
            colRead: "تاريخ القراءة",
            colReplied: "تاريخ الرد",
            colError: "سبب الفشل",
            colAction: "الإجراء",
            retrySingleBtn: "إعادة إرسال",
            resendSingleBtn: "إرسال مجدداً",
            noContactsFilter: "لا توجد جهات اتصال بحالة",
            noContactsRecorded: "لا توجد أرقام مسجلة في هذه الحملة حتى الآن."
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
    const [locale, setLocale]   = useState<'en' | 'ar'>(() => {
        if (typeof window !== 'undefined') {
            const saved = window.localStorage.getItem('whatsapp_sender_locale');
            if (saved === 'en' || saved === 'ar') return saved;
        }
        return 'en';
    });

    useEffect(() => {
        if (typeof window !== 'undefined') {
            window.localStorage.setItem('whatsapp_sender_locale', locale);
        }
    }, [locale]);
    
    // Initialize state from URL
    const [activeTab, setActiveTab] = useState<TabId>(() => {
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            const path = params.get('path') as TabId;
            if (path && ['dashboard', 'accounts', 'campaign', 'groups', 'group-campaign', 'history', 'report', 'templates', 'inbox', 'auto-reply', 'contacts', 'media', 'broadcast', 'deliverability'].includes(path)) {
                return path;
            }
        }
        return 'dashboard';
    });
    
    const [reportCampaignId, setReportCampaignId] = useState<string | null>(() => {
        if (typeof window !== 'undefined') return new URLSearchParams(window.location.search).get('reportId');
        return null;
    });
    
    const [reportCampaignName, setReportCampaignName] = useState<string | null>(() => {
        if (typeof window !== 'undefined') return new URLSearchParams(window.location.search).get('reportName');
        return null;
    });
    const [followUpData, setFollowUpData] = useState<any>(null);

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
    const [initialEditTemplateId, setInitialEditTemplateId] = useState<string | null>(null);

    const handleEditTemplate = (templateId: string) => {
        setInitialEditTemplateId(templateId);
        setActiveTab('templates');
    };
    
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
    const [campaignDelays, setCampaignDelays]        = useState<Record<string, number>>({});  // campaignId → delaySec
    const runningCampaignsCount = Object.keys(activeCampaigns).length;
    const [unreadInboxCount, setUnreadInboxCount] = useState(0);
    const inboxNewMessageCallbackRef = useRef<((data: any) => void) | null>(null);
    const contactValidationCallbackRef = useRef<((event: string, data: any) => void) | null>(null);
    const warmupActivityCallbackRef = useRef<((data: any) => void) | null>(null);

    const t = translations[locale];

    // ── Broadcast event handler ───────────────────────────────────────────────

    const onBroadcast = (event: string, data: any) => {
        console.log('[Broadcast]', event, data);
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

        if (event === 'whatsapp.campaign.delay') {
            // Show actual inter-message delay in the campaign list
            setCampaignDelays(prev => ({ ...prev, [data.campaignId]: data.delaySec }));
            // Clear after the delay has elapsed so it doesn't linger
            setTimeout(() => {
                setCampaignDelays(prev => { const n = { ...prev }; delete n[data.campaignId]; return n; });
            }, (data.delayMs || 5000) + 500);
        }

        if (event === 'whatsapp.campaign.completed' || event === 'whatsapp.campaign.stopped' || event === 'whatsapp.campaign.failed') {
            setActiveCampaigns(prev => {
                const next = { ...prev };
                delete next[data.campaignId];
                return next;
            });
            setCampaignDelays(prev => { const n = { ...prev }; delete n[data.campaignId]; return n; });
            setIsCampaignRunning(false);
        }

        // Real-time inbox
        if (event === 'whatsapp.inbox.new_message') {
            setUnreadInboxCount(prev => prev + 1);
            if (inboxNewMessageCallbackRef.current) {
                inboxNewMessageCallbackRef.current(data);
            }
        }

        // Real-time warmup activity
        if (event === 'whatsapp.warmup.activity') {
            if (warmupActivityCallbackRef.current) {
                warmupActivityCallbackRef.current(data);
            }
        }

        // Contact validation progress
        if (event === 'whatsapp.contacts.validation_progress' || event === 'whatsapp.contacts.validation_complete') {
            if (contactValidationCallbackRef.current) {
                contactValidationCallbackRef.current(event, data);
            }
        }
    };

    const { connected: daemonConnected, callRPC } = useRuntimeWS(pluginSlug || 'whatsapp-sender', onBroadcast);

    // ── Session & Template management ────────────────────────────────────────────────────

    useEffect(() => { 
        if (daemonConnected) {
            fetchSessions();
            fetchTemplates();
            // Fetch initial unread count
            callRPC('getUnreadCount', {}).then((res: any) => setUnreadInboxCount(res?.count || 0)).catch(() => {});
        }
    }, [daemonConnected]);

    // Poll unread inbox count
    useEffect(() => {
        if (!daemonConnected) return;
        const interval = setInterval(() => {
            callRPC('getUnreadCount', {}).then((res: any) => setUnreadInboxCount(res?.count || 0)).catch(() => {});
        }, 5000);
        return () => clearInterval(interval);
    }, [daemonConnected]);

    useEffect(() => {
        if (daemonConnected && (activeTab === 'campaign' || activeTab === 'templates')) {
            fetchTemplates();
        }
    }, [activeTab, daemonConnected]);

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

    const handleDeleteSession = async (accountId: string) => {
        try {
            await callRPC('deleteSession', { accountId });
            setSessions(prev => prev.filter(s => s.accountId !== accountId));
            if (qrSessionId === accountId) { setActiveQR(null); setQrSessionId(null); }
        } catch (err: any) {
            alert(`Delete Error: ${err.message}`);
        }
    };

    const handleRenameSession = async (accountId: string, newName: string) => {
        try {
            await callRPC('renameSession', { accountId, newName });
            setSessions(prev => prev.map(s => s.accountId === accountId ? { ...s, displayName: newName } : s));
        } catch (err: any) {
            alert(`Rename Error: ${err.message}`);
        }
    };

    const handleCheckStatus = async (accountId: string) => {
        try {
            const res: any = await callRPC('checkSessionStatus', { accountId });
            // Update local state with fresh status
            setSessions(prev => prev.map(s =>
                s.accountId === accountId
                    ? { ...s, state: res.status, phoneNumber: res.phoneNumber || s.phoneNumber }
                    : s
            ));
            return res;
        } catch (err: any) {
            return { status: 'error', phoneNumber: null };
        }
    };

    const handleGetProfilePhoto = async (accountId: string): Promise<string | null> => {
        try {
            const res: any = await callRPC('getProfilePicture', { accountId });
            return res?.url || null;
        } catch {
            return null;
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




    const handleLaunchCampaign = async (dripSteps: any[] = [], scheduleOpts: any = null) => {
        const parsed = parseContacts();
        if (!selectedAccount) return alert(t.campaign.selectAccountError);
        if (parsed.length === 0)  return alert(t.campaign.noContactsError);
        if (!selectedTemplateId) return alert("Please select a template to use for this campaign.");

        const tpl = templates.find(t => t.id === selectedTemplateId);
        if (!tpl) return;

        setIsCampaignRunning(!scheduleOpts?.scheduledAt);
        try {
            // Step 1: Create campaign record via RPC
            const res: any = await callRPC('createCampaign', {
                name:         campaignName.trim() || `Campaign ${new Date().toLocaleDateString()}`,
                accountId:    selectedAccount,
                contactsJson: parsed,
                parts:        tpl.parts || [],
                // Legacy fallback
                message:      tpl.message,
                mediaUrl:     tpl.media_url,
                mediaType:    tpl.media_type,
                type:         'bulk',
                minWpm,
                maxWpm,
                typoChance,
                useSynonyms,
                bellCurve,
                trackDelivery,
                stopOnBlock,
                maxBlockRate,
                scheduledAt:       scheduleOpts?.scheduledAt || null,
                isRecurring:       scheduleOpts?.isRecurring ? 1 : 0,
                recurrencePattern: scheduleOpts?.recurrencePattern || 'none',
                recurrenceDays:    scheduleOpts?.recurrenceDays || '',
                // A/B Testing
                abEnabled:         scheduleOpts?.abEnabled ? 1 : 0,
                abSplitRatio:      scheduleOpts?.abSplitRatio || 50,
                abVariantBMessage: scheduleOpts?.abVariantBMessage || ''
            });

            // Save Drip Steps if any exist (Milestone 3)
            if (dripSteps && dripSteps.length > 0) {
                await callRPC('saveCampaignSteps', { campaignId: res.campaignId, steps: dripSteps });
            }

            // Step 2: Start it ONLY if not scheduled
            if (!scheduleOpts?.scheduledAt) {
                await callRPC('startCampaign', { campaignId: res.campaignId, accountId: selectedAccount });
            }

            // Switch to history tab to monitor
            setActiveTab('history');
        } catch (err: any) {
            alert(`Campaign Error: ${err.message}`);
            setIsCampaignRunning(false);
        }
    };

    const handleSendTestMessage = async (testNumber: string) => {
        if (!selectedAccount) throw new Error(t.campaign.selectAccountError);
        if (!selectedTemplateId) throw new Error("Please select a template first.");

        const tpl = templates.find(t => t.id === selectedTemplateId);
        if (!tpl) throw new Error("Template not found");

        const testCampaignId = `test_${Date.now()}`;
        
        // Step 1: Create test campaign record
        await callRPC('createCampaign', {
            campaignId: testCampaignId,
            name: `Test Send - ${testNumber}`,
            accountId:    selectedAccount,
            contactsJson: [{ phone: testNumber.trim(), name: 'Test Recipient', company: 'Musoftware Co.' }],
            parts:        tpl.parts || [],
            message:      tpl.message,
            mediaUrl:     tpl.media_url,
            mediaType:    tpl.media_type,
            type:         'bulk'
        });

        // Step 2: Start it
        await callRPC('startCampaign', { campaignId: testCampaignId, accountId: selectedAccount });
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
                    unreadInboxCount={unreadInboxCount}
                    selectedAccount={selectedAccount}
                    setSelectedAccount={setSelectedAccount}
                    sessions={sessions}
                />
            }
        >
            {activeTab === 'dashboard' && (
                <DashboardWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    setActiveTab={setActiveTab}
                    selectedAccount={selectedAccount}
                />
            )}

            {activeTab === 'accounts' && (
                <AccountsWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
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
                    handleDeleteSession={handleDeleteSession}
                    handleRenameSession={handleRenameSession}
                    handleCheckStatus={handleCheckStatus}
                    handleGetProfilePhoto={handleGetProfilePhoto}
                />
            )}

            {activeTab === 'campaign' && (
                <CampaignWorkspace
                    t={t}
                    locale={locale}
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
                    handleSendTestMessage={handleSendTestMessage}
                    isCampaignRunning={isCampaignRunning}
                    onEditTemplate={handleEditTemplate}
                    callRPC={callRPC}
                    followUpData={followUpData}
                    clearFollowUpData={() => setFollowUpData(null)}
                />
            )}

            {activeTab === 'group-campaign' && (
                <GroupCampaignWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    sessions={sessions}
                />
            )}

            {activeTab === 'templates' && (
                <TemplatesWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    onUseTemplate={handleUseTemplate}
                    onTemplatesChange={setTemplates}
                    initialEditTemplateId={initialEditTemplateId}
                    setInitialEditTemplateId={setInitialEditTemplateId}
                />
            )}

            {activeTab === 'groups' && (
                <GroupsWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    selectedAccount={selectedAccount}
                    sessions={sessions}
                    daemonConnected={daemonConnected}
                />
            )}

            {activeTab === 'auto-reply' && (
                <AutoReplyWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    selectedAccount={selectedAccount}
                    sessions={sessions}
                    daemonConnected={daemonConnected}
                />
            )}

            {activeTab === 'contacts' && (
                <ContactsWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    validationCallbackRef={contactValidationCallbackRef}
                    sessions={sessions}
                    selectedAccount={selectedAccount}
                />
            )}

            {activeTab === 'media' && (
                <MediaLibraryWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                />
            )}

            {activeTab === 'broadcast' && (
                <BroadcastListsWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    onSendCampaign={(phones: string[]) => {
                        setContactsText(phones.join('\n'));
                        setActiveTab('campaign');
                    }}
                />
            )}

            {activeTab === 'history' && (
                <CampaignsListWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    activeCampaigns={activeCampaigns}
                    campaignDelays={campaignDelays}
                    daemonConnected={daemonConnected}
                    selectedAccount={selectedAccount}
                    onViewReport={(id: string, name: string) => {
                        setReportCampaignId(id);
                        setReportCampaignName(name);
                        setActiveTab('report');
                    }}
                    onCreateCampaign={(data: any) => {
                        setFollowUpData(data);
                        setActiveTab('campaign');
                    }}
                />
            )}


            {activeTab === 'report' && reportCampaignId && (
                <CampaignReportWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    campaignId={reportCampaignId}
                    campaignName={reportCampaignName}
                    daemonConnected={daemonConnected}
                    selectedAccount={selectedAccount}
                    onBack={() => setActiveTab('history')}
                />
            )}

            {activeTab === 'inbox' && (
                <InboxWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    sessions={sessions}
                    selectedAccount={selectedAccount}
                    onNewMessageRef={inboxNewMessageCallbackRef}
                    onUnreadReset={() => {
                        callRPC('getUnreadCount', {}).then((res: any) => setUnreadInboxCount(res?.count || 0)).catch(() => {});
                    }}
                />
            )}

            {activeTab === 'deliverability' && (
                <DeliverabilityWorkspace
                    t={t}
                    locale={locale}
                    callRPC={callRPC}
                    daemonConnected={daemonConnected}
                    sessions={sessions}
                    selectedAccount={selectedAccount}
                    onActivityRef={warmupActivityCallbackRef}
                />
            )}
        </ToolShellLayout>
    );
}
