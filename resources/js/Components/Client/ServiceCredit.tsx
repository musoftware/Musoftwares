import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Coins, CreditCard, Wallet } from 'lucide-react';
import { Card, EmptyNote, Section, TextAction } from '@/Components/Client/ClientPage';
import { useConfirm } from '@/hooks/useConfirm';
import { cn, formatMoney, formatNumber } from '@/lib/utils';
import { __ } from '@/lib/i18n';

const ADDS = ['purchased', 'earned'];

type PriceTier = { min: number; max: number | null; price_per_point: number };
type QuickPackage = { id: number; points: number; total_cost: number; price_per_point: number; savings: number; discount_percent: number };
type CreditMove = { id: number; type: string; points: number; description: string; created_at: string };
type Paged<T> = { data: T[]; current_page: number; last_page: number; prev_page_url?: string | null; next_page_url?: string | null };

export interface ServiceCreditData {
    tiers: PriceTier[];
    quickPackages: QuickPackage[];
    transactions: Paged<CreditMove>;
    currency: string;
}

/** Bought points: the balance, ready-made packages, any other amount, and the history. */
export default function ServiceCredit({ tiers, quickPackages, transactions, currency }: ServiceCreditData) {
    const { auth, wallet } = usePage().props as { auth?: { user?: { points_balance?: number } }; wallet?: { balance: number | string; currency?: string } };
    const walletBalance = wallet ? Number(wallet.balance) : 0;
    const money = (amount: number) => formatMoney(Number(amount || 0), currency || wallet?.currency);
    const { confirm, confirmDialog } = useConfirm();

    const confirmPurchase = (totalCost: number, points: number) => {
        const amount = money(totalCost);
        const description = walletBalance >= totalCost
            ? __('general.points_confirm_wallet', { amount, points: formatNumber(points) })
            : __('general.points_confirm_kashier', { amount });
        return confirm({ title: __('general.points_confirm_title'), description });
    };
    const buyPackage = async (pkg: QuickPackage) => {
        if (await confirmPurchase(pkg.total_cost, pkg.points)) router.post(route('point-purchases.store'), { package_id: pkg.id });
    };
    const buyCustom = async (points: number, totalCost: number) => {
        if (await confirmPurchase(totalCost, points)) router.post(route('point-purchases.store-wallet'), { points });
    };

    return (
        <>
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
                        {quickPackages.map((pkg) => (
                            <li key={pkg.id}>
                                <PackageCard pkg={pkg} money={money} walletBalance={walletBalance} onBuy={() => buyPackage(pkg)} />
                            </li>
                        ))}
                    </ul>
                </Section>
            )}

            <CustomAmount tiers={tiers} money={money} walletBalance={walletBalance} onBuy={buyCustom} />
            <CreditHistory transactions={transactions} />
            {confirmDialog}
        </>
    );
}

function BuyButton({ cost, walletBalance, money, onClick }: { cost: number; walletBalance: number; money: (n: number) => string; onClick: () => void }) {
    const fromWallet = walletBalance >= cost;
    return (
        <>
            <button type="button" onClick={onClick} className="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed]">
                {fromWallet ? <Wallet className="h-4 w-4" /> : <CreditCard className="h-4 w-4" />}
                {__('client_money.points.buy', { amount: money(cost) })}
            </button>
            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{fromWallet ? __('client_money.points.from_wallet') : __('client_money.points.by_card')}</p>
        </>
    );
}

function PackageCard({ pkg, money, walletBalance, onBuy }: { pkg: QuickPackage; money: (n: number) => string; walletBalance: number; onBuy: () => void }) {
    const best = pkg.discount_percent >= 40;
    return (
        <Card className={cn('flex h-full flex-col gap-3', best && 'border-[#0071e3]/40')}>
            <div className="flex items-baseline justify-between gap-2">
                <p className="text-2xl font-bold">{__('client_money.points.points', { count: formatNumber(pkg.points) })}</p>
                {best && <span className="text-xs font-semibold text-[#0071e3]">{__('client_money.points.best')}</span>}
                {!best && pkg.discount_percent > 0 && <span className="text-xs font-semibold text-emerald-700" dir="ltr">−{pkg.discount_percent}%</span>}
            </div>
            <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                {__('client_money.points.per_point', { amount: money(pkg.price_per_point) })}
                {pkg.savings > 0 && ` · ${__('client_money.points.save', { amount: money(pkg.savings) })}`}
            </p>
            <div className="mt-auto flex flex-col items-stretch gap-2 text-center">
                <BuyButton cost={pkg.total_cost} walletBalance={walletBalance} money={money} onClick={onBuy} />
            </div>
        </Card>
    );
}

/** Any number of points; the price per point comes from the volume tier the number falls in. */
function CustomAmount({ tiers, money, walletBalance, onBuy }: { tiers: PriceTier[]; money: (n: number) => string; walletBalance: number; onBuy: (points: number, cost: number) => void }) {
    const [customPoints, setCustomPoints] = useState('');
    const custom = useMemo(() => {
        const pts = parseInt(customPoints, 10);
        const tier = pts > 0 ? tiers.find((t) => pts >= t.min && (t.max === null || pts <= t.max)) : undefined;
        if (!tier) return null;
        const totalCost = pts * tier.price_per_point;
        return { points: pts, pricePerPoint: tier.price_per_point, totalCost, savings: pts * tiers[0].price_per_point - totalCost };
    }, [customPoints, tiers]);

    return (
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
                {!custom && <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_money.points.invalid')}</p>}
                {custom && (
                    <>
                        <p className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                            {__('client_money.points.you_pay', { amount: money(custom.totalCost) })} · {__('client_money.points.per_point', { amount: money(custom.pricePerPoint) })}
                            {custom.savings > 0 && ` · ${__('client_money.points.save', { amount: money(custom.savings) })}`}
                        </p>
                        <BuyButton cost={custom.totalCost} walletBalance={walletBalance} money={money} onClick={() => onBuy(custom.points, custom.totalCost)} />
                    </>
                )}
            </Card>
        </Section>
    );
}

function CreditHistory({ transactions }: { transactions: Paged<CreditMove> }) {
    const { locale } = usePage().props as { locale?: string };
    const rows = transactions?.data ?? [];
    const formatDate = (value: string) =>
        value ? new Date(value).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    return (
        <Section title={__('client_money.points.history')}>
            {rows.length === 0 && <EmptyNote>{__('client_money.points.empty')}</EmptyNote>}
            {rows.length > 0 && (
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
            {transactions?.last_page > 1 && (
                <div className="mt-3 flex items-center justify-between text-sm font-semibold">
                    {transactions.prev_page_url ? <TextAction href={`${transactions.prev_page_url}#credit`}>{__('client_rewards.referrals.previous')}</TextAction> : <span />}
                    <span className="text-[#1d1d1f]/50" dir="ltr">{transactions.current_page} / {transactions.last_page}</span>
                    {transactions.next_page_url ? <TextAction href={`${transactions.next_page_url}#credit`}>{__('client_rewards.referrals.next')}</TextAction> : <span />}
                </div>
            )}
        </Section>
    );
}
