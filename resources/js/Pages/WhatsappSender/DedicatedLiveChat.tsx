import { __ } from '@/lib/i18n';
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
    Paperclip,
    Image as ImageIcon,
    FileCode,
    Tag,
    StickyNote,
    SlidersHorizontal,
    Plus,
    Trash2,
    CornerDownLeft,
    ChevronRight,
    Edit2,
    Save,
    Smile,
    Radio,
    Flame,
    Zap
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
    tags?: string[];
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

interface QuickReply {
    shortcut: string;
    title: string;
    message: string;
}

interface Props {
    business: Business;
    accounts: Account[];
    templates: Template[];
}

const AVAILABLE_TAGS = [
    { label: 'VIP', labelKey: 'whatsapp.livechat_tag_vip', color: 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950 dark:text-purple-300' },
    { label: 'Lead', labelKey: 'whatsapp.livechat_tag_lead', color: 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950 dark:text-blue-300' },
    { label: 'Urgent', labelKey: 'whatsapp.livechat_tag_urgent', color: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300' },
    { label: 'Pending', labelKey: 'whatsapp.livechat_tag_pending', color: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300' },
    { label: 'Resolved', labelKey: 'whatsapp.livechat_tag_resolved', color: 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300' },
];

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
    const [freeWindowInfo, setFreeWindowInfo] = useState<any | null>(null);
    const [loadingConversations, setLoadingConversations] = useState(false);
    const [loadingMessages, setLoadingMessages] = useState(false);
    const [sending, setSending] = useState(false);

    const [searchQuery, setSearchQuery] = useState('');
    const [tagFilter, setTagFilter] = useState<string>('all');

    const [messageText, setMessageText] = useState('');
    const [messageType, setMessageType] = useState<'text' | 'template' | 'interactive'>('text');
    const [selectedTemplate, setSelectedTemplate] = useState<string>('');
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    // Rich Features States
    const [quickReplies, setQuickReplies] = useState<QuickReply[]>([]);
    const [showQuickRepliesPopup, setShowQuickRepliesPopup] = useState(false);
    const [filteredQuickReplies, setFilteredQuickReplies] = useState<QuickReply[]>([]);
    const [selectedQuickReplyIndex, setSelectedQuickReplyIndex] = useState(0);

    // Media attachment state
    const [showMediaModal, setShowMediaModal] = useState(false);
    const [mediaType, setMediaType] = useState<'image' | 'document'>('image');
    const [selectedMediaFile, setSelectedMediaFile] = useState<File | null>(null);
    const [mediaCaption, setMediaCaption] = useState('');
    const [mediaPreviewUrl, setMediaPreviewUrl] = useState<string | null>(null);

    // Interactive buttons state
    const [showInteractiveModal, setShowInteractiveModal] = useState(false);
    const [interactiveBody, setInteractiveBody] = useState(__('whatsapp.livechat_default_interactive_body'));
    const [interactiveButtons, setInteractiveButtons] = useState<string[]>([__('whatsapp.livechat_default_btn_yes'), __('whatsapp.livechat_default_btn_human'), __('whatsapp.livechat_default_btn_later')]);

    // Right CRM Sidebar
    const [showCrmSidebar, setShowCrmSidebar] = useState(true);
    const [isEditingContactName, setIsEditingContactName] = useState(false);
    const [contactNameInput, setContactNameInput] = useState('');
    const [contactNotesInput, setContactNotesInput] = useState('');
    const [isSavingCrm, setIsSavingCrm] = useState(false);

    const messagesEndRef = useRef<HTMLDivElement>(null);
    const mediaInputRef = useRef<HTMLInputElement>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    // Load Quick Replies
    const fetchQuickReplies = async () => {
        try {
            const res = await axios.get(`/whatsapp-sender/businesses/${business.id}/quick-replies`);
            if (res.data.success && Array.isArray(res.data.quick_replies)) {
                setQuickReplies(res.data.quick_replies);
            }
        } catch (err) {
            console.error('Failed to load quick replies:', err);
        }
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
                setFreeWindowInfo(res.data.free_window || null);
                if (res.data.contact) {
                    setActiveContact(res.data.contact);
                    setContactNameInput(res.data.contact.name || '');
                    setContactNotesInput(res.data.contact.custom_fields?.internal_notes || '');
                } else {
                    setActiveContact({
                        name: __('whatsapp.livechat_customer_name', { phone }),
                        phone: phone,
                    });
                    setContactNameInput(__('whatsapp.livechat_customer_name', { phone }));
                    setContactNotesInput('');
                }
            }
        } catch (err) {
            console.error('Failed to fetch chat messages:', err);
        } finally {
            setLoadingMessages(false);
            setTimeout(scrollToBottom, 100);
        }
    };

    useEffect(() => {
        fetchConversations();
        fetchQuickReplies();
        const interval = setInterval(() => {
            fetchConversations();
            if (selectedPhoneRef.current) {
                fetchChatMessages(selectedPhoneRef.current);
            }
        }, 5000);
        return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [business.id]);

    useEffect(() => {
        if (selectedPhone) {
            fetchChatMessages(selectedPhone);
        } else {
            setMessages([]);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedPhone]);

    // Handle Quick Reply `/` shortcut in textarea
    const handleTextChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const val = e.target.value;
        setMessageText(val);

        if (val.startsWith('/')) {
            const query = val.toLowerCase();
            const matches = quickReplies.filter(q =>
                q.shortcut.toLowerCase().includes(query) ||
                q.title.toLowerCase().includes(query) ||
                q.message.toLowerCase().includes(query)
            );
            setFilteredQuickReplies(matches.length > 0 ? matches : quickReplies);
            setShowQuickRepliesPopup(true);
            setSelectedQuickReplyIndex(0);
        } else {
            setShowQuickRepliesPopup(false);
        }
    };

    const applyQuickReply = (qr: QuickReply) => {
        setMessageText(qr.message);
        setShowQuickRepliesPopup(false);
    };

    const handleSendMessage = async (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        if (!selectedPhone) return;
        if (messageType === 'text' && !messageText.trim()) return;
        if (messageType === 'template' && !selectedTemplate) return;

        const currentAccount = accounts.find(a => a.id === selectedAccountId);
        if (!currentAccount) {
            setErrorMsg(__('whatsapp.livechat_err_no_account'));
            return;
        }

        if (currentAccount.status !== 'active') {
            setErrorMsg(__('whatsapp.livechat_err_account_inactive', { name: currentAccount.name, status: currentAccount.status }));
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
            } else if (messageType === 'template') {
                const chosenTemplate = templates.find(t => String(t.id) === selectedTemplate || t.name === selectedTemplate);
                if (chosenTemplate) {
                    payload.template_id = chosenTemplate.id;
                    payload.template_name = chosenTemplate.name;
                    payload.template_language = chosenTemplate.language;
                } else {
                    payload.template_name = selectedTemplate;
                    payload.template_language = 'en_US';
                }
            }

            const res = await axios.post(`/whatsapp-sender/businesses/${business.id}/send-chat-message`, payload);

            if (res.data && res.data.success) {
                setMessageText('');
                setMessageType('text');
                setShowQuickRepliesPopup(false);
                if (res.data.log) {
                    setMessages(prev => [...prev, res.data.log]);
                    setTimeout(scrollToBottom, 100);
                } else {
                    fetchChatMessages(selectedPhone);
                }
                fetchConversations();
            } else {
                setErrorMsg(res.data.error || __('whatsapp.livechat_err_send_failed'));
            }
        } catch (err: any) {
            console.error('Send message error:', err);
            setErrorMsg(err.response?.data?.error || err.response?.data?.message || __('whatsapp.livechat_err_send_error'));
        } finally {
            setSending(false);
        }
    };

    const handleSendMedia = async () => {
        if (!selectedPhone || !selectedMediaFile) return;
        setSending(true);
        setErrorMsg(null);

        const formData = new FormData();
        formData.append('whatsapp_account_id', String(selectedAccountId));
        formData.append('recipient_phone', selectedPhone);
        formData.append('message_type', mediaType);
        formData.append('media_file', selectedMediaFile);
        if (mediaCaption.trim()) {
            formData.append('caption', mediaCaption.trim());
        }

        try {
            const res = await axios.post(`/whatsapp-sender/businesses/${business.id}/send-chat-message`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data && res.data.success) {
                setShowMediaModal(false);
                setSelectedMediaFile(null);
                setMediaCaption('');
                setMediaPreviewUrl(null);
                if (res.data.log) {
                    setMessages(prev => [...prev, res.data.log]);
                    setTimeout(scrollToBottom, 100);
                } else {
                    fetchChatMessages(selectedPhone);
                }
                fetchConversations();
            } else {
                setErrorMsg(res.data.error || __('whatsapp.livechat_err_media_failed'));
            }
        } catch (err: any) {
            setErrorMsg(err.response?.data?.error || __('whatsapp.livechat_err_media_upload'));
        } finally {
            setSending(false);
        }
    };

    const handleSendInteractiveButtons = async () => {
        if (!selectedPhone || !interactiveBody.trim()) return;
        setSending(true);
        setErrorMsg(null);

        const validButtons = interactiveButtons.map(b => b.trim()).filter(Boolean);

        try {
            const res = await axios.post(`/whatsapp-sender/businesses/${business.id}/send-chat-message`, {
                whatsapp_account_id: selectedAccountId,
                recipient_phone: selectedPhone,
                message_type: 'interactive',
                message_body: interactiveBody.trim(),
                buttons: validButtons,
            });

            if (res.data && res.data.success) {
                setShowInteractiveModal(false);
                if (res.data.log) {
                    setMessages(prev => [...prev, res.data.log]);
                    setTimeout(scrollToBottom, 100);
                } else {
                    fetchChatMessages(selectedPhone);
                }
                fetchConversations();
            } else {
                setErrorMsg(res.data.error || __('whatsapp.livechat_err_interactive'));
            }
        } catch (err: any) {
            setErrorMsg(err.response?.data?.error || __('whatsapp.livechat_err_interactive'));
        } finally {
            setSending(false);
        }
    };

    const handleSaveCrmDetails = async () => {
        if (!selectedPhone) return;
        setIsSavingCrm(true);
        try {
            const currentTags = activeContact?.custom_fields?.tags || [];
            const res = await axios.post(`/whatsapp-sender/businesses/${business.id}/contacts/crm`, {
                phone: selectedPhone,
                name: contactNameInput,
                tags: currentTags,
                internal_notes: contactNotesInput,
            });
            if (res.data.success) {
                setActiveContact(res.data.contact);
                setIsEditingContactName(false);
                setConversations(prev => prev.map(c => {
                    const cPhone = c.recipient_phone.replace(/[^0-9]/g, '');
                    const sPhone = selectedPhone.replace(/[^0-9]/g, '');
                    if (cPhone === sPhone || c.recipient_phone === selectedPhone) {
                        return { ...c, contact_name: res.data.contact.name || c.contact_name, tags: currentTags };
                    }
                    return c;
                }));
                fetchConversations();
            }
        } catch (err) {
            console.error('Failed to save CRM details:', err);
        } finally {
            setIsSavingCrm(false);
        }
    };

    const handleToggleTag = async (tagLabel: string) => {
        if (!selectedPhone) return;
        const currentTags: string[] = activeContact?.custom_fields?.tags || [];
        const newTags = currentTags.includes(tagLabel)
            ? currentTags.filter(t => t !== tagLabel)
            : [...currentTags, tagLabel];

        try {
            const res = await axios.post(`/whatsapp-sender/businesses/${business.id}/contacts/crm`, {
                phone: selectedPhone,
                name: contactNameInput || activeContact?.name,
                tags: newTags,
                internal_notes: contactNotesInput,
            });
            if (res.data.success) {
                setActiveContact(res.data.contact);
                setConversations(prev => prev.map(c => {
                    const cPhone = c.recipient_phone.replace(/[^0-9]/g, '');
                    const sPhone = selectedPhone.replace(/[^0-9]/g, '');
                    if (cPhone === sPhone || c.recipient_phone === selectedPhone) {
                        return { ...c, tags: newTags, contact_name: res.data.contact.name || c.contact_name };
                    }
                    return c;
                }));
                fetchConversations();
            }
        } catch (err) {
            console.error('Failed to update tags:', err);
        }
    };

    // Filtered conversations
    const filteredConversations = conversations.filter(c => {
        const matchesSearch = c.recipient_phone.toLowerCase().includes(searchQuery.toLowerCase()) ||
            c.contact_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (c.last_message && c.last_message.toLowerCase().includes(searchQuery.toLowerCase()));

        if (!matchesSearch) return false;
        if (tagFilter === 'all') return true;
        if (tagFilter === 'ctwa') return c.is_ctwa_ad || c.referral;
        const contactTags = c.tags || [];
        return contactTags.includes(tagFilter);
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
        if (diffMs <= 0) return __('whatsapp.livechat_expired');
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const mins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        return __('whatsapp.livechat_time_left', { hours, mins });
    };

    const activeReferral = messages.find(m => m.referral)?.referral;
    const selectedAccount = accounts.find(a => a.id === selectedAccountId);
    const activeContactTags: string[] = activeContact?.custom_fields?.tags || [];

    const isWindowActive = freeWindowInfo?.is_active ?? true;

    return (
        <div className="h-screen max-h-screen w-screen overflow-hidden flex bg-[#f0f2f5] dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100 font-sans antialiased select-none">
            <Head title={__('whatsapp.livechat_page_title', { name: business.name })} />

            {/* 1. Leftmost Slim Multi-Account Switcher Drawer */}
            <div className="w-16 md:w-20 bg-[#f0f2f5] dark:bg-zinc-900 border-r border-[#e9edef] dark:border-zinc-800 flex flex-col items-center py-3 space-y-4 shrink-0 z-10 overflow-hidden">
                <TooltipProvider>
                    {/* Return to Workspace Button */}
                    <Tooltip delayDuration={150}>
                        <TooltipTrigger asChild>
                            <Link
                                href={route('whatsapp.businesses.workspace', business.id)}
                                aria-label={__('whatsapp.livechat_return_workspace')}
                                className="w-10 h-10 rounded-full bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 flex items-center justify-center transition shadow-xs cursor-pointer"
                            >
                                <ArrowLeft className="w-5 h-5 text-zinc-700 dark:text-zinc-200" />
                            </Link>
                        </TooltipTrigger>
                        <TooltipContent side="right" className="bg-zinc-900 text-white font-bold border-zinc-800 text-xs px-3 py-1.5 shadow-xl">
                            {__('whatsapp.livechat_return_workspace')}
                        </TooltipContent>
                    </Tooltip>

                    <div className="w-8 border-b border-zinc-300 dark:border-zinc-700"></div>

                    {/* Multi-Account Drawer List */}
                    <div className="flex-1 w-full overflow-y-auto space-y-3 px-2 flex flex-col items-center">
                        <span className="text-[9px] font-bold uppercase tracking-wider text-zinc-500 text-center">{__('whatsapp.livechat_numbers')}</span>
                        {accounts.length === 0 ? (
                            <div className="text-[10px] text-zinc-400 text-center">{__('whatsapp.livechat_no_numbers')}</div>
                        ) : (
                            accounts.map((acc) => {
                                const isAccSelected = selectedAccountId === acc.id;
                                const isAccActive = acc.status === 'active';
                                return (
                                    <Tooltip key={acc.id} delayDuration={150}>
                                        <TooltipTrigger asChild>
                                            <button
                                                onClick={() => setSelectedAccountId(acc.id)}
                                                aria-label={`${acc.name} (${acc.display_phone_number || acc.phone_number_id})`}
                                                className={`relative w-11 h-11 rounded-2xl flex items-center justify-center transition-all cursor-pointer ${
                                                    isAccSelected
                                                        ? 'bg-[#00a884] text-white shadow-sm border-2 border-[#00a884]'
                                                        : 'bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700'
                                                }`}
                                            >
                                                <Smartphone className="w-5 h-5" />
                                                <span className={`absolute top-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-zinc-800 ${isAccActive ? 'bg-emerald-500' : 'bg-amber-500'}`} />
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
            <div className="w-80 md:w-96 bg-white dark:bg-zinc-900 border-r border-[#e9edef] dark:border-zinc-800 flex flex-col h-full shrink-0 z-10">
                {/* Column Header */}
                <div className="p-3.5 border-b border-[#e9edef] dark:border-zinc-800 bg-[#f0f2f5] dark:bg-zinc-900 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <div className="w-9 h-9 rounded-full bg-[#00a884] text-white flex items-center justify-center font-bold text-sm shadow-xs">
                            <MessageSquare className="w-5 h-5" />
                        </div>
                        <div>
                            <h2 className="text-xs font-bold text-zinc-900 dark:text-zinc-100 tracking-wide flex items-center gap-1.5">
                                WhatsApp Web
                            </h2>
                            <p className="text-[11px] text-[#00a884] font-mono font-bold flex items-center gap-1">
                                <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                {selectedAccount ? `${selectedAccount.name}${selectedAccount.display_phone_number ? ` • ${selectedAccount.display_phone_number}` : ''}` : __('whatsapp.livechat_no_line')}
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={() => { fetchConversations(); if (selectedPhone) fetchChatMessages(selectedPhone); }}
                        className="p-2 rounded-lg bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition border border-zinc-200 dark:border-zinc-700 cursor-pointer"
                        title={__('whatsapp.livechat_refresh')}
                        aria-label={__('whatsapp.livechat_refresh')}
                    >
                        <RefreshCw className={`w-4 h-4 ${loadingConversations || loadingMessages ? 'animate-spin text-[#00a884]' : ''}`} />
                    </button>
                </div>

                {/* Search & Tag Filter Pills */}
                <div className="p-2.5 border-b border-[#e9edef] dark:border-zinc-800 space-y-2 bg-[#f0f2f5] dark:bg-zinc-900">
                    <div className="relative">
                        <Search className="w-4 h-4 absolute left-3 top-2.5 text-zinc-400" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder={__('whatsapp.livechat_search_placeholder')}
                            aria-label={__('whatsapp.livechat_search_placeholder')}
                            className="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-zinc-800 border border-transparent rounded-lg text-xs text-zinc-800 dark:text-zinc-100 placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-[#00a884] transition-all shadow-xs"
                        />
                    </div>

                    {/* Tag Filter Pills */}
                    <div className="flex items-center gap-1 overflow-x-auto pb-1 no-scrollbar text-[10px]">
                        <button
                            onClick={() => setTagFilter('all')}
                            className={`px-2.5 py-1 font-semibold rounded-lg shrink-0 transition-all cursor-pointer ${tagFilter === 'all' ? 'bg-[#00a884] text-white shadow-xs' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700'}`}
                        >
                            {__('general.all')}
                        </button>
                        <button
                            onClick={() => setTagFilter('ctwa')}
                            className={`px-2.5 py-1 font-bold rounded-lg shrink-0 transition-all cursor-pointer ${tagFilter === 'ctwa' ? 'bg-amber-500 text-black shadow-xs' : 'bg-white dark:bg-zinc-800 text-amber-600 border border-amber-200 dark:border-amber-900'}`}
                        >
                            <Flame className="inline w-3 h-3 me-0.5" aria-hidden="true" />{__('whatsapp.livechat_ctwa_ads')}
                        </button>
                        {AVAILABLE_TAGS.map(t => (
                            <button
                                key={t.label}
                                onClick={() => setTagFilter(tagFilter === t.label ? 'all' : t.label)}
                                className={`px-2.5 py-1 font-semibold rounded-lg shrink-0 transition-all cursor-pointer ${tagFilter === t.label ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700'}`}
                            >
                                {__(t.labelKey)}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Scrollable Conversations List */}
                <div className="flex-1 overflow-y-auto divide-y divide-[#f0f2f5] dark:divide-zinc-800/60">
                    {loadingConversations && conversations.length === 0 ? (
                        <div className="p-8 text-center text-zinc-400 text-xs flex flex-col items-center gap-2">
                            <RefreshCw className="w-5 h-5 animate-spin text-[#00a884]" />
                            {__('whatsapp.livechat_loading_chats')}
                        </div>
                    ) : filteredConversations.length === 0 ? (
                        <div className="p-8 text-center text-zinc-400 text-xs">
                            {__('whatsapp.livechat_no_match')}
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
                                            ? 'bg-[#f0f2f5] dark:bg-zinc-800 border-l-4 border-[#00a884]'
                                            : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50 border-l-4 border-transparent'
                                    }`}
                                >
                                    <div className={`w-11 h-11 rounded-full flex items-center justify-center text-xs font-bold shrink-0 shadow-xs ${isAd ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200'}`}>
                                        {conv.contact_name ? conv.contact_name.charAt(0).toUpperCase() : <User className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1">
                                            <h4 className="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate flex items-center gap-1">
                                                {conv.contact_name || `+${conv.recipient_phone}`}
                                                {isAd && (
                                                    <span className="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 font-bold text-[9px]">
                                                        <Flame className="inline w-2.5 h-2.5 me-0.5" aria-hidden="true" />CTWA
                                                    </span>
                                                )}
                                            </h4>
                                            <span className="text-[10px] text-zinc-400 font-mono">
                                                {formatTimestamp(conv.last_message_at)}
                                            </span>
                                        </div>

                                        <p className="text-[11px] text-zinc-500 truncate flex items-center gap-1">
                                            {conv.last_message_direction === 'outbound' && (
                                                <span className="text-[#00a884] font-bold text-[10px]">{__('whatsapp.livechat_you')}</span>
                                            )}
                                            <span>{conv.last_message || __('whatsapp.livechat_type_message', { type: conv.last_message_type })}</span>
                                        </p>

                                        {/* Render conversation tags */}
                                        {conv.tags && conv.tags.length > 0 && (
                                            <div className="flex flex-wrap gap-1 mt-1.5">
                                                {conv.tags.map(t => {
                                                    const tagMeta = AVAILABLE_TAGS.find(at => at.label === t);
                                                    return (
                                                        <span
                                                            key={t}
                                                            className={`px-1.5 py-0.5 rounded text-[9px] font-bold border ${
                                                                tagMeta ? tagMeta.color : 'bg-purple-100 text-purple-800 border-purple-300'
                                                            }`}
                                                        >
                                                            {tagMeta ? __(tagMeta.labelKey) : t}
                                                        </span>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>

            {/* 3. Main WhatsApp Web Chat Canvas */}
            <div className="flex-1 bg-[#efeae2] dark:bg-zinc-900 flex flex-col h-full relative overflow-hidden">
                {selectedPhone ? (
                    <>
                        {/* Chat Header */}
                        <div className="px-5 py-3 bg-[#f0f2f5] dark:bg-zinc-900 border-b border-[#e9edef] dark:border-zinc-800 flex items-center justify-between shadow-xs z-10">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-full bg-[#00a884] flex items-center justify-center text-white font-bold text-sm shadow-xs">
                                    {activeContact?.name ? activeContact.name.charAt(0).toUpperCase() : 'C'}
                                </div>
                                <div>
                                    <h3 className="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        {activeContact?.name || selectedPhone}
                                        <a
                                            href={`https://wa.me/${selectedPhone.replace(/[^0-9]/g, '')}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-[#00a884] hover:underline text-[10px] flex items-center gap-0.5"
                                            title={__('whatsapp.livechat_open_wa_web')}
                                            aria-label={__('whatsapp.livechat_open_wa_web')}
                                        >
                                            <ExternalLink className="w-3.5 h-3.5" />
                                        </a>
                                    </h3>
                                    <p className="text-[11px] text-zinc-500 font-mono">
                                        +{selectedPhone}
                                    </p>
                                </div>
                            </div>

                            {/* Window Badges & CRM Toggle */}
                            <div className="flex items-center gap-3">
                                {activeReferral ? (
                                    <div className="px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 border border-amber-300 text-amber-900 dark:text-amber-300 text-xs font-bold flex items-center gap-1.5 shadow-xs">
                                        <Clock className="w-4 h-4 text-amber-600" />
                                        <span className="flex items-center gap-1"><Zap className="w-3.5 h-3.5" aria-hidden="true" />{__('whatsapp.livechat_free_ad_window')}</span>
                                    </div>
                                ) : (
                                    <div className={`px-3 py-1 rounded-full border text-[11px] font-semibold flex items-center gap-1.5 shadow-xs ${
                                        isWindowActive
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
                                            : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
                                    }`}>
                                        <Clock className={`w-3.5 h-3.5 ${isWindowActive ? 'text-[#00a884]' : 'text-rose-500'}`} />
                                        <span>{isWindowActive ? __('whatsapp.livechat_session_active') : __('whatsapp.livechat_session_expired')}</span>
                                    </div>
                                )}

                                <button
                                    onClick={() => setShowCrmSidebar(!showCrmSidebar)}
                                    className={`p-2 rounded-xl border transition-all cursor-pointer ${
                                        showCrmSidebar
                                            ? 'bg-[#00a884] text-white border-[#00a884]'
                                            : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700'
                                    }`}
                                    title={__('whatsapp.livechat_toggle_crm')}
                                    aria-label={__('whatsapp.livechat_toggle_crm')}
                                >
                                    <SlidersHorizontal className="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {/* 24h Policy Safeguard Alert if window expired */}
                        {!isWindowActive && (
                            <div className="px-4 py-2 bg-amber-500 text-zinc-950 text-xs font-bold flex items-center justify-between shadow-xs">
                                <span className="flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 shrink-0" />
                                    {__('whatsapp.livechat_session_expired_notice')}
                                </span>
                                <button
                                    onClick={() => setMessageType('template')}
                                    className="px-3 py-1 bg-zinc-900 text-white rounded-lg text-[11px] hover:bg-black transition-all cursor-pointer"
                                >
                                    {__('whatsapp.livechat_select_template')}
                                </button>
                            </div>
                        )}

                        {/* WhatsApp Web Chat Thread Area */}
                        <div className="flex-1 p-6 overflow-y-auto space-y-3 bg-[#efeae2] dark:bg-zinc-950 bg-[radial-gradient(#e5ddd0_1px,transparent_1px)] dark:bg-[radial-gradient(#27272a_1px,transparent_1px)] [background-size:16px_16px]">
                            {/* CTWA Meta Ad Referral Card */}
                            {activeReferral && (
                                <div className="mb-4 p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-2xl text-xs text-amber-900 dark:text-amber-200 flex items-start gap-3 shadow-sm">
                                    <Sparkles className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                    <div className="flex-1 space-y-1">
                                        <div className="font-bold text-amber-900 dark:text-amber-200 flex items-center justify-between">
                                            <span className="flex items-center gap-1"><Flame className="w-3.5 h-3.5" aria-hidden="true" />{__('whatsapp.livechat_came_from_ad', { headline: activeReferral.headline || __('whatsapp.livechat_ctwa_campaign') })}</span>
                                            {activeReferral.ctwa_clid && (
                                                <span className="font-mono text-[10px] bg-amber-200 dark:bg-amber-900 px-2 py-0.5 rounded text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700">
                                                    CLID: {activeReferral.ctwa_clid}
                                                </span>
                                            )}
                                        </div>
                                        {activeReferral.body && <p className="text-[11px] text-amber-800 dark:text-amber-300">{activeReferral.body}</p>}
                                        <div className="text-[10px] text-amber-700 dark:text-amber-400 font-mono">
                                            {__('whatsapp.livechat_ad_id')} {activeReferral.source_id || __('general.n_a')} {activeReferral.source_url && `| ${activeReferral.source_url}`}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {loadingMessages && messages.length === 0 ? (
                                <div className="h-full flex items-center justify-center text-zinc-500 text-xs gap-2">
                                    <RefreshCw className="w-4 h-4 animate-spin text-[#00a884]" />
                                    {__('whatsapp.livechat_loading_messages')}
                                </div>
                            ) : messages.length === 0 ? (
                                <div className="h-full flex flex-col items-center justify-center text-zinc-500 text-xs gap-2">
                                    <MessageSquare className="w-10 h-10 text-zinc-400" />
                                    {__('whatsapp.livechat_no_messages')}
                                </div>
                            ) : (
                                messages.map((msg) => {
                                    const isInbound = msg.direction === 'inbound' || msg.status === 'inbound';
                                    const isInteractive = msg.message_type === 'interactive';
                                    const isImage = msg.message_type === 'image';
                                    const isDocument = msg.message_type === 'document';

                                    return (
                                        <div
                                            key={msg.id}
                                            className={`flex ${isInbound ? 'justify-start' : 'justify-end'}`}
                                        >
                                            <div
                                                className={`max-w-[75%] md:max-w-[65%] rounded-lg px-3.5 py-2 shadow-xs text-xs leading-relaxed ${
                                                    isInbound
                                                        ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-tl-none border border-zinc-200/80 dark:border-zinc-700'
                                                        : 'bg-[#d9fdd3] dark:bg-emerald-950/80 text-zinc-900 dark:text-zinc-100 rounded-tr-none border border-[#b5eba9] dark:border-emerald-800'
                                                }`}
                                            >
                                                {/* Image Message */}
                                                {isImage && msg.payload?.image?.link && (
                                                    <div className="mb-2 rounded-lg overflow-hidden max-w-xs border border-black/10">
                                                        <img src={msg.payload.image.link} alt={__('whatsapp.livechat_attachment')} className="w-full h-auto object-cover max-h-64" />
                                                    </div>
                                                )}

                                                {/* Document Message */}
                                                {isDocument && (
                                                    <div className="mb-2 p-2.5 rounded-lg bg-black/5 dark:bg-white/5 flex items-center gap-2.5 border border-black/10">
                                                        <FileCode className="w-6 h-6 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                                        <div className="min-w-0 flex-1">
                                                            <div className="font-semibold text-xs truncate">
                                                                {msg.payload?.document?.filename || __('whatsapp.livechat_document_file')}
                                                            </div>
                                                            {msg.payload?.document?.link && (
                                                                <a href={msg.payload.document.link} target="_blank" rel="noreferrer" className="text-[10px] text-emerald-600 underline">
                                                                    {__('whatsapp.livechat_download_document')}
                                                                </a>
                                                            )}
                                                        </div>
                                                    </div>
                                                )}

                                                <div className="whitespace-pre-wrap font-sans break-words text-sm text-zinc-900 dark:text-zinc-100">
                                                    {msg.message_body}
                                                </div>

                                                {/* Interactive Buttons rendering */}
                                                {isInteractive && msg.payload?.interactive?.action?.buttons && (
                                                    <div className="mt-2 pt-2 border-t border-black/10 space-y-1">
                                                        {msg.payload.interactive.action.buttons.map((btn: any, idx: number) => (
                                                            <div key={idx} className="p-1.5 text-center bg-white/80 dark:bg-zinc-800 rounded text-emerald-600 dark:text-emerald-400 font-bold text-xs border border-emerald-200 dark:border-emerald-800">
                                                                {btn.reply?.title || __('whatsapp.livechat_option')}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}

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
                            <div className="px-6 py-2 bg-rose-100 dark:bg-rose-950/60 border-t border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                                    {errorMsg}
                                </span>
                                <button onClick={() => setErrorMsg(null)} className="text-rose-600 hover:text-rose-800 cursor-pointer" aria-label={__('general.close')}>
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        )}

                        {/* Canned Quick Replies Suggestions Popover */}
                        {showQuickRepliesPopup && (
                            <div className="mx-4 mb-2 p-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-2xl space-y-1 max-h-56 overflow-y-auto z-20 animate-in fade-in slide-in-from-bottom-2">
                                <div className="text-[10px] font-bold uppercase tracking-wider text-zinc-400 px-3 py-1 flex items-center justify-between">
                                    <span className="flex items-center gap-1"><Zap className="w-3 h-3" aria-hidden="true" />{__('whatsapp.livechat_quick_replies_filter')}</span>
                                    <span>{__('whatsapp.livechat_press_enter')}</span>
                                </div>
                                {filteredQuickReplies.length === 0 ? (
                                    <div className="px-3 py-2 text-xs text-zinc-400">{__('whatsapp.livechat_no_quick_replies')}</div>
                                ) : (
                                    filteredQuickReplies.map((qr, idx) => (
                                        <button
                                            key={qr.shortcut}
                                            type="button"
                                            onClick={() => applyQuickReply(qr)}
                                            className="w-full text-left p-2.5 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-950/50 flex items-start gap-2.5 transition-colors cursor-pointer"
                                        >
                                            <span className="font-mono text-xs font-bold text-[#00a884] shrink-0 bg-emerald-100 dark:bg-emerald-950 px-2 py-0.5 rounded-md">
                                                {qr.shortcut}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{qr.title}</div>
                                                <div className="text-[11px] text-zinc-500 truncate">{qr.message}</div>
                                            </div>
                                        </button>
                                    ))
                                )}
                            </div>
                        )}

                        {/* Bottom WhatsApp Web Composer */}
                        <div className="p-3 bg-[#f0f2f5] dark:bg-zinc-900 border-t border-[#e9edef] dark:border-zinc-800 flex flex-col gap-2">
                            <div className="flex items-center justify-between text-[11px] text-zinc-500">
                                <div className="flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setMessageType(messageType === 'text' ? 'template' : 'text')}
                                        className="text-[#00a884] hover:underline flex items-center gap-1 font-semibold cursor-pointer"
                                    >
                                        <FileText className="w-3.5 h-3.5" />
                                        {messageType === 'text' ? __('whatsapp.livechat_switch_template') : __('whatsapp.livechat_switch_text')}
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setShowInteractiveModal(true)}
                                        className="text-zinc-600 dark:text-zinc-400 hover:text-[#00a884] flex items-center gap-1 font-semibold cursor-pointer"
                                    >
                                        <Radio className="w-3.5 h-3.5 text-emerald-500" />
                                        {__('whatsapp.livechat_interactive_buttons')}
                                    </button>
                                </div>
                                <span className="text-[10px] text-zinc-400">{__('whatsapp.livechat_slash_hint')}</span>
                            </div>

                            {messageType === 'template' ? (
                                <form onSubmit={handleSendMessage} className="flex items-center gap-2">
                                    <select
                                        value={selectedTemplate}
                                        onChange={(e) => setSelectedTemplate(e.target.value)}
                                        aria-label={__('whatsapp.livechat_select_template_placeholder')}
                                        className="flex-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-[#00a884]"
                                    >
                                        <option value="">{__('whatsapp.livechat_select_template_placeholder')}</option>
                                        {templates.map(t => (
                                            <option key={t.id} value={String(t.id)}>
                                                {t.name} ({t.language}) - [{t.status}]
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        type="submit"
                                        disabled={sending || !selectedTemplate}
                                        className="px-6 py-2.5 bg-[#00a884] hover:bg-[#008f70] text-white rounded-lg font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-xs cursor-pointer"
                                    >
                                        {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                                        {__('whatsapp.livechat_send_template')}
                                    </button>
                                </form>
                            ) : (
                                <form onSubmit={handleSendMessage} className="flex items-center gap-2">
                                    {/* Attachments button */}
                                    <button
                                        type="button"
                                        onClick={() => setShowMediaModal(true)}
                                        className="p-2 rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                                        title={__('whatsapp.livechat_attach')}
                                        aria-label={__('whatsapp.livechat_attach')}
                                    >
                                        <Paperclip className="w-4 h-4" />
                                    </button>

                                    {/* Quick Replies Shortcut button */}
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setFilteredQuickReplies(quickReplies);
                                            setShowQuickRepliesPopup(!showQuickRepliesPopup);
                                        }}
                                        className="p-2 rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-emerald-600 dark:text-emerald-400 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer font-bold text-xs"
                                        title={__('whatsapp.livechat_quick_canned')}
                                        aria-label={__('whatsapp.livechat_quick_canned')}
                                    >
                                        <Zap className="w-4 h-4" aria-hidden="true" />
                                    </button>

                                    <textarea
                                        value={messageText}
                                        onChange={handleTextChange}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && !e.shiftKey) {
                                                e.preventDefault();
                                                handleSendMessage();
                                            }
                                        }}
                                        placeholder={__('whatsapp.livechat_message_placeholder')}
                                        aria-label={__('whatsapp.livechat_message_placeholder')}
                                        rows={1}
                                        className="flex-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg px-4 py-2 text-xs text-zinc-900 dark:text-zinc-100 placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-[#00a884] resize-none shadow-xs"
                                    />

                                    <button
                                        type="submit"
                                        disabled={sending || !messageText.trim()}
                                        aria-label={__('general.send')}
                                        className="h-9 px-5 bg-[#00a884] hover:bg-[#008f70] text-white rounded-lg font-bold text-xs flex items-center gap-2 disabled:opacity-50 transition-all shadow-xs shrink-0 cursor-pointer"
                                    >
                                        {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                                    </button>
                                </form>
                            )}
                        </div>
                    </>
                ) : (
                    <div className="h-full flex flex-col items-center justify-center text-zinc-400 text-sm gap-2">
                        <Smartphone className="w-12 h-12 text-zinc-300 dark:text-zinc-700" />
                        {__('whatsapp.livechat_select_conversation')}
                    </div>
                )}
            </div>

            {/* 4. Right Contact CRM & Notes Drawer (Collapsible) */}
            {selectedPhone && showCrmSidebar && (
                <div className="w-80 bg-white dark:bg-zinc-900 border-l border-[#e9edef] dark:border-zinc-800 h-full flex flex-col shrink-0 overflow-y-auto p-5 space-y-6 z-10 animate-in slide-in-from-right-4">
                    <div className="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-zinc-500">{__('whatsapp.livechat_contact_crm')}</h3>
                        <button onClick={() => setShowCrmSidebar(false)} className="text-zinc-400 hover:text-zinc-600 cursor-pointer" aria-label={__('general.close')}>
                            <X className="w-4 h-4" />
                        </button>
                    </div>

                    {/* Contact Profile & Editable Name */}
                    <div className="flex flex-col items-center text-center space-y-3">
                        <div className="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 font-black text-xl flex items-center justify-center border-2 border-emerald-300">
                            {activeContact?.name?.charAt(0).toUpperCase() || 'C'}
                        </div>

                        <div className="w-full">
                            {isEditingContactName ? (
                                <div className="flex items-center gap-1.5">
                                    <input
                                        type="text"
                                        value={contactNameInput}
                                        onChange={(e) => setContactNameInput(e.target.value)}
                                        aria-label={__('whatsapp.livechat_contact_name')}
                                        className="flex-1 px-2.5 py-1 text-xs bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-center font-bold"
                                    />
                                    <button
                                        onClick={handleSaveCrmDetails}
                                        disabled={isSavingCrm}
                                        className="p-1.5 bg-[#00a884] text-white rounded-lg cursor-pointer"
                                        aria-label={__('general.save')}
                                    >
                                        <Save className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            ) : (
                                <div className="flex items-center justify-center gap-1.5">
                                    <h4 className="font-bold text-sm text-zinc-900 dark:text-zinc-100">{activeContact?.name || selectedPhone}</h4>
                                    <button onClick={() => setIsEditingContactName(true)} className="text-zinc-400 hover:text-zinc-600 cursor-pointer" aria-label={__('whatsapp.livechat_edit_name')}>
                                        <Edit2 className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            )}
                            <p className="text-xs text-zinc-500 font-mono mt-0.5">+{selectedPhone}</p>
                        </div>
                    </div>

                    {/* Tags & Labels Card */}
                    <div className="space-y-2.5">
                        <div className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-zinc-500">
                            <Tag className="w-3.5 h-3.5 text-purple-500" />
                            <span>{__('whatsapp.livechat_tags_labels')}</span>
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            {AVAILABLE_TAGS.map(tag => {
                                const isSelected = activeContactTags.includes(tag.label);
                                return (
                                    <button
                                        key={tag.label}
                                        onClick={() => handleToggleTag(tag.label)}
                                        className={`px-2.5 py-1 rounded-full text-xs font-semibold border transition-all cursor-pointer ${
                                            isSelected
                                                ? tag.color + ' ring-2 ring-emerald-500'
                                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border-zinc-200 dark:border-zinc-700 opacity-60 hover:opacity-100'
                                        }`}
                                    >
                                        {isSelected ? <Check className="inline w-3 h-3 me-0.5" aria-hidden="true" /> : '+ '}{__(tag.labelKey)}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Private Internal Team Notes Card */}
                    <div className="space-y-2.5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-zinc-500">
                                <StickyNote className="w-3.5 h-3.5 text-amber-500" />
                                <span>{__('whatsapp.livechat_internal_notes')}</span>
                            </div>
                            <span className="text-[10px] text-amber-600 font-bold bg-amber-50 dark:bg-amber-950 px-1.5 py-0.5 rounded">{__('whatsapp.livechat_staff_only')}</span>
                        </div>
                        <textarea
                            rows={3}
                            value={contactNotesInput}
                            onChange={(e) => setContactNotesInput(e.target.value)}
                            placeholder={__('whatsapp.livechat_notes_placeholder')}
                            aria-label={__('whatsapp.livechat_internal_notes')}
                            className="w-full p-3 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-xl text-xs text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-amber-500 resize-none leading-relaxed"
                        />
                        <button
                            onClick={handleSaveCrmDetails}
                            disabled={isSavingCrm}
                            className="w-full py-2 bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs font-bold rounded-xl transition cursor-pointer"
                        >
                            {isSavingCrm ? __('general.saving') : __('whatsapp.livechat_save_notes')}
                        </button>
                    </div>

                    {/* CTWA Meta Ad Referral Details */}
                    {activeReferral && (
                        <div className="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 rounded-2xl space-y-2 text-xs">
                            <div className="font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                                <Sparkles className="w-4 h-4 text-amber-600" />
                                {__('whatsapp.livechat_ad_attribution')}
                            </div>
                            <div className="text-[11px] text-amber-800 dark:text-amber-300 space-y-1">
                                <div><strong>{__('whatsapp.livechat_campaign')}</strong> {activeReferral.headline || __('whatsapp.livechat_direct_ctwa')}</div>
                                {activeReferral.source_id && <div><strong>{__('whatsapp.livechat_ad_id')}</strong> {activeReferral.source_id}</div>}
                                {activeReferral.ctwa_clid && <div className="truncate font-mono"><strong>CLID:</strong> {activeReferral.ctwa_clid}</div>}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Media Attachment Modal */}
            {showMediaModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
                        <div className="flex items-center justify-between">
                            <h3 className="font-bold text-base">{__('whatsapp.livechat_send_media')}</h3>
                            <button onClick={() => setShowMediaModal(false)} className="text-zinc-400 hover:text-zinc-600 cursor-pointer" aria-label={__('general.close')}>
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Media Type Switcher */}
                        <div className="flex items-center gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                            <button
                                onClick={() => { setMediaType('image'); setSelectedMediaFile(null); }}
                                className={`flex-1 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer ${mediaType === 'image' ? 'bg-white dark:bg-zinc-700 text-[#00a884] shadow-xs' : 'text-zinc-500'}`}
                            >
                                <ImageIcon className="w-4 h-4" /> {__('whatsapp.livechat_image')}
                            </button>
                            <button
                                onClick={() => { setMediaType('document'); setSelectedMediaFile(null); }}
                                className={`flex-1 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer ${mediaType === 'document' ? 'bg-white dark:bg-zinc-700 text-[#00a884] shadow-xs' : 'text-zinc-500'}`}
                            >
                                <FileCode className="w-4 h-4" /> {__('whatsapp.livechat_document_pdf')}
                            </button>
                        </div>

                        {/* File Upload Box */}
                        <div
                            onClick={() => mediaInputRef.current?.click()}
                            className="border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-2xl p-6 text-center hover:border-[#00a884] transition cursor-pointer space-y-2"
                        >
                            <input
                                ref={mediaInputRef}
                                type="file"
                                aria-label={__('whatsapp.livechat_attach')}
                                accept={mediaType === 'image' ? 'image/jpeg,image/png,image/webp' : 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,text/plain'}
                                className="hidden"
                                onChange={(e) => {
                                    const file = e.target.files?.[0];
                                    if (file) {
                                        setSelectedMediaFile(file);
                                        if (mediaType === 'image') {
                                            setMediaPreviewUrl(URL.createObjectURL(file));
                                        }
                                    }
                                }}
                            />
                            {selectedMediaFile ? (
                                <div className="space-y-2">
                                    {mediaPreviewUrl && (
                                        <img src={mediaPreviewUrl} alt={__('general.preview')} className="w-24 h-24 object-cover rounded-xl mx-auto border" />
                                    )}
                                    <div className="font-bold text-xs text-zinc-900 dark:text-zinc-100">{selectedMediaFile.name}</div>
                                    <div className="text-[10px] text-zinc-400 font-mono">{(selectedMediaFile.size / 1024).toFixed(1)} KB</div>
                                </div>
                            ) : (
                                <>
                                    <Paperclip className="w-8 h-8 mx-auto text-zinc-400" />
                                    <p className="text-xs font-semibold text-zinc-600 dark:text-zinc-300">{mediaType === 'image' ? __('whatsapp.livechat_select_photo') : __('whatsapp.livechat_select_file')}</p>
                                    <p className="text-[10px] text-zinc-400">{__('whatsapp.livechat_max_size')}</p>
                                </>
                            )}
                        </div>

                        {/* Caption input */}
                        <div className="space-y-1.5">
                            <label className="text-xs font-semibold text-zinc-500">{__('whatsapp.livechat_caption')}</label>
                            <input
                                type="text"
                                value={mediaCaption}
                                onChange={(e) => setMediaCaption(e.target.value)}
                                placeholder={__('whatsapp.livechat_caption_placeholder')}
                                aria-label={__('whatsapp.livechat_caption')}
                                className="w-full px-3.5 py-2 text-xs bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-1 focus:ring-[#00a884] focus:outline-none"
                            />
                        </div>

                        <button
                            onClick={handleSendMedia}
                            disabled={sending || !selectedMediaFile}
                            className="w-full py-3 bg-[#00a884] hover:bg-[#008f70] text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 disabled:opacity-50 transition cursor-pointer"
                        >
                            {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                            {mediaType === 'image' ? __('whatsapp.livechat_send_image') : __('whatsapp.livechat_send_document')}
                        </button>
                    </div>
                </div>
            )}

            {/* Interactive Buttons Modal */}
            {showInteractiveModal && (
                <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Radio className="w-5 h-5 text-emerald-500" />
                                <h3 className="font-bold text-base">{__('whatsapp.livechat_send_interactive_title')}</h3>
                            </div>
                            <button onClick={() => setShowInteractiveModal(false)} className="text-zinc-400 hover:text-zinc-600 cursor-pointer" aria-label={__('general.close')}>
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <p className="text-xs text-zinc-500">
                            {__('whatsapp.livechat_interactive_desc')}
                        </p>

                        <div className="space-y-1.5">
                            <label className="text-xs font-semibold text-zinc-500">{__('whatsapp.livechat_message_body')}</label>
                            <textarea
                                rows={2}
                                value={interactiveBody}
                                onChange={(e) => setInteractiveBody(e.target.value)}
                                placeholder={__('whatsapp.livechat_interactive_placeholder')}
                                aria-label={__('whatsapp.livechat_message_body')}
                                className="w-full p-3 text-xs bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-1 focus:ring-[#00a884] focus:outline-none resize-none"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-semibold text-zinc-500">{__('whatsapp.livechat_button_labels')}</label>
                            {interactiveButtons.map((btn, idx) => (
                                <div key={idx} className="flex items-center gap-2">
                                    <span className="text-xs font-mono text-zinc-400 w-5">#{idx + 1}</span>
                                    <input
                                        type="text"
                                        maxLength={20}
                                        value={btn}
                                        onChange={(e) => {
                                            const newBtns = [...interactiveButtons];
                                            newBtns[idx] = e.target.value;
                                            setInteractiveButtons(newBtns);
                                        }}
                                        placeholder={__('whatsapp.livechat_button_placeholder', { number: idx + 1 })}
                                        aria-label={__('whatsapp.livechat_button_placeholder', { number: idx + 1 })}
                                        className="flex-1 px-3 py-1.5 text-xs bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                                    />
                                    {interactiveButtons.length > 1 && (
                                        <button
                                            onClick={() => setInteractiveButtons(interactiveButtons.filter((_, i) => i !== idx))}
                                            className="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg cursor-pointer"
                                            aria-label={__('general.delete')}
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>
                            ))}

                            {interactiveButtons.length < 3 && (
                                <button
                                    onClick={() => setInteractiveButtons([...interactiveButtons, __('whatsapp.livechat_option_n', { number: interactiveButtons.length + 1 })])}
                                    className="text-xs text-[#00a884] hover:underline font-semibold flex items-center gap-1 cursor-pointer pt-1"
                                >
                                    <Plus className="w-3.5 h-3.5" /> {__('whatsapp.livechat_add_button')}
                                </button>
                            )}
                        </div>

                        <button
                            onClick={handleSendInteractiveButtons}
                            disabled={sending || !interactiveBody.trim() || interactiveButtons.filter(b => b.trim()).length === 0}
                            className="w-full py-3 bg-[#00a884] hover:bg-[#008f70] text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 disabled:opacity-50 transition cursor-pointer"
                        >
                            {sending ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                            {__('whatsapp.livechat_send_interactive')}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
