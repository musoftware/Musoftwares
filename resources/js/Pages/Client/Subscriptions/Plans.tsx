import { useState } from 'react';
import { router } from '@inertiajs/react';
import { CreditCard, Wallet } from 'lucide-react';
import PricingBuilder, { ServiceItem, ActiveSub } from '@/Components/PricingBuilder';
import ClientPage, { Card, TextAction } from '@/Components/Client/ClientPage';
import { useConfirm } from '@/hooks/useConfirm';
import { cn, formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface PlansProps {
    serviceItems: ServiceItem[];
    activeSubscription: ActiveSub | null;
    walletBalance: number;
    currency: string;
    proratedRefund?: number;
}

/** Build a plan from modules, then pay from the wallet or by card. */
export default function Plans({ serviceItems, activeSubscription, walletBalance, currency, proratedRefund = 0 }: PlansProps) {
    const { confirm, confirmDialog } = useConfirm();
    const hasActiveSub = !!(activeSubscription?.owned_features && activeSubscription.owned_features.length > 0);
    const [isNewSystem, setIsNewSystem] = useState<boolean>(!hasActiveSub);
    const money = (amount: number) => formatMoney(amount, currency);

    return (
        <ClientPage
            width="lg"
            title={__('client_money.plans.title')}
            hint={__('client_money.plans.hint')}
            action={<TextAction href={route('subscriptions.manage')}>{__('client_money.plans.mine')}</TextAction>}
        >
            {hasActiveSub && (
                <Card className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                        {__('client_money.plans.has_active')}
                        {activeSubscription?.expires_at && ` ${__('client.subs_renews_on', { date: activeSubscription.expires_at })}`}
                    </p>
                    <div className="flex shrink-0 rounded-full bg-[#f5f5f7] p-1 dark:bg-zinc-800" role="group">
                        {[false, true].map((isNew) => (
                            <button
                                key={String(isNew)}
                                type="button"
                                onClick={() => setIsNewSystem(isNew)}
                                aria-pressed={isNewSystem === isNew}
                                className={cn('rounded-full px-4 py-2 text-sm font-semibold transition-colors',
                                    isNewSystem === isNew ? 'bg-white text-[#1d1d1f] shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-[#1d1d1f]/60 dark:text-zinc-400')}
                            >
                                {isNew ? __('client_money.plans.new_workspace') : __('client_money.plans.add_to_current')}
                            </button>
                        ))}
                    </div>
                </Card>
            )}

            <PricingBuilder
                serviceItems={serviceItems}
                currency={currency}
                activeSubscription={activeSubscription}
                isNewSystem={isNewSystem}
                onSystemTypeChange={setIsNewSystem}
                proratedRefund={proratedRefund}
                renderActions={({ selectedItems, billing, total }) => {
                    const finalTotal = Math.max(0, total - (!isNewSystem ? proratedRefund : 0));
                    const canAfford = walletBalance >= finalTotal;
                    const nothing = selectedItems.length === 0;

                    const payWallet = async () => {
                        if (nothing) return;
                        const description = isNewSystem
                            ? __('client.subs_confirm_new_workspace', { count: selectedItems.length })
                            : __('client.subs_confirm_wallet', { count: selectedItems.length });
                        if (!(await confirm({ title: __('client.subs_confirm_title'), description }))) return;
                        router.post(route('subscriptions.subscribe'), { items: selectedItems, billing_cycle: billing, is_new_system: isNewSystem });
                    };
                    const payCard = () => {
                        if (nothing) return;
                        router.post(route('subscriptions.kashier.checkout'), { items: selectedItems, billing_cycle: billing, is_new_system: isNewSystem });
                    };

                    return (
                        <div className="space-y-3">
                            {canAfford || nothing ? (
                                <button type="button" onClick={payWallet} disabled={nothing}
                                    className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-[#0071e3] text-sm font-semibold text-white hover:bg-[#0077ed] disabled:opacity-50">
                                    <Wallet className="h-4 w-4" />{__('client_money.plans.pay_wallet')}
                                </button>
                            ) : (
                                <a href={route().has('financial.add-balance') ? route('financial.add-balance') : '/financial/add-balance'}
                                    className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-[#f5f5f7] text-sm font-semibold text-[#1d1d1f] hover:bg-black/5 dark:bg-zinc-800 dark:text-white">
                                    <Wallet className="h-4 w-4" />{__('client_money.plans.need_more', { amount: money(finalTotal - walletBalance) })}
                                </a>
                            )}
                            <button type="button" onClick={payCard} disabled={nothing}
                                className={cn('inline-flex h-11 w-full items-center justify-center gap-2 rounded-full text-sm font-semibold disabled:opacity-50',
                                    !canAfford && !nothing ? 'bg-[#0071e3] text-white hover:bg-[#0077ed]' : 'border border-black/10 text-[#1d1d1f] hover:bg-[#f5f5f7] dark:border-white/10 dark:text-white dark:hover:bg-zinc-800')}>
                                <CreditCard className="h-4 w-4" />{__('client_money.plans.pay_card')}
                            </button>
                            <p className="text-center text-xs text-[#1d1d1f]/60 dark:text-zinc-400" dir="auto">
                                {__('client_money.plans.wallet', { amount: money(walletBalance) })}
                            </p>
                        </div>
                    );
                }}
            />
            {confirmDialog}
        </ClientPage>
    );
}
