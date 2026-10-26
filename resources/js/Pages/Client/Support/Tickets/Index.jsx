import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { ChevronRight, LifeBuoy, Plus } from 'lucide-react';
import ClientPage, { EmptyNote, PrimaryAction } from '@/Components/Client/ClientPage';
import SafeLink from '@/Components/SafeLink';
import Pagination from '@/Components/Pagination';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * Ticket status as the person looking at it reads it. For a client, agent_replied means the
 * ball is in their court ("waiting for you"); for staff, user_replied means a reply is due.
 */
export function ticketState(status, isAdmin = false) {
    const s = status || 'open';
    if (s === 'closed' || s === 'resolved') return 'closed';
    if (s === 'agent_replied') return isAdmin ? 'waiting_client' : 'waiting_you';
    if (s === 'user_replied') return isAdmin ? 'needs_reply' : 'waiting_us';
    return 'open';
}

export const STATE_STYLE = {
    open: 'bg-[#0071e3]/10 text-[#0071e3]',
    waiting_you: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
    needs_reply: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
    waiting_us: 'bg-[#0071e3]/10 text-[#0071e3]',
    waiting_client: 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-300',
    closed: 'bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-400',
};

export default function TicketsIndex({ tickets, isAdmin }) {
    const { locale } = usePage().props;
    const [filterState, setFilterState] = useState('');
    const [filterPriority, setFilterPriority] = useState('');
    const date = (d) => (d ? new Date(d).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '');

    const rows = (tickets?.data ?? []).filter((t) => {
        if (filterState && ticketState(t.ticket_status || t.status, isAdmin) !== filterState) return false;
        if (filterPriority && t.priority?.toLowerCase() !== filterPriority) return false;
        return true;
    });

    return (
        <ClientPage
            title={__('client_account.tickets.title')}
            hint={isAdmin ? __('client_account.tickets.hint_admin') : __('client_account.tickets.hint')}
            width="md"
            action={!isAdmin ? (
                <PrimaryAction href={route('tickets.create')} icon={<Plus className="h-4 w-4" />}>{__('client_account.tickets.new')}</PrimaryAction>
            ) : undefined}
        >
            {isAdmin && (
                <div className="flex flex-wrap gap-3">
                    <select value={filterState} onChange={(e) => setFilterState(e.target.value)} aria-label={__('client_account.tickets.status')}
                        className="h-10 rounded-xl border-black/10 text-sm dark:bg-zinc-900">
                        <option value="">{__('client_account.tickets.all')}</option>
                        {['open', 'needs_reply', 'waiting_client', 'closed'].map((s) => <option key={s} value={s}>{__(`client_account.tickets.state.${s}`)}</option>)}
                    </select>
                    <select value={filterPriority} onChange={(e) => setFilterPriority(e.target.value)} aria-label={__('client_account.tickets.priority')}
                        className="h-10 rounded-xl border-black/10 text-sm dark:bg-zinc-900">
                        <option value="">{__('client_account.tickets.all_priorities')}</option>
                        {['high', 'medium', 'low'].map((p) => <option key={p} value={p}>{__(`client_account.tickets.priority_${p}`)}</option>)}
                    </select>
                </div>
            )}

            {rows.length === 0 ? (
                <EmptyNote
                    icon={<LifeBuoy className="h-5 w-5" />}
                    action={!isAdmin ? <SafeLink href={route('tickets.create')} className="text-sm font-semibold text-[#0071e3]">{__('client_account.tickets.new')}</SafeLink> : null}
                >
                    {__('client_account.tickets.empty')}
                </EmptyNote>
            ) : (
                <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                    {rows.map((t) => {
                        const state = ticketState(t.ticket_status || t.status, isAdmin);
                        return (
                            <li key={t.id}>
                                <SafeLink href={route('tickets.show', t.id)} className="flex items-center gap-3 px-4 py-3.5 hover:bg-[#f5f5f7] dark:hover:bg-zinc-800">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-[15px] font-semibold">
                                            <span className="font-normal text-[#1d1d1f]/50 dark:text-zinc-500" dir="ltr">#{t.id} </span>
                                            {t.ticket_subject || t.subject || t.title}
                                        </p>
                                        <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                                            <span className={cn('rounded-full px-2 py-0.5 font-semibold', STATE_STYLE[state])}>{__(`client_account.tickets.state.${state}`)}</span>
                                            {isAdmin && t.user?.name && <span>{t.user.name}</span>}
                                            {t.priority?.toLowerCase() === 'high' && <span className="font-semibold text-rose-600">{__('client_account.tickets.priority_high')}</span>}
                                            <span>{__('client_account.tickets.updated', { date: date(t.updated_at) })}</span>
                                        </p>
                                    </div>
                                    <ChevronRight className="h-4 w-4 shrink-0 text-[#1d1d1f]/30 rtl:rotate-180" />
                                </SafeLink>
                            </li>
                        );
                    })}
                </ul>
            )}
            <Pagination links={tickets?.links} />
        </ClientPage>
    );
}
