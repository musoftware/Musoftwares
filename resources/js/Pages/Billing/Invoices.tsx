import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { buttonVariants } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { FileText, CreditCard, Wallet, AlertCircle, Eye } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Invoice {
    id: number;
    uuid: string;
    invoice_number: string;
    amount: number;
    paid_amount: number;
    remaining: number;
    currency: string;
    status: string;
    due_date: string;
    issued_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface IndexProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    invoices?: {
        data: Invoice[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
        per_page: number;
        links: PaginationLink[];
    };
    unpaid_invoices?: Invoice[];
    paid_invoices?: Invoice[];
    client_balance?: number;
    wallet_currency?: string;
}

export default function Invoices({
    auth,
    invoices,
    unpaid_invoices = [],
    paid_invoices = [],
    client_balance = 0.0,
    wallet_currency = 'USD',
}: IndexProps) {
    const totalOutstanding = unpaid_invoices.reduce((sum, inv) => sum + inv.remaining, 0);

    const columns = [
        {
            key: 'invoice_number',
            label: __('Invoice No'),
            render: (row: Invoice) => (
                <Link
                    href={route('billing.invoices.pay', row.id)}
                    className="font-mono text-slate-900 font-semibold hover:text-indigo-600 transition-colors text-[13px]"
                >
                    {row.invoice_number}
                </Link>
            ),
        },
        {
            key: 'issued_at',
            label: __('Issued'),
            render: (row: Invoice) => (
                <DateDisplay date={row.issued_at} className="text-slate-500 text-[13px]" />
            ),
        },
        {
            key: 'due_date',
            label: __('Due Date'),
            render: (row: Invoice) => {
                const isOverdue =
                    row.due_date &&
                    new Date(row.due_date) < new Date() &&
                    row.status !== 'paid';
                return (
                    <DateDisplay
                        date={row.due_date}
                        className={isOverdue ? 'text-red-600 font-semibold text-[13px]' : 'text-slate-500 text-[13px]'}
                    />
                );
            },
        },
        {
            key: 'amount',
            label: __('Amount'),
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.amount}
                    currency={row.currency}
                    className="font-semibold text-slate-900 text-[13px]"
                />
            ),
        },
        {
            key: 'paid_amount',
            label: __('Paid'),
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.paid_amount}
                    currency={row.currency}
                    className="font-medium text-slate-500 text-[13px]"
                />
            ),
        },
        {
            key: 'remaining',
            label: __('Remaining'),
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.remaining}
                    currency={row.currency}
                    className="font-semibold text-slate-900 text-[13px]"
                />
            ),
        },
        {
            key: 'status',
            label: __('Status'),
            render: (row: Invoice) => <StatusBadge status={row.status} />,
        },
        {
            key: 'actions',
            label: '',
            className: 'text-right w-[100px]',
            render: (row: Invoice) => (
                <div className="flex items-center justify-end gap-2">
                    {row.status !== 'paid' && row.status !== 'cancelled' && row.status !== 'refunded' ? (
                        <Link
                            href={route('billing.invoices.pay', row.id)}
                            className={buttonVariants({
                                variant: 'default',
                                size: 'sm',
                                className: 'bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs px-3 shadow-sm h-8 inline-flex items-center'
                            })}
                        >
                            <CreditCard className="mr-1.5 h-3.5 w-3.5" /> {__('Pay')}
                        </Link>
                    ) : (
                        <Link
                            href={route('billing.invoices.pay', row.id)}
                            className={buttonVariants({
                                variant: 'ghost',
                                size: 'icon-sm',
                                className: 'text-slate-400 hover:text-slate-600 inline-flex items-center justify-center'
                            })}
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                    )}
                </div>
            ),
        },
    ];

    const tableData = invoices?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title={__('Billing & Invoices')} />
            <div className="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans space-y-8">
                
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('Billing & Invoices')}</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        {__('View outstanding statements and settle balances securely using your wallet.')}
                    </p>
                </div>

                {/* Dashboard Stats Panel */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Wallet Balance */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{__('Wallet Balance')}</span>
                            <span className="text-3xl font-bold text-slate-900 tracking-tight">
                                <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                            </span>
                            <p className="text-xs text-slate-400 leading-normal">
                                {__('Your current available balance, which can be used to settle outstanding invoices and platform services.')}
                            </p>
                        </div>
                        <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <Wallet className="w-6 h-6" />
                        </div>
                    </div>

                    {/* Outstanding Balance */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{__('Total Outstanding Invoices')}</span>
                            <span className="text-3xl font-bold text-slate-900 tracking-tight">
                                <CurrencyDisplay amount={totalOutstanding} currency={wallet_currency} />
                            </span>
                            <p className="text-xs text-slate-400 leading-normal">
                                {__('Settle outstanding payments instantly with your wallet balance.')}
                            </p>
                        </div>
                        <div className="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <FileText className="w-6 h-6" />
                        </div>
                    </div>
                </div>

                {/* Invoices List Table */}
                <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-slate-900">{__('Billing History')}</h2>
                    </div>

                    <DataTable
                        columns={columns}
                        data={tableData}
                        pagination={
                            invoices?.links
                                ? {
                                      current_page: invoices.current_page,
                                      last_page: invoices.last_page,
                                      total: invoices.total,
                                      from: invoices.from,
                                      to: invoices.to,
                                      per_page: invoices.per_page,
                                      links: invoices.links,
                                  }
                                : undefined
                        }
                        emptyIcon={FileText}
                        emptyTitle={__('No invoices')}
                        emptyDescription={__('There are currently no billing statements associated with your account.')}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
