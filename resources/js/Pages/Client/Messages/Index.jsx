import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { MessageSquare, Plus, Search, User, Shield, Briefcase, ShoppingBag, Send } from 'lucide-react';
import ChatWindow from '@/Components/Chat/ChatWindow';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function MessagesIndex({ conversations, users }) {
    const { auth } = usePage().props;
    const currentUser = auth.user;

    const [activeTab, setActiveTab] = useState('all'); // all, service, freelancer, client, direct
    const [selectedConvId, setSelectedConvId] = useState(conversations?.[0]?.id || null);
    const [searchQuery, setSearchQuery] = useState('');
    const [isNewModalOpen, setIsNewModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        recipient_id: '',
        message: '',
    });

    const activeConv = useMemo(() => {
        return conversations?.find((c) => c.id === selectedConvId) || conversations?.[0];
    }, [conversations, selectedConvId]);

    const filteredConversations = useMemo(() => {
        if (!conversations) return [];
        return conversations.filter((c) => {
            // Filter by Tab
            if (activeTab === 'service' && c.type !== 'marketplace_order') return false;
            if (activeTab === 'freelancer' && c.type !== 'freelance_contract') return false;
            if (activeTab === 'client' && c.type !== 'support_ticket') return false;
            if (activeTab === 'direct' && c.type !== 'direct_message') return false;

            // Filter by search query
            if (searchQuery.trim()) {
                const otherParticipants = c.participants?.filter((p) => p.user_id !== currentUser.id);
                const title = otherParticipants?.map((p) => p.user?.name).join(' ') || '';
                if (!title.toLowerCase().includes(searchQuery.toLowerCase())) return false;
            }

            return true;
        });
    }, [conversations, activeTab, searchQuery, currentUser.id]);

    const startDirectMessage = (e) => {
        e.preventDefault();
        post(route('messages.direct.store'), {
            onSuccess: () => {
                setIsNewModalOpen(false);
                reset();
                // Switch to direct tab
                setActiveTab('direct');
            },
        });
    };

    const getChatTitle = (conv) => {
        const others = conv.participants?.filter((p) => p.user_id !== currentUser.id);
        if (!others || others.length === 0) return `Chat #${conv.id}`;
        return others.map((p) => p.user?.name).join(', ');
    };

    const getAvatarText = (conv) => {
        const title = getChatTitle(conv);
        return title ? title.substring(0, 2).toUpperCase() : 'CH';
    };

    return (
        <AuthenticatedLayout header="Messages & Communications">
            <Head title="Messages" />

            <div className="max-w-7xl mx-auto space-y-6">
                {/* Header Controls & Tabs */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                    <div className="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
                        {[
                            { id: 'all', label: 'All Messages', icon: MessageSquare },
                            { id: 'service', label: 'Service Orders', icon: ShoppingBag },
                            { id: 'freelancer', label: 'Freelancer Contracts', icon: Briefcase },
                            { id: 'client', label: 'Client / Support', icon: Shield },
                            { id: 'direct', label: 'Direct Messages', icon: User },
                        ].map((t) => {
                            const Icon = t.icon;
                            return (
                                <button
                                    key={t.id}
                                    onClick={() => setActiveTab(t.id)}
                                    className={`flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm whitespace-nowrap transition-all ${
                                        activeTab === t.id
                                            ? 'bg-indigo-600 text-white shadow-md'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    }`}
                                >
                                    <Icon className="w-4 h-4" />
                                    {t.label}
                                </button>
                            );
                        })}
                    </div>

                    <PrimaryButton onClick={() => setIsNewModalOpen(true)} className="self-start md:self-auto shadow-md">
                        <Plus className="w-4 h-4 me-2" />{__('general.new_direct_chat')}</PrimaryButton>
                </div>

                {/* Main Chat Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[720px]">
                    {/* Conversations Sidebar */}
                    <div className="lg:col-span-4 bg-white rounded-2xl border border-slate-200 flex flex-col overflow-hidden shadow-sm h-full">
                        <div className="p-4 border-b border-slate-100">
                            <div className="relative">
                                <Search className="absolute start-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input
                                    type="text"
                                    placeholder={__('general.search_conversations')}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full ps-10 pe-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-all"
                                />
                            </div>
                        </div>

                        <div className="flex-1 overflow-y-auto divide-y divide-slate-50 p-2">
                            {filteredConversations.length === 0 ? (
                                <div className="p-8 text-center text-slate-400 font-light text-sm">{__('general.no_conversations_found_in_this_tab')}</div>
                            ) : (
                                filteredConversations.map((conv) => {
                                    const isSelected = activeConv?.id === conv.id;
                                    const lastMsg = conv.messages?.[0];
                                    const title = getChatTitle(conv);

                                    return (
                                        <button
                                            key={conv.id}
                                            onClick={() => setSelectedConvId(conv.id)}
                                            className={`w-full p-3.5 rounded-xl flex items-start gap-3 transition-all text-start ${
                                                isSelected ? 'bg-indigo-50/80 border-indigo-100 shadow-sm' : 'hover:bg-slate-50/80'
                                            }`}
                                        >
                                            <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-bold flex items-center justify-center flex-shrink-0 shadow-sm">
                                                {getAvatarText(conv)}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between gap-1 mb-1">
                                                    <h4 className="font-semibold text-slate-900 text-sm truncate">{title}</h4>
                                                    {lastMsg && (
                                                        <span className="text-[10px] text-slate-400 flex-shrink-0 font-medium">
                                                            {new Date(lastMsg.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-slate-500 truncate mb-1.5">
                                                    {lastMsg ? lastMsg.body || '📷 Attachment' : 'No messages yet'}
                                                </p>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-[10px] uppercase font-semibold tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                                                        {conv.type.replace('_', ' ')}
                                                    </span>
                                                    {conv.unread_count > 0 && (
                                                        <span className="bg-rose-500 text-white font-bold text-xs px-2 py-0.5 rounded-full flex items-center justify-center shadow-sm">
                                                            {conv.unread_count}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </button>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    {/* Chat Area */}
                    <div className="lg:col-span-8 h-full flex flex-col">
                        {!activeConv ? (
                            <div className="bg-white rounded-2xl border border-slate-200 flex-1 flex flex-col items-center justify-center p-12 text-center shadow-sm">
                                <div className="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mb-4">
                                    <MessageSquare className="w-8 h-8" />
                                </div>
                                <h3 className="text-lg font-semibold text-slate-800 mb-1">{__('general.no_active_chat_selected')}</h3>
                                <p className="text-sm text-slate-500 max-w-md">{__('general.select_a_conversation_from_the_sidebar_or_initiate_a_new_direct_message_to_start_chatting')}</p>
                            </div>
                        ) : (
                            <div className="flex-1 h-full shadow-sm rounded-2xl overflow-hidden border border-slate-200 bg-white">
                                <ChatWindow
                                    conversationId={activeConv.id}
                                    participants={activeConv.participants?.map((p) => p.user) || []}
                                    readOnly={activeConv.status === 'closed'}
                                />
                            </div>
                        )}
                    </div>
                </div>

                {/* New Direct Chat Modal */}
                <Modal show={isNewModalOpen} onClose={() => setIsNewModalOpen(false)}>
                    <div className="p-6">
                        <h2 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <Send className="w-5 h-5 text-indigo-600" />{__('general.new_direct_chat')}</h2>
                        <form onSubmit={startDirectMessage} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="recipient" value="Contact Support Team" />
                                <p className="text-xs text-slate-400 mb-1">{__('general.direct_chats_can_only_be_initiated_with_support_or_admin_staff')}</p>
                                <select
                                    id="recipient"
                                    value={data.recipient_id}
                                    onChange={(e) => setData('recipient_id', e.target.value)}
                                    className="mt-1 block w-full rounded-xl border-slate-200 font-medium text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">-- Choose Support Agent --</option>
                                    {users?.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name} ({u.role})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.recipient_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="message" value="Initial Message" />
                                <textarea
                                    id="message"
                                    rows="4"
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder={__('general.type_your_first_message')}
                                    className="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError message={errors.message} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <SecondaryButton onClick={() => setIsNewModalOpen(false)}>Cancel</SecondaryButton>
                                <PrimaryButton type="submit" disabled={processing}>{__('general.start_chat')}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
