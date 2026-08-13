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
    Shield
} from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/Components/ui/tooltip';

interface Account {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
    display_phone_number?: string | null;
    metadata?: any;
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
    const selectedPhoneRef = useRef<string | null>(selectedPhone);

    useEffect(() => {
        selectedPhoneRef.current = selectedPhone;
    }, [selectedPhone]);

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
                if (!selectedPhoneRef.current && res.data.conversations.length > 0) {
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

    // Initial load & Single Polling interval (5s)
    useEffect(() => {
        fetchConversations();
        const interval = setInterval(() => {
            fetchConversations();
            if (selectedPhoneRef.current) {
                fetchChatMessages(selectedPhoneRef.current);
            }
        }, 5000);
        return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [business.id]);

    // Fetch messages immediately when selectedPhone changes
    useEffect(() => {
        if (selectedPhone) {
            fetchChatMessages(selectedPhone);
        } else {
            setMessages([]);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
        <div className="h-screen max-h-screen w-screen overflow-hidden flex bg-[#f0f2f5] text-zinc-800 font-sans antialiased select-none">
            <Head title={`WhatsApp Web Live Chat - ${business.name}`} />

            {/* 1. Leftmost Slim Multi-Account Switcher Drawer */}
            <div className="w-16 md:w-20 bg-[#f0f2f5] border-r border-[#e9edef] flex flex-col items-center py-3 space-y-4 shrink-0 z-10 overflow-hidden">
                <TooltipProvider>
                    {/* Return to Workspace Button */}
                    <Tooltip delayDuration={150}>
                        <TooltipTrigger asChild>
                            <Link
                                href={route('whatsapp.businesses.workspace', business.id)}
                                className="w-10 h-10 rounded-full bg-white hover:bg-zinc-100 border border-zinc-200 text-zinc-600 flex items-center justify-center transition shadow-xs"
                            >
                                <ArrowLeft className="w-5 h-5 text-zinc-700" />
                            </Link>
                        </TooltipTrigger>
                        <TooltipContent side="right" className="bg-zinc-900 text-white font-bold border-zinc-800 text-xs px-3 py-1.5 shadow-xl">
                            Return to Business Workspace
                        </TooltipContent>
                    </Tooltip>

                    <div className="w-8 border-b border-zinc-300"></div>

                    {/* Multi-Account Drawer List */}
                    <div className="flex-1 w-full overflow-y-auto space-y-3 px-2 flex flex-col items-center">
                        <span className="text-[9px] font-bold uppercase tracking-wider text-zinc-500 text-center">Numbers</span>
                        {accounts.length === 0 ? (
                            <div className="text-[10px] text-zinc-400 text-center">No Numbers</div>
                        ) : (
                            accounts.map((acc) => {
                                const isAccSelected = selectedAccountId === acc.id;
                                const isAccActive = acc.status === 'active';
                                return (
                                    <Tooltip key={acc.id} delayDuration={150}>
                                        <TooltipTrigger asChild>
                                            <button
                                                onClick={() => setSelectedAccountId(acc.id)}
                                                className={`relative w-11 h-11 rounded-2xl flex items-center justify-center transition-all ${
                                                    isAccSelected
                                                        ? 'bg-[#00a884] text-white shadow-sm border-2 border-[#00a884]'
                                                        : 'bg-white hover:bg-zinc-100 text-zinc-600 border border-zinc-200'
                                                }`}
                                            >
                                                <Smartphone className="w-5 h-5" />
                                                <span className={`absolute top-0 right-0 w-3 h-3 rounded-full border-2 border-white ${isAccActive ? 'bg-emerald-500' : 'bg-amber-500'}`} />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="right" className="bg-zinc-900 text-white font-bold border-zinc-800 text-xs px-3 py-1.5 shadow-xl">
                                            {acc.name} ({acc.display_phone_number || acc.phone_number_id})
                                        </TooltipContent>
                                    </Tooltip>
                                );
                            })
                        )}
                    </div>
                </TooltipProvider>
            </div>

            {/* 2. Conversations & Search Column */}
            <div className="w-80 md:w-96 bg-white border-r border-[#e9edef] flex flex-col h-full shrink-0 z-10">
                {/* Column Header */}
                <div className="p-3.5 border-b border-[#e9edef] bg-[#f0f2f5] flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <div className="w-9 h-9 rounded-full bg-[#00a884] text-white flex items-center justify-center font-bold text-sm shadow-xs">
                            <MessageSquare className="w-5 h-5" />
                        </div>
                        <div>
                            <h2 className="text-xs font-bold text-zinc-900 tracking-wide flex items-center gap-1.5">
                                WhatsApp Web
                            </h2>
                            <p className="text-[11px] text-[#00a884] font-mono font-bold flex items-center gap-1">
                                <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                {selectedAccount ? `${selectedAccount.name}${selectedAccount.display_phone_number ? ` • ${selectedAccount.display_phone_number}` : ''}` : 'No Line'}
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={() => { fetchConversations(); if (selectedPhone) fetchChatMessages(selectedPhone); }}
                        className="p-2 rounded-lg bg-white hover:bg-zinc-100 text-zinc-600 transition border border-zinc-200"
                        title="Refresh Conversations"
                    >
                        <RefreshCw className={`w-4 h-4 ${loadingConversations || loadingMessages ? 'animate-spin text-[#00a884]' : ''}`} />
                    </button>
                </div>

                {/* Search & CTWA Filter Bar */}
                <div className="p-2.5 border-b border-[#e9edef] space-y-2 bg-[#f0f2f5]">
                    <div className="relative">
                        <Search className="w-4 h-4 absolute left-3 top-2.5 text-zinc-400" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search or start new chat..."
                            className="w-full pl-9 pr-3 py-1.5 bg-white border border-transparent rounded-lg text-xs text-zinc-800 placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-[#00a884] transition-all shadow-xs"
                        />
                    </div>

                    <div className="flex items-center gap-1 bg-white p-1 rounded-lg border border-zinc-200">
                        <button
                            onClick={() => setChannelFilter('all')}
                            className={`flex-1 py-1 text-[10px] font-semibold rounded-md transition-all ${channelFilter === 'all' ? 'bg-[#00a884] text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900'}`}
                        >
                            All
                        </button>
                        <button
                            onClick={() => setChannelFilter('ctwa')}
                            className={`flex-1 py-1 text-[10px] font-bold rounded-md transition-all ${channelFilter === 'ctwa' ? 'bg-amber-500 text-black shadow-xs' : 'text-amber-700 hover:text-amber-800'}`}
                        >
                            🔥 CTWA Ads
                        </button>
                        <button
                            onClick={() => setChannelFilter('whatsapp')}
                            className={`flex-1 py-1 text-[10px] font-semibold rounded-md transition-all ${channelFilter === 'whatsapp' ? 'bg-zinc-800 text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900'}`}
                        >
                            WhatsApp
                        </button>
                    </div>
                </div>

                {/* Scrollable Conversations List */}
                <div className="flex-1 overflow-y-auto divide-y divide-[#f0f2f5]">
                    {loadingConversations && conversations.length === 0 ? (
                        <div className="p-8 text-center text-zinc-400 text-xs flex flex-col items-center gap-2">
                            <RefreshCw className="w-5 h-5 animate-spin text-[#00a884]" />
                            Loading chats...
                        </div>
                    ) : filteredConversations.length === 0 ? (
                        <div className="p-8 text-center text-zinc-400 text-xs">
                            No chats found. Incoming webhook messages will appear here.
                        </div>
                    ) : (
                        filteredConversations.map((conv) => {
                            const isSelected = selectedPhone === conv.recipient_phone;
                            const isAd = conv.is_ctwa_ad || conv.referral;
                            return (
                                <div
                                    key={conv.recipient_phone}
                                    onClick={() => setSelectedPhone(conv.recipient_phone)}
                                    className={`p-3 flex items-start gap-3 cursor-pointer transition-all ${
                                        isSelected
                                            ? 'bg-[#f0f2f5] border-l-4 border-[#00a884]'
                                            : 'hover:bg-zinc-50 border-l-4 border-transparent'
                                    }`}
                                >
                                    <div className={`w-11 h-11 rounded-full flex items-center justify-center text-xs font-bold shrink-0 shadow-xs ${isAd ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'}`}>
                                        {conv.contact_name ? conv.contact_name.charAt(0).toUpperCase() : <User className="w-4 h-4 text-emerald-700" />}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1">
                                            <h4 className="text-xs font-semibold text-zinc-900 truncate flex items-center gap-1">
                                                {conv.contact_name || `+${conv.recipient_phone}`}
                                                {isAd && (
                                                    <span className="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 border border-amber-300 font-bold text-[9px]">
                                                        🔥 CTWA
                                                    </span>
                                                )}
                                            </h4>
                                            <span className="text-[10px] text-zinc-400 font-mono">
                                                {formatTimestamp(conv.last_message_at)}
                                            </span>
                                        </div>

                                        <p className="text-[11px] text-zinc-500 truncate flex items-center gap-1">
                                            {conv.last_message_direction === 'outbound' && (
                                                <span className="text-[#00a884] font-bold text-[10px]">You:</span>
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
            <div className="flex-1 bg-[#efeae2] flex flex-col h-full relative overflow-hidden">
                {selectedPhone ? (
                    <>
                        {/* Chat Header */}
                        <div className="px-5 py-3 bg-[#f0f2f5] border-b border-[#e9edef] flex items-center justify-between shadow-xs z-10">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-full bg-[#00a884] flex items-center justify-center text-white font-bold text-sm shadow-xs">
                                    {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                </div>
                                <div>
                                    <h3 className="text-xs font-bold text-zinc-900 flex items-center gap-2">
                                        {activeContact?.name || selectedPhone}
                                        <a
                                            href={`https://wa.me/${selectedPhone.replace(/[^0-9]/g, '')}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-[#00a884] hover:underline text-[10px] flex items-center gap-0.5"
                                            title="Open in WhatsApp Web"
                                        >
                                            <ExternalLink className="w-3.5 h-3.5" />
                                        </a>
                                    </h3>
                                    <p className="text-[11px] text-zinc-500 font-mono">
                                        +{selectedPhone}
                                    </p>
                                </div>
                            </div>

                            {/* 72-Hour Golden Window Timer Badge */}
                            <div className="flex items-center gap-3">
                                {activeReferral ? (
                                    <div className="px-3 py-1 rounded-full bg-amber-100 border border-amber-300 text-amber-900 text-xs font-bold flex items-center gap-1.5 shadow-xs">
                                        <Clock className="w-4 h-4 text-amber-600" />
                                        <span>⚡ 72h Free Ad Window: {calculateRemainingTime(messages.find(m => m.referral)?.created_at ? new Date(new Date(messages.find(m => m.referral)!.created_at).getTime() + 72*3600*1000).toISOString() : undefined) || '71h left'}</span>
                                    </div>
                                ) : (
                                    <div className="px-3 py-1 rounded-full bg-white border border-zinc-200 text-zinc-600 text-[11px] flex items-center gap-1.5 shadow-xs">
                                        <Clock className="w-3.5 h-3.5 text-[#00a884]" />
                                        <span>24h Customer Window</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* WhatsApp Web Chat Thread Area */}
                        <div className="flex-1 p-6 overflow-y-auto space-y-3 bg-[#efeae2] bg-[radial-gradient(#e5ddd0_1px,transparent_1px)] [background-size:16px_16px]">
                            {/* CTWA Meta Ad Referral Card */}
                            {activeReferral && (
                                <div className="mb-4 p-4 bg-amber-50 border border-amber-300 rounded-2xl text-xs text-amber-900 flex items-start gap-3 shadow-sm">
                                    <Sparkles className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                    <div className="flex-1 space-y-1">
                                        <div className="font-bold text-amber-900 flex items-center justify-between">
                                            <span>🔥 Came from Meta Ad: {activeReferral.headline || 'Click to WhatsApp Campaign'}</span>
                                            {activeReferral.ctwa_clid && (
                                                <span className="font-mono text-[10px] bg-amber-200 px-2 py-0.5 rounded text-amber-900 border border-amber-300">
                                                    CLID: {activeReferral.ctwa_clid}
                                                </span>
                                            )}
                                        </div>
                                        {activeReferral.body && <p className="text-[11px] text-amber-800">{activeReferral.body}</p>}
                                        <div className="text-[10px] text-amber-700 font-mono">
                                            Ad ID: {activeReferral.source_id || 'N/A'} {activeReferral.source_url && `| ${activeReferral.source_url}`}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {loadingMessages && messages.length === 0 ? (
                                <div className="h-full flex items-center justify-center text-zinc-500 text-xs gap-2">
                                    <RefreshCw className="w-4 h-4 animate-spin text-[#00a884]" />
                                    Loading messages...
                                </div>
                            ) : messages.length === 0 ? (
                                <div className="h-full flex flex-col items-center justify-center text-zinc-500 text-xs gap-2">
                                    <MessageSquare className="w-10 h-10 text-zinc-400" />
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
                                                className={`max-w-[75%] md:max-w-[65%] rounded-lg px-3.5 py-2 shadow-xs text-xs leading-relaxed ${
                                                    isInbound
                                                        ? 'bg-white text-zinc-900 rounded-tl-none border border-zinc-200/80'
                                                        : 'bg-[#d9fdd3] text-zinc-900 rounded-tr-none border border-[#b5eba9]'
                                                }`}
                                            >
                                                <div className="whitespace-pre-wrap font-sans break-words text-sm text-zinc-900">
                                                    {msg.message_body}
                                                </div>

                                                <div className="mt-1 flex items-center justify-end gap-1 text-[10px] text-zinc-400">
                                                    <span className="font-mono">{formatTimestamp(msg.created_at)}</span>
                                                    {!isInbound && (
                                                        <span>
                                                            {msg.status === 'sent' && <Check className="w-3.5 h-3.5 text-zinc-500 inline" />}
                                                            {(msg.status === 'delivered' || msg.status === 'read') && <CheckCheck className="w-3.5 h-3.5 text-[#53bdeb] inline" />}
                                                            {msg.status === 'failed' && <AlertCircle className="w-3.5 h-3.5 text-rose-500 inline" />}
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
                            <div className="px-6 py-2 bg-rose-100 border-t border-rose-200 text-rose-800 text-xs flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                                    {errorMsg}
                                </span>
                                <button onClick={() => setErrorMsg(null)} className="text-rose-600 hover:text-rose-800">
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        )}

                        {/* Bottom WhatsApp Web Composer */}
                        <form onSubmit={handleSendMessage} className="p-3 bg-[#f0f2f5] border-t border-[#e9edef] flex flex-col gap-2">
                            <div className="flex items-center justify-between text-[11px] text-zinc-500">
                                <button
                                    type="button"
                                    onClick={() => setMessageType(messageType === 'text' ? 'template' : 'text')}
                                    className="text-[#00a884] hover:underline flex items-center gap-1 font-semibold"
                                >
                                    <FileText className="w-3.5 h-3.5" />
                                    {messageType === 'text' ? 'Switch to Approved WABA Template' : 'Switch to Direct Text Reply'}
                                </button>
                                <span>Press Enter to send</span>
                            </div>

                            {messageType === 'template' ? (
                                <div className="flex items-center gap-2">
                                    <select
                                        value={selectedTemplate}
                                        onChange={(e) => setSelectedTemplate(e.target.value)}
                                        className="flex-1 bg-white border border-zinc-200 rounded-lg px-4 py-2.5 text-xs text-zinc-900 focus:outline-none focus:ring-1 focus:ring-[#00a884]"
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
                                        className="px-6 py-2.5 bg-[#00a884] hover:bg-[#008f70] text-white rounded-lg font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-xs"
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
                                        placeholder="Type a message..."
                                        rows={1}
                                        className="flex-1 bg-white border border-zinc-200 rounded-lg px-4 py-2 text-xs text-zinc-900 placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-[#00a884] resize-none shadow-xs"
                                    />
                                    <button
                                        type="submit"
                                        disabled={sending || !messageText.trim()}
                                        className="h-9 px-5 bg-[#00a884] hover:bg-[#008f70] text-white rounded-lg font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-xs shrink-0"
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
                        <div className="w-20 h-20 rounded-full bg-[#f0f2f5] border border-zinc-200 flex items-center justify-center text-[#00a884] shadow-sm">
                            <MessageSquare className="w-10 h-10" />
                        </div>
                        <h3 className="text-base font-bold text-zinc-800">WhatsApp Web Live Chat</h3>
                        <p className="text-xs max-w-sm text-zinc-500 leading-relaxed">
                            Select a chat thread from the left list to send and receive messages in real-time.
                        </p>
                    </div>
                )}
            </div>

            {/* 4. Customer & CRM Sidebar (Far Right) */}
            <div className="hidden xl:flex w-72 bg-white border-l border-[#e9edef] p-5 flex-col gap-5 overflow-y-auto shrink-0">
                <h3 className="text-xs font-bold text-zinc-500 uppercase tracking-wider flex items-center gap-2">
                    <User className="w-4 h-4 text-[#00a884]" />
                    CRM Contact Info
                </h3>

                {selectedPhone ? (
                    <>
                        <div className="p-4 bg-[#f0f2f5] rounded-2xl border border-zinc-200 space-y-3 shadow-xs">
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-full bg-[#00a884] flex items-center justify-center text-white font-bold text-base shadow-xs">
                                    {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-zinc-900">{activeContact?.name}</h4>
                                    <p className="text-xs text-[#00a884] font-mono">+{selectedPhone}</p>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-zinc-200 space-y-2 text-xs">
                                <div className="flex items-center justify-between text-zinc-500">
                                    <span>Segment Group:</span>
                                    <span className="text-zinc-800 font-medium">{activeContact?.group_name || 'Inbound Customers'}</span>
                                </div>
                                <div className="flex items-center justify-between text-zinc-500">
                                    <span>Total Messages:</span>
                                    <span className="text-[#00a884] font-bold font-mono">{messages.length}</span>
                                </div>
                            </div>
                        </div>

                        {/* CTWA Referral Card Details */}
                        {activeReferral && (
                            <div className="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-xs space-y-2 shadow-xs">
                                <div className="flex items-center gap-2 text-amber-900 font-bold">
                                    <Sparkles className="w-4 h-4 text-amber-600" />
                                    Meta CTWA Ad Attributes
                                </div>
                                <div className="space-y-1 text-[11px]">
                                    <div className="text-amber-900 font-semibold">{activeReferral.headline}</div>
                                    <div className="text-zinc-600 font-mono text-[10px]">Ad ID: {activeReferral.source_id}</div>
                                    {activeReferral.ctwa_clid && (
                                        <div className="text-amber-800 font-mono text-[10px] break-all">CLID: {activeReferral.ctwa_clid}</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Wallet Fee Notice */}
                        <div className="p-4 bg-[#f0f2f5] rounded-2xl border border-zinc-200 text-xs space-y-1.5 shadow-xs">
                            <div className="flex items-center gap-2 text-[#00a884] font-bold">
                                <Shield className="w-4 h-4" />
                                Platform Message Fee
                            </div>
                            <p className="text-[11px] text-zinc-600 leading-relaxed">
                                Outbound messages deduct <strong>${business.per_message_fee || '0.0010'} USD</strong> directly from your business balance.
                            </p>
                        </div>
                    </>
                ) : (
                    <div className="p-6 text-center text-zinc-400 text-xs border border-zinc-200 rounded-2xl bg-[#f0f2f5]">
                        Select a chat thread to view contact info.
                    </div>
                )}
            </div>
        </div>
    );
}
