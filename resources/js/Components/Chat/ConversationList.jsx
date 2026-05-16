import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';

export default function ConversationList({ onSelectConversation, selectedId }) {
    const { auth } = usePage().props;
    const [conversations, setConversations] = useState([]);
    const pollingIntervalRef = useRef(null);

    const fetchConversations = async () => {
        try {
            const res = await axios.get('/api/conversations');
            // Expected response format is an array of conversations.
            // We format it mapping over to simulate the UI state.
            const formatted = res.data.map(conv => {
                // Determine the other participant name (simplification for UI)
                const otherParticipant = conv.participants?.find(p => p.user_id !== auth.user.id);
                const name = otherParticipant?.user?.name || `Conversation #${conv.id}`;

                return {
                    id: conv.id,
                    user: { name: name, isOnline: false }, // Polling doesn't support real-time presence well
                    lastMessage: conv.messages?.[0] || null, // Assuming backend sends latest message
                    unreadCount: 0 // In real app, calculate based on last_read_at
                };
            });

            setConversations(formatted);
        } catch (err) {
            console.error("Failed to fetch conversations", err);
        }
    };

    useEffect(() => {
        fetchConversations();

        pollingIntervalRef.current = setInterval(() => {
            fetchConversations();
        }, 5000); // Poll every 5 seconds

        return () => {
            if (pollingIntervalRef.current) clearInterval(pollingIntervalRef.current);
        };
    }, []);

    return (
        <div className="flex flex-col h-[600px] bg-white border rounded-lg shadow-sm w-80">
            <div className="p-4 border-b font-semibold text-lg">
                Conversations
            </div>
            <div className="flex-1 overflow-y-auto">
                {conversations.length === 0 ? (
                    <div className="p-4 text-center text-gray-500">No conversations</div>
                ) : (
                    conversations.map(conv => (
                        <div
                            key={conv.id}
                            onClick={() => {
                                // Mark as read locally
                                setConversations(prev => prev.map(c => c.id === conv.id ? {...c, unreadCount: 0} : c));
                                onSelectConversation(conv.id);
                            }}
                            className={`p-4 border-b cursor-pointer hover:bg-gray-50 flex items-center gap-3 transition-colors ${selectedId === conv.id ? 'bg-blue-50' : ''}`}
                        >
                            <div className="relative">
                                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600">
                                    {conv.user.name.charAt(0)}
                                </div>
                                {conv.user.isOnline && (
                                    <div className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                                )}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="flex justify-between items-baseline mb-1">
                                    <h4 className="font-semibold text-sm truncate">
                                        {conv.user.name}
                                    </h4>
                                    {conv.lastMessage && (
                                        <span className="text-[10px] text-gray-500">
                                            {new Date(conv.lastMessage.created_at).toLocaleDateString(undefined, {month: 'short', day: 'numeric'})}
                                        </span>
                                    )}
                                </div>
                                <div className="flex justify-between items-center">
                                    <p className={`text-xs truncate ${conv.unreadCount > 0 ? 'font-semibold text-gray-900' : 'text-gray-500'}`}>
                                        {conv.lastMessage?.body || 'No messages'}
                                    </p>
                                    {conv.unreadCount > 0 && (
                                        <span className="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[1.25rem] text-center">
                                            {conv.unreadCount}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}
