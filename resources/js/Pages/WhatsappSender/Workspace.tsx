import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlowBuilder from './FlowBuilder';

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
}

interface Account {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
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
    flows
}: Props) {
    const [activeChannel, setActiveChannel] = useState<'whatsapp' | 'telegram'>('whatsapp');
    const [activeTab, setActiveTab] = useState<'send' | 'connectors' | 'templates' | 'groups' | 'schedules' | 'logs' | 'flows' | 'bots' | 'subscribers'>('connectors');
    const [selectedGroup, setSelectedGroup] = useState<ContactGroup | null>(null);

    const [editingFlow, setEditingFlow] = useState<any | null>(null);
    const [isCreatingFlow, setIsCreatingFlow] = useState(false);
    const [selectedSubscriberGroup, setSelectedSubscriberGroup] = useState<any | null>(null);

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
    const sendForm = useForm({
        channel: 'whatsapp',
        whatsapp_account_id: accounts[0]?.id || '',
        telegram_bot_id: bots[0]?.id || '',
        recipient_source: 'single', // single or group
        whatsapp_contact_group_id: contactGroups[0]?.id || '',
        recipient_phone: '',
        message_type: 'text',
        message_body: '',
        template_name: templates[0]?.name || '',
        template_language: 'en_US',
        template_components: [] as any[],
        is_scheduled: false,
        scheduled_at: '',
    });

    useEffect(() => {
        sendForm.setData(data => ({
            ...data,
            channel: activeChannel,
            whatsapp_account_id: accounts[0]?.id || '',
            telegram_bot_id: bots[0]?.id || '',
            message_type: activeChannel === 'telegram' ? 'text' : data.message_type
        }));
    }, [activeChannel, accounts, bots]);

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
                value: mappedVariables[`var_${i + 1}`] || '',
            }));
            componentsPayload = [{
                type: 'body',
                parameters: params
            }];
        }

        const endpoint = sendForm.data.is_scheduled ? '/whatsapp-sender/schedules' : '/whatsapp-sender/send';

        router.post(endpoint, {
            ...sendForm.data,
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
            <Head title={`${business.name} - Hub Workspace`} />

            <div className="flex min-h-[calc(100vh-64px)] bg-zinc-50 dark:bg-zinc-950 font-sans">
                {/* Left Mini Sidebar with Icons only */}
                <div className="w-16 flex flex-col items-center py-6 border-r border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 gap-6 shrink-0 z-10">
                    {/* WhatsApp Icon */}
                    <button
                        onClick={() => {
                            setActiveChannel('whatsapp');
                            setActiveTab('connectors');
                        }}
                        className={`p-2.5 rounded-2xl transition duration-200 relative group border-2 ${
                            activeChannel === 'whatsapp'
                                ? 'border-emerald-500 bg-emerald-500/10 dark:bg-emerald-950/20 shadow-md shadow-emerald-500/10'
                                : 'border-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        }`}
                        title="WhatsApp Hub"
                    >
                        <svg viewBox="0 0 24 24" width="24" height="24" className="w-6 h-6">
                            <circle cx="12" cy="12" r="12" fill="#25D366" />
                            <path d="M12.012 5.5c-3.585 0-6.5 2.915-6.5 6.5 0 1.144.298 2.257.865 3.242L5.5 18.5l3.429-.9c.945.516 2.012.79 3.083.79 3.585 0 6.5-2.915 6.5-6.5s-2.915-6.5-6.5-6.5zm3.834 8.763c-.168.473-.97.857-1.338.91-.334.05-.765.09-2.32-.54-1.99-.8-3.264-2.812-3.363-2.946-.098-.133-.796-1.062-.796-2.025 0-.963.502-1.435.684-1.624.18-.188.397-.236.53-.236.133 0 .266.002.38.006.122.006.286-.05.447.35.168.412.574 1.402.624 1.504.05.102.083.222.014.358-.067.137-.102.222-.205.34-.103.12-.216.266-.308.358-.103.102-.21.214-.09.42.12.205.53 1.077 1.138 1.617.608.54 1.118.708 1.318.808.2.102.318.082.437-.055.12-.137.502-.587.637-.787.135-.2.268-.17.45-.102.184.068 1.17.55 1.37.646.2.1.336.143.38.222.05.078.05.454-.12.928z" fill="#FFFFFF" />
                        </svg>
                        <span className="absolute left-20 bg-zinc-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-30 shadow-lg">
                            WhatsApp Hub
                        </span>
                    </button>

                    {/* Telegram Icon */}
                    <button
                        onClick={() => {
                            setActiveChannel('telegram');
                            setActiveTab('bots');
                        }}
                        className={`p-2.5 rounded-2xl transition duration-200 relative group border-2 ${
                            activeChannel === 'telegram'
                                ? 'border-sky-500 bg-sky-500/10 dark:bg-sky-950/20 shadow-md shadow-sky-500/10'
                                : 'border-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        }`}
                        title="Telegram Hub"
                    >
                        <svg viewBox="0 0 24 24" width="24" height="24" className="w-6 h-6">
                            <circle cx="12" cy="12" r="12" fill="#0088CC" />
                            <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.47-.52-.17l-9.49 5.96-4.11-1.28c-.9-.28-.92-.9.19-1.33l16.1-6.2c.74-.27 1.39.17 1.13 1.25l-2.73 12.87c-.2.93-.76 1.16-1.54.73l-4.17-3.07-2.01 1.94c-.22.22-.41.41-.83.41z" fill="#FFFFFF" />
                        </svg>
                        <span className="absolute left-20 bg-zinc-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-30 shadow-lg">
                            Telegram Hub
                        </span>
                    </button>
                </div>

                {/* Main panel content */}
                <div className="flex-1 py-8 px-6 space-y-8 overflow-y-auto">
                    {/* Header Information Dashboard Card */}
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{business.name}</h1>
                                <span className="bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 text-xs px-2.5 py-1 rounded-full font-medium border border-emerald-200/50 dark:border-emerald-900/30">
                                    Active Workspace
                                </span>
                            </div>
                            <p className="text-zinc-500 dark:text-zinc-400 mt-2 text-sm">
                                Client: <span className="font-semibold text-zinc-700 dark:text-zinc-300">{business.client_name || 'N/A'}</span>
                                {business.client_email && ` | Email: ${business.client_email}`}
                                {business.client_mobile && ` | Mobile: ${business.client_mobile}`}
                                {business.client_whatsapp && ` | WhatsApp: ${business.client_whatsapp}`}
                            </p>
                        </div>

                        <div className="flex flex-col md:flex-row items-stretch md:items-center gap-4 w-full md:w-auto">
                            {/* Balance display */}
                            <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-2xl px-5 py-3 text-right">
                                <span className="text-xs text-zinc-500 dark:text-zinc-400 block font-medium">Business Balance</span>
                                <span className="text-2xl font-black text-zinc-900 dark:text-zinc-50">
                                    ${parseFloat(business.wallet_balance).toFixed(4)} <span className="text-xs font-normal text-zinc-500">{business.currency}</span>
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
                                        className="pl-7 pr-3 py-2 w-24 text-sm bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl focus:ring-1 focus:ring-zinc-900 dark:focus:ring-zinc-200"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={rechargeForm.processing}
                                    className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-sm px-4 py-2 rounded-xl font-medium transition duration-200"
                                >
                                    Recharge
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Invite guest link Card */}
                    <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-3xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 className="font-semibold text-zinc-900 dark:text-zinc-100">Guest Client Connect Invite Link</h2>
                            <p className="text-xs text-zinc-500 mt-1">Send this invitation link to your client so they can easily pair their Meta WhatsApp Business account directly.</p>
                        </div>
                        <div className="flex items-center gap-2 w-full md:w-auto">
                            <input
                                type="text"
                                readOnly
                                value={guestLink}
                                className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-xs px-3 py-2.5 rounded-xl w-full md:w-80 text-zinc-600 dark:text-zinc-300"
                            />
                            <button
                                onClick={() => copyToClipboard(guestLink)}
                                className="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/80 px-4 py-2.5 rounded-xl text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition"
                            >
                                {copied ? 'Copied!' : 'Copy'}
                            </button>
                        </div>
                    </div>

                    {/* Tabs selection */}
                    <div className="flex border-b border-zinc-200 dark:border-zinc-800 overflow-x-auto pb-px gap-6">
                        {(activeChannel === 'whatsapp'
                            ? ['connectors', 'send', 'templates', 'groups', 'schedules', 'logs', 'flows'] as const
                            : ['bots', 'subscribers', 'send', 'schedules', 'logs', 'flows'] as const
                        ).map(tab => (
                            <button
                                key={tab}
                                onClick={() => setActiveTab(tab as any)}
                                className={`pb-4 text-sm font-semibold tracking-tight whitespace-nowrap border-b-2 transition duration-200 capitalize ${
                                    activeTab === tab
                                        ? 'border-zinc-900 dark:border-zinc-50 text-zinc-900 dark:text-zinc-50'
                                        : 'border-transparent text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300'
                                }`}
                            >
                                {tab === 'send' ? 'Campaign / Quick Send' :
                                 tab === 'connectors' ? 'Meta Accounts' :
                                 tab === 'groups' ? 'WhatsApp Groups' :
                                 tab === 'bots' ? 'Telegram Bots' :
                                 tab === 'flows' ? 'Chat Flows (Bot Builder)' :
                                 tab === 'subscribers' ? 'Telegram Subscribers' :
                                 tab}
                            </button>
                        ))}
                    </div>

                {/* Tab Contents */}
                <div>
                    {activeTab === 'send' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            {/* Unified dispatch form */}
                            <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">Create Campaign / Message</h3>
                                <form onSubmit={handleSend} className="space-y-5">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">
                                                {activeChannel === 'telegram' ? 'Select Sender Bot' : 'Select Sender Device'}
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
                                                    {accounts.length === 0 && <option value="">No WhatsApp account connected</option>}
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
                                                    {bots.length === 0 && <option value="">No Telegram bots registered</option>}
                                                </select>
                                            )}
                                        </div>

                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Recipient Source</label>
                                            <select
                                                value={sendForm.data.recipient_source}
                                                onChange={e => sendForm.setData('recipient_source', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                            >
                                                <option value="single">Single Recipient</option>
                                                <option value="group">Bulk Contact Group</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {sendForm.data.recipient_source === 'single' ? (
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">
                                                    {activeChannel === 'telegram' ? 'Recipient Chat ID' : 'Recipient Phone Number'}
                                                </label>
                                                <input
                                                    type="text"
                                                    value={sendForm.data.recipient_phone}
                                                    onChange={e => sendForm.setData('recipient_phone', e.target.value)}
                                                    placeholder={activeChannel === 'telegram' ? 'e.g. 123456789' : 'e.g. 201001234567'}
                                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                />
                                            </div>
                                        ) : (
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Select Target Contact Group</label>
                                                <select
                                                    value={sendForm.data.whatsapp_contact_group_id}
                                                    onChange={e => sendForm.setData('whatsapp_contact_group_id', e.target.value)}
                                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                                >
                                                    {contactGroups.map(gp => (
                                                        <option key={gp.id} value={gp.id}>{gp.name} ({gp.contacts_count} contacts)</option>
                                                    ))}
                                                    {contactGroups.length === 0 && <option value="">No contact groups available</option>}
                                                </select>
                                            </div>
                                        )}
                                    </div>


                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Message Format</label>
                                        <select
                                            value={sendForm.data.message_type}
                                            onChange={e => sendForm.setData('message_type', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                        >
                                            <option value="text">Raw Text Message</option>
                                            <option value="template">Meta Template Message</option>
                                        </select>
                                    </div>

                                    {sendForm.data.message_type === 'text' ? (
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Message Body (HTML Allowed for Telegram)</label>
                                            <textarea
                                                rows={4}
                                                value={sendForm.data.message_body}
                                                onChange={e => sendForm.setData('message_body', e.target.value)}
                                                placeholder="Write your notification message here..."
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                            />
                                        </div>
                                    ) : (
                                        <div className="space-y-4 bg-zinc-50 dark:bg-zinc-950 p-4 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2 font-medium">Select Approved Template</label>
                                                <select
                                                    value={sendForm.data.template_name}
                                                    onChange={e => sendForm.setData('template_name', e.target.value)}
                                                    className="w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                                >
                                                    {templates.map(tpl => (
                                                        <option key={tpl.id} value={tpl.name}>{tpl.name} ({tpl.language})</option>
                                                    ))}
                                                    {templates.length === 0 && <option value="">No templates registered</option>}
                                                </select>
                                            </div>

                                            {selectedTemplate && (
                                                <div className="space-y-3">
                                                    <span className="text-xs text-zinc-500 font-medium">Template Text Content:</span>
                                                    <p className="text-sm bg-white dark:bg-zinc-900 p-3 rounded-xl border border-zinc-100 dark:border-zinc-850 font-mono text-zinc-700 dark:text-zinc-300">
                                                        {bodyText}
                                                    </p>

                                                    {variableCount > 0 && (
                                                        <div className="space-y-3 pt-2">
                                                            <span className="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide block">Map Template Variables</span>
                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                {Array.from({ length: variableCount }).map((_, idx) => (
                                                                    <div key={idx}>
                                                                        <label className="text-xs text-zinc-500 block mb-1">Variable {"{{"}{idx + 1}{"}}"}</label>
                                                                        <input
                                                                            type="text"
                                                                            placeholder="Static value or 'name'/'phone'"
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
                                                                Tip: Type <code>name</code> or <code>phone</code> to map variables dynamically to contact fields, or enter custom text.
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
                                                <h4 className="text-sm font-bold text-zinc-900 dark:text-zinc-50">Schedule for Future Delivery</h4>
                                                <p className="text-xs text-zinc-500">Enable to process this campaign later at a specific Cairo timezone date/time.</p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => sendForm.setData('is_scheduled', !sendForm.data.is_scheduled)}
                                                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                                    sendForm.data.is_scheduled ? 'bg-zinc-900 dark:bg-zinc-100' : 'bg-zinc-200 dark:bg-zinc-850'
                                                }`}
                                            >
                                                <span className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-900 shadow ring-0 transition duration-200 ease-in-out ${
                                                    sendForm.data.is_scheduled ? 'translate-x-5' : 'translate-x-0'
                                                }`} />
                                            </button>
                                        </div>

                                        {sendForm.data.is_scheduled && (
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Delivery Time (Cairo Timezone - Africa/Cairo)</label>
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
                                        {sendForm.data.is_scheduled ? 'Schedule Delivery' : 'Send Immediately'}
                                    </button>
                                </form>
                            </div>

                            {/* Sidebar Guidelines & Fee Info */}
                            <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-3xl p-6 space-y-6">
                                <div>
                                    <h3 className="font-bold text-zinc-900 dark:text-zinc-50 text-lg">Platform Message Fees</h3>
                                    <p className="text-xs text-zinc-500 mt-1">Wallet deductions are processed automatically upon each successfully processed delivery attempt.</p>
                                    <div className="mt-4 border-t border-zinc-200 dark:border-zinc-800 pt-4 space-y-2">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-zinc-500">WhatsApp Dispatch Fee:</span>
                                            <span className="font-bold text-zinc-850 dark:text-zinc-200">${business.per_message_fee} USD / msg</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-zinc-500">Telegram Bot Dispatch Fee:</span>
                                            <span className="font-bold text-zinc-850 dark:text-zinc-200">${business.per_message_fee} USD / msg</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-850 p-4 rounded-2xl">
                                    <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">Dynamic Fields Mapping</h4>
                                    <p className="text-xxs text-zinc-500 leading-relaxed">
                                        You can map variables inside your templates (e.g. <code>{"{{"}1{"}}"}</code>) to specific properties in contact segments:
                                    </p>
                                    <ul className="text-xxs text-zinc-500 list-disc list-inside space-y-1 mt-2">
                                        <li><code>name</code> - Resolves contact Name</li>
                                        <li><code>phone</code> - Resolves contact Phone / Telegram Chat ID</li>
                                        <li><code>custom_fields.FIELD_NAME</code> - Resolves CSV imported fields</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'connectors' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">WhatsApp Accounts</h3>
                                        <p className="text-xs text-zinc-500 mt-1">Direct API integration endpoints powered by Facebook WABA.</p>
                                    </div>
                                    <a
                                        href={facebookLoginUrl}
                                        className="bg-[#1877F2] hover:bg-[#166FE5] text-white text-xs font-bold px-4 py-2.5 rounded-xl transition duration-200 shadow-sm"
                                    >
                                        Log in with Facebook
                                    </a>
                                </div>

                                <div className="space-y-4 mt-6">
                                    {accounts.map(acc => (
                                        <div key={acc.id} className="border border-zinc-100 dark:border-zinc-800 p-4 rounded-2xl flex justify-between items-center">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{acc.name}</span>
                                                    <span className={`text-xxs px-2 py-0.5 rounded-full font-semibold ${
                                                        acc.status === 'active' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-amber-50 text-amber-600'
                                                    }`}>
                                                        {acc.status}
                                                    </span>
                                                </div>
                                                <div className="text-xs text-zinc-500 mt-1">Phone ID: {acc.phone_number_id} | WABA ID: {acc.waba_id}</div>
                                            </div>
                                            <button
                                                onClick={() => {
                                                    if(confirm('Are you sure you want to disconnect this number?')) {
                                                        router.delete(`/whatsapp-sender/accounts/${acc.id}`);
                                                    }
                                                }}
                                                className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                            >
                                                Disconnect
                                            </button>
                                        </div>
                                    ))}
                                    {accounts.length === 0 && (
                                        <p className="text-sm text-zinc-400 text-center py-6">No WhatsApp account connected yet.</p>
                                    )}
                                </div>
                            </div>

                            <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-3xl p-6 space-y-4">
                                <h3 className="font-bold text-zinc-900 dark:text-zinc-50 text-lg font-sans">WABA Connection Guide</h3>
                                <p className="text-xs text-zinc-500 leading-relaxed">
                                    Click "Log in with Facebook" to pair your Meta WhatsApp Business Account. Make sure you have admin rights on the Meta Business Suite portfolio.
                                </p>
                            </div>
                        </div>
                    )}

                    {activeTab === 'bots' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">Telegram Bots</h3>
                                        <p className="text-xs text-zinc-500 mt-1">Register bot tokens to send notifications. Webhook setup will trigger automatically.</p>
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
                                                <div className="text-xs text-emerald-500 font-semibold mt-1">Webhook Active</div>
                                            </div>
                                            <button
                                                onClick={() => {
                                                    if(confirm('Are you sure you want to delete this Telegram bot?')) {
                                                        router.delete(`/whatsapp-sender/telegram-bots/${bot.id}`);
                                                    }
                                                }}
                                                className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    ))}
                                    {bots.length === 0 && (
                                        <p className="text-sm text-zinc-400 text-center py-6">No Telegram bots registered yet.</p>
                                    )}
                                </div>
                            </div>

                            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <div>
                                    <h3 className="text-lg font-bold text-zinc-950 dark:text-zinc-50 font-sans">Register New Bot</h3>
                                    <p className="text-xs text-zinc-500 mt-1">Provide your bot's FatherToken to link it to this business workspace.</p>
                                </div>
                                <form onSubmit={handleAddBot} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Telegram Bot Token</label>
                                        <input
                                            type="text"
                                            value={botForm.data.token}
                                            onChange={e => botForm.setData('token', e.target.value)}
                                            placeholder="Enter token from @BotFather"
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                        {botForm.errors.token && <span className="text-xs text-red-500 mt-1 block">{botForm.errors.token}</span>}
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={botForm.processing}
                                        className="w-full bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-2.5 rounded-xl text-sm font-semibold transition"
                                    >
                                        Verify & Register Bot
                                    </button>
                                </form>
                            </div>
                        </div>
                    )}

                    {activeTab === 'templates' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            {/* Create Template Form */}
                            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">Create Meta Template</h3>
                                <form onSubmit={handleCreateTemplate} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Template Name (alphanumeric & underscore)</label>
                                        <input
                                            type="text"
                                            value={templateForm.data.name}
                                            onChange={e => templateForm.setData('name', e.target.value)}
                                            placeholder="e.g. promo_coupon"
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Category</label>
                                        <select
                                            value={templateForm.data.category}
                                            onChange={e => templateForm.setData('category', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                        >
                                            <option value="UTILITY">UTILITY</option>
                                            <option value="MARKETING">MARKETING</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Language</label>
                                        <select
                                            value={templateForm.data.language}
                                            onChange={e => templateForm.setData('language', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                        >
                                            <option value="en_US">English (US)</option>
                                            <option value="ar">Arabic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Body Text (with variable indicators like {"{{"}1{"}}"})</label>
                                        <textarea
                                            rows={4}
                                            value={templateForm.data.components[0].text}
                                            onChange={e => {
                                                const updated = [...templateForm.data.components];
                                                updated[0].text = e.target.value;
                                                templateForm.setData('components', updated);
                                            }}
                                            placeholder="Write body content here..."
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={templateForm.processing}
                                        className="w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 py-2.5 rounded-xl text-sm font-semibold transition"
                                    >
                                        Submit Template to Meta
                                    </button>
                                </form>
                            </div>

                            {/* Templates Table List */}
                            <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <div className="flex justify-between items-center">
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">Synced Templates</h3>
                                    <button
                                        onClick={() => router.post(`/whatsapp-sender/templates/${business.id}/sync`)}
                                        className="bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800 text-xs px-4 py-2 rounded-xl font-semibold transition duration-200 text-zinc-800 dark:text-zinc-200"
                                    >
                                        Sync from Facebook
                                    </button>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                <th className="py-3 px-2 font-semibold">Name</th>
                                                <th className="py-3 px-2 font-semibold">Category</th>
                                                <th className="py-3 px-2 font-semibold">Language</th>
                                                <th className="py-3 px-2 font-semibold">Status</th>
                                                <th className="py-3 px-2 font-semibold text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {templates.map(tpl => (
                                                <tr key={tpl.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                    <td className="py-3 px-2 font-semibold text-zinc-800 dark:text-zinc-200">{tpl.name}</td>
                                                    <td className="py-3 px-2 text-zinc-500 text-xs">{tpl.category}</td>
                                                    <td className="py-3 px-2 text-zinc-500 text-xs">{tpl.language}</td>
                                                    <td className="py-3 px-2">
                                                        <span className={`text-xxs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                                            tpl.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-amber-50 text-amber-700'
                                                        }`}>
                                                            {tpl.status}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-2 text-right">
                                                        <button
                                                            onClick={() => {
                                                                if(confirm('Are you sure you want to delete this template from Meta?')) {
                                                                    router.delete(`/whatsapp-sender/templates/${tpl.id}`);
                                                                }
                                                            }}
                                                            className="text-red-500 hover:text-red-600 text-xs font-semibold"
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                            {templates.length === 0 && (
                                                <tr>
                                                    <td colSpan={5} className="py-6 text-center text-zinc-400">No synced templates found.</td>
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
                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">Create Segment Group</h3>
                                <form onSubmit={handleCreateGroup} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Group Name</label>
                                        <input
                                            type="text"
                                            value={groupForm.data.name}
                                            onChange={e => groupForm.setData('name', e.target.value)}
                                            placeholder="e.g. Premium Customers"
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Description</label>
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
                                        Create Group
                                    </button>
                                </form>

                                <div className="space-y-3 mt-6">
                                    <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">Available Contact Segments</h4>
                                    {contactGroups.map(gp => (
                                        <div
                                            key={gp.id}
                                            onClick={() => setSelectedGroup(gp)}
                                            className={`p-4 border rounded-2xl cursor-pointer transition ${
                                                selectedGroup?.id === gp.id
                                                    ? 'border-zinc-900 dark:border-zinc-50 bg-zinc-50/50 dark:bg-zinc-850/40'
                                                    : 'border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50/40'
                                            }`}
                                        >
                                            <div className="flex justify-between items-center">
                                                <span className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{gp.name}</span>
                                                <span className="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 px-2.5 py-0.5 rounded-full font-semibold">
                                                    {gp.contacts_count} contacts
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
                                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{selectedGroup.name} Workspace</h3>
                                                <p className="text-xs text-zinc-500 mt-1">{selectedGroup.description || 'No description provided.'}</p>
                                            </div>
                                            <button
                                                onClick={() => {
                                                    if(confirm('Are you sure you want to delete this group? All contacts in this group will be deleted.')) {
                                                        router.delete(`/whatsapp-sender/contact-groups/${selectedGroup.id}`);
                                                        setSelectedGroup(null);
                                                    }
                                                }}
                                                className="text-red-500 hover:text-red-600 text-xs font-semibold"
                                            >
                                                Delete Group
                                            </button>
                                        </div>

                                        <form onSubmit={handleImportContacts} className="space-y-4">
                                            <div>
                                                <label className="text-xs font-semibold text-zinc-500 block mb-1">
                                                    Paste contacts text (Format: <code>phone_or_chat_id,name</code> per line, example: <code>201001234567,John Doe</code>)
                                                </label>
                                                <textarea
                                                    rows={5}
                                                    value={importForm.data.contacts_text}
                                                    onChange={e => importForm.setData('contacts_text', e.target.value)}
                                                    placeholder="201001234567,John Doe&#10;201009876543,Alice Smith&#10;123456789,Telegram User"
                                                    className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-mono"
                                                />
                                            </div>
                                            <button
                                                type="submit"
                                                disabled={importForm.processing}
                                                className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-250 text-white dark:text-zinc-900 text-xs px-4 py-2.5 rounded-xl font-bold transition duration-200"
                                            >
                                                Import / Save Contacts
                                            </button>
                                        </form>
                                    </>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-20 text-center space-y-3">
                                        <span className="text-4xl text-zinc-300">👥</span>
                                        <h3 className="text-zinc-500 dark:text-zinc-400 font-bold">No Contact Group Selected</h3>
                                        <p className="text-zinc-400 dark:text-zinc-500 text-xs max-w-sm">Select a contact group from the left panel to import list numbers, customize CSV fields, and schedule bulk notifications.</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {activeTab === 'schedules' && (
                        <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                            <div>
                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">Active Scheduler Campaigns</h3>
                                <p className="text-xs text-zinc-500 mt-1">Pending and executed schedules managed under Cairo Timezone.</p>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                            <th className="py-3 px-2 font-semibold">Channel</th>
                                            <th className="py-3 px-2 font-semibold">Sender Device / Bot</th>
                                            <th className="py-3 px-2 font-semibold">Recipient</th>
                                            <th className="py-3 px-2 font-semibold">Type</th>
                                            <th className="py-3 px-2 font-semibold">Scheduled Date (Cairo Time)</th>
                                            <th className="py-3 px-2 font-semibold">Status</th>
                                            <th className="py-3 px-2 font-semibold text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {schedules.map(sch => (
                                            <tr key={sch.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                <td className="py-3 px-2">
                                                    <span className={`text-xxs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                                        sch.channel === 'telegram' ? 'bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-400' : 'bg-green-50 text-green-600 dark:bg-green-950 dark:text-green-400'
                                                    }`}>
                                                        {sch.channel}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-2 text-zinc-700 dark:text-zinc-300 font-medium">
                                                    {sch.channel === 'telegram' ? sch.telegram_bot?.name : sch.account?.name}
                                                </td>
                                                <td className="py-3 px-2 text-zinc-600 dark:text-zinc-400">
                                                    {sch.group ? `Group: ${sch.group.name}` : sch.recipient_phone}
                                                </td>
                                                <td className="py-3 px-2 text-zinc-500 text-xs capitalize">{sch.message_type}</td>
                                                <td className="py-3 px-2 text-zinc-500 text-xs font-mono">{new Date(sch.scheduled_at).toLocaleString('en-US', { timeZone: 'Africa/Cairo' })}</td>
                                                <td className="py-3 px-2">
                                                    <span className={`text-xxs px-2 py-0.5 rounded-full font-bold capitalize ${
                                                        sch.status === 'sent' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' :
                                                        sch.status === 'failed' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' :
                                                        'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'
                                                    }`}>
                                                        {sch.status}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-2 text-right">
                                                    {sch.status === 'pending' && (
                                                        <button
                                                            onClick={() => {
                                                                if(confirm('Are you sure you want to cancel this scheduled delivery?')) {
                                                                    router.delete(`/whatsapp-sender/schedules/${sch.id}`);
                                                                }
                                                            }}
                                                            className="text-red-500 hover:text-red-600 text-xs font-semibold"
                                                        >
                                                            Cancel
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {schedules.length === 0 && (
                                            <tr>
                                                <td colSpan={7} className="py-6 text-center text-zinc-400">No scheduled message logs found.</td>
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
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">Unified Message Logs</h3>
                                    <p className="text-xs text-zinc-500 mt-1">Audit log of successfully dispatched notifications across both channels.</p>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                <th className="py-3 px-2 font-semibold">Channel</th>
                                                <th className="py-3 px-2 font-semibold">Sender Device / Bot</th>
                                                <th className="py-3 px-2 font-semibold">Recipient</th>
                                                <th className="py-3 px-2 font-semibold">Content Preview</th>
                                                <th className="py-3 px-2 font-semibold">Fee Charged</th>
                                                <th className="py-3 px-2 font-semibold">Status</th>
                                                <th className="py-3 px-2 font-semibold text-right">Timestamp</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {logs.map(log => (
                                                <tr key={log.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                    <td className="py-3 px-2">
                                                        <span className={`text-xxs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                                            log.channel === 'telegram' ? 'bg-sky-50 text-sky-600 dark:bg-sky-950 dark:text-sky-400' : 'bg-green-50 text-green-600 dark:bg-green-950 dark:text-green-400'
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
                                                        <span className={`text-xxs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                                            log.status === 'sent' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-red-50 text-red-700'
                                                        }`}>
                                                            {log.status}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-2 text-right text-zinc-400 text-xxs font-mono">{new Date(log.created_at).toLocaleString()}</td>
                                                </tr>
                                            ))}
                                            {logs.length === 0 && (
                                                <tr>
                                                    <td colSpan={7} className="py-6 text-center text-zinc-400">No message logs available.</td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Wallet Ledger Transactions */}
                            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                                <div>
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50 font-sans">Business Wallet Transaction History</h3>
                                    <p className="text-xs text-zinc-500 mt-1">Audit log of wallet recharges and bulk campaign deductions.</p>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                <th className="py-3 px-2 font-semibold">Transaction Type</th>
                                                <th className="py-3 px-2 font-semibold">Amount</th>
                                                <th className="py-3 px-2 font-semibold">Balance After</th>
                                                <th className="py-3 px-2 font-semibold">Description</th>
                                                <th className="py-3 px-2 font-semibold text-right">Timestamp</th>
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
                                                    <td colSpan={5} className="py-6 text-center text-zinc-400">No transaction logs found.</td>
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
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">Visual Chat Flows</h3>
                                    <p className="text-xs text-zinc-500 mt-1">Configure automated chatbot response flows for triggers, keywords, delay pacers, and conditions.</p>
                                </div>
                                <button
                                    onClick={() => setIsCreatingFlow(true)}
                                    className="bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs font-bold px-4 py-2.5 rounded-xl transition duration-200"
                                >
                                    + New Chat Flow
                                </button>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {flows.map(flow => (
                                    <div key={flow.id} className="border border-zinc-200 dark:border-zinc-800 p-5 rounded-3xl bg-zinc-50/30 dark:bg-zinc-955/20 flex flex-col justify-between h-48 relative overflow-hidden group">
                                        <div className="absolute right-4 top-4 flex items-center space-x-2">
                                            <span className={`text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                                flow.channel === 'telegram' ? 'bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400' : 'bg-green-100 text-green-600 dark:bg-green-950 dark:text-green-400'
                                            }`}>
                                                {flow.channel}
                                            </span>
                                        </div>

                                        <div>
                                            <h4 className="font-bold text-base text-zinc-900 dark:text-zinc-100">{flow.name}</h4>
                                            <p className="text-xs text-zinc-500 mt-1 font-medium">
                                                Trigger: <span className="font-semibold text-zinc-700 dark:text-zinc-300 capitalize">{flow.trigger_type}</span>
                                            </p>
                                            {flow.trigger_type === 'keyword' && (
                                                <p className="text-xxs text-zinc-400 mt-1 truncate max-w-xs">
                                                    Keywords: {flow.trigger_keywords?.join(', ')}
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex justify-between items-center border-t border-zinc-100 dark:border-zinc-800/80 pt-4 mt-4">
                                            <div className="flex items-center space-x-2">
                                                <button
                                                    onClick={() => router.post(`/whatsapp-sender/bot-flows/${flow.id}/toggle`)}
                                                    className={`text-[10px] font-bold px-2 py-0.5 rounded-md transition ${
                                                        flow.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-zinc-150 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                    }`}
                                                >
                                                    {flow.is_active ? 'Active' : 'Inactive'}
                                                </button>
                                            </div>

                                            <div className="flex items-center space-x-2">
                                                <button
                                                    onClick={() => setEditingFlow(flow)}
                                                    className="text-zinc-650 dark:text-zinc-400 hover:text-zinc-900 text-xs font-semibold px-2 py-1"
                                                >
                                                    Edit Canvas
                                                </button>
                                                <button
                                                    onClick={() => {
                                                        if(confirm('Are you sure you want to delete this flow?')) {
                                                            router.delete(`/whatsapp-sender/bot-flows/${flow.id}`);
                                                        }
                                                    }}
                                                    className="text-red-500 hover:text-red-650 text-xs font-semibold px-2 py-1"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {flows.length === 0 && (
                                    <div className="col-span-full py-12 text-center text-zinc-400 text-sm">
                                        No chatbot response flows configured yet. Click "+ New Chat Flow" to design your first conversation logic.
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
                                    <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">Create Subscriber Group</h3>
                                    <p className="text-xs text-zinc-500 mt-1">Organize bot subscribers into custom target cohorts.</p>
                                </div>
                                
                                <form onSubmit={handleCreateTgGroup} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Select Bot</label>
                                        <select
                                            value={tgGroupForm.data.telegram_bot_id}
                                            onChange={e => tgGroupForm.setData('telegram_bot_id', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        >
                                            {bots.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                                            {bots.length === 0 && <option value="">No bots registered</option>}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Group Name</label>
                                        <input
                                            type="text"
                                            value={tgGroupForm.data.name}
                                            onChange={e => tgGroupForm.setData('name', e.target.value)}
                                            placeholder="e.g. Daily Subscribers"
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 block mb-1">Description</label>
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
                                        Create Subscriber Group
                                    </button>
                                </form>

                                <div className="space-y-3 mt-6 border-t border-zinc-100 dark:border-zinc-800 pt-6">
                                    <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">Subscriber Segments</h4>
                                    {telegramSubscriberGroups.map(gp => (
                                        <div
                                            key={gp.id}
                                            onClick={() => setSelectedSubscriberGroup(selectedSubscriberGroup?.id === gp.id ? null : gp)}
                                            className={`p-4 border rounded-2xl cursor-pointer transition ${
                                                selectedSubscriberGroup?.id === gp.id
                                                    ? 'border-zinc-900 dark:border-zinc-50 bg-zinc-50/50 dark:bg-zinc-850/40'
                                                    : 'border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50/40'
                                            }`}
                                        >
                                            <div className="flex justify-between items-center">
                                                <span className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{gp.name}</span>
                                                <span className="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-650 dark:text-zinc-300 px-2.5 py-0.5 rounded-full font-semibold">
                                                    {gp.subscribers_count} members
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
                                            {selectedSubscriberGroup ? `${selectedSubscriberGroup.name} Members` : 'All Telegram Subscribers'}
                                        </h3>
                                        <p className="text-xs text-zinc-500 mt-1">Subscribers who have initiated communication with registered bots.</p>
                                    </div>
                                    {selectedSubscriberGroup && (
                                        <button
                                            onClick={() => {
                                                if(confirm('Are you sure you want to delete this subscriber group?')) {
                                                    router.delete(`/whatsapp-sender/telegram-subscriber-groups/${selectedSubscriberGroup.id}`);
                                                    setSelectedSubscriberGroup(null);
                                                }
                                            }}
                                            className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                        >
                                            Delete Group
                                        </button>
                                    )}
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                                                <th className="py-3 px-2 font-semibold">Chat ID</th>
                                                <th className="py-3 px-2 font-semibold">Username</th>
                                                <th className="py-3 px-2 font-semibold">Name</th>
                                                <th className="py-3 px-2 font-semibold">Assigned Cohort</th>
                                                <th className="py-3 px-2 font-semibold text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {telegramSubscribers
                                                .filter(sub => !selectedSubscriberGroup || sub.telegram_subscriber_group_id === selectedSubscriberGroup.id)
                                                .map(sub => (
                                                    <tr key={sub.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                                        <td className="py-3 px-2 font-mono text-xs text-zinc-700 dark:text-zinc-300 font-bold">{sub.chat_id}</td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs">@{sub.username || 'n/a'}</td>
                                                        <td className="py-3 px-2 text-zinc-700 dark:text-zinc-200 font-medium">
                                                            {sub.first_name} {sub.last_name}
                                                        </td>
                                                        <td className="py-3 px-2 text-zinc-500 text-xs">
                                                            <select
                                                                value={sub.telegram_subscriber_group_id || ''}
                                                                onChange={e => router.put(`/whatsapp-sender/telegram-subscribers/${sub.id}/group`, {
                                                                    telegram_subscriber_group_id: e.target.value || null
                                                                })}
                                                                className="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg py-1 px-2 text-xs focus:outline-none text-zinc-700 dark:text-zinc-300"
                                                            >
                                                                <option value="">Unassigned</option>
                                                                {telegramSubscriberGroups.map(g => (
                                                                    <option key={g.id} value={g.id}>{g.name}</option>
                                                                ))}
                                                            </select>
                                                        </td>
                                                        <td className="py-3 px-2 text-right">
                                                            <button
                                                                onClick={() => {
                                                                    if(confirm('Are you sure you want to remove this subscriber?')) {
                                                                        router.delete(`/whatsapp-sender/telegram-subscribers/${sub.id}`);
                                                                    }
                                                                }}
                                                                className="text-red-500 hover:text-red-650 text-xs font-semibold"
                                                            >
                                                                Remove
                                                            </button>
                                                        </td>
                                                    </tr>
                                                ))}
                                            {telegramSubscribers.length === 0 && (
                                                <tr>
                                                    <td colSpan={5} className="py-6 text-center text-zinc-400">No active subscribers discovered yet.</td>
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
    </AuthenticatedLayout>
);
}
