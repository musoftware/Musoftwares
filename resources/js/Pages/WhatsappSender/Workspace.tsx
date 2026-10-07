

import React, { useState, useEffect } from 'react';
import { Head, useForm, router, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlowBuilder from './FlowBuilder';
import CrmChatTab from './Components/CrmChatTab';
import AccountsHealthTab from './Components/AccountsHealthTab';
import AudiencesTab from './Components/AudiencesTab';
import AdPerformanceTab from './Components/AdPerformanceTab';
import BusinessProfileTab from './Components/BusinessProfileTab';
import OnboardingWizard from './Components/OnboardingWizard';
import { AlertTriangle, BarChart3, Check, MessageSquare, Phone, Send, Settings, Sparkles, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';

const TAB_LABEL_KEYS: Record<string, string> = {
    connectors: 'whatsapp.workspace_tab_connectors',
    profile: 'whatsapp.workspace_tab_profile',
    audiences: 'whatsapp.workspace_tab_audiences',
    ad_performance: 'whatsapp.workspace_tab_ad_performance',
    send: 'whatsapp.workspace_tab_send',
    templates: 'whatsapp.workspace_tab_templates',
    groups: 'whatsapp.workspace_tab_groups',
    schedules: 'whatsapp.workspace_tab_schedules',
    logs: 'whatsapp.workspace_tab_logs',
    flows: 'whatsapp.workspace_tab_flows',
    bots: 'whatsapp.workspace_tab_bots',
    subscribers: 'whatsapp.workspace_tab_subscribers',
};

const TAB_ICONS: Record<string, LucideIcon> = {
    connectors: Settings,
    profile: Sparkles,
    audiences: Users,
    ad_performance: BarChart3,
    send: Send,
};

interface Business {
    id: number;
    uuid: string;
    name: string;
    client_name: string | null;
    client_email: string | null;
    client_mobile: string | null;
    client_whatsapp: string | null;
    wallet_balance: string;
    currency: string;
    per_message_fee: string;
    bot_reply_fee: string;
    facebook_client_id?: string | null;
    facebook_client_secret?: string | null;
    is_test_mode?: boolean;
    test_phone_number_id?: string | null;
    test_waba_id?: string | null;
    webhook_verify_token?: string | null;
}

interface Account {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
    display_phone_number?: string | null;
    metadata?: any;
    access_token?: string;
}

interface Bot {
    id: number;
    name: string;
    username: string | null;
    status: string;
}

interface Template {
    id: number;
    name: string;
    category: string;
    language: string;
    components: any[];
    status: string;
}

interface ContactGroup {
    id: number;
    name: string;
    description: string | null;
    contacts_count: number;
}

interface Schedule {
    id: number;
    recipient_phone: string | null;
    channel: string;
    message_type: string;
    message_body: string | null;
    template_name: string | null;
    scheduled_at: string;
    status: string;
    error_message: string | null;
    account?: { name: string } | null;
    telegram_bot?: { name: string } | null;
    group?: { name: string } | null;
}

interface LogEntry {
    id: number;
    recipient_phone: string;
    channel: string;
    cost_charged: string;
    message_type: string;
    message_body: string | null;
    status: string;
    created_at: string;
    account?: { name: string } | null;
    telegram_bot?: { name: string } | null;
}

interface Transaction {
    id: number;
    type: string;
    amount: string;
    balance_after: string;
    description: string;
    created_at: string;
}

interface Props {
    business: Business;
    accounts: Account[];
    bots: Bot[];
    templates: Template[];
    contactGroups: ContactGroup[];
    schedules: Schedule[];
    logs: LogEntry[];
    transactions: Transaction[];
    apiToken: string;
    webhookUrl: string;
    webhookVerifyToken: string;
    facebookLoginUrl: string;
    fbOauthToken?: string | null;
    telegramSubscribers: any[];
    telegramSubscriberGroups: any[];
    flows: any[];
    isAdmin: boolean;
    hasFacebookApp?: boolean;
}

export default function Workspace({
    business,
    accounts,
    bots,
    templates,
    contactGroups,
    schedules,
    logs,
    transactions,
    apiToken,
    webhookUrl,
    webhookVerifyToken,
    facebookLoginUrl,
    fbOauthToken,
    telegramSubscribers,
    telegramSubscriberGroups,
    flows,
    isAdmin,
    hasFacebookApp = true
}: Props) {
    const pageProps = usePage<any>().props;
    const flash = pageProps?.flash;
    const { confirm, confirmDialog } = useConfirm();

    const [activeChannel, setActiveChannel] = useState<'whatsapp' | 'telegram'>('whatsapp');
    const queryTab = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('tab') : null;
    const [activeTab, setActiveTab] = useState<'connectors' | 'profile' | 'audiences' | 'ad_performance' | 'send' | 'templates' | 'groups' | 'schedules' | 'logs' | 'flows' | 'bots' | 'subscribers'>(
        (queryTab && ['connectors', 'profile', 'audiences', 'ad_performance', 'send', 'templates', 'groups', 'schedules', 'logs', 'flows', 'bots', 'subscribers'].includes(queryTab))
            ? (queryTab as any)
            : 'connectors'
    );
    const [selectedGroup, setSelectedGroup] = useState<ContactGroup | null>(null);

    const firstActiveAccount = accounts.find(a => a.status === 'active') || accounts[0];
    const [selectedAccountId, setSelectedAccountId] = useState<number>(firstActiveAccount?.id || 0);

    useEffect(() => {
        if (!selectedAccountId || !accounts.some(a => a.id === selectedAccountId)) {
            const activeAcc = accounts.find(a => a.status === 'active') || accounts[0];
            if (activeAcc) {
                setSelectedAccountId(activeAcc.id);
            }
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [accounts]);

    const [editingFlow, setEditingFlow] = useState<any | null>(null);
    const [isCreatingFlow, setIsCreatingFlow] = useState(false);
    const [selectedSubscriberGroup, setSelectedSubscriberGroup] = useState<any | null>(null);

    const [showEditModal, setShowEditModal] = useState(false);
    const [showAddAccountModal, setShowAddAccountModal] = useState(false);
    const [showEditAccountModal, setShowEditAccountModal] = useState(false);
    const [editingAccount, setEditingAccount] = useState<Account | null>(null);
    const [editingWabaAccountId, setEditingWabaAccountId] = useState<number | null>(null);
    const [tempWabaId, setTempWabaId] = useState('');
    const [testingAccountId, setTestingAccountId] = useState<number | null>(null);
    const [reconnectAccount, setReconnectAccount] = useState<Account | null>(null);
    const [reconnectPin, setReconnectPin] = useState('');
    const [isRequestingCode, setIsRequestingCode] = useState(false);
    const [isRegisteringPin, setIsRegisteringPin] = useState(false);
    const [codeRequested, setCodeRequested] = useState(false);
    const [isWebhookOpen, setIsWebhookOpen] = useState(false);

    const handleTestAccount = (accId: number) => {
        setTestingAccountId(accId);
        router.post(`/whatsapp-sender/accounts/${accId}/test`, {}, {
            onFinish: () => setTestingAccountId(null),
        });
    };

    const accountForm = useForm({
        whatsapp_business_id: business.id,
        name: '',
        phone_number_id: '',
        waba_id: '',
        access_token: '',
    });

    const handleAddAccount = (e: React.FormEvent) => {
        e.preventDefault();
        accountForm.post('/whatsapp-sender/accounts', {
            onSuccess: () => {
                setShowAddAccountModal(false);
                accountForm.reset();
            }
        });
    };

    const editAccountForm = useForm({
        name: '',
        phone_number_id: '',
        waba_id: '',
        access_token: '',
    });

    const openEditAccountModal = (acc: Account) => {
        setEditingAccount(acc);
        editAccountForm.setData({
            name: acc.name || '',
            phone_number_id: acc.phone_number_id || '',
            waba_id: acc.waba_id || '',
            access_token: (acc as any).access_token || '',
        });
        setShowEditAccountModal(true);
    };

    const handleDeleteAccount = (id: number) => {
        router.delete(`/whatsapp-sender/accounts/${id}`, {
            preserveScroll: true,
        });
    };

    const handleUpdateAccount = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingAccount) return;
        editAccountForm.put(`/whatsapp-sender/accounts/${editingAccount.id}`, {
            onSuccess: () => {
                setShowEditAccountModal(false);
                setEditingAccount(null);
                editAccountForm.reset();
            }
        });
    };

    const editForm = useForm({
        name: business.name,
        client_name: business.client_name || '',
        client_email: business.client_email || '',
        client_mobile: business.client_mobile || '',
        client_whatsapp: business.client_whatsapp || '',
        per_message_fee: business.per_message_fee,
        bot_reply_fee: business.bot_reply_fee || '0.0005',
        facebook_client_id: business.facebook_client_id || '',
        facebook_client_secret: business.facebook_client_secret || '',
    });

    const handleEditBusiness = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.put(`/whatsapp-sender/businesses/${business.id}`, {
            onSuccess: () => {
                setShowEditModal(false);
            }
        });
    };

    useEffect(() => {
        editForm.setData({
            name: business.name,
            client_name: business.client_name || '',
            client_email: business.client_email || '',
            client_mobile: business.client_mobile || '',
            client_whatsapp: business.client_whatsapp || '',
            per_message_fee: business.per_message_fee,
            bot_reply_fee: business.bot_reply_fee || '0.0005',
            facebook_client_id: business.facebook_client_id || '',
            facebook_client_secret: business.facebook_client_secret || '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [business]);

    const tgGroupForm = useForm({
        telegram_bot_id: bots[0]?.id || '',
        name: '',
        description: '',
    });
    const handleCreateTgGroup = (e: React.FormEvent) => {
        e.preventDefault();
        tgGroupForm.post('/whatsapp-sender/telegram-subscriber-groups', {
            onSuccess: () => tgGroupForm.reset('name', 'description'),
        });
    };

    const testModeToggleLabel = business.is_test_mode
        ? __('whatsapp.workspace_switch_to_live')
        : __('whatsapp.workspace_switch_to_sandbox');

    // Copy link helper
    const guestLink = `${window.location.origin}/whatsapp-sender/guest/connect/${business.uuid}`;
    const [copied, setCopied] = useState(false);
    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    // 1. Recharge balance form
    const rechargeForm = useForm({
        amount: '10.00',
    });
    const handleRecharge = (e: React.FormEvent) => {
        e.preventDefault();
        rechargeForm.post(`/whatsapp-sender/businesses/${business.id}/recharge`, {
            onSuccess: () => rechargeForm.reset(),
        });
    };

    // 2. Add Telegram Bot form
    const botForm = useForm({
        whatsapp_business_id: business.id,
        token: '',
    });
    const handleAddBot = (e: React.FormEvent) => {
        e.preventDefault();
        botForm.post('/whatsapp-sender/telegram-bots', {
            onSuccess: () => botForm.reset(),
        });
    };

    // 3. Quick Send & Scheduler form
    const defaultTemplate = templates.find(t => t.status === 'APPROVED') || templates[0];
    const sendForm = useForm({
        channel: 'whatsapp',
        whatsapp_account_id: accounts[0]?.id || '',
        telegram_bot_id: bots[0]?.id || '',
        recipient_source: 'single', // single or group
        whatsapp_contact_group_id: contactGroups[0]?.id || '',
        recipient_phone: '',
        message_type: 'text',
        message_body: '',
        template_name: defaultTemplate?.name || '',
        template_language: defaultTemplate?.language || 'en_US',
        template_components: [] as any[],
        is_scheduled: false,
        scheduled_at: '',
    });

    useEffect(() => {
        const approvedTpl = templates.find(t => t.status === 'APPROVED') || templates[0];
        sendForm.setData(data => {
            const currentTpl = templates.find(t => t.name === data.template_name);
            const validName = (currentTpl && currentTpl.status === 'APPROVED') ? currentTpl.name : approvedTpl?.name || '';
            const validLang = (currentTpl && currentTpl.status === 'APPROVED') ? currentTpl.language : approvedTpl?.language || 'en_US';
            return {
                ...data,
                channel: activeChannel,
                whatsapp_account_id: accounts[0]?.id || '',
                telegram_bot_id: bots[0]?.id || '',
                template_name: validName,
                template_language: validLang,
                message_type: activeChannel === 'telegram' ? 'text' : data.message_type
            };
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeChannel, accounts, bots, templates]);

    const [mappedVariables, setMappedVariables] = useState<{ [key: string]: string }>({});

    const selectedTemplate = templates.find(t => t.name === sendForm.data.template_name);
    const bodyText = selectedTemplate?.components?.find((c: any) => c.type === 'BODY')?.text || '';
    const variableCount = (bodyText.match(/\{\{\d+\}\}/g) || []).length;

    const handleSend = (e: React.FormEvent) => {
        e.preventDefault();

        // Package dynamic template components if needed
        let componentsPayload = [] as any[];
        if (sendForm.data.message_type === 'template' && variableCount > 0) {
            const params = Array.from({ length: variableCount }).map((_, i) => ({
                type: 'text',
                text: mappedVariables[`var_${i + 1}`] || '',
            }));
            componentsPayload = [{
                type: 'body',
                parameters: params
            }];
        }

        const endpoint = sendForm.data.is_scheduled
            ? '/whatsapp-sender/schedules'
            : (sendForm.data.recipient_source === 'group' ? '/whatsapp-sender/send-campaign' : '/whatsapp-sender/send');

        const payloadMessageBody = sendForm.data.message_type === 'template'
            ? (bodyText || sendForm.data.template_name || 'Template Message')
            : sendForm.data.message_body;

        router.post(endpoint, {
            ...sendForm.data,
            message_body: payloadMessageBody,
            whatsapp_business_id: business.id,
            template_components: componentsPayload,
        }, {
            onSuccess: () => {
                sendForm.reset('recipient_phone', 'message_body', 'scheduled_at');
                setMappedVariables({});
            }
        });
    };

    // 4. Create Template Form
    const templateForm = useForm({
        whatsapp_business_id: business.id,
        name: '',
        category: 'UTILITY',
        language: 'en_US',
        components: [
            {
                type: 'BODY',
                text: 'Hello {{1}}, welcome to our service.',
            }
        ]
    });
    const handleCreateTemplate = (e: React.FormEvent) => {
        e.preventDefault();
        templateForm.post('/whatsapp-sender/templates', {
            onSuccess: () => templateForm.reset(),
        });
    };

    // 5. Contact Group Form
    const groupForm = useForm({
        name: '',
        description: '',
        whatsapp_business_id: business.id,
    });
    const handleCreateGroup = (e: React.FormEvent) => {
        e.preventDefault();
        groupForm.post('/whatsapp-sender/contact-groups', {
            onSuccess: () => groupForm.reset(),
        });
    };

    // 6. CSV/Text Import Contacts Form
    const importForm = useForm({
        contacts_text: '',
    });
    const handleImportContacts = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedGroup) return;
        importForm.post(`/whatsapp-sender/contact-groups/${selectedGroup.id}/contacts`, {
            onSuccess: () => {
                importForm.reset();
                setSelectedGroup(null);
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('whatsapp.workspace_page_title', { name: business.name })} />

            <div className="flex min-h-[calc(100vh-64px)] bg-zinc-50 dark:bg-zinc-950 font-sans">
                {/* Left Mini Sidebar with Icons only */}
                <div className="w-16 flex flex-col items-center py-6 border-r border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 gap-6 shrink-0 z-10">
                    {/* WhatsApp Icon */}
                    <button
                        onClick={() => {
                            setActiveChannel('whatsapp');
                            setActiveTab('connectors');
                        }}
                        className={`p-2.5 rounded-2xl transition duration-200 relative group border-2 ${activeChannel === 'whatsapp'
                                ? 'border-emerald-500 bg-emerald-500/10 dark:bg-emerald-950/20 shadow-md shadow-emerald-500/10'
                                : 'border-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800'
                            }`}
                        title={__('whatsapp.workspace_whatsapp_hub')}
                        aria-label={__('whatsapp.workspace_whatsapp_hub')}
                    >
                        <svg viewBox="0 0 24 24" width="24" height="24" className="w-6 h-6">
                            <circle cx="12" cy="12" r="12" fill="#25D366" />
                            <path d="M12.012 5.5c-3.585 0-6.5 2.915-6.5 6.5 0 1.144.298 2.257.865 3.242L5.5 18.5l3.429-.9c.945.516 2.012.79 3.083.79 3.585 0 6.5-2.915 6.5-6.5s-2.915-6.5-6.5-6.5zm3.834 8.763c-.168.473-.97.857-1.338.91-.334.05-.765.09-2.32-.54-1.99-.8-3.264-2.812-3.363-2.946-.098-.133-.796-1.062-.796-2.025 0-.963.502-1.435.684-1.624.18-.188.397-.236.53-.236.133 0 .266.002.38.006.122.006.286-.05.447.35.168.412.574 1.402.624 1.504.05.102.083.222.014.358-.067.137-.102.222-.205.34-.103.12-.216.266-.308.358-.103.102-.21.214-.09.42.12.205.53 1.077 1.138 1.617.608.54 1.118.708 1.318.808.2.102.318.082.437-.055.12-.137.502-.587.637-.787.135-.2.268-.17.45-.102.184.068 1.17.55 1.37.646.2.1.336.143.38.222.05.078.05.454-.12.928z" fill="#FFFFFF" />
                        </svg>
                        <span className="absolute left-20 bg-zinc-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-30 shadow-lg">
                            {__('whatsapp.workspace_whatsapp_hub')}
                        </span>
                    </button>

                    {/* Telegram Icon */}
                    <button
                        onClick={() => {
                            setActiveChannel('telegram');
                            setActiveTab('bots');
                        }}
                        className={`p-2.5 rounded-2xl transition duration-200 relative group border-2 ${activeChannel === 'telegram'
                                ? 'border-sky-500 bg-sky-500/10 dark:bg-sky-950/20 shadow-md shadow-sky-500/10'
                                : 'border-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800'
                            }`}
                        title={__('whatsapp.workspace_telegram_hub')}
                        aria-label={__('whatsapp.workspace_telegram_hub')}
                    >
                        <svg viewBox="0 0 24 24" width="24" height="24" className="w-6 h-6">
                            <circle cx="12" cy="12" r="12" fill="#0088CC" />
                            <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.47-.52-.17l-9.49 5.96-4.11-1.28c-.9-.28-.92-.9.19-1.33l16.1-6.2c.74-.27 1.39.17 1.13 1.25l-2.73 12.87c-.2.93-.76 1.16-1.54.73l-4.17-3.07-2.01 1.94c-.22.22-.41.41-.83.41z" fill="#FFFFFF" />
                        </svg>
                        <span className="absolute left-20 bg-zinc-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-30 shadow-lg">
                            {__('whatsapp.workspace_telegram_hub')}
                        </span>
                    </button>
                </div>

                {/* Main panel content */}
                <div className="flex-1 py-8 px-6 space-y-8 overflow-y-auto">
                    {/* Flash Notifications */}
                    {flash?.success && (
                        <div className="bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-medium space-y-3 shadow-sm">
                            <div className="flex items-center justify-between">
                                <span className="font-bold flex items-center gap-2">
                                    <svg className="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {flash.success}
                                </span>
                            </div>
                            {flash?.meta_response && (
                                <div className="mt-2 border-t border-emerald-500/20 pt-2 space-y-1">
                                    <span className="text-xxs font-bold uppercase tracking-wider block text-emerald-700 dark:text-emerald-400">
                                        {__('whatsapp.workspace_meta_raw_response')}
                                    </span>
                                    <pre className="bg-zinc-950 text-emerald-400 text-xs p-3 rounded-xl font-mono overflow-x-auto border border-zinc-800 dir-ltr text-left">
                                        {JSON.stringify(flash.meta_response, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="bg-red-500/10 border border-red-500/30 text-red-800 dark:text-red-300 rounded-2xl p-4 text-sm font-medium space-y-3 shadow-sm">
                            <div className="flex items-center justify-between">
                                <span className="font-bold flex items-center gap-2">
                                    <svg className="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {flash.error}
                                </span>
                            </div>
                            {flash?.meta_response && (
                                <div className="mt-2 border-t border-red-500/20 pt-2 space-y-1">
                                    <span className="text-xxs font-bold uppercase tracking-wider block text-red-700 dark:text-red-400">
                                        {__('whatsapp.workspace_meta_error_payload')}
                                    </span>
                                    <pre className="bg-zinc-950 text-red-400 text-xs p-3 rounded-xl font-mono overflow-x-auto border border-zinc-800 dir-ltr text-left">
                                        {JSON.stringify(flash.meta_response, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Stripe Test Mode Banner */}
                    {business.is_test_mode && (
                        <div className="bg-amber-500/10 border border-amber-500/30 dark:bg-amber-950/40 rounded-2xl p-4 flex items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <span className="p-2 bg-amber-500 text-white rounded-xl font-bold text-xs">{__('whatsapp.workspace_test_mode_badge')}</span>
                                <div>
                                    <h4 className="text-sm font-bold text-amber-800 dark:text-amber-300">{__('whatsapp.workspace_sandbox_active')}</h4>
                                    <p className="text-xs text-amber-700/80 dark:text-amber-400/80 mt-0.5">
                                        {__('whatsapp.workspace_sandbox_desc')}
                                    </p>
                                </div>
                            </div>
                            <Link
                                href={route('whatsapp.meta-app-guide')}
                                className="text-xs text-amber-800 dark:text-amber-300 underline font-medium hover:text-amber-900"
                            >
                                {__('whatsapp.workspace_view_review_guide')} &rarr;
                            </Link>
                        </div>
                    )}

                    {/* Header Information Dashboard Card */}
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{business.name}</h1>
                                <span className="bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 text-xs px-2.5 py-1 rounded-full font-medium border border-emerald-200/50 dark:border-emerald-900/30">
                                    {__('whatsapp.workspace_active_workspace')}
                                </span>

                                 {/* Stripe-style Test/Live Mode Toggle */}
                                 <div className={`flex items-center gap-2 px-3 py-1 rounded-full border transition-all duration-200 ${
                                     business.is_test_mode
                                         ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-300 dark:border-amber-800'
                                         : 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800'
                                 }`}>
                                     <button
                                         type="button"
                                         onClick={() => router.post(route('whatsapp.businesses.toggle-test-mode', business.id))}
                                         className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                             business.is_test_mode ? 'bg-amber-500' : 'bg-emerald-500'
                                         }`}
                                         title={testModeToggleLabel}
                                         aria-label={testModeToggleLabel}
                                     >
                                         <span
                                             className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                                                 business.is_test_mode ? 'translate-x-0' : 'translate-x-4'
                                             }`}
                                         />
                                     </button>
                                     <span className={`text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 ${
                                         business.is_test_mode ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'
                                     }`}>
                                         <span className={`w-1.5 h-1.5 rounded-full ${business.is_test_mode ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse'}`} />
                                         {business.is_test_mode ? __('whatsapp.workspace_test_mode_sandbox') : __('whatsapp.workspace_live_production')}
                                     </span>
                                 </div>

                                <button
                                    onClick={() => setShowEditModal(true)}
                                    className="p-1.5 rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-950 transition duration-200 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center shrink-0"
                                    title={__('whatsapp.workspace_edit_business_settings')}
                                    aria-label={__('whatsapp.workspace_edit_business_settings')}
                                >
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4">
                                        <circle cx="12" cy="12" r="3" />
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                    </svg>
                                </button>
                            </div>
                            <p className="text-zinc-500 dark:text-zinc-400 mt-2 text-sm">
                                {__('whatsapp.workspace_client_label')} <span className="font-semibold text-zinc-700 dark:text-zinc-300">{business.client_name || __('general.n_a')}</span>
                                {business.client_email && ` | ${__('whatsapp.workspace_email_value', { value: business.client_email })}`}
                                {business.client_mobile && ` | ${__('whatsapp.workspace_mobile_value', { value: business.client_mobile })}`}
                                {business.client_whatsapp && ` | ${__('whatsapp.workspace_whatsapp_value', { value: business.client_whatsapp })}`}
                            </p>
                        </div>

                        <div className="flex flex-col md:flex-row items-stretch md:items-center gap-4 w-full md:w-auto">
                            {/* Balance display */}
                            <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-2xl px-5 py-3 text-right">
                                <span className="text-xs text-zinc-500 dark:text-zinc-400 block font-medium">{__('whatsapp.workspace_business_balance')}</span>
                                <span className="text-2xl font-black text-zinc-900 dark:text-zinc-50">
                                    ${parseFloat(business.wallet_balance).toFixed(2)} <span className="text-xs font-normal text-zinc-500">{business.currency}</span>
                                </span>
                            </div>

                            {/* Top-up Form */}
                            <form onSubmit={handleRecharge} className="flex items-center gap-2">
                                <div className="relative">
                                    <span className="absolute left-3 top-2.5 text-zinc-400 text-sm">$</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={rechargeForm.data.amount}
                                        onChange={e => rechargeForm.setData('amount', e.target.value)}
                                        aria-label={__('whatsapp.workspace_recharge_amount')}
                                        className="pl-7 pr-3 py-2 w-24 text-sm bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl focus:ring-1 focus:ring-zinc-900 dark:focus:ring-zinc-200"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={rechargeForm.processing}
                                    className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-sm px-4 py-2 rounded-xl font-medium transition duration-200"
                                >
                                    {__('whatsapp.workspace_recharge')}
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Invite guest link Card */}
                    <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-3xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 className="font-semibold text-zinc-900 dark:text-zinc-100">{__('whatsapp.workspace_guest_link_title')}</h2>
                            <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_guest_link_desc')}</p>
                        </div>
                        <div className="flex items-center gap-2 w-full md:w-auto">
                            <input
                                type="text"
                                readOnly
                                value={guestLink}
                                aria-label={__('whatsapp.workspace_guest_link_title')}
                                className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-xs px-3 py-2.5 rounded-xl w-full md:w-80 text-zinc-600 dark:text-zinc-300"
                            />
                            <button
                                onClick={() => copyToClipboard(guestLink)}
                                className="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/80 px-4 py-2.5 rounded-xl text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition"
                            >
                                {copied ? __('whatsapp.workspace_copied') : __('general.copy')}
                            </button>
                        </div>
                    </div>

                    {/* Tabs selection */}
                    <div className="flex border-b border-zinc-200 dark:border-zinc-800 overflow-x-auto pb-px gap-6">
                        {(activeChannel === 'whatsapp'
                            ? ['connectors', 'profile', 'audiences', 'ad_performance', 'send', 'templates', 'schedules', 'logs', 'flows'] as const
                            : ['bots', 'subscribers', 'send', 'schedules', 'logs', 'flows'] as const
                        ).map(tab => (
                            <button
                                key={tab}
                                onClick={() => setActiveTab(tab as any)}
                                className={`pb-4 text-sm font-semibold tracking-tight whitespace-nowrap border-b-2 transition duration-200 capitalize flex items-center gap-2 cursor-pointer ${activeTab === tab
                                        ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold'
                                        : 'border-transparent text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300'
                                    }`}
                            >
                                {TAB_ICONS[tab] && React.createElement(TAB_ICONS[tab], { className: 'w-4 h-4' })}
                                {__(TAB_LABEL_KEYS[tab])}
                            </button>
                        ))}
                    </div>

                    {/* Tab Contents */}
                    <div>

                        {activeTab === 'connectors' && (
                            <AccountsHealthTab
                                business={business}
                                accounts={accounts}
                                facebookLoginUrl={facebookLoginUrl}
                                hasFacebookApp={hasFacebookApp}
                                onOpenInbox={() => window.open(route('whatsapp.businesses.live-chat', business.id), '_blank')}
                                onAddAccount={() => setShowAddAccountModal(true)}
                                onEditAccount={openEditAccountModal}
                                onTestAccount={handleTestAccount}
                                testingAccountId={testingAccountId}
                                onReconnectAccount={(acc) => { setReconnectAccount(acc); setCodeRequested(false); setReconnectPin(''); }}
                                onDeleteAccount={handleDeleteAccount}
                                onManageProfile={(acc) => { setSelectedAccountId(acc.id); setActiveTab('profile'); }}
                            />
                        )}

                        {activeTab === 'profile' && (
                            <BusinessProfileTab
                                business={business}
                                accounts={accounts}
                                selectedAccountId={selectedAccountId}
                                onSelectAccount={(id) => setSelectedAccountId(id)}
                            />
                        )}

                        {activeTab === 'audiences' && (
                            <AudiencesTab
                                businessId={business.id}
                                contactGroups={contactGroups}
                            />
                        )}

                        {activeTab === 'ad_performance' && (
                            <AdPerformanceTab
                                businessId={business.id}
                                perMessageFee={business.per_message_fee}
                            />
                        )}

                        {activeTab === 'send' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Unified dispatch form */}
                                <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_create_campaign')}</h3>
                                    <form onSubmit={handleSend} className="space-y-5">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">
                                                    {activeChannel === 'telegram' ? __('whatsapp.workspace_select_sender_bot') : __('whatsapp.workspace_select_sender_device')}
                                                </label>
                                                {activeChannel === 'whatsapp' ? (
                                                    <select
                                                        value={sendForm.data.whatsapp_account_id}
                                                        onChange={e => sendForm.setData('whatsapp_account_id', e.target.value)}
                                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                    >
                                                        {accounts.map(acc => (
                                                            <option key={acc.id} value={acc.id}>{acc.name} ({acc.phone_number_id})</option>
                                                        ))}
                                                        {accounts.length === 0 && <option value="">{__('whatsapp.workspace_no_whatsapp_account')}</option>}
                                                    </select>
                                                ) : (
                                                    <select
                                                        value={sendForm.data.telegram_bot_id}
                                                        onChange={e => sendForm.setData('telegram_bot_id', e.target.value)}
                                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                    >
                                                        {bots.map(bot => (
                                                            <option key={bot.id} value={bot.id}>{bot.name} (@{bot.username})</option>
                                                        ))}
                                                        {bots.length === 0 && <option value="">{__('whatsapp.workspace_no_telegram_bots')}</option>}
                                                    </select>
                                                )}
                                            </div>

                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">{__('whatsapp.workspace_recipient_source')}</label>
                                                <select
                                                    value={sendForm.data.recipient_source}
                                                    onChange={e => sendForm.setData('recipient_source', e.target.value)}
                                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                >
                                                    <option value="single">{__('whatsapp.workspace_single_recipient')}</option>
                                                    <option value="group">{__('whatsapp.workspace_bulk_contact_group')}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {sendForm.data.recipient_source === 'single' ? (
                                                <div>
                                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">
                                                        {activeChannel === 'telegram' ? __('whatsapp.workspace_recipient_chat_id') : __('whatsapp.workspace_recipient_phone')}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={sendForm.data.recipient_phone}
                                                        onChange={e => sendForm.setData('recipient_phone', e.target.value)}
                                                        placeholder={activeChannel === 'telegram' ? __('whatsapp.workspace_example', { value: '123456789' }) : __('whatsapp.workspace_example', { value: '201001234567' })}
                                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                    />
                                                </div>
                                            ) : (
                                                <div>
                                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">{__('whatsapp.workspace_select_target_group')}</label>
                                                    <select
                                                        value={sendForm.data.whatsapp_contact_group_id}
                                                        onChange={e => sendForm.setData('whatsapp_contact_group_id', e.target.value)}
                                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                    >
                                                        {contactGroups.map(gp => (
                                                            <option key={gp.id} value={gp.id}>{gp.name} ({__('whatsapp.workspace_contacts_count', { count: gp.contacts_count })})</option>
                                                        ))}
                                                        {contactGroups.length === 0 && <option value="">{__('whatsapp.workspace_no_contact_groups')}</option>}
                                                    </select>
                                                </div>
                                            )}
                                        </div>


                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">{__('whatsapp.workspace_message_format')}</label>
                                            <select
                                                value={sendForm.data.message_type}
                                                onChange={e => sendForm.setData('message_type', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                            >
                                                <option value="text">{__('whatsapp.workspace_raw_text_message')}</option>
                                                <option value="template">{__('whatsapp.workspace_meta_template_message')}</option>
                                            </select>
                                        </div>

                                        {sendForm.data.message_type === 'text' ? (
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">{__('whatsapp.workspace_message_body_label')}</label>
                                                <textarea
                                                    rows={4}
                                                    value={sendForm.data.message_body}
                                                    onChange={e => sendForm.setData('message_body', e.target.value)}
                                                    placeholder={__('whatsapp.workspace_message_body_placeholder')}
                                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                                />
                                            </div>
                                        ) : (
                                            <div className="space-y-4 bg-zinc-50 dark:bg-zinc-950 p-4 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
                                                <div>
                                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2 font-medium">{__('whatsapp.workspace_select_approved_template')}</label>
                                                    <select
                                                        value={sendForm.data.template_name}
                                                        onChange={e => {
                                                            const tpl = templates.find(t => t.name === e.target.value);
                                                            sendForm.setData(data => ({
                                                                ...data,
                                                                template_name: e.target.value,
                                                                template_language: tpl?.language || 'en_US',
                                                            }));
                                                        }}
                                                        className="w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                                    >
                                                        {templates
                                                            .slice()
                                                            .sort((a, b) => (a.status === 'APPROVED' ? -1 : 1))
                                                            .map(tpl => {
                                                                const isApproved = tpl.status === 'APPROVED';
                                                                return (
                                                                    <option key={tpl.id} value={tpl.name} disabled={!isApproved}>
                                                                        {tpl.name} ({tpl.language}) — [{tpl.status}]{!isApproved ? ` (${__('whatsapp.workspace_cannot_send')})` : ''}
                                                                    </option>
                                                                );
                                                            })}
                                                        {templates.length === 0 && <option value="">{__('whatsapp.workspace_no_templates')}</option>}
                                                    </select>
                                                </div>

                                                {selectedTemplate && (
                                                    <div className="space-y-3">
                                                        <span className="text-xs text-zinc-500 font-medium">{__('whatsapp.workspace_template_text_content')}</span>
                                                        <p className="text-sm bg-white dark:bg-zinc-900 p-3 rounded-xl border border-zinc-100 dark:border-zinc-850 font-mono text-zinc-700 dark:text-zinc-300">
                                                            {bodyText}
                                                        </p>

                                                        {variableCount > 0 && (
                                                            <div className="space-y-3 pt-2">
                                                                <span className="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide block">{__('whatsapp.workspace_map_template_variables')}</span>
                                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                    {Array.from({ length: variableCount }).map((_, idx) => (
                                                                        <div key={idx}>
                                                                            <label className="text-xs text-zinc-500 block mb-1">{__('whatsapp.workspace_variable_n', { index: idx + 1 })}</label>
                                                                            <input
                                                                                type="text"
                                                                                placeholder={__('whatsapp.workspace_variable_placeholder')}
                                                                                value={mappedVariables[`var_${idx + 1}`] || ''}
                                                                                onChange={e => setMappedVariables({
                                                                                    ...mappedVariables,
                                                                                    [`var_${idx + 1}`]: e.target.value
                                                                                })}
                                                                                className="w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-xs"
                                                                            />
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                                <p className="text-xxs text-zinc-400">
                                                                    {__('whatsapp.workspace_tip_type')} <code>name</code> {__('whatsapp.workspace_or')} <code>phone</code> {__('whatsapp.workspace_tip_map_suffix')}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {/* Campaign Scheduler options */}
                                        <div className="border-t border-zinc-150 dark:border-zinc-800 pt-5 space-y-4">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <h4 className="text-sm font-bold text-zinc-900 dark:text-zinc-50">{__('whatsapp.workspace_schedule_future')}</h4>
                                                    <p className="text-xs text-zinc-500">{__('whatsapp.workspace_schedule_future_desc')}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => sendForm.setData('is_scheduled', !sendForm.data.is_scheduled)}
                                                    aria-label={__('whatsapp.workspace_schedule_future')}
                                                    aria-pressed={sendForm.data.is_scheduled}
                                                    className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${sendForm.data.is_scheduled ? 'bg-zinc-900 dark:bg-zinc-100' : 'bg-zinc-200 dark:bg-zinc-850'
                                                        }`}
                                                >
                                                    <span className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-900 shadow ring-0 transition duration-200 ease-in-out ${sendForm.data.is_scheduled ? 'translate-x-5' : 'translate-x-0'
                                                        }`} />
                                                </button>
                                            </div>

                                            {sendForm.data.is_scheduled && (
                                                <div>
                                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">{__('whatsapp.workspace_delivery_time')}</label>
                                                    <input
                                                        type="datetime-local"
                                                        value={sendForm.data.scheduled_at}
                                                        onChange={e => sendForm.setData('scheduled_at', e.target.value)}
                                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                    />
                                                </div>
                                            )}
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={sendForm.processing}
                                            className="w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-3 rounded-xl font-semibold tracking-tight transition duration-200"
                                        >
                                            {sendForm.data.is_scheduled ? __('whatsapp.workspace_schedule_delivery') : __('whatsapp.workspace_send_immediately')}
                                        </button>
                                    </form>
                                </div>

                                {/* Sidebar Guidelines & Fee Info */}
                                <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-3xl p-6 space-y-6">
                                    <div>
                                        <h3 className="font-bold text-zinc-900 dark:text-zinc-50 text-lg">{__('whatsapp.workspace_platform_fees')}</h3>
                                        <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_platform_fees_desc')}</p>
                                        <div className="mt-4 border-t border-zinc-200 dark:border-zinc-800 pt-4 space-y-2">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-zinc-500">{__('whatsapp.workspace_whatsapp_fee')}</span>
                                                <span className="font-bold text-zinc-850 dark:text-zinc-200">{__('whatsapp.workspace_fee_per_message', { fee: business.per_message_fee })}</span>
                                            </div>
                                            <div className="flex justify-between text-sm">
                                                <span className="text-zinc-500">{__('whatsapp.workspace_telegram_fee')}</span>
                                                <span className="font-bold text-zinc-850 dark:text-zinc-200">{__('whatsapp.workspace_fee_per_message', { fee: business.per_message_fee })}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="space-y-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-850 p-4 rounded-2xl">
                                        <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">{__('whatsapp.workspace_dynamic_fields')}</h4>
                                        <p className="text-xxs text-zinc-500 leading-relaxed">
                                            {__('whatsapp.workspace_dynamic_fields_prefix')} <code>{"{{"}1{"}}"}</code>{__('whatsapp.workspace_dynamic_fields_suffix')}
                                        </p>
                                        <ul className="text-xxs text-zinc-500 list-disc list-inside space-y-1 mt-2">
                                            <li><code>name</code> - {__('whatsapp.workspace_resolves_name')}</li>
                                            <li><code>phone</code> - {__('whatsapp.workspace_resolves_phone')}</li>
                                            <li><code>custom_fields.FIELD_NAME</code> - {__('whatsapp.workspace_resolves_custom')}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}


                        {activeTab === 'bots' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div className="flex justify-between items-center">
                                        <div>
                                            <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_tab_bots')}</h3>
                                            <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_bots_desc')}</p>
                                        </div>
                                    </div>

                                    <div className="space-y-4 mt-6">
                                        {bots.map(bot => (
                                            <div key={bot.id} className="border border-zinc-100 dark:border-zinc-800/80 p-4 rounded-2xl flex justify-between items-center">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{bot.name}</span>
                                                        <span className="text-xs text-zinc-500">(@{bot.username})</span>
                                                    </div>
                                                    <div className="text-xs text-emerald-500 font-semibold mt-1">{__('whatsapp.workspace_webhook_active')}</div>
                                                </div>
                                                <button
                                                    onClick={async () => {
                                                        if (!(await confirm({ title: __('whatsapp.workspace_delete_bot_title'), description: __('whatsapp.workspace_delete_bot_confirm'), variant: 'danger' }))) return;
                                                        router.delete(`/whatsapp-sender/telegram-bots/${bot.id}`);
                                                    }}
                                                    className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                                >
                                                    {__('whatsapp.workspace_remove')}
                                                </button>
                                            </div>
                                        ))}
                                        {bots.length === 0 && (
                                            <p className="text-sm text-zinc-400 text-center py-6">{__('whatsapp.workspace_no_bots_yet')}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div>
                                        <h3 className="text-lg font-bold text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_register_new_bot')}</h3>
                                        <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_register_bot_desc')}</p>
                                    </div>
                                    <form onSubmit={handleAddBot} className="space-y-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('whatsapp.workspace_bot_token')}</label>
                                            <input
                                                type="text"
                                                value={botForm.data.token}
                                                onChange={e => botForm.setData('token', e.target.value)}
                                                placeholder={__('whatsapp.workspace_bot_token_placeholder')}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                            />
                                            {botForm.errors.token && <span className="text-xs text-red-500 mt-1 block">{botForm.errors.token}</span>}
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={botForm.processing}
                                            className="w-full bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-2.5 rounded-xl text-sm font-semibold transition"
                                        >
                                            {__('whatsapp.workspace_verify_register_bot')}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        )}

                        {activeTab === 'templates' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Create Template Form */}
                                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_create_meta_template')}</h3>

                                    {templateForm.errors.whatsapp_business_id && (
                                        <div className="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-xs font-medium text-red-700 dark:text-red-300">
                                            {templateForm.errors.whatsapp_business_id}
                                        </div>
                                    )}

                                    <form onSubmit={handleCreateTemplate} className="space-y-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">
                                                {__('whatsapp.workspace_template_name_label')}
                                            </label>
                                            <input
                                                type="text"
                                                value={templateForm.data.name}
                                                onChange={e => {
                                                    const raw = e.target.value;
                                                    // Convert input to lowercase slug format
                                                    const val = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_');
                                                    templateForm.setData('name', val);
                                                }}
                                                placeholder={__('whatsapp.workspace_template_name_placeholder')}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm font-mono"
                                            />
                                            {templateForm.errors.name && (
                                                <span className="text-xs text-red-500 mt-1 block">{templateForm.errors.name}</span>
                                            )}
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('general.category')}</label>
                                            <select
                                                value={templateForm.data.category}
                                                onChange={e => templateForm.setData('category', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                            >
                                                <option value="UTILITY">{__('whatsapp.workspace_category_utility')}</option>
                                                <option value="MARKETING">{__('whatsapp.workspace_category_marketing')}</option>
                                                <option value="AUTHENTICATION">{__('whatsapp.workspace_category_authentication')}</option>
                                            </select>
                                            {templateForm.errors.category && (
                                                <span className="text-xs text-red-500 mt-1 block">{templateForm.errors.category}</span>
                                            )}
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('general.language')}</label>
                                            <select
                                                value={templateForm.data.language}
                                                onChange={e => templateForm.setData('language', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                            >
                                                <option value="en_US">{__('whatsapp.workspace_lang_en_us')}</option>
                                                <option value="en_GB">{__('whatsapp.workspace_lang_en_gb')}</option>
                                                <option value="ar">{__('whatsapp.workspace_lang_ar')}</option>
                                                <option value="es">{__('whatsapp.workspace_lang_es')}</option>
                                                <option value="fr">{__('whatsapp.workspace_lang_fr')}</option>
                                                <option value="de">{__('whatsapp.workspace_lang_de')}</option>
                                                <option value="it">{__('whatsapp.workspace_lang_it')}</option>
                                                <option value="pt_BR">{__('whatsapp.workspace_lang_pt_br')}</option>
                                                <option value="tr">{__('whatsapp.workspace_lang_tr')}</option>
                                                <option value="ru">{__('whatsapp.workspace_lang_ru')}</option>
                                                <option value="hi">{__('whatsapp.workspace_lang_hi')}</option>
                                                <option value="id">{__('whatsapp.workspace_lang_id')}</option>
                                                <option value="ur">{__('whatsapp.workspace_lang_ur')}</option>
                                            </select>
                                            {templateForm.errors.language && (
                                                <span className="text-xs text-red-500 mt-1 block">{templateForm.errors.language}</span>
                                            )}
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('whatsapp.workspace_body_text_label')}</label>
                                            <textarea
                                                rows={4}
                                                value={templateForm.data.components[0].text}
                                                onChange={e => {
                                                    const updated = [...templateForm.data.components];
                                                    updated[0].text = e.target.value;
                                                    templateForm.setData('components', updated);
                                                }}
                                                placeholder={__('whatsapp.workspace_body_placeholder')}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                            />
                                            {templateForm.errors.components && (
                                                <span className="text-xs text-red-500 mt-1 block">{templateForm.errors.components}</span>
                                            )}
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={templateForm.processing}
                                            className="w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-50 flex items-center justify-center gap-2"
                                        >
                                            {templateForm.processing ? (
                                                <>
                                                    <svg className="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span>{__('whatsapp.workspace_submitting_to_meta')}</span>
                                                </>
                                            ) : (
                                                <span>{__('whatsapp.workspace_submit_to_meta')}</span>
                                            )}
                                        </button>
                                    </form>
                                </div>

                                {/* Templates Table List */}
                                <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div className="flex justify-between items-center">
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_synced_templates')}</h3>
                                        <button
                                            onClick={() => router.post(`/whatsapp-sender/templates/${business.id}/sync`)}
                                            className="bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800 text-xs px-4 py-2 rounded-xl font-semibold transition duration-200 text-zinc-800 dark:text-zinc-200"
                                        >
                                            {__('whatsapp.workspace_sync_from_facebook')}
                                        </button>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                    <th className="py-3 px-2 font-semibold">{__('general.name')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.category')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.language')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.status')}</th>
                                                    <th className="py-3 px-2 font-semibold text-right">{__('general.actions')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {templates.map(tpl => (
                                                    <tr key={tpl.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                        <td className="py-3 px-2 font-semibold text-zinc-800 dark:text-zinc-200">{tpl.name}</td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs">{tpl.category}</td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs">{tpl.language}</td>
                                                        <td className="py-3 px-2">
                                                            <span className={`text-xxs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider ${tpl.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-amber-50 text-amber-700'
                                                                }`}>
                                                                {tpl.status}
                                                            </span>
                                                        </td>
                                                        <td className="py-3 px-2 text-right">
                                                            <button
                                                                onClick={async () => {
                                                                    if (!(await confirm({ title: __('whatsapp.workspace_delete_template_title'), description: __('whatsapp.workspace_delete_template_confirm'), variant: 'danger' }))) return;
                                                                    router.delete(`/whatsapp-sender/templates/${tpl.id}`);
                                                                }}
                                                                className="text-red-500 hover:text-red-600 text-xs font-semibold"
                                                            >
                                                                {__('general.delete')}
                                                            </button>
                                                        </td>
                                                    </tr>
                                                ))}
                                                {templates.length === 0 && (
                                                    <tr>
                                                        <td colSpan={5} className="py-6 text-center text-zinc-400">{__('whatsapp.workspace_no_synced_templates')}</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'groups' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Contact Groups setup list */}
                                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_create_segment_group')}</h3>
                                    <form onSubmit={handleCreateGroup} className="space-y-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('whatsapp.workspace_group_name')}</label>
                                            <input
                                                type="text"
                                                value={groupForm.data.name}
                                                onChange={e => groupForm.setData('name', e.target.value)}
                                                placeholder={__('whatsapp.workspace_group_name_placeholder')}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('general.description')}</label>
                                            <textarea
                                                rows={2}
                                                value={groupForm.data.description}
                                                onChange={e => groupForm.setData('description', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                            />
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={groupForm.processing}
                                            className="w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-2.5 rounded-xl text-sm font-semibold transition"
                                        >
                                            {__('whatsapp.workspace_create_group')}
                                        </button>
                                    </form>

                                    <div className="space-y-3 mt-6">
                                        <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">{__('whatsapp.workspace_available_segments')}</h4>
                                        {contactGroups.map(gp => (
                                            <div
                                                key={gp.id}
                                                onClick={() => setSelectedGroup(gp)}
                                                className={`p-4 border rounded-2xl cursor-pointer transition ${selectedGroup?.id === gp.id
                                                        ? 'border-zinc-900 dark:border-zinc-50 bg-zinc-50/50 dark:bg-zinc-850/40'
                                                        : 'border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50/40'
                                                    }`}
                                            >
                                                <div className="flex justify-between items-center">
                                                    <span className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{gp.name}</span>
                                                    <span className="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 px-2.5 py-0.5 rounded-full font-semibold">
                                                        {__('whatsapp.workspace_contacts_count', { count: gp.contacts_count })}
                                                    </span>
                                                </div>
                                                {gp.description && <p className="text-xs text-zinc-400 mt-2 line-clamp-1">{gp.description}</p>}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Group Contacts Import / Display panel */}
                                <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    {selectedGroup ? (
                                        <>
                                            <div className="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-800 pb-4">
                                                <div>
                                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_group_workspace', { name: selectedGroup.name })}</h3>
                                                    <p className="text-xs text-zinc-500 mt-1">{selectedGroup.description || __('whatsapp.workspace_no_description')}</p>
                                                </div>
                                                <button
                                                    onClick={async () => {
                                                        if (!(await confirm({ title: __('whatsapp.workspace_delete_group_title'), description: __('whatsapp.workspace_delete_group_confirm'), variant: 'danger' }))) return;
                                                        router.delete(`/whatsapp-sender/contact-groups/${selectedGroup.id}`);
                                                        setSelectedGroup(null);
                                                    }}
                                                    className="text-red-500 hover:text-red-600 text-xs font-semibold"
                                                >
                                                    {__('whatsapp.workspace_delete_group')}
                                                </button>
                                            </div>

                                            <form onSubmit={handleImportContacts} className="space-y-4">
                                                <div>
                                                    <label className="text-xs font-semibold text-zinc-500 block mb-1">
                                                        {__('whatsapp.workspace_paste_contacts_prefix')} <code>phone_or_chat_id,name</code> {__('whatsapp.workspace_paste_contacts_middle')} <code>201001234567,John Doe</code>)
                                                    </label>
                                                    <textarea
                                                        rows={5}
                                                        value={importForm.data.contacts_text}
                                                        onChange={e => importForm.setData('contacts_text', e.target.value)}
                                                        placeholder={__('whatsapp.workspace_contacts_placeholder')}
                                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-mono"
                                                    />
                                                </div>
                                                <button
                                                    type="submit"
                                                    disabled={importForm.processing}
                                                    className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-250 text-white dark:text-zinc-900 text-xs px-4 py-2.5 rounded-xl font-bold transition duration-200"
                                                >
                                                    {__('whatsapp.workspace_import_contacts')}
                                                </button>
                                            </form>
                                        </>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center py-20 text-center space-y-3">
                                            <Users className="w-10 h-10 text-zinc-300" aria-hidden="true" />
                                            <h3 className="text-zinc-500 dark:text-zinc-400 font-bold">{__('whatsapp.workspace_no_group_selected')}</h3>
                                            <p className="text-zinc-400 dark:text-zinc-500 text-xs max-w-sm">{__('whatsapp.workspace_no_group_selected_desc')}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeTab === 'schedules' && (
                            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <div>
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_active_schedules')}</h3>
                                    <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_active_schedules_desc')}</p>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_channel')}</th>
                                                <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_sender_device_bot')}</th>
                                                <th className="py-3 px-2 font-semibold">{__('general.recipient')}</th>
                                                <th className="py-3 px-2 font-semibold">{__('general.type')}</th>
                                                <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_scheduled_date')}</th>
                                                <th className="py-3 px-2 font-semibold">{__('general.status')}</th>
                                                <th className="py-3 px-2 font-semibold text-right">{__('general.actions')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {schedules.map(sch => (
                                                <tr key={sch.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                    <td className="py-3 px-2">
                                                        <span className={`text-xxs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${sch.channel === 'telegram' ? 'bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-400' : 'bg-green-50 text-green-600 dark:bg-green-950 dark:text-green-400'
                                                            }`}>
                                                            {sch.channel}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-2 text-zinc-700 dark:text-zinc-300 font-medium">
                                                        {sch.channel === 'telegram' ? sch.telegram_bot?.name : sch.account?.name}
                                                    </td>
                                                    <td className="py-3 px-2 text-zinc-600 dark:text-zinc-400">
                                                        {sch.group ? __('whatsapp.workspace_group_prefix', { name: sch.group.name }) : sch.recipient_phone}
                                                    </td>
                                                    <td className="py-3 px-2 text-zinc-500 text-xs capitalize">{sch.message_type}</td>
                                                    <td className="py-3 px-2 text-zinc-500 text-xs font-mono">{new Date(sch.scheduled_at).toLocaleString('en-US', { timeZone: 'Africa/Cairo' })}</td>
                                                    <td className="py-3 px-2">
                                                        <span className={`text-xxs px-2 py-0.5 rounded-full font-bold capitalize ${sch.status === 'sent' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' :
                                                                sch.status === 'failed' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' :
                                                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'
                                                            }`}>
                                                            {sch.status}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-2 text-right">
                                                        {sch.status === 'pending' && (
                                                            <button
                                                                onClick={async () => {
                                                                    if (!(await confirm({ title: __('whatsapp.workspace_cancel_schedule_title'), description: __('whatsapp.workspace_cancel_schedule_confirm'), variant: 'danger' }))) return;
                                                                    router.delete(`/whatsapp-sender/schedules/${sch.id}`);
                                                                }}
                                                                className="text-red-500 hover:text-red-600 text-xs font-semibold"
                                                            >
                                                                {__('general.cancel')}
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                            {schedules.length === 0 && (
                                                <tr>
                                                    <td colSpan={7} className="py-6 text-center text-zinc-400">{__('whatsapp.workspace_no_schedules')}</td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {activeTab === 'logs' && (
                            <div className="grid grid-cols-1 gap-8">
                                {/* Message Logs */}
                                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div>
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_message_logs')}</h3>
                                        <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_message_logs_desc')}</p>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_channel')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_sender_device_bot')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.recipient')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_content_preview')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_fee_charged')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.status')}</th>
                                                    <th className="py-3 px-2 font-semibold text-right">{__('whatsapp.workspace_timestamp')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {logs.map(log => (
                                                    <tr key={log.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                        <td className="py-3 px-2">
                                                            <span className={`text-xxs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${log.channel === 'telegram' ? 'bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-400' : 'bg-green-50 text-green-600 dark:bg-green-950 dark:text-green-400'
                                                                }`}>
                                                                {log.channel}
                                                            </span>
                                                        </td>
                                                        <td className="py-3 px-2 text-zinc-700 dark:text-zinc-300 font-medium">
                                                            {log.channel === 'telegram' ? log.telegram_bot?.name : log.account?.name}
                                                        </td>
                                                        <td className="py-3 px-2 text-zinc-650 dark:text-zinc-300 font-mono text-xs">{log.recipient_phone}</td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs truncate max-w-xs">{log.message_body || `[${log.message_type}]`}</td>
                                                        <td className="py-3 px-2 text-zinc-900 dark:text-zinc-100 font-bold">${parseFloat(log.cost_charged).toFixed(4)}</td>
                                                        <td className="py-3 px-2">
                                                            <span className={`text-xxs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider ${log.status === 'sent' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-red-50 text-red-700'
                                                                }`}>
                                                                {log.status}
                                                            </span>
                                                        </td>
                                                        <td className="py-3 px-2 text-right text-zinc-400 text-xxs font-mono">{new Date(log.created_at).toLocaleString()}</td>
                                                    </tr>
                                                ))}
                                                {logs.length === 0 && (
                                                    <tr>
                                                        <td colSpan={7} className="py-6 text-center text-zinc-400">{__('whatsapp.workspace_no_logs')}</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Wallet Ledger Transactions */}
                                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div>
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_wallet_history')}</h3>
                                        <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_wallet_history_desc')}</p>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_transaction_type')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.amount')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_balance_after')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.description')}</th>
                                                    <th className="py-3 px-2 font-semibold text-right">{__('whatsapp.workspace_timestamp')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {transactions.map(tx => (
                                                    <tr key={tx.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                        <td className="py-3 px-2 font-bold capitalize text-zinc-700 dark:text-zinc-300">{tx.type.replace(/_/g, ' ')}</td>
                                                        <td className={`py-3 px-2 font-bold ${tx.type.includes('recharge') ? 'text-emerald-500' : 'text-red-500'}`}>
                                                            {tx.type.includes('recharge') ? '+' : '-'}${parseFloat(tx.amount).toFixed(4)}
                                                        </td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs">${parseFloat(tx.balance_after).toFixed(4)}</td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs">{tx.description}</td>
                                                        <td className="py-3 px-2 text-right text-zinc-400 text-xxs font-mono">{new Date(tx.created_at).toLocaleString()}</td>
                                                    </tr>
                                                ))}
                                                {transactions.length === 0 && (
                                                    <tr>
                                                        <td colSpan={5} className="py-6 text-center text-zinc-400">{__('whatsapp.workspace_no_transactions')}</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'flows' && (
                            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6 font-sans">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_visual_flows')}</h3>
                                        <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_visual_flows_desc')}</p>
                                    </div>
                                    <button
                                        onClick={() => setIsCreatingFlow(true)}
                                        className="bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs font-bold px-4 py-2.5 rounded-xl transition duration-200"
                                    >
                                        + {__('whatsapp.workspace_new_flow')}
                                    </button>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {flows.map(flow => (
                                        <div key={flow.id} className="border border-zinc-200 dark:border-zinc-800 p-5 rounded-3xl bg-zinc-50/30 dark:bg-zinc-955/20 flex flex-col justify-between h-48 relative overflow-hidden group">
                                            <div className="absolute right-4 top-4 flex items-center space-x-2">
                                                <span className={`text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${flow.channel === 'telegram' ? 'bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400' : 'bg-green-100 text-green-600 dark:bg-green-950 dark:text-green-400'
                                                    }`}>
                                                    {flow.channel}
                                                </span>
                                            </div>

                                            <div>
                                                <h4 className="font-bold text-base text-zinc-900 dark:text-zinc-100">{flow.name}</h4>
                                                <p className="text-xs text-zinc-500 mt-1 font-medium">
                                                    {__('whatsapp.workspace_trigger_label')} <span className="font-semibold text-zinc-700 dark:text-zinc-300 capitalize">{flow.trigger_type}</span>
                                                </p>
                                                {flow.trigger_type === 'keyword' && (
                                                    <p className="text-xxs text-zinc-400 mt-1 truncate max-w-xs">
                                                        {__('whatsapp.workspace_keywords_value', { keywords: flow.trigger_keywords?.join(', ') ?? '' })}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="flex justify-between items-center border-t border-zinc-100 dark:border-zinc-800/80 pt-4 mt-4">
                                                <div className="flex items-center space-x-2">
                                                    <button
                                                        onClick={() => router.post(`/whatsapp-sender/bot-flows/${flow.id}/toggle`)}
                                                        className={`text-[10px] font-bold px-2 py-0.5 rounded-md transition ${flow.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-zinc-150 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                            }`}
                                                    >
                                                        {flow.is_active ? __('general.active') : __('general.inactive')}
                                                    </button>
                                                </div>

                                                <div className="flex items-center space-x-2">
                                                    <button
                                                        onClick={() => setEditingFlow(flow)}
                                                        className="text-zinc-650 dark:text-zinc-400 hover:text-zinc-900 text-xs font-semibold px-2 py-1"
                                                    >
                                                        {__('whatsapp.workspace_edit_canvas')}
                                                    </button>
                                                    <button
                                                        onClick={async () => {
                                                            if (!(await confirm({ title: __('whatsapp.workspace_delete_flow_title'), description: __('whatsapp.workspace_delete_flow_confirm'), variant: 'danger' }))) return;
                                                            router.delete(`/whatsapp-sender/bot-flows/${flow.id}`);
                                                        }}
                                                        className="text-red-500 hover:text-red-650 text-xs font-semibold px-2 py-1"
                                                    >
                                                        {__('general.delete')}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    {flows.length === 0 && (
                                        <div className="col-span-full py-12 text-center text-zinc-400 text-sm">
                                            {__('whatsapp.workspace_no_flows')}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeTab === 'subscribers' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 font-sans">
                                {/* Create subscriber group */}
                                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div>
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_create_subscriber_group')}</h3>
                                        <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_subscriber_group_desc')}</p>
                                    </div>

                                    <form onSubmit={handleCreateTgGroup} className="space-y-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('whatsapp.workspace_select_bot')}</label>
                                            <select
                                                value={tgGroupForm.data.telegram_bot_id}
                                                onChange={e => tgGroupForm.setData('telegram_bot_id', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                            >
                                                {bots.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                                                {bots.length === 0 && <option value="">{__('whatsapp.workspace_no_telegram_bots')}</option>}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('whatsapp.workspace_group_name')}</label>
                                            <input
                                                type="text"
                                                value={tgGroupForm.data.name}
                                                onChange={e => tgGroupForm.setData('name', e.target.value)}
                                                placeholder={__('whatsapp.workspace_subscriber_group_placeholder')}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 block mb-1">{__('general.description')}</label>
                                            <textarea
                                                rows={2}
                                                value={tgGroupForm.data.description}
                                                onChange={e => tgGroupForm.setData('description', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={tgGroupForm.processing}
                                            className="w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-2.5 rounded-xl text-sm font-semibold transition"
                                        >
                                            {__('whatsapp.workspace_create_subscriber_group')}
                                        </button>
                                    </form>

                                    <div className="space-y-3 mt-6 border-t border-zinc-100 dark:border-zinc-800 pt-6">
                                        <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">{__('whatsapp.workspace_subscriber_segments')}</h4>
                                        {telegramSubscriberGroups.map(gp => (
                                            <div
                                                key={gp.id}
                                                onClick={() => setSelectedSubscriberGroup(selectedSubscriberGroup?.id === gp.id ? null : gp)}
                                                className={`p-4 border rounded-2xl cursor-pointer transition ${selectedSubscriberGroup?.id === gp.id
                                                        ? 'border-zinc-900 dark:border-zinc-50 bg-zinc-50/50 dark:bg-zinc-850/40'
                                                        : 'border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50/40'
                                                    }`}
                                            >
                                                <div className="flex justify-between items-center">
                                                    <span className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{gp.name}</span>
                                                    <span className="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-650 dark:text-zinc-300 px-2.5 py-0.5 rounded-full font-semibold">
                                                        {__('whatsapp.workspace_members_count', { count: gp.subscribers_count })}
                                                    </span>
                                                </div>
                                                {gp.description && <p className="text-xs text-zinc-400 mt-2 line-clamp-1">{gp.description}</p>}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Subscribers listing */}
                                <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                    <div className="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-800 pb-4">
                                        <div>
                                            <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">
                                                {selectedSubscriberGroup ? __('whatsapp.workspace_group_members', { name: selectedSubscriberGroup.name }) : __('whatsapp.workspace_all_subscribers')}
                                            </h3>
                                            <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_all_subscribers_desc')}</p>
                                        </div>
                                        {selectedSubscriberGroup && (
                                            <button
                                                onClick={async () => {
                                                    if (!(await confirm({ title: __('whatsapp.workspace_delete_subscriber_group_title'), description: __('whatsapp.workspace_delete_subscriber_group_confirm'), variant: 'danger' }))) return;
                                                    router.delete(`/whatsapp-sender/telegram-subscriber-groups/${selectedSubscriberGroup.id}`);
                                                    setSelectedSubscriberGroup(null);
                                                }}
                                                className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                            >
                                                {__('whatsapp.workspace_delete_group')}
                                            </button>
                                        )}
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_chat_id')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_username')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('general.name')}</th>
                                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.workspace_assigned_group')}</th>
                                                    <th className="py-3 px-2 font-semibold text-right">{__('general.actions')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {telegramSubscribers
                                                    .filter(sub => !selectedSubscriberGroup || sub.telegram_subscriber_group_id === selectedSubscriberGroup.id)
                                                    .map(sub => (
                                                        <tr key={sub.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                            <td className="py-3 px-2 font-mono text-xs text-zinc-700 dark:text-zinc-300 font-bold">{sub.chat_id}</td>
                                                            <td className="py-3 px-2 text-zinc-500 text-xs">@{sub.username || __('general.n_a')}</td>
                                                            <td className="py-3 px-2 text-zinc-700 dark:text-zinc-200 font-medium">
                                                                {sub.first_name} {sub.last_name}
                                                            </td>
                                                            <td className="py-3 px-2 text-zinc-500 text-xs">
                                                                <select
                                                                    value={sub.telegram_subscriber_group_id || ''}
                                                                    onChange={e => router.put(`/whatsapp-sender/telegram-subscribers/${sub.id}/group`, {
                                                                        telegram_subscriber_group_id: e.target.value || null
                                                                    })}
                                                                    aria-label={__('whatsapp.workspace_assigned_group')}
                                                                    className="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg py-1 px-2 text-xs focus:outline-none text-zinc-700 dark:text-zinc-300"
                                                                >
                                                                    <option value="">{__('general.unassigned')}</option>
                                                                    {telegramSubscriberGroups.map(g => (
                                                                        <option key={g.id} value={g.id}>{g.name}</option>
                                                                    ))}
                                                                </select>
                                                            </td>
                                                            <td className="py-3 px-2 text-right">
                                                                <button
                                                                    onClick={async () => {
                                                                        if (!(await confirm({ title: __('whatsapp.workspace_remove_subscriber_title'), description: __('whatsapp.workspace_remove_subscriber_confirm'), variant: 'danger' }))) return;
                                                                        router.delete(`/whatsapp-sender/telegram-subscribers/${sub.id}`);
                                                                    }}
                                                                    className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                                                >
                                                                    {__('whatsapp.workspace_remove')}
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                {telegramSubscribers.length === 0 && (
                                                    <tr>
                                                        <td colSpan={5} className="py-6 text-center text-zinc-400">{__('whatsapp.workspace_no_subscribers')}</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Visual Chat Flow Builder Screen Overlay */}
            {(isCreatingFlow || editingFlow) && (
                <FlowBuilder
                    flow={editingFlow}
                    whatsappBusinessId={business.id}
                    channel={activeChannel}
                    telegramBotId={bots[0]?.id || null}
                    bots={bots}
                    onClose={() => {
                        setIsCreatingFlow(false);
                        setEditingFlow(null);
                    }}
                />
            )}

            {/* Edit Business Modal inside workspace */}
            {showEditModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 max-w-lg w-full rounded-3xl p-6 shadow-2xl space-y-6">
                        <div>
                            <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.workspace_edit_business_title')}</h3>
                            <p className="text-xs text-zinc-400 mt-1">{__('whatsapp.workspace_edit_business_desc')}</p>
                        </div>
                        <form onSubmit={handleEditBusiness} className="space-y-4">
                            <div>
                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_company_name')}</label>
                                <input
                                    type="text"
                                    required
                                    value={editForm.data.name}
                                    onChange={e => editForm.setData('name', e.target.value)}
                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_client_contact_name')}</label>
                                    <input
                                        type="text"
                                        value={editForm.data.client_name}
                                        onChange={e => editForm.setData('client_name', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_client_email')}</label>
                                    <input
                                        type="email"
                                        value={editForm.data.client_email}
                                        onChange={e => editForm.setData('client_email', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_client_mobile')}</label>
                                    <input
                                        type="text"
                                        value={editForm.data.client_mobile}
                                        onChange={e => editForm.setData('client_mobile', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_client_whatsapp')}</label>
                                    <input
                                        type="text"
                                        value={editForm.data.client_whatsapp}
                                        onChange={e => editForm.setData('client_whatsapp', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_custom_app_id')}</label>
                                    <input
                                        type="text"
                                        value={editForm.data.facebook_client_id}
                                        onChange={e => editForm.setData('facebook_client_id', e.target.value)}
                                        placeholder={__('whatsapp.workspace_example', { value: '104829384920' })}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_custom_app_secret')}</label>
                                    <input
                                        type="password"
                                        value={editForm.data.facebook_client_secret}
                                        onChange={e => editForm.setData('facebook_client_secret', e.target.value)}
                                        placeholder="••••••••"
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                            </div>

                            {isAdmin && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_per_message_fee')}</label>
                                        <input
                                            type="number"
                                            step="0.0001"
                                            value={editForm.data.per_message_fee}
                                            onChange={e => editForm.setData('per_message_fee', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_bot_reply_fee')}</label>
                                        <input
                                            type="number"
                                            step="0.0001"
                                            value={editForm.data.bot_reply_fee}
                                            onChange={e => editForm.setData('bot_reply_fee', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                <button
                                    type="button"
                                    onClick={() => setShowEditModal(false)}
                                    className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-250 text-xs px-4 py-2 rounded-xl font-bold transition"
                                >
                                    {__('general.cancel')}
                                </button>
                                <button
                                    type="submit"
                                    disabled={editForm.processing}
                                    className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs px-4 py-2 rounded-xl font-bold transition"
                                >
                                    {__('whatsapp.workspace_save_changes')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Manual Meta Account Modal */}
            {showAddAccountModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-6">
                        <div className="flex justify-between items-center">
                            <div>
                                <h3 className="text-lg font-bold text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_add_account_title')}</h3>
                                <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_add_account_desc')}</p>
                            </div>
                            <button type="button" onClick={() => setShowAddAccountModal(false)} aria-label={__('general.close')} className="text-zinc-400 hover:text-zinc-600 font-bold text-xl">&times;</button>
                        </div>

                        <form onSubmit={handleAddAccount} className="space-y-4">
                            <div>
                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_account_display_name')}</label>
                                <input
                                    type="text"
                                    required
                                    placeholder={__('whatsapp.workspace_account_name_placeholder')}
                                    value={accountForm.data.name}
                                    onChange={e => accountForm.setData('name', e.target.value)}
                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_phone_number_id')}</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder={__('whatsapp.workspace_example', { value: '1280491895139233' })}
                                        value={accountForm.data.phone_number_id}
                                        onChange={e => accountForm.setData('phone_number_id', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-mono text-zinc-700 dark:text-zinc-300"
                                    />
                                    {accountForm.errors.phone_number_id && <span className="text-xxs text-red-500 mt-1 block">{accountForm.errors.phone_number_id}</span>}
                                </div>
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_waba_id_optional')}</label>
                                    <input
                                        type="text"
                                        placeholder={__('whatsapp.workspace_example', { value: '109283748291029' })}
                                        value={accountForm.data.waba_id}
                                        onChange={e => accountForm.setData('waba_id', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-mono text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_access_token_label')}</label>
                                <textarea
                                    rows={3}
                                    required
                                    placeholder={__('whatsapp.workspace_example', { value: 'EAAsewqHOtVsBS...' })}
                                    value={accountForm.data.access_token}
                                    onChange={e => accountForm.setData('access_token', e.target.value)}
                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-xs font-mono text-zinc-700 dark:text-zinc-300"
                                />
                                {accountForm.errors.access_token && <span className="text-xxs text-red-500 mt-1 block">{accountForm.errors.access_token}</span>}
                            </div>

                            <div className="flex justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                <button
                                    type="button"
                                    onClick={() => setShowAddAccountModal(false)}
                                    className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-250 text-xs px-4 py-2 rounded-xl font-bold transition"
                                >
                                    {__('general.cancel')}
                                </button>
                                <button
                                    type="submit"
                                    disabled={accountForm.processing}
                                    className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs px-4 py-2 rounded-xl font-bold transition"
                                >
                                    {accountForm.processing ? __('whatsapp.workspace_verifying_saving') : __('whatsapp.workspace_save_meta_account')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Edit Meta Account Modal */}
            {showEditAccountModal && editingAccount && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-6">
                        <div className="flex justify-between items-center">
                            <div>
                                <h3 className="text-lg font-bold text-zinc-950 dark:text-zinc-50 font-sans">{__('whatsapp.workspace_edit_account_title')}</h3>
                                <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.workspace_edit_account_desc')}</p>
                            </div>
                            <button type="button" onClick={() => { setShowEditAccountModal(false); setEditingAccount(null); }} aria-label={__('general.close')} className="text-zinc-400 hover:text-zinc-600 font-bold text-xl">&times;</button>
                        </div>

                        <form onSubmit={handleUpdateAccount} className="space-y-4">
                            <div>
                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_account_display_name_phone')}</label>
                                <input
                                    type="text"
                                    required
                                    placeholder={__('whatsapp.workspace_example', { value: '+20 12 26024269' })}
                                    value={editAccountForm.data.name}
                                    onChange={e => editAccountForm.setData('name', e.target.value)}
                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                />
                                {editAccountForm.errors.name && <span className="text-xxs text-red-500 mt-1 block">{editAccountForm.errors.name}</span>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_phone_number_id')}</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder={__('whatsapp.workspace_example', { value: '1327754942721420' })}
                                        value={editAccountForm.data.phone_number_id}
                                        onChange={e => editAccountForm.setData('phone_number_id', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-mono text-zinc-700 dark:text-zinc-300"
                                    />
                                    {editAccountForm.errors.phone_number_id && <span className="text-xxs text-red-500 mt-1 block">{editAccountForm.errors.phone_number_id}</span>}
                                </div>
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_waba_id')}</label>
                                    <input
                                        type="text"
                                        placeholder={__('whatsapp.workspace_example', { value: '1223248954207318' })}
                                        value={editAccountForm.data.waba_id}
                                        onChange={e => editAccountForm.setData('waba_id', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-mono text-zinc-700 dark:text-zinc-300"
                                    />
                                    {editAccountForm.errors.waba_id && <span className="text-xxs text-red-500 mt-1 block">{editAccountForm.errors.waba_id}</span>}
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.workspace_access_token_optional')}</label>
                                <textarea
                                    rows={3}
                                    placeholder={__('whatsapp.workspace_access_token_placeholder')}
                                    value={editAccountForm.data.access_token}
                                    onChange={e => editAccountForm.setData('access_token', e.target.value)}
                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-xs font-mono text-zinc-700 dark:text-zinc-300"
                                />
                                {editAccountForm.errors.access_token && <span className="text-xxs text-red-500 mt-1 block">{editAccountForm.errors.access_token}</span>}
                            </div>

                            <div className="flex justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                <button
                                    type="button"
                                    onClick={() => { setShowEditAccountModal(false); setEditingAccount(null); }}
                                    className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-250 text-xs px-4 py-2 rounded-xl font-bold transition"
                                >
                                    {__('general.cancel')}
                                </button>
                                <button
                                    type="submit"
                                    disabled={editAccountForm.processing}
                                    className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs px-4 py-2 rounded-xl font-bold transition"
                                >
                                    {editAccountForm.processing ? __('general.saving') : __('whatsapp.workspace_save_changes')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}


            {/* Reconnect Verification Modal */}
            {reconnectAccount && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-6 dir-rtl text-right">
                        <div className="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center font-bold text-lg">
                                    <AlertTriangle className="w-5 h-5" aria-hidden="true" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-zinc-900 dark:text-zinc-100 text-base">
                                        {__('whatsapp.workspace_disconnected')}
                                    </h3>
                                    <p className="text-xs text-zinc-500">{reconnectAccount.name}</p>
                                </div>
                            </div>
                            <button
                                onClick={() => setReconnectAccount(null)}
                                aria-label={__('general.close')}
                                className="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-xl font-bold"
                            >
                                &times;
                            </button>
                        </div>

                        <div className="space-y-4">
                            <p className="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed">
                                {__('whatsapp.workspace_reconnect_desc')}
                            </p>

                            {flash?.success && (
                                <div className="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 p-3 rounded-2xl text-xs font-semibold space-y-1">
                                    <div className="flex items-center gap-1.5 font-bold">
                                        <Check className="w-4 h-4" aria-hidden="true" />
                                        <span>{flash.success}</span>
                                    </div>
                                    <p className="text-xxs text-emerald-700/80 dark:text-emerald-400/80">
                                        {__('whatsapp.workspace_reconnect_code_hint')}
                                    </p>
                                </div>
                            )}

                            {flash?.error && (
                                <div className="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 p-3.5 rounded-2xl text-xs font-semibold space-y-2">
                                    <div className="flex items-center gap-1.5 font-bold">
                                        <AlertTriangle className="w-4 h-4" aria-hidden="true" />
                                        <span>{flash.error}</span>
                                    </div>
                                    {flash?.meta_response && (
                                        <div className="pt-2 border-t border-red-200 dark:border-red-900/60 space-y-1">
                                            <span className="text-xxs font-bold text-red-700 dark:text-red-400 block uppercase">
                                                {__('whatsapp.workspace_meta_graph_error_payload')}
                                            </span>
                                            <pre className="bg-zinc-950 text-red-400 text-xxs p-2.5 rounded-xl font-mono overflow-x-auto border border-zinc-800 dir-ltr text-left">
                                                {JSON.stringify(flash.meta_response, null, 2)}
                                            </pre>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Step 1: Request Code Buttons */}
                            <div className="space-y-2">
                                <span className="text-xs font-bold text-zinc-700 dark:text-zinc-300 block">{__('whatsapp.workspace_step_request_code')}</span>
                                <div className="grid grid-cols-2 gap-3">
                                    <button
                                        type="button"
                                        disabled={isRequestingCode}
                                        onClick={() => {
                                            setIsRequestingCode(true);
                                            router.post(`/whatsapp-sender/accounts/${reconnectAccount.id}/request-code`, {
                                                code_method: 'SMS',
                                                language: 'ar',
                                            }, {
                                                onFinish: () => {
                                                    setIsRequestingCode(false);
                                                    setCodeRequested(true);
                                                }
                                            });
                                        }}
                                        className="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 p-3 rounded-2xl text-xs font-bold transition flex flex-col items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                    >
                                        <MessageSquare className="w-4 h-4" aria-hidden="true" />
                                        <span>{__('whatsapp.workspace_send_via_sms')}</span>
                                    </button>

                                    <button
                                        type="button"
                                        disabled={isRequestingCode}
                                        onClick={() => {
                                            setIsRequestingCode(true);
                                            router.post(`/whatsapp-sender/accounts/${reconnectAccount.id}/request-code`, {
                                                code_method: 'VOICE',
                                                language: 'ar',
                                            }, {
                                                onFinish: () => {
                                                    setIsRequestingCode(false);
                                                    setCodeRequested(true);
                                                }
                                            });
                                        }}
                                        className="bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 dark:hover:bg-sky-900/60 text-sky-800 dark:text-sky-300 p-3 rounded-2xl text-xs font-bold transition flex flex-col items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                    >
                                        <Phone className="w-4 h-4" aria-hidden="true" />
                                        <span>{__('whatsapp.workspace_send_via_call')}</span>
                                    </button>
                                </div>
                            </div>

                            {/* Step 2: Input PIN & Register */}
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    if (!reconnectPin.trim() || reconnectPin.length !== 6) {
                                        toast.error(__('whatsapp.workspace_pin_required'));
                                        return;
                                    }
                                    setIsRegisteringPin(true);
                                    router.post(`/whatsapp-sender/accounts/${reconnectAccount.id}/register`, {
                                        pin: reconnectPin.trim(),
                                    }, {
                                        onFinish: () => {
                                            setIsRegisteringPin(false);
                                            setReconnectAccount(null);
                                        }
                                    });
                                }}
                                className="border-t border-zinc-100 dark:border-zinc-800 pt-4 space-y-3"
                            >
                                <span className="text-xs font-bold text-zinc-700 dark:text-zinc-300 block">{__('whatsapp.workspace_step_enter_code')}</span>
                                <input
                                    type="text"
                                    maxLength={6}
                                    required
                                    placeholder={__('whatsapp.workspace_example', { value: '123456' })}
                                    aria-label={__('whatsapp.workspace_step_enter_code')}
                                    value={reconnectPin}
                                    onChange={e => setReconnectPin(e.target.value)}
                                    className="w-full text-center tracking-widest text-lg font-mono bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                />

                                <button
                                    type="submit"
                                    disabled={isRegisteringPin || reconnectPin.length !== 6}
                                    className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-3 rounded-xl transition shadow-md disabled:opacity-50 cursor-pointer"
                                >
                                    {isRegisteringPin ? __('whatsapp.workspace_activating') : __('whatsapp.workspace_confirm_activate')}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            )}
            {confirmDialog}
        </AuthenticatedLayout>
    );
}
