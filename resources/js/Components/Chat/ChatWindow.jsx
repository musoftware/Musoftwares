import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import Message from './Message';

export default function ChatWindow({ conversationId }) {
    const { auth } = usePage().props;
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [preview, setPreview] = useState(null);

    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);
    const pollingIntervalRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const fetchMessages = async () => {
        if (!conversationId) return;
        try {
            const res = await axios.get(`/api/conversations/${conversationId}/messages`);
            const newMessages = res.data.data.reverse();

            setMessages(prev => {
                // Check if the last message ID has changed or if length differs
                const prevLastId = prev.length > 0 ? prev[prev.length - 1].id : null;
                const newLastId = newMessages.length > 0 ? newMessages[newMessages.length - 1].id : null;

                if (prevLastId !== newLastId || prev.length !== newMessages.length) {
                    return newMessages;
                }
                return prev;
            });
        } catch (err) {
            console.error("Error fetching messages:", err);
        }
    };

    useEffect(() => {
        if (!conversationId) return;

        // Fetch initial messages
        fetchMessages();

        // Start polling
        pollingIntervalRef.current = setInterval(() => {
            fetchMessages();
        }, 3000);

        return () => {
            if (pollingIntervalRef.current) clearInterval(pollingIntervalRef.current);
        };
    }, [conversationId]);

    const markAsRead = () => {
        if (!conversationId) return;
        axios.post(`/api/conversations/${conversationId}/read`).catch(console.error);
    };

    const handleFocus = () => {
        markAsRead();
    };

    const handleTyping = (e) => {
        setNewMessage(e.target.value);
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
        if (!newMessage.trim() && !attachment) return;

        const formData = new FormData();
        if (newMessage.trim()) formData.append('body', newMessage.trim());
        if (attachment) formData.append('attachment', attachment);

        // Optimistic UI update
        const tempMessage = {
            id: Date.now(),
            body: newMessage.trim(),
            sender_id: auth.user.id,
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

    return (
        <div className="flex flex-col h-[600px] bg-white border rounded-lg shadow-sm" onFocus={handleFocus}>
            <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
                {messages.map((msg) => (
                    <Message
                        key={msg.id}
                        message={msg}
                        isOwnMessage={msg.sender_id === auth.user.id}
                    />
                ))}
                <div ref={messagesEndRef} />
            </div>

            <div className="p-4 bg-white border-t rounded-b-lg">
                {preview && (
                    <div className="relative inline-block mb-2">
                        <img src={preview} alt="Preview" className="h-20 w-20 object-cover rounded-md border" />
                        <button
                            onClick={removeAttachment}
                            className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs"
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
                    />
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
                        title="Attach image"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </button>

                    <textarea
                        value={newMessage}
                        onChange={handleTyping}
                        placeholder="Type a message..."
                        className="flex-1 resize-none rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 max-h-32 min-h-[44px] py-2 px-3"
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
                        disabled={!newMessage.trim() && !attachment}
                        className="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    );
}
