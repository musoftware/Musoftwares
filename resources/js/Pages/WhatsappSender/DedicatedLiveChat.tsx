import React, { useState, useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import {
    Search,
    Send,
    MessageSquare,
    User,
    Check,
    CheckCheck,
    AlertCircle,
    RefreshCw,
    ExternalLink,
    Clock,
    Sparkles,
    X,
    FileText,
    Smartphone,
    ArrowLeft,
    Shield,
    Sliders,
    Layers,
    Plus
} from 'lucide-react';

interface Account {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
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

interface Business {
    id: number;
    name: string;
    wallet_balance: string;
    currency: string;
    per_message_fee: string;
}

interface Conversation {
    recipient_phone: string;
    contact_name: string;
    group_name: string | null;
    last_message: string | null;
    last_message_type: string;
    last_message_status: string;
    last_message_direction: string;
    last_message_at: string;
    channel: string;
    is_ctwa_ad?: boolean;
    referral?: any;
    free_window_expires_at?: string;
}

interface ChatMessage {
    id: number;
    recipient_phone: string;
    channel: string;
    cost_charged: number;
    message_type: string;
    message_body: string | null;
    status: string;
    direction: string;
    meta_message_id: string | null;
    error_message: string | null;
    referral?: any;
    payload?: any;
    created_at: string;
    account_name?: string | null;
}

interface Props {
    business: Business;
    accounts: Account[];
    templates: Template[];
}

export default function DedicatedLiveChat({ business, accounts, templates }: Props) {
    const queryAccountId = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('account_id') : null;
    const initialAccId = queryAccountId ? Number(queryAccountId) : 0;
    const validInitialAcc = accounts.find(a => a.id === initialAccId) || accounts.find(a => a.status === 'active') || accounts[0];
    const [selectedAccountId, setSelectedAccountId] = useState<number>(validInitialAcc?.id || 0);

    const [conversations, setConversations] = useState<Conversation[]>([]);
    const [selectedPhone, setSelectedPhone] = useState<string | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [activeContact, setActiveContact] = useState<{ name: string; phone: string; group_name?: string; custom_fields?: any } | null>(null);
    const [loadingConversations, setLoadingConversations] = useState(false);
    const [loadingMessages, setLoadingMessages] = useState(false);
    const [sending, setSending] = useState(false);

    const [searchQuery, setSearchQuery] = useState('');
    const [channelFilter, setChannelFilter] = useState<'all' | 'ctwa' | 'whatsapp'>('all');

    const [messageText, setMessageText] = useState('');
    const [messageType, setMessageType] = useState<'text' | 'template'>('text');
    const [selectedTemplate, setSelectedTemplate] = useState<string>('');
    const [showTemplateModal, setShowTemplateModal] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const messagesEndRef = useRef<HTMLDivElement>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    // Load active conversations
    const fetchConversations = async () => {
        setLoadingConversations(true);
        try {
            const res = await axios.get(`/whatsapp-sender/businesses/${business.id}/conversations`);
            if (res.data && res.data.conversations) {
                setConversations(res.data.conversations);
                if (!selectedPhone && res.data.conversations.length > 0) {
                    setSelectedPhone(res.data.conversations[0].recipient_phone);
                }
            }
        } catch (err) {
            console.error('Failed to fetch conversations:', err);
        } finally {
            setLoadingConversations(false);
        }
    };

    // Load message history for selected phone
    const fetchChatMessages = async (phone: string) => {
        setLoadingMessages(true);
        setErrorMsg(null);
        try {
            const res = await axios.get(`/whatsapp-sender/businesses/${business.id}/chat-messages`, {
                params: { phone }
            });
            if (res.data) {
                setMessages(res.data.messages || []);
                if (res.data.contact) {
                    setActiveContact(res.data.contact);
                } else {
                    setActiveContact({
                        name: `Customer ${phone}`,
                        phone: phone,
                    });
                }
            }
        } catch (err) {
            console.error('Failed to fetch chat messages:', err);
        } finally {
            setLoadingMessages(false);
            setTimeout(scrollToBottom, 100);
        }
    };

    // Initial load & Polling interval (4s)
    useEffect(() => {
        fetchConversations();
        const interval = setInterval(() => {
            fetchConversations();
            if (selectedPhone) {
                fetchChatMessages(selectedPhone);
            }
        }, 4000);
        return () => clearInterval(interval);
    }, [business.id, selectedPhone]);

    useEffect(() => {
        if (selectedPhone) {
            fetchChatMessages(selectedPhone);
        }
    }, [selectedPhone]);

    const handleSendMessage = async (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        if (!selectedPhone) return;
        if (messageType === 'text' && !messageText.trim()) return;
        if (messageType === 'template' && !selectedTemplate) return;

        const currentAccount = accounts.find(a => a.id === selectedAccountId);
        if (!currentAccount) {
            setErrorMsg('Please connect or select a valid WhatsApp Account first.');
            return;
        }

        if (currentAccount.status !== 'active') {
            setErrorMsg(`Selected account "${currentAccount.name}" is not registered (${currentAccount.status}). Please select an active account.`);
            return;
        }

        setSending(true);
        setErrorMsg(null);

        try {
            const payload: any = {
                whatsapp_account_id: selectedAccountId,
                recipient_phone: selectedPhone,
                message_type: messageType,
            };

            if (messageType === 'text') {
                payload.message_body = messageText.trim();
            } else {
                payload.template_name = selectedTemplate;
                payload.template_language = templates.find(t => t.name === selectedTemplate)?.language || 'en_US';
            }

            const res = await axios.post(`/whatsapp-sender/businesses/${business.id}/send-chat-message`, payload);

            if (res.data && res.data.success) {
                setMessageText('');
                setShowTemplateModal(false);
                setMessageType('text');
                if (res.data.log) {
                    setMessages(prev => [...prev, res.data.log]);
                    setTimeout(scrollToBottom, 100);
                } else {
                    fetchChatMessages(selectedPhone);
                }
                fetchConversations();
            } else {
                setErrorMsg(res.data.error || 'Failed to send message.');
            }
        } catch (err: any) {
            console.error('Send message error:', err);
            setErrorMsg(err.response?.data?.error || err.response?.data?.message || 'Error occurred while sending message.');
        } finally {
            setSending(false);
        }
    };

    // Filtered conversations
    const filteredConversations = conversations.filter(c => {
        const matchesSearch = c.recipient_phone.toLowerCase().includes(searchQuery.toLowerCase()) ||
            c.contact_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (c.last_message && c.last_message.toLowerCase().includes(searchQuery.toLowerCase()));

        if (channelFilter === 'ctwa') return matchesSearch && (c.is_ctwa_ad || c.referral);
        if (channelFilter === 'whatsapp') return matchesSearch && (c.channel === 'whatsapp' || !c.channel);
        return matchesSearch;
    });

    const formatTimestamp = (dateStr: string) => {
        try {
            const d = new Date(dateStr);
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch {
            return dateStr;
        }
    };

    const calculateRemainingTime = (expiresAtStr?: string) => {
        if (!expiresAtStr) return null;
        const now = new Date().getTime();
        const expires = new Date(expiresAtStr).getTime();
        const diffMs = expires - now;
        if (diffMs <= 0) return 'Expired';
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const mins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        return `${hours}h ${mins}m left`;
    };

    const activeReferral = messages.find(m => m.referral)?.referral;
    const selectedAccount = accounts.find(a => a.id === selectedAccountId);

    return (
        <div className="h-screen max-h-screen w-screen overflow-hidden flex bg-zinc-950 text-zinc-100 font-sans antialiased select-none">
            <Head title={`WhatsApp Web Live Chat - ${business.name}`} />

            {/* 1. Leftmost Slim Multi-Account Switcher Drawer */}
            <div className="w-16 md:w-20 bg-zinc-950 border-r border-zinc-800/80 flex flex-col items-center py-4 space-y-6 shrink-0 z-30">
                {/* Return to Workspace Button */}
                <Link
                    href={route('whatsapp.businesses.workspace', business.id)}
                    className="w-10 h-10 rounded-2xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-zinc-400 hover:text-white flex items-center justify-center transition-all shadow-md"
                    title="Return to Business Workspace"
                >
                    <ArrowLeft className="w-5 h-5" />
                </Link>

                <div className="w-8 border-b border-zinc-800/80"></div>

                {/* Multi-Account Drawer List */}
                <div className="flex-1 w-full overflow-y-auto space-y-3 px-2 flex flex-col items-center">
                    <span className="text-[9px] font-bold uppercase tracking-wider text-zinc-500 text-center">Accounts</span>
                    {accounts.length === 0 ? (
                        <div className="text-[10px] text-zinc-500 text-center">No Accounts</div>
                    ) : (
                        accounts.map((acc) => {
                            const isAccSelected = selectedAccountId === acc.id;
                            const isAccActive = acc.status === 'active';
                            return (
                                <button
                                    key={acc.id}
                                    onClick={() => setSelectedAccountId(acc.id)}
                                    className={`relative group w-11 h-11 rounded-2xl flex items-center justify-center transition-all ${
                                        isAccSelected
                                            ? 'bg-emerald-600 text-white shadow-lg ring-2 ring-emerald-500 ring-offset-2 ring-offset-zinc-950'
                                            : 'bg-zinc-900 hover:bg-zinc-800 text-zinc-400 border border-zinc-800'
                                    }`}
                                    title={`${acc.name} (${acc.phone_number_id}) - Status: ${acc.status}`}
                                >
                                    <Smartphone className="w-5 h-5" />
                                    <span className={`absolute -top-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-zinc-950 ${isAccActive ? 'bg-emerald-500' : 'bg-amber-500'}`} />

                                    {/* Tooltip on hover */}
                                    <div className="absolute left-full ml-3 px-3 py-1.5 bg-zinc-900 text-white text-xs font-bold rounded-xl shadow-xl border border-zinc-700 whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-all z-50">
                                        {acc.name} ({acc.phone_number_id})
                                    </div>
                                </button>
                            );
                        })
                    )}
                </div>

                {/* Live Webhook Indicator */}
                <div className="p-2 rounded-xl bg-emerald-950/80 border border-emerald-800/80 text-emerald-400 flex flex-col items-center gap-1 shadow-xs" title="Meta Live Webhook Active">
                    <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span className="text-[9px] font-mono font-bold">LIVE</span>
                </div>
            </div>

            {/* 2. Conversations & Search Column */}
            <div className="w-80 md:w-96 bg-zinc-900 border-r border-zinc-800 flex flex-col h-full shrink-0 z-20">
                {/* Column Header */}
                <div className="p-4 border-b border-zinc-800 bg-zinc-950/60 flex items-center justify-between">
                    <div>
                        <h2 className="text-sm font-bold text-white tracking-wide flex items-center gap-2">
                            WhatsApp Web Inbox
                        </h2>
                        <p className="text-[11px] text-emerald-400 font-mono flex items-center gap-1 mt-0.5">
                            <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                            {selectedAccount ? `${selectedAccount.name} (${selectedAccount.phone_number_id})` : 'No Account Selected'}
                        </p>
                    </div>

                    <button
                        onClick={() => { fetchConversations(); if (selectedPhone) fetchChatMessages(selectedPhone); }}
                        className="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition border border-zinc-700"
                        title="Refresh Conversations"
                    >
                        <RefreshCw className={`w-4 h-4 ${loadingConversations || loadingMessages ? 'animate-spin text-emerald-400' : ''}`} />
                    </button>
                </div>

                {/* Search & CTWA Filter Bar */}
                <div className="p-3 border-b border-zinc-800 space-y-2 bg-zinc-900">
                    <div className="relative">
                        <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-500" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search customer or phone..."
                            className="w-full pl-9 pr-3 py-2 bg-zinc-950 border border-zinc-800 rounded-xl text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-emerald-500 transition-all"
                        />
                    </div>

                    <div className="flex items-center gap-1 bg-zinc-950 p-1 rounded-lg border border-zinc-800">
                        <button
                            onClick={() => setChannelFilter('all')}
                            className={`flex-1 py-1 text-[10px] font-medium rounded-md transition-all ${channelFilter === 'all' ? 'bg-zinc-800 text-white shadow-xs' : 'text-zinc-400 hover:text-white'}`}
                        >
                            All
                        </button>
                        <button
                            onClick={() => setChannelFilter('ctwa')}
                            className={`flex-1 py-1 text-[10px] font-bold rounded-md transition-all ${channelFilter === 'ctwa' ? 'bg-amber-500 text-black shadow-xs' : 'text-amber-400 hover:text-amber-300'}`}
                        >
                            🔥 CTWA Ads
                        </button>
                        <button
                            onClick={() => setChannelFilter('whatsapp')}
                            className={`flex-1 py-1 text-[10px] font-medium rounded-md transition-all ${channelFilter === 'whatsapp' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-400 hover:text-white'}`}
                        >
                            WhatsApp
                        </button>
                    </div>
                </div>

                {/* Scrollable Conversations List */}
                <div className="flex-1 overflow-y-auto p-2 space-y-1 divide-y divide-zinc-800/40">
                    {loadingConversations && conversations.length === 0 ? (
                        <div className="p-8 text-center text-zinc-500 text-xs flex flex-col items-center gap-2">
                            <RefreshCw className="w-5 h-5 animate-spin text-emerald-500" />
                            Loading conversations...
                        </div>
                    ) : filteredConversations.length === 0 ? (
                        <div className="p-8 text-center text-zinc-500 text-xs">
                            No customer conversations found. Incoming webhook messages will appear here automatically.
                        </div>
                    ) : (
                        filteredConversations.map((conv) => {
                            const isSelected = selectedPhone === conv.recipient_phone;
                            const isAd = conv.is_ctwa_ad || conv.referral;
                            return (
                                <div
                                    key={conv.recipient_phone}
                                    onClick={() => setSelectedPhone(conv.recipient_phone)}
                                    className={`p-3 rounded-xl flex items-start gap-3 cursor-pointer transition-all ${
                                        isSelected
                                            ? 'bg-emerald-950/80 border border-emerald-500/50 shadow-md text-white'
                                            : 'hover:bg-zinc-800/60 border border-transparent text-zinc-300'
                                    }`}
                                >
                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold shrink-0 shadow-sm ${isAd ? 'bg-amber-500 text-black' : 'bg-emerald-600 text-white'}`}>
                                        {conv.contact_name ? conv.contact_name.charAt(0).toUpperCase() : <User className="w-4 h-4" />}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1">
                                            <h4 className="text-xs font-semibold truncate flex items-center gap-1">
                                                {conv.contact_name || `+${conv.recipient_phone}`}
                                                {isAd && (
                                                    <span className="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold text-[9px]">
                                                        🔥 CTWA
                                                    </span>
                                                )}
                                            </h4>
                                            <span className="text-[10px] text-zinc-500 font-mono">
                                                {formatTimestamp(conv.last_message_at)}
                                            </span>
                                        </div>

                                        <p className="text-[11px] text-zinc-400 truncate flex items-center gap-1">
                                            {conv.last_message_direction === 'outbound' && (
                                                <span className="text-emerald-400 font-bold text-[10px]">You:</span>
                                            )}
                                            <span>{conv.last_message || `[${conv.last_message_type} message]`}</span>
                                        </p>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>

            {/* 3. Main WhatsApp Web Chat Canvas */}
            <div className="flex-1 bg-zinc-950 flex flex-col h-full relative overflow-hidden">
                {selectedPhone ? (
                    <>
                        {/* Chat Header */}
                        <div className="px-6 py-3.5 bg-zinc-900 border-b border-zinc-800 flex items-center justify-between shadow-sm z-10">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                    {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                </div>
                                <div>
                                    <h3 className="text-xs font-bold text-white flex items-center gap-2">
                                        {activeContact?.name || selectedPhone}
                                        <a
                                            href={`https://wa.me/${selectedPhone.replace(/[^0-9]/g, '')}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-emerald-400 hover:underline text-[10px] flex items-center gap-0.5"
                                            title="Open in WhatsApp Web"
                                        >
                                            <ExternalLink className="w-3.5 h-3.5" />
                                        </a>
                                    </h3>
                                    <p className="text-[11px] text-zinc-400 font-mono">
                                        +{selectedPhone}
                                    </p>
                                </div>
                            </div>

                            {/* 72-Hour Golden Window Timer Badge */}
                            <div className="flex items-center gap-3">
                                {activeReferral ? (
                                    <div className="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/40 text-amber-300 text-xs font-bold flex items-center gap-1.5 shadow-xs animate-pulse">
                                        <Clock className="w-4 h-4 text-amber-400" />
                                        <span>⚡ 72h Free Ad Window: {calculateRemainingTime(messages.find(m => m.referral)?.created_at ? new Date(new Date(messages.find(m => m.referral)!.created_at).getTime() + 72*3600*1000).toISOString() : undefined) || '71h left'}</span>
                                    </div>
                                ) : (
                                    <div className="px-3 py-1 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-300 text-[11px] flex items-center gap-1.5">
                                        <Clock className="w-3.5 h-3.5 text-emerald-400" />
                                        <span>24h Customer Window</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* WhatsApp Web Chat Thread Area */}
                        <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-zinc-950 bg-[radial-gradient(#18181b_1px,transparent_1px)] [background-size:16px_16px]">
                            {/* CTWA Meta Ad Referral Card */}
                            {activeReferral && (
                                <div className="mb-4 p-4 bg-amber-500/10 border border-amber-500/40 rounded-2xl text-xs text-amber-200 flex items-start gap-3 shadow-lg">
                                    <Sparkles className="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
                                    <div className="flex-1 space-y-1">
                                        <div className="font-bold text-amber-300 flex items-center justify-between">
                                            <span>🔥 Came from Meta Ad: {activeReferral.headline || 'Click to WhatsApp Campaign'}</span>
                                            {activeReferral.ctwa_clid && (
                                                <span className="font-mono text-[10px] bg-amber-950 px-2 py-0.5 rounded text-amber-400 border border-amber-800">
                                                    CLID: {activeReferral.ctwa_clid}
                                                </span>
                                            )}
                                        </div>
                                        {activeReferral.body && <p className="text-[11px] text-amber-200/80">{activeReferral.body}</p>}
                                        <div className="text-[10px] text-amber-400/70 font-mono">
                                            Ad ID: {activeReferral.source_id || 'N/A'} {activeReferral.source_url && `| ${activeReferral.source_url}`}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {loadingMessages && messages.length === 0 ? (
                                <div className="h-full flex items-center justify-center text-zinc-500 text-xs gap-2">
                                    <RefreshCw className="w-4 h-4 animate-spin text-emerald-400" />
                                    Loading messages...
                                </div>
                            ) : messages.length === 0 ? (
                                <div className="h-full flex flex-col items-center justify-center text-zinc-500 text-xs gap-2">
                                    <MessageSquare className="w-10 h-10 text-zinc-700" />
                                    No messages in this chat thread yet. Send a message below to start conversation!
                                </div>
                            ) : (
                                messages.map((msg) => {
                                    const isInbound = msg.direction === 'inbound' || msg.status === 'inbound';
                                    return (
                                        <div
                                            key={msg.id}
                                            className={`flex ${isInbound ? 'justify-start' : 'justify-end'}`}
                                        >
                                            <div
                                                className={`max-w-[75%] md:max-w-[65%] rounded-2xl px-4 py-2.5 shadow-md text-xs leading-relaxed ${
                                                    isInbound
                                                        ? 'bg-zinc-800 text-zinc-100 rounded-tl-none border border-zinc-700/80'
                                                        : 'bg-emerald-700 text-white rounded-tr-none shadow-lg'
                                                }`}
                                            >
                                                <div className="whitespace-pre-wrap font-sans break-words text-sm">
                                                    {msg.message_body}
                                                </div>

                                                <div className={`mt-1.5 flex items-center justify-end gap-1 text-[10px] ${isInbound ? 'text-zinc-400' : 'text-emerald-100/90'}`}>
                                                    <span className="font-mono">{formatTimestamp(msg.created_at)}</span>
                                                    {!isInbound && (
                                                        <span>
                                                            {msg.status === 'sent' && <Check className="w-3.5 h-3.5 text-emerald-200 inline" />}
                                                            {(msg.status === 'delivered' || msg.status === 'read') && <CheckCheck className="w-3.5 h-3.5 text-white inline" />}
                                                            {msg.status === 'failed' && <AlertCircle className="w-3.5 h-3.5 text-rose-300 inline" />}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Error banner */}
                        {errorMsg && (
                            <div className="px-6 py-2.5 bg-rose-950/90 border-t border-rose-800 text-rose-200 text-xs flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
                                    {errorMsg}
                                </span>
                                <button onClick={() => setErrorMsg(null)} className="text-rose-400 hover:text-rose-200">
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        )}

                        {/* Bottom WhatsApp Web Composer */}
                        <form onSubmit={handleSendMessage} className="p-4 bg-zinc-900 border-t border-zinc-800 flex flex-col gap-2">
                            <div className="flex items-center justify-between text-[11px] text-zinc-400">
                                <button
                                    type="button"
                                    onClick={() => setMessageType(messageType === 'text' ? 'template' : 'text')}
                                    className="text-emerald-400 hover:underline flex items-center gap-1 font-medium"
                                >
                                    <FileText className="w-3.5 h-3.5" />
                                    {messageType === 'text' ? 'Switch to WABA Template' : 'Switch to Direct Text Reply'}
                                </button>
                                <span>Press Enter to send</span>
                            </div>

                            {messageType === 'template' ? (
                                <div className="flex items-center gap-2">
                                    <select
                                        value={selectedTemplate}
                                        onChange={(e) => setSelectedTemplate(e.target.value)}
                                        className="flex-1 bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500"
                                    >
                                        <option value="">Select Approved WABA Template...</option>
                                        {templates.map(t => (
                                            <option key={t.id} value={t.name}>
                                                {t.name} ({t.language}) - [{t.status}]
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        type="submit"
                                        disabled={sending || !selectedTemplate}
                                        className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-md"
                                    >
                                        {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                                        Send Template
                                    </button>
                                </div>
                            ) : (
                                <div className="flex items-center gap-3">
                                    <textarea
                                        value={messageText}
                                        onChange={(e) => setMessageText(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && !e.shiftKey) {
                                                e.preventDefault();
                                                handleSendMessage();
                                            }
                                        }}
                                        placeholder="Type your WhatsApp message..."
                                        rows={2}
                                        className="flex-1 bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-emerald-500 resize-none"
                                    />
                                    <button
                                        type="submit"
                                        disabled={sending || !messageText.trim()}
                                        className="h-11 px-6 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-md shrink-0"
                                    >
                                        {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                                        Send
                                    </button>
                                </div>
                            )}
                        </form>
                    </>
                ) : (
                    <div className="h-full flex flex-col items-center justify-center p-8 text-center text-zinc-500 gap-4">
                        <div className="w-20 h-20 rounded-3xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-emerald-500 shadow-xl">
                            <MessageSquare className="w-10 h-10" />
                        </div>
                        <h3 className="text-base font-bold text-white">Select a Conversation</h3>
                        <p className="text-xs max-w-sm text-zinc-400 leading-relaxed">
                            Pick an active customer thread from the left sidebar to start real-time messaging on WhatsApp Web.
                        </p>
                    </div>
                )}
            </div>

            {/* 4. Customer & CRM Sidebar (Far Right) */}
            <div className="hidden xl:flex w-72 bg-zinc-900 border-l border-zinc-800 p-5 flex-col gap-5 overflow-y-auto shrink-0">
                <h3 className="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                    <User className="w-4 h-4 text-emerald-400" />
                    CRM Contact Profile
                </h3>

                {selectedPhone ? (
                    <>
                        <div className="p-4 bg-zinc-950 rounded-2xl border border-zinc-800 space-y-3 shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-base shadow-md">
                                    {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-white">{activeContact?.name}</h4>
                                    <p className="text-xs text-emerald-400 font-mono">+{selectedPhone}</p>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-zinc-800 space-y-2 text-xs">
                                <div className="flex items-center justify-between text-zinc-400">
                                    <span>Segment Group:</span>
                                    <span className="text-zinc-200 font-medium">{activeContact?.group_name || 'Inbound Customers'}</span>
                                </div>
                                <div className="flex items-center justify-between text-zinc-400">
                                    <span>Total Messages:</span>
                                    <span className="text-emerald-400 font-bold font-mono">{messages.length}</span>
                                </div>
                            </div>
                        </div>

                        {/* CTWA Referral Card Details */}
                        {activeReferral && (
                            <div className="p-4 bg-amber-500/10 rounded-2xl border border-amber-500/30 text-xs space-y-2 shadow-md">
                                <div className="flex items-center gap-2 text-amber-300 font-bold">
                                    <Sparkles className="w-4 h-4 text-amber-400" />
                                    Meta CTWA Ad Attributes
                                </div>
                                <div className="space-y-1 text-[11px]">
                                    <div className="text-amber-200 font-semibold">{activeReferral.headline}</div>
                                    <div className="text-zinc-400 font-mono text-[10px]">Ad ID: {activeReferral.source_id}</div>
                                    {activeReferral.ctwa_clid && (
                                        <div className="text-amber-400 font-mono text-[10px] break-all">CLID: {activeReferral.ctwa_clid}</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Wallet Fee Notice */}
                        <div className="p-4 bg-zinc-950 rounded-2xl border border-zinc-800 text-xs space-y-1.5 shadow-md">
                            <div className="flex items-center gap-2 text-emerald-400 font-bold">
                                <Sparkles className="w-4 h-4" />
                                Platform Message Fee
                            </div>
                            <p className="text-[11px] text-zinc-400 leading-relaxed">
                                Each outbound message deducts <strong>${business.per_message_fee || '0.0010'} USD</strong> directly from your business balance.
                            </p>
                        </div>
                    </>
                ) : (
                    <div className="p-6 text-center text-zinc-500 text-xs border border-zinc-800 rounded-2xl bg-zinc-950 shadow-md">
                        Select a customer conversation to inspect CRM metadata.
                    </div>
                )}
            </div>
        </div>
    );
}
