import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import PageHeader from '@/Components/PageHeader';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/StatusBadge';
import CurrencyDisplay from '@/Components/CurrencyDisplay';
import { format, parseISO } from 'date-fns';
import { ChevronLeft, Info } from 'lucide-react';

export default function Logs({ recurring, logs }) {
    const columns = [
        {
            header: 'Date',
            cell: (row) => (
                <div className="text-sm text-gray-900 font-medium">
                    {format(parseISO(row.executed_at), 'MMM d, yyyy HH:mm')}
                </div>
            )
        },
        {
            header: 'Amount',
            cell: (row) => (
                <CurrencyDisplay amount={row.amount} currency={row.amount_currency} />
            )
        },
        {
            header: 'Business Amount',
            cell: (row) => (
                <CurrencyDisplay amount={row.business_amount} currency={row.business_currency} />
            )
        },
        {
            header: 'Exchange Rate',
            cell: (row) => (
                <div className="text-sm text-gray-500">
                    1 {row.amount_currency} = {row.exchange_rate} {row.business_currency}
                </div>
            )
        },
        {
            header: 'Status',
            cell: (row) => (
                <div className={row.status === 'failed' ? 'bg-red-50 p-1 rounded' : ''}>
                    <StatusBadge status={row.status} variant={row.status === 'failed' ? 'red' : 'green'} />
                </div>
            )
        },
        {
            header: 'Note',
            cell: (row) => (
                <div className={`text-sm ${row.status === 'failed' ? 'text-red-600 font-medium' : 'text-gray-500'}`}>
                    {row.note || '-'}
                </div>
            )
        }
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`${recurring.title} - Execution History`} />

            <div className="p-4 sm:p-8">
                <div className="max-w-7xl mx-auto">
                    <div className="mb-6">
                        <Link
                            href={route('erp.recurring.index')}
                            className="text-sm text-gray-500 hover:text-gray-700 flex items-center"
                        >
                            <ChevronLeft className="w-4 h-4 mr-1" />
                            Back to Recurring Entries
                        </Link>
                    </div>

                    <PageHeader
                        title={`${recurring.title} — Execution History`}
                        description={`Showing execution logs for this ${recurring.type} entry`}
                    />

                    <div className="mt-8">
                        <div className="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <Info className="h-5 w-5 text-blue-400" />
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm text-blue-700">
                                        Recurring entry: <span className="font-bold">{recurring.title}</span>
                                        ({recurring.amount} {recurring.amount_currency}) - {recurring.frequency}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <DataTable
                            columns={columns}
                            data={logs.data}
                            pagination={logs}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
