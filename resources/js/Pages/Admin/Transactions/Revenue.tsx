import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { formatMoney } from '@/lib/utils';
import TransactionUserCard from './Components/TransactionUserCard';
import { MetricCard } from '@/Components/ui/MetricCard';
import { TrendingUp, TrendingDown, Wallet } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Revenue({ income, cost, filters, businessCurrency, filteredUser, stats }) {
    const handleSearch = (search: string, type: 'income' | 'cost') => {
        router.get(
            '/admin/transactions',
            { ...filters, search, type, page: 1 },
            { preserveState: true, replace: true },
        );
    };

    const handleSort = (key: string) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/transactions',
            { ...filters, sort: key, type: 'revenue', direction },
            { preserveState: true, replace: true },
        );
    };

    const buildColumns = (kind: 'income' | 'cost', color: 'emerald' | 'rose') => [
        {
            key: 'id',
            label: __('general.id'),
            render: (tx: any) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>,
        },
        {
            key: 'user',
            label: __('general.user'),
            render: (tx: any) => tx.user ? tx.user.name : '—',
        },
        {
            key: 'business_amount',
            label: kind === 'income' ? __('general.income') : __('erp.cost'),
            className: 'text-end',
            render: (tx: any) => (
                <span className={`font-medium font-mono ${color === 'emerald' ? 'text-emerald-600' : 'text-rose-600'}`}>
                    {formatMoney(tx.business_amount || 0, tx.business_currency)}
                </span>
            ),
        },
    ];

    return (
        <AdminSidebarLayout title={__('general.revenue')} header={__('erp.transactions')}>
            <Head title={__('general.revenue')} />
            {filteredUser && <TransactionUserCard user={filteredUser} />}

            {stats && (
                <div className="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <MetricCard label={__('general.income')} value={formatMoney(stats.income_total ?? 0, businessCurrency)} icon={TrendingUp} />
                    <MetricCard label={__('erp.cost')} value={formatMoney(stats.cost_total ?? 0, businessCurrency)} icon={TrendingDown} />
                    <MetricCard
                        label={__('general.net')}
                        value={formatMoney((stats.income_total ?? 0) - (stats.cost_total ?? 0), businessCurrency)}
                        icon={Wallet}
                    />
                </div>
            )}

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900">{__('general.revenue_summary')}</h2>
                    <p className="text-sm text-slate-500">{__('erp.view_combined_income_and_costs')}</p>
                </div>
                <Button asChild>
                    <Link href={`/admin/transactions/create${filteredUser ? `?user_id=${filteredUser.id}` : ''}`}>
                        {__('erp.create_transaction')}
                    </Link>
                </Button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <Card>
                    <CardContent className="pt-6">
                        <h3 className="text-md font-medium text-slate-800 mb-3">{__('general.income')}</h3>
                        <DataTable
                            columns={buildColumns('income', 'emerald')}
                            data={income.data}
                            pagination={income}
                            filters={filters}
                            onSearch={(s) => handleSearch(s, 'income')}
                            onSort={handleSort}
                            emptyTitle={__('general.no_income_found')}
                        />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-6">
                        <h3 className="text-md font-medium text-slate-800 mb-3">{__('erp.costs')}</h3>
                        <DataTable
                            columns={buildColumns('cost', 'rose')}
                            data={cost.data}
                            pagination={cost}
                            filters={filters}
                            onSearch={(s) => handleSearch(s, 'cost')}
                            onSort={handleSort}
                            emptyTitle={__('erp.no_costs_found')}
                        />
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}