import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { buttonVariants } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { FileText, CreditCard, Wallet, AlertCircle, Eye, Download } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Invoice {
    id: number;
    uuid: string;
    invoice_number: string;
    amount: number;
    paid_amount: number;
    remaining: number;
    currency: any;
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
    wallet_currency?: any;
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
            label: __('erp.invoice_no'),
            render: (row: Invoice) => (
                <Link
                    href={route('billing.invoices.pay', row.uuid)}
                    className="font-mono text-slate-900 font-semibold hover:text-indigo-600 transition-colors text-[13px]"
                >
                    {row.invoice_number}
                </Link>
            ),
        },
        {
            key: 'issued_at',
            label: __('general.issued'),
            render: (row: Invoice) => (
                <DateDisplay date={row.issued_at} className="text-slate-500 text-[13px]" />
            ),
        },
        {
            key: 'due_date',
            label: __('general.due_date'),
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
            label: __('general.amount'),
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
            label: __('general.paid'),
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
            label: __('general.remaining'),
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
            label: __('general.status'),
            render: (row: Invoice) => <StatusBadge status={row.status} />,
        },
        {
            key: 'actions',
            label: '',
            className: 'text-end w-[100px]',
            render: (row: Invoice) => (
                <div className="flex items-center justify-end gap-2">
                    {row.status !== 'paid' && row.status !== 'cancelled' && row.status !== 'refunded' ? (
                        <Link
                            href={route('billing.invoices.pay', row.uuid)}
                            className={buttonVariants({
                                variant: 'default',
                                size: 'sm',
                                className: 'bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs px-3 shadow-sm h-8 inline-flex items-center'
                            })}
                        >
                            <CreditCard className="me-1.5 h-3.5 w-3.5" /> {__('payment.pay')}
                        </Link>
                    ) : (
                        <Link
                            href={route('billing.invoices.pay', row.uuid)}
                            className={buttonVariants({
                                variant: 'ghost',
                                size: 'icon-sm',
                                className: 'text-slate-400 hover:text-slate-600 inline-flex items-center justify-center'
                            })}
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                    )}
                    <a
                        href={route('billing.invoices.pdf', row.uuid)}
                        className={buttonVariants({
                            variant: 'ghost',
                            size: 'icon-sm',
                            className: 'text-slate-400 hover:text-slate-600 inline-flex items-center justify-center'
                        })}
                        target="_blank"
                        rel="noreferrer"
                        title={__('general.download')}
                    >
                        <Download className="h-4 w-4" />
                    </a>
                </div>
            ),
        },
    ];

    const tableData = invoices?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title={__('erp.billing_invoices')} />
            <div className="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans space-y-8">
                
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('erp.billing_invoices')}</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        {__('erp.view_outstanding_statements_and_settle')}
                    </p>
                </div>

                {/* Dashboard Stats Panel */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Wallet Balance */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{__('erp.wallet_balance')}</span>
                            <span className="text-3xl font-bold text-slate-900 tracking-tight">
                                <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                            </span>
                            <p className="text-xs text-slate-400 leading-normal">
                                {__('erp.your_current_available_balance_which')}
                            </p>
                        </div>
                        <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <Wallet className="w-6 h-6" />
                        </div>
                    </div>

                    {/* Outstanding Balance */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{__('erp.total_outstanding_invoices')}</span>
                            <span className="text-3xl font-bold text-slate-900 tracking-tight">
                                <CurrencyDisplay amount={totalOutstanding} currency={wallet_currency} />
                            </span>
                            <p className="text-xs text-slate-400 leading-normal">
                                {__('erp.settle_outstanding_payments_instantly_with')}
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
                        <h2 className="text-lg font-semibold text-slate-900">{__('billing.billing_history')}</h2>
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
                        emptyTitle={__('erp.no_invoices')}
                        emptyDescription={__('billing.there_are_currently_no_billing')}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
