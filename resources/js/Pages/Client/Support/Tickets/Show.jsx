import React from 'react';
import { router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, FolderKanban } from 'lucide-react';
import ClientPage, { Card } from '@/Components/Client/ClientPage';
import SafeLink from '@/Components/SafeLink';
import ChatWindow from '@/Components/Chat/ChatWindow';
import { ticketState, STATE_STYLE } from './Index';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** One ticket read like a conversation: what it is about and its state on top, the chat below. */
export default function Show({ ticket, isAdmin }) {
    const { locale } = usePage().props;
    const status = ticket.ticket_status || ticket.status || 'open';
    const state = ticketState(status, isAdmin);
    const isClosed = state === 'closed';
    const hasQuotation = ticket.price !== null && ticket.price !== undefined && Number(ticket.price) > 0;
    const date = (d) => (d ? new Date(d).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '');

    const markResolved = () => router.post(route('tickets.resolve', ticket.id), {}, { preserveScroll: true });

    return (
        <ClientPage
            title={ticket.ticket_subject || ticket.subject || ticket.title || __('client_account.tickets.ticket', { id: ticket.id })}
            width="md"
            action={isAdmin && !isClosed ? (
                <button type="button" onClick={markResolved}
                    className="inline-flex h-11 items-center gap-2 rounded-full bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700">
                    <CheckCircle2 className="h-4 w-4" />{__('client_account.tickets.resolve')}
                </button>
            ) : undefined}
        >
            <div className="-mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                <SafeLink href={route('tickets.index')} className="inline-flex items-center gap-1 font-semibold text-[#0071e3]">
                    <ArrowLeft className="h-4 w-4 rtl:rotate-180" />{__('client_account.tickets.back')}
                </SafeLink>
                <span dir="ltr">#{ticket.id}</span>
                <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-semibold', STATE_STYLE[state])}>{__(`client_account.tickets.state.${state}`)}</span>
                {isAdmin && ticket.user?.name && <span>{ticket.user.name}</span>}
                {ticket.project && (
                    <SafeLink href={`/client/projects/${ticket.project.id}`} className="inline-flex items-center gap-1 text-[#0071e3] hover:underline">
                        <FolderKanban className="h-4 w-4" />{ticket.project.name}
                    </SafeLink>
                )}
                <span>{__('client_account.tickets.opened', { date: date(ticket.created_at) })}</span>
            </div>

            {state === 'waiting_you' && (
                <p className="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">{__('client_account.tickets.waiting_you_hint')}</p>
            )}

            {hasQuotation && (
                <Card className="border-emerald-200 dark:border-emerald-900/50">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">{__('client_account.tickets.quote_title')}</h2>
                            <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_account.tickets.quote_hint')}</p>
                        </div>
                        <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400" dir="ltr">
                            {Number(ticket.price).toLocaleString('en-US')} {ticket.currency?.symbol || ticket.currency_symbol || '$'}
                        </p>
                    </div>
                    {ticket.pricing_notes && (
                        <p className="mt-3 whitespace-pre-wrap rounded-xl bg-[#f5f5f7] p-3 text-sm dark:bg-zinc-800" dir="auto">{ticket.pricing_notes}</p>
                    )}
                </Card>
            )}

            <div className="flex h-[calc(100dvh-260px)] min-h-[480px] flex-col overflow-hidden rounded-[20px] border border-black/5 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <ChatWindow
                    conversationId={ticket.conversation?.id}
                    participants={[{ id: ticket.user_id, name: ticket.user?.name }]}
                    readOnly={isClosed}
                    showHeader={false}
                    className="h-full rounded-none border-0 shadow-none"
                />
            </div>
            {isClosed && <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_account.tickets.closed_hint')}</p>}
        </ClientPage>
    );
}
