import React, { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Coins, CreditCard, Wallet } from 'lucide-react';
import ClientPage, { Card, EmptyNote, Section } from '@/Components/Client/ClientPage';
import { useConfirm } from '@/hooks/useConfirm';
import { cn, formatMoney, formatNumber } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const ADDS = ['purchased', 'earned'];

/** Points balance, ready-made packages, any other amount, and the history of point movements. */
export default function PointsIndex({ auth, tiers = [], quickPackages = [], transactions, currency }) {
    const { wallet, locale } = usePage().props;
    const walletBalance = wallet ? Number(wallet.balance) : 0;
    const money = (amount) => formatMoney(Number(amount || 0), currency || wallet?.currency);
    const [customPoints, setCustomPoints] = useState('');
    const { confirm, confirmDialog } = useConfirm();
    const rows = transactions?.data ?? [];
    const formatDate = (value) =>
        value ? new Date(value).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    // price of a custom amount comes from the volume tier it falls in
    const custom = useMemo(() => {
        const pts = parseInt(customPoints, 10);
        if (!pts || pts <= 0 || !tiers.length) return null;
        const tier = tiers.find((t) => pts >= t.min && (t.max === null || pts <= t.max));
        if (!tier) return null;
        const totalCost = pts * tier.price_per_point;
        return { points: pts, pricePerPoint: tier.price_per_point, totalCost, savings: pts * tiers[0].price_per_point - totalCost };
    }, [customPoints, tiers]);

    const confirmPurchase = (totalCost, points) => {
        const amount = money(totalCost);
        const description = walletBalance >= totalCost
            ? __('general.points_confirm_wallet', { amount, points: formatNumber(points) })
            : __('general.points_confirm_kashier', { amount });
        return confirm({ title: __('general.points_confirm_title'), description });
    };
    const buyPackage = async (pkg) => {
        if (await confirmPurchase(pkg.total_cost, pkg.points)) router.post(route('point-purchases.store'), { package_id: pkg.id });
    };
    const buyCustom = async () => {
        if (custom && (await confirmPurchase(custom.totalCost, custom.points))) router.post(route('point-purchases.store-wallet'), { points: custom.points });
    };
    const payNote = (cost) => (walletBalance >= cost ? __('client_money.points.from_wallet') : __('client_money.points.by_card'));

    return (
        <ClientPage title={__('client_money.points.title')} hint={__('client_money.points.hint')}>
            <Card className="flex flex-wrap items-center justify-between gap-3">
                <span className="flex items-center gap-3">
                    <span className="flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-950/40"><Coins className="h-5 w-5" /></span>
                    <span className="text-xl font-bold">{__('client_money.points.balance', { count: formatNumber(auth?.user?.points_balance || 0) })}</span>
                </span>
                <span className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_money.wallet_balance')}: <b dir="ltr">{money(walletBalance)}</b></span>
            </Card>

            {quickPackages.length > 0 && (
                <Section title={__('client_money.points.packages')}>
                    <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        {quickPackages.map((pkg) => {
                            const best = pkg.discount_percent >= 40;
                            return (
                                <li key={pkg.id}>
                                    <Card className={cn('flex h-full flex-col gap-3', best && 'border-[#0071e3]/40')}>
                                        <div className="flex items-baseline justify-between gap-2">
                                            <p className="text-2xl font-bold">{__('client_money.points.points', { count: formatNumber(pkg.points) })}</p>
                                            {best ? (
                                                <span className="text-xs font-semibold text-[#0071e3]">{__('client_money.points.best')}</span>
                                            ) : pkg.discount_percent > 0 ? (
                                                <span className="text-xs font-semibold text-emerald-700" dir="ltr">−{pkg.discount_percent}%</span>
                                            ) : null}
                                        </div>
                                        <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                                            {__('client_money.points.per_point', { amount: money(pkg.price_per_point) })}
                                            {pkg.savings > 0 && ` · ${__('client_money.points.save', { amount: money(pkg.savings) })}`}
                                        </p>
                                        <button type="button" onClick={() => buyPackage(pkg)} className="mt-auto inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-5 text-sm font-semibold text-white hover:bg-[#0077ed]">
                                            {walletBalance >= pkg.total_cost ? <Wallet className="h-4 w-4" /> : <CreditCard className="h-4 w-4" />}
                                            {__('client_money.points.buy', { amount: money(pkg.total_cost) })}
                                        </button>
                                        <p className="text-center text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{payNote(pkg.total_cost)}</p>
                                    </Card>
                                </li>
                            );
                        })}
                    </ul>
                </Section>
            )}

            <Section title={__('client_money.points.other')}>
                <Card className="space-y-3">
                    <label htmlFor="custom-points" className="block text-sm font-semibold">{__('client_money.points.how_many')}</label>
                    <input
                        id="custom-points"
                        type="number"
                        min="1"
                        inputMode="numeric"
                        dir="ltr"
                        value={customPoints}
                        onChange={(e) => setCustomPoints(e.target.value)}
                        className="h-11 w-full max-w-xs rounded-xl border border-black/10 bg-white px-3 text-base focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/20 dark:border-white/10 dark:bg-zinc-900"
                    />
                    {custom ? (
                        <>
                            <p className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                                {__('client_money.points.you_pay', { amount: money(custom.totalCost) })} · {__('client_money.points.per_point', { amount: money(custom.pricePerPoint) })}
                                {custom.savings > 0 && ` · ${__('client_money.points.save', { amount: money(custom.savings) })}`}
                            </p>
                            <button type="button" onClick={buyCustom} className="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed]">
                                {walletBalance >= custom.totalCost ? <Wallet className="h-4 w-4" /> : <CreditCard className="h-4 w-4" />}
                                {__('client_money.points.buy', { amount: money(custom.totalCost) })}
                            </button>
                            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{payNote(custom.totalCost)}</p>
                        </>
                    ) : (
                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_money.points.invalid')}</p>
                    )}
                </Card>
            </Section>

            <Section title={__('client_money.points.history')}>
                {rows.length === 0 ? (
                    <EmptyNote>{__('client_money.points.empty')}</EmptyNote>
                ) : (
                    <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                        {rows.map((tx) => {
                            const adds = ADDS.includes(tx.type);
                            return (
                                <li key={tx.id} className="flex items-center gap-3 px-4 py-3">
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-[15px] font-medium">{tx.description}</p>
                                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{formatDate(tx.created_at)}</p>
                                    </div>
                                    <span className={cn('shrink-0 text-sm font-semibold', adds ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600')} dir="ltr">
                                        {adds ? '+' : '−'}{formatNumber(tx.points)}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </Section>
            {confirmDialog}
        </ClientPage>
    );
}
