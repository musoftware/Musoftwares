import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { Wallet, Sparkles } from 'lucide-react';
import { formatMoney, safeRoute } from '@/lib/utils';
import { MetricCard } from '@/Components/ui/MetricCard';
import { __ } from '@/lib/i18n';
import PendingInvoicesBanner from './Dashboard/Components/PendingInvoicesBanner';
import CoreOperationsCards from './Dashboard/Components/CoreOperationsCards';
import FinancialHistory from './Dashboard/Components/FinancialHistory';
import type { DashboardStats,  RecentTransaction, ChartData } from './Dashboard/types';

interface DashboardProps {
    stats?: DashboardStats;
    recentTransactions?: RecentTransaction[];
    chartData?: ChartData[];
}

export default function Dashboard({ 
    stats, 
    recentTransactions = [],
    chartData = [],
}: DashboardProps) {
    const { auth } = usePage<{ auth: { user: { id: number; name: string; email: string } } }>().props;
    const user = auth?.user;

    const activityFeedItems = React.useMemo(() => {
        return recentTransactions.map((txn) => {
            const isDeposit = txn.type === 'deposit';
            
            return {
                id: txn.id,
                description: isDeposit 
                    ? __('general.transaction_deposit_desc', { amount: formatMoney(txn.amount, txn.currency), method: txn.method })
                    : __('general.transaction_withdrawal_desc', { amount: formatMoney(txn.amount, txn.currency), method: txn.method }),
                created_at: txn.date,
                icon: isDeposit ? 'wallet' : 'receipt',
                isDeposit,
            };
        });
    }, [recentTransactions]);

    if (!stats) {
        return (
            <AuthenticatedLayout>
                <Head title={__('general.dashboard')} />
                <div className="flex h-[50vh] items-center justify-center">
                    <div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-slate-800"></div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title={__('general.dashboard')} />
                <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

                {/* SECTION 1: PENDING INVOICES BANNER (FIRST — most urgent) */}
                <PendingInvoicesBanner
                    stats={stats}
                />

                {/* SECTION 2: HEADER & IDENTITY */}
                <div className="mb-2">
                    <h1 className="text-3xl font-bold tracking-tight text-slate-900">
                        {__('general.welcome')}, {user?.name}
                    </h1>
                </div>

                {/* SECTION 3: FINANCIAL CONSOLIDATION (2 COLS) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <MetricCard
                        label={__('general.account_balance')}
                        value={formatMoney(stats.walletBalance, stats.currency)}
                        icon={Wallet}
                    />
                    <MetricCard
                        label={__('general.monthly_subscription')}
                        value={formatMoney(stats.totalMonthlySubscription, stats.currency)}
                        icon={Sparkles}
                    />
                </div>

                {/* SECTION 3: CORE OPERATIONS (3 ACTION CARDS) */}
                <CoreOperationsCards 
                    stats={stats} 
                />

                {/* SECTION 4: FINANCIAL HISTORY (8/4 SPLIT) */}
                <FinancialHistory 
                    chartData={chartData} 
                    activityFeedItems={activityFeedItems} 
                />

            </div>
        </AuthenticatedLayout>
    );
}
