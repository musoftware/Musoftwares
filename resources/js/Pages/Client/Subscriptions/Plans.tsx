import React, { useState } from 'react';
import { __ } from '@/lib/i18n';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import PricingBuilder, { ServiceItem, ActiveSub } from '@/Components/PricingBuilder';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import {
    Wallet, CreditCard, ArrowRight, ShieldCheck,
    Sparkles, Building2, Crown, Settings, Layers,
    ArrowLeft, PlusCircle, CheckCircle2
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useConfirm } from '@/hooks/useConfirm';

interface PlansProps {
    serviceItems: ServiceItem[];
    activeSubscription: ActiveSub | null;
    walletBalance: number;
    currency: string;
    proratedRefund?: number;
}

export default function Plans({
    serviceItems,
    activeSubscription,
    walletBalance,
    currency,
    proratedRefund = 0
}: PlansProps) {
    const { confirm, confirmDialog } = useConfirm();
    const hasActiveSub = !!(activeSubscription?.owned_features && activeSubscription.owned_features.length > 0);
    const [isNewSystem, setIsNewSystem] = useState<boolean>(!hasActiveSub);

    const menuItems = [
        { id: 'dashboard', label: __('general.overview'), icon: Building2, href: '/dashboard', isActive: false },
        { id: 'wallet', label: __('general.wallet'), icon: Wallet, href: route().has('financial.add-balance') ? route('financial.add-balance') : '#', isActive: false },
        { id: 'subscriptions', label: __('general.subscriptions'), icon: Crown, href: route('subscriptions.manage'), isActive: false },
        { id: 'plans', label: __('client.subs_browse_plans'), icon: Sparkles, href: route('subscriptions.plans'), isActive: true },
        { id: 'settings', label: __('general.settings'), icon: Settings, href: '/profile', isActive: false },
    ];

    const breadcrumbs = [
        { label: __('client.subs_portal'), href: '/dashboard' },
        { label: __('general.my_subscriptions'), href: route('subscriptions.manage') },
        { label: __('general.build_your_workspace'), active: true },
    ];

    return (
        <WorkspaceLayout
            title={__('general.build_your_workspace')}
            workspaceName={__('client.subs_workspace_name')}
            tenantId="CUST-PORTAL"
            menuItems={menuItems}
        >
            <div className="space-y-6">
                {/* ── Module Page Header ── */}
                <ModulePageHeader
                    title={__('general.build_your_workspace')}
                    description={__('general.select_exactly_what_you_need_no_more_no_less')}
                    icon={Crown}
                    breadcrumbs={breadcrumbs}
                    actions={
                        <Link href={route('subscriptions.manage')}>
                            <Button variant="outline" size="sm" className="h-9 text-xs font-semibold gap-1.5 border-slate-200 hover:bg-slate-50">
                                <ArrowLeft className="h-3.5 w-3.5" />
                                {__('general.back_to_my_subscriptions')}
                            </Button>
                        </Link>
                    }
                />

                {/* ── Active Subscription Workspace Banner & Mode Switcher ── */}
                {hasActiveSub && (
                    <div className="bg-white dark:bg-[#0f172a] border border-slate-200/80 dark:border-white/10 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4 transition-colors">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40 flex items-center justify-center shrink-0">
                                    <ShieldCheck className="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-slate-900 dark:text-white">
                                        {__('client.subs_active_modules')}
                                    </h4>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        {__('client.subs_active_features')}
                                        {activeSubscription.expires_at && __('client.subs_renews_on', { date: activeSubscription.expires_at })}
                                    </p>
                                </div>
                            </div>

                            {/* Mode Switcher */}
                            <div className="flex bg-slate-100 dark:bg-white/5 p-1 rounded-xl shrink-0 self-start sm:self-auto border border-transparent dark:border-white/10">
                                <button
                                    type="button"
                                    onClick={() => setIsNewSystem(false)}
                                    className={cn(
                                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5',
                                        !isNewSystem
                                            ? 'bg-white dark:bg-[#1e293b] text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    )}
                                >
                                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                    <span>{__('client.subs_add_to_current')}</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setIsNewSystem(true)}
                                    className={cn(
                                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5',
                                        isNewSystem
                                            ? 'bg-white dark:bg-[#1e293b] text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    )}
                                >
                                    <PlusCircle className="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                    <span>{__('client.subs_new_workspace')}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* ── Pricing & Module Builder ── */}
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

                        const handleSubscribeWallet = async () => {
                            if (selectedItems.length === 0) return;
                            const msg = isNewSystem
                                ? __('client.subs_confirm_new_workspace', { count: selectedItems.length })
                                : __('client.subs_confirm_wallet', { count: selectedItems.length });
                            const accepted = await confirm({ title: __('client.subs_confirm_title'), description: msg });
                            if (!accepted) return;
                            router.post(route('subscriptions.subscribe'), {
                                items: selectedItems,
                                billing_cycle: billing,
                                is_new_system: isNewSystem
                            });
                        };

                        const handleSubscribeKashier = () => {
                            if (selectedItems.length === 0) return;
                            router.post(route('subscriptions.kashier.checkout'), {
                                items: selectedItems,
                                billing_cycle: billing,
                                is_new_system: isNewSystem
                            });
                        };

                        return (
                            <div className="space-y-3">
                                <Button
                                    type="button"
                                    onClick={handleSubscribeWallet}
                                    disabled={selectedItems.length === 0 || !canAfford}
                                    className={cn(
                                        'w-full h-11 rounded-xl text-xs font-bold uppercase tracking-wider gap-2 transition-all',
                                        canAfford && selectedItems.length > 0
                                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                    )}
                                >
                                    <Wallet className="h-4 w-4" />
                                    {canAfford || selectedItems.length === 0
                                        ? __('client.subs_subscribe_wallet')
                                        : __('client.subs_insufficient_need', { amount: `${finalTotal.toFixed(2)} ${currency}` })}
                                </Button>

                                <Button
                                    type="button"
                                    onClick={handleSubscribeKashier}
                                    disabled={selectedItems.length === 0}
                                    variant="outline"
                                    className="w-full h-11 rounded-xl text-xs font-semibold gap-2 border-slate-200 hover:bg-slate-50 text-slate-700"
                                >
                                    <CreditCard className="h-4 w-4 text-slate-400" />
                                    {__('general.pay_by_card')}
                                </Button>

                                {/* Wallet Status & Quick Top-Up */}
                                <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                    <div className="flex items-center gap-1.5">
                                        <Wallet className="h-3.5 w-3.5 text-slate-400" />
                                        <span>{__('client.subs_wallet_label')} <strong className="text-slate-800 font-semibold">{walletBalance.toFixed(2)} {currency}</strong></span>
                                    </div>
                                    <Link
                                        href={route().has('financial.add-balance') ? route('financial.add-balance') : '#'}
                                        className="text-indigo-600 hover:text-indigo-700 font-semibold inline-flex items-center gap-1 text-[11px]"
                                    >
                                        {__('general.add_funds_to_wallet')}
                                        <ArrowRight className="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        );
                    }}
                />
            </div>
            {confirmDialog}
        </WorkspaceLayout>
    );
}
