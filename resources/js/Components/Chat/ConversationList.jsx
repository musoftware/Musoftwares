import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { __ } from '@/lib/i18n';

export default function ConversationList({ onSelectConversation, selectedId }) {
    const { auth } = usePage().props;
    const [conversations, setConversations] = useState([]);

    const fetchConversations = async () => {
        try {
            const res = await axios.get('/api/conversations');
            const formatted = res.data.map((conv) => {
                const otherParticipant = conv.participants?.find(
                    (p) => p.user_id !== auth.user.id,
                );
                const name =
                    otherParticipant?.user?.name || `Conversation #${conv.id}`;

                return {
                    id: conv.id,
                    type: conv.type || 'Support', // Fallback type if backend doesn't provide
                    user: { name: name, isOnline: false },
                    lastMessage: conv.messages?.[0] || null,
                    unreadCount: conv.unread_count || 0,
                };
            });

            setConversations(formatted);
        } catch (err) {
            console.error('Failed to fetch conversations', err);
        }
    };

    useEffect(() => {
        fetchConversations();

        // Listen for new messages or conversation updates on the user's private channel
        if (window.Echo) {
            window.Echo.private(`user.${auth.user.id}`).listen(
                'ConversationUpdated',
                (e) => {
                    setConversations((prev) => {
                        const existing = prev.find(
                            (c) => c.id === e.conversation.id,
                        );
                        if (existing) {
                            return prev
                                .map((c) =>
                                    c.id === existing.id
                                        ? {
                                              ...c,
                                              lastMessage:
                                                  e.message || c.lastMessage,
                                              unreadCount:
                                                  c.id === selectedId
                                                      ? 0
                                                      : c.unreadCount + 1,
                                          }
                                        : c,
                                )
                                .sort((a, b) => {
                                    // Sort by latest message
                                    const timeA = a.lastMessage?.created_at
                                        ? new Date(
                                              a.lastMessage.created_at,
                                          ).getTime()
                                        : 0;
                                    const timeB = b.lastMessage?.created_at
                                        ? new Date(
                                              b.lastMessage.created_at,
                                          ).getTime()
                                        : 0;
                                    return timeB - timeA;
                                });
                        } else {
                            // Fetch fresh if a completely new conversation is detected
                            fetchConversations();
                            return prev;
                        }
                    });
                },
            );
        }

        return () => {
            if (window.Echo) {
                window.Echo.leave(`user.${auth.user.id}`);
            }
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
                <h3 className="border-y bg-gray-50 px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
                    {title}
                </h3>
                {items.map((conv) => (
                    <div
                        key={conv.id}
                        onClick={() => {
                            setConversations((prev) =>
                                prev.map((c) =>
                                    c.id === conv.id
                                        ? { ...c, unreadCount: 0 }
                                        : c,
                                ),
                            );
                            onSelectConversation(conv.id);
                        }}
                        className={`flex cursor-pointer items-center gap-3 border-b p-4 transition-colors hover:bg-gray-50 ${selectedId === conv.id ? 'border-s-4 border-s-indigo-500 bg-indigo-50' : 'border-s-4 border-s-transparent'}`}
                    >
                        <div className="relative">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">
                                {conv.user.name.charAt(0)}
                            </div>
                            {conv.user.isOnline && (
                                <div className="absolute end-0 bottom-0 h-3 w-3 rounded-full border-2 border-white bg-green-500"></div>
                            )}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="mb-1 flex items-baseline justify-between">
                                <h4 className="truncate text-sm font-semibold text-gray-900">
                                    {conv.user.name}
                                </h4>
                                {conv.lastMessage && (
                                    <span className="ms-2 text-[10px] whitespace-nowrap text-gray-500">
                                        {new Date(
                                            conv.lastMessage.created_at,
                                        ).toLocaleTimeString([], {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <p
                                    className={`truncate text-xs ${conv.unreadCount > 0 ? 'font-semibold text-gray-900' : 'text-gray-500'}`}
                                >
                                    {conv.lastMessage
                                        ? conv.lastMessage.body || '📷 Image'
                                        : 'No messages'}
                                </p>
                                {conv.unreadCount > 0 && (
                                    <span className="min-w-[1.25rem] rounded-full bg-indigo-600 px-1.5 py-0.5 text-center text-[10px] font-bold text-white">
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
        <div className="flex h-[600px] w-80 flex-col rounded-lg border bg-white shadow-sm">
            <div className="flex items-center justify-between border-b p-4">
                <h2 className="text-lg font-semibold text-gray-900">
                    {__('general.messages')}</h2>
            </div>
            <div className="flex-1 overflow-y-auto">
                {conversations.length === 0 ? (
                    <div className="flex flex-col items-center p-8 text-center text-gray-500">
                        <svg
                            className="mb-2 h-12 w-12 text-gray-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="1"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                            />
                        </svg>
                        <p>{__('general.no_active_conversations')}</p>
                    </div>
                ) : (
                    <>
                        {renderGroup(
                            'Orders',
                            groupedConversations['Marketplace'],
                        )}
                        {renderGroup(
                            'Contracts',
                            groupedConversations['Freelance'],
                        )}
                        {renderGroup(
                            'Support',
                            groupedConversations['Support'],
                        )}
                        {/* Fallback for unclassified */}
                        {Object.keys(groupedConversations)
                            .filter(
                                (k) =>
                                    ![
                                        'Marketplace',
                                        'Freelance',
                                        'Support',
                                    ].includes(k),
                            )
                            .map((k) =>
                                renderGroup(k, groupedConversations[k]),
                            )}
                    </>
                )}
            </div>
        </div>
    );
}
