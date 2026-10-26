import React from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { ArrowUpRight, ShieldCheck, Wallet } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import Pagination from '@/Components/Pagination';
import { cn, formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const STATUS_STYLE = {
    pending: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
    approved: 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400',
    paid: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
    rejected: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400',
};

const methodLabel = (pm) => pm?.details?.bank_name || pm?.details?.instapay_username || pm?.details?.mobile_number || pm?.details?.paypal_email || __('client_money.withdraw.account');

/** What can be withdrawn, the request form (once identity and a payout method are ready), then past requests. */
export default function Withdrawals({ auth, withdrawals, payoutMethods = [], wallet }) {
    const { locale } = usePage().props;
    const available = Number(wallet?.earned_balance || 0);
    const currency = wallet?.currency;
    const rows = withdrawals?.data ?? [];
    const { data, setData, post, processing, errors, reset } = useForm({ amount: '', payout_method_id: payoutMethods?.[0]?.id || '' });
    const verified = !!auth?.user?.kyc_verified;
    const canRequest = verified && payoutMethods.length > 0 && available > 0;
    const formatDate = (value) =>
        value ? new Date(value).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    const submit = (e) => {
        e.preventDefault();
        post(route('financial.withdrawals.store'), { onSuccess: () => reset('amount') });
    };

    return (
        <ClientPage
            title={__('client_money.withdraw.title')}
            hint={__('client_money.withdraw.hint')}
            action={<TextAction href={route('financial.payout-methods.index')}>{__('client_money.withdraw.methods')}</TextAction>}
        >
            <Card>
                <p className="flex items-center gap-2 text-sm text-[#1d1d1f]/60 dark:text-zinc-400"><Wallet className="h-4 w-4 text-emerald-600" />{__('client_money.withdraw.available')}</p>
                <p className="mt-1 text-3xl font-bold tracking-tight" dir="ltr">{formatMoney(available, currency)}</p>

                {!verified ? (
                    <div className="mt-4"><PrimaryAction href={route('kyc.index')} icon={<ShieldCheck className="h-4 w-4" />}>{__('client_money.withdraw.verify_first')}</PrimaryAction></div>
                ) : payoutMethods.length === 0 ? (
                    <div className="mt-4"><PrimaryAction href={route('financial.payout-methods.index')}>{__('client_money.withdraw.method_first')}</PrimaryAction></div>
                ) : canRequest ? (
                    <form onSubmit={submit} className="mt-5 grid grid-cols-1 gap-4 border-t border-black/5 pt-5 dark:border-white/10 sm:grid-cols-2">
                        <div>
                            <label htmlFor="amount" className="mb-1.5 block text-sm font-semibold">{__('client_money.withdraw.amount')}</label>
                            <div className="flex gap-2">
                                <input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max={available}
                                    inputMode="decimal"
                                    dir="ltr"
                                    value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)}
                                    className="h-11 w-full rounded-xl border border-black/10 bg-white px-3 text-base focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900"
                                    required
                                />
                                <button type="button" onClick={() => setData('amount', available.toString())} className="shrink-0 rounded-xl bg-[#f5f5f7] px-4 text-sm font-semibold hover:bg-black/5 dark:bg-zinc-800">
                                    {__('client_money.withdraw.max')}
                                </button>
                            </div>
                            {errors.amount && <p className="mt-1 text-sm font-medium text-rose-600">{errors.amount}</p>}
                        </div>
                        <div>
                            <label htmlFor="payout_method_id" className="mb-1.5 block text-sm font-semibold">{__('client_money.withdraw.send_to')}</label>
                            <select
                                id="payout_method_id"
                                value={data.payout_method_id}
                                onChange={(e) => setData('payout_method_id', e.target.value)}
                                className="h-11 w-full rounded-xl border border-black/10 bg-white px-3 text-sm focus:border-[#0071e3] focus:outline-none dark:border-white/10 dark:bg-zinc-900"
                                required
                            >
                                {payoutMethods.map((pm) => (
                                    <option key={pm.id} value={pm.id}>{__(`client.payout_type_${pm.type}`)} — {methodLabel(pm)}</option>
                                ))}
                            </select>
                            {errors.payout_method_id && <p className="mt-1 text-sm font-medium text-rose-600">{errors.payout_method_id}</p>}
                        </div>
                        <div className="sm:col-span-2">
                            <button type="submit" disabled={processing || !data.amount} className="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                                <ArrowUpRight className="h-4 w-4" />{__('client_money.withdraw.request')}
                            </button>
                        </div>
                    </form>
                ) : null}
            </Card>

            <Section title={__('client_money.withdraw.requests')}>
                {rows.length === 0 ? (
                    <EmptyNote>{__('client_money.withdraw.empty')}</EmptyNote>
                ) : (
                    <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                        {rows.map((w) => (
                            <li key={w.id} className="flex items-center gap-3 px-4 py-3">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[15px] font-medium">
                                        {w.payout_method ? __(`client.payout_type_${w.payout_method.type}`) : __('client_money.withdraw.standard')}
                                    </p>
                                    <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{formatDate(w.created_at)}</p>
                                </div>
                                <span className={cn('shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold', STATUS_STYLE[w.status] || 'bg-[#f5f5f7] text-[#1d1d1f]/70')}>
                                    {__(`client_money.withdraw.status.${w.status}`) === `client_money.withdraw.status.${w.status}` ? w.status : __(`client_money.withdraw.status.${w.status}`)}
                                </span>
                                <span className="shrink-0 text-sm font-semibold" dir="ltr">{formatMoney(w.amount, w.currency || currency)}</span>
                            </li>
                        ))}
                    </ul>
                )}
                {withdrawals?.links?.length > 3 && <div className="mt-4"><Pagination links={withdrawals.links} /></div>}
            </Section>
        </ClientPage>
    );
}
