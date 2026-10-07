import React from 'react';
import { __ } from '@/lib/i18n';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Ban, Calendar, Clock, Receipt, Wallet, Layers, Sparkles, Building2, Settings, Crown, ArrowRight } from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { DataTable } from '@/Components/ui/DataTable';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { useConfirm } from '@/hooks/useConfirm';

const BILLING_CYCLE_KEYS: Record<string, string> = {
    monthly: 'client.subs_cycle_monthly',
    yearly: 'client.subs_cycle_yearly',
};

interface Subscription {
    id: number;
    plan_name: string;
    plan_slug: string;
    billing_cycle: string;
    amount: number;
    currency: string;
    status: string;
    started_at: string;
    expires_at: string;
    auto_renew: boolean;
    custom_items: string[] | null;
    is_custom: boolean;
}

interface Invoice {
    id: number;
    invoice_number: string;
    amount: number;
    currency: string;
    status: string;
    payment_method: string;
    paid_at: string;
}

interface ManageProps {
    subscriptions: Subscription[];
    invoices: Invoice[];
    walletBalance: number;
    currency: string;
}

export default function Manage({ subscriptions, invoices, walletBalance, currency }: ManageProps) {
    const { confirm, confirmDialog } = useConfirm();

    const formatMoney = (amount: number, customCurr?: string) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: customCurr || currency
        }).format(amount);
    };

    const handleCancel = async (subId: number) => {
        const accepted = await confirm({
            title: __('client.subs_cancel_title'),
            description: __('client.subs_cancel_confirm'),
            variant: 'danger',
        });
        if (!accepted) return;
        router.post(route('subscriptions.cancel'), { id: subId });
    };

    const handleRenew = async (subId: number, price: number) => {
        if (walletBalance < price) {
            toast.error(__('client.subs_insufficient_balance', { price: formatMoney(price) }));
            return;
        }
        const accepted = await confirm({
            title: __('client.subs_renew_title'),
            description: __('client.subs_renew_confirm', { price: formatMoney(price) }),
        });
        if (!accepted) return;
        router.post(route('subscriptions.renew'), { id: subId });
    };

    const invoiceColumns: any[] = [
        { key: 'invoice_number', label: __('client.subs_invoice_no'), render: (row: any) => <span className="font-mono font-medium">{row.invoice_number}</span> },
        { key: 'amount', label: __('client.subs_amount_paid'), render: (row: any) => <CurrencyDisplay amount={row.amount} currency={row.currency} className="font-semibold" /> },
        { key: 'payment_method', label: __('general.payment_method'), render: (row: any) => <span className="text-xs">{row.payment_method}</span> },
        { key: 'paid_at', label: __('general.date'), render: (row: any) => <span className="text-xs text-text-muted">{row.paid_at}</span> },
        { key: 'status', label: __('general.status'), render: (row: any) => <StatusBadge status={row.status} size="sm" /> }
    ];

    const menuItems = [
        { id: 'dashboard', label: __('general.overview'), icon: Building2, href: '/dashboard', isActive: false },
        { id: 'wallet', label: __('general.wallet'), icon: Wallet, href: route().has('financial.add-balance') ? route('financial.add-balance') : '#', isActive: false },
        { id: 'subscriptions', label: __('general.subscriptions'), icon: Crown, href: '/subscriptions/manage', isActive: true },
        { id: 'plans', label: __('client.subs_browse_plans'), icon: Sparkles, href: '/subscriptions/plans', isActive: false },
        { id: 'settings', label: __('general.settings'), icon: Settings, href: '/profile', isActive: false },
    ];

    const activeCount = subscriptions.filter(s => s.status === 'active').length;
    const monthlySpend = subscriptions
        .filter(s => s.status === 'active')
        .reduce((sum, s) => {
            if (s.billing_cycle === 'yearly') return sum + (s.amount / 12);
            return sum + s.amount;
        }, 0);

    return (
        <WorkspaceLayout 
            title={__('general.my_subscriptions')}
            workspaceName={__('client.subs_workspace_name')}
            tenantId="CUST-PORTAL"
            menuItems={menuItems}
        >
            <div className="space-y-8">
                <ModulePageHeader 
                    title={__('general.my_subscriptions')}
                    description={__('general.manage_your_unified_platform_subscription_renewals_and_billing_history')}
                    actions={
                        <Link href={route('subscriptions.plans')}>
                            <Button className="shadow-sm bg-primary hover:bg-primary-hover text-white font-semibold h-9 text-xs gap-1.5">
                                <Crown className="h-3.5 w-3.5" />{__('general.explore_plans')}</Button>
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <MetricCard 
                        label={__('general.active_subscription')}
                        value={activeCount > 0 ? subscriptions.find(s => s.status === 'active')?.plan_name ?? __('general.none') : __('general.none')}
                        icon={Layers}
                    />
                    <MetricCard 
                        label={__('general.monthly_run_rate')}
                        value={__('client.subs_per_month_value', { amount: formatMoney(monthlySpend) })}
                        icon={Receipt}
                    />
                    <MetricCard 
                        label={__('general.wallet_balance')}
                        value={formatMoney(walletBalance)}
                        icon={Wallet}
                    />
                </div>

                <div className="space-y-6">
                    <h3 className="text-sm font-bold tracking-wider uppercase text-text-muted flex items-center gap-2">
                        <Layers className="h-4 w-4" />{__('general.subscription_history')}</h3>
                    
                    {subscriptions.length === 0 ? (
                        <OperationalCard>
                            <EmptyState 
                                icon={Clock}
                                title={__('general.no_subscriptions_yet')}
                                description={__('general.subscribe_to_a_plan_to_unlock_platform_features_build_your_own_or_pick_from_our_curated_tiers')}
                                action={{ label: __('general.explore_plans'), href: route('subscriptions.plans') }}
                            />
                        </OperationalCard>
                    ) : (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {subscriptions.map((sub) => {
                                const isActive = sub.status === 'active';
                                const isCancelled = sub.status === 'cancelled';
                                const isExpired = sub.status === 'expired';

                                return (
                                    <div key={sub.id} className={cn(
                                        "bg-surface border flex flex-col rounded-xl overflow-hidden shadow-sm transition-all hover:shadow relative",
                                        isActive ? "border-s-4 border-s-emerald-500 border-border" : 
                                        isCancelled ? "border-s-4 border-s-amber-500 border-border" : 
                                        "border-s-4 border-s-danger border-border"
                                    )}>
                                        <div className="p-6 space-y-5">
                                            <div className="flex justify-between items-start">
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-[10px] uppercase font-bold text-text-muted bg-surface-raised px-2 py-1 rounded">
                                                            {sub.is_custom ? __('client.subs_custom') : sub.plan_slug.toUpperCase().replace('_', ' ')}
                                                        </span>
                                                        <span className="text-[10px] uppercase font-medium text-slate-400">
                                                            {BILLING_CYCLE_KEYS[sub.billing_cycle] ? __(BILLING_CYCLE_KEYS[sub.billing_cycle]) : sub.billing_cycle}
                                                        </span>
                                                    </div>
                                                    <h4 className="text-lg font-bold tracking-tight mt-1 text-text-primary">{sub.plan_name}</h4>
                                                    <div className="text-sm font-semibold text-primary">
                                                        <CurrencyDisplay amount={sub.amount} currency={sub.currency} /> {sub.billing_cycle === 'yearly' ? __('client.subs_per_year') : __('client.subs_per_month')}
                                                    </div>
                                                </div>

                                                <div>
                                                    {isActive && <StatusBadge status="active" />}
                                                    {isCancelled && <span className="inline-flex items-center rounded-full border px-2.5 py-1 text-[12px] font-sans font-medium bg-amber-50 text-amber-700 border-amber-100"><span className="me-1.5 h-1.5 w-1.5 rounded-full bg-amber-500"></span>{__('general.pending_expiry')}</span>}
                                                    {isExpired && <StatusBadge status="expired" />}
                                                </div>
                                            </div>

                                            {/* Custom items list */}
                                            {sub.is_custom && sub.custom_items && sub.custom_items.length > 0 && (
                                                <div className="flex flex-wrap gap-1.5">
                                                    {sub.custom_items.map((item, i) => (
                                                        <span key={i} className="text-[10px] font-medium bg-fuchsia-50 text-fuchsia-700 border border-fuchsia-100 px-2 py-0.5 rounded-full">
                                                            {item}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}

                                            <div className="grid grid-cols-2 gap-4 text-xs font-medium text-text-muted border-t border-b border-border/40 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4" />
                                                    <span>{__('client.subs_started', { date: sub.started_at })}</span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Clock className="h-4 w-4" />
                                                    <span>{__('client.subs_expires', { date: sub.expires_at })}</span>
                                                </div>
                                            </div>

                                            <div className="flex justify-end gap-3 pt-1">
                                                {isActive && sub.auto_renew && walletBalance >= sub.amount && (
                                                    <Button
                                                        onClick={() => handleCancel(sub.id)}
                                                        variant="outline"
                                                        size="sm"
                                                        className="shadow-none text-danger hover:text-danger hover:bg-danger/10 border-border bg-transparent h-8 text-xs font-semibold gap-1.5"
                                                    >
                                                        <Ban className="h-3.5 w-3.5" />{__('general.cancel_auto_renew')}</Button>
                                                )}

                                                {(isCancelled || isExpired) && (
                                                    <Button
                                                        onClick={() => handleRenew(sub.id, sub.amount)}
                                                        size="sm"
                                                        className="shadow-none bg-primary hover:bg-primary-hover text-white font-semibold h-8 text-xs gap-1.5"
                                                    >
                                                        <RefreshCw className="h-3.5 w-3.5" />{__('general.renew_via_wallet')}</Button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <div className="space-y-6">
                    <h3 className="text-sm font-bold tracking-wider uppercase text-text-muted flex items-center gap-2">
                        <Receipt className="h-4 w-4" />{__('general.platform_billing_history')}</h3>

                    <OperationalCard noPadding>
                        <DataTable 
                            columns={invoiceColumns as any}
                            data={invoices as any}
                            emptyState={
                                <EmptyState 
                                    icon={Receipt}
                                    title={__('general.no_invoice_history_found')}
                                />
                            }
                            className="border-0 shadow-none rounded-none"
                        />
                    </OperationalCard>
                </div>
            </div>
            {confirmDialog}
        </WorkspaceLayout>
    );
}
