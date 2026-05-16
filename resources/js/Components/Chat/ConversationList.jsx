import React, { useState } from 'react';

export default function ConversationList({
    conversations = [],
    selectedId = null,
    onSelect = () => {},
}) {
    const [filter, setFilter] = useState('all'); // all, orders, contracts, support

    const filteredConversations = conversations.filter((c) => {
        if (filter === 'all') return true;
        return c.type === filter;
    });

    const formatTime = (dateString) => {
        if (!dateString) return '';
        const d = new Date(dateString);
        // Simple formatter, could be improved for "yesterday", etc.
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    return (
        <div className="flex flex-col h-full bg-white border-r border-gray-200">
            <div className="p-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-800 mb-4">Messages</h2>

                {/* Filters */}
                <div className="flex space-x-2 overflow-x-auto pb-2 scrollbar-hide">
                    {['all', 'orders', 'contracts', 'support'].map((type) => (
                        <button
                            key={type}
                            onClick={() => setFilter(type)}
                            className={`px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-colors ${
                                filter === type
                                    ? 'bg-indigo-100 text-indigo-700'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}
                        >
                            {type.charAt(0).toUpperCase() + type.slice(1)}
                        </button>
                    ))}
                </div>
            </div>

            <div className="flex-1 overflow-y-auto">
                {filteredConversations.length === 0 ? (
                    <div className="p-4 text-center text-gray-500 text-sm">
                        No conversations found.
                    </div>
                ) : (
                    <ul className="divide-y divide-gray-100">
                        {filteredConversations.map((conv) => {
                            const isSelected = selectedId === conv.id;
                            return (
                                <li
                                    key={conv.id}
                                    onClick={() => onSelect(conv)}
                                    className={`p-4 cursor-pointer hover:bg-gray-50 transition-colors ${
                                        isSelected ? 'bg-indigo-50/50' : ''
                                    }`}
                                >
                                    <div className="flex items-center space-x-3">
                                        <div className="relative flex-shrink-0">
                                            <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
                                                {conv.name?.charAt(0) || '?'}
                                            </div>
                                            {conv.isOnline && (
                                                <span className="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white"></span>
                                            )}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                    {conv.name}
                                                </p>
                                                <p className="text-xs text-gray-500 whitespace-nowrap">
                                                    {formatTime(conv.last_message_at)}
                                                </p>
                                            </div>
                                            <div className="flex items-center justify-between mt-1">
                                                <p className={`text-sm truncate ${conv.unread_count > 0 ? 'text-gray-900 font-semibold' : 'text-gray-500'}`}>
                                                    {conv.last_message || 'Start a conversation'}
                                                </p>
                                                {conv.unread_count > 0 && (
                                                    <span className="inline-flex items-center justify-center h-5 w-5 rounded-full bg-indigo-600 text-white text-[10px] font-bold ml-2">
                                                        {conv.unread_count}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </div>
    );
}
