import { usePage } from '@inertiajs/react';
import axios from 'axios';
import React, { useEffect, useRef, useState, useCallback } from 'react';
import Message from './Message';
import { Paperclip, Send, X, WifiOff, FileText, Lock } from 'lucide-react';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function ChatWindow({
    conversationId,
    participants = [],
    readOnly = false,
    showHeader = true,
    className = '',
}) {
    const { auth } = usePage().props;
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [preview, setPreview] = useState(null);
    const [typingUsers, setTypingUsers] = useState([]);
    const [fetchError, setFetchError] = useState(null);
    const [isConnected, setIsConnected] = useState(true);

    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);
    const typingTimeoutsRef = useRef({});
    const textareaRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, typingUsers]);

    const markAsRead = () => {
        if (!conversationId) return;
        axios
            .post(`/api/conversations/${conversationId}/read`)
            .catch(console.error);
    };

    const fetchMessages = useCallback(async () => {
        if (!conversationId) return;
        try {
            const res = await axios.get(
                `/api/conversations/${conversationId}/messages`,
            );
            const newMessages = res.data.data.reverse();
            setMessages((prev) => {
                const prevLastId = prev.length > 0 ? prev[prev.length - 1].id : null;
                const newLastId = newMessages.length > 0 ? newMessages[newMessages.length - 1].id : null;
                if (prevLastId !== newLastId || prev.length !== newMessages.length) {
                    return newMessages;
                }
                return prev;
            });
            setFetchError(null);
        } catch (err) {
            console.error('Error fetching messages:', err);
            setFetchError('Failed to load messages. Please try again.');
        }
    }, [conversationId]);

    // Real-time events connection
    useEffect(() => {
        if (!conversationId) return;

        fetchMessages();

        if (window.Echo) {
            window.Echo.private(`conversation.${conversationId}`)
                .listen('MessageSent', (e) => {
                    setMessages((prev) => {
                        if (prev.find((m) => m.id === e.message.id)) return prev;
                        return [...prev, e.message];
                    });

                    if (document.hasFocus()) {
                        markAsRead();
                    }
                })
                .listenForWhisper('typing', (e) => {
                    if (e.userId !== auth.user.id) {
                        setTypingUsers((prev) => {
                            if (!prev.includes(e.name)) {
                                return [...prev, e.name];
                            }
                            return prev;
                        });

                        if (typingTimeoutsRef.current[e.userId]) {
                            clearTimeout(typingTimeoutsRef.current[e.userId]);
                        }

                        typingTimeoutsRef.current[e.userId] = setTimeout(() => {
                            setTypingUsers((prev) =>
                                prev.filter((name) => name !== e.name),
                            );
                            delete typingTimeoutsRef.current[e.userId];
                        }, 2000);
                    }
                });

            if (window.Echo.connector.pusher) {
                const handleStateChange = (states) => {
                    if (states.current === 'connected') {
                        setIsConnected(true);
                    } else if (states.current === 'disconnected' || states.current === 'unavailable') {
                        setIsConnected(false);
                    }
                };
                window.Echo.connector.pusher.connection.bind('state_change', handleStateChange);
            }
        }

        return () => {
            if (window.Echo) {
                window.Echo.leave(`conversation.${conversationId}`);
                if (window.Echo.connector.pusher) {
                    window.Echo.connector.pusher.connection.unbind('state_change');
                }
            }
            Object.values(typingTimeoutsRef.current).forEach(clearTimeout);
        };
    }, [conversationId]);

    // Polling fallback when offline
    useEffect(() => {
        let pollInterval;
        if (!isConnected && conversationId) {
            pollInterval = setInterval(() => {
                fetchMessages();
            }, 5000);
        }
        return () => clearInterval(pollInterval);
    }, [isConnected, conversationId, fetchMessages]);

    const handleFocus = () => {
        markAsRead();
    };

    const handleTyping = (e) => {
        setNewMessage(e.target.value);

        if (window.Echo && conversationId) {
            window.Echo.private(`conversation.${conversationId}`).whisper(
                'typing',
                {
                    userId: auth.user.id,
                    name: auth.user.name,
                },
            );
        }
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 15 * 1024 * 1024) {
                alert('File size must be less than 15MB');
                return;
            }
            setAttachment(file);
            if (file.type.startsWith('image/')) {
                setPreview(URL.createObjectURL(file));
            } else {
                setPreview(null);
            }
        }
    };

    const removeAttachment = () => {
        setAttachment(null);
        setPreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const sendMessage = async (e) => {
        if (e) e.preventDefault();
        if (readOnly) return;
        if (!newMessage.trim() && !attachment) return;

        const bodyToSend = newMessage.trim();
        const attachmentToSend = attachment;

        const formData = new FormData();
        if (bodyToSend) formData.append('body', bodyToSend);
        if (attachmentToSend) formData.append('attachment', attachmentToSend);

        // Optimistic UI update
        const tempMessage = {
            id: Date.now(),
            body: bodyToSend,
            sender_id: auth.user.id,
            sender: auth.user,
            created_at: new Date().toISOString(),
            isTemp: true,
            attachments: preview
                ? [
                      {
                          id: Date.now(),
                          type: 'image',
                          path: preview,
                          isTempUrl: true,
                      },
                  ]
                : [],
        };

        setMessages((prev) => [...prev, tempMessage]);
        setNewMessage('');
        removeAttachment();

        try {
            const res = await axios.post(
                `/api/conversations/${conversationId}/messages`,
                formData,
                {
                    headers: { 'Content-Type': 'multipart/form-data' },
                },
            );
            setMessages((prev) =>
                prev.map((m) => (m.id === tempMessage.id ? res.data : m)),
            );
        } catch (error) {
            console.error('Error sending message:', error);
            setMessages((prev) => prev.filter((m) => m.id !== tempMessage.id));
            alert('Failed to send message');
        }
    };

    const otherParticipants = participants.filter((p) => p.id !== auth.user.id);
    const chatTitle =
        otherParticipants.length > 0
            ? otherParticipants.map((p) => p.name).join(', ')
            : `Conversation #${conversationId}`;

    const firstUnreadIndex = messages.findIndex(
        (m) => !m.read && m.sender_id !== auth.user.id,
    );
    const unreadCount =
        firstUnreadIndex !== -1 ? messages.length - firstUnreadIndex : 0;

    return (
        <div
            className={cn(
                'flex flex-col h-full min-h-0 bg-white dark:bg-zinc-900 text-[#1d1d1f] dark:text-zinc-100 font-sans',
                showHeader ? 'rounded-2xl border border-black/5 dark:border-white/10 shadow-sm' : '',
                className
            )}
            onFocus={handleFocus}
            tabIndex={0}
        >
            {/* Optional Standalone Header */}
            {showHeader && (
                <div className="flex flex-col border-b border-black/5 dark:border-white/10 bg-[#fbfbfd] dark:bg-zinc-800/60 px-5 py-3.5 shrink-0">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3] font-bold text-sm">
                                {chatTitle.charAt(0)}
                            </div>
                            <div>
                                <h3 className="font-semibold text-sm text-[#1d1d1f] dark:text-zinc-100 leading-tight">
                                    {chatTitle}
                                </h3>
                                <p className="text-[11px] text-[#1d1d1f]/50 dark:text-zinc-400">
                                    {readOnly ? __('general.read_only', {}, 'للقراءة فقط') : __('general.active', {}, 'نشط')}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Offline Connection Warning */}
            {!isConnected && (
                <div className="bg-amber-50 dark:bg-amber-950/40 px-4 py-2 text-xs text-amber-800 dark:text-amber-300 font-medium flex items-center justify-center gap-2 border-b border-amber-200/50 dark:border-amber-900/50 shrink-0">
                    <WifiOff className="w-3.5 h-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <span>Real-time connection paused. Polling every 5s.</span>
                </div>
            )}

            {/* Messages Scrollable Area */}
            <div className="flex flex-1 min-h-0 flex-col overflow-y-auto bg-[#f5f5f7]/40 dark:bg-zinc-900/40 p-4 sm:p-6 space-y-2">
                {fetchError ? (
                    <div className="flex flex-1 items-center justify-center text-rose-500 dark:text-rose-400 font-medium text-xs sm:text-sm" data-testid="error-message">
                        {fetchError}
                    </div>
                ) : (
                    <>
                        {messages.map((msg, index) => {
                            const showUnreadSeparator = firstUnreadIndex === index;

                            return (
                                <React.Fragment key={msg.id}>
                                    {showUnreadSeparator && (
                                        <div className="flex items-center my-4 select-none">
                                            <div className="flex-1 border-t border-rose-200 dark:border-rose-900/40" />
                                            <span className="px-3 text-[11px] text-rose-600 dark:text-rose-400 font-semibold uppercase tracking-wider">
                                                {unreadCount} {__('general.new_messages', {}, 'رسائل جديدة')}
                                            </span>
                                            <div className="flex-1 border-t border-rose-200 dark:border-rose-900/40" />
                                        </div>
                                    )}
                                    <Message
                                        message={msg}
                                        isOwnMessage={msg.sender_id === auth.user.id}
                                    />
                                </React.Fragment>
                            );
                        })}

                        {typingUsers.length > 0 && (
                            <div className="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 ms-10 py-1">
                                <span className="flex gap-1 items-center">
                                    <span className="w-1.5 h-1.5 rounded-full bg-zinc-400 animate-pulse" />
                                    <span className="w-1.5 h-1.5 rounded-full bg-zinc-400 animate-pulse delay-150" />
                                    <span className="w-1.5 h-1.5 rounded-full bg-zinc-400 animate-pulse delay-300" />
                                </span>
                                <span className="text-[11px]">{typingUsers.join(', ')} is typing...</span>
                            </div>
                        )}

                        <div ref={messagesEndRef} />
                    </>
                )}
            </div>

            {/* Input Composer Area */}
            <div className="border-t border-black/5 dark:border-white/10 bg-white dark:bg-zinc-900 p-3 sm:p-4 shrink-0">
                {readOnly ? (
                    <div className="flex items-center justify-center gap-2 py-2 px-4 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-xs font-medium">
                        <Lock className="w-3.5 h-3.5" />
                        <span>{__('general.conversation_closed_notice', {}, 'تم إغلاق هذه التذكرة ولا يمكن إرسال ردود جديدة بها.')}</span>
                    </div>
                ) : (
                    <div>
                        {/* Selected Attachment Preview */}
                        {attachment && (
                            <div className="mb-3 flex items-center gap-2.5 p-2 rounded-xl bg-[#f5f5f7] dark:bg-zinc-800 border border-black/5 dark:border-white/10 max-w-fit">
                                {preview ? (
                                    <img
                                        src={preview}
                                        alt="Preview"
                                        className="h-12 w-12 rounded-lg object-cover border border-black/10"
                                    />
                                ) : (
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-black/5 dark:bg-white/5 text-zinc-600 dark:text-zinc-400">
                                        <FileText className="w-6 h-6" />
                                    </div>
                                )}
                                <div className="max-w-[200px] min-w-0">
                                    <p className="truncate text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                        {attachment.name}
                                    </p>
                                    <p className="text-[10px] text-zinc-500">
                                        {(attachment.size / 1024).toFixed(1)} KB
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={removeAttachment}
                                    className="p-1 rounded-full text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-black/5 transition-colors cursor-pointer"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        )}

                        {/* Hidden File Input */}
                        <input
                            ref={fileInputRef}
                            type="file"
                            onChange={handleFileChange}
                            disabled={readOnly}
                            className="hidden"
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                        />

                        {/* Composer Form */}
                        <form onSubmit={sendMessage} className="flex items-end gap-2">
                            {/* Attachment Button */}
                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                disabled={readOnly}
                                className="h-10 w-10 rounded-full flex items-center justify-center text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors disabled:opacity-40 cursor-pointer shrink-0"
                                title={__('general.attach_file', {}, 'إرفاق ملف أو صورة')}
                            >
                                <Paperclip className="w-4 h-4" />
                            </button>

                            {/* Textarea */}
                            <textarea
                                ref={textareaRef}
                                value={newMessage}
                                onChange={handleTyping}
                                placeholder={__('general.type_a_message', {}, 'اكتب رسالتك هنا...')}
                                disabled={readOnly}
                                rows={1}
                                className="flex-1 min-h-[42px] max-h-32 resize-none rounded-xl border border-black/10 dark:border-white/10 bg-[#f5f5f7] dark:bg-zinc-800/80 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#0071e3] transition-all"
                                dir="auto"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        sendMessage();
                                    }
                                }}
                            />

                            {/* Send Button */}
                            <button
                                type="submit"
                                disabled={readOnly || (!newMessage.trim() && !attachment)}
                                className="h-10 w-10 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white flex items-center justify-center transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm shrink-0 cursor-pointer"
                                title={__('general.send', {}, 'إرسال')}
                            >
                                <Send className="w-4 h-4 rtl:-scale-x-100" />
                            </button>
                        </form>
                    </div>
                )}
            </div>
        </div>
    );
}
