import React, { useState, useEffect, useRef } from 'react';
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
    Smartphone
} from 'lucide-react';

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

interface Props {
    business: Business;
    accounts: Account[];
    templates: Template[];
    selectedAccountId?: number;
    setSelectedAccountId?: (id: number) => void;
}

export default function CrmChatTab({ business, accounts, templates, selectedAccountId: propAccountId, setSelectedAccountId: propSetAccountId }: Props) {
    const [conversations, setConversations] = useState<Conversation[]>([]);
    const [selectedPhone, setSelectedPhone] = useState<string | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [activeContact, setActiveContact] = useState<{ name: string; phone: string; group_name?: string; custom_fields?: any } | null>(null);
    const [loadingConversations, setLoadingConversations] = useState(false);
    const [loadingMessages, setLoadingMessages] = useState(false);
    const [sending, setSending] = useState(false);

    const [searchQuery, setSearchQuery] = useState('');
    const [channelFilter, setChannelFilter] = useState<'all' | 'ctwa' | 'whatsapp' | 'telegram'>('all');

    // Internal state synced with parent prop if provided
    const firstActiveAccount = accounts.find(a => a.status === 'active') || accounts[0];
    const [internalAccountId, setInternalAccountId] = useState<number>(propAccountId || firstActiveAccount?.id || 0);

    const selectedAccountId = propAccountId !== undefined ? propAccountId : internalAccountId;
    const setSelectedAccountId = propSetAccountId || setInternalAccountId;

    const [messageText, setMessageText] = useState('');
    const [messageType, setMessageType] = useState<'text' | 'template'>('text');
    const [selectedTemplate, setSelectedTemplate] = useState<string>('');
    const [showTemplateModal, setShowTemplateModal] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const messagesEndRef = useRef<HTMLDivElement>(null);

    // Keep selectedAccountId synced if accounts list changes
    useEffect(() => {
        if (!selectedAccountId || !accounts.some(a => a.id === selectedAccountId)) {
            const activeAcc = accounts.find(a => a.status === 'active') || accounts[0];
            if (activeAcc) {
                setSelectedAccountId(activeAcc.id);
            }
        }
    }, [accounts]);

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

    // Initial load & Polling interval (5s)
    useEffect(() => {
        fetchConversations();
        const interval = setInterval(() => {
            fetchConversations();
            if (selectedPhone) {
                fetchChatMessages(selectedPhone);
            }
        }, 5000);
        return () => clearInterval(interval);
    }, [business.id, selectedPhone]);

    // Handle selection change
    useEffect(() => {
        if (selectedPhone) {
            fetchChatMessages(selectedPhone);
        }
    }, [selectedPhone]);

    // Send direct text or template message
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
            setErrorMsg(`Selected account "${currentAccount.name}" is not registered (${currentAccount.status}). Please switch to an Active account.`);
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
        if (channelFilter === 'telegram') return matchesSearch && c.channel === 'telegram';
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
        <div className="flex flex-col h-[calc(100vh-180px)] min-h-[650px] bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden text-zinc-900 dark:text-zinc-100">
            {/* Top Toolbar */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-6 py-4 bg-zinc-50/80 dark:bg-zinc-950/80 border-b border-zinc-200 dark:border-zinc-800 backdrop-blur-md gap-4">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md shrink-0">
                        <MessageSquare className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h2 className="text-base font-bold text-zinc-950 dark:text-zinc-50 tracking-wide flex items-center gap-2">
                            WhatsApp CRM Live Inbox
                            <span className="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 font-mono">
                                Live Webhook Active
                            </span>
                        </h2>
                        <p className="text-xs text-zinc-500">
                            Receive & send messages in real-time for business <strong className="text-zinc-700 dark:text-zinc-300">{business.name}</strong>
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    {/* Balance Badge */}
                    <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 text-xs">
                        <span className="text-zinc-500">Balance:</span>
                        <span className={`font-semibold font-mono ${parseFloat(business.wallet_balance) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`}>
                            ${parseFloat(business.wallet_balance).toFixed(4)}
                        </span>
                    </div>

                    <button
                        onClick={() => { fetchConversations(); if (selectedPhone) fetchChatMessages(selectedPhone); }}
                        className="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition border border-zinc-200 dark:border-zinc-700"
                        title="Refresh Conversations"
                    >
                        <RefreshCw className={`w-4 h-4 ${loadingConversations || loadingMessages ? 'animate-spin text-emerald-500' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Main 3-Column Layout */}
            <div className="grid grid-cols-12 flex-1 overflow-hidden">
                {/* 1. Left Sidebar: Active Conversations List */}
                <div className="col-span-12 md:col-span-4 lg:col-span-3 bg-zinc-50/50 dark:bg-zinc-950/60 border-r border-zinc-200 dark:border-zinc-800 flex flex-col h-full overflow-hidden relative">
                    {/* Search & Filter Bar */}
                    <div className="p-3 border-b border-zinc-200 dark:border-zinc-800 space-y-2">
                        <div className="relative">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-400" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Search customer or phone..."
                                className="w-full pl-9 pr-3 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:border-emerald-500 transition-all"
                            />
                        </div>

                        <div className="flex items-center gap-1 bg-zinc-200/60 dark:bg-zinc-900 p-1 rounded-lg border border-zinc-200/80 dark:border-zinc-800">
                            <button
                                onClick={() => setChannelFilter('all')}
                                className={`flex-1 py-1 text-[10px] font-medium rounded-md transition-all ${channelFilter === 'all' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'}`}
                            >
                                All
                            </button>
                            <button
                                onClick={() => setChannelFilter('ctwa')}
                                className={`flex-1 py-1 text-[10px] font-bold rounded-md transition-all ${channelFilter === 'ctwa' ? 'bg-amber-500 text-black shadow-xs' : 'text-amber-600 dark:text-amber-400 hover:text-amber-700'}`}
                            >
                                🔥 CTWA Ads
                            </button>
                            <button
                                onClick={() => setChannelFilter('whatsapp')}
                                className={`flex-1 py-1 text-[10px] font-medium rounded-md transition-all ${channelFilter === 'whatsapp' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'}`}
                            >
                                WhatsApp
                            </button>
                        </div>
                    </div>

                    {/* Conversations List */}
                    <div className="flex-1 overflow-y-auto p-2 space-y-1">
                        {loadingConversations && conversations.length === 0 ? (
                            <div className="p-8 text-center text-zinc-400 text-xs flex flex-col items-center gap-2">
                                <RefreshCw className="w-5 h-5 animate-spin text-emerald-500" />
                                Loading conversations...
                            </div>
                        ) : filteredConversations.length === 0 ? (
                            <div className="p-8 text-center text-zinc-400 text-xs">
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
                                        className={`p-3 rounded-xl flex items-start gap-3 cursor-pointer transition-all relative ${
                                            isSelected
                                                ? 'bg-emerald-100/70 dark:bg-emerald-950/60 border border-emerald-500/40 shadow-xs'
                                                : 'hover:bg-zinc-100 dark:hover:bg-zinc-800/40 border border-transparent'
                                        }`}
                                    >
                                        <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0 shadow-xs ${isAd ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700' : conv.channel === 'telegram' ? 'bg-sky-100 dark:bg-sky-900/80 text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-700' : 'bg-emerald-600 text-white'}`}>
                                            {conv.contact_name ? conv.contact_name.charAt(0).toUpperCase() : <User className="w-4 h-4" />}
                                        </div>

                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <h4 className="text-xs font-semibold text-zinc-900 dark:text-zinc-200 truncate flex items-center gap-1">
                                                    {conv.contact_name || `+${conv.recipient_phone}`}
                                                    {isAd && (
                                                        <span className="px-1.5 py-0.2 rounded bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/40 font-bold text-[9px]">
                                                            🔥 CTWA
                                                        </span>
                                                    )}
                                                </h4>
                                                <span className="text-[10px] text-zinc-400">
                                                    {formatTimestamp(conv.last_message_at)}
                                                </span>
                                            </div>

                                            <p className="text-[11px] text-zinc-500 dark:text-zinc-400 truncate flex items-center gap-1">
                                                {conv.last_message_direction === 'outbound' && (
                                                    <span className="text-emerald-600 dark:text-emerald-400 font-bold text-[10px]">You:</span>
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

                {/* 2. Middle Panel: Live Chat Messages Thread */}
                <div className="col-span-12 md:col-span-8 lg:col-span-6 bg-white dark:bg-zinc-900 flex flex-col h-full border-r border-zinc-200 dark:border-zinc-800">
                    {selectedPhone ? (
                        <>
                            {/* Chat Header */}
                            <div className="px-5 py-3.5 bg-zinc-50/80 dark:bg-zinc-950/70 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900/80 border border-emerald-200 dark:border-emerald-600/50 flex items-center justify-center text-emerald-800 dark:text-emerald-300 font-bold text-xs">
                                        {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                    </div>
                                    <div>
                                        <h3 className="text-xs font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            {activeContact?.name || selectedPhone}
                                            <a
                                                href={`https://wa.me/${selectedPhone.replace(/[^0-9]/g, '')}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-emerald-600 dark:text-emerald-400 hover:underline text-[10px] flex items-center gap-0.5"
                                                title="Open in WhatsApp Web"
                                            >
                                                <ExternalLink className="w-3 h-3" />
                                            </a>
                                        </h3>
                                        <p className="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono">
                                            +{selectedPhone}
                                        </p>
                                    </div>
                                </div>

                                {/* 72-Hour Golden Window Timer Badge */}
                                <div className="flex items-center gap-3">
                                    {activeReferral ? (
                                        <div className="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/40 text-amber-800 dark:text-amber-300 text-xs font-bold flex items-center gap-1.5 shadow-xs animate-pulse">
                                            <Clock className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                            <span>⚡ 72h Free Ad Window: {calculateRemainingTime(messages.find(m => m.referral)?.created_at ? new Date(new Date(messages.find(m => m.referral)!.created_at).getTime() + 72*3600*1000).toISOString() : undefined) || '71h left'}</span>
                                        </div>
                                    ) : (
                                        <div className="px-3 py-1 rounded-xl bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 text-[11px] flex items-center gap-1.5">
                                            <Clock className="w-3.5 h-3.5 text-emerald-500" />
                                            <span>24h Customer Window</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Chat Thread Messages */}
                            <div className="flex-1 p-4 overflow-y-auto space-y-3 bg-zinc-50/50 dark:bg-zinc-950/80">
                                {/* CTWA Meta Ad Referral Banner Card */}
                                {activeReferral && (
                                    <div className="mb-4 p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/40 rounded-xl text-xs text-amber-900 dark:text-amber-200 flex items-start gap-3 shadow-xs">
                                        <Sparkles className="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                        <div className="flex-1 space-y-1">
                                            <div className="font-bold text-amber-900 dark:text-amber-300 flex items-center justify-between">
                                                <span>🔥 Came from Meta Ad: {activeReferral.headline || 'Click to WhatsApp Campaign'}</span>
                                                {activeReferral.ctwa_clid && (
                                                    <span className="font-mono text-[10px] bg-amber-100 dark:bg-amber-950/60 px-1.5 py-0.5 rounded text-amber-800 dark:text-amber-400">
                                                        CLID: {activeReferral.ctwa_clid}
                                                    </span>
                                                )}
                                            </div>
                                            {activeReferral.body && <p className="text-[11px] text-amber-800 dark:text-amber-200/80">{activeReferral.body}</p>}
                                            <div className="text-[10px] text-amber-700 dark:text-amber-400/70 font-mono">
                                                Ad ID: {activeReferral.source_id || 'N/A'} {activeReferral.source_url && `| ${activeReferral.source_url}`}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {loadingMessages && messages.length === 0 ? (
                                    <div className="h-full flex items-center justify-center text-zinc-400 text-xs gap-2">
                                        <RefreshCw className="w-4 h-4 animate-spin text-emerald-500" />
                                        Loading messages...
                                    </div>
                                ) : messages.length === 0 ? (
                                    <div className="h-full flex flex-col items-center justify-center text-zinc-400 text-xs gap-2">
                                        <MessageSquare className="w-8 h-8 text-zinc-300 dark:text-zinc-700" />
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
                                                    className={`max-w-[80%] rounded-2xl px-4 py-2.5 shadow-xs text-xs leading-relaxed ${
                                                        isInbound
                                                            ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-tl-none border border-zinc-200 dark:border-zinc-700/60'
                                                            : 'bg-emerald-600 text-white rounded-tr-none shadow-sm'
                                                    }`}
                                                >
                                                    <div className="whitespace-pre-wrap font-sans break-words">
                                                        {msg.message_body}
                                                    </div>

                                                    <div className={`mt-1.5 flex items-center justify-end gap-1 text-[10px] ${isInbound ? 'text-zinc-400' : 'text-emerald-100/90'}`}>
                                                        <span>{formatTimestamp(msg.created_at)}</span>
                                                        {!isInbound && (
                                                            <span>
                                                                {msg.status === 'sent' && <Check className="w-3 h-3 text-emerald-100 inline" />}
                                                                {(msg.status === 'delivered' || msg.status === 'read') && <CheckCheck className="w-3 h-3 text-white inline" />}
                                                                {msg.status === 'failed' && <AlertCircle className="w-3 h-3 text-rose-200 inline" />}
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

                            {/* Unregistered Account Warning Banner if selected account is unregistered */}
                            {selectedAccount && selectedAccount.status !== 'active' && (
                                <div className="px-4 py-2 bg-amber-50 dark:bg-amber-950/80 border-t border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs flex items-center justify-between">
                                    <span className="flex items-center gap-2">
                                        <AlertCircle className="w-4 h-4 text-amber-600 shrink-0" />
                                        Selected account "{selectedAccount.name}" ({selectedAccount.phone_number_id}) is not registered ({selectedAccount.status}).
                                    </span>
                                    {accounts.some(a => a.status === 'active') && (
                                        <button
                                            onClick={() => {
                                                const activeAcc = accounts.find(a => a.status === 'active');
                                                if (activeAcc) setSelectedAccountId(activeAcc.id);
                                            }}
                                            className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[11px] font-bold"
                                        >
                                            Switch to Active Account
                                        </button>
                                    )}
                                </div>
                            )}

                            {/* Error banner */}
                            {errorMsg && (
                                <div className="px-4 py-2 bg-rose-50 dark:bg-rose-950/80 border-t border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-center justify-between">
                                    <span className="flex items-center gap-2">
                                        <AlertCircle className="w-4 h-4 text-rose-500 shrink-0" />
                                        {errorMsg}
                                    </span>
                                    <button onClick={() => setErrorMsg(null)} className="text-rose-500 hover:text-rose-700">
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            )}

                            {/* Composer Bottom Form */}
                            <form onSubmit={handleSendMessage} className="p-3 bg-zinc-50/90 dark:bg-zinc-900/90 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2">
                                <div className="flex items-center justify-between text-[11px] text-zinc-500">
                                    <div className="flex items-center gap-3">
                                        <button
                                            type="button"
                                            onClick={() => setMessageType(messageType === 'text' ? 'template' : 'text')}
                                            className="text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 font-medium"
                                        >
                                            <FileText className="w-3.5 h-3.5" />
                                            {messageType === 'text' ? 'Switch to WABA Template' : 'Switch to Direct Text Reply'}
                                        </button>
                                    </div>
                                    <span className="text-zinc-400">Press Enter to send</span>
                                </div>

                                {messageType === 'template' ? (
                                    <div className="flex items-center gap-2">
                                        <select
                                            value={selectedTemplate}
                                            onChange={(e) => setSelectedTemplate(e.target.value)}
                                            className="flex-1 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500"
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
                                            className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-sm"
                                        >
                                            {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                                            Send Template
                                        </button>
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-2">
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
                                            className="flex-1 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl px-3 py-2 text-xs text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:border-emerald-500 resize-none"
                                        />
                                        <button
                                            type="submit"
                                            disabled={sending || !messageText.trim()}
                                            className="h-10 px-5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-sm shrink-0"
                                        >
                                            {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                                            Send
                                        </button>
                                    </div>
                                )}
                            </form>
                        </>
                    ) : (
                        <div className="h-full flex flex-col items-center justify-center p-8 text-center text-zinc-400 gap-3">
                            <div className="w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-emerald-500 shadow-sm">
                                <MessageSquare className="w-8 h-8" />
                            </div>
                            <h3 className="text-sm font-bold text-zinc-700 dark:text-zinc-300">Select a Conversation</h3>
                            <p className="text-xs max-w-sm text-zinc-400">
                                Pick an active customer thread from the left sidebar to view live chat history and send direct replies.
                            </p>
                        </div>
                    )}
                </div>

                {/* 3. Right Sidebar: Customer & CRM Info */}
                <div className="hidden lg:flex col-span-3 bg-zinc-50/60 dark:bg-zinc-950/40 p-4 flex-col gap-4 overflow-y-auto">
                    <h3 className="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1 flex items-center gap-2">
                        <User className="w-4 h-4 text-emerald-500" />
                        CRM Contact Details
                    </h3>

                    {selectedPhone ? (
                        <>
                            <div className="p-4 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3 shadow-sm">
                                <div className="flex items-center gap-3">
                                    <div className="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
                                        {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-bold text-zinc-950 dark:text-zinc-50">{activeContact?.name}</h4>
                                        <p className="text-xs text-emerald-600 dark:text-emerald-400 font-mono">+{selectedPhone}</p>
                                    </div>
                                </div>

                                <div className="pt-2 border-t border-zinc-200 dark:border-zinc-800 space-y-2 text-xs">
                                    <div className="flex items-center justify-between text-zinc-500">
                                        <span>Segment Group:</span>
                                        <span className="text-zinc-800 dark:text-zinc-200 font-medium">{activeContact?.group_name || 'Inbound Customers'}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-zinc-500">
                                        <span>Total Messages:</span>
                                        <span className="text-emerald-600 dark:text-emerald-400 font-bold font-mono">{messages.length}</span>
                                    </div>
                                </div>
                            </div>

                            {/* CTWA Referral Card Details in Sidebar */}
                            {activeReferral && (
                                <div className="p-3.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-700/50 text-xs space-y-2 shadow-sm">
                                    <div className="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold">
                                        <Sparkles className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                        Meta CTWA Ad Attributes
                                    </div>
                                    <div className="space-y-1 text-[11px]">
                                        <div className="text-amber-900 dark:text-amber-200 font-semibold">{activeReferral.headline}</div>
                                        <div className="text-zinc-500 font-mono text-[10px]">Ad ID: {activeReferral.source_id}</div>
                                        {activeReferral.ctwa_clid && (
                                            <div className="text-amber-700 dark:text-amber-400 font-mono text-[10px] break-all">CLID: {activeReferral.ctwa_clid}</div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Wallet Fee Notice */}
                            <div className="p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs space-y-1.5 shadow-sm">
                                <div className="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-bold">
                                    <Sparkles className="w-4 h-4" />
                                    Platform Message Fee
                                </div>
                                <p className="text-[11px] text-zinc-500 leading-relaxed">
                                    Each outbound message deducts <strong>${business.per_message_fee || '0.0010'} USD</strong> directly from your business balance.
                                </p>
                            </div>

                            {/* Quick Templates Shortcuts */}
                            {templates.length > 0 && (
                                <div className="space-y-2">
                                    <h4 className="text-xs font-semibold text-zinc-500">Quick Template Shortcuts</h4>
                                    <div className="space-y-1.5">
                                        {templates.slice(0, 4).map(t => (
                                            <button
                                                key={t.id}
                                                onClick={() => {
                                                    setMessageType('template');
                                                    setSelectedTemplate(t.name);
                                                }}
                                                className="w-full text-left p-2.5 bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-700 dark:text-zinc-300 flex items-center justify-between transition-colors shadow-xs"
                                            >
                                                <span className="truncate font-mono">{t.name}</span>
                                                <span className="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">{t.language}</span>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="p-6 text-center text-zinc-400 text-xs border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
                            Select a customer conversation to inspect CRM metadata.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
