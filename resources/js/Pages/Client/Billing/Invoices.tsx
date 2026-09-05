import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/Components/ui/DataTable';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { FileText, CreditCard, Wallet, Eye, Download, Plus, ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Invoice {
    id: number;
    uuid: string;
    invoice_number: string;
    amount: number;
    paid_amount: number;
    remaining: number;
    wallet_amount?: number;
    wallet_remaining?: number;
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
    total_outstanding?: number;
    client_balance?: number;
    wallet_currency?: any;
}

export default function Invoices({
    auth,
    invoices,
    unpaid_invoices = [],
    paid_invoices = [],
    total_outstanding,
    client_balance = 0.0,
    wallet_currency = 'USD',
}: IndexProps) {
    const totalOutstanding = total_outstanding !== undefined
        ? total_outstanding
        : unpaid_invoices
            .filter((inv) => inv.status === 'unpaid' || inv.status === 'partially_paid')
            .reduce((sum, inv) => sum + inv.remaining, 0);

    const columns = [
        {
            key: 'invoice_number',
            label: __('erp.invoice_no'),
            render: (row: Invoice) => (
                <Link
                    href={route('billing.invoices.pay', row.uuid)}
                    className="font-mono text-[#1d1d1f] font-semibold hover:text-[#0071e3] transition-colors text-xs sm:text-sm"
                >
                    {row.invoice_number}
                </Link>
            ),
        },
        {
            key: 'issued_at',
            label: __('general.issued'),
            render: (row: Invoice) => (
                <DateDisplay date={row.issued_at} className="text-[#1d1d1f]/60 text-xs font-sans" />
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
                        className={isOverdue ? 'text-rose-600 font-semibold text-xs' : 'text-[#1d1d1f]/60 text-xs'}
                    />
                );
            },
        },
        {
            key: 'amount',
            label: __('general.amount'),
            render: (row: Invoice) => {
                const isDifferentCurrency =
                    row.wallet_amount !== undefined &&
                    row.currency &&
                    wallet_currency &&
                    ((row.currency.id && wallet_currency.id && row.currency.id !== wallet_currency.id) ||
                     (row.currency.currency && wallet_currency.currency && row.currency.currency !== wallet_currency.currency));

                return (
                    <CurrencyDisplay
                        amount={row.amount}
                        currency={row.currency}
                        businessAmount={isDifferentCurrency ? row.wallet_amount : undefined}
                        businessCurrency={wallet_currency}
                        className="font-bold text-[#1d1d1f] text-xs sm:text-sm font-mono"
                    />
                );
            },
        },
        {
            key: 'paid_amount',
            label: __('general.paid'),
            render: (row: Invoice) => (
                <CurrencyDisplay
                    amount={row.paid_amount}
                    currency={row.currency}
                    className="font-medium text-[#1d1d1f]/60 text-xs font-mono"
                />
            ),
        },
        {
            key: 'remaining',
            label: __('general.remaining'),
            render: (row: Invoice) => {
                const isDifferentCurrency =
                    row.wallet_remaining !== undefined &&
                    row.currency &&
                    wallet_currency &&
                    ((row.currency.id && wallet_currency.id && row.currency.id !== wallet_currency.id) ||
                     (row.currency.currency && wallet_currency.currency && row.currency.currency !== wallet_currency.currency));

                return (
                    <CurrencyDisplay
                        amount={row.remaining}
                        currency={row.currency}
                        businessAmount={isDifferentCurrency ? row.wallet_remaining : undefined}
                        businessCurrency={wallet_currency}
                        className="font-bold text-[#1d1d1f] text-xs sm:text-sm font-mono"
                    />
                );
            },
        },
        {
            key: 'status',
            label: __('general.status'),
            render: (row: Invoice) => <StatusBadge status={row.status} />,
        },
        {
            key: 'actions',
            label: '',
            className: 'text-end w-[110px]',
            render: (row: Invoice) => (
                <div className="flex items-center justify-end gap-1.5">
                    {row.status !== 'paid' && row.status !== 'cancelled' && row.status !== 'refunded' ? (
                        <Link
                            href={route('billing.invoices.pay', row.uuid)}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs px-3 py-1.5 rounded-[980px] shadow-sm shadow-blue-500/20 inline-flex items-center gap-1 transition-all"
                        >
                            <CreditCard className="w-3 h-3" />
                            <span>{__('payment.pay')}</span>
                        </Link>
                    ) : (
                        <Link
                            href={route('billing.invoices.pay', row.uuid)}
                            className="w-8 h-8 rounded-full bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f]/60 hover:text-[#1d1d1f] inline-flex items-center justify-center transition-colors"
                            title="View"
                        >
                            <Eye className="w-3.5 h-3.5" />
                        </Link>
                    )}
                    <a
                        href={route('billing.invoices.pdf', row.uuid)}
                        className="w-8 h-8 rounded-full bg-[#f5f5f7] hover:bg-black/5 text-[#1d1d1f]/60 hover:text-[#1d1d1f] inline-flex items-center justify-center transition-colors"
                        target="_blank"
                        rel="noreferrer"
                        title={__('general.download')}
                    >
                        <Download className="w-3.5 h-3.5" />
                    </a>
                </div>
            ),
        },
    ];

    const tableData = invoices?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title={`${__('erp.billing_invoices')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_dashboard')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('erp.billing_invoices')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('erp.view_outstanding_statements_and_settle')}
                            </p>
                        </div>

                        <Link
                            href={route('financial.add-balance')}
                            className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer shrink-0"
                        >
                            <Plus className="w-4 h-4" />
                            <span>{__('general.charge_balance')}</span>
                        </Link>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* Dashboard Bento Stats Panel */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {/* 1. Wallet Balance */}
                        <div className="bg-white p-6 sm:p-7 rounded-[24px] border border-black/5 shadow-sm flex items-center justify-between group hover:border-[#0071e3]/30 hover:shadow-md transition-all">
                            <div className="space-y-2">
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block">
                                    {__('erp.wallet_balance')}
                                </span>
                                <span className="text-3xl sm:text-4xl font-bold text-[#1d1d1f] tracking-tight block">
                                    <CurrencyDisplay amount={client_balance} currency={wallet_currency} />
                                </span>
                                <p className="text-xs text-[#1d1d1f]/60 leading-normal max-w-sm">
                                    {__('erp.your_current_available_balance_which')}
                                </p>
                            </div>
                            <div className="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 flex items-center justify-center shrink-0 ms-4">
                                <Wallet className="w-7 h-7" />
                            </div>
                        </div>

                        {/* 2. Outstanding Balance */}
                        <div className="bg-white p-6 sm:p-7 rounded-[24px] border border-black/5 shadow-sm flex items-center justify-between group hover:border-amber-500/30 hover:shadow-md transition-all">
                            <div className="space-y-2">
                                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 block">
                                    {__('erp.total_outstanding_invoices')}
                                </span>
                                <span className="text-3xl sm:text-4xl font-bold text-[#1d1d1f] tracking-tight block">
                                    <CurrencyDisplay amount={totalOutstanding} currency={wallet_currency} />
                                </span>
                                <p className="text-xs text-[#1d1d1f]/60 leading-normal max-w-sm">
                                    {__('erp.settle_outstanding_payments_instantly_with')}
                                </p>
                            </div>
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] flex items-center justify-center shrink-0 ms-4">
                                <FileText className="w-7 h-7" />
                            </div>
                        </div>

                    </div>

                    {/* Invoices List Table Card */}
                    <div className="bg-white rounded-[24px] border border-black/5 shadow-sm p-6 sm:p-8 space-y-6">
                        <div className="flex items-center justify-between border-b border-black/5 pb-4">
                            <div>
                                <h2 className="text-base font-bold text-[#1d1d1f] font-sans">
                                    {__('billing.billing_history')}
                                </h2>
                                <p className="text-xs text-[#1d1d1f]/60 mt-0.5">
                                    Track all issued and settled invoices with official tax statements.
                                </p>
                            </div>
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

            </div>
        </AuthenticatedLayout>
    );
}
