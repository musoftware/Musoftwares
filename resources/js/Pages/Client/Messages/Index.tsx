import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import {
    Briefcase,
    ExternalLink,
    Headphones,
    MessageSquare,
    Plus,
    Search,
    Send,
    ShieldAlert,
    ShoppingBag,
    UserCheck,
    X,
} from 'lucide-react';
import ChatWindow from '@/Components/Chat/ChatWindow';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface ParticipantUser {
    id: number;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

interface Participant {
    id: number;
    user_id: number;
    role?: string;
    user?: ParticipantUser;
}

interface MessageItem {
    id: number;
    body: string;
    sender_id: number;
    attachment?: string | null;
    created_at: string;
}

interface Conversation {
    id: number;
    conversable_id?: number | null;
    conversable_type?: string | null;
    type?: string;
    status?: string;
    category: 'service_orders' | 'custom_projects' | 'support_tickets' | 'direct_messages';
    category_label: string;
    title: string;
    subtitle?: string;
    target_url?: string | null;
    unread_count?: number;
    participants?: Participant[];
    messages?: MessageItem[];
    updated_at?: string;
}

interface UserOption {
    id: number;
    name: string;
    email: string;
    role?: string;
}

interface Props {
    conversations?: Conversation[];
    users?: UserOption[];
}

function relativeTime(dateStr?: string) {
    if (!dateStr) return '';
    const parsed = new Date(dateStr);
    if (Number.isNaN(parsed.getTime())) return '';
    const diff = Date.now() - parsed.getTime();
    const minute = 60 * 1000;
    const hour = 60 * minute;
    const day = 24 * hour;

    if (diff < minute) return __('general.just_now') || 'Just now';
    if (diff < hour) return `${Math.floor(diff / minute)}m`;
    if (diff < day) return `${Math.floor(diff / hour)}h`;
    if (diff < 7 * day) return `${Math.floor(diff / day)}d`;
    return parsed.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

export default function MessagesIndex({ conversations = [], users = [] }: Props) {
    const { auth } = usePage().props as unknown as { auth: { user: ParticipantUser } };
    const currentUser = auth.user;

    const [activeTab, setActiveTab] = useState<string>('all');
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [selectedConvId, setSelectedConvId] = useState<number | null>(
        conversations.length > 0 ? conversations[0].id : null,
    );
    const [isNewModalOpen, setIsNewModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        recipient_id: '',
        message: '',
    });

    // Counts per tab
    const tabCounts = useMemo(() => {
        const counts: Record<string, number> = {
            all: conversations.length,
            service_orders: 0,
            custom_projects: 0,
            support_tickets: 0,
            direct_messages: 0,
        };

        conversations.forEach((c) => {
            if (c.category && counts[c.category] !== undefined) {
                counts[c.category] += 1;
            } else {
                counts.direct_messages += 1;
            }
        });

        return counts;
    }, [conversations]);

    // Filter conversations based on Active Tab & Search Query
    const filteredConversations = useMemo(() => {
        return conversations.filter((conv) => {
            // Filter by service tab
            if (activeTab !== 'all' && conv.category !== activeTab) {
                return false;
            }

            // Filter by search query
            if (searchQuery && typeof searchQuery === 'string' && searchQuery.trim()) {
                const query = searchQuery.toLowerCase();
                const titleMatch = String(conv.title ?? '').toLowerCase().includes(query);
                const subtitleMatch = String(conv.subtitle ?? '').toLowerCase().includes(query);
                const lastMsgMatch = String(conv.messages?.[0]?.body ?? '').toLowerCase().includes(query);
                const participantMatch = (conv.participants || []).some((p) =>
                    String(p?.user?.name ?? '').toLowerCase().includes(query),
                );

                if (!titleMatch && !subtitleMatch && !lastMsgMatch && !participantMatch) {
                    return false;
                }
            }

            return true;
        });
    }, [conversations, activeTab, searchQuery]);

    // Currently active selected conversation
    const activeConv = useMemo(() => {
        if (!selectedConvId) return filteredConversations[0] || null;
        return conversations.find((c) => c.id === selectedConvId) || filteredConversations[0] || null;
    }, [conversations, filteredConversations, selectedConvId]);

    const startDirectMessage = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('messages.direct.store'), {
            onSuccess: () => {
                setIsNewModalOpen(false);
                reset();
                setActiveTab('direct_messages');
            },
        });
    };

    const getBadgeStyle = (category: string) => {
        switch (category) {
            case 'service_orders':
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'custom_projects':
                return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'support_tickets':
                return 'bg-rose-50 text-rose-700 border-rose-200';
            default:
                return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        }
    };

    const tabsConfig = [
        { id: 'all', label: __('general.all_messages') || 'All Messages', icon: MessageSquare },
        { id: 'service_orders', label: __('general.service_orders') || 'Service Orders', icon: ShoppingBag },
        { id: 'custom_projects', label: __('general.custom_projects') || 'Custom Projects', icon: Briefcase },
        { id: 'support_tickets', label: __('general.support_tickets') || 'Support Tickets', icon: ShieldAlert },
        { id: 'direct_messages', label: __('general.direct_messages') || 'Direct Messages', icon: UserCheck },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={__('general.messages') || 'Messages & Support'} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                {/* Top Header & Tabs Bar */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="flex items-center gap-2.5 text-2xl font-bold text-slate-900">
                                <Headphones className="h-7 w-7 text-indigo-600" />
                                {__('general.messages') || 'Messages & Communications'}
                            </h1>
                            <p className="mt-0.5 text-xs text-slate-500">
                                {__('general.messages_intro') || 'All service chats, orders, custom projects, and support requests in one place.'}
                            </p>
                        </div>

                        <PrimaryButton
                            onClick={() => setIsNewModalOpen(true)}
                            className="inline-flex items-center gap-2 self-start rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 md:self-auto"
                        >
                            <Plus className="h-4 w-4" />
                            {__('general.new_direct_chat') || 'New Direct Message'}
                        </PrimaryButton>
                    </div>

                    {/* Service Tabs */}
                    <div className="mt-5 flex items-center gap-2 overflow-x-auto border-t border-slate-100 pt-4 scrollbar-none">
                        {tabsConfig.map((t) => {
                            const Icon = t.icon;
                            const count = tabCounts[t.id] ?? 0;
                            const isActive = activeTab === t.id;

                            return (
                                <button
                                    key={t.id}
                                    type="button"
                                    onClick={() => setActiveTab(t.id)}
                                    className={cn(
                                        'flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-semibold transition-all',
                                        isActive
                                            ? 'bg-slate-900 text-white shadow-sm'
                                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                    )}
                                >
                                    <Icon className="h-3.5 w-3.5" />
                                    <span>{t.label}</span>
                                    <span
                                        className={cn(
                                            'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                            isActive
                                                ? 'bg-white/20 text-white'
                                                : 'bg-slate-200 text-slate-700',
                                        )}
                                    >
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Main 2-Column Chat Grid */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:h-[720px]">
                    {/* Conversations Sidebar (4 Columns) */}
                    <div className="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-4">
                        {/* Search Input */}
                        <div className="border-b border-slate-100 p-3.5">
                            <div className="relative">
                                <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="text"
                                    placeholder={__('general.search_conversations') || 'Search conversations...'}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pe-3 ps-9 text-xs transition-all focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                />
                                {searchQuery && (
                                    <button
                                        type="button"
                                        onClick={() => setSearchQuery('')}
                                        className="absolute end-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Conversations List */}
                        <div className="flex-1 divide-y divide-slate-100 overflow-y-auto p-2">
                            {filteredConversations.length === 0 ? (
                                <div className="p-8 text-center">
                                    <MessageSquare className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                                    <p className="text-xs font-semibold text-slate-500">
                                        {__('general.no_conversations') || 'No conversations found'}
                                    </p>
                                    <p className="mt-1 text-[11px] text-slate-400">
                                        {__('general.no_conversations_tab_desc') || 'There are no active conversations under this service tab.'}
                                    </p>
                                </div>
                            ) : (
                                filteredConversations.map((conv) => {
                                    const isSelected = activeConv?.id === conv.id;
                                    const lastMsg = conv.messages?.[0];

                                    return (
                                        <button
                                            key={conv.id}
                                            type="button"
                                            onClick={() => setSelectedConvId(conv.id)}
                                            className={cn(
                                                'flex w-full items-start gap-3 rounded-xl p-3 text-start transition-all',
                                                isSelected
                                                    ? 'border border-indigo-100 bg-indigo-50/70 shadow-sm'
                                                    : 'hover:bg-slate-50/80',
                                            )}
                                        >
                                            {/* Avatar / Letter */}
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-xs font-bold text-white shadow-sm">
                                                {(conv.title || '?').substring(0, 2).toUpperCase()}
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <div className="mb-0.5 flex items-center justify-between gap-1">
                                                    <h4 className="truncate text-xs font-bold text-slate-900">
                                                        {conv.title}
                                                    </h4>
                                                    {lastMsg && (
                                                        <span className="shrink-0 text-[10px] font-medium text-slate-400">
                                                            {relativeTime(lastMsg.created_at)}
                                                        </span>
                                                    )}
                                                </div>

                                                <p className="mb-1.5 truncate text-[11px] text-slate-500">
                                                    {lastMsg?.body || __('general.no_messages_yet') || 'No messages yet'}
                                                </p>

                                                <div className="flex items-center justify-between gap-1">
                                                    <span
                                                        className={cn(
                                                            'rounded-md border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                            getBadgeStyle(conv.category),
                                                        )}
                                                    >
                                                        {conv.category_label}
                                                    </span>

                                                    {!!conv.unread_count && conv.unread_count > 0 && (
                                                        <span className="flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-600 px-1.5 text-[9px] font-bold text-white shadow-sm">
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

                    {/* Chat Area (8 Columns) */}
                    <div className="flex h-full flex-col lg:col-span-8">
                        {!activeConv ? (
                            <div className="flex flex-1 flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                    <MessageSquare className="h-8 w-8" />
                                </div>
                                <h3 className="text-base font-bold text-slate-800">
                                    {__('general.pick_a_conversation') || 'Select a conversation'}
                                </h3>
                                <p className="mt-1 max-w-sm text-xs text-slate-500">
                                    {__('general.select_a_conversation_desc') ||
                                        'Choose a conversation from the sidebar or click "New Direct Message" to contact support.'}
                                </p>
                            </div>
                        ) : (
                            <div className="flex h-full flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                {/* Chat Header */}
                                <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <h2 className="truncate text-sm font-bold text-slate-900">
                                                {activeConv.title}
                                            </h2>
                                            <span
                                                className={cn(
                                                    'rounded-md border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                    getBadgeStyle(activeConv.category),
                                                )}
                                            >
                                                {activeConv.category_label}
                                            </span>
                                        </div>
                                        {activeConv.subtitle && (
                                            <p className="mt-0.5 truncate text-[11px] text-slate-500">
                                                {activeConv.subtitle}
                                            </p>
                                        )}
                                    </div>

                                    {/* Action link to Service Order / Ticket if present */}
                                    {activeConv.target_url && (
                                        <a
                                            href={activeConv.target_url}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                                        >
                                            <span>{__('general.view_details') || 'View Details'}</span>
                                            <ExternalLink className="h-3.5 w-3.5 text-slate-400" />
                                        </a>
                                    )}
                                </div>

                                {/* Chat Window Component */}
                                <div className="flex-1 overflow-hidden">
                                    <ChatWindow
                                        conversationId={activeConv.id}
                                        participants={(activeConv.participants?.map((p) => p.user).filter(Boolean) || []) as any}
                                        readOnly={activeConv.status === 'closed'}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* New Direct Chat Modal */}
                <Modal show={isNewModalOpen} onClose={() => setIsNewModalOpen(false)}>
                    <div className="p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-lg font-bold text-slate-900">
                                <Send className="h-5 w-5 text-indigo-600" />
                                {__('general.new_direct_chat') || 'New Direct Message'}
                            </h2>
                            <button
                                type="button"
                                onClick={() => setIsNewModalOpen(false)}
                                className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        <form onSubmit={startDirectMessage} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="recipient" value={__('general.select_support_recipient') || 'Contact Support / Admin Team'} />
                                <p className="mb-1.5 text-xs text-slate-500">
                                    {__('general.direct_chats_can_only_be_initiated_with_support_or_admin_staff') ||
                                        'Direct chats can only be sent to support or administration staff.'}
                                </p>
                                <select
                                    id="recipient"
                                    value={data.recipient_id}
                                    onChange={(e) => setData('recipient_id', e.target.value)}
                                    className="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">
                                        -- {__('general.choose_a_recipient') || 'Choose Support Agent'} --
                                    </option>
                                    {users.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name} ({u.role || 'Support'})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.recipient_id} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="message" value={__('general.message') || 'Initial Message'} />
                                <textarea
                                    id="message"
                                    rows={4}
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder={__('general.type_your_first_message') || 'Type your message here...'}
                                    className="mt-1 block w-full rounded-xl border border-slate-200 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError message={errors.message} className="mt-1" />
                            </div>

                            <div className="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                <SecondaryButton type="button" onClick={() => setIsNewModalOpen(false)}>
                                    {__('general.cancel') || 'Cancel'}
                                </SecondaryButton>
                                <PrimaryButton
                                    type="submit"
                                    disabled={processing || !data.recipient_id || !data.message.trim()}
                                    className="bg-indigo-600 text-white hover:bg-indigo-700"
                                >
                                    {__('general.start_chat') || 'Start Chat'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
