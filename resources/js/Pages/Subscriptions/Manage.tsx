import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { RefreshCw, Ban, Calendar, Clock, Receipt, Wallet, Layers } from 'lucide-react';
import { AppPage } from '@/Components/ui/AppPage';
import { PageHeader } from '@/Components/ui/PageHeader';
import { StatCard } from '@/Components/ui/StatCard';
import { SectionCard } from '@/Components/ui/SectionCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { DataTable } from '@/Components/ui/DataTable';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { cn } from '@/lib/utils';

interface Subscription {
    id: number;
    module: string;
    plan_name: string;
    price: number;
    billing: string;
    status: string;
    started_at: string;
    expires_at: string;
    auto_renew: boolean;
}

interface Invoice {
    id: number;
    invoice_number: string;
    plan_name: string;
    module: string;
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
    
    const formatMoney = (amount: number, customCurr?: string) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: customCurr || currency
        }).format(amount);
    };

    const handleCancel = (subId: number) => {
        if (confirm("Are you sure you want to cancel the auto-renewal for this subscription? You will retain access until the end of the billing cycle.")) {
            router.post(route('subscriptions.cancel'), { id: subId });
        }
    };

    const handleRenew = (subId: number, price: number) => {
        if (walletBalance < price) {
            alert(`Insufficient wallet balance to renew. Price is ${formatMoney(price)}. Please add funds first.`);
            return;
        }
        if (confirm(`Renew subscription for ${formatMoney(price)} using your wallet balance?`)) {
            router.post(route('subscriptions.renew'), { id: subId });
        }
    };

    const invoiceColumns: any[] = [
        { key: 'invoice_number', label: 'Invoice #', render: (row: any) => <span className="font-mono font-medium">{row.invoice_number}</span> },
        { key: 'module', label: 'Module', render: (row: any) => <span className="text-[10px] bg-surface-raised px-2 py-0.5 rounded font-semibold text-text-muted uppercase">{row.module}</span> },
        { key: 'plan_name', label: 'Plan', render: (row: any) => <span className="text-sm font-medium">{row.plan_name}</span> },
        { key: 'amount', label: 'Amount Paid', render: (row: any) => <CurrencyDisplay amount={row.amount} currency={row.currency} className="font-semibold" /> },
        { key: 'payment_method', label: 'Payment Method', render: (row: any) => <span className="text-xs">{row.payment_method}</span> },
        { key: 'paid_at', label: 'Date', render: (row: any) => <span className="text-xs text-text-muted">{row.paid_at}</span> },
        { key: 'status', label: 'Status', render: (row: any) => <StatusBadge status={row.status} size="sm" /> }
    ];

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="My Subscriptions" />

            <AppPage>
                <PageHeader 
                    title="My Subscriptions"
                    subtitle="Manage renewals, cycles, billing, and platform module access."
                    actions={
                        <Link href={route('subscriptions.plans')}>
                            <Button className="shadow-sm bg-primary hover:bg-primary-hover text-white font-semibold h-9 text-xs">
                                Explore Pricing Plans
                            </Button>
                        </Link>
                    }
                />

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <StatCard 
                        label="Active Modules"
                        value={subscriptions.filter(s => s.status === 'active').length}
                        icon={Layers}
                    />
                    <StatCard 
                        label="Wallet Balance"
                        value={<CurrencyDisplay amount={walletBalance} currency={currency} className="font-sans text-2xl font-bold" />}
                        icon={Wallet}
                    />
                    <StatCard 
                        label="Invoices Paid"
                        value={`${invoices.filter(i => i.status === 'paid').length} Invoices`}
                        icon={Receipt}
                    />
                </div>

                <div className="space-y-6">
                    <h3 className="text-sm font-bold tracking-wider uppercase text-text-muted flex items-center gap-2">
                        <Layers className="h-4 w-4" /> Subscription Access Layer
                    </h3>
                    
                    {subscriptions.length === 0 ? (
                        <SectionCard>
                            <EmptyState 
                                icon={Clock}
                                title="No active subscriptions found"
                                description="You do not currently have any paid SaaS module subscriptions enabled. Unlock features in a single click."
                                action={route('subscriptions.plans')}
                                actionLabel="Explore pricing plans"
                            />
                        </SectionCard>
                    ) : (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {subscriptions.map((sub) => {
                                const isActive = sub.status === 'active';
                                const isCancelled = sub.status === 'cancelled';
                                const isExpired = sub.status === 'expired';

                                return (
                                    <div key={sub.id} className={cn(
                                        "bg-surface border flex flex-col rounded-xl overflow-hidden shadow-sm transition-all hover:shadow relative",
                                        isActive ? "border-l-4 border-l-emerald-500 border-border" : 
                                        isCancelled ? "border-l-4 border-l-amber-500 border-border" : 
                                        "border-l-4 border-l-danger border-border"
                                    )}>
                                        <div className="p-6 space-y-5">
                                            <div className="flex justify-between items-start">
                                                <div className="space-y-1">
                                                    <span className="text-[10px] uppercase font-bold text-text-muted bg-surface-raised px-2 py-1 rounded">
                                                        {sub.module} MODULE
                                                    </span>
                                                    <h4 className="text-lg font-bold tracking-tight mt-1 text-text-primary">{sub.plan_name}</h4>
                                                    <p className="text-sm font-semibold text-primary">
                                                        <CurrencyDisplay amount={sub.price} currency={currency} /> /{sub.billing === 'yearly' ? 'year' : 'month'}
                                                    </p>
                                                </div>

                                                <div>
                                                    {isActive && <StatusBadge status="active" />}
                                                    {isCancelled && <span className="inline-flex items-center rounded-full border px-2.5 py-1 text-[12px] font-sans font-medium bg-amber-50 text-amber-700 border-amber-100"><span className="mr-1.5 h-1.5 w-1.5 rounded-full bg-amber-500"></span>Pending Expiry</span>}
                                                    {isExpired && <StatusBadge status="expired" />}
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 text-xs font-medium text-text-muted border-t border-b border-border/40 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4" />
                                                    <span>Started: {sub.started_at}</span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Clock className="h-4 w-4" />
                                                    <span>Expires: {sub.expires_at}</span>
                                                </div>
                                            </div>

                                            <div className="flex justify-end gap-3 pt-1">
                                                {isActive && (
                                                    <Button
                                                        onClick={() => handleCancel(sub.id)}
                                                        variant="outline"
                                                        size="sm"
                                                        className="shadow-none text-danger hover:text-danger hover:bg-danger/10 border-border bg-transparent h-8 text-xs font-semibold gap-1.5"
                                                    >
                                                        <Ban className="h-3.5 w-3.5" /> Cancel Auto-Renew
                                                    </Button>
                                                )}

                                                {(isCancelled || isExpired) && (
                                                    <Button
                                                        onClick={() => handleRenew(sub.id, sub.price)}
                                                        size="sm"
                                                        className="shadow-none bg-primary hover:bg-primary-hover text-white font-semibold h-8 text-xs gap-1.5"
                                                    >
                                                        <RefreshCw className="h-3.5 w-3.5" /> Renew via Wallet
                                                    </Button>
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
                        <Receipt className="h-4 w-4" /> Platform Billing History
                    </h3>

                    <SectionCard noPadding>
                        <DataTable 
                            columns={invoiceColumns}
                            data={invoices}
                            emptyState={
                                <EmptyState 
                                    icon={Receipt}
                                    title="No invoice history found"
                                />
                            }
                            className="border-0 shadow-none rounded-none"
                        />
                    </SectionCard>
                </div>
            </AppPage>
        </AuthenticatedLayout>
    );
}
