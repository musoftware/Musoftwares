import { usePage } from '@inertiajs/react';
import { Gift, Plus, Ticket } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import { __ } from '@/lib/i18n';
import { formatMoney } from '@/lib/utils';

interface Voucher {
    id: number;
    name: string;
    description: string | null;
    spend_amount_user_currency: number;
    reward_amount_user_currency: number;
    expires_at: string | null;
    max_uses_per_user: number | null;
}

interface Redemption {
    id: number;
    spent_amount: number;
    reward_amount: number;
    created_at: string;
    voucher: { name: string } | null;
}

/** Top-up offers: add X to the wallet, get Y extra. One card per offer, then what was used. */
export default function Index({ vouchers = [], redemptions }: { vouchers: Voucher[]; redemptions?: { data: Redemption[] } }) {
    const { auth, locale } = usePage().props as any;
    const currency = auth?.user?.currency;
    const formatDate = (date: string | null) =>
        date ? new Date(date.length === 10 ? date + 'T00:00:00' : date).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '';
    const used = redemptions?.data ?? [];

    return (
        <ClientPage
            title={__('client_rewards.vouchers.title')}
            hint={__('client_rewards.vouchers.hint')}
            action={<PrimaryAction href={route('financial.add-balance')} icon={<Plus className="h-4 w-4" />}>{__('client_rewards.vouchers.add_balance')}</PrimaryAction>}
        >
            <Section>
                {vouchers.length === 0 ? (
                    <EmptyNote icon={<Ticket className="h-5 w-5" />}>{__('client_rewards.vouchers.empty')}</EmptyNote>
                ) : (
                    <>
                        <p className="mb-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_rewards.vouchers.how')}</p>
                        <ul className="space-y-3">
                            {vouchers.map((voucher) => (
                                <li key={voucher.id}>
                                    <Card className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex min-w-0 items-start gap-3">
                                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40"><Gift className="h-5 w-5" /></span>
                                            <div className="min-w-0">
                                                <h3 className="font-bold">{voucher.name}</h3>
                                                <p className="mt-0.5 text-sm">
                                                    {__('client_rewards.vouchers.spend', { amount: formatMoney(voucher.spend_amount_user_currency, currency) })}{' '}
                                                    <span className="font-semibold text-emerald-700 dark:text-emerald-400">{__('client_rewards.vouchers.get', { amount: formatMoney(voucher.reward_amount_user_currency, currency) })}</span>
                                                </p>
                                                {voucher.description && <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{voucher.description}</p>}
                                                <p className="mt-1 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                                                    {voucher.expires_at ? __('client_rewards.vouchers.until', { date: formatDate(voucher.expires_at) }) : __('client_rewards.vouchers.no_expiry')}
                                                    {voucher.max_uses_per_user ? ` · ${__('client_rewards.vouchers.per_user', { count: voucher.max_uses_per_user })}` : ''}
                                                </p>
                                            </div>
                                        </div>
                                        <TextAction href={route('financial.add-balance')}>{__('client_rewards.vouchers.add_balance')}</TextAction>
                                    </Card>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Section>

            {used.length > 0 && (
                <Section title={__('client_rewards.vouchers.history')}>
                    <Card className="divide-y divide-black/5 !p-0 dark:divide-white/10">
                        {used.map((r) => (
                            <div key={r.id} className="flex items-center gap-3 px-4 py-3 sm:px-5">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-semibold">{r.voucher?.name}</p>
                                    <p className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                        {__('client_rewards.vouchers.used_on', { date: formatDate(r.created_at) })} · {__('client_rewards.vouchers.spent', { amount: formatMoney(r.spent_amount, currency) })}
                                    </p>
                                </div>
                                <span className="shrink-0 font-semibold text-emerald-700 dark:text-emerald-400" dir="ltr">
                                    {__('client_rewards.vouchers.reward', { amount: formatMoney(r.reward_amount, currency) })}
                                </span>
                            </div>
                        ))}
                    </Card>
                </Section>
            )}
        </ClientPage>
    );
}
