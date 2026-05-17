import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import Message from './Message';

export default function ChatWindow({ conversationId, participants = [], readOnly = false }) {
    const { auth } = usePage().props;
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [preview, setPreview] = useState(null);
    const [typingUsers, setTypingUsers] = useState([]);
    const [fetchError, setFetchError] = useState(null);

    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);
    const typingTimeoutsRef = useRef({});

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, typingUsers]);

    const markAsRead = () => {
        if (!conversationId) return;
        axios.post(`/api/conversations/${conversationId}/read`).catch(console.error);
    };

    const fetchMessages = async () => {
        if (!conversationId) return;
        try {
            const res = await axios.get(`/api/conversations/${conversationId}/messages`);
            const newMessages = res.data.data.reverse();
            setMessages(prev => {
                const prevLastId = prev.length > 0 ? prev[prev.length - 1].id : null;
                const newLastId = newMessages.length > 0 ? newMessages[newMessages.length - 1].id : null;
                if (prevLastId !== newLastId || prev.length !== newMessages.length) {
                    return newMessages;
                }
                return prev;
            });
            setFetchError(null);
        } catch (err) {
            console.error("Error fetching messages:", err);
            setFetchError("Failed to load messages. Please try again.");
        }
    };

    useEffect(() => {
        if (!conversationId) return;

        // Fetch initial messages
        fetchMessages();

        // Subscribe to real-time events
        if (window.Echo) {
            window.Echo.private(`conversation.${conversationId}`)
                .listen('MessageSent', (e) => {
                    setMessages(prev => {
                        // Prevent duplicates
                        if (prev.find(m => m.id === e.message.id)) return prev;
                        return [...prev, e.message];
                    });

                    // Mark as read if window is focused
                    if (document.hasFocus()) {
                        markAsRead();
                    }
                })
                .listenForWhisper('typing', (e) => {
                    if (e.userId !== auth.user.id) {
                        setTypingUsers(prev => {
                            if (!prev.includes(e.name)) {
                                return [...prev, e.name];
                            }
                            return prev;
                        });

                        // Clear individual typing indicator after 2 seconds of inactivity
                        if (typingTimeoutsRef.current[e.userId]) {
                            clearTimeout(typingTimeoutsRef.current[e.userId]);
                        }

                        typingTimeoutsRef.current[e.userId] = setTimeout(() => {
                            setTypingUsers(prev => prev.filter(name => name !== e.name));
                            delete typingTimeoutsRef.current[e.userId];
                        }, 2000);
                    }
                });
        }

        return () => {
            if (window.Echo) {
                window.Echo.leave(`conversation.${conversationId}`);
            }
            // Clear all typing timeouts
            Object.values(typingTimeoutsRef.current).forEach(clearTimeout);
        };
    }, [conversationId]);

    const handleFocus = () => {
        markAsRead();
    };

    const handleTyping = (e) => {
        setNewMessage(e.target.value);

        if (window.Echo && conversationId) {
            window.Echo.private(`conversation.${conversationId}`)
                .whisper('typing', {
                    userId: auth.user.id,
                    name: auth.user.name
                });
        }
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            setAttachment(file);
            setPreview(URL.createObjectURL(file));
        }
    };

    const removeAttachment = () => {
        setAttachment(null);
        setPreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const sendMessage = async (e) => {
        e.preventDefault();
        if (readOnly) return;
        if (!newMessage.trim() && !attachment) return;

        const formData = new FormData();
        if (newMessage.trim()) formData.append('body', newMessage.trim());
        if (attachment) formData.append('attachment', attachment);

        // Optimistic UI update
        const tempMessage = {
            id: Date.now(),
            body: newMessage.trim(),
            sender_id: auth.user.id,
            sender: auth.user,
            created_at: new Date().toISOString(),
            isTemp: true,
            attachments: preview ? [{ id: Date.now(), type: 'image', path: preview, isTempUrl: true }] : []
        };

        setMessages(prev => [...prev, tempMessage]);
        setNewMessage('');
        removeAttachment();

        try {
            const res = await axios.post(`/api/conversations/${conversationId}/messages`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            // Replace temp message with real one
            setMessages(prev => prev.map(m => m.id === tempMessage.id ? res.data : m));
        } catch (error) {
            console.error('Error sending message:', error);
            // Remove temp message on error
            setMessages(prev => prev.filter(m => m.id !== tempMessage.id));
            alert('Failed to send message');
        }
    };

    // Derived values for the header
    const otherParticipants = participants.filter(p => p.id !== auth.user.id);
    const chatTitle = otherParticipants.length > 0
        ? otherParticipants.map(p => p.name).join(', ')
        : `Conversation #${conversationId}`;

    // Calculate unread separator index
    const firstUnreadIndex = messages.findIndex(m => !m.read && m.sender_id !== auth.user.id);
    const unreadCount = firstUnreadIndex !== -1 ? messages.length - firstUnreadIndex : 0;

    return (
        <div className="flex flex-col h-[600px] bg-white border rounded-lg shadow-sm" onFocus={handleFocus} tabIndex="0">
            {/* Header */}
            <div className="p-4 border-b bg-gray-50 flex items-center gap-3">
                <div className="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center font-bold text-indigo-700 flex-shrink-0">
                    {chatTitle.charAt(0)}
                </div>
                <div>
                    <h3 className="font-semibold text-gray-900">{chatTitle}</h3>
                    <p className="text-xs text-gray-500">
                        {readOnly ? "Read Only" : "Active"}
                    </p>
                </div>
            </div>

            {/* Messages Area */}
            <div className="flex-1 overflow-y-auto p-4 bg-gray-50 flex flex-col">
                {fetchError ? (
                    <div className="flex-1 flex items-center justify-center text-red-500 font-medium" data-testid="error-message">
                        {fetchError}
                    </div>
                ) : (
                    <>
                        {messages.map((msg, index) => {
                            const showUnreadSeparator = firstUnreadIndex === index;

                            return (
                                <React.Fragment key={msg.id}>
                                    {showUnreadSeparator && (
                                        <div className="flex items-center my-4">
                                            <div className="flex-1 border-t border-red-300"></div>
                                            <span className="px-2 text-xs text-red-500 font-medium">── {unreadCount} new message{unreadCount !== 1 ? 's' : ''} ──</span>
                                            <div className="flex-1 border-t border-red-300"></div>
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
                            <div className="flex items-center gap-2 text-gray-500 text-sm mt-2 ml-10">
                                <span className="italic">{typingUsers.join(', ')} is typing...</span>
                            </div>
                        )}

                        <div ref={messagesEndRef} />
                    </>
                )}
            </div>

            {/* Input Area */}
            <div className="p-4 bg-white border-t rounded-b-lg">
                {preview && (
                    <div className="relative inline-block mb-2 group">
                        <img src={preview} alt="Preview" className="h-20 w-20 object-cover rounded-md border" />
                        <button
                            onClick={removeAttachment}
                            className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            &times;
                        </button>
                    </div>
                )}

                <form onSubmit={sendMessage} className="flex items-end gap-2">
                    <input
                        type="file"
                        ref={fileInputRef}
                        onChange={handleFileChange}
                        accept="image/*"
                        className="hidden"
                        disabled={readOnly}
                    />
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        disabled={readOnly}
                        className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors disabled:opacity-50"
                        title="Attach image"
                    >
                        <span role="img" aria-label="attachment">📎</span>
                    </button>

                    <textarea
                        value={newMessage}
                        onChange={handleTyping}
                        placeholder={readOnly ? "Chat is closed" : "Type a message..."}
                        disabled={readOnly}
                        className="flex-1 resize-none rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 max-h-32 min-h-[44px] py-2 px-3"
                        rows="1"
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                sendMessage(e);
                            }
                        }}
                    />

                    <button
                        type="submit"
                        disabled={readOnly || (!newMessage.trim() && !attachment)}
                        className="p-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span role="img" aria-label="send">Send →</span>
                    </button>
                </form>
            </div>
        </div>
    );
}
