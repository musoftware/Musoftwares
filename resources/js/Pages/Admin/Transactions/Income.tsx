import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
 // Example for translations if applicable
import { __ } from '@/lib/i18n';

export default function Income({ transactions, filters }) {
    const handleSearch = (search) => {
        router.get(
            '/admin/transactions',
            { ...filters, search, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/transactions',
            { ...filters, sort: key, direction },
            { preserveState: true, replace: true }
        );
    };

    const columns = [
        {
            key: 'id',
            label: __('general.id'),
            sortable: true,
            className: 'w-[60px]',
            render: (tx) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>
        },
        {
            key: 'user',
            label: __('general.user'),
            render: (tx) => tx.user ? (
                <div className="flex flex-col">
                    <span className="font-medium text-slate-900">{tx.user.name}</span>
                    <span className="text-xs text-slate-500">{tx.user.email}</span>
                </div>
            ) : <span className="text-slate-400">—</span>
        },
        {
            key: 'project',
            label: __('erp.project'),
            render: (tx) => tx.project ? (
                <span className="text-slate-700">{tx.project.project_name}</span>
            ) : <span className="text-slate-400">—</span>
        },
        {
            key: 'type',
            label: __('general.type'),
            render: (tx) => (
                <Badge variant={tx.type === 'refunded' || tx.type === 'send' ? 'destructive' : 'default'} className="uppercase">
                    {__(tx.type)}
                </Badge>
            )
        },
        {
            key: 'amount',
            label: __('general.amount'),
            className: 'text-right',
            render: (tx) => (
                <span className="font-medium font-mono text-slate-800">
                    {formatCurrency(tx.amount || 0, tx.currency)}
                </span>
            )
        },
        {
            key: 'business_amount',
            label: __('general.business_amount'),
            className: 'text-right',
            render: (tx) => (
                <span className="font-medium font-mono text-green-600">
                    {formatCurrency(tx.business_amount || 0, tx.business_currency)}
                </span>
            )
        },
        {
            key: 'reason',
            label: __('general.reason'),
            render: (tx) => <span className="text-slate-600 max-w-[250px] truncate block" title={tx.reason}>{tx.reason}</span>
        },
        {
            key: 'created_at',
            label: __('general.date'),
            sortable: true,
            render: (tx) => (
                <span className="text-slate-600 whitespace-nowrap">
                    {new Date(tx.created_at).toLocaleDateString()}
                </span>
            )
        },
    ];

    return (
        <AdminSidebarLayout title={__('erp.income_transactions')} header={__('erp.transactions')}>
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900">{__('erp.income_transactions')}</h2>
                    <p className="text-sm text-slate-500">{__('erp.view_all_income_and_related')}</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="default" asChild>
                        <Link href="/admin/transactions/create?type=receive">{__('general.receive')}</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/admin/transactions/create?type=earn">{__('general.earn')}</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/admin/transactions/create?type=refund">{__('general.refund')}</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/admin/transactions/create?type=send">{__('general.send')}</Link>
                    </Button>
                </div>
            </div>

            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={transactions.data}
                    pagination={transactions}
                    filters={filters}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={__('erp.no_transactions_found')}
                    emptyDescription={__('general.try_adjusting_your_search_filters')}
                />
            </div>
        </AdminSidebarLayout>
    );
}
