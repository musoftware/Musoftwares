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
            label: __('ID'),
            sortable: true,
            className: 'w-[60px]',
            render: (tx) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>
        },
        {
            key: 'user',
            label: __('User'),
            render: (tx) => tx.user ? (
                <div className="flex flex-col">
                    <span className="font-medium text-slate-900">{tx.user.name}</span>
                    <span className="text-xs text-slate-500">{tx.user.email}</span>
                </div>
            ) : <span className="text-slate-400">—</span>
        },
        {
            key: 'project',
            label: __('Project'),
            render: (tx) => tx.project ? (
                <span className="text-slate-700">{tx.project.project_name}</span>
            ) : <span className="text-slate-400">—</span>
        },
        {
            key: 'type',
            label: __('Type'),
            render: (tx) => (
                <Badge variant={tx.type === 'refunded' || tx.type === 'send' ? 'destructive' : 'default'} className="uppercase">
                    {__(tx.type)}
                </Badge>
            )
        },
        {
            key: 'amount',
            label: __('Amount'),
            className: 'text-right',
            render: (tx) => (
                <span className="font-medium font-mono text-slate-800">
                    {formatCurrency(tx.amount || 0, tx.currency)}
                </span>
            )
        },
        {
            key: 'business_amount',
            label: __('Business Amount'),
            className: 'text-right',
            render: (tx) => (
                <span className="font-medium font-mono text-green-600">
                    {formatCurrency(tx.business_amount || 0, tx.business_currency)}
                </span>
            )
        },
        {
            key: 'reason',
            label: __('Reason'),
            render: (tx) => <span className="text-slate-600 max-w-[250px] truncate block" title={tx.reason}>{tx.reason}</span>
        },
        {
            key: 'created_at',
            label: __('Date'),
            sortable: true,
            render: (tx) => (
                <span className="text-slate-600 whitespace-nowrap">
                    {new Date(tx.created_at).toLocaleDateString()}
                </span>
            )
        },
    ];

    return (
        <AdminSidebarLayout title={__('Income Transactions')} header={__('Transactions')}>
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900">{__('Income Transactions')}</h2>
                    <p className="text-sm text-slate-500">{__('View all income and related transactions.')}</p>
                </div>
                <Button asChild>
                    <Link href="/admin/transactions/create">{__('Create Transaction')}</Link>
                </Button>
            </div>

            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={transactions.data}
                    pagination={transactions}
                    filters={filters}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle={__('No transactions found')}
                    emptyDescription={__('Try adjusting your search filters.')}
                />
            </div>
        </AdminSidebarLayout>
    );
}
