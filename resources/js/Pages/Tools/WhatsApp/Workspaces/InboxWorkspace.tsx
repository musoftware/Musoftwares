import React, { useState, useEffect, useRef } from 'react';
import {
    MessageCircle, Send, Search, ArrowLeft, Phone, User,
    Image, Paperclip, Smile, ChevronRight, Clock, Check, CheckCheck,
    RefreshCw, Inbox as InboxIcon, Trash2, Bot, Loader2
} from 'lucide-react';
import { Card } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

function formatTime(dateStr: string) {
    if (!dateStr) return '';
    try {
        const iso = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T') + 'Z';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        const now = new Date();
        const isToday = d.toDateString() === now.toDateString();
        if (isToday) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    } catch { return ''; }
}

export default function InboxWorkspace({ callRPC, daemonConnected, sessions, selectedAccount, onNewMessageRef, onUnreadReset, t, locale }: any) {
    const isRtl = locale === 'ar';
    const [threads, setThreads] = useState<any[]>([]);
    const [selectedThread, setSelectedThread] = useState<any>(null);
    const [messages, setMessages] = useState<any[]>([]);
    const [replyText, setReplyText] = useState('');
    const [sending, setSending] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    
    // AI Copilot State Hooks
    const [showCopilot, setShowCopilot] = useState(false);
    const [copilotDraft, setCopilotDraft] = useState('');
    const [draftLoading, setDraftLoading] = useState(false);
    const [copilotClassification, setCopilotClassification] = useState<any>(null);

    const fetchCopilotDraft = async () => {
        if (!selectedThread) return;
        setDraftLoading(true);
        try {
            const res: any = await callRPC('generateAICopilotDraft', {
                sessionId: selectedThread.session_id,
                phone: selectedThread.contact_number
            });
            setCopilotDraft(res.draft || '');
            setCopilotClassification(res.classification || null);
        } catch (err) {
            console.error('Failed to generate AI Copilot draft:', err);
        }
        setDraftLoading(false);
    };

    const handleApplyCopilotDraft = () => {
        setReplyText(copilotDraft);
    };

    useEffect(() => {
        if (showCopilot && selectedThread?.id) {
            fetchCopilotDraft();
        }
    }, [selectedThread?.id, showCopilot]);
    const selectedThreadRef = useRef<any>(null);
    const callRPCRef = useRef(callRPC);
    const fetchThreadsRef = useRef<() => void>(() => {});
    const fetchMessagesRef = useRef<(id: string) => void>(() => {});

    // Keep refs in sync every render
    useEffect(() => { selectedThreadRef.current = selectedThread; }, [selectedThread]);
    useEffect(() => { callRPCRef.current = callRPC; }, [callRPC]);

    // Fetch threads
    const fetchThreads = async () => {
        try {
            const res: any = await callRPCRef.current('getThreads', { sessionId: selectedAccount || null });
            setThreads(res.threads || []);
        } catch (err) { /* silent */ }
    };
    fetchThreadsRef.current = fetchThreads;

    // Fetch messages for selected thread
    const fetchMessages = async (threadId: string) => {
        if (!threadId) return;
        try {
            const res: any = await callRPCRef.current('getThreadMessages', { threadId });
            setMessages(res.messages || []);
        } catch (err) { /* silent */ }
    };
    fetchMessagesRef.current = fetchMessages;

    // ── Dedicated WebSocket for REAL-TIME inbox ──────────────────────────────
    useEffect(() => {
        const host = typeof window !== 'undefined'
            ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1')
            : '127.0.0.1';
        let ws: WebSocket | null = null;
        let reconnectTimer: any = null;
        let reconnectAttempt = 0;

        const connect = () => {
            try {
                ws = new WebSocket(`ws://${host}:18401/ws`);
                ws.onopen = () => {
                    reconnectAttempt = 0; // Reset backoff on successful connect
                    console.log('[Inbox WS] ✅ Connected — listening for live messages');
                };
                ws.onmessage = (ev) => {
                    try {
                        const msg = JSON.parse(ev.data);
                        if (msg.event === 'whatsapp.inbox.new_message') {
                            const renderStart = performance.now();
                            const newMsgData = msg.data;
                            if (!newMsgData) return;

                            // ── Latency diagnostic ──
                            const msgTs = newMsgData.timestamp ? new Date(newMsgData.timestamp).getTime() : 0;
                            const pipelineDelay = msgTs ? Date.now() - msgTs : -1;
                            console.log(`[Inbox WS] 📩 ${newMsgData.direction === 'out' ? 'OUT' : 'IN'} | from=${newMsgData.phone} | pipeline=${pipelineDelay}ms | "${newMsgData.content?.substring(0, 40)}"`);
                            
                            // Explicit debug log for raw message payload
                            if (newMsgData.rawMessage) {
                                console.log('%c[RAW MESSAGE DATA - INBOX UI]', 'background: #0d9488; color: white; font-weight: bold; padding: 4px; border-radius: 4px;', newMsgData.rawMessage);
                            }
                            
                            // 1. If this message belongs to the currently active thread, append it instantly to the messages list
                            const current = selectedThreadRef.current;
                            if (current && newMsgData.threadId === current.id) {
                                setMessages(prev => {
                                    // 1. Filter out matching optimistic messages
                                    const filtered = prev.filter(m => !(m.id.toString().startsWith('opt_') && m.content === newMsgData.content && m.direction === newMsgData.direction));
                                    
                                    // 2. Prevent duplicates by message_id
                                    if (filtered.some(m => m.message_id === newMsgData.msgId && newMsgData.msgId)) return filtered;
                                    
                                    const formattedMsg = {
                                        id: newMsgData.id || Math.random(),
                                        thread_id: newMsgData.threadId,
                                        session_id: newMsgData.sessionId,
                                        contact_number: newMsgData.phone,
                                        direction: newMsgData.direction || 'in',
                                        message_type: newMsgData.messageType || 'text',
                                        content: newMsgData.content,
                                        media_url: newMsgData.mediaUrl || null,
                                        message_id: newMsgData.msgId,
                                        timestamp: newMsgData.timestamp || new Date().toISOString()
                                    };
                                    return [...filtered, formattedMsg];
                                });
                            }

                            // 2. Update the threads list state in real-time
                            setThreads(prev => {
                                const existingIdx = prev.findIndex(t => t.id === newMsgData.threadId);
                                const updatedThreads = [...prev];
                                let threadObj: any;
                                
                                // Format preview text
                                const previewText = newMsgData.direction === 'out'
                                    ? `You: ${newMsgData.content?.substring(0, 80) || ''}`
                                    : (newMsgData.content?.substring(0, 100) || (newMsgData.messageType !== 'text' ? `📎 ${newMsgData.messageType}` : ''));
                                
                                if (existingIdx > -1) {
                                    const existing = prev[existingIdx];
                                    threadObj = {
                                        ...existing,
                                        last_message_preview: previewText,
                                        last_message: newMsgData.content,
                                        last_message_at: newMsgData.timestamp || new Date().toISOString(),
                                        unread_count: (selectedThreadRef.current?.id === newMsgData.threadId || newMsgData.direction === 'out')
                                            ? existing.unread_count
                                            : (existing.unread_count || 0) + 1
                                    };
                                    updatedThreads.splice(existingIdx, 1);
                                } else {
                                    // Thread doesn't exist yet, construct dynamic placeholder
                                    threadObj = {
                                        id: newMsgData.threadId,
                                        session_id: newMsgData.sessionId,
                                        contact_number: newMsgData.phone,
                                        contact_name: null,
                                        unread_count: (selectedThreadRef.current?.id === newMsgData.threadId || newMsgData.direction === 'out') ? 0 : 1,
                                        last_message_preview: previewText,
                                        last_message: newMsgData.content,
                                        last_message_at: newMsgData.timestamp || new Date().toISOString(),
                                    };
                                }
                                return [threadObj, ...updatedThreads];
                            });

                            console.log(`[Inbox WS] ✅ UI RENDERED in ${(performance.now() - renderStart).toFixed(1)}ms`);
                        } else if (msg.event === 'whatsapp.inbox.classification') {
                            const classData = msg.data;
                            if (classData) {
                                const { threadId, classification } = classData;
                                setSelectedThread(prev => {
                                    if (prev && prev.id === threadId) {
                                        return { ...prev, intent: classification.intent, sentiment: classification.sentiment };
                                    }
                                    return prev;
                                });
                                setThreads(prev => prev.map(t => {
                                    if (t.id === threadId) {
                                        return { ...t, intent: classification.intent, sentiment: classification.sentiment };
                                    }
                                    return t;
                                }));
                            }
                        }
                    } catch (e) {
                        console.error('[Inbox WS] Error processing message event:', e);
                    }
                };
                ws.onclose = () => {
                    // Exponential backoff: 1s, 2s, 4s, 8s... max 15s
                    const backoff = Math.min(1000 * Math.pow(2, reconnectAttempt), 15000);
                    reconnectAttempt++;
                    console.log(`[Inbox WS] 🔄 Reconnecting in ${backoff}ms (attempt #${reconnectAttempt})`);
                    reconnectTimer = setTimeout(connect, backoff);
                };
                ws.onerror = () => ws?.close();
            } catch {}
        };

        connect();
        return () => {
            clearTimeout(reconnectTimer);
            ws?.close();
        };
    }, []);

    // Initial threads load
    useEffect(() => {
        if (daemonConnected) {
            fetchThreads();
        }
    }, [daemonConnected, selectedAccount]);

    // Fetch initial messages for active thread when it is selected
    useEffect(() => {
        if (selectedThread?.id && daemonConnected) {
            fetchMessages(selectedThread.id);
        }
    }, [selectedThread?.id, daemonConnected]);

    // Scroll to bottom on new messages
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Mark as read when opening thread
    const openThread = async (thread: any) => {
        setSelectedThread(thread);
        setMessages([]);
        if (thread.unread_count > 0) {
            try {
                await callRPC('markThreadRead', { threadId: thread.id });
                // Update unread count locally for instant responsiveness
                setThreads(prev => prev.map(t => t.id === thread.id ? { ...t, unread_count: 0 } : t));
                onUnreadReset?.();
            } catch {}
        }
        fetchMessages(thread.id);
        setTimeout(() => inputRef.current?.focus(), 100);
    };

    // Send reply
    const handleSendReply = async () => {
        if (!replyText.trim() || !selectedThread || sending) return;
        setSending(true);
        const text = replyText.trim();
        setReplyText(''); // Clear input instantly for immediate responsiveness

        const optId = `opt_${Date.now()}`;
        const tempMsg = {
            id: optId,
            thread_id: selectedThread.id,
            session_id: selectedThread.session_id,
            contact_number: selectedThread.contact_number,
            direction: 'out',
            message_type: 'text',
            content: text,
            media_url: null,
            message_id: null,
            timestamp: new Date().toISOString()
        };

        // 1. Instantly append the optimistic message to state
        setMessages(prev => [...prev, tempMsg]);

        // 2. Instantly bubble/reorder active thread to the top of the sidebar
        setThreads(prev => {
            const existingIdx = prev.findIndex(t => t.id === selectedThread.id);
            const updatedThreads = [...prev];
            let threadObj: any;
            
            const previewText = `You: ${text.substring(0, 80)}`;
            
            if (existingIdx > -1) {
                const existing = prev[existingIdx];
                threadObj = {
                    ...existing,
                    last_message_preview: previewText,
                    last_message: text,
                    last_message_at: new Date().toISOString(),
                };
                updatedThreads.splice(existingIdx, 1);
            } else {
                threadObj = {
                    ...selectedThread,
                    last_message_preview: previewText,
                    last_message: text,
                    last_message_at: new Date().toISOString(),
                };
            }
            return [threadObj, ...updatedThreads];
        });

        try {
            await callRPC('sendReply', {
                sessionId: selectedThread.session_id,
                phone: selectedThread.contact_number,
                content: text,
                messageType: 'text'
            });
            // The real-time WS payload will arrive and reconcile/replace the optimistic message, so we do not need to fetch or pull!
        } catch (err: any) {
            // Clean up the optimistic message on failure
            setMessages(prev => prev.filter(m => m.id !== optId));
            alert(`${t.inbox.replyFailed}${err.message}`);
            setReplyText(text); // Restore text on failure
        }
        setSending(false);
    };

    const filteredThreads = threads.filter(t => {
        if (!searchQuery) return true;
        const q = searchQuery.toLowerCase();
        return (t.contact_number || '').includes(q) ||
               (t.contact_name || '').toLowerCase().includes(q) ||
               (t.last_message_preview || '').toLowerCase().includes(q);
    });

    const totalUnread = threads.reduce((sum, t) => sum + (t.unread_count || 0), 0);

    return (
        <div className="h-[calc(100vh-120px)] flex rounded-2xl overflow-hidden border shadow-lg bg-background">
            {/* Thread List - Left Panel */}
            <div className={`${selectedThread ? 'hidden md:flex' : 'flex'} flex-col w-full md:w-[360px] border-e bg-background`}>
                {/* Header */}
                <div className="p-4 border-b bg-gradient-to-r from-teal-600 to-teal-700">
                    <div className="flex items-center justify-between mb-3">
                        <div className="flex items-center gap-2">
                            <MessageCircle className="w-5 h-5 text-white" />
                            <h2 className="text-white font-bold text-base">{t.inbox.title}</h2>
                            {totalUnread > 0 && (
                                <Badge className="bg-red-500 hover:bg-red-600 text-white text-[10px] px-1.5 min-w-5 h-5 flex items-center justify-center rounded-full animate-pulse">
                                    {totalUnread}
                                </Badge>
                            )}
                        </div>
                        <div className="flex items-center gap-1">
                            <Button variant="ghost" size="icon" onClick={async () => {
                                if (!confirm('Are you sure you want to clear all inbox conversations?')) return;
                                try {
                                    await callRPC('clearInbox', {});
                                    setThreads([]);
                                    setMessages([]);
                                    setSelectedThread(null);
                                } catch (err: any) {
                                    alert('Failed to clear inbox: ' + err.message);
                                }
                            }} className="text-white/70 hover:text-red-300 hover:bg-white/10 h-8 w-8" title={__('general.clear_inbox')}>
                                <Trash2 className="w-4 h-4" />
                            </Button>
                            <Button variant="ghost" size="icon" onClick={fetchThreads} className="text-white/70 hover:text-white hover:bg-white/10 h-8 w-8">
                                <RefreshCw className="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                    <div className="relative">
                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-teal-200" />
                        <Input
                            value={searchQuery}
                            onChange={e => setSearchQuery(e.target.value)}
                            placeholder={t.inbox.searchPlaceholder}
                            className="ps-9 bg-white/15 border-white/20 text-white placeholder:text-teal-200/70 h-9 rounded-xl text-sm focus:bg-white/25 text-start"
                        />
                    </div>
                </div>

                {/* Thread List */}
                <div className="flex-1 overflow-y-auto">
                    {filteredThreads.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-full text-center p-8">
                            <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
                                <InboxIcon className="w-7 h-7 text-muted-foreground" />
                            </div>
                            <p className="text-sm font-semibold text-muted-foreground">{t.inbox.emptyStateTitle}</p>
                            <p className="text-xs text-muted-foreground/70 mt-1">{t.inbox.emptyStateSub}</p>
                        </div>
                    ) : (
                        filteredThreads.map(thread => (
                            <button
                                key={thread.id}
                                onClick={() => openThread(thread)}
                                className={`w-full flex items-center gap-3 p-4 border-b transition-all text-start hover:bg-muted/50 ${
                                    selectedThread?.id === thread.id ? 'bg-teal-50/80 dark:bg-teal-950/20' : ''
                                }`}
                            >
                                {/* Avatar */}
                                <div className="w-11 h-11 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shrink-0 shadow-sm">
                                    <span className="text-white font-bold text-sm">
                                        {(thread.contact_name || thread.contact_number || '?')[0].toUpperCase()}
                                    </span>
                                </div>
                                {/* Content */}
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between">
                                        <p className="font-bold text-sm truncate flex items-center gap-1.5">
                                            <span>{thread.contact_name || `+${thread.contact_number}`}</span>
                                            {thread.intent && thread.intent !== 'neutral' && (
                                                <span className={`text-[9px] font-bold px-1.5 py-0.5 rounded backdrop-blur-md shadow-sm shrink-0 border ${
                                                    thread.intent === 'interested' 
                                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' 
                                                        : thread.intent === 'price_inquiry' 
                                                        ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' 
                                                        : thread.intent === 'opt_out' 
                                                        ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' 
                                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
                                                }`}>
                                                    {thread.intent === 'interested' ? (isRtl ? 'مهتم' : 'Interested') :
                                                     thread.intent === 'price_inquiry' ? (isRtl ? 'سعر' : 'Price') :
                                                     thread.intent === 'opt_out' ? (isRtl ? 'إيقاف' : 'Opt-out') :
                                                     (isRtl ? 'غير مهتم' : 'Uninterested')}
                                                </span>
                                            )}
                                        </p>
                                        <span className="text-[10px] text-muted-foreground shrink-0 ms-2">
                                            {formatTime(thread.last_message_at)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between mt-0.5">
                                        <p className="text-xs text-muted-foreground truncate pe-2 text-start">
                                            {thread.last_message_preview || thread.last_message || t.inbox.noMessagesYet}
                                        </p>
                                        {(thread.unread_count || 0) > 0 && (
                                            <Badge className="bg-teal-500 hover:bg-teal-600 text-white text-[10px] px-1.5 min-w-5 h-5 flex items-center justify-center rounded-full shrink-0">
                                                {thread.unread_count}
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            </button>
                        ))
                    )}
                </div>
            </div>

            {/* Chat View - Right Panel */}
            <div className={`${selectedThread ? 'flex' : 'hidden md:flex'} flex-col flex-1 bg-[#efeae2] dark:bg-[#0b141a]`}>
                {selectedThread ? (
                    <>
                        {/* Chat Header */}
                        <div className="flex items-center gap-3 px-4 py-3 bg-background border-b shadow-sm">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="md:hidden h-8 w-8"
                                onClick={() => setSelectedThread(null)}
                            >
                                <ArrowLeft className="w-4 h-4 rtl:rotate-180" />
                            </Button>
                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-sm">
                                <span className="text-white font-bold text-sm">
                                    {(selectedThread.contact_name || selectedThread.contact_number || '?')[0].toUpperCase()}
                                </span>
                            </div>
                            <div className="flex-1 min-w-0 text-start flex items-center justify-between gap-3">
                                <div>
                                    <p className="font-bold text-sm">{selectedThread.contact_name || `+${selectedThread.contact_number}`}</p>
                                    <p className="text-[11px] text-muted-foreground font-mono">+{selectedThread.contact_number}</p>
                                </div>
                                <div className="flex flex-wrap gap-1.5 shrink-0">
                                    {selectedThread.intent && selectedThread.intent !== 'neutral' && (
                                        <Badge className={`text-[10px] font-bold shadow-sm backdrop-blur-md border ${
                                            selectedThread.intent === 'interested' 
                                                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/25 hover:bg-emerald-500/15' 
                                                : selectedThread.intent === 'price_inquiry' 
                                                ? 'bg-blue-500/10 text-blue-600 border-blue-500/25 hover:bg-blue-500/15' 
                                                : selectedThread.intent === 'opt_out' 
                                                ? 'bg-rose-500/10 text-rose-600 border-rose-500/25 hover:bg-rose-500/15' 
                                                : 'bg-amber-500/10 text-amber-600 border-amber-500/25 hover:bg-amber-500/15'
                                        }`}>
                                            ✨ {selectedThread.intent === 'interested' ? (isRtl ? 'عميل مهتم' : 'Interested Lead') :
                                                 selectedThread.intent === 'price_inquiry' ? (isRtl ? 'استفسار عن السعر' : 'Price Inquiry') :
                                                 selectedThread.intent === 'opt_out' ? (isRtl ? 'طلب إيقاف التواصل' : 'Opt-out Requested') :
                                                 (isRtl ? 'غير مهتم' : 'Uninterested')}
                                        </Badge>
                                    )}
                                    {selectedThread.sentiment && (
                                        <Badge className={`text-[10px] font-bold shadow-sm backdrop-blur-md border ${
                                            selectedThread.sentiment === 'positive' 
                                                ? 'bg-teal-500/10 text-teal-600 border-teal-500/25 hover:bg-teal-500/15' 
                                                : selectedThread.sentiment === 'negative' 
                                                ? 'bg-red-500/10 text-red-600 border-red-500/25 hover:bg-red-500/15' 
                                                : 'bg-slate-500/10 text-slate-600 border-slate-500/25 hover:bg-slate-500/15'
                                        }`}>
                                            {selectedThread.sentiment === 'positive' ? '😊 ' + (isRtl ? 'إيجابي' : 'Positive') :
                                             selectedThread.sentiment === 'negative' ? '😠 ' + (isRtl ? 'سلبي' : 'Negative') :
                                             '😐 ' + (isRtl ? 'محايد' : 'Neutral')}
                                        </Badge>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center gap-1.5 shrink-0">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setShowCopilot(!showCopilot)}
                                    className={`h-8 w-8 rounded-full shrink-0 ${showCopilot ? 'bg-teal-500/10 text-teal-600 border border-teal-500/20' : 'text-muted-foreground'}`}
                                    title={__('general.ai_copilot')}
                                >
                                    <Bot className="w-4 h-4" />
                                </Button>
                                <Badge variant="outline" className="text-[10px] gap-1 text-teal-600 shrink-0">
                                    <Phone className="w-3 h-3" />
                                    {selectedThread.session_id}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex-1 flex flex-row overflow-hidden relative">
                            {/* Main Chat Flow */}
                            <div className="flex-1 flex flex-col h-full overflow-hidden relative">
                                {/* Messages Area */}
                                <div
                                    className="flex-1 overflow-y-auto p-4 space-y-2"
                                    style={{ backgroundImage: 'radial-gradient(circle, rgba(0, 0, 0, 0.03) 1px, transparent 1px)', backgroundSize: '20px 20px' }}
                                >
                                    {messages.length === 0 && (
                                        <div className="flex items-center justify-center h-full">
                                            <p className="text-xs text-muted-foreground bg-white/80 dark:bg-black/30 px-4 py-2 rounded-lg">
                                                {t.inbox.noMessages}
                                            </p>
                                        </div>
                                    )}
                                    {messages.map((msg, idx) => {
                                        const justifyClass = msg.direction === 'out' 
                                            ? (isRtl ? 'justify-start' : 'justify-end') 
                                            : (isRtl ? 'justify-end' : 'justify-start');
                                        return (
                                            <div key={msg.id || idx} className={`flex ${justifyClass}`}>
                                                <div className={`relative max-w-[75%] rounded-2xl px-3.5 py-2 shadow-sm text-[13px] ${
                                                    msg.direction === 'out'
                                                        ? 'bg-[#d9fdd3] text-[#111b21] dark:bg-[#005c4b] dark:text-[#e9edef]'
                                                        : 'bg-white text-[#111b21] dark:bg-[#202c33] dark:text-[#e9edef]'
                                                }`}>
                                                    {/* Media indicator */}
                                                    {msg.message_type !== 'text' && (
                                                        <div className="flex items-center gap-1.5 text-[11px] text-muted-foreground mb-1 bg-black/5 dark:bg-white/5 rounded-lg px-2 py-1.5">
                                                            {msg.message_type === 'image' && <Image className="w-3.5 h-3.5" />}
                                                            {msg.message_type === 'document' && <Paperclip className="w-3.5 h-3.5" />}
                                                            {msg.message_type === 'audio' && <span>🎵</span>}
                                                            {msg.message_type === 'video' && <span>🎬</span>}
                                                            <span className="capitalize font-semibold">{msg.message_type}</span>
                                                        </div>
                                                    )}
                                                    {/* Message content */}
                                                    {msg.content && (
                                                        <p className="whitespace-pre-wrap break-words leading-relaxed text-start">{msg.content}</p>
                                                    )}
                                                    {/* Timestamp */}
                                                    <div className="flex items-center justify-end gap-1 mt-1">
                                                        <span className="text-[10px] text-muted-foreground/70">{formatTime(msg.timestamp)}</span>
                                                        {msg.direction === 'out' && (
                                                            msg.id.toString().startsWith('opt_') ? (
                                                                <Clock className="w-3.5 h-3.5 text-muted-foreground/50 animate-pulse" />
                                                            ) : (
                                                                <CheckCheck className="w-3.5 h-3.5 text-teal-500" />
                                                            )
                                                        )}
                                                    </div>
                                                    {/* Bubble tail */}
                                                    {idx === 0 || messages[idx-1]?.direction !== msg.direction ? (
                                                        <div className={`absolute top-0 w-3 h-3 ${
                                                            msg.direction === 'out'
                                                                ? 'right-[-5px] bg-[#d9fdd3] dark:bg-[#005c4b]'
                                                                : 'left-[-5px] bg-white dark:bg-[#202c33]'
                                                        } rotate-45 transform origin-top-${msg.direction === 'out' ? 'left' : 'right'}`}
                                                        style={{ clipPath: msg.direction === 'out' ? 'polygon(100% 0, 0 0, 100% 100%)' : 'polygon(0 0, 100% 0, 0 100%)' }} />
                                                    ) : null}
                                                </div>
                                            </div>
                                        );
                                    })}
                                    <div ref={messagesEndRef} />
                                </div>

                                {/* Reply Input */}
                                <div className="p-3 bg-background border-t">
                                    <div className="flex items-center gap-2">
                                        <div className="flex-1 relative">
                                            <Input
                                                ref={inputRef}
                                                value={replyText}
                                                onChange={e => setReplyText(e.target.value)}
                                                onKeyDown={e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSendReply(); } }}
                                                placeholder={t.inbox.typePlaceholder}
                                                className="px-4 h-11 rounded-2xl bg-muted/50 border-0 text-sm focus:bg-muted text-start"
                                                disabled={sending}
                                            />
                                        </div>
                                        <Button
                                            onClick={handleSendReply}
                                            disabled={!replyText.trim() || sending}
                                            className="h-11 w-11 rounded-full bg-teal-600 hover:bg-teal-700 text-white shrink-0 shadow-md"
                                            size="icon"
                                        >
                                            {sending ? (
                                                <RefreshCw className="w-4 h-4 animate-spin" />
                                            ) : (
                                                <Send className="w-4 h-4 rtl:rotate-180" />
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* AI Copilot Drawer Sidebar */}
                            {showCopilot && (
                                <div className="w-[280px] shrink-0 border-s bg-background flex flex-col h-full overflow-hidden animate-in slide-in-from-right duration-200">
                                    <div className="p-3 border-b bg-gradient-to-r from-teal-600/5 to-teal-700/5 flex items-center justify-between">
                                        <div className="flex items-center gap-1.5">
                                            <Bot className="w-4 h-4 text-teal-600" />
                                            <span className="font-bold text-xs text-foreground">{isRtl ? 'مساعد مبيعات الذكاء الاصطناعي' : 'AI Sales Copilot'}</span>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={fetchCopilotDraft}
                                            disabled={draftLoading}
                                            className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                            title={__('general.regenerate_draft')}
                                        >
                                            <RefreshCw className={`w-3.5 h-3.5 ${draftLoading ? 'animate-spin' : ''}`} />
                                        </Button>
                                    </div>
                                    
                                    <div className="flex-1 overflow-y-auto p-4 space-y-4">
                                        {/* Dynamic Lead Scoring & Sentiment summary */}
                                        <div className="p-3 rounded-xl border bg-muted/40 text-start space-y-2">
                                            <div className="flex justify-between items-center">
                                                <span className="text-[10px] font-bold text-muted-foreground uppercase">{isRtl ? 'تحليل العميل الحالي' : 'Live Lead Analysis'}</span>
                                                <Badge className="text-[9px] font-mono font-bold bg-teal-500/10 text-teal-600 border border-teal-500/20 px-1 py-0 h-4">{__('general.active_context')}</Badge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 pt-1 font-sans">
                                                <div className="p-2 rounded bg-background border text-center">
                                                    <p className="text-[9px] text-muted-foreground font-medium">{isRtl ? 'النية المكتشفة' : 'Detected Intent'}</p>
                                                    <p className="text-[10px] font-bold text-foreground mt-0.5 capitalize">
                                                        {copilotClassification?.intent === 'interested' ? (isRtl ? '😍 مهتم' : 'Interested') :
                                                         copilotClassification?.intent === 'price_inquiry' ? (isRtl ? '💰 سعر' : 'Price Inquiry') :
                                                         copilotClassification?.intent === 'opt_out' ? (isRtl ? '🛑 إيقاف' : 'Opt-out') :
                                                         copilotClassification?.intent === 'uninterested' ? (isRtl ? '😒 غير مهتم' : 'Uninterested') :
                                                         (isRtl ? '💬 عام' : 'General')}
                                                    </p>
                                                </div>
                                                <div className="p-2 rounded bg-background border text-center">
                                                    <p className="text-[9px] text-muted-foreground font-medium">{isRtl ? 'نبرة العميل' : 'Sentiment'}</p>
                                                    <p className="text-[10px] font-bold text-foreground mt-0.5 capitalize">
                                                        {copilotClassification?.sentiment === 'positive' ? (isRtl ? '😊 إيجابي' : 'Positive') :
                                                         copilotClassification?.sentiment === 'negative' ? (isRtl ? '😠 سلبي' : 'Negative') :
                                                         (isRtl ? '😐 محايد' : 'Neutral')}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Draft view */}
                                        <div className="space-y-2 text-start">
                                            <Label className="font-bold text-[11px] text-muted-foreground flex items-center gap-1.5">
                                                <span>✨</span>
                                                {isRtl ? 'مسودة الرد الذكية المقترحة' : 'AI Suggested Response Draft'}
                                            </Label>
                                            {draftLoading ? (
                                                <div className="w-full h-32 rounded-xl border bg-muted/30 flex flex-col items-center justify-center gap-2 p-4">
                                                    <Loader2 className="w-6 h-6 text-teal-600 animate-spin" />
                                                    <span className="text-[10px] text-muted-foreground">{isRtl ? 'جارٍ صياغة الرد الذكي...' : 'AI is drafting response...'}</span>
                                                </div>
                                            ) : copilotDraft ? (
                                                <div className="w-full rounded-xl border bg-gradient-to-br from-teal-50/50 to-emerald-50/20 dark:from-teal-950/10 dark:to-emerald-950/5 p-3.5 space-y-3">
                                                    <p className="text-xs text-foreground leading-relaxed whitespace-pre-wrap select-all font-medium text-start">{copilotDraft}</p>
                                                    <Button
                                                        onClick={handleApplyCopilotDraft}
                                                        className="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-lg h-8 text-[11px] font-bold gap-1 shadow-sm"
                                                    >
                                                        <span>✍️</span>
                                                        {isRtl ? 'إدراج الرد في صندوق الكتابة' : 'Insert Reply in Input Box'}
                                                    </Button>
                                                </div>
                                            ) : (
                                                <div className="w-full p-4 rounded-xl border border-dashed text-center text-xs text-muted-foreground">
                                                    {isRtl ? 'لا توجد مسودة حالياً. أرسل العميل رسالة أولاً ليقوم الذكاء الاصطناعي بتحليلها وصياغتها.' : 'No draft available yet. The customer needs to message first for the AI to draft a response.'}
                                                </div>
                                            )}
                                        </div>

                                        {/* Guidelines overview */}
                                        <div className="p-3 rounded-xl border bg-gradient-to-br from-violet-500/5 to-fuchsia-500/5 text-start space-y-1.5">
                                            <p className="text-[10px] font-bold text-muted-foreground uppercase flex items-center gap-1">
                                                <span>💡</span>
                                                {isRtl ? 'توجيهات المبيعات النشطة' : 'AI Active Directives'}
                                            </p>
                                            <p className="text-[10px] text-muted-foreground leading-relaxed">
                                                {isRtl ? 'يعتمد المساعد على قاعدة المعرفة الخاصة بحسابك، ويقوم بالصياغة الفورية للردود لتكون مقنعة، خلوقة، وبنبرة مبيعات ودية للغاية.' : 'The assistant coordinates using your account knowledge base, drafting highly concise FAQs to maximize conversion.'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </>
                ) : (
                    /* Empty state when no thread is selected */
                    <div className="flex-1 flex flex-col items-center justify-center text-center p-8">
                        <div className="w-24 h-24 rounded-full bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center mb-6">
                            <MessageCircle className="w-10 h-10 text-teal-400" />
                        </div>
                        <h3 className="text-xl font-bold text-foreground/80">{t.inbox.detailsTitle}</h3>
                        <p className="text-sm text-muted-foreground mt-2 max-w-sm">
                            {t.inbox.detailsSub}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
