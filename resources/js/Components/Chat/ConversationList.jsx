import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';

export default function ConversationList({ onSelectConversation, selectedId }) {
    const { auth } = usePage().props;
    const [conversations, setConversations] = useState([]);

    const fetchConversations = async () => {
        try {
            const res = await axios.get('/api/conversations');
            const formatted = res.data.map(conv => {
                const otherParticipant = conv.participants?.find(p => p.user_id !== auth.user.id);
                const name = otherParticipant?.user?.name || `Conversation #${conv.id}`;

                return {
                    id: conv.id,
                    type: conv.type || 'Support', // Fallback type if backend doesn't provide
                    user: { name: name, isOnline: false },
                    lastMessage: conv.messages?.[0] || null,
                    unreadCount: conv.unread_count || 0
                };
            });

            setConversations(formatted);
        } catch (err) {
            console.error("Failed to fetch conversations", err);
        }
    };

    useEffect(() => {
        fetchConversations();

        // Listen for new messages or conversation updates on the user's private channel
        if (window.Echo) {
            window.Echo.private(`user.${auth.user.id}`)
                .listen('ConversationUpdated', (e) => {
                    setConversations(prev => {
                        const existing = prev.find(c => c.id === e.conversation.id);
                        if (existing) {
                            return prev.map(c => c.id === existing.id ? {
                                ...c,
                                lastMessage: e.message || c.lastMessage,
                                unreadCount: c.id === selectedId ? 0 : (c.unreadCount + 1)
                            } : c).sort((a, b) => {
                                // Sort by latest message
                                const timeA = a.lastMessage?.created_at ? new Date(a.lastMessage.created_at).getTime() : 0;
                                const timeB = b.lastMessage?.created_at ? new Date(b.lastMessage.created_at).getTime() : 0;
                                return timeB - timeA;
                            });
                        } else {
                            // Fetch fresh if a completely new conversation is detected
                            fetchConversations();
                            return prev;
                        }
                    });
                });
        }

        return () => {
            if (window.Echo) {
                window.Echo.leave(`user.${auth.user.id}`);
            }
        };
    }, [auth.user.id, selectedId]);

    // Group conversations by type
    const groupedConversations = conversations.reduce((acc, conv) => {
        if (!acc[conv.type]) acc[conv.type] = [];
        acc[conv.type].push(conv);
        return acc;
    }, {});

    const renderGroup = (title, items) => {
        if (!items || items.length === 0) return null;

        return (
            <div key={title} className="mb-4">
                <h3 className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 border-y">
                    {title}
                </h3>
                {items.map(conv => (
                    <div
                        key={conv.id}
                        onClick={() => {
                            setConversations(prev => prev.map(c => c.id === conv.id ? {...c, unreadCount: 0} : c));
                            onSelectConversation(conv.id);
                        }}
                        className={`p-4 border-b cursor-pointer hover:bg-gray-50 flex items-center gap-3 transition-colors ${selectedId === conv.id ? 'bg-indigo-50 border-l-4 border-l-indigo-500' : 'border-l-4 border-l-transparent'}`}
                    >
                        <div className="relative">
                            <div className="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center font-bold text-indigo-700">
                                {conv.user.name.charAt(0)}
                            </div>
                            {conv.user.isOnline && (
                                <div className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                            )}
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="flex justify-between items-baseline mb-1">
                                <h4 className="font-semibold text-sm truncate text-gray-900">
                                    {conv.user.name}
                                </h4>
                                {conv.lastMessage && (
                                    <span className="text-[10px] text-gray-500 whitespace-nowrap ml-2">
                                        {new Date(conv.lastMessage.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                    </span>
                                )}
                            </div>
                            <div className="flex justify-between items-center gap-2">
                                <p className={`text-xs truncate ${conv.unreadCount > 0 ? 'font-semibold text-gray-900' : 'text-gray-500'}`}>
                                    {conv.lastMessage ? (
                                        conv.lastMessage.body || '📷 Image'
                                    ) : 'No messages'}
                                </p>
                                {conv.unreadCount > 0 && (
                                    <span className="bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[1.25rem] text-center">
                                        {conv.unreadCount}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        );
    };

    return (
        <div className="flex flex-col h-[600px] bg-white border rounded-lg shadow-sm w-80">
            <div className="p-4 border-b flex justify-between items-center">
                <h2 className="font-semibold text-lg text-gray-900">Messages</h2>
            </div>
            <div className="flex-1 overflow-y-auto">
                {conversations.length === 0 ? (
                    <div className="p-8 text-center text-gray-500 flex flex-col items-center">
                        <svg className="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <p>No active conversations</p>
                    </div>
                ) : (
                    <>
                        {renderGroup('Orders', groupedConversations['Marketplace'])}
                        {renderGroup('Contracts', groupedConversations['Freelance'])}
                        {renderGroup('Support', groupedConversations['Support'])}
                        {/* Fallback for unclassified */}
                        {Object.keys(groupedConversations)
                            .filter(k => !['Marketplace', 'Freelance', 'Support'].includes(k))
                            .map(k => renderGroup(k, groupedConversations[k]))
                        }
                    </>
                )}
            </div>
        </div>
    );
}
