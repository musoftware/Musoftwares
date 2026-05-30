import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Revenue({ income, cost, filters, businessCurrency }) {
    const handleSearch = (search) => {
        router.get(
            '/admin/transactions',
            { ...filters, search, type: 'revenue', page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleSort = (key) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            '/admin/transactions',
            { ...filters, sort: key, type: 'revenue', direction },
            { preserveState: true, replace: true }
        );
    };

    const incomeColumns = [
        {
            key: 'id',
            label: __('ID'),
            render: (tx) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>
        },
        {
            key: 'user',
            label: __('User'),
            render: (tx) => tx.user ? tx.user.name : '—'
        },
        {
            key: 'business_amount',
            label: __('Income'),
            className: 'text-right',
            render: (tx) => (
                <span className="font-medium font-mono text-green-600">
                    {formatCurrency(tx.business_amount || 0, tx.business_currency_id || 1)}
                </span>
            )
        }
    ];

    const costColumns = [
        {
            key: 'id',
            label: __('ID'),
            render: (tx) => <span className="text-slate-500 font-mono text-xs">#{tx.id}</span>
        },
        {
            key: 'user',
            label: __('User'),
            render: (tx) => tx.user ? tx.user.name : '—'
        },
        {
            key: 'business_amount',
            label: __('Cost'),
            className: 'text-right',
            render: (tx) => (
                <span className="font-medium font-mono text-red-600">
                    {formatCurrency(tx.business_amount || 0, tx.business_currency_id || 1)}
                </span>
            )
        }
    ];

    return (
        <AdminSidebarLayout title={__('Revenue')} header={__('Transactions')}>
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900">{__('Revenue Summary')}</h2>
                    <p className="text-sm text-slate-500">{__('View combined income and costs.')}</p>
                </div>
                <Button asChild>
                    <Link href="/admin/transactions/create">{__('Create Transaction')}</Link>
                </Button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 className="text-md font-medium text-slate-800 mb-3">{__('Income')}</h3>
                    <DataTable
                        columns={incomeColumns}
                        data={income.data}
                        pagination={income}
                        filters={filters}
                        onSearch={handleSearch}
                        onSort={handleSort}
                        emptyTitle={__('No income found')}
                    />
                </div>
                <div>
                    <h3 className="text-md font-medium text-slate-800 mb-3">{__('Costs')}</h3>
                    <DataTable
                        columns={costColumns}
                        data={cost.data}
                        pagination={cost}
                        filters={filters}
                        onSearch={handleSearch}
                        onSort={handleSort}
                        emptyTitle={__('No costs found')}
                    />
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
