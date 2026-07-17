import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Plus, Send, Users } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/card';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Conversation {
    id: number;
    conversable_id: number;
    conversable_type: string;
    type?: string;
    status?: string;
    unread_count?: number;
    participants?: { id: number; user_id: number; user?: { id: number; name: string; avatar_url?: string | null } }[];
    messages?: { id: number; body: string; sender_id: number; created_at: string }[];
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

function relativeFrom(date?: string) {
    if (!date) return '';
    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) return '';
    const diff = Date.now() - parsed.getTime();
    const day = 24 * 60 * 60 * 1000;
    if (diff < 60_000) return __('general.just_now') || 'Just now';
    if (diff < 3_600_000) return `${Math.floor(diff / 60_000)}m`;
    if (diff < day) return `${Math.floor(diff / 3_600_000)}h`;
    if (diff < 7 * day) return `${Math.floor(diff / day)}d`;
    return parsed.toLocaleDateString();
}

export default function MessagesIndex({ conversations = [], users = [] }: Props) {
    const initialSelectedId = conversations[0]?.id ?? null;
    const [selectedId, setSelectedId] = useState<number | null>(initialSelectedId);
    const selected = useMemo(
        () => conversations.find((c) => c.id === selectedId) ?? null,
        [conversations, selectedId],
    );

    const sendForm = useForm({ recipient_id: '', message: '' });
    const submitNew = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        sendForm.post(route('messages.direct.store'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.messages') || 'Messages'} />
            <div className="mx-auto w-full max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <header className="space-y-1">
                    <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight text-slate-900">
                        <Users className="icon-md text-slate-400" aria-hidden="true" />
                        {__('general.messages') || 'Messages'}
                    </h1>
                    <p className="text-sm text-slate-500">
                        {__('general.messages_intro') || 'Talk to the support and admin team here.'}
                    </p>
                </header>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
                    <Card className="rounded-xl border border-slate-200">
                        <CardContent className="p-0">
                            <div className="border-b border-slate-100 px-4 py-3">
                                <h2 className="text-sm font-semibold text-slate-700">
                                    {__('general.conversations') || 'Conversations'}
                                </h2>
                            </div>
                            {conversations.length === 0 ? (
                                <div className="p-4">
                                    <EmptyState
                                        icon={Users}
                                        tone="friendly"
                                        title={__('general.no_conversations') || 'No conversations yet'}
                                        description={
                                            __('general.start_a_conversation_desc') ||
                                            'Start one below — say hi to the support team.'
                                        }
                                    />
                                </div>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {conversations.map((conversation) => {
                                        const lastMessage = conversation.messages?.[0];
                                        return (
                                            <li key={conversation.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedId(conversation.id)}
                                                    className={cn(
                                                        'flex w-full items-start gap-3 px-4 py-3 text-start transition-colors hover:bg-slate-50',
                                                        selectedId === conversation.id && 'bg-slate-50',
                                                    )}
                                                >
                                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                                        {(conversation.participants?.[0]?.user?.name || '?').slice(0, 1).toUpperCase()}
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-semibold text-slate-900">
                                                            {conversation.participants?.[0]?.user?.name ||
                                                                __('general.unknown_user')}
                                                        </p>
                                                        <p className="truncate text-xs text-slate-500">
                                                            {lastMessage?.body || __('general.no_messages_yet')}
                                                        </p>
                                                    </div>
                                                    {!!conversation.unread_count && (
                                                        <span className="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white">
                                                            {conversation.unread_count}
                                                        </span>
                                                    )}
                                                </button>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border border-slate-200">
                        <CardContent className="flex h-[60vh] flex-col p-0">
                            {selected ? (
                                <>
                                    <div className="border-b border-slate-100 px-5 py-4">
                                        <p className="font-semibold text-slate-900">
                                            {selected.participants?.[0]?.user?.name || __('general.unknown_user')}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {__('general.last_message_ago', {
                                                when: relativeFrom(selected.messages?.[0]?.created_at),
                                            }) || `Last activity ${relativeFrom(selected.messages?.[0]?.created_at)}`}
                                        </p>
                                    </div>
                                    <div className="flex-1 space-y-4 overflow-y-auto p-5">
                                        {(selected.messages ?? []).map((message) => (
                                            <div
                                                key={message.id}
                                                className={cn(
                                                    'max-w-[80%] rounded-2xl border px-4 py-2 text-sm shadow-sm',
                                                    message.sender_id === selected.conversable_id
                                                        ? 'ml-auto rounded-br-sm bg-slate-900 text-white'
                                                        : 'rounded-bl-sm border-slate-200 bg-white text-slate-800',
                                                )}
                                            >
                                                <p className="whitespace-pre-wrap">{message.body}</p>
                                                <p
                                                    className={cn(
                                                        'mt-1 text-[10px]',
                                                        message.sender_id === selected.conversable_id
                                                            ? 'text-slate-300'
                                                            : 'text-slate-400',
                                                    )}
                                                >
                                                    {relativeFrom(message.created_at)}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                    <form
                                        onSubmit={submitNew}
                                        className="flex items-center gap-2 border-t border-slate-100 bg-slate-50/40 px-5 py-4"
                                    >
                                        <Input
                                            type="hidden"
                                            value={selected.participants?.[0]?.user_id ?? ''}
                                            name="recipient_id"
                                        />
                                        <Input
                                            type="text"
                                            value={sendForm.data.message}
                                            onChange={(e) => sendForm.setData('message', e.target.value)}
                                            placeholder={__('general.type_a_message') || 'Type a message...'}
                                            className="flex-1"
                                        />
                                        <Button
                                            type="submit"
                                            disabled={!sendForm.data.message.trim() || sendForm.processing}
                                            className="rounded-lg bg-slate-900 text-white hover:bg-slate-800"
                                        >
                                            <Send className="me-1.5 h-4 w-4" />
                                            {__('general.send') || 'Send'}
                                        </Button>
                                    </form>
                                </>
                            ) : (
                                <div className="flex flex-1 items-center justify-center p-8">
                                    <EmptyState
                                        icon={Users}
                                        tone="friendly"
                                        title={__('general.pick_a_conversation') || 'Pick a conversation'}
                                        description={
                                            __('general.choose_or_start') ||
                                            'Choose a conversation on the left or start a new one below.'
                                        }
                                    />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="rounded-xl border border-slate-200">
                    <CardContent className="space-y-4 p-5">
                        <h2 className="text-sm font-semibold text-slate-700">
                            {__('general.start_a_new_conversation') || 'Start a new conversation'}
                        </h2>
                        <form
                            onSubmit={submitNew}
                            className="flex flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <select
                                value={sendForm.data.recipient_id}
                                onChange={(e) => sendForm.setData('recipient_id', e.target.value)}
                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 sm:w-72"
                                required
                            >
                                <option value="">
                                    {__('general.choose_a_recipient') || 'Choose a recipient'}
                                </option>
                                {users.map((u) => (
                                    <option key={u.id} value={String(u.id)}>
                                        {u.name} {u.role ? `(${u.role})` : ''}
                                    </option>
                                ))}
                            </select>
                            <Input
                                type="text"
                                value={sendForm.data.message}
                                onChange={(e) => sendForm.setData('message', e.target.value)}
                                placeholder={__('general.type_a_message') || 'Type a message...'}
                                className="flex-1"
                            />
                            <Button
                                type="submit"
                                disabled={
                                    !sendForm.data.recipient_id ||
                                    !sendForm.data.message.trim() ||
                                    sendForm.processing
                                }
                                className="rounded-lg bg-slate-900 text-white hover:bg-slate-800"
                            >
                                <Send className="me-1.5 h-4 w-4" />
                                {__('general.send') || 'Send'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

export { Check, Plus };
