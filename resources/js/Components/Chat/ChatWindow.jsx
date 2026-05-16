import React, { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

export default function ChatWindow({
    conversationId,
    currentUserId,
    participants = [],
    initialMessages = [],
    readOnly = false,
    title = 'Chat',
    subtitle = '',
    status = '',
    unreadCount = 0,
}) {
    const [messages, setMessages] = useState(initialMessages);
    const [newMessage, setNewMessage] = useState('');
    const [typingUsers, setTypingUsers] = useState([]);
    const [selectedImage, setSelectedImage] = useState(null);
    const [attachment, setAttachment] = useState(null);
    const [attachmentPreview, setAttachmentPreview] = useState(null);
    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    useEffect(() => {
        if (!conversationId || typeof window === 'undefined' || !window.Echo) return;

        const channel = window.Echo.private(`conversation.${conversationId}`);

        channel.listen('MessageSent', (e) => {
            if (e.message) {
                setMessages((prev) => [...prev, e.message]);
            }
        });

        channel.listenForWhisper('typing', (e) => {
            if (e.user) {
                setTypingUsers((prev) => {
                    if (!prev.find((u) => u.id === e.user.id)) {
                        return [...prev, e.user];
                    }
                    return prev;
                });

                // Remove typing indicator after 2 seconds
                setTimeout(() => {
                    setTypingUsers((prev) => prev.filter((u) => u.id !== e.user.id));
                }, 2000);
            }
        });

        return () => {
            channel.stopListening('MessageSent');
            channel.stopListeningForWhisper('typing');
            window.Echo.leave(`conversation.${conversationId}`);
        };
    }, [conversationId]);

    const handleTyping = () => {
        if (!conversationId || typeof window === 'undefined' || !window.Echo) return;
        window.Echo.private(`conversation.${conversationId}`).whisper('typing', {
            user: { id: currentUserId, name: 'User' }, // Adjust as needed
        });
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setAttachment(file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setAttachmentPreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSendMessage = (e) => {
        e.preventDefault();
        if ((!newMessage.trim() && !attachment) || readOnly) return;

        const formData = new FormData();
        formData.append('content', newMessage);
        if (attachment) {
            formData.append('attachment', attachment);
        }

        router.post(
            `/chat/conversations/${conversationId}/messages`,
            formData,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNewMessage('');
                    setAttachment(null);
                    setAttachmentPreview(null);
                    if (fileInputRef.current) {
                        fileInputRef.current.value = '';
                    }
                },
            }
        );
    };

    const formatTime = (dateString) => {
        if (!dateString) return '';
        const d = new Date(dateString);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    const getParticipant = (userId) => {
        return participants.find((p) => p.id === userId) || { name: 'Unknown' };
    };

    // Determine where to place the unread separator
    const unreadIndex = messages.length - unreadCount;

    return (
        <div className="flex flex-col h-full bg-white rounded-lg shadow border border-gray-200">
            {/* Header */}
            <div className="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 rounded-t-lg">
                <div className="flex items-center space-x-3">
                    <div className="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                        {title.charAt(0)}
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-800">{title}</h3>
                        <p className="text-xs text-gray-500">
                            {status && <span className="mr-2">Status: {status}</span>}
                            {subtitle}
                        </p>
                    </div>
                </div>
            </div>

            {/* Messages Area */}
            <div className="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50">
                {messages.map((msg, index) => {
                    const isOwn = msg.user_id === currentUserId;
                    const sender = isOwn ? { name: 'You' } : getParticipant(msg.user_id);
                    const showUnreadSeparator = unreadCount > 0 && index === unreadIndex;

                    return (
                        <React.Fragment key={msg.id || index}>
                            {showUnreadSeparator && (
                                <div className="flex items-center text-xs text-gray-400 my-4">
                                    <div className="flex-1 border-t border-gray-200"></div>
                                    <span className="px-2">── {unreadCount} new messages ──</span>
                                    <div className="flex-1 border-t border-gray-200"></div>
                                </div>
                            )}
                            <div className={`flex ${isOwn ? 'justify-end' : 'justify-start'}`}>
                                <div className={`max-w-[70%] group flex flex-col ${isOwn ? 'items-end' : 'items-start'}`}>
                                    <span className="text-xs text-gray-500 mb-1 px-1">
                                        {sender.name}
                                    </span>
                                    <div
                                        className={`px-4 py-2 rounded-2xl relative ${
                                            isOwn
                                                ? 'bg-indigo-600 text-white rounded-tr-none'
                                                : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none shadow-sm'
                                        }`}
                                        title={formatTime(msg.created_at)}
                                    >
                                        {msg.content && <p className="text-sm whitespace-pre-wrap">{msg.content}</p>}

                                        {/* Image Attachment */}
                                        {msg.attachment_url && (
                                            <div className="mt-2 cursor-pointer" onClick={() => setSelectedImage(msg.attachment_url)}>
                                                <img
                                                    src={msg.attachment_url}
                                                    alt="attachment"
                                                    className="max-h-32 rounded-lg object-cover"
                                                />
                                            </div>
                                        )}

                                        {/* Read Receipt */}
                                        {isOwn && (
                                            <span className="absolute bottom-1 right-2 text-[10px] opacity-70">
                                                {msg.read_at ? '✓✓' : '✓'}
                                            </span>
                                        )}
                                    </div>
                                    <span className="text-[10px] text-gray-400 mt-1 px-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        {formatTime(msg.created_at)}
                                    </span>
                                </div>
                            </div>
                        </React.Fragment>
                    );
                })}

                {typingUsers.length > 0 && (
                    <div className="text-xs text-gray-500 italic">
                        {typingUsers.map((u) => u.name).join(', ')} is typing...
                    </div>
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* Input Area */}
            {!readOnly && (
                <div className="p-4 border-t border-gray-200 bg-white rounded-b-lg">
                    {attachmentPreview && (
                        <div className="mb-2 relative inline-block">
                            <img src={attachmentPreview} alt="Preview" className="h-20 w-20 object-cover rounded-md border" />
                            <button
                                type="button"
                                className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 w-5 h-5 flex items-center justify-center text-xs"
                                onClick={() => {
                                    setAttachment(null);
                                    setAttachmentPreview(null);
                                    if (fileInputRef.current) {
                                        fileInputRef.current.value = '';
                                    }
                                }}
                            >
                                ✕
                            </button>
                        </div>
                    )}
                    <form onSubmit={handleSendMessage} className="flex items-end space-x-2">
                        <input
                            type="file"
                            accept="image/*"
                            ref={fileInputRef}
                            onChange={handleFileChange}
                            className="hidden"
                        />
                        <button
                            type="button"
                            className="p-2 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-full transition-colors"
                            title="Attach File"
                            onClick={() => fileInputRef.current?.click()}
                        >
                            📎
                        </button>
                        <div className="flex-1 bg-gray-100 rounded-2xl px-4 py-2">
                            <textarea
                                value={newMessage}
                                onChange={(e) => {
                                    setNewMessage(e.target.value);
                                    handleTyping();
                                }}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleSendMessage(e);
                                    }
                                }}
                                placeholder="Type a message..."
                                className="w-full bg-transparent border-none focus:ring-0 resize-none max-h-32 text-sm text-gray-800"
                                rows={1}
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={!newMessage.trim() && !attachment}
                            className="p-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Send →
                        </button>
                    </form>
                </div>
            )}

            {/* Image Modal */}
            {selectedImage && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4"
                    onClick={() => setSelectedImage(null)}
                >
                    <img src={selectedImage} alt="Enlarged" className="max-w-full max-h-full rounded" />
                </div>
            )}
        </div>
    );
}
