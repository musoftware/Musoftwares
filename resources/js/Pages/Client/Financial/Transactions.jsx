import React from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowDownLeft, ArrowUpRight, Plus } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import Pagination from '@/Components/Pagination';
import { cn, formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

/** Wallet balance at the top, then every movement as one row: what, when, how much. */
export default function Transactions({ transactions, wallet }) {
    const { locale } = usePage().props;
    const currency = wallet?.currency;
    const rows = transactions?.data ?? [];
    const formatDate = (value) =>
        value ? new Date(value).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    return (
        <ClientPage
            title={__('client_money.tx.title')}
            hint={__('client_money.tx.hint')}
            action={<PrimaryAction href="/financial/add-balance" icon={<Plus className="h-4 w-4" />}>{__('client_money.add_balance')}</PrimaryAction>}
        >
            <Card>
                <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_money.wallet_balance')}</p>
                <p className="mt-1 text-3xl font-bold tracking-tight" dir="ltr">{formatMoney(wallet?.balance || 0, currency)}</p>
                <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-black/5 pt-4 text-sm dark:border-white/10">
                    <span className="text-[#1d1d1f]/70 dark:text-zinc-300">
                        {__('client_money.tx.withdrawable')}: <b dir="ltr">{formatMoney(wallet?.earned_balance || 0, currency)}</b>
                    </span>
                    {Number(wallet?.locked_balance || 0) > 0 && (
                        <span className="text-[#1d1d1f]/70 dark:text-zinc-300">
                            {__('client_money.tx.on_hold')}: <b dir="ltr">{formatMoney(wallet.locked_balance, currency)}</b>
                        </span>
                    )}
                    <span className="ms-auto"><TextAction href="/financial/withdrawals">{__('client_money.tx.withdraw')}</TextAction></span>
                </div>
            </Card>

            <Section>
                {rows.length === 0 ? (
                    <EmptyNote>{__('client_money.tx.empty')}</EmptyNote>
                ) : (
                    <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                        {rows.map((tx) => {
                            const added = tx.type === 'credit';
                            const Icon = added ? ArrowDownLeft : ArrowUpRight;
                            return (
                                <li key={tx.id} className="flex items-center gap-3 px-4 py-3">
                                    <span className={cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-full', added ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40')}>
                                        <Icon className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-[15px] font-medium">{tx.description || __('client_money.tx.system')}</p>
                                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                                            {added ? __('client_money.tx.added') : __('client_money.tx.taken')} · {formatDate(tx.created_at)} · {__('client_money.tx.balance_after', { amount: formatMoney(tx.balance_after || 0, currency) })}
                                        </p>
                                    </div>
                                    <span className={cn('shrink-0 text-sm font-semibold', added ? 'text-emerald-700 dark:text-emerald-400' : 'text-[#1d1d1f] dark:text-zinc-200')} dir="ltr">
                                        {added ? '+' : '−'}{formatMoney(tx.amount || 0, currency)}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}
                {transactions?.links?.length > 3 && <div className="mt-4"><Pagination links={transactions.links} /></div>}
            </Section>
        </ClientPage>
    );
}
