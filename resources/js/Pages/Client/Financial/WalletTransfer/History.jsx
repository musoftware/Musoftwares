import React from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowDownLeft, ArrowUpRight, Send } from 'lucide-react';
import ClientPage, { EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import SafeLink from '@/Components/SafeLink';
import Pagination from '@/Components/Pagination';
import { cn, formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

/** Transfers sent and received, one row each, with a link to the receipt. */
export default function History({ transfers }) {
    const { locale } = usePage().props;
    const rows = transfers?.data ?? [];
    const formatDate = (value) =>
        value ? new Date(value).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
    const statusLabel = (status) => {
        const key = `client_money.transfers.status.${status}`;
        return __(key) === key ? status : __(key);
    };

    return (
        <ClientPage
            title={__('client_money.transfers.title')}
            hint={__('client_money.transfers.hint')}
            action={<PrimaryAction href={route('financial.transfer.create')} icon={<Send className="h-4 w-4" />}>{__('client_money.transfers.send')}</PrimaryAction>}
        >
            <Section>
                {rows.length === 0 ? (
                    <EmptyNote action={<TextAction href={route('financial.transfer.create')}>{__('client_money.transfers.send')}</TextAction>}>
                        {__('client_money.transfers.empty')}
                    </EmptyNote>
                ) : (
                    <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                        {rows.map((tx) => {
                            const sent = tx.type === 'sent';
                            const Icon = sent ? ArrowUpRight : ArrowDownLeft;
                            const shownAmount = sent ? tx.amount : (tx.converted_amount || tx.amount);
                            const shownCurrency = sent ? tx.currency : (tx.converted_currency || tx.currency);
                            return (
                                <li key={tx.id}>
                                    <SafeLink href={route('financial.transfer.show', tx.id)} className="flex items-center gap-3 px-4 py-3 hover:bg-[#f5f5f7] dark:hover:bg-zinc-800">
                                        <span className={cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-full', sent ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40')}>
                                            <Icon className="h-4 w-4" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-[15px] font-medium">
                                                {sent ? __('client_money.transfers.sent_to', { name: tx.other_party }) : __('client_money.transfers.from', { name: tx.other_party })}
                                            </p>
                                            <p className="truncate text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                                                {statusLabel(tx.status)} · {formatDate(tx.created_at)}
                                                {sent && Number(tx.fee) > 0 && ` · ${__('client_money.transfers.fee', { amount: formatMoney(tx.fee, tx.currency) })}`}
                                                {tx.reason && ` · ${tx.reason}`}
                                            </p>
                                        </div>
                                        <span className={cn('shrink-0 text-sm font-semibold', sent ? '' : 'text-emerald-700 dark:text-emerald-400')} dir="ltr">
                                            {sent ? '−' : '+'}{formatMoney(shownAmount, shownCurrency)}
                                        </span>
                                    </SafeLink>
                                </li>
                            );
                        })}
                    </ul>
                )}
                {transfers?.links?.length > 3 && <div className="mt-4"><Pagination links={transfers.links} /></div>}
            </Section>
        </ClientPage>
    );
}
