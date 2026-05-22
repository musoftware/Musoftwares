import React, { useState, useEffect, useRef } from 'react';
import {
    MessageCircle, Send, Search, ArrowLeft, Phone, User,
    Image, Paperclip, Smile, ChevronRight, Clock, Check, CheckCheck,
    RefreshCw, Inbox as InboxIcon
} from 'lucide-react';
import { Card } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';

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

export default function InboxWorkspace({ callRPC, daemonConnected, sessions, onNewMessageRef, onUnreadReset, t, locale }: any) {
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
            const res: any = await callRPCRef.current('getThreads', {});
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

        const connect = () => {
            try {
                ws = new WebSocket(`ws://${host}:18401/ws`);
                ws.onopen = () => console.log('[Inbox WS] ✅ Connected — listening for live messages');
                ws.onmessage = (ev) => {
                    try {
                        const msg = JSON.parse(ev.data);
                        if (msg.event === 'whatsapp.inbox.new_message') {
                            console.log('[Inbox WS] 📩 New message:', msg.data?.content?.substring(0, 30));
                            // Use refs to call latest function versions
                            fetchThreadsRef.current();
                            const current = selectedThreadRef.current;
                            if (current && msg.data?.threadId === current.id) {
                                fetchMessagesRef.current(current.id);
                            }
                        }
                    } catch {}
                };
                ws.onclose = () => {
                    reconnectTimer = setTimeout(connect, 3000);
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

    // Polling as reliable fallback — threads every 3s
    useEffect(() => {
        if (!daemonConnected) return;
        fetchThreads();
        const interval = setInterval(fetchThreads, 3000);
        return () => clearInterval(interval);
    }, [daemonConnected]);

    // Polling messages for active thread every 2s
    useEffect(() => {
        if (!selectedThread || !daemonConnected) return;
        fetchMessages(selectedThread.id);
        const interval = setInterval(() => fetchMessages(selectedThread.id), 2000);
        return () => clearInterval(interval);
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
                fetchThreads();
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
        try {
            await callRPC('sendReply', {
                sessionId: selectedThread.session_id,
                phone: selectedThread.contact_number,
                content: replyText.trim(),
                messageType: 'text'
            });
            setReplyText('');
            await fetchMessages(selectedThread.id);
            await fetchThreads();
        } catch (err: any) {
            alert(`${t.inbox.replyFailed}${err.message}`);
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
                        <Button variant="ghost" size="icon" onClick={fetchThreads} className="text-white/70 hover:text-white hover:bg-white/10 h-8 w-8">
                            <RefreshCw className="w-4 h-4" />
                        </Button>
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
                                        <p className="font-bold text-sm truncate">
                                            {thread.contact_name || `+${thread.contact_number}`}
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
                            <div className="flex-1 min-w-0 text-start">
                                <p className="font-bold text-sm">{selectedThread.contact_name || `+${selectedThread.contact_number}`}</p>
                                <p className="text-[11px] text-muted-foreground font-mono">+{selectedThread.contact_number}</p>
                            </div>
                            <div className="flex items-center gap-1">
                                <Badge variant="outline" className="text-[10px] gap-1 text-teal-600">
                                    <Phone className="w-3 h-3" />
                                    {selectedThread.session_id}
                                </Badge>
                            </div>
                        </div>

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
                                                    <CheckCheck className="w-3.5 h-3.5 text-teal-500" />
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
